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
    
    if ($_GET["type"] == "getPendingPackingDispensing") {
        $output = array();
        $sql = "SELECT l.*, p.dosage_form, p.product_name, p.grade, b1.packing_materials FROM bpr l 
        LEFT JOIN product p ON l.product_code=p.product_code LEFT JOIN unitformula b1 ON l.mfr_no=b1.mfr_no 
        WHERE l.status='pending' AND l.bpr_no NOT IN (SELECT document_no FROM dispensing 
        WHERE dispensing_for='PACKING')";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["packing_materials"] = json_decode($row["packing_materials"]);
                
                $materials = $row["packing_materials"];
                for ($i = 0; $i < count($materials); $i++) {
                    $material = $materials[$i];
                    $sql1 = "SELECT * FROM material WHERE material_code='".$material->material_code."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $material->material_name = $row1["material_name"];
                            $material->grade = $row1["grade"];
                        }
                    }
                    $materials[$i] = $material;
                }
                $row["packing_materials"] = $materials;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "sendPackingDispensingRequest") {
        $sql = "INSERT INTO dispensing (dispensing_for, document_no, product_code, batch_no, batch_size, request_by, request_date) VALUES ('PACKING', '".$input["bpr_no"]."', '".$input["product_code"]."', '".$input["batch_no"]."', '".$input["batch_size"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            
            $materials = $input["packing_materials"];
            for ($i = 0; $i < count($materials); $i++) {
                $material = $materials[$i];
                if ($material['qty'] > 0) {
                    $sql1 = "INSERT INTO dispensing_materials (user_no, dispensing_no, material_code, qty, batch_qty, unit) VALUES ('".$_GET["user_no"]."', '$last_id', '".$material["material_code"]."', '".$material["qty"]."', '".$material["qty"]."', '".$material["unit"]."')";
                    $conn->query($sql1);
                }
            }
            echo json_encode(array("status"=>"success","msg"=>"Packing Material Dispensing Request has been send to Store Department!"));
        } else {
            echo json_encode(array("status"=>"failed","msg"=>$conn->error));
        }
    } 
    // else if ($_GET["type"] == "getPackingDispensingReceivings") {
    //     $output = array();
    //     $fifo_method='';
    //     $sql ="select param_value from software_customization where param_label ='Fifo Method' 
    //     and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
    //   $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //         $fifo_method = $row["param_value"];
                
    //         }
            
    //     }
    //     $lod_status='';
    //     $assay_status='';
    //       $sql ="SELECT a.id as a_id,b.id as b_id,a.pm_disp_completed_date,a.pm_store_disp_date,a.bmr_no,a.pm_store_status_entry_by,a.id,a.batch_number,a.no_of_lots,
    //       a.work_order_no,a.rm_qa_dislc_date as 
    //      palnned_by,a.approved_by,a.lod_status,a.calculation_type,
    //      a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
    //      a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,p.dosage_form,b.batch_size,
    //      p.product_name,p.product_type,
    //          p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, 
    //          a.dispense_request_sent_on,a.dispensing_status,a.pm_qa_dislc_status as lc_status,
    //          a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date,
    //       (SELECT COUNT(pm_disp_completed_by) FROM sp_bmr_sifting z WHERE z.work_order_id = a.id and z.pm_disp_completed_by!='0') as pm_disp_completed_by_count

             
    //          FROM
    //          mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
    //          b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
    //          b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'and (a.pm_receiving!='done' or a.pm_receiving=' ')
    //          and (pm_dispensing_status ='Request Sent' or pm_dispensing_status='0') and a.pm_disp_completed_by !='' ";
        
    //     $result = $conn->query($sql);

    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
              
    //             $output[] = $row;
    //         }
    //     } 
        
        
    //     echo json_encode($output);
    // }
    else if ($_GET["type"] == "getPackingDispensingReceivings") {
        $output = array();
        $fifo_method='';
        $sql ="select param_value from software_customization where param_label ='Fifo Method' 
        and module ='Dispensing Activity' and plant_id = '".$_GET["plant_id"]."' ";
      
      $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $fifo_method = $row["param_value"];
                
            }
            
        }
        $lod_status='';
        $assay_status='';
          $sql ="SELECT b.id as b_id,a.pm_disp_completed_date,a.pm_store_disp_date,a.bmr_no,a.pm_store_status_entry_by,a.id,a.batch_number,a.no_of_lots,a.work_order_no,a.rm_qa_dislc_date as 
         palnned_by,a.approved_by,a.lod_status,a.calculation_type,
         a.stability,a.stability_reason, a.process_validation,a.qa_person,a.qa_date,
         a.approved_by, b.plan_no, b.bfr_no,b.mfr_no,b.product_code,p.dosage_form,b.batch_size,
         p.product_name,p.product_type,
             p.grade,b.pack_size,b.pack_unit,a.dispense_request_sent_by, 
             a.dispense_request_sent_on,a.dispensing_status,a.pm_qa_dislc_status as lc_status,
             a.pm_qa_dislc_by as lc_by,a.pm_qa_dislc_date as lc_date FROM
             mfg_work_order_hdr a JOIN batch_planning b on a.batch_plan_id = 
             b.id and a.plant_id = b.plant_id JOIN product p on b.product_code = p.product_code and 
             b.plant_id = p.plant_id where  a.plant_id ='".$_GET["plant_id"]."'and a.pm_receiving!='done'
             and (pm_dispensing_status ='Request Sent' or pm_dispensing_status='0')  and pm_receiving !='done' and a.pm_disp_completed_by !='' ";
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $calculation_type = $row['calculation_type'];
                $row["checkpoints"] = json_decode($row["checkpoints"]);
                 $sql9 = "select * from batch_planing_raw_material_hdr where batch_plan_id = '".$row["b_id"]."'";
                 $result9 = $conn->query($sql9);
                $pm_id;
        if ($result9->num_rows > 0) {
            while ($row9 = $result9->fetch_assoc()) {
                $pm_id=$row9['id'];
                
            }
        }
                $output1 = array();
                  $sql1="SELECT *,a.batch_qty as b_qty, a.material_code AS m_code,a.id as bpm_id FROM batch_planning_materials a LEFT JOIN material b ON a.material_code = b.material_code  
                  LEFT JOIN mfg_work_order_dtl d ON b.material_code = d.material_code LEFT JOIN batch_planing_raw_material_hdr e ON
                 e.batch_plan_id=d.work_order_id and a.pm_hdr_id=e.id  WHERE 
                  a.material_type = 'Packing Material' AND a.batch_plan_id = '".$row["b_id"]."'  AND d.work_order_id = '".$row["id"]."'";
//                 $sql1="SELECT a.*, b.avbl_stock 
// FROM (
//     SELECT a.*, b.material_code, d.material_type AS m_type, d.category, d.material_subtype, d.material_name, 
//         c.lod_status AS lod_stat, c.assay_status AS assay_stat, b.disp_id AS dispence_id, b.pm_hdr_id, b.batch_qty 
//     FROM mfg_work_order_hdr a 
//     LEFT JOIN batch_planning_materials b ON a.batch_plan_id = b.batch_plan_id 
//     LEFT JOIN mfg_work_order_dtl c ON b.material_code = c.material_code AND c.work_order_id = a.id 
//     LEFT JOIN material d ON b.material_code = d.material_code AND a.plant_id = d.plant_id 
//     LEFT JOIN dispensing_details_hdr e ON a.id = e.lot_id 
//     WHERE a.id = '".$row["id"]."' AND b.pm_hdr_id = '$pm_id' AND b.disp_id='1'
// ) AS a 
// LEFT JOIN (
//     SELECT material_code, SUM(qty) AS avbl_stock 
//     FROM stock_book 
//     WHERE plant_id = '".$_GET["plant_id"]."' AND material_code IN (SELECT material_code FROM work_order_batch_lots WHERE work_order_id = '".$row["id"]."') 
//     GROUP BY material_code
// ) AS b ON a.material_code = b.material_code AND a.material_type = 'Packing Material'
// GROUP BY a.id, a.batch_plan_id, a.material_code, a.lod_stat, a.assay_stat, a.dispence_id, a.pm_hdr_id, a.batch_qty, a.material_type, a.category,
// a.material_subtype, a.material_name, a.plant_id, a.work_order_no, a.rm_qa_dislc_date, a.approved_by, a.calculation_type, a.stability, a.stability_reason,
// a.process_validation, a.qa_person, a.qa_date, a.approved_by";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $category = $row1["category"];
                     /* $sql2="SELECT a.*, IFNULL(b.issued_qty,0) as issued_qty, (a.qty-IFNULL(b.issued_qty,0)) as balance_qty,
                        floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size) as intact_containers,
                        (a.qty-IFNULL(b.issued_qty,0))-(a.pack_size*(floor((a.qty-IFNULL(b.issued_qty,0))/a.pack_size))) as loose_Qty, 
                        case when '".$lod_status."' = 'On Dried Basis' then  ROUND((a.qty-IFNULL(b.issued_qty,0)) -(((a.qty-IFNULL(b.issued_qty,0))*lod_per)/100),2) else 0 end as dry_qty,
                         case when '".$assay_status."' = 'Assay Basis' then  ROUND((((a.qty-IFNULL(b.issued_qty,0))*assay)/100),2) else 0 end as pure_qty
                        from (SELECT IFNULL(SUM(qty), 0) as qty, ar_no ,pack_size ,batch_no,containers,lod_per,assay
                        FROM stock_book
                        WHERE material_code='".$row1["material_code"]."' AND status='Approved' GROUP BY ar_no,pack_size, batch_no,containers,lod_per,assay) a
                        left join (SELECT ar_no, IFNULL(SUM(qty), 0) as issued_qty FROM material_issue Group by ar_no) b on a.ar_no = b.ar_no";*/
                        //echo $sql2;
                        $sql2="select * from dispensing_details_hdr where id = '".$row1["dispence_id"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2 = $row2;
                            }
                        }
                         $row1["gross_total"] = $output2["gross_total"];
                          $row1["net_total"] = $output2["net_total"];
                          $row1["tare_total"] = $output2["tare_total"];
                        $row1["available_ars"] = json_decode($output2["ars"]);
                        $row1["containers"] = json_decode($output2["containers"]);
                       
                        if ($row1["status"] == "pending") {
                            $flag = 1;
                        }
                      
                                      $output1[] = $row1;
                    }
                }
                $row["fifo_method"] =$fifo_method;
                $row["materials"] = $output1;
                $output[] = $row;
            }
        } 
        
        
        echo json_encode($output);
    }
    else if ($_GET["type"] == "receivePackingMaterial") {
    // echo    $sql = "UPDATE dispensing SET receiving='done', receive_by='".$_GET["emp_id"]."', receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
        $sql = "UPDATE dispensing SET receiving='done', receive_by='".$_GET["emp_id"]."', receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    // else if ($_GET["type"] == "receivePackingMaterial_sp") {
    // // echo    $sql = "UPDATE dispensing SET receiving='done', receive_by='".$_GET["emp_id"]."', receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
    //     $sql = "UPDATE sp_bmr_sifting SET pm_receiving='done', pm_receive_by='".$_GET["emp_id"]."', pm_receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // } 
    else if ($_GET["type"] == "receivePackingMaterial_sp") {
    // echo    $sql = "UPDATE dispensing SET receiving='done', receive_by='".$_GET["emp_id"]."', receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
        $sql = "UPDATE mfg_work_order_hdr SET pm_receiving='done', pm_receive_by='".$_GET["emp_id"]."', pm_receive_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "getPackingDispensingLog") {
        $output = array();
        $sql = "SELECT d.*, DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code WHERE d.dispensing_for='PACKING' AND d.company_unit='PLANT-05' AND d.receiving='done' AND DATE(d.receive_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql1 = "SELECT d.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM dispensing_materials d LEFT JOIN material m ON d.material_code=m.material_code WHERE d.dispensing_no='".$row["id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["materials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadFinishDispensingReport") {
        $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= '<h3 style="text-align: center">MATERIAL REQUISITION SLIP</h3>';
        $output = array();
        $sql = "SELECT b.*, p.product_name, p.grade, p.product_type, p1.product_type as material_type, p1.product_name as material_name, p1.grade as material_grade FROM fg_dispensing b LEFT JOIN product p ON b.product_code=p.product_code LEFT JOIN product p1 ON b.material_code=p1.product_code WHERE b.id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='
            <table border="1" cellpadding="3">
                <tr>
                    <td style="width: 100%;">Format No.: WH012/F/01/01</td>
                </tr>
                <tr>
                    <td style="width: 33%;">From: PLANT-05</td>
                    <td style="width: 33%;">Date: '.date('d-m-Y', strtotime($row["request_date"])).'</td>
                    <td style="width: 34%;">Raised By:'.$row['request_by'].'</td>
                </tr>
                <tr>
                    <td style="width: 70%;">Name of Product: '.$row["product_name"].'</td>
                    <td style="width: 30%;">Batch No.: '.$row["batch_no"].'</td>
                </tr>
                <tr style="border: solid 1px black">
                    <td style="width: 4%; text-align:center;">Sr.</td>
                    <td style="width: 34%; text-align:center;">Description</td>
                    <td style="width: 15%; text-align:center;">A. R. No.</td>
                    <td style="width: 12%; text-align:center;">Required Quantity Kg./Lit./Nos.</td>
                    <td style="width: 12%; text-align:center;">Issued Quantity Kg./Lit./Nos.</td>
                    <td style="width: 12%; text-align:center;">No. of Container & Quantity</td>
                    <td style="width: 11%; text-align:center;">Ledger Folio No.</td>
                </tr>';
                $html.='
                <tr>
                    <td style="width: 4%;">1.</td>
                    <td style="width: 34%;">'.$row["material_name"].' '.$row["grade"].'</td>
                    <td style="width: 15%;">'.$row["ar_no"].'</td>
                    <td style="width: 12%;">'.$row["req_qty"].' '.$row["unit"].'</td>
                    <td style="width: 12%;">'.$row["issue_qty"].' '.$row["unit"].'</td>
                    <td style="width: 12%;">NA</td>
                    <td style="width: 11%;">NA</td>
                </tr>';
                $html.='
                <tr>
                    <td style="width: 100%;">Note: Container, Weight and Surrounding area of the balance should be cleaned before and after Weighing.</td>
                </tr>
                <tr>
                    <td style="width: 100%;">Remark:'.$row['remark'].'</td>
                </tr>
                <tr>
                    <td style="width: 25%;">Weight By: '.$row['send_by'].'</td>
                    <td style="width: 25%;">Checked and Issue By: '.$row['accept_by'].'</td>
                    <td style="width: 25%;">Date: '.date('d-m-Y', strtotime($row["send_date"])).'</td>
                    <td style="width: 25%;">Received By: '.$row['receive_by'].'</td>
                </tr>';
            $html.="
            </table>";
           }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Dispensing Record.pdf', 'I');
    }

}

$conn->close();
?>