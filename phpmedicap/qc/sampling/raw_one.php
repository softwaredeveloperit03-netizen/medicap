<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    
    
     if ($_GET["type"] == "getGRNLog") {
         $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name,
        m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN 
        material m ON c.material_code=m.material_code 
        LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no AND m.material_type='Raw Material' 
        WHERE c1.next_stage='GRN_Received' AND c.grn in('inprocess','approve') 
        AND m.material_type='Raw Material' ORDER BY c.id DESC ";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
     } else if ($_GET["type"] == "getPendingAllocation") {
        $output = Array();
         $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM sampling s LEFT JOIN material m ON 
         s.material_code=m.material_code  order by 1 desc"; 
        //$sql = "SELECT s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.material_code, s.batch_no, m.material_subtype,m.material_name, m.grade, s1.specification_no FROM sampling s LEFT JOIN material m ON s.material_code=m.material_codeLEFT JOIN specification s1 ON s.material_code=s1.material_code WHERE s.user_no='".$_GET["user_no"]."'AND s.status='pending'AND m.material_type='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
     } else if ($_GET["type"] == "allocatePerson") {
        $sql = "UPDATE sampling SET sampling_person='".$input["sampling_person"]."', status='inprocess', request_by='".$_GET["emp_id"]."', request_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
     } else if ($_GET["type"] == "getActiveSamplings") {
        $output = Array();
        $sql = "SELECT s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, 
        s.laf_start, s.clearance_status, s.sample_status, s.clearance_no, s.area_details, s.sampling_details, m.material_subtype, 
        m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit FROM sampling s LEFT JOIN material m ON 
        s.material_code=m.material_code LEFT JOIN specification s1 ON s.material_code=s1.material_code order by 1 desc";
        //WHERE s.status='active' AND m.material_type='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
    
                $row["area_details"] = json_decode($row["area_details"]);
                $row["sampling_details"] = json_decode($row["sampling_details"]);
    
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
     } else if ($_GET["type"] == "getCheckedSamplings") {
        $output = Array();
        $sql = "SELECT s.id, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, s.laf_start, s.clearance_status, s.sample_status, s.clearance_no, s.area_details, s.sampling_details, m.material_subtype, m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN specification s1 ON s.material_code=s1.material_code WHERE s.status='checked' AND m.material_type='Packing Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];
                        break;
                    }
                }
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["material_code"] = $row1["material_no"];
                        $temp["batch_no"] = $row1["batch_no"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
    
                $row["area_details"] = json_decode($row["area_details"]);
                $row["sampling_details"] = json_decode($row["sampling_details"]);
    
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
        
     } else if ($_GET["type"] == "getSamplings") {
        $output = Array();
        $sql = "SELECT s.id,s.entry_date,s.sampling_no, s.containers, s.grn_no, s.grn_date, s.mfg_date, s.exp_date, s.containers, s.material_code, s.batch_no, s.laf_start, s.clearance_status, s.sample_status, s.clearance_no, s.area_details, s.sampling_details, m.material_subtype, m.material_name, m.grade, s1.specification_no, s1.sample_qty, s1.unit, s.status FROM sampling s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN specification s1 ON s.material_code=s1.material_code WHERE m.material_type='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
    
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];
                        break;
                    }
                }
    
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["material_code"] = $row1["material_no"];
                        $temp["batch_no"] = $row1["batch_no"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
    
                $row["area_details"] = json_decode($row["area_details"]);
                $row["sample_details"] = json_decode($row["sampling_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }  else if ($_GET["type"] == "updateReceivingGrn") {
        $sql = "UPDATE challan_materials SET status='".$input["status"]."', grncheck_by='".$_GET["emp_id"]."', grncheck_date='$entry_date' ,vendor_coa='".$input["vendor_coa"]."' ,grn_receive_remark ='".$input["grn_receive_remark"]."' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
             $data = $input['batches'];
            for ($i = 0; $i < count($data); $i++) {
                $row1 = $data[$i];
                $sql2 = "UPDATE sampling_batches SET grn_no = '".$input["grn_no"]."' WHERE batch_no = '".$row1["batch_no"]."' ";
                $conn->query($sql2);
               
                 $sql1 = "INSERT INTO sampling (user_no,supplier_no,manufacturer_no,inward_no,material_code,batch_no,ar_no,containers,grn_no,grn_date,mfg_date,exp_date) VALUES
             ('".$_GET["user_no"]."','".$input["vendor_no"]."', '".$input["manufacturer_no"]."', '".$input["inword_no"]."','".$input["material_code"]."', '".$row1["batch_no"]."','".$row1["ar_no"]."', '".$row1["total_containers"]."','".$input["grn_no"]."', '$entry_date', '".$row1["mfg_date"]."', '".$row1["exp_date"]."')";
                $conn->query($sql1);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }else if($_GET["type"] == ''){
         $_GET['filename'] = 'Sampling Report'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        
             $output = Array();
                $sql = "SELECT * FROM sampling WHERE status NOT IN ('pending', 'inprocess') AND id='".$_GET["id"]."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $row["material_type"] = $row1["material_type"];
                                $row["material_name"] = $row1["material_name"];
                                $row["grade"] = $row1["grade"];
                            }
                        
            
                        
            $html.='
            <h2 style="text-align:cenetr">Sampling Report</h2>
            <table cellpadding="5" style="text-align:left;">
                <tr><td style="width:100%"><b>Line Clearance By QA</b></td></tr>
                <tr>
                    <td style="width:25%"><b>Previous Material Name</b></td>
                    <td style="width:75%">'.$row['material_name'].'</td>
                </tr>
                <tr>
                    <td><b>Previous Material GRN No.</b></td>
                    <td>'.$row['grn_no'].'</td>
                </tr>
                <tr>
                    <td style="width:25%"><b>Area Cleaned By</b></td>
                    <td style="width:25%">'.$row['sampling_person'].'</td>
                    <td style="width:25%"><b>Cleaning Date</b></td>
                    <td style="width:25%">'.date("d/m/Y", strtotime($row['entry_date'])).'</td>
                </tr>
                <tr>
                    <td style="width:100%"><b>QA Person has to ensure that all Traces of Previous Material Shall be Cleaned and Ensure that area is cleaned.</b><br><br>
                        <table>
                            <tr>
                                <td style="border:none;"><b>Time of Line Clearance :</b></td>
                                <td style="border:none;"><b>Sign and Date of IPQA</b></td>
                            </tr>
                            <tr>
                                <td style="width:100%; border:none;"><b>Person</b></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr>
                    <td style="width:100%;"><b>Name of Raw Material:</b> '.$row['material_name'].'</td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Item Code :</b> '.$row['material_code'].'</td>
                    <td style="width:50%;"><b>A.R. No :</b> '.$row['ar_no'].'</td>
                </tr>
                <tr>
                    <td><b>Mfg Date:</b> '.date("d/m/Y", strtotime($row['mfg_date'])).'</td>
                    <td><b>Exp Date:</b> '.date("d/m/Y", strtotime($row['exp_date'])).'</td>
                </tr>
                <tr>
                    <td><b>Manufacturer ( Vendor)</b> : '.$row['manufacturer'].'</td>
                    <td><b>Approved Not Approved</b></td>
                </tr>
                <tr><td style="width:100%;"><b>Supplier Name:</b></td></tr>
                <tr><td><b>Cleaning of surrounding area of consignment : </b>'.$row['cleaning_area'].'</td></tr>
                <tr><td><b>Proper consignment label affixed by vendor: </b>'.$row['label_affixed'].'</td></tr>
                <tr><td><b>Material’s Physical Description:</b> '.$row['physical_description'].'</td></tr>
                <tr>
                    <td style="width:50%"><b>Total No. of containers:</b> '.$row['total_containers'].'</td>
                    <td style="width:50%"><b>No. Of Containers Sampled:</b> '.$row['containers'].'</td>
                </tr>
                <tr><td style="width:100%;"><b>Containers number Sampled (For n+1 criteria):</b></td></tr>
                <tr><td><b>Temperature /Rel. Humidity of Sampling Room: </b>'.$row['room_temperature'].'°C / '.$row['humidity'].'%</td></tr>
                <tr><td><b>After Sampling Containers sealed and closed by:</b> '.$row['emp_name'].'</td></tr>
                <tr><td><b>Remark : </b>'.$row['remark'].'</td></tr>
                <tr><td><b>Sampled by :</b> '.$row['emp_name'].' &nbsp;&nbsp;&nbsp;<b> Sampled Date :</b> '.date("d/m/Y", strtotime($row['entry_date'])).'</td></tr>
                <tr><td><b>Reviewed by QA (If any Abnormality) sign/Date:</b></td></tr>
            </table>
            <div></div>';
             }
        }

            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Sampling Report.pdf', 'I');
                }
        
    } else if ($_GET["type"] == "getPendingSamplings") {
    $output = Array();
    $sql = "SELECT * FROM sampling ";
    //WHERE status = 'inprocess' AND sampling_person='".$_GET["emp_id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            if ($row["area_status"] == "complete") {
                $row["area_details"] = json_decode($row["area_details"]);
            }

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }

                $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["material_code"] = $row1["material_no"];
                        $temp["batch_no"] = $row1["batch_no"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            if ($row["specification_no"] == "") {
                $sql1 = "SELECT * FROM specification WHERE material_code='".$row["material_code"]."' AND spec_type='Raw Material Specification' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["specification_no"] = $row1["specification_no"];
                        
                        $sql1 = "UPDATE sampling SET specification_no='".$row["specification_no"]."' WHERE id='".$row["id"]."'";
                        $conn->query($sql1);
                        break;
                    }
                }
            }

            $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["specification_no"] = $row1["specification_no"];
                    $row["composite_qty"] = +$row1["sample_qty"];
                    $row["unit"] = $row1["unit"];

                    $sql2 = "SELECT IFNULL(SUM(sample_qty), 0) as identication_qty FROM spec_tests WHERE specification_no='".$row1["specification_no"]."' AND test='Identification'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["identication_qty"] = +$row2["identication_qty"];
                        }
                    } else {
                        $row["identication_qty"] = 0;
                    }
                    $row["actual_indentification"] = number_format(+$row["identication_qty"] * 2, 2);
                    $row["actual_composite"] = number_format(+$row["composite_qty"] * 2, 2);
                    break;
                }
            }

            if ($row["specification_no"] == "") {
                $row["current_status"] = "Specification not Available";
            } else if ($row["area_status"] == 'pending') {
                $row["current_status"] = 'Area Checkpoints';
            } else if ($row["area_status"] == 'complete' && $row["clearance_status"] == 'pending') {
                $row["current_status"] = 'Line Clearance';
            } else if ($row["area_status"] == 'complete' && $row["clearance_status"] == 'inprocess') {
                $row["current_status"] = 'Line Clearance';
            } else if ($row["clearance_status"] == 'complete' && $row["area_status"] == 'complete' && $row["sample_status"] == 'pending') {
                $row["current_status"] = 'Sampling Information';
                $output1 = Array();
                for ($i = 0; $i < +$row["containers"]; $i++) {
                    $temp = Array();
                    $temp['container_no'] = $i + 1;
                    $temp['identication_qty'] = +$row["identication_qty"];
                    $temp['composite_qty'] = +$row["composite_qty"];
                    $temp['status'] = "pending";
                    $output1[] = $temp;
                }
                $row["container_details"] = $output1;
            }

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getPendingLineClearance") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status = 'inprocess' AND sampling_person='".$_GET["emp_id"]."' AND area_status='complete' AND (clearance_status='pending' OR clearance_status='inprocess')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["area_details"] = json_decode($row["area_details"]);

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE id='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($row1['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }

                $sql1 = "SELECT * FROM lineclearance WHERE id !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["material_code"] = $row1["material_no"];
                        $temp["batch_no"] = $row1["batch_no"];
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            $row["current_status"] = 'Line Clearance';
            
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE grn_no = '".$row["grn_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;

            $output[] = $row;
        }
    }
    echo json_encode($output);
}else if ($_GET["type"] == "callforclearance") {
    $sql = "INSERT INTO lineclearance (department,section,activity,material_no,grn_no,batch_no, checkpoints,request_by,request_date) VALUES ('".$_GET["department"]."','Sampling','".$input["material_type"]." Sampling','".$input["material_code"]."','".$input["grn_no"]."','".$input["batch_no"]."','".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','$entry_date')";
    if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        $sql = "UPDATE sampling SET clearance_no='".$last_id."', clearance_status='inprocess' WHERE id='".$input["id"]."'";
        $conn->query($sql);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}else if ($_GET["type"] == "saveAreaCheckpoints") {
        $sql = "UPDATE sampling SET area_details='".json_encode($input)."', area_status='complete' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingSamplingForm") {
    $output = Array();
    $sql = "SELECT * FROM sampling WHERE status = 'allocate' AND area_status='complete' AND sample_status='pending' AND sampling_person='".$_GET['emp_id']."' ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["material_type"] = $row1["material_type"];
                    $row["material_name"] = $row1["material_name"];
                    $row["grade"] = $row1["grade"];
                }
            }

            $row["area_details"] = json_decode($row["area_details"]);
             $row["sampling_details"] = json_decode($row["sampling_details"]);

            if ($row["clearance_status"] !== 'pending') {
                $temp = Array();
                $sql1 = "SELECT * FROM lineclearance WHERE id='".$row["clearance_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                        $temp["area_cleaned"] = $row1["area_cleaned"];
                        $temp["product_traces"] = $row1["product_traces"];
                        $temp["temperature"] = $row1["temperature"];
                        $temp["humidity"] = $row1["humidity"];
                        $temp["entry_by"] = $row1["entry_by"];
                        $temp["entry_date"] = $row1["entry_date"];
                        $temp["status"] = $row1["status"];

                        if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                            $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                            $conn->query($sql2);
                            $row["clearance_status"] = "complete";
                        }
                        break;
                    }
                }
                $row["clearance_details"] = $temp;
            }

            $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["specification_no"] = $row1["specification_no"];
                    $row["composite_qty"] = +$row1["sample_qty"];
                    $row["unit"] = $row1["unit"];

                    $sql2 = "SELECT IFNULL(SUM(sample_qty), 0) as identication_qty FROM spec_tests WHERE specification_no='".$row1["specification_no"]."' AND test='Identification'";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $row["identication_qty"] = +$row2["identication_qty"];
                        }
                    } else {
                        $row["identication_qty"] = 0;
                    }
                    $row["actual_indentification"] = number_format(+$row["identication_qty"] * 2, 2);
                    $row["actual_composite"] = number_format(+$row["composite_qty"] * 2, 2);
                    break;
                }
            }

            $output1 = Array();
            for ($i = 0; $i < +$row["containers"]; $i++) {
                $temp = Array();
                $temp['container_no'] = $i + 1;
                $temp['identication_qty'] = +$row["identication_qty"];
                $temp['composite_qty'] = +$row["composite_qty"];
                $temp['status'] = "pending";
                $output1[] = $temp;
            }
            $row["container_details"] = $output1;
            
             $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE grn_no = '".$row["grn_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;

            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveSamplingInfo") {
  //  $sql = "UPDATE sampling SET sampling_details='".json_encode($input)."',checklist='".json_encode($input["checklist"])."', sample_status='complete',undertest_qty='".$input["undertest_qty"]."', status='active', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
    $sql = "UPDATE sampling SET sampling_details='".json_encode($input)."',checklist='".json_encode($input["checklist"])."',
     sample_status='complete',undertest_qty='".$input["undertest_qty"]."', 
     sampling_start_time = '".$input["start_time"]."', sampling_end_time = '".$input["stop_date"]."', 
     balance_qeuipment_code = '".$input["balance_eqip_code"]."', laf_qeuipment_code = '".$input["laf_eqip_code"]."', 
     status='active',sampling_person= '".$_GET["emp_id"]."',entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
    echo $sql;
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if($_GET["type"] == 'downloadSamplingsRecord'){
    $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
    
     $sql = "SELECT * FROM sampling WHERE status NOT IN ('pending', 'inprocess') AND id='".$_GET["id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_type"] = $row1["material_type"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
        
                    $temp = Array();
                    $sql1 = "SELECT * FROM lineclearance WHERE clearance_no='".$row["clearance_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $temp["checkpoints"] = json_decode($row1["checkpoints"]);
                            $temp["area_cleaned"] = $row1["area_cleaned"];
                            $temp["product_traces"] = $row1["product_traces"];
                            $temp["temperature"] = $row1["temperature"];
                            $temp["humidity"] = $row1["humidity"];
                            $temp["entry_by"] = $row1["entry_by"];
                            $temp["entry_date"] = $row1["entry_date"];
                            $temp["status"] = $row1["status"];
        
                            if ($temp['status'] == 'active' && $row["clearance_status"] == 'inprocess') {
                                $sql2 = "UPDATE sampling SET clearance_status='complete' WHERE id='".$row["id"]."'";
                                $conn->query($sql2);
                                $row["clearance_status"] = "complete";
                            }
                            break;
                        }
                    }
        
                    $sql1 = "SELECT * FROM lineclearance WHERE clearance_no !='".$row["clearance_no"]."' AND section='Sampling' ORDER BY id DESC";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $temp["material_code"] = $row1["material_no"];
                            $temp["batch_no"] = $row1["batch_no"];
                            break;
                        }
                    }
                    $row["clearance_details"] = $temp;
        
                    $row["area_details"] = json_decode($row["area_details"]);
                    $row["sample_details"] = json_decode($row["sampling_details"]);
                    $sample_details=$row["sample_details"];
                    
                    
                    $output1 = array();
                    
                    $sql1 = "SELECT * FROM sampling_batches WHERE grn_no = '".$row["grn_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row['batch_no']=$row1["batch_no"];
                            $row['ar_no']=$row1["ar_no"];
                            $row['mfg_date']=date('d-m-Y',strtotime($row1["mfg_date"]));
                            $row['exp_date']=date('d-m-Y',strtotime($row1["exp_date"]));
                            $row['total_containers']=$row1["total_containers"];
                            $row['qty_received']=$row1["qty_received"];
                            $row['root_container']=$row1["root_container"];
                             $output1[] = $row1;
                        }
                    }
                    $manufacturer='';
                    $sql1 = "SELECT c1.*,v.vendor_name FROM challan_materials c1 LEFT JOIN vendor v ON v.vendor_no=c1.manufacturer_no WHERE c1.material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $manufacturer= $row1["vendor_name"];
                            //$output1[] = $row1;
                            
                        }
                    }
    //     }
    // }
    // echo json_encode($output);
    // $sql = "SELECT * FROM sampling WHERE id='".$_GET["id"]."'";
    // $result = $conn->query($sql);
    // if ($result->num_rows > 0) {
    //     while ($row = $result->fetch_assoc()) {
    
     $html.='<h3 style="text-align:center;">CHECK LIST FOR SAMPLING</h3>
             <table border="1" cellpadding="5" >
                <tr>
                   <td>
                        <table cellpadding="5">
                            <tr>
                                <td style="width:30%; border:none;"><b>Material Name</b></td>
                                <td style="width:70%; border:none;">:'.$row["material_name"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>GRN No.</b></td>
                                <td style="width:70%; border:none;">:'.$row["grn_no"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Medicap lot no</b></td>
                                <td style="width:70%; border:none;">:'.$row["ar_no"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Batch No.</b></td>
                                <td style="width:70%; border:none;">:'.$row["batch_no"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Mfg. Name</b></td>
                                <td style="width:70%; border:none;">:'.$manufacturer.'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Mfg. Date</b></td>
                                <td style="width:70%; border:none;">:'.$row["mfg_date"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Exp. Date/Retest Date</b></td>
                                <td style="width:70%; border:none;">:'.$row["exp_date"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Mfg. COA</b></td>
                                <td style="width:70%; border:none;">:'.$row["mfg_coa"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Supplied By</b></td>
                                <td style="width:70%; border:none;">:'.$row["sampling_person"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Approved Vendor</b></td>
                                <td style="width:70%; border:none;">:'.$row["approve_vendor"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Date of Receive</b></td>
                                <td style="width:70%; border:none;">:'.date('d-m-Y',strtotime($row["entry_date"])).'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Total Qty. Received</b></td>
                                <td style="width:70%; border:none;">:'.$row["qty_received"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>No. of Container</b></td>
                                <td style="width:35%; border:none;"><b>Received:</b>'.$row['root_container'].'</td>
                                <td style="width:35%; border:none;"><b>Sampled:</b>'.$row['total_containers'].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Materials Condition</b></td>
                                <td style="width:70%; border:none;">'.$sample_details->container_condition.'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Qty. Sampled</b></td>
                                <td style="width:70%; border:none;">'.$sample_details->sample_qty.'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Sampler Used for Sampling</b></td>
                                <td style="width:70%; border:none;"><b>Sampler ID:</b>'.$row["sampling_no"].'</td>
                            </tr>
                            <tr>
                                <td style="width:30%; border:none;"><b>Observation/Remark if Any</b></td>
                                <td style="width:70%; border:none;">'.$row["remark"].'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
             </table>
             <div></div>
             <table cellpadding="5" >
                <tr>
                    <td style="width:50%;">'.$row["entry_by"].'<br>'.date('d-m-Y H:i',strtotime($row["entry_date"])).''.$row1["remark"].'</td>
                    <td style="width:50%;">'.$row["check_by"].'<br>'.date('d-m-Y H:i',strtotime($row["check_date"])).'</td>
                </tr>
                <tr>
                    <td style="width:50%;"><b>Sampled By</b><br><b>(Sign/Date)</b></td>
                    <td style="width:50%;"><b>Checked By</b><br><b>(Sign/Date)</b></td>
                </tr>
             </table>';
        }
    }
    
    //   $_GET['filename'] = 'SAMPLING CHECK LIST';
    //     $sql = "SELECT * FROM sampling WHERE id='".$_GET["id"]."'";
    //     $result = $conn->query($sql);
    //     if($result->num_rows > 0){
    //         while($row = $result->fetch_assoc()){
    //             $_GET['type'] = 'empdetail';include("../pdfimp.php");
    //             class MYPDF extends TCPDF {
    //                 public function Header() {
    //                     $_GET['type'] = 'header';
    //                     include("../pdfimp.php");
    //                 }
    //                 public function Footer() {
    //                     $_GET['type'] = 'footer';
    //                     include("../pdfimp.php");
    //                 }
    //             }
    //             $_GET['type'] = 'pdfdata';
    //             include("../pdfimp.php");
    //             $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
    //             $result1 = $conn->query($sql1); 
    //             $row1 = $result1->fetch_assoc();
                
    //             $sql2 = "SELECT * FROM grn WHERE grn_no='".$row["grn_no"]."'";
    //             $result2 = $conn->query($sql2);
    //             $row2 = $result2->fetch_assoc();
                
    //             $sql3 = "SELECT * FROM material_received WHERE receiving_no='".$row2["receiving_no"]."'";
    //             $result3 = $conn->query($sql3);
    //             $row3 = $result3->fetch_assoc();
                
    //             $sql5 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
    //             $result5 = $conn->query($sql5);
    //             $row5 = $result5->fetch_assoc();
                
    //             $sql9 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
    //             $result9 = $conn->query($sql9);
    //             $row9 = $result9->fetch_assoc();
    //         $html.='
    //         <style>td { border:solid 1px BCBBBA;}</style>
    //         <table cellpadding="5" style="text-align:left;">
    //             <tr><td style="width:100%"><b>Line Clearance By QA</b></td></tr>
    //             <tr>
    //                 <td style="width:25%"><b>Previous Material Name</b></td>
    //                 <td style="width:75%">'.$row5['material_name'].'</td>
    //             </tr>
    //             <tr>
    //                 <td><b>Previous Material GRN No.</b></td>
    //                 <td>'.$row['grn_no'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:25%"><b>Area Cleaned By</b></td>
    //                 <td style="width:25%">'.$row['sampling_person'].'</td>
    //                 <td style="width:25%"><b>Cleaning Date</b></td>
    //                 <td style="width:25%">'.date("d/m/Y", strtotime($row['entry_date'])).'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:100%"><b>QA Person has to ensure that all Traces of Previous Material Shall be Cleaned and Ensure that area is cleaned.</b><br><br>
    //                     <table>
    //                         <tr>
    //                             <td style="border:none;"><b>Time of Line Clearance :</b></td>
    //                             <td style="border:none;"><b>Sign and Date of IPQA</b></td>
    //                         </tr>
    //                         <tr>
    //                             <td style="width:100%; border:none;"><b>Person</b></td>
    //                         </tr>
    //                     </table>
    //                 </td>
    //             </tr>
    //         </table>
    //         <div></div>
    //         <table cellpadding="5">
    //             <tr>
    //                 <td style="width:100%;">Name of Raw Material: '.$row1['material_name'].'</td>
    //             </tr>
    //             <tr>
    //                 <td style="width:50%;">Item Code : '.$row['material_code'].'</td>
    //                 <td style="width:50%;">A.R. No : '.$row9['ar_no'].'</td>
    //             </tr>
    //             <tr>
    //                 <td>Mfg Date: '.date("d/m/Y", strtotime($row3['mfg_date'])).'</td>
    //                 <td>Exp Date: '.date("d/m/Y", strtotime($row3['exp_date'])).'</td>
    //             </tr>
    //             <tr>
    //                 <td>Manufacturer ( Vendor) : '.$row3['manufacturer'].'</td>
    //                 <td>Approved Not Approved</td>
    //             </tr>
    //             <tr><td style="width:100%;">Supplier Name:</td></tr>
    //             <tr><td>Cleaning of surrounding area of consignment : '.$row['cleaning_area'].'</td></tr>
    //             <tr><td>Proper consignment label affixed by vendor: '.$row['label_affixed'].'</td></tr>
    //             <tr><td>Material’s Physical Description: '.$row['physical_description'].'</td></tr>
    //             <tr>
    //                 <td style="width:50%">Total No. of containers: '.$row['total_containers'].'</td>
    //                 <td style="width:50%">No. Of Containers Sampled: '.$row['sampled_containers'].'</td>
    //             </tr>
    //             <tr><td style="width:100%;">Containers number Sampled (For n+1 criteria):</td></tr>
    //             <tr><td>Temperature /Rel. Humidity of Sampling Room: '.$row['room_temperature'].'°C / '.$row['humidity'].'%</td></tr>
    //             <tr><td>After Sampling Containers sealed and closed by: '.$row6['emp_name'].'</td></tr>
    //             <tr><td>Remark : '.$row['remark'].'</td></tr>
    //             <tr><td>Sampled by : '.$row6['emp_name'].' &nbsp;&nbsp;&nbsp; Sampled Date : '.date("d/m/Y", strtotime($row['entry_date'])).'</td></tr>
    //             <tr><td>Reviewed by QA (If any Abnormality) sign/Date:</td></tr>
    //         </table>
    //         <div></div>';
            
    //         }
    //         EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('samplingsopannexure.pdf', 'I');
        
    }else if ($_GET["type"] == "downloadSamplingsLog") {
        $_GET['filename'] = 'Sampling Log'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        
        $html.='
      <h2 style="text-align:center">Sampling Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">GRN No</td>
                    <td style="width: 10%;">Medicap lot no</td>
                    <td style="width: 10%;">GRN Date</td>
                    <td style="width: 12%;">Material Code</td>
                    <td style="width: 10%;">Material_name</td>
                    <td style="width: 10%;">Batch No</td>
                    <td style="width: 13%;">Containers</td>
                    <td style="width: 10%;">Sampling Person</td>
                    <td style="width: 10%;">status</td>
                </tr>
            </thead>';
            $i=1;
             $sql = "SELECT * FROM sampling WHERE status NOT IN ('pending', 'inprocess')";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_type"] = $row1["material_type"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                    $html.='<tr nobr="true">
                            <td style="width: 5%;">'.$i.'</td>
                            <td style="width: 10%;">'.$row['grn_no'].'</td>
                            <td style="width: 10%;">'.$row['ar_no'].'</td>
                            <td style="width: 10%;">'.date('d/m/Y',strtotime($row['grn_date'])).'</td>
                            <td style="width: 12%;">'.$row['material_code'].'</td>
                            <td style="width: 10%;">'.$row['material_name'].'</td>
                            <td style="width: 10%;text-align:right;">'.$row['batch_no'].'</td>
                            <td style="width: 13%;"text-align:right;>'.$row['containers'].'</td>
                            <td style="width: 10%;">'.$row['sampling_person'].'</td>
                            <td style="width: 10%;">'.$row['status'].'</td>
                        </tr>';
                    $i++;
                }
            }
   
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Samplings Log.pdf', 'I');
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>   