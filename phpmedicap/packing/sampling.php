<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "getPendingFGSampling") {
    //     $output = array();
    //   echo  $sql = "SELECT b.*, p.product_name, p.dosage_form, p.grade FROM bpr b LEFT JOIN product p ON b.product_code=p.product_code WHERE b.bpr_no NOT IN (SELECT document_no FROM samplingfg)";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    
          $output = array();
       
         $sql ="SELECT  b.mfr_no,
                        a.actual_yeild,
                        a.id,
                        a.batch_number,
                        a.work_order_no,
                        a.rm_qa_dislc_date AS palnned_by,
                        a.approved_by,
                        a.lod_status,
                        a.calculation_type,
                        a.batch_commence_date,
                        a.stage_checked_by,
                        a.tr_to_packing_dept_by,
                        a.batch_complete_date,
                        a.stability,
                        a.stability_reason,
                        a.process_validation,
                        a.qa_person,
                        a.qa_date,
                        a.no_of_lots,
                        uf.min_per AS min_yeild,
                        uf.max_per AS max_yeild,
                        a.approved_by,
                        b.plan_no,
                        b.bfr_no,
                        b.mfr_no,
                        b.product_code,
                        b.batch_size,
                        p.product_name,
                        p.product_type,
                        p.grade,
                        b.pack_size,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        a.pm_qa_dislc_status AS lc_status,
                        a.pm_qa_dislc_by AS lc_by,
                        a.pm_qa_dislc_date AS lc_date,
                        ps.product_type
                    FROM
                        mfg_work_order_hdr a
                    JOIN batch_planning b ON
                        a.batch_plan_id = b.id AND a.plant_id = b.plant_id
                    JOIN product p ON
                        b.product_code = p.product_code AND b.plant_id = p.plant_id
                    LEFT JOIN unitformula uf ON
                        uf.mfr_no = b.mfr_no AND uf.plant_id = b.plant_id
                    LEFT JOIN packing_stage ps ON
                        p.product_code = ps.product_code
             where  a.plant_id ='".$_GET["plant_id"]."'
             and dispensing_status ='Request Sent'   and a.receiving='done' and a.sampling_intimation!='' 
             GROUP BY
                        
                        a.id,
                        a.batch_number,
                        a.work_order_no,
                        palnned_by,
                        a.approved_by,
                        a.lod_status,
                        a.calculation_type,
                        a.batch_commence_date,
                        a.stage_checked_by,
                        a.tr_to_packing_dept_by,
                        a.batch_complete_date,
                        a.stability,
                        a.stability_reason,
                        a.process_validation,
                        a.qa_person,
                        a.qa_date,
                        a.no_of_lots,
                        min_yeild,
                        max_yeild,
                        a.approved_by,
                        b.plan_no,
                        b.bfr_no,
                        b.mfr_no,
                        b.product_code,
                        b.batch_size,
                        p.product_name,
                        p.product_type,
                        p.grade,
                        b.pack_size,
                        b.pack_unit,
                        a.dispense_request_sent_by,
                        a.dispense_request_sent_on,
                        a.dispensing_status,
                        rm_disp_completed_by,
                        lc_status,
                        lc_by,
                        lc_date,
                        ps.product_type";
            
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
            
    }
    else if ($_GET["type"] == "sendIntimation") {
        $sql = "INSERT INTO samplingfg (plant_id,document_no, document_type, product_code, batch_no, batch_size, batch_qty, unit, mfg_date, exp_date, retest_date, prepared_by, prepared_date) VALUES ('".$_GET["plant_id"]."','".$input["mfr_no"]."','BMR','".$input["product_code"]."','".$input["batch_number"]."','".$input["batch_size"]."','".$input["actual_yeild"]."', '".$input["unit"]."','".$input["mfg_date"]."','".$input["exp_date"]."','".$input["retest_date"]."','".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
 $sql="insert into fg_stock_book(material_code,batch_no,product_type,batch_size,plant_id) VALUES('".$input["product_code"]."','".$input["batch_no"]."'
            ,'".$input["product_type"]."','".$input["batch_size"]."','".$_GET["plant_id"]."')";        
            $conn->query($sql);
           
            // echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "sendIntimation_saipro") {
         $sql = "INSERT INTO samplingfg (plant_id,document_no, document_type, product_code, batch_no, batch_size, batch_qty, unit, mfg_date, exp_date, retest_date, prepared_by, prepared_date,work_order_no,work_order_id,sp_bmr_sifting_id) VALUES 
                ('".$_GET["plant_id"]."','".$input["mfr_no"]."','BMR','".$input["product_code"]."','".$input["batch_number"]."','".$input["batch_size"]."',
                '".$input["actual_yeild"]."', '".$input["unit"]."','".$input["mfg_date"]."','".$input["exp_date"]."','".$input["retest_date"]."','".$_GET["emp_id"]."',
                '$entry_date','".$input["work_order_no"]."','".$_GET["work_id"]."','".$_GET["sift_id"]."')";
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
 $sql="insert into fg_stock_book(material_code,batch_no,product_type,batch_size,plant_id) VALUES('".$input["product_code"]."','".$input["batch_no"]."'
            ,'".$input["product_type"]."','".$input["batch_size"]."','".$_GET["plant_id"]."')";        
            $conn->query($sql);
 $sql1="update sp_bmr_sifting set fg_sampling_intimation='sent',fg_intimation_raised_by='".$_GET["emp_id"]."',	fg_intimation_raised_date='$entry_date' where id ='".$_GET["sift_id"]."'";        
//  $sql1="update mfg_work_order_hdr set fg_sampling_intimation='sent',fg_intimation_raised_by='".$_GET["emp_id"]."',	fg_intimation_raised_date='$entry_date' where id ='".$input["id"]."'";        
            $conn->query($sql1);
           
            // echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getIntimationLog") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.dosage_form 
        FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.document_type='BMR' 
        AND DATE(s.prepared_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingIntimations") {
        $output = array();
    //   echo $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.dosage_form, e.firstname as prepared_by FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code LEFT JOIN employee e ON s.prepared_by=e.emp_id WHERE s.document_type='BMR' AND s.status='pending' ORDER BY s.id DESC";
      
      $sql="SELECT
                    s.*,
                    DATE(s.prepared_date) AS prepared_date,
                    p.product_name,
                    p.grade,
                    p.dosage_form,
                    e.firstname AS prepare_by,
                    c.lot
                FROM
                    samplingfg s
                LEFT JOIN product p ON
                    s.product_code = p.product_code
                LEFT JOIN employee e ON
                    s.prepared_by = e.emp_id
                    left join sp_bmr_sifting c on s.sp_bmr_sifting_id=c.id
                WHERE
                    s.document_type = 'BMR' AND s.intimation_status = 'receive' and s.status!='approve'
                GROUP BY
                    s.id,
                    p.product_name,
                    p.grade,
                    p.dosage_form,
                    prepare_by";
      $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkIntimation") {
        $sql = "UPDATE samplingfg SET status='".$_GET["status"]."', checked_by='".$_GET["emp_id"]."', checked_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingQAIntimations") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.document_type='BMR' AND s.status='approve' AND s.intimation_status='pending' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "receiveIntimation") {
        $sql = "UPDATE samplingfg SET intimation_status='receive', received_by='".$_GET["emp_id"]."', received_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingWithdrawals") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.document_type='BMR' AND s.status='approve' AND s.intimation_status='receive' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM samplingfg WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["chemical_analysis"] = $row1["chemical_analysis"];
                        $row["microbiology_analysis"] = $row1["microbiology_analysis"];
                        $row["reserve_analysis"] = $row1["reserve_analysis"];
                        $row["sample_qty"] = $row1["total_qty"];
                    }
                } else {
                    $row["chemical_analysis"] = 0;
                    $row["microbiology_analysis"] = 0;
                    $row["reserve_analysis"] = 0;
                    $row["sample_qty"] = 0;
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "withdrawSample") {
        $sql = "UPDATE samplingfg SET intimation_status='withdraw',chemical_analysis='".$input["chemical_analysis"]."', microbiology_analysis='".$input["microbiology_analysis"]."', reserve_analysis='".$input["reserve_analysis"]."', additional_sample='".$input["additional_sample"]."', total_qty='".$input["total_qty"]."', withdrawal_by='".$_GET["emp_id"]."', withdrawal_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getAwaitingTransferMaterials") {
        $output = array();
        $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.document_type='BMR' AND s.status='approve' AND s.release_status='Approved' AND s.transfer='pending' ORDER BY s.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "transferMaterial") {
        $sql = "UPDATE samplingfg SET transfer='done', transfer_by='".$_GET["emp_id"]."', transfer_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $sql = "INSERT INTO finish_product (company_unit, product_code, batch_no, mfg_date, exp_date, batch_size, qty, unit, status, entry_by, entry_date) VALUES ('".$input["company_unit"]."', '".$input["product_code"]."', '".$input["batch_no"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["batch_size"]."', '".$input["batch_qty"]."', '".$input["unit"]."', 'approve', '".$_GET["emp_id"]."', '$entry_date')";
            $conn->query($sql);
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "downloadIntimationSlip") {
        $_GET['filename'] = 'Intimation slip'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Intimation slip</h2>
        <table border="1" cellpadding="5">
                 <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Sr.No</td>
                    <td style="width:10%;">BMR No</td>
                    <td style="width:15%;">Product Type</td>
                    <td style="width:15%;">Product Name</td>
                    <td style="width:15%;">Batch No</td>
                    <td style="width:10%;">Batch Size</td>
                    <td style="width:10%;">Batch qty</td>
                    <td style="width:15%;">Retest Date</td>
                 </tr>';
                 $output = array();
                 $sql = "SELECT s.*, DATE(s.prepared_date) as prepared_date, p.product_name, p.grade, p.product_type FROM samplingfg s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.document_type='BMR' ";
                 $result = $conn->query($sql);
                 $i=1;
                 if ($result->num_rows > 0) {
                     while ($row = $result->fetch_assoc()) {
                         $output[] = $row;
    
        $html.='<tr>
                    <td style="width:10%;">'.$i.'</td>
                    <td style="width:10%;">'.$row['document_no'].'</td>
                    <td style="width:15%;">'.$row['product_type'].'</td>
                    <td style="width:15%;">'.$row['product_name'].'</td>
                    <td style="width:15%;">'.$row['batch_no'].'</td>
                    <td style="width:10%;">'.$row['batch_size'].'</td>
                    <td style="width:10%;">'.$row['batch_qty'].'</td>
                    <td style="width:15%;">'.$row['retest_date'].'</td>
                </tr>
           ';
           $i++;
             }
        }
    
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('IntimationSlip.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadIntimationRecord") {
        $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= '<h3 style="text-align: center;">INTIMATION SLIP FOR SAMPLING OF FINISHED PRODUCT</h3>';
        $html.='<h4 style="text-align: left;">Format No.: QA039/F/01-05</h4><br>
                <table border="1" cellpadding="3">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:100%;"><b>A. Production Part:</b></td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Product Name:</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:75%;">'.$row[''].'</td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Product Grade</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:75%;">'.$row[''].'</td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Lot/Batch Number</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:75%;">'.$row[''].'</td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Lot/Batch Quantity</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:75%;">'.$row[''].' '.$row[""].'</td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Lot/Batch Size</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:75%;">'.$row[''].' '.$row[""].'</td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Mfg.Date</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:75%;"></td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Exp./Retest Date</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:75%;">'.$row[''].'</td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Prepared By/Date:</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:75%;">'.$row[''].' / '.$row[''].'</td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Checked By/Date:</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:25%;">'.$row[''].' / '.$row[''].'</td>
                        <td style="width:23%;"><b>Time</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:25%;">'.$row[''].'</td>
                    </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:100%;"><b>B. Quality Assurance part</b></td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Sample Intimation Received By/Date</b></td>
                        <td style="width:2%;">:</td>
                        <td style="width:25%;">'.$row[''].'/'.$row[''].'</td>
                        <td style="width:23%;"><b>Time</b></td>
                        <td style="width:2%;"></td>
                        <td style="width:25%;">'.$row[''].'</td>
                    </tr>
                    <tr>
                        <td style="width:23%;"><b>Sample Done By/Date</b></td>
                        <td  style="width:2%;">:</td>
                        <td style="width:25%;">'.$row[''].'</td>
                        <td style="width:23%;"><b>Time</b></td>
                        <td style="width:2%;"></td>
                        <td style="width:25%;">'.$row[''].'</td>
                    </tr>
                    <tr>
                        <td style="width:100%;"><b>Sample Quantity:</b><br>
                            <table border="1" cellpadding="5">
                                <tr>
                                    <td style="width:25%;"><b>Chemical Analysis</b></td>
                                    <td style="width:25%;"><b>Microbial Analysis</b></td>
                                    <td style="width:25%;"><b>Reserve Sample</b></td>
                                    <td style="width:25%;"><b>Additional Sample</b></td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">'.$row[''].'</td>
                                    <td style="width:25%;">'.$row[''].'</td>
                                    <td style="width:25%;">'.$row[''].'</td>
                                    <td style="width:25%;">'.$row[''].'</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;"><b>Total Sample Quantity:</b>'.$row[''].'</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
            <td style="width:100%;"><b>c.Quality Control Part:</b></td>
        </tr>
        <tr>
            <td rowspan="2" style="width:15%;"><b>Sample Received By:</b></td>
            <td rowspan="2" style="width:15%;">:</td>
            <td style="width:20%;"><b>Quality Control Part:</b></td>
            <td style="width:15%;"></td>
            <td style="width:20%;"><b>Microbiologist:</b></td>
            <td style="width:15%;"></td>
        </tr>
        <tr>
            <td style="width:17%;"><b>Time:</b></td>
            <td style="width:18%;"><b>Sign/Date:</b></td>
            <td style="width:17%;"><b>Time:</b></td>
            <td style="width:18%;"><b>Sign/Date:</b></td>
        </tr>
        <tr>
            <td style="width:100%;"><b>A.R.No</b>'.$row[''].'</td>
        </tr>
        <tr>
            <td style="width:100%;"><b>Batch Analysis Status:</b>Completed/Not Completed</td>
        </tr>
        <tr>
            <td style="width:70%;"><b>Batch Analysis Completion Date:</b></td>
            <td style="width:30%;"><b>Time</b></td>
        </tr>
        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
            <td style="width:100%;"><b>D.Quality Assurance part:</b></td>
        </tr>
        <tr>
            <td style="width:100%;"><b>Lot/Batch status:</b>Approved/Rejected</td>
        </tr>
        <tr>
            <td style="width:100%;"><b>Approved By/Date</b></td>
        </tr>
            ';
    
        $html.="</table>";
            
        

        $pdf->writeHTML($html, true, false, false, false, '');
         $pdf->Output('Dispensing Record.pdf', 'I');
    }

}

$conn->close();
?>