<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);

    if ($_GET["type"] == "getDosages") {
        $output = Array();
        $sql = "SELECT * FROM dosage_form";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
        }else if ($_GET["type"] == "getTests") {
        $output = Array();
        $sql = "SELECT * FROM test";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getProducts") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
             
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"]=="saveSpecification") {
    	$id = 0;
    	$sql = "SELECT MAX(id) as id FROM specification";
    	$result = $conn->query($sql);
    	if ($result->num_rows > 0) {
    	    while ($row = $result->fetch_assoc()) {
    	        $id = $row["id"];
    	    }
    	}
    	$id++;
    	$spec_no = "IP-0".$id;
    	
    	if ($input["specification"] == "Existing") {
    	    $spec_no = $input["spec_no"];
    	}
    	
    	$sql = "INSERT INTO specification (plant_id,spec_type, material_code, specification_no, version_no, supersede_no, sample_qty,
    	shelf_life, storage, safety_precaution, entry_by, entry_date, unit, review_date,effective_date, retest_period) VALUES ('".$_GET["plant_id"]."',
    	'Inprocess Specification','".$input["product_code"]."','".$spec_no."','".$input["version_no"]."','".$input["supersede_no"]."',
    	'".$input["sample_qty"]."','".$input["shelf_life"]."','".$input["storage"]."','".$input["safety_precaution"]."','".$_GET["emp_id"]."',
    	'".$entry_date."','".$input["unit"]."', '".$input["review_date"]."','".$input["effective_date"]."', '".$input["retest_period"]."')";
    	if($conn->query($sql)===TRUE){
    		$experience_company = $input["tests"];
    		$len = count($experience_company);
    		for($i = 0; $i<$len; $i++) {
    			$data = $experience_company[$i];
    			if($data["limit"] == 'Limits'){
    				$data["lessthan"] = ''; 
    				$data["morethan"] = ''; 
    			}else if($data["limit"] == 'LessThan'){
    				$data["lower_limit"] = ''; 
    				$data["upper_limit"] = ''; 
    				$data["morethan"] = ''; 
    			}else if($data["limit"] == 'MoreThan'){
    				$data["lower_limit"] = ''; 
    				$data["upper_limit"] = ''; 
    				$data["lessthan"] = ''; 
    			}else if($data["limit"] == 'Compliances'){
    				$data["lower_limit"] = ''; 
    				$data["upper_limit"] = ''; 
    				$data["lessthan"] = '';
    				$data["morethan"] = '';  
    			}
    			if ($data['retest_applicable'] == true) {
    			    $data['retest_applicable'] = 'yes';
    			} else {
    			    $data['retest_applicable'] = 'no';
    			}
    			$sql="INSERT INTO spec_tests (plant_id,stage,test_type,sample_qty,specification_no, test, subtest,description,reference_type,
    			limit_type,limits,lower_limit,upper_limit,lessthan,morethan, unit, retest) VALUES
    			('".$_GET["plant_id"]."','".$data["stage"]."', '".$data["test_type"]."','".$data['sample_qty']."','".$spec_no."',
    			'".$data["test"]."','".$data["subtest"]."','".$data["descr"]."','".$data["reference_type"]."','".$data["limit"]."','".$data["limits"]."',
    			'".$data["lower_limit"]."','".$data["upper_limit"]."','".$data["lessthan"]."','".$data["morethan"]."', '".$data["unit"]."',
    			'".$data["retest_applicable"]."')";
    		
    			$conn->query($sql);
    	    } 
    	    $len = count($input["revisionHistory"]);
    	    $revisionHistory = $input["revisionHistory"];
    	    for ($i =0; $i < $len; $i++) {
    	        $data = $revisionHistory[$i];
    	        $sql = "INSERT INTO spec_revision (spec_no, specification_no, version_no, change_mode, reason, effective_date) VALUES ('".$spec_no."','".$data["spec_no"]."','".$data["ver_no"]."','".$data["change_mode"]."','".$data["change_reason"]."', '".$data["effective_date"]."')";
    	        $conn->query($sql);
    	    }
    		echo "{\"status\":\"success\"}";
    	}
    	else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    else if ($_GET["type"] == "getPendingSpecifications") {
        
        $output = Array();
        $sql = "SELECT s.*,  p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.material_code = p.product_code WHERE spec_type LIKE 'Inprocess%' AND s.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['tests'] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['revision_history'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "checkSpecification") {
        $sql = "UPDATE specification SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCheckedSpecifications") {
        $output = Array();
        $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Inprocess%' AND s.status='checked'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['tests'] = $output1;
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['revision_history'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "approveSpecification") {
        $sql = "UPDATE specification SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    // else if ($_GET["type"] == "getSpecificationsLog") {
    //     $output = Array();
    //     $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Inprocess%' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.grade LIKE '%".$_GET["grade"]."%' AND s.status LIKE '%".$_GET["status"]."%'";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $output1 = Array();
    //             $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     $row1["conditions"] =json_decode($row1["conditions"]);
    //                      $row1["instruments"] =json_decode($row1["instruments"]); 
    //                      $row1["reagents"] =json_decode($row1["reagents"]);
    //                      $row1["solutions"] =json_decode($row1["solutions"]);
    //                     $output1[] = $row1;
    //                 }
    //             }
    //             $row['tests'] = $output1;
                
    //             $output1 = Array();
    //             $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     $output1[] = $row1;
    //                 }
    //             }
    //             $row['revision_history'] = $output1;
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // }
    
    else if ($_GET["type"] == "getSpecificationsLog") {
        $output = Array();
        $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p 
        ON s.product_code=p.product_code WHERE s.spec_type LIKE 'Inprocess%' ORDER BY s.specification_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       
                        $output1[] = $row1;
                    }
                }
                $row['tests'] = $output1;
                
                $output12 = Array();
                $sql12 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."' ";
                $result12 = $conn->query($sql12);
                if ($result12->num_rows > 0) {
                    while ($row12 = $result12->fetch_assoc()) {
                       
                        $output12 = $row12;
                    }
                }
                $row['revision_history'] = $output12;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'SpecificationLogPDF'){
        $_GET['filename'] = 'SPECIFICATION INDEX (INPROCESS)'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">SPECIFICATION INDEX (INPROCESS)</h2>
        <table cellpadding="5" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold">
                    <td style="width:10%;">Spec No.</td>
                    <td style="width:10%;">Product Type	</td>
                    <td style="width:10%;">Product Name</td>
                    <td style="width:10%;">Product Code</td>
                    <td style="width:5%;">Grade</td>
                    <td style="width:10%;">Sample Qty</td>
                    <td style="width:10%;">Version No</td>
                    <td style="width:10%;">Supersed No.</td>
                    <td style="width:10%;">Effective Date</td>
                    <td style="width:10%;">Revision Period</td>
                    <td style="width:5%;">Status</td>
                </tr>';
                $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE s.spec_type LIKE 'Inprocess%' ORDER BY s.specification_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='
                <tr>
                    <td style="width:10%;">'.$row['specification_no'].'</td>
                    <td style="width:10%;">'.$row['type'].'</td>
                    <td style="width:10%;">'.$row['product_name'].'</td>
                    <td style="width:10%;">'.$row['product_code'].'</td>
                    <td style="width:5%;">'.$row['grade'].'</td>
                    <td style="width:10%;">'.$row['sample_qty'].' '.$row['unit'].'</td>
                    <td style="width:10%;">'.$row['version_no'].'</td>
                    <td style="width:10%;">'.$row['supersede_no'].'</td>
                    <td style="width:10%;">'.date('d-m-Y',strtotime($row['effective_date'])).'</td>
                    <td style="width:10%;">'.$row['review_date'].'</td>
                    <td style="width:5%;">'.$row['status'].'</td>
                </tr>';
            }
        }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
    else if($_GET['type'] == 'SpecificationPDF'){
        $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['filename'] = 'INPROCESS SPECIFICATION'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
                
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <h2 style="text-align:center">INPROCESS SPECIFICATION</h2>
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b>'.$row['product_code'].'</td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b>'.$row['product_name'].'</td>
                        <td><b>Specification No. :</b>'.$row['specification_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b>'.$row['chemical_name'].'</td>
                        <td><b>Version No.</b>'.$row['version_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b>'.$row['reference'].'</td>
                        <td><b>Supersede No :</b>'.$row['supersede_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b>'.$row['sample_qty'].'</td>
                        <td><b>Shelf Life :</b>'.$row['shelf'].'</td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b>'.$row['effective_date'].'</td>
                        <td><b>Specification Type :</b>'.$row['spec_type'].'</td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b>'.$row['review_date'].'</td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'SpecificationdigitalPDF'){
        $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['filename'] = 'INPROCESS SPECIFICATION'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
                
                $html.='
                <h2 style="text-align:center">INPROCESS SPECIFICATION</h2>
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }

}

$conn->close();
?>