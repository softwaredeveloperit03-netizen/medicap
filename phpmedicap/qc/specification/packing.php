<?php

// ini_set('display_errors', 1);
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

    if ($_GET["type"] == "getTests") {
        $sql = "SELECT test FROM test WHERE classification='Packing Material' AND status='active' GROUP BY test";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $output1 = array();
    		    $sql1 = "SELECT * FROM subtest WHERE classification='Packing Material' AND test='".$row["test"]."' GROUP BY subtest";
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
    } else if ($_GET["type"] == "getMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material WHERE material_type='Packing Material' AND material_subtype='".$_GET["material_subtype"]."'";
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
    	
    	$sql = "INSERT INTO specification (spec_type, material_code, specification_no, version_no, supersede_no, sample_qty, shelf_life, storage, safety_precaution, entry_by, entry_date, unit, review_date, retest_period) VALUES ('Packing Material Specification','".$input["material_code"]."','".$spec_no."','".$input["version_no"]."','".$input["supersede_no"]."','".$input["sample_qty"]."','".$input["shelf_life"]."','".$input["storage"]."','".$input["safety_precaution"]."','".$_GET["emp_id"]."','".$entry_date."','".$input["unit"]."', '".$input["next_review_date"]."', '".$input["retest_period"]."')";
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
    		    $sql="INSERT INTO spec_tests (user_no, specification_no,test_type, test, subtest,description,reference_type,limit_type,lower_limit,upper_limit,lessthan,morethan, unit, sample_qty, retest, limits) VALUES ('".$_GET["user_no"]."','".$spec_no."','".$data["test_type"]."','".$data["test"]."','".$data["subtest"]."','".$data["descr"]."','".$data["reference_type"]."','".$data["limit"]."','".$data["lower_limit"]."','".$data["upper_limit"]."','".$data["lessthan"]."','".$data["morethan"]."', '".$data["unit"]."', '".$data["sample_qty"]."', '".$data["retest_applicable"]."', '".$data["limits"]."')";
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
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE spec_type LIKE 'Packing Material%' AND s.status='pending'";
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
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE spec_type LIKE 'Packing Material%' AND s.status='checked'";
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
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE spec_type LIKE 'Packing Material%' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND m.grade LIKE '%".$_GET["grade"]."%' AND s.status LIKE '%".$_GET["status"]."%'";
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
    else if($_GET['type'] == 'SpecificationLogPDF'){
        $_GET['filename'] = 'SPECIFICATION INDEX (PACKING MATERIAL)'; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">SPECIFICATION INDEX (PACKING MATERIAL)</h2>
        <table cellpadding="5" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold">
                    <td style=>Spec No.</td>
                    <td>Material Type</td>
                    <td>Material Name</td>
                    <td>Material Code</td>
                    <td>Version </td>
                </tr>';
            $html.='
                <tr>
                    <td>'.$row['specification_no'].'</td>
                    <td>'.$row['material_subtype'].'</td>
                    <td>'.$row['material_name'].'</td>
                    <td>'.$row['material_code'].'</td>
                    <td>'.$row['version_no'].'</td>
                </tr>';
        $html.='</table>';
        // EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
    else if($_GET['type'] == 'SpecificationPDF'){
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        // $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp.php");
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        $html.='
        <h3 style="text-align:center;">PACKING MATERIAL SPECIFICATION</h3>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td class="tdb" style="width:15%;"><b>Department</b></td>
                <td class="tdb" style="width:85%;">: <b>Quality Control</b></td>
            </tr>
            <tr>
                <td class="tdb"><b>Material Name</b></td>
                <td class="tdb">: '.$row["material_name"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Specification No</b></td>
                <td class="tdbr" style="width:45%;">: '.$row['specification_no'].'</td>
                <td class="tdb" style="width:15%;"><b>Material Code</b></td>
                <td class="tdb" style="width:25%;">: '.$row["material_code"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Effective Date</b></td>
                <td class="tdbr">: </td>
                <td class="tdb"><b>Review Before</b></td>
                <td class="tdb">: </td>
            </tr>
            <tr>
                <td class="tdb"><b>Retest Period</b></td>
                <td class="tdbr">: 24 Months 0 Days</td>
                <td class="tdb"><b>Supersedes No</b></td>
                <td class="tdb">: NA</td>
            </tr>
            <tr>
                <td class="tdb"><b>Shelf Life</b></td>
                <td class="tdbr">: '.$row['shelf_life'].' Months 0 Days</td>
                <td class="tdb"><b>Page</b></td>
                <td class="tdb">: 1 of 1</td>
            </tr>
            <tr>
                <td class="tdb" style="width:22%;"><b>Sampling Procedure No.</b></td>
                <td class="tdbr" style="width:38%;">: </td>
                <td class="tdb" style="width:15%;"><b>STP No</b></td>
                <td class="tdb" style="width:25%;">: </td>
            </tr>
            <tr>
                <td class="tdb" colspan="4"><b>Approved Vendor Details :</b></td>
            </tr>
            <tr>
                <td><b>Storage Condition</b></td>
                <td colspan="3">: Store Protected from light and moisture, at a temperature not exceeding 30oC.</td>
            </tr>
        </table>
        <div></div>

        <table cellpadding="5">
            <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                <td class="tdall" style="width:10%;">Sr. No</td>
                <td class="tdall" style="width:40%;">Test</td>
                <td class="tdall" style="width:50%;">Specification</td>
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
                    <td class="tdall">'.$j++.'.</td>
                    <td class="tdall">'.$row1["test"].'</td>
                    <td class="tdall">White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                </tr>';
            }
        }
        $html.='
        </table>
        <p style="text-align:center;"><b>REVISION HISTORY</b></p>
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9; text-align:center;">
                <td class="tdall">Specification No.</td>
                <td class="tdall">Version No.</td>
                <td class="tdall">Change Made</td>
                <td class="tdall">Reasons for change</td>
            </tr>';
            $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td class="tdall">'.$row["specification_no"].'</td>
                        <td class="tdall">'.$row1["version_no"].'</td>
                        <td class="tdall">'.$row1["change_mode"].'</td>
                        <td class="tdall">'.$row["reason"].'</td>
                    </tr>';
                }
            }
            $html.='
        </table>';
            
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
    else if($_GET['type'] == 'SpecificationdigitalPDF'){
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE specification_no='".$_GET['specification_no']."' LIMIT 1";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        // $_GET['filename'] = ''; $_GET['pdftype'] = 'headfootdigital';  include("../../pdfimp.php");
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader';  include("../../pdfimp2.php");
        $html.='
        <h3 style="text-align:center;">PACKING MATERIAL SPECIFICATION</h3>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td class="tdb" style="width:15%;"><b>Department</b></td>
                <td class="tdb" style="width:85%;">: <b>Quality Control</b></td>
            </tr>
            <tr>
                <td class="tdb"><b>Material Name</b></td>
                <td class="tdb">: '.$row["material_name"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Specification No</b></td>
                <td class="tdbr" style="width:45%;">: '.$row['specification_no'].'</td>
                <td class="tdb" style="width:15%;"><b>Material Code</b></td>
                <td class="tdb" style="width:25%;">: '.$row["material_code"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Effective Date</b></td>
                <td class="tdbr">: </td>
                <td class="tdb"><b>Review Before</b></td>
                <td class="tdb">: </td>
            </tr>
            <tr>
                <td class="tdb"><b>Retest Period</b></td>
                <td class="tdbr">: 24 Months 0 Days</td>
                <td class="tdb"><b>Supersedes No</b></td>
                <td class="tdb">: NA</td>
            </tr>
            <tr>
                <td class="tdb"><b>Shelf Life</b></td>
                <td class="tdbr">: '.$row['shelf_life'].' Months 0 Days</td>
                <td class="tdb"><b>Page</b></td>
                <td class="tdb">: 1 of 1</td>
            </tr>
            <tr>
                <td class="tdb" style="width:22%;"><b>Sampling Procedure No.</b></td>
                <td class="tdbr" style="width:38%;">: </td>
                <td class="tdb" style="width:15%;"><b>STP No</b></td>
                <td class="tdb" style="width:25%;">: </td>
            </tr>
            <tr>
                <td class="tdb" colspan="4"><b>Approved Vendor Details :</b></td>
            </tr>
            <tr>
                <td><b>Storage Condition</b></td>
                <td colspan="3">: Store Protected from light and moisture, at a temperature not exceeding 30oC.</td>
            </tr>
        </table>
        <div></div>

        <table cellpadding="5">
            <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                <td class="tdall" style="width:10%;">Sr. No</td>
                <td class="tdall" style="width:40%;">Test</td>
                <td class="tdall" style="width:50%;">Specification</td>
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
                    <td class="tdall">'.$j++.'.</td>
                    <td class="tdall">'.$row1["test"].'</td>
                    <td class="tdall">White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                </tr>';
            }
        }
        $html.='
        </table>
        <p style="text-align:center;"><b>REVISION HISTORY</b></p>
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9; text-align:center;">
                <td class="tdall">Specification No.</td>
                <td class="tdall">Version No.</td>
                <td class="tdall">Change Made</td>
                <td class="tdall">Reasons for change</td>
            </tr>';
            $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
            $result1 = $conn->query($sql1);
            $output1 = Array();
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td class="tdall">'.$row["specification_no"].'</td>
                        <td class="tdall">'.$row1["version_no"].'</td>
                        <td class="tdall">'.$row1["change_mode"].'</td>
                        <td class="tdall">'.$row["specification_no"].'</td>
                    </tr>';
                }
            }
            $html.='
        </table>';
            
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
}

$conn->close();
?>