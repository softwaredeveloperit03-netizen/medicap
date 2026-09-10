<?php

//   ini_set('display_errors', 1);
//   error_reporting(E_ALL);

    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    $counter = 1;
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
    
    
    if ($_GET["type"] == "getTests") {
        $sql = "SELECT test FROM test WHERE classification='Finish Product' AND status='active' GROUP BY test";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $output1 = array();
    		    $sql1 = "SELECT * FROM subtest WHERE classification='Finish Product' AND test='".$row["test"]."' GROUP BY subtest";
    		    $result1 = $conn->query($sql1);
    		    if ($result1->num_rows > 0) {
    		        while ($row1 = $result1->fetch_assoc()) {
    		            $output1[] = $row1;
    		        }
    		    }
    		    $row["subtests"] = $output1;
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    } else if ($_GET["type"] == "getDosages") {
        $output = Array();
        $sql = "SELECT * FROM dosage_form";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getProducts") {
        $output = Array();
       $sql = "SELECT * FROM product WHERE  product_type='".$_GET["product_type"]."'  AND (product_code,grade) NOT IN (SELECT product_code,grade FROM specification WHERE status IN ('pending','checked', 'approve') AND spec_type LIKE '%Finish Product%') GROUP BY product_code";
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
    	$spec_no = "FP-0".$id;
    	
    	if ($input["specification"] == "Existing") {
    	    $spec_no = $input["spec_no"];
    	}
    	
    	$sql = "INSERT INTO specification (spec_type, product_code, specification_no, version_no, supersede_no, sample_qty, shelf_life, storage, safety_precaution, entry_by, entry_date, unit, review_date,effective_date, retest_period,sample_applicable,control_sample,additional_sample,grade, cas_name, molecular_weight, molecular_formula, storage_condition) VALUES ('Finish Product','".$input["product_code"]."','".$spec_no."','".$input["version_no"]."','".$input["supersede_no"]."','".$input["sample_qty"]."','".$input["shelf_life"]."','".$input["storage"]."','".$input["safety_precaution"]."','".$_GET["emp_id"]."','".$entry_date."','".$input["unit"]."', '".$input["review_date"]."','".$input["effective_date"]."', '".$input["retest_period"]."','".$input["sample_applicable"]."','".$input["control_sample"]."','".$input["additional_sample"]."','".$input["grade"]."','".$input["cas_name"]."','".$input["molecular_weight"]."','".$input["molecular_formula"]."','".$input["storage_condition"]."')";
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
    			$sql="INSERT INTO spec_tests (specification_no, test,test_type, limits, subtest,description,reference_type,limit_type,lower_limit,upper_limit,lessthan,morethan, unit, sample_qty, retest) VALUES ('".$spec_no."','".$data["test"]."','".$data["test_type"]."','".$data["limits"]."','".$data["subtest"]."','".$data["descr"]."','".$data["reference_type"]."','".$data["limit"]."','".$data["lower_limit"]."','".$data["upper_limit"]."','".$data["lessthan"]."','".$data["morethan"]."', '".$data["unit"]."', '".$data["sample_qty"]."', '".$data["retest_applicable"]."')";
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
    } else if ($_GET["type"] == "getPendingSpecifications") {
        $output = Array();
        $sql = "SELECT s.*, p.product_type, p.product_name FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Finish%' AND s.status='pending' GROUP BY s.grade,s.product_code";
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
    } else if ($_GET["type"] == "checkSpecification") {
        $sql = "UPDATE specification SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getCheckedSpecifications") {
        $output = Array();
        $sql = "SELECT s.*, p.product_type, p.product_name FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Finish%' AND s.status='checked' GROUP BY s.grade,s.product_code";
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
    } else if ($_GET["type"] == "getSpecificationsLog") {
        $output = Array();
        $sql = "SELECT s.*, p.product_type, p.product_name FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Finish%' GROUP BY s.product_code,s.grade";
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
    
    else if($_GET['type'] == 'SpecificationPDF'){
        // $_GET['filename'] = 'FINISH PRODUCT SPECIFICATION';
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php"); 
                
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_type"] = $row2["product_type"];
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        // $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <h3 style="text-align:center; ">FINISHED PRODUCT SPECIFICATION</h3>
                <style>
                    .tdall { border:solid 1px BCBBBA; }
                    .tdb { border-bottom:solid 1px BCBBBA; }
                    .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
                </style>
                <table style="border:solid 1px BCBBBA;" cellpadding="2">
                    <tr>
                        <td><b>Department </b></td>
                        <td colspan="3"><b> Quality Control</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product </b></td>
                        <td colspan="3"> '.$row['product_name'].'</td>
                    </tr>
                    <tr>
                        <td><b>Product Type </b></td>
                        <td> '.$row['product_type'].'</td>
                        <td><b>Product Code </b></td>
                        <td> '.$row['product_code'].'</td>
                    </tr>
                    <tr>
                        <td><b>CAS No. </b></td>
                        <td> '.$row['cas_name'].'</td>
                        <td><b>Sample Qty. </b></td>
                        <td> '.$row['sample_qty'].' '.$row['unit'].'</td>
                    </tr>
                    <tr>
                        <td><b>Molecular Formula </b></td>
                        <td> '.$row['molecular_formula'].'</td>
                        <td><b>Molecular Weight </b></td>
                        <td> '.$row['molecular_weight'].'</td>
                    </tr>
                    <tr>
                        <td><b>Specification No.</b></td>
                        <td> '.$row['specification_no'].'</td>
                         <td><b>Grade </b></td>
                        <td> '.$row['grade'].'</td>
                    </tr>
                    <tr>
                        <td><b>Revision No.</b></td>
                        <td> '.$row['version_no'].'</td>
                        <td><b>Supersede No </b></td>
                        <td> '.$row['supersede_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Effective Date</b></td>
                        <td> '.date('d-m-Y',strtotime($row['effective_date'])).'</td>
                        <td><b>Review Date </b></td>
                        <td> '.date('d-m-Y',strtotime($row['review_date'])).'</td>
                    </tr>
                    <tr>
                        <td><b>Storage Condition </b></td>
                        <td colspan="3"> '.$row['storage_condition'].'</td>
                       
                    </tr>
                </table>
                <div></div>
                <table cellpadding="2">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:45%;">Test</td>
                        <td style="width:45%;">Specification/Limits</td>
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
                            <td class="tdall" style="width: 10%" align="center;">'.$j++.'.</td>
                            <td> '.$row1["test"].'</td>
                            <td> '.$row1["limits"].'</td>
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
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // $_GET['filename'] = 'FINISH PRODUCT SPECIFICATION'; $_GET['pdftype'] = 'headfootdigital';  include("../../pdfimp.php");
                // $_GET['filename'] = 'FINISH PRODUCT SPECIFICATION'; 
                $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
                
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
                <h3 style="text-align:center;">FINISH PRODUCT SPECIFICATION</h3>
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
                            while ($row2 = $result2->fetch_assoc()) {}
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
    else if($_GET['type'] == 'SpecificationLogPDF') {
         $_GET['filename'] = 'Raw Material Specification Report'; $_GET['pdftype'] = 'onlyheader'; include('../../pdfimp.php');
         $html.="";
        $html.='
        <h2 style="text-align:center">Raw Material Specification Report</h2>
        <table cellpadding="5" border="1">
                <tr>
                    <td style="width:10%; text-align:centre;"><b>Spec No.</b></td>
                    <td style="width:10%; text-align:centre;"><b>Product type</b></td>
                    <td style="width:15%; text-align:centre;"><b>Product Name</b></td>
                    <td style="width:15%; text-align:centre;"><b>Product Code</b></td>
                    <td style="width:10%; text-align:centre;"><b>Grade</b></td>
                    <td style="width:15%; text-align:centre;"><b>Sample Qty</b></td>
                    <td style="width:15%; text-align:centre;"><b>Version No</b></td>
                    <td style="width:10%; text-align:centre;"><b>Revision Period</b></td>
                </tr>';
                $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Finish%' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.grade LIKE '%".$_GET["grade"]."%' AND s.status LIKE '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                $html.='<tr>
                    <td style="width:10%;">'.$row['specification_no'].'</td>
                    <td style="width:10%;">'.$row['product_type'].'</td>
                    <td style="width:15%;">'.$row['product_name'].'</td>
                    <td style="width:15%;">'.$row['product_code'].'</td>
                    <td style="width:10%;">'.$row['grade'].'</td>
                    <td style="width:15%;">'.$row['qty'].'</td>
                    <td style="width:15%;">'.$row['version_no'].'</td>
                    <td style="width:10%;">'.$row['revision_period'].'</td>
                </tr>';
            }
        }
            }
        }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('RawMaterialSpecificationReport.pdf', 'I');
    }
//     else if($_GET['type'] == 'SpecificationLogPDF') {
//     $_GET['filename'] = 'Raw Material Specification Report'; 
//     $_GET['pdftype'] = 'onlyheader'; 

//     // Include PDF library and ensure $pdf is initialized
//     include('../../pdfimp2.php');
    
//     // Check if $pdf object is instantiated
//     if (!isset($pdf)) {
//         // Initialize the PDF object if it’s not already set
//         $pdf = new TCPDF(); // or the appropriate class from your library
//     }

//     // Start building the HTML content
//     $html = '';
//     $html .= '
//     <h2 style="text-align:center">Raw Material Specification Report</h2>
//     <table cellpadding="5" border="1">
//         <tr>
//             <td style="width:10%; text-align:center;"><b>Spec No.</b></td>
//             <td style="width:10%; text-align:center;"><b>Product type</b></td>
//             <td style="width:15%; text-align:center;"><b>Product Name</b></td>
//             <td style="width:15%; text-align:center;"><b>Product Code</b></td>
//             <td style="width:10%; text-align:center;"><b>Grade</b></td>
//             <td style="width:15%; text-align:center;"><b>Sample Qty</b></td>
//             <td style="width:15%; text-align:center;"><b>Version No</b></td>
//             <td style="width:10%; text-align:center;"><b>Revision Period</b></td>
//         </tr>';

//     // Fetch data from database
//     $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade 
//             FROM specification s 
//             LEFT JOIN product p ON s.product_code = p.product_code 
//             WHERE spec_type LIKE 'Finish%' 
//               AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' 
//               AND p.grade LIKE '%".$_GET["grade"]."%' 
//               AND s.status LIKE '%".$_GET["status"]."%'";
    
//     $result = $conn->query($sql);
//     if ($result->num_rows > 0) {
//         while ($row = $result->fetch_assoc()) {
//             $html .= '<tr>
//                 <td style="width:10%;">' . $row['specification_no'] . '</td>
//                 <td style="width:10%;">' . $row['product_type'] . '</td>
//                 <td style="width:15%;">' . $row['product_name'] . '</td>
//                 <td style="width:15%;">' . $row['product_code'] . '</td>
//                 <td style="width:10%;">' . $row['grade'] . '</td>
//                 <td style="width:15%;">' . $row['qty'] . '</td>
//                 <td style="width:15%;">' . $row['version_no'] . '</td>
//                 <td style="width:10%;">' . $row['revision_period'] . '</td>
//             </tr>';
//         }
//     }
//     $html .= '</table>';

//     // Write HTML content to PDF
//     $pdf->writeHTML($html, true, false, false, false, '');
    
//     // Output the PDF
//     $pdf->Output('RawMaterialSpecificationReport.pdf', 'I');
// }

}

$conn->close();
?>