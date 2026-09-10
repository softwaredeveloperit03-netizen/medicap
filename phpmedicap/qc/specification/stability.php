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
    } else if ($_GET["type"] == "getProducts") {
        $output = Array();
        $sql = "SELECT * FROM product WHERE status='approve'";
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
    	$spec_no = "PM-0".$id;
    	
    	if ($input["specification"] == "Existing") {
    	    $spec_no = $input["spec_no"];
    	}
    	
    	$sql = "INSERT INTO specification (spec_type, product_code, specification_no, version_no, supersede_no, sample_qty, shelf_life, storage, safety_precaution, entry_by, entry_date, unit, review_date, retest_period) VALUES ('Stability Specification','".$input["product_code"]."','".$spec_no."','".$input["version_no"]."','".$input["supersede_no"]."','".$input["sample_qty"]."','".$input["shelf_life"]."','".$input["storage"]."','".$input["safety_precaution"]."','".$_GET["emp_id"]."','".$entry_date."','".$input["unit"]."', '".$input["next_review_date"]."', '".$input["retest_period"]."')";
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
    			$sql="INSERT INTO spec_tests (specification_no, test, subtest,description,reference_type,limit_type,lower_limit,upper_limit,lessthan,morethan, unit, sample_qty, retest) VALUES ('".$spec_no."','".$data["test"]."','".$data["subtest"]."','".$data["descr"]."','".$data["ref_type"]."','".$data["limit"]."','".$data["lower_limit"]."','".$data["upper_limit"]."','".$data["lessthan"]."','".$data["morethan"]."', '".$data["unit"]."', '".$data["sample_qty"]."', '".$data["retest_applicable"]."')";
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
        $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Stability%' AND s.status='pending'";
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
        $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Stability%' AND s.status='checked'";
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
    
    else if ($_GET["type"] == "allocateTestingPerson_finish1") {
      
     $sql = "UPDATE stability_testing SET  status='inprocess' WHERE id='".$_GET["testing_no"]."'";
   
     if ($conn->query($sql)) {
            $data = $input['tests'];
            for ($i = 0; $i < count($data); $i++) {
                $row1 = $data[$i];

            if($row1["methodID"]=='') $method = "Yes"  ; else $method = "No" ;

             
            $sql1 = "INSERT INTO testing_tests (plant_id, spec_test_id,testing_no,fg_sampling_no,test,subtest,description,isoutside,person,
            person_alt,ismethod,method_details,outside_testing,bmr_qcsample_tests_id,specification_no)VALUES ('".$_GET["plant_id"]."','".$row1["id"]."',
            '".$_GET["testing_no"]."','".$_GET["sampling_no"]."','".$row1["test"]."','".$row1["subtest"]."','".$row1["description"]."',
            '".$row1["isoutside"]."','".$row1["person"]."','".$row1["alternate_chemist"]."','".$method."','".$row1["methodID"]."',
            '".$row1["lab_name"]."','".$input["bmr_qcsample_tests_id"]."', '".$_GET["specification_no"]."')"; 
            
                $conn->query($sql1);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "getSpecificationsLog") {
        $output = Array();
        $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p 
        ON s.product_code=p.product_code WHERE spec_type LIKE 'Stability%' ";
       // AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.grade LIKE '%".$_GET["grade"]."%' 
       // AND s.status LIKE '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                 $sql1 = "SELECT * FROM spec_tests WHERE  release_stability = '1' AND specification_no='".$row["specification_no"]."'";
                //  $sql1 = "SELECT * FROM spec_tests WHERE  release_stability = 'Stability' AND specification_no='".$row["specification_no"]."'";
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
    else if($_GET['type'] == 'SpecificationLogPDF'){
            $_GET['filename'] = 'INPROCESS SPECIFICATION'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
            $html.='
            <h2 style="text-align:center">INPROCESS SPECIFICATION</h2>
            <table cellpadding="5" border="1">
                        <tr>
                            <td style="width:5%;  text-align:centre;"><b>Spec No.</b></td>
                            <td style="width:10%; text-align:centre;"><b>Doasge Form</b></td>
                            <td style="width:10%; text-align:centre;"><b>Product Name</b></td>
                            <td style="width:10%; text-align:centre;"><b>Product Code</b></td>
                            <td style="width:10%; text-align:centre;"><b>Reference</b></td>
                            <td style="width:10%; text-align:centre;"><b>Sample Qty</b></td>
                            <td style="width:10%; text-align:centre;"><b>Version No</b></td>
                            <td style="width:10%; text-align:centre;"><b>Supersed No.</b></td>
                            <td style="width:10%; text-align:centre;"><b>Effective date</b></td>
                            <td style="width:10%; text-align:centre;"><b>Revision Period</b></td>
                            <td style="width:5%;  text-align:centre;"><b>Status</b></td>
                        </tr>';
                        $sql = "SELECT s.*, p.dosage_form, p.product_name, p.grade FROM specification s LEFT JOIN product p ON s.product_code=p.product_code WHERE spec_type LIKE 'Stability%' AND p.dosage_form LIKE '%".$_GET["dosage_form"]."%' AND p.grade LIKE '%".$_GET["grade"]."%' AND s.status LIKE '%".$_GET["status"]."%'";
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
                $html.='<tr>
                            <td style="width:5%;">'.$row['specification_no'].'</td>
                            <td style="width:10%;">'.$row['dosage_form'].'</td>
                            <td style="width:10%;">'.$row['product_name'].'</td>
                            <td style="width:10%;">'.$row['product_code'].'</td>
                            <td style="width:10%;">'.$row['grade'].'</td>
                            <td style="width:10%;">'.$row['sample_qty'].'</td>
                            <td style="width:10%;">'.$row['version_no'].'</td>
                            <td style="width:10%;">'.$row['supersed_no'].'</td>
                            <td style="width:10%;">'.$row['effective_date'].'</td>
                            <td style="width:10%;">'.$row['revision_period'].'</td>
                            <td style="width:5%;">'.$row['status'].'</td>
                        </tr>';
                    }
                }
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
                
                $html.='
                <h2 style="text-align:cenetr">INPROCESS SPECIFICATION</h2>
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
            $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $_GET['filename'] = 'Stability Specification'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
                    
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
                    <h2 style="text-align:center">Stability Specification</h2>
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
                    <p style="text-align:center;"><b>Finish Product Specification</b></p>
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