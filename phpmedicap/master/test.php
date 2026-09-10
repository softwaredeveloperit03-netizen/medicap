<?php 

// ini_set('display_errors', 1);
//  error_reporting(E_ALL);


require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveTest") {
         
        $sql = "INSERT INTO test (plant_id,multiple_anaylist,test_type,test,test_method_no,entry_by,entry_date,calculation,status) VALUES 
        ('".$_GET["plant_id"]."','NO','".$input["test_type"]."','".$input["test"]."','".$input["testCode"]."','".$_GET["emp_id"]."','$entry_date',
        'Not Applicable','Pending')";
        
    	if($conn->query($sql)){
    	     $test_id = $conn->insert_id; 
    	     
    		$subtests = $input["subtestList"];
    		for ($i = 0; $i < count($subtests); $i++) {
    		    $test = $subtests[$i];
 
            	    $sql = "INSERT INTO `subtest`(`plant_id`,`test_id`,`test_type`, `test`, `subtest`, `entry_by`, 
            	    `entry_date`) VALUES('".$_GET["plant_id"]."','".$test_id."','".$input["test_type"]."','".$input["test"]."',
            	    '".$test["subtest"]."','".$_GET["emp_id"]."','$entry_date')";
            	    
            	    $conn->query($sql);
            	    
    		}
    		 
    		  	echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    
     else if  ($_GET["type"] == "saveTestMeha") {
         
        $sql = "INSERT INTO test (plant_id,multiple_anaylist,test_type,test,test_method_no,entry_by,entry_date,calculation,status) VALUES 
        ('".$_GET["plant_id"]."','NO','".$input["test_type"]."','".$input["test"]."','".$input["testCode"]."','".$_GET["emp_id"]."','$entry_date',
        'Not Applicable','Pending')";
        
    	if($conn->query($sql)){
    	     $test_id = $conn->insert_id; 
    	     
    		$subtests = $input["subtestList"];
    		for ($i = 0; $i < count($subtests); $i++) {
    		    $test = $subtests[$i];
 
            	    $sql = "INSERT INTO `subtest`(`plant_id`,`test_id`,`test_type`, `test`, `subtest`, `entry_by`, 
            	    `entry_date`) VALUES('".$_GET["plant_id"]."','".$test_id."','".$input["test_type"]."','".$input["test"]."',
            	    '".$test["subtest"]."','".$_GET["emp_id"]."','$entry_date')";
            	    
            	    $conn->query($sql);
            	    
    		}

  
    		  	echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
        else if ($_GET["type"] == "getStp") {
        $output = array();
        $sql = "SELECT a.*,b.software_test_method_no FROM test a LEFT join test_methods b on a.id=b.test_id WHERE b.software_test_method_no is not null; "; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                  $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
        else if ($_GET["type"] == "getTestsapprovalMeha") {
        $output = array();
        $sql = "SELECT * from test where plant_id =  '".$_GET["plant_id"]."' AND status = 'Pending'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $sql1 = "SELECT * from subtest where test_id =  '".$row["id"]."'  "; 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['subtests'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "approveTest") {
        
        $sql = "UPDATE test SET status = 'Active',approve_by = '".$_GET["emp_id"]."',approve_date = '$entry_date' 
        where id =  '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
                $sql = "UPDATE subtest SET status = 'Active',approve_by = '".$_GET["emp_id"]."',approve_date = '$entry_date' 
                where test_id =  '".$_GET["id"]."'";
                
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
                
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }

    
    
    else if ($_GET["type"] == "approveTestMeha") {
        
        $sql = "UPDATE test SET status = 'Active',approve_by = '".$_GET["emp_id"]."',approve_date = '$entry_date' 
        where id =  '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            
                $sql = "UPDATE subtest SET status = 'Active',approve_by = '".$_GET["emp_id"]."',approve_date = '$entry_date' 
                where test_id =  '".$_GET["id"]."'";
                
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
                
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
    else if ($_GET["type"] == "saveTest_medical") {
        $sql = "INSERT INTO test_medical  (test,test_name,plant_id) VALUES ('".$input["test"]."','".$input["test_name"]."','".$_GET["plant_id"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    		}
    	 else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    else if ($_GET["type"] == "gettest_method_no") {
        
        $output = array();
        $sql = "SELECT test_method_no from test group by test_method_no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
        
    } 
    else if ($_GET["type"] == "Check_iest_id") {
        
        $output = array();
         $sql = "SELECT test_id from test where test_id='".$input["value"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"success\"}";
        }
        // echo json_encode($output);
    
        
    } 
    else if ($_GET["type"] == "Test_for_medical") {

         $sql = "INSERT INTO medical_tests  (test_name,plant_id) VALUES ('".$input["test_name"]."','".$_GET["plant_id"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    		}
    	 else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    else if ($_GET["type"] == "get_saveTest_medical") {
        
        $output = array();
        $sql = "SELECT * from test_medical ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
        
    }
      else if ($_GET["type"] == "saveStp") {
        
         $sql = "update specification set stp_no ='".$input["stp_no"]."' where specification_no='".$_GET["Specificspecification_no"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
    
     else if ($_GET["type"] == "save_Request") {
        
         $sql = "update test set rivision='Sent' where test_method_no='".$input["doc_no"]."' ";
        if ($conn->query($sql)) {
            
             $sql1 = "INSERT INTO rivision_request( entry_by,department,entry_date,reason_Revision,pro_content,cur_content,revison_from,doc_name, doc_no, Version_no, rivision_chnages, plant_id,description) 
                        VALUES ('".$_GET["emp_id"]."','".$_GET["department"]."','$entry_date','".$input["reason_Revision"]."','".$input["pro_content"]."','".$input["cur_content"]."','".$input["revison_from"]."', '".$input["doc_name"]."','".$input["doc_no"]."','".$input["Version_no"]."',
                        '".$input["rivision_chnages"]."','".$_GET["plant_id"]."','".$input["description"]."')";
            $conn->query($sql1);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
    else if ($_GET["type"] == "get_Test_for_medical") {
        
        $output = array();
        $sql = "SELECT * from medical_tests ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
        
    }
    else if ($_GET["type"] == "getTestsLog") {
        $output = array();
        $plant = $conn->real_escape_string($_GET["plant_id"]);
        $sql = "SELECT t.*,
            TRIM(CONCAT(COALESCE(e.firstname,''),' ',COALESCE(e.middlename,''),' ',COALESCE(e.lastname,''))) AS entry_by_name,
            TRIM(CONCAT(COALESCE(a.firstname,''),' ',COALESCE(a.middlename,''),' ',COALESCE(a.lastname,''))) AS approve_by_name
            FROM test t
            LEFT JOIN employee e ON e.emp_id = t.entry_by AND e.plant_id = t.plant_id
            LEFT JOIN employee a ON a.emp_id = t.approve_by AND a.plant_id = t.plant_id
            WHERE t.plant_id = '".$plant."'
            ORDER BY t.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (trim($row['entry_by_name']) === '') {
                    $row['entry_by_name'] = $row['entry_by'];
                }
                if (trim($row['approve_by_name']) === '') {
                    $row['approve_by_name'] = $row['approve_by'];
                }
                $output1 = array();
                $sql1 = "SELECT * from subtest where test_id =  '".$row["id"]."'  ";
                $result1 = $conn->query($sql1);
                if ($result1 && $result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['subtests'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getTestsForMoa") {
        // MOA Master — Test Specific: plant first, then unfiltered fallback so the tab is never blank
        header('Content-Type: application/json; charset=utf-8');
        $output = array();
        $plant = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $sql = "SELECT t.id, t.plant_id, t.test_type, t.test, t.test_method_no, t.status, t.method,
                       t.entry_by, t.entry_date, t.approve_by, t.approve_date
                FROM test t
                WHERE (t.plant_id = '".$plant."' OR '".$plant."' = '' OR t.plant_id IS NULL OR t.plant_id = '')
                ORDER BY t.id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        if (count($output) === 0) {
            $result2 = $conn->query("SELECT t.id, t.plant_id, t.test_type, t.test, t.test_method_no, t.status, t.method,
                       t.entry_by, t.entry_date, t.approve_by, t.approve_date
                FROM test t ORDER BY t.id DESC LIMIT 500");
            if ($result2 && $result2->num_rows > 0) {
                while ($row = $result2->fetch_assoc()) {
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "seedStandardTests") {
        $plant = $conn->real_escape_string($_GET["plant_id"]);
        $empId = $conn->real_escape_string($_GET["emp_id"]);
        $masterUserName = isset($input["masterUserName"]) ? $conn->real_escape_string(trim($input["masterUserName"])) : 'Master User';
        $tests = isset($input["tests"]) && is_array($input["tests"]) ? $input["tests"] : array();
        if (count($tests) === 0) {
            $tests = array(
                array("test_type" => "Chemical", "test" => "Assay", "testCode" => "TM-001", "subtestList" => array(
                    array("subtest" => "Standard Preparation"), array("subtest" => "Sample Preparation"),
                    array("subtest" => "Procedure"), array("subtest" => "Calculation"))),
                array("test_type" => "Indentification", "test" => "Identification", "testCode" => "TM-002", "subtestList" => array(
                    array("subtest" => "By IR Spectrum"), array("subtest" => "By HPLC Retention Time"), array("subtest" => "By TLC Rf Value"))),
                array("test_type" => "Chemical", "test" => "Related Substances", "testCode" => "TM-003", "subtestList" => array(
                    array("subtest" => "Standard Preparation"), array("subtest" => "Sample Preparation"),
                    array("subtest" => "Chromatographic Conditions"), array("subtest" => "Calculation"))),
                array("test_type" => "LOD", "test" => "Loss on Drying", "testCode" => "TM-004", "subtestList" => array(
                    array("subtest" => "Procedure"), array("subtest" => "Calculation"))),
                array("test_type" => "Chemical", "test" => "Water Content", "testCode" => "TM-005", "subtestList" => array(
                    array("subtest" => "Karl Fischer Procedure"), array("subtest" => "Calculation"))),
                array("test_type" => "Physcial", "test" => "Description", "testCode" => "TM-006", "subtestList" => array(
                    array("subtest" => "Appearance"), array("subtest" => "Colour"))),
                array("test_type" => "Chemical", "test" => "pH", "testCode" => "TM-007", "subtestList" => array(
                    array("subtest" => "Procedure"))),
                array("test_type" => "Microbiology", "test" => "Microbial Limit", "testCode" => "TM-008", "subtestList" => array(
                    array("subtest" => "Total Aerobic Microbial Count"), array("subtest" => "Total Yeast and Mould Count"),
                    array("subtest" => "Escherichia coli"), array("subtest" => "Salmonella")))
            );
        }
        $added = 0;
        $skipped = 0;
        foreach ($tests as $testRow) {
            $testType = $conn->real_escape_string($testRow["test_type"]);
            $testName = $conn->real_escape_string($testRow["test"]);
            $testCode = $conn->real_escape_string($testRow["testCode"]);
            $check = $conn->query("SELECT id FROM test WHERE plant_id='".$plant."' AND test_method_no='".$testCode."' LIMIT 1");
            if ($check && $check->num_rows > 0) {
                $skipped++;
                continue;
            }
            $sql = "INSERT INTO test (plant_id,multiple_anaylist,test_type,test,test_method_no,entry_by,entry_date,calculation,status,approve_by,approve_date) VALUES
            ('".$plant."','NO','".$testType."','".$testName."','".$testCode."','".$empId."','$entry_date','Not Applicable','Active','".$empId."','$entry_date')";
            if ($conn->query($sql)) {
                $test_id = $conn->insert_id;
                $subtests = isset($testRow["subtestList"]) && is_array($testRow["subtestList"]) ? $testRow["subtestList"] : array();
                for ($i = 0; $i < count($subtests); $i++) {
                    $sub = $subtests[$i];
                    $subName = $conn->real_escape_string($sub["subtest"]);
                    $sqlSub = "INSERT INTO subtest (plant_id,test_id,test_type,test,subtest,entry_by,entry_date)
                    VALUES ('".$plant."','".$test_id."','".$testType."','".$testName."','".$subName."','".$empId."','$entry_date')";
                    $conn->query($sqlSub);
                }
                $added++;
            }
        }
        echo json_encode(array(
            "status" => "success",
            "added" => $added,
            "skipped" => $skipped,
            "masterUserName" => $masterUserName
        ));
    } 
        else if ($_GET["type"] == "getTestsLogMeha") {
        $output = array();
        $sql = "SELECT * from test where plant_id =  '".$_GET["plant_id"]."' "; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $sql1 = "SELECT * from subtest where test_id =  '".$row["id"]."'  "; 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['subtests'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "approve_method") {
        
        $sql = "update test set method='".$_GET["status"]."' where test_method_no='".$_GET["method_no"]."' ";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    }
      else if ($_GET["type"] == "getTestsapproval") {
        $output = array();
        $sql = "SELECT * from test where plant_id =  '".$_GET["plant_id"]."' AND status = 'Pending'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output1 = array();
                $sql1 = "SELECT * from subtest where test_id =  '".$row["id"]."'  "; 
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['subtests'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_S_Test") {
        
        $output = array();
        $sql = "SELECT test_type from test GROUP by test_type";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
        
    }
          else if ($_GET["type"] == "downloadTests") {
        $_GET['filename'] = 'Test Master'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html="";
       $sql= "SELECT test_methods.*, subtest.test FROM test_methods LEFT JOIN subtest ON test_methods.test_id = subtest.test_id  where test_method_no='".$_GET["test_method_no"]."'";
                 $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {

         $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td colspan="4" style="text-align:center" >Method of Analysis</td>
                </tr>';
          
                                $output[] = $row;
                               $html.=' <tr>
                                        <th style="text-align:center" > Method Name:</th>
                                          <th colspan="3"> '.$row['test'].' </th>
                                    </tr>
                                </thead>';
         $html.="</table>";
             $html.=" <div> </div>";
        $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td colspan="3" >General Instruction</td>
                </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <th>Parameter Heading</th>
                        <th>Description</th>
                        <th>Specification/Limit</th>
                    </tr>
                </thead>';
                
                  $json_obj = $row['Genral_Instruction'];
                $array = json_decode($json_obj, true);
             
                foreach ($array as $values)
                {
                  
                    $parameter = $values['parameter'];
                    $discrption = $values['discrption'];
                    $spec_limit = $values['spec_limit'];
             $html.='<tr>
                    <td> ' . $parameter . ' </td>
                    <td> ' . $discrption . ' </td>
                    <td> ' . $spec_limit . ' </td>
             </tr>';
              }

         $html.="</table>";
                 $html.=" <div> </div>";

       $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center; border: solid 1px black">
                    <td >Purpose</td>
                </tr>
            </thead>
             <tr style="border: solid 1px black">
                    <td >'.$row['purpose'].'</td>
                </tr>'; 
                 $html.="</table>";
                    $html.=" <div> </div>";

       $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center; border: solid 1px black">
                    <td >Scope</td>
                </tr>
            </thead>
             <tr style="border: solid 1px black">
                    <td >'.$row['Scope'].'</td>
                </tr>'; 
                 $html.="</table>";
                 
                   $html.=" <div> </div>";
        $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td colspan="2" >Associative Document</td>
                </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <th>Document No. </th>
                        <th>Document Name</th>
                    </tr>
                </thead>';
                
                  $json_obj = $row['Associative_Document'];
                $array = json_decode($json_obj, true);
             
                foreach ($array as $values)
                {
                  
                    $doc_no = $values['doc_no'];
                    $doc_name = $values['doc_name'];
             $html.='<tr>
                    <td> ' . $doc_no . ' </td>
                    <td> ' . $doc_name . ' </td>
             </tr>';
              }

         $html.="</table>";
          $html.=" <div> </div>";
        $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td colspan="2" >Definition</td>
                </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <th>Term</th>
                        <th>Definition</th>
                    </tr>
                </thead>';
                
                  $json_obj = $row['defination'];
                $array = json_decode($json_obj, true);
             
                foreach ($array as $values)
                {
                  
                    $short = $values['short'];
                    $Full = $values['Full'];
             $html.='<tr>
                    <td> ' . $short . ' </td>
                    <td> ' . $Full . ' </td>
             </tr>';
              }

         $html.="</table>";
                                   $html.=" <div> </div>";
                        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center; border: solid 1px black">
                    <td >Safety</td>
                </tr>
            </thead>
             <tr style=" border: solid 1px black">
                    <td >'.$row['Safety'].'</td>
                </tr>';
           
        $html.="</table>";
         $html.=" <div> </div>";
        $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td>Testing Instruction</td>
                </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <th>testing_instruction</th>
                    </tr>
                </thead>';
                
                  $json_obj = $row['testinginstruction'];
                $array = json_decode($json_obj, true);
                foreach ($array as $values)
                {
                    $testing_heading = $values['testing_heading'];
             $html.='<tr>
                    <td> ' . $testing_heading . ' </td>
             </tr>';
              }

         $html.="</table>";
          $html.=" <div> </div>";
        $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td colspan="2" >Equipments / Instruments</td>
                </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <th>Equipment Name</th>
                        <th>Equipment Code</th>
                    </tr>
                </thead>';
                  $json_obj = $row['equipment_instruments'];
                $array = json_decode($json_obj, true);
                foreach ($array as $values)
                {
                    $discrption = $values['equipment_id'];
                    $spec_limit = $values['equipment_name'];
             $html.='<tr>
                    <td> ' . $discrption . ' </td>
                    <td> ' . $spec_limit . ' </td>
             </tr>';
              }

         $html.="</table>";
                         $html.=" <div> </div>";
                        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center; border: solid 1px black">
                    <td >Procedure</td>
                </tr>
            </thead>
             <tr style=" border: solid 1px black">
                    <td >'.$row['procedure'].'</td>
                </tr>';
           
        $html.="</table>";
         $html.=" <div> </div>";
        $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td colspan="2" >Chemical / Reagents</td>
                </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <th>Chemical Name</th>
                        <th>Grade</th>
                    </tr>
                </thead>';
                  $json_obj = $row['chemical_reagents'];
                $array = json_decode($json_obj, true);
                foreach ($array as $values)
                {
                    $discrption = $values['material_name'];
                    $spec_limit = $values['grade'];
             $html.='<tr>
                    <td> ' . $discrption . ' </td>
                    <td> ' . $spec_limit . ' </td>
             </tr>';
              }

         $html.="</table>";
       
                $html.=" <div> </div>";
        $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td colspan="3" >Glasswares</td>
                </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                         <th>Glassware Name</th>
                         <th>Capacity</th>
                         <th>Type</th>
                    </tr>
                </thead>';
                  $json_obj = $row['glasswares'];
                $array = json_decode($json_obj, true);
                foreach ($array as $values)
                {
                    $material_name = $values['material_name'];
                    $capacity = $values['capacity'];
                       $class_type = $values['class_type'];
             $html.='<tr>
                    <td> ' . $material_name . ' </td>
                    <td> ' . $capacity . ' </td>
                     <td> ' . $class_type . ' </td>
             </tr>';
              }

         $html.="</table>";
          $html.=" <div> </div>";
        $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td colspan="3" >Weighing Balance:</td>
                </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                         <th>Glassware Name</th>
                         <th>Capacity</th>
                         <th>Type</th>
                    </tr>
                </thead>';
                  $json_obj = $row['balance'];
                $array = json_decode($json_obj, true);
                foreach ($array as $values)
                {
                    $equipment_name = $values['equipment_name'];
                    $make = $values['make'];
                    $capacity = $values['capacity'];
             $html.='<tr>
                    <td> ' . $equipment_name . ' </td>
                    <td> ' . $capacity . ' </td>
                     <td> ' . $make . ' </td>
             </tr>';
              }

         $html.="</table>";
          
           $html.=" <div> </div>";
        $html.='<table border="1">
        <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td colspan="3" >Dilutions:</td>
                </tr>
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                <th rowspan="2" style="width: 3%;">Sr.</th>
                <th colspan="2" style="width: 40%;">First Solution</th>
                <th colspan="2" style="width: 40%;">Second Solution</th>
                <th rowspan="2" style="width: 10%;">Make up Volume upto</th>
                <th rowspan="2" style="width: 7%;">Result</th>
            </tr>
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                <th style="width: 20%;">Name</th>
                <th style="width: 20%;">Qty</th>
                <th style="width: 20%;">Name</th>
                <th style="width: 20%;">Qty</th>
            </tr>
            ';
            //       $json_obj = $row['dilutions'];
            //     $array = json_decode($json_obj, true);
            //     foreach ($array as $values)
            //     {
            //         $result = $values['result'];
            //         $make = $values['make'];
            //         $second_qty = $values['second_qty'];
            //         $second_name = $values['second_name'];
            //         $first_qty = $values['first_qty'];
            //         $first_name = $values['first_name'];
            //         $i=1;
            //  $html.='<tr>
            //         <td style="width: 3%;">'.$i++.'</td>
            //         <td  style="width: 20%;"> '.$first_name . ' </td>
            //         <td  style="width: 20%;"> ' . $first_qty . ' </td>
            //         <td  style="width: 20%;"> ' . $second_name . ' </td>
            //         <td  style="width: 20%;"> ' . $second_qty . ' </td>
            //         <td style="width: 10%;"> ' . $make . ' </td>
            //         <td style="width: 7%;"> ' . $result . ' </td>
            //  </tr>';
            //   }
       
         $html.="</table>";
          $html.=" <div> </div>";

       $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;text-align:center; border: solid 1px black">
                    <td >Trending</td>
                </tr>
            </thead>
             <tr style="border: solid 1px black">
                    <td >'.$row['Trendings'].'</td>
                </tr>'; 
                 $html.="</table>";
        $html.=" <div> </div>";
        $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td colspan="3" >Volumetric Solutions</td>
                </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <th>Solution Name</th>
                        <th>Percentage/Strength</th>
                        <th>Unit</th>
                    </tr>
                </thead>';
                  $json_obj = $row['volumetric_solutions'];
                $array = json_decode($json_obj, true);
                foreach ($array as $values)
                {
                    $equipment_name = $values['solution_name'];
                    $make = $values['strength'];
                    $capacity = $values['unit'];
             $html.='<tr>
                    <td> ' . $equipment_name . ' </td>
                     <td> ' . $make . ' </td>
                    <td> ' . $capacity . ' </td>
                    
             </tr>';
              }

         $html.="</table>";
            $html.=" <div> </div>";
        $html.='<table border="1" cellpadding="5">
                <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black;text-align:center;">
                    <td colspan="4" >Revision History:</td>
                </tr>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <th>Version</th>
                        <th>Date Effective</th>
                        <th>Section</th>
                        <th>Description and Rationale</th>
                    </tr>
                </thead>';
                  $json_obj = $row['revision_history'];
                $array = json_decode($json_obj, true);
                foreach ($array as $values)
                {
                    $version = $values['version'];
                    $doe = $values['doe'];
                      $section = $values['section'];
                    $desc_rational = $values['desc_rational'];
             $html.='<tr>
                    <td> ' . $version . ' </td>
                    <td> ' . $doe . ' </td>
                     <td> ' . $section . ' </td>
                    <td> ' . $desc_rational . ' </td>
             </tr>';
              }

         $html.="</table>";
          
                            }} 
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SubTests Log.pdf', 'I');
    }

    else if ($_GET["type"] == "getTestsLog") {
        $output = array();
       //$sql = "SELECT * FROM test WHERE test_type LIKE '%".$_GET["test_type"]."%' ";
        //  $sql = "select * from (SELECT a.test,a.test_no,a.test_type,count(b.subtest)as no_of_products from subtest b join test a on a.test=b.test and a.test_type=b.test_type group by a.test,a.test_type union ALL SELECT a.test ,a.test_no,a.test_type,count(b.subtest) as no_of_products from subtest b join test a on a.test=b.test and a.test_type=b.test_type group by a.test,a.test_type)sub";
           $sql = "SELECT a.test,MAX(a.test_no) as test_no ,a.test_type,count(b.subtest)
          as no_of_products from subtest b join test a on a.test=b.test and a.test_type=b.test_type 
          group by a.test,a.test_type ORDER BY test_no DESC";
         //$sql = "SELECT *, test,test_type FROM test";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getTestssubtest") {
        $output = array();
       //$sql = "SELECT * FROM test WHERE test_type LIKE '%".$_GET["test_type"]."%' ";
         $sql = "SELECT t.*,s.subtest FROM test t left JOIN subtest s on t.test=s.test where test_no='".$_GET["test_no"]."'";
        //  $sql = "SELECT *, test as test_name,test_type FROM test";
         //$sql = "SELECT *, test,test_type FROM test";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getTests") {
        $output = array();
        $sql = "SELECT * FROM test WHERE classification LIKE '%".$_GET["classification"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                $sql = "SELECT * FROM subtest WHERE test_type='".$row["test_type"]."' AND test='".$row["test"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["subtests"] = $output1;
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveSubTest") {
        $sql = "INSERT INTO subtest (user_no, classification, dosage_form, test_type, test, subtest, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["classification"]."','".$input["dosage_form"]."','".$input["test_type"]."','".$input["test"]."','".$input["subtest"]."','".$_GET["emp_id"]."','".$entry_date."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingSubTests") {
        $output = array();
        $sql = "SELECT * FROM subtest WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateSubTest") {
        $sql = "UPDATE subtest SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getSubTestsLog") {
        $output = array();
        $sql = "SELECT * FROM subtest WHERE user_no='".$_GET["user_no"]."' AND classification LIKE '%".$_GET["classification"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND test_type LIKE '%".$_GET["test_type"]."%' AND status LIKE '%".$_GET["status"]."%' AND test LIKE '%".$_GET["test"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "DownloadPdf") {
        $_GET['filename'] = 'Test Master'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
           $sql = "SELECT a.*,b.material_subtype,b.material_name, b.grade,b.storage_condition FROM specification a 
            left JOIN material b on a.material_code = b.material_code WHERE ismoa = 'pending' AND a.status = 'approve' AND   a.specification_no = '".$_GET["id"]."' and a.plant_id = '".$_GET["plant_id"]."' order by a.id desc";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
        $html="";

        $html .= '

        <table cellpadding="5" border="1">
        <tr>
            <td colspan="6" style="text-align: center; font-weight: bold;">STANDARD TEST SPECIFICATION QUALITY CONTROL DEPARTMENT</td>
        </tr>
        <tr>
            <td colspan="6" style="text-align: center; font-weight: bold;">RAW MATERIAL SPECIFICATION</td>
        </tr>
        <tr>
            <td style="font-weight: bold; width: 25%;">Name of Material</td>
            <td  style="width: 75%;">'.$row['material_name'].'</td>
        </tr>
        <tr>
            <td style="font-weight: bold; width: 25%;">Reference</td>
            <td style="width: 25%;">'.$row['gradeName'].'</td>
            <td style="font-weight: bold; width: 25%;">Spec/STP No.</td>
            <td style="width: 25%;">'.$row['stp_no'].'</td>
        </tr>
          <tr>
            <td style="font-weight: bold; width: 25%;">Supersedes</td>
            <td style="width: 25%;">'.$row['supersede_no'].'</td>
            <td style="font-weight: bold;width: 25%;">Revision No</td>
            <td style="width: 25%;">'.$row['version_no'].'</td>
        </tr>
          <tr>
            <td style="font-weight: bold; width: 25%;">Effective Date</td>
            <td style="width: 25%;">'.$row['effective_date'].'</td>
            <td style="font-weight: bold;width: 25%;">Review Date</td>
            <td style="width: 25%;">'.$row['review_date'].'</td>
        </tr>';
     
    $html .= '  </table> <div></div>';
             
                  
$html .= '<table cellpadding="5" border="1">
    <tr style="text-align: center;">
        <td style="width: 10%;"><b>Sr. No.</b></td>
        <td style="width: 35%;"><b>Tests</b></td>
        <td style="width: 55%;"><b>Specification</b></td>
    </tr>';

$i = 1;
$sql1 = "SELECT * FROM spec_tests WHERE specification_no = '".$row["specification_no"]."' ";
$result1 = $conn->query($sql1);

if ($result1->num_rows > 0) {
    while ($row1 = $result1->fetch_assoc()) {
        $row1["method_details"] = json_decode($row1["method_details"]);

        $html .= '<tr>
            <td style="text-align: center;">'.$i++.'</td>
            <td>'.htmlspecialchars($row1["test"]).'</td>
            <td>'.htmlspecialchars($row1["limits"]).'</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="3" style="text-align:center;">No tests available</td></tr>';
}

$html .= '</table><br><br>';

 
$html .= '<br><br>
 
<div style="margin-bottom: 40px;">
 
</div>
 
 
<table  cellpadding="5" border="1">
  <tr>
    <td style="width: 16%;  padding: 6px;"></td>
    <td style="width: 21%;  text-align: center; padding: 6px;">Prepared By</td>
    <td style="width: 21%;  text-align: center; padding: 6px;">Checked By</td>
    <td style="width: 21%;  text-align: center; padding: 6px;">Reviewed by</td>
    <td style="width: 21%;  text-align: center; padding: 6px;">Approved By</td>
  </tr>
  <tr>
    <td style=" padding: 6px;">Department</td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
  </tr>
   <tr>
    <td style=" padding: 6px;">Sign</td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
  </tr>
   <tr>
    <td style=" padding: 6px;">Date</td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
  </tr>
   <tr>
    <td style=" padding: 6px;">Name</td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
    <td style="padding: 6px;"></td>
  </tr>';}}
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SubTests Log.pdf', 'I');
    }
    
        else if ($_GET["type"] == "downloadSubTestsLog") {
        $_GET['filename'] = 'SubTests Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">SubTests Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ">Sr No</td>
                    <td style="width: 20%; ">Classification</td>
                    <td style="width: 20%; ">Type</td>
                    <td style="width: 20%; ">Test</td>
                    <td style="width: 20%; ">TestType</td>
                    <td style="width: 15%; ">Sub Test</td>
                </tr>
            </thead>';
            $output = array();
        $sql = "SELECT * FROM subtest WHERE user_no='".$_GET["user_no"]."' AND classification LIKE '%".$_GET["classification"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND test_type LIKE '%".$_GET["test_type"]."%' AND status LIKE '%".$_GET["status"]."%' AND test LIKE '%".$_GET["test"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
                $html.='
                <tr nobr="true">
                    <td style="width: 5%; ">'.$i.'.</td>
                    <td style="width: 20%;">'.$row['classification'].'</td>
                    <td style="width: 20%;">'.$row['dosage_form'].'</td>
                    <td style="width: 20%; ">'.$row['test'].'</td>
                    <td style="width: 20%;">'.$row['test_type'].'</td>
                    <td style="width: 15%;">'.$row['subtest'].'</td>
                </tr>';
                $i++;
            }
        }
    
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SubTests Log.pdf', 'I');
    }

    else if ($_GET["type"] == "downloadSubTestsLog") {
        $_GET['filename'] = 'SubTests Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">SubTests Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ">Sr No</td>
                    <td style="width: 20%; ">Classification</td>
                    <td style="width: 20%; ">Type</td>
                    <td style="width: 20%; ">Test</td>
                    <td style="width: 20%; ">TestType</td>
                    <td style="width: 15%; ">Sub Test</td>
                </tr>
            </thead>';
            $output = array();
        $sql = "SELECT * FROM subtest WHERE user_no='".$_GET["user_no"]."' AND classification LIKE '%".$_GET["classification"]."%' AND dosage_form LIKE '%".$_GET["dosage_form"]."%' AND test_type LIKE '%".$_GET["test_type"]."%' AND status LIKE '%".$_GET["status"]."%' AND test LIKE '%".$_GET["test"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $row = array_map('utf8_encode', $row);
                $output[] = $row;
                $html.='
                <tr nobr="true">
                    <td style="width: 5%; ">'.$i.'.</td>
                    <td style="width: 20%;">'.$row['classification'].'</td>
                    <td style="width: 20%;">'.$row['dosage_form'].'</td>
                    <td style="width: 20%; ">'.$row['test'].'</td>
                    <td style="width: 20%;">'.$row['test_type'].'</td>
                    <td style="width: 15%;">'.$row['subtest'].'</td>
                </tr>';
                $i++;
            }
        }
    
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SubTests Log.pdf', 'I');
    }

}

$conn->close();
?>