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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "saveIncident") {
		$sql = "INSERT INTO incident (user_no, department, related_to, category, type, classification, 
		description, immediate_action, initiate_by, initiate_date) VALUES 
		('".$_GET["user_no"]."', '".$_GET["department"]."', '".$input["related_to"]."', '".$input["category"]."', '".$input["type"]."', '".$input["classification"]."', '".$input["description"]."', '".$input["immediate_action"]."', '".$_GET["emp_id"]."', '$entry_date')";
		if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	} else if ($_GET["type"] == "getPendingIncidents") {
	    $output = array();
	    $sql = "SELECT * FROM incident WHERE status='pending'  ";
	    //WHERE user_no='".$_GET["user_no"]."' AND department='".$_GET["department"]."' AND status='pending'ORDER BY id DESC
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "incidentChecking") {
	    if ($input["incident_status"] == "Approve") {
	        $input["incident_status"] = "Checked";
	    }
	    $sql = "UPDATE incident SET investigation='".$input["investigation"]."', impact_assessment='".json_encode($input["assessments"])."', other_assessment='".$input["other_assessment"]."', corrective_action='".$input["corrective_action"]."', preventive_action='".$input["preventive_action"]."', customer_notification='".$input["customer_notification"]."',check_by='".$_GET["check_by"]."', check_date='$entry_date', status='".$input["incident_status"]."' WHERE incident_no='".$_GET["incident_no"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	        
	        $departments = $input["departments"];
	        for ($i = 0; $i < count($departments); $i++) {
	            $department = $departments[$i];
	            $sql = "INSERT INTO incident_comments (user_no, incident_no, department) VALUES ('".$_GET["user_no"]."', '".$_GET["incident_no"]."', '".$department."')";
	            $conn->query($sql);
	        }
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] == "getCheckedIncidents") {
	    $output = array();
	    $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND status='Checked' ORDER BY id DESC";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["impact_assessment"] = json_decode($row["impact_assessment"]);
	            $output1 = array();
	            $sql = "SELECT * FROM incident_comments WHERE incident_no='".$row["incident_no"]."'";
    	        $result1 = $conn->query($sql);
    	        if ($result1->num_rows > 0) {
    	            while ($row1 = $result1->fetch_assoc()) {
    	                $output1[] = $row1;
    	            }
    	        }
    	        $row["departments"] = $output1;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "incidentVerification") {
	    $sql = "UPDATE incident SET status='".$_GET["status"]."', verify_by='".$_GET["emp_id"]."', verify_date='".$entry_date."', qa_comment='".$_GET["qa_comment"]."' WHERE incident_no='".$_GET["incident_no"]."'";
	    if ($conn->query($sql)) {
		    echo "{\"status\":\"success\"}";
		    
		    $sql = "SELECT * FROM incident_comments WHERE incident_no='".$_GET["incident_no"]."' AND status='pending'";
	        $result = $conn->query($sql);
	        if ($result->num_rows == 0) {
	            $sql = "UPDATE incident SET status='active' WHERE incident_no='".$_GET["incident_no"]."'";
	            $conn->query($sql);
	        }
		} else {
		    echo "{\"status\":\"".$conn->error."\"}";
		}
	} else if ($_GET["type"] == "getPendingReviewDept") {
	    $output = array();
	    $sql = "SELECT * FROM incident WHERE status='verify'";
	    //WHERE user_no='".$_GET["user_no"]."' AND status='verify'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["impact_assessment"] = json_decode($row["impact_assessment"]);
	            $output = array();
	            $sql1 = "SELECT * FROM incident_comments WHERE incident_no='".$row["incident_no"]."' ";
	            //AND department='".$_GET["department"]."' AND status='pending'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                $output[] = $row;
	            }
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "saveReview") {
	    $sql = "UPDATE incident_comments SET comment='".$_GET["comment"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date', status='complete' WHERE incident_no='".$_GET["incident_no"]."' AND department='".$_GET["department"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	        
	        $sql = "SELECT * FROM incident_comments WHERE incident_no='".$_GET["incident_no"]."' AND status='pending'";
	        $result = $conn->query($sql);
	        if ($result->num_rows == 0) {
	            $sql = "UPDATE incident SET status='Active' WHERE incident_no='".$_GET["incident_no"]."'";
	            $conn->query($sql);
	        }
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] == "getActiveIncidents") {
	    $output = array();
	    $sql = "SELECT * FROM incident WHERE 
	    user_no='".$_GET["user_no"]."' AND department='".$_GET["department"]."' AND status='Active'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["impact_assessment"] = json_decode($row["impact_assessment"]);
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_comments WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "saveDeptActivity") {
	    $sql = "UPDATE incident SET isextension='".$input["isextension"]."', extension_in='".$input["extension_in"]."', extension_justification='".$input["extension_justification"]."', completion_date='".$input["completion_date"]."', extension_by='".$_GET["emp_id"]."', extension_date='".$entry_date."', status='Extension' WHERE incident_no='".$_GET["incident_no"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} 
	 else if($_GET["type"]=='updateTestingStatus') {
    //   echo  $sql = "UPDATE testing SET  correction='pending' WHERE testing_no='".$_GET["testingID"]."'";
    //       if ($conn->query($sql2)) { echo "{\"status\":\"success\"}";
    //           } 
    //         // }
    //         else {
    //             echo "{\"status\":\"".$conn->error."\"}";
    //         }
        
        
	    $sql = "UPDATE testing SET  correction='pending' WHERE testing_no='".$_GET["testingID"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	
   
    }
	else if ($_GET["type"] == "getPendingExtensionVerifications") {
	    $output = array();
	    $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND status='Extension'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["impact_assessment"] = json_decode($row["impact_assessment"]);
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_comments WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "verifyExtension") {
	    $sql = "UPDATE incident SET extension_verify_by='".$_GET["emp_id"]."', extension_verify_date='$entry_date', status='".$_GET["status"]."' WHERE incident_no='".$_GET["incident_no"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] == "getPendingEvaluatation1") {
	    $output = array();
	    $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' 
	    AND department='".$_GET["department"]."' AND status='evaluate1'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["impact_assessment"] = json_decode($row["impact_assessment"]);
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_comments WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "evaluate1Checking") {
	    $sql = "UPDATE incident SET evaluate1_by='".$_GET["emp_id"]."', evaluate1_date='$entry_date', evaluate1_comment='".$_GET["comment"]."', status='evaluate2' WHERE incident_no='".$_GET["incident_no"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] == "getPendingEvaluatation2") {
	    $output = array();
	    $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' 
	    AND department='".$_GET["department"]."' AND status='evaluate2'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["impact_assessment"] = json_decode($row["impact_assessment"]);
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_comments WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "evaluate2Checking") {
	    $sql = "UPDATE incident SET evaluate2_by='".$_GET["emp_id"]."', evaluate2_date='$entry_date', evaluate2_comment='".$_GET["comment"]."', status='complete' WHERE incident_no='".$_GET["incident_no"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] == "getCompletedIncident") {
	    $output = array();
	    $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' 
	    AND department='".$_GET["department"]."' AND status='complete'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["impact_assessment"] = json_decode($row["impact_assessment"]);
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_comments WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_files WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	} else if ($_GET["type"] == "uploadAttachment") {
	    $attachment = "";
        if(isset($_FILES['attachment'])) {
            $id = date("YmdHis", $timestamp);
            $file_tmp =$_FILES['attachment']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['attachment']['name'])));
            $file_name = $id.".".$file_ext;
            $attachment = $file_name;
            move_uploaded_file($file_tmp,"../upload/incident/".$file_name);
            
            $sql = "INSERT INTO incident_files (incident_no, particular, file) VALUES ('".$_GET["incident_no"]."', '".$_POST["particular"]."', '$attachment')";
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        } else {
            echo "{\"status\":\"failed\"}";
        }
	} else if ($_GET["type"] == "getIncidentDetails") {
	    $output = array();
	    $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND incident_no='".$_GET["incident_no"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["impact_assessment"] = json_decode($row["impact_assessment"]);
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_comments WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_files WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            echo json_encode($row);
	        }
	    } else {
	        echo "{}";
	    }
	} else if ($_GET["type"] == "closeIncident") {
	    $sql = "UPDATE incident SET close_by='".$_GET["emp_id"]."', close_date='$entry_date', status='close' WHERE incident_no='".$_GET["incident_no"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	} else if ($_GET["type"] == "getIncidentLog") {
	    $output = array();
	    $sql = "SELECT *, DATE(initiate_date) as initiate_date, DATE(close_date) as close_date FROM incident 
	    WHERE user_no='".$_GET["user_no"]."' AND category LIKE '%".$_GET["category"]."%' AND DATE(initiate_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["impact_assessment"] = json_decode($row["impact_assessment"]);
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_comments WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_files WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	else if($_GET["type"]=="downloadIncidentLog"){
	      
	     $_GET['filename'] = 'Incidentlog '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
	      
	      $html.='
	      <h2 style="text-align:cenetr;color:brown">Incident Log</h2>
	      <table border="1" cellpadding="5">
	              <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ">Sr No</td>
                    <td style="width: 15%; ">Date</td>
                    <td style="width: 15%; ">Incident No</td>
                    <td style="width: 15%; ">Department</td>
                    <td style="width: 15%; ">category</td>
                    <td style="width: 10%; ">Type</td>
                    <td style="width: 10%; ">Related To</td>
                    <td style="width: 15%; ">Close date</td>
                 </tr> ';
       $output = array();
	    $sql = "SELECT *, DATE(initiate_date) as initiate_date, DATE(close_date) as close_date FROM incident WHERE user_no='".$_GET["user_no"]."' AND category LIKE '%".$_GET["category"]."%' AND DATE(initiate_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
	    $result = $conn->query($sql);
	    $i=1;
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            $row["impact_assessment"] = json_decode($row["impact_assessment"]);
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_comments WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["departments"] = $output1;
	            
	            $output1 = array();
	            $sql1 = "SELECT * FROM incident_files WHERE incident_no='".$row["incident_no"]."'";
	            $result1 = $conn->query($sql1);
	            if ($result1->num_rows > 0) {
	                while ($row1 = $result1->fetch_assoc()) {
	                    $output1[] = $row1;
	                }
	            }
	            $row["attachments"] = $output1;
	            $output[] = $row;
                 
         $html.='<tr>
                    <td style="width: 5%; ">'.$i.'</td>
                    <td style="width: 15%; ">'.$row['initiate_date'].'</td>
                    <td style="width: 15%; ">'.$row['incident_no'].'</td>
                    <td style="width: 15%; ">'.$row['department'].'</td>
                    <td style="width: 15%; ">'.$row['category'].'</td>
                    <td style="width: 10%; ">'.$row['type'].'</td>
                    <td style="width: 10%; ">'.$row['related_to'].'</td>
                    <td style="width: 15%; ">'.$row['close_date'].'</td>
                 </tr>';
                 $i++;
	         }
	    }
	      $html.='</table>';
	     
	      $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('incidentlog .pdf', 'I');
	}
	 
else if ($_GET["type"] == "downloadIncident") {
       
        $_GET['filename'] = 'Incident Report'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.= "";
        
         $sql = "SELECT * FROM incident WHERE user_no='".$_GET["user_no"]."' AND incident_no='".$_GET["incident_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

        $html.='<h2 style="text-align:center; color:brown">Incident Reporting Form</h2>
                <table cellpadding="5">
                    <tr>
                        <td style="width: 20%;"><b>Date</b>&nbsp;'.$row['initiate_date'].'</td>
                        <td style="width: 40%;"><b>Initiating Department:</b>&nbsp;'.$row['department'].'</td>
                        <td style="width: 40%;"><b>Initiated By:</b>&nbsp;'.$row['initiate_by'].'</td>
                    </tr>
                    <tr>
                        <td style="width: 20%;"><b>Incident Number:</b></td>
                        <td style="width: 80%;">'.$row['incident_no'].'</td>
                    </tr>
                     <tr >
                        <td style="width: 100%;"><b>Description of Incident:</b>&nbsp;'.$row['description'].'</td>
                    </tr>
                     <tr >
                        <td style="width: 100%;"><b>Immediate Action:</b>&nbsp;'.$row['immediate_action'].'</td>
                    </tr>
                    <tr>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Initiator:</b><br>
                                                <b>Sign/Date:</b><br></td>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Initiating Dept.Head:</b><br>
                                                <b>Sign/Date:</b><br></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Classification Of Incident:</b>Quality Impacting/Non-Quality Impacting(Tick as applicable)</td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Investigation:</b><br>&nbsp;'.$row['investigation'].'</td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Impact Assessment:</b>&nbsp;'.$row['impact_assessment'].'</td>
                    </tr>
                    <tr>
                        <td style="width:100%;"><b>Corrective Actions:</b>&nbsp;'.$row['corrective_action'].'
                        <div></div>
                                                 <span style="text-align:right;"><b>Sign/Date:</b></span></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Preventive Actions:</b><br>&nbsp;'.$row['preventive_action'].'
                                                 <div></div>
                                                 <span style="text-align:right;"><b>Sign/Date:</b></span></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;">&nbsp;&nbsp;<b>Notification to respective Customers(in Case of Quality Impacting Incident): YES/NO</b><br>
                                                <b>Attach Customer Approval as Annexure:</b>_____________________________</td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Initiating Department Head Comments:</b><br>
                                                 <div></div>
                                                 <span style="text-align:right;"><b>Sign/Date:</b></span></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>QA Comments:</b>&nbsp;'.$row['qa_decision'].'
                                                 <div></div>
                                                 <span style="text-align:right;"><b>Sign/Date:</b></span></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Incident Extension:</b><br></td>
                    </tr>
                    <tr>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Extension Required In:</b><br></td>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Justification In Extension:</b><br></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Revised Target Completion Date:</b><br></td>
                    </tr>
                    <tr>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Initiating Dept.Head:</b><br>
                                                <b>Sign/Date:</b><br></td>
                        <td style="width: 50%;">&nbsp;&nbsp;<b>Head QA/Designe</b><br>
                                                <b>Sign/Date:</b><br></td>
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>Evalution by Corporate Quality Assurance:</b>
                                                 <div></div>
                                                 <span style=" width:100%; text-align:right;"><b>Sign/Date:</b></span><br></td>
                    </tr>
                     <tr>
                        <td style="width: 100%;"><b>Evalution by Head Quality/Designee:</b>
                                                 <div></div>
                                                 <span style="width:100%; text-align:right;"><b>Sign/Date:</b></span></td>
                    </tr>
                    <tr>
                        <td style="width:100%;">&nbsp;&nbsp;<b>Incident Closed By</b>&nbsp;'.$row['close_by'].'<br>
                                                <b>Sign/Date:</b>______________________&nbsp;'.$row['close_date'].' <br></td>                  
                    </tr>
                    <tr>
                        <td style="width: 100%;"><b>List of Attachments</b><br>
                                                 <table border="1" cellpadding="5">
                                                 <tr>
                                                    <td style="width:20%;"><b>Attachment No</b></td>
                                                    <td style="width:80%;"><b>Particular</b></td>
                                                 </tr>
                                                 <tr>
                                                    <td style="width:20%;"></td>
                                                    <td style="width:80%;"></td>
                                                 </tr>
                                                 <tr>
                                                    <td style="width:20%;"></td>
                                                    <td style="width:80%;"></td>
                                                 </tr>
                                                
                                                 </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%"><b>Attach Additional Sheets if required</b></td>
                    </tr>
            ';
            }
        }
		
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('IncidentReport.pdf', 'I');
    }
       

}

$conn->close();
?>