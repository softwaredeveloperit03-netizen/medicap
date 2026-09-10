<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

    require 'db.php';
    require 'token.php';
    require 'tcpdf/tcpdf.php';
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
    if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    } 

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    // $conn->query($sql);

    if($_GET["type"]=="generatesop") {
    
        $sql = "SELECT department, form_name FROM sop_gen WHERE department = '".$_POST['department']."' AND form_name = '".$_POST['form_name']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "{\"status\":\"generated\"}"; 
            }
        } else {
            
            $words = explode(" ", $_POST['department']);
            $acronym = "";
            foreach ($words as $w) {
              $dp .= $w[0];
            }
            
            $sql = "SELECT IFNULL(MAX(i_no), 0) as i_no FROM sop_gen";
            $result = $conn->query($sql);
            $sop_no = "";
            $i_no = 0;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $i_no = $row["i_no"];
                }
            }
            
            $i_no++;
            $num = strlen($i_no);
            if ($num == 1) {
                $sop_no = 'SOP/'.$dp.'/00'.$i_no.'/00';
                $file_no = '00'.$i_no.'00';
            } else if ($num == 2) {
                $sop_no = 'SOP/'.$dp.'/0'.$i_no.'/00';
                $file_no = '0'.$i_no.'00';
            } else if ($num == 3) {
                $sop_no = 'SOP/'.$dp.'/'.$i_no.'/00';
                $file_no = $i_no.'00';
            }
            
            class MYPDF extends TCPDF {
                public function Header() {
                }
                public function Footer() {
                    $this->SetY(-18);
                    $this->SetFont('Times', '', 10);
                    $this->Cell(0, 10, 'Format No. : '.$sop_no.'-02-F1', 0, false, 'L', 0, '', 0, false, 'T', 'M');
                    $this->Cell(0, 10, 'Page No. : '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                }
            } 
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(15, 15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }
            $pdf->SetFont('times', '', 12);
            $pdf->AddPage();
            
            $html='
            <style>
            th { border:solid 1px BCBBBA; }
            td { border:solid 1px BCBBBA; }
            .td1 { width:5%;  border:none; }
            .td2 { width:95%;  border:none; }
            </style>
            <table border="0" cellpadding="5" style="text-align:left; vertical-align:middle;">
                <thead class="tablehead">
                    <tr>
                        <th>Master Copy Stamp</th>
                        <th><img src="../assets/logo.png" style="height:50px;"></th>
                        <th>Controlled Copy Stamp</th>
                    </tr>
                    <tr><br></tr>
                    <tr>
                        <th style="background-color:#DDDAD9; width:100%; text-align:center;">STANDARD OPERATING PROCEDURE</th>
                    </tr>
                    <tr><br></tr>
                    <tr>
                        <th style="width:16.66%;"><b>Department</b></th>
                        <th style="width:33.34%;">'.$_POST["department"].'</th>
                        <th style="width:16.66%;"><b>Copy No</b></th>
                        <th style="width:33.34%;">'.$_POST["copy_no"].'</th>
                    </tr>
                    <tr>
                        <th style="width:100%;"><b>Title - '.$_POST["title"].'</b></th>
                    </tr>
                    <tr>
                        <td style="width:16.66%;"><b>SOP No.</b></td>
                        <td style="width:16.66%;">'.$sop_no.'</td>
                        <td style="width:16.66%;"><b>Revision No.</b></td>
                        <td style="width:16.66%;">'.$_POST["revision_no"].'</td>
                        <td style="width:16.66%;"><b>Effective Date</b></td>
                        <td style="width:16.66%;">'.$_POST["effective_date"].'</td>
                    </tr>
                    <tr>
                        <td><b>Supersede No.</b></td>
                        <td>'.$_POST["supersed_no"].'</td>
                        <td><b>Version No.</b></td>
                        <td>00</td>
                        <td><b>Next Review Date</b></td>
                        <td>'.$_POST["next_review_date"].'</td>
                    </tr>
                </thead>
                <tbody>
                    <tr><br></tr>
                    <tr>
                        <td style="border:none; text-align:center;"><b>Annexure I : SOP format</b></td>
                    </tr>
                    <tr><br></tr>';
                    $details = json_decode($_POST["details"], true);
                    for ($i = 0; $i < sizeof($details); $i++) {
                        $temp = $details[$i];
                        $count = $i + 1;
                        $html.='
                            <tr>
                                <td class="td1">'.$count.'</td>
                                <td class="td2"><b>'.$temp["title"].'</b></td>
                            </tr>
                            <tr>
                                <td class="td1"></td>
                                <td class="td2"><b>'.$temp["detail"].'</b></td>
                            </tr>
                            <tr><br></tr>';
                    }
                    $html.='
                </tbody>
            </table>
            ';
            $pdf->writeHTML($html, true, false, true, false, '');
            $file = $file_no.'.pdf';
            $pdf->Output(dirname(__FILE__).'/sops/'.$file, 'F');
            
            $sql = "Insert INTO sop_gen(department,form_name,copy_no,title,sop_no,file,revision_no,effective_date,supersede_no,version_no,next_review_date,i_no,entry_by,entry_date) VALUE
            ('".$_POST['department']."','".$_POST['form_name']."','".$_POST['copy_no']."','".$_POST['title']."','$sop_no','$file','".$_POST['revision_no']."','".$_POST['effective_date']."','".$_POST['supersede_no']."','00','".$_POST['next_review_date']."','$i_no','".$_GET['emp_id']."','".$entry_date."')";
            if($conn->query($sql)===TRUE) {
                $flag = 0;
                $details = json_decode($_POST["details"], true);
                $length = sizeof($details);
            	
            	for($i = 0; $i < $length; $i++) {
            	    $data = $details[$i];
            	    $sql = "INSERT INTO sop_details(sop_no, file, title, detail) VALUES 
            	    ('$sop_no', '$file','".$data["title"]."','".$data["detail"]."')";
            	    if ($conn->query($sql) === TRUE) {
            	        $flag = 0;
            	    } else {
            	        $flag = 1;
            	    }
            	}
            	
            	if ($flag == 0) {
                    echo "{\"status\":\"success\"}";
            	} else {
            	    echo "{\"status\":\"".$conn->error."\"}";
            	}
            	
            }
            else {
                echo "{\"status\":\"An error has occurred, Please try again.\"}";
            }
        }} 
    else if($_GET["type"] == "getCreatedSOPlist") {
    	$sql = "SELECT * FROM sop_gen WHERE status != 'oldversion' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	$data = array();
    	
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
    		    $data[] = $row;
    		}
    	}
    	echo json_encode($data);} 
    
    else if ($_GET["type"] == "getSOPIndex") {
        $sql = "SELECT * FROM sop_gen ORDER BY id DESC";
        $result = $conn->query($sql);
        $data = array();
        if ($result->num_rows > 0) {
            while ($row = $result -> fetch_assoc()) {
                $row["sop_type"] = "Created";
                $row["sop_name"] = $row["title"];
                $data[] = $row;
            }
        }
        
        $sql = "SELECT * FROM z_forms ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result -> fetch_assoc()) {
                $row["sop_type"] = "Uploaded";
                $data[] = $row;
            }
        }
        echo json_encode($data);
        } 
    else if ($_GET["type"] == "saveSOPRevisionRequest") {
        $sql = "INSERT INTO sop_revision(department, sop_title, sop_no, due_date, revision_reason, cc_no, change_required, post_review_comment, depthead_comment, qahead_comment, change_made, reviewed_no_change, next_review_date)
        VALUES ('".$_POST["department"]."', '".$_POST["sop_title"]."', '".$_POST["sop_no"]."', '".$_POST["due_date"]."', '".$_POST["revision_reason"]."', '".$_POST["cc_no"]."', '".$_POST["change_required"]."', '".$_POST["post_review_comment"]."','".$_POST["depthead_comment"]."','".$_POST["qahead_comment"]."','".$_POST["change_made"]."','".$_POST["reviewed_no_change"]."','".$_POST["next_review_date"]."')";
        
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        } 
        
         }else if ($_GET["type"] == "saveuploadsops") {
        $sql = "INSERT INTO z_forms( sop_title, sop_for, version_no)
        VALUES ('".$_POST["sop_title"]."', '".$_POST["sop_for"]."', '".$_POST["version_no"]."')";
        
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        } 
    }else if ($_GET["type"] == "getUploadedSops") {
        $sql = "SELECT * FROM z_forms ORDER BY id DESC";
        $result = $conn->query($sql);
        $data = array();
        
        if ($result-> num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        }
        else if ($_GET["type"] == "uploadSop") {
    $target_dir = "upload/";
    $sop_file = "";
    $flowchart = "";
    
    if(isset($_FILES["sop"]["name"])) {
        $target_file = $target_dir . "sop-" . $_POST["form_name"] . ".pdf";
        $sop_file = "sop-" . $_POST["form_name"] . ".pdf";
        move_uploaded_file($_FILES["sop"]["tmp_name"], $target_file);
    }
    
    if(isset($_FILES["flowchart"]["name"])) {
        $ext = pathinfo(basename($_FILES["flowchart"]["name"]), PATHINFO_EXTENSION);
        $target_file = $target_dir . "flowchart-" . $_POST["form_name"] . "." . $ext;
        $flowchart = "flowchart-" . $_POST["form_name"] . "." . $ext;
        move_uploaded_file($_FILES["flowchart"]["tmp_name"], $target_file);
    }
    
    $sql = "UPDATE z_forms SET sop_name='".$_POST["sop_name"]."', sop_no='".$_POST["sop_no"]."', format_no='".$_POST["format_no"]."', flowchart='$flowchart', sop_file='$sop_file', status='active' WHERE id='".$_POST["form_name"]."'";
    
    if ($conn->query($sql) === TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"" . $conn->error . "\"}";
    }
}

    // else if ($_GET["type"] == "uploadSop") {
        
    //     $target_dir = "upload/";
    //     $sop_file = "";
    //     $flowchart = "";
        
    //     if(isset($_FILES["sop"]["name"])) {
    //     	$target_file = $target_dir."sop-".$_POST["form_name"].".pdf";
    //     	$sop_file = "sop-".$_POST["form_name"].".pdf";
    //     	move_uploaded_file($_FILES["sop"]["tmp_name"], $target_file);
    // 	}
    	
    // 	if(isset($_FILES["flowchart"]["name"])) {
    // 	    $ext = pathinfo(basename($_FILES["flowchart"]["name"]), PATHINFO_EXTENSION);
    //     	$target_file = $target_dir."flowchart-".$_POST["form_name"].".".$ext;
    //     	$flowchart = "flowchart-".$_POST["form_name"].".".$ext;
    //     	move_uploaded_file($_FILES["flowchart"]["tmp_name"], $target_file);
    // 	}
    	
    //      $sql = "UPDATE z_forms SET sop_name='".$_POST["sop_name"]."', sop_no='".$_POST["sop_no"]."', format_no='".$_POST["format_no"]."', flowchart='$flowchart', sop_file='$sop_file', status='active' WHERE id='".$_POST["form_name"];
    //     // $sql = "UPDATE z_forms SET sop_name='" . $_POST["sop_name"] . "', sop_no='" . $_POST["sop_no"] . "', format_no='" . $_POST["format_no"] . "', flowchart='$flowchart', sop_file='$sop_file', status='active' WHERE id='" . $_POST["form_name"] . "'";
    //     if ($conn->query($sql) ==TRUE) {
    //         echo "{\"status\":\"success\"}";
    //     } 
    //     else {
    //         echo "{\"status\":.$conn->error}";
    //     }
        
    // } 
    else if ($_GET["type"] === "getSOPRevisions") {
        $sql = "SELECT sr.*, sg.form_name FROM sop_revision sr JOIN sop_gen sg ON sg.sop_no = sr.sop_no";
        $result = $conn->query($sql);
        $data = array();
        
        if ($result-> num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        } 
    else if ($_GET["type"] == "getSelectedSOPData") {
        
        $sql = "SELECT * FROM sop_revision sr JOIN sop_gen sg ON sg.sop_no = sr.sop_no WHERE sr.sop_no = '".$_GET["sop_no"]."'";
        $result = $conn->query($sql);
        $output = array();
        
        if ($result-> num_rows > 0) {
            while ($row = $result-> fetch_assoc()) {
                $sql2 = "SELECT * FROM sop_details WHERE sop_no = '".$_GET["sop_no"]."'";
                $result2 = $conn->query($sql2);
                $data = array();
                
                if ($result2-> num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $data[] = $row2;
                    }
                }
                
                $row["details"] = $data;
                $output[] = $row;
            }
        }
        echo json_encode($output);} 
    else if ($_GET["type"] == "getTrainingCompleted") {
        $sql = "SELECT * FROM sop_training WHERE status='active' ORDER BY id DESC";
        $result = $conn->query($sql);
        $data = array();
        if ($result-> num_rows > 0) {
            while ($row = $result -> fetch_assoc()) {
                
                $sql2 = "SELECT * FROM sop_gen WHERE sop_no = '".$row["sop_no"]."' LIMIT 1";
                $result2 = $conn->query($sql2);
                if ($result2 -> num_rows > 0) {
                    while($row2 = $result2->fetch_assoc()) {
                        $row["title"] = $row2["title"]; 
                        $row["copy_no"] = $row2["copy_no"]; 
                        $row["form_name"] = $row2["form_name"]; 
                    }
                }
                
                $data[] = $row;
            }
        }
        echo json_encode($data);
        
    } 
    else if ($_GET["type"] == "approveSOPRevision"){
        
        $sql = "UPDATE sop_revision SET status='active' WHERE id=".$_GET["id"];
        if ($conn->query($sql) === TRUE) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
    } 
    else if ($_GET["type"] == "saveprocess_overview"){
        
                    $did  =  $_GET["ID"];

    
            if(isset($_FILES["doc"])) {
            $file_tmp =$_FILES['doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['doc']['name'])));
            $file_name = $did."process.".$file_ext;
            $doc = $file_name;
            move_uploaded_file($file_tmp,"../../upload/Sops/".$file_name);
        }
        
        
        
        
        
        
        

          $sql = "UPDATE sopinitiation SET process = '$doc'  WHERE id ='$did'";
          
       	if($conn->query($sql)){    
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
        
    } 
    else if ($_GET["type"] == "saveattachments"){
        
                    $did  =  $_GET["ID"];

    
            if(isset($_FILES["doc"])) {
            $file_tmp =$_FILES['doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['doc']['name'])));
            $file_name = $did."attachments.".$file_ext;
            $doc = $file_name;
            move_uploaded_file($file_tmp,"../../upload/Sops/".$file_name);
        }

           $sql = "UPDATE sopinitiation SET attachments ='$doc'  WHERE id ='$did'";
          
       	if($conn->query($sql)){    
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
        
    } 
    else if ($_GET["type"] == "getChangeControl") {
        $sql = "SELECT cc.* FROM sop_revision sr 
        JOIN changecontrol cc ON sr.cc_no = cc.ctrl_no
        WHERE sop_no='".$_GET["sop_no"]."' LIMIT 1";
        $result = $conn->query($sql);
        $data = array();
        
        if ($result -> num_rows > 0) {
            while ($row = $result -> fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);} 
    else if ($_GET["type"] == "saveChangeControl") {
        
        $sql = "SELECT * FROM sop_revision WHERE sop_no='".$_POST["sop_no"]."' LIMIT 1";
        $result = $conn->query($sql);
        $cc_no;
        
        if ($result->num_rows > 0) {
            while($row = $result -> fetch_assoc()) {
                $cc_no = $row["cc_no"];
            }
        }
        
        $sql = "SELECT * FROM changecontrol WHERE ctrl_no = '".$cc_no."' LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc() ) {
                $sql = "UPDATE";
                if ($conn->query($sql) === TRUE) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"failed\"}";
                }
            }
        } else {
            $sql = "SELECT IFNULL(MAX(id), 0) as id FROM changecontrol";
            $id = 0;
            if ($result->num_rows > 0) {
                while($row = $result -> fetch_assoc()) {
                    $id = $row["id"];
                }
            }
            $id++;
            $cc_no="CC".$id;
            $sql = "INSERT INTO changecontrol(ctrl_no, department, change_related, change_title, existing_procedure, proposed_change, 
                    reason_for_changes, product_name, market_detail, impact_quality, description, c_no1, entry_by, entry_date) 
                    VALUES ('".$cc_no."', '".$_GET["department"]."', '".$_POST["change_related"]."', '".$_POST["change_title"]."',
                    '".$_POST["existing_procedure"]."', '".$_POST["proposed_change"]."', '".$_POST["reason_for_changes"]."',
                    '".$_POST["product_name"]."', '".$_POST["market_detail"]."', '".$_POST["impact_quality"]."', '".$_POST["description"]."', 
                    $id, '".$_GET["emp_id"]."', '".$entry_date."')";
            if ($conn->query($sql) === TRUE) {
                $sql = "UPDATE sop_revision SET cc_no ='".$cc_no."' WHERE sop_no='".$_POST["sop_no"]."' LIMIT 1";
                if ($conn->query($sql) === TRUE) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"failed\"}";
                }
            }
        }
    }
    
    ///// NEW SOP /////
    
    else if($_GET['type'] == 'getDepartments') {
        $output = array();
        $sql = "SELECT * FROM department WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDesignations") {
        $output = array();
        $sql = "SELECT * FROM designation WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'saveinitiation'){
        $sql = "INSERT INTO sopinitiation (plant_id,entry_date,department,title,sop_for,equipment_code,other,entry_by)
        VALUES ('".$_GET['plant_id']."','$entry_date','".$input['department']."','".$input['title']."','".$input['sop_for']."',
        '".$input['enquipment_code']."', '".$input["other"]."','".$_GET["emp_id"]."')";
        // '".json_encode($input)."')";
         if($conn->query($sql)) {
              $last_id = $conn->insert_id;
            echo "{\"status\":\"success\",\"initiation_no\":\"$last_id\"}";
         }else{
             echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
         }
    }
    else if($_GET['type'] == 'getpendinginitiation'){
        $output = array();
         $sql = "SELECT * FROM sopinitiation WHERE status='draft' or status='pending' and department='".$_GET["department1"]."'  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1="SELECT * FROM changecontrol WHERE ctrl_no='".$row['ctrl_no']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                $row['ctrl_status'] = $row1['status'];
                
                $row['role'] = $row['role'];
                $row['training'] = $row['training'];
                $row['distribution'] = $row['distribution'];
                $row['revision'] = $row['revision'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getcheckedinitiation'){
        $output = array();
         $sql = "SELECT * FROM sopinitiation WHERE status='checked'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1="SELECT * FROM changecontrol WHERE ctrl_no='".$row['ctrl_no']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                $row['ctrl_status'] = $row1['status'];
                
                $row['role'] = json_decode($row['role']);
                $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'checkinitiation'){
         ini_set('display_errors', 1);
error_reporting(E_ALL);

        $sql = "UPDATE sopinitiation SET check_date='$entry_date',check_by='".$_GET["emp_id"]."',check_remark='".$_GET['remark']."',status='".$_GET['status']."' WHERE id='".$_GET['id']."'";
        if($conn->query($sql)===TRUE) {
            
          
            
            $inputDepartments = json_encode($input['selectedDepartments']);
$departmentArray = json_decode($inputDepartments, true);

$entry_date = date("Y-m-d H:i:s");
$entry_by = $_GET['emp_id'];
$status = "pending";

foreach ($departmentArray as $department) {
    // Trim whitespace and insert each department into the sop_review table
    $sql1 = "INSERT INTO sop_review (sop_no, departments, entry_date, entry_by, status) 
            VALUES ('".$_GET['sop_no']."', '".trim($department)."', '$entry_date', '$entry_by', '$status')";
    $conn->query($sql1);
}

            
            
      
            
            
            
            // $conn->query($sql1);
    		echo "{\"status\":\"success\"}";
    	}else{
            echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
        }
    }
    else if($_GET['type'] == 'approveinitiation'){
        $sql = "UPDATE sopinitiation SET approve_date = '$entry_date',approve_by='".$_GET["emp_id"]."',status='".$_GET['status']."' WHERE id='".$_GET['id']."'";
        if($conn->query($sql)===TRUE) {
            $sql1 = "UPDATE changecontrol SET status='inprocess',verify_by='".$_GET['emp_id']."',verify_date='$entry_date',verify_remark='".$_GET['remark']."' WHERE ctrl_no='".$_GET['ctrl_no']."'";
            $conn->query($sql1);
    		echo "{\"status\":\"success\"}";
    	}else{
            echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
        }
    }
    else if($_GET['type'] == 'getinitiationlog') {
        $output = array();
            //  $sql = "SELECT * FROM sopinitiation WHERE department='".$_GET["department"]."'";
             $sql = "SELECT * FROM sopinitiation WHERE status='checked' and plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1="SELECT * FROM changecontrol WHERE ctrl_no='".$row['ctrl_no']."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                $row['ctrl_status'] = $row1['status'];
                
                $row['role'] = json_decode($row['role']);
                $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getinitiationlog') {
        $output = array();
             $sql = "SELECT * FROM sopinitiation WHERE department='".$_GET["department"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
                $row['view_format'] = json_decode($row['view_format']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getpendingdraft') {
        $output = array();
         $sql = "SELECT * FROM sopinitiation WHERE status='pending' and department='".$_GET["department1"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['training'] = $row['training'];
                $row['distribution'] = $row['distribution'];
                $row['revision'] = $row['revision'];
                $row['view_format'] = $row['view_format'];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getpendingdraftrecord'){
        $output = array();
        $sql = "SELECT * FROM sopinitiation WHERE status='pending' and department='".$_GET["department1"]."' AND id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['role'] = json_decode($row['role']);
                $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
                $output = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'savedraft'){
        
        $sql4 = "UPDATE sopinitiation SET  status='draft' WHERE id='".$_GET['id']."'";
        
        if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'savePurpose'){
          ini_set('display_errors', 1);
error_reporting(E_ALL);
        $purpose=$conn->real_escape_string($input['purpose']);
        
         $sql4 = "UPDATE sopinitiation SET purpose='$purpose' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saveprocedure'){
          ini_set('display_errors', 1);
error_reporting(E_ALL);
        $procedure=$conn->real_escape_string($input['procedures']);
        
          $sql4 = "UPDATE sopinitiation SET procedures='$procedure' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saveDefination'){
          ini_set('display_errors', 1);
error_reporting(E_ALL);
        $definition=$conn->real_escape_string($input['definition']);
        
         $sql4 = "UPDATE sopinitiation SET definition ='$definition' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saveabbreviationForm'){
          ini_set('display_errors', 1);
error_reporting(E_ALL);
        $abbreviation=$conn->real_escape_string($input['abbreviation']);
        
         $sql4 = "UPDATE sopinitiation SET abbreviation ='$abbreviation' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saverevisionList'){
         ini_set('display_errors', 1);
error_reporting(E_ALL);
        $revision=$conn->real_escape_string($input['revision']);
        
         $sql4 = "UPDATE sopinitiation SET revision ='$revision' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'savetrainingList'){
         ini_set('display_errors', 1);
error_reporting(E_ALL);
        $training=$conn->real_escape_string($input['training']);
        
        
          $sql4 = "UPDATE sopinitiation SET training ='$training' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'savedistributionList'){
         ini_set('display_errors', 1);
error_reporting(E_ALL);
        $distribution=$conn->real_escape_string($input['distribution']);
        
          $sql4 = "UPDATE sopinitiation SET distribution ='$distribution' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saveAssDocument'){
         ini_set('display_errors', 1);
error_reporting(E_ALL);
        $reference=$conn->real_escape_string($input['reference']);
        
         $sql4 = "UPDATE sopinitiation SET reference ='$reference' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saverolesform'){
          ini_set('display_errors', 1);
error_reporting(E_ALL);
        $roles=$conn->real_escape_string($input['role']);
        
         $sql4 = "UPDATE sopinitiation SET role ='$roles' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saveScope'){
        ini_set('display_errors', 1);
error_reporting(E_ALL);
        $scope=$conn->real_escape_string($input['scope']);
         $sql4 = "UPDATE sopinitiation SET scope='$scope' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saveadds1'){
        ini_set('display_errors', 1);
error_reporting(E_ALL);
        $adds1=$conn->real_escape_string($input['adds1']);
         $sql4 = "UPDATE sopinitiation SET adds1='$adds1' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saveadds2'){
        ini_set('display_errors', 1);
error_reporting(E_ALL);
        $adds2=$conn->real_escape_string($input['adds2']);
         $sql4 = "UPDATE sopinitiation SET adds2='$adds2' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saveadds3'){
        ini_set('display_errors', 1);
error_reporting(E_ALL);
        $adds3=$conn->real_escape_string($input['adds3']);
         $sql4 = "UPDATE sopinitiation SET adds3='$adds3' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saveadds4'){
        ini_set('display_errors', 1);
error_reporting(E_ALL);
        $adds4=$conn->real_escape_string($input['adds4']);
         $sql4 = "UPDATE sopinitiation SET adds4='$adds4' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if($_GET['type'] == 'saveadds5'){
        ini_set('display_errors', 1);
error_reporting(E_ALL);
        $adds5=$conn->real_escape_string($input['adds5']);
         $sql4 = "UPDATE sopinitiation SET adds5='$adds5' where id =  '".$_GET["ID"]."'";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    else if ($_GET["type"] == "saveInitiatedChangeControl") {
        $c_id1 = 0;
         $sql = "SELECT IFNULL(max(c_no1),0) as c_no1 FROM changecontrol LIMIT 1";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $c_id1 = $row["c_no1"];
                break;
    		}
    	}
    	
    	$ctrl_no = "";
    	$c_id1++;
    	$ctrl_no = "CC-".$c_id1;
    	
        $export = "no";
        $domastic = "no";
    	if ($input["export"] == true) {
    	    $market_details = "yes";
    	}
    	if ($input["domastic"] == true) {
    	    $market_details = "yes";
        }
        $product = Array();
        if ($input["impact_quality"] == "Yes") {
            $product["product_name"] = $input["product_name"];
            $product["batch_no"] = $input["batch_no"];
        }
    	
          $sql1 = "INSERT INTO changecontrol (ctrl_no,department,change_related,change_title,existing_procedure,
        proposed_change,change_reason, export, domastic, impact_product, product_details, entry_by, entry_date, c_no1)
        VALUES ('".$ctrl_no."', '".json_encode($input['departments'])."','".$input["change_related"]."','".$input["change_title"]."',
        '".$input["existing_procedure"]."','".$input["proposed_change"]."','".$input["reason_for_changes"]."','".$export."',
        '$domastic', '".$input["impact_quality"]."', '".json_encode($product)."', '".$_GET["emp_id"]."', '".$entry_date."', ".$c_id1.")";
    	if($conn->query($sql1)===TRUE) {
    	    $data = $input["departments"];
            for ($i = 0; $i < count($data); $i++) {
                $sql2 = "INSERT INTO change_comments (ctrl_no,department) VALUES ('$ctrl_no','".$data[$i]."')";
                $conn->query($sql2);
            }
            
            $sql4 = "UPDATE sopinitiation SET ctrl_no='$ctrl_no',status='changecontrol' WHERE id='".$_GET['id']."'";
            $conn->query($sql4);
    
            // $sql3 = "INSERT INTO pendingdocument (entry_id, form, formname, purpose, department, entry_by, entry_date, checker, approver) VALUES ('$ctrl_no', 'changecontrol', 'Change Control', 'Checking', '".$_GET["department"]."', '".$_GET["emp_id"]."', '$entry_date', 'true', 'true')";
            // $conn->query($sql3);
            
            
            echo "{\"status\":\"success\"}";
        }else{
             echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
        }
    }
    else if ($_GET["type"] == "getPendingChangeControls") {
        $output = array();
        $sql = "SELECT * FROM sopinitiation WHERE status='draft'";
        // $sql = "SELECT * FROM sopinitiation WHERE status='draft' AND department='".$_GET["department"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['role'] = json_decode($row['role']);
                $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getPendingChangeControlsRecords") {
        $output = array();
        $sql = "SELECT * FROM sopinitiation WHERE status='draft' AND department='".$_GET["department"]."' AND id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['role'] = json_decode($row['role']);
                $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
                $output = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getPendingSOPPreparation') {
        $output = array();
        $sql = "SELECT * FROM sopinitiation WHERE status='approve' AND sopcreated='pending' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1="SELECT * FROM changecontrol WHERE ctrl_no='".$row['ctrl_no']."' AND status='close'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row['ctrl_status'] = $row1['status'];
                        $row['role'] = json_decode($row['role']);
                        $row['training'] = json_decode($row['training']);
                        $row['distribution'] = json_decode($row['distribution']);
                        $row['revision'] = json_decode($row['revision']);
                        $output[] = $row;
                    }
                }
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'prepareSOP') {
        $sql = "SELECT IFNULL(MAX(i_no), 0) as i_no FROM sop_generate";
        $result = $conn->query($sql);
        $sop_no = "";
        $i_no = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no = $row["i_no"];
            }
        }
        $i_no++;
        $num = strlen($i_no);
        $words = explode(" ", $input['department']);
        $acronym = "";
        foreach ($words as $w) {
          $dp .= $w[0];
        }
            
        if ($num == 1) {
            $sop_no = 'SOP/'.$dp.'/00'.$i_no.'/00';
        } else if ($num == 2) {
            $sop_no = 'SOP/'.$dp.'/0'.$i_no.'/00';
        } else if ($num == 3) {
            $sop_no = 'SOP/'.$dp.'/'.$i_no.'/00';
        }
            
        $sql ="INSERT INTO sop_generate(department,initiation_no,sop_no,i_no,version_no,title,sop_for,purpose,scope,role,definition,reference,process,procedures,
        abbreviation,training,distribution,revision,entry_by,entry_date)VALUE('".$input['department']."','".$input['id']."','$sop_no','$i_no','$version_no','".$input['title']."',
        '".$input['sop_for']."','".$input['purpose']."','".$input['scope']."','".$input['role']."','".$input['definition']."','".$input['reference']."',
        '".$input['process']."','".$input['procedures']."','".$input['abbreviation']."','".json_encode($input['training'])."','".json_encode($input['distribution'])."',
        '".json_encode($input['revision'])."','".$_GET['emp_id']."','$entry_date')";
        if($conn->query($sql) === true) {
            echo "{\"status\":\"success\"}";
            $sql1="UPDATE sopinitiation SET sopcreated='created' WHERE id='".$input['id']."'";
            $conn->query($sql1);
            
        } else{
            echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
        }
    }
    else if($_GET["type"] == "getPendingSOPs") {
    	$sql = "SELECT * FROM sopinitiation     ORDER BY id DESC";
    	$result = $conn->query($sql);
    	$data = array();
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
    		    $row['role'] = json_decode($row['role']);
    		    $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
    		    $data[] = $row;
    		}
    	}
    	echo json_encode($data);
    }
    else if($_GET["type"] == "checkCreatedSOP") {
    	$sql = "UPDATE sop_generate SET status = '".$_GET['status']."',check_by='".$_GET['emp_id']."',check_date='$entry_date' WHERE sop_no='".$_GET["sop_no"]."' LIMIT 1";
    	if($conn->query($sql) === TRUE) {
    	    echo "{\"status\":\"success\"}";
    	} else {
    	    echo "{\"status\":\"success\"}";
    	}
    } 
    else if($_GET["type"] == "getCheckedSOPs") {
    	$sql = "SELECT * FROM sop_generate WHERE status = 'checked' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	$data = array();
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
    		    $row['role'] = json_decode($row['role']);
    		    $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
    		    $data[] = $row;
    		}
    	}
    	echo json_encode($data);
    } 
    else if($_GET["type"] == "approveCreatedSOP") {
    	$sql = "UPDATE sop_generate SET status = '".$_GET['status']."',approve_by='".$_GET['emp_id']."',approve_date='$entry_date' WHERE sop_no='".$_GET["sop_no"]."' LIMIT 1";
    	if($conn->query($sql) === TRUE) {
    	    echo "{\"status\":\"success\"}";
    	} else {
    	    echo "{\"status\":\"success\"}";
    	}
    } 
    else if($_GET['type'] == 'getsoplog'){
    	$sql = "SELECT * FROM sop_generate ORDER BY id DESC";
    	$result = $conn->query($sql);
    	$data = array();
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
    		    $row['role'] = json_decode($row['role']);
    		    $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
    		    $data[] = $row;
    		}
    	}
    	echo json_encode($data);
    }
    else if($_GET['type'] == 'getSOPDeptLog'){
    	$sql = "SELECT * FROM sop_generate WHERE department='".$_GET["department"]."' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	$data = array();
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
    		    $row['role'] = json_decode($row['role']);
    		    $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
    		    $data[] = $row;
    		}
    	}
    	echo json_encode($data);
    }
    else if($_GET['type'] == 'getPendingSOPTraining'){
        $sql = "SELECT * FROM sop_generate WHERE status = 'approve' AND training_status='pending' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	$data = array();
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
    		    $row['role'] = json_decode($row['role']);
    		    $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
                
                $data1 = Array();
                $trainings = $row["training"];
                for ($i = 0; $i < count($trainings); $i++) {
                    $training = $trainings[$i];
                    
                    $output1 = Array();
                    $sql1 = "SELECT * FROM employee WHERE department='".$training."' AND status='active'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $temp = Array();
                    $temp["department"] = $training;
                    $temp["employees"] = $output1;
                    $data1[] = $temp;
                }
                $row['training'] = $data1;
                
    		    $data[] = $row;
    		}
    	}
    	echo json_encode($data);
    }
    else if($_GET['type'] == 'getPendingSOPImplementation'){
        $sql = "SELECT * FROM sop_generate WHERE status = 'approve' AND training_status='done' AND implementation_status='pending' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	$data = array();
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
    		    $row['role'] = json_decode($row['role']);
    		    $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
    		    $data[] = $row;
    		}
    	}
    	echo json_encode($data);
    }
    else if($_GET['type'] == 'getsopview'){
            	$data = array();

        $sql = "SELECT * FROM sop_views WHERE  plant_id  = '".$_GET['plant_id']."'";
    	$result = $conn->query($sql);
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
     
    		    $data[] = $row;
    		}
    		    	echo json_encode($data);

    	}else{
    	        	echo json_encode([]);

    	}
    }
    
      else if($_GET['type'] == 'savePurposeView1'){
        
          $sql4 = "INSERT INTO `sop_views`( `plant_id`, `purpose`, `scope`, `role`, `definition`, `reference`, `process`, `procedures`,
    `abbreviation`, `training`, `distribution`, `attachments`, `revision`,`adds1`,`adds2`,`adds3`,`adds4`,`adds5`) VALUES
    ('".$_GET['plant_id']."','".json_encode($input['purpose'])."','".json_encode($input['scope'])."','".json_encode($input['role'])."',
     '".json_encode($input['defination'])."','".json_encode($input['external1'])."','".json_encode($input['process'])."',
     '".json_encode($input['procedure'])."','".json_encode($input['abbrivation'])."','".json_encode($input['training'])."',
     '".json_encode($input['distribution'])."', '".json_encode($input['attachments'])."','".json_encode($input['rivision'])."'
     ,'".json_encode($input['adds1'])."','".json_encode($input['adds2'])."','".json_encode($input['adds3'])."'
     ,'".json_encode($input['adds4'])."','".json_encode($input['adds5'])."')";
    
    
    //       $sql4 = "INSERT INTO `sop_views`( `plant_id`, `purpose`, `scope`, `role`, `definition`, `reference`, `process`, `procedures`,
    // `abbreviation`, `training`, `distribution`, `attachments`, `revision`) VALUES
    // ('".$_GET['plant_id']."','".$input['purpose']."','".$input['scope']."','".$input['role']."','".$input['defination']."','".$input['external1']."',
    // '".$input['process']."','".$input['procedure']."','".$input['abbrivation']."','".$input['training']."','".$input['distribution']."',
    // '".$input['attachments']."',
    // '".$input['rivision']."')";
         if ($conn->query($sql4)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        } 
    }
    
    
    else if($_GET['type'] == 'getPendingSOPDistribution'){
        $sql = "SELECT * FROM sop_generate WHERE status = 'approve' AND implementation_status='done' AND distribution_status='pending' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	$data = array();
    	if($result-> num_rows > 0) {
    		while($row = $result-> fetch_assoc()) {
    		    $row['role'] = json_decode($row['role']);
    		    $row['training'] = json_decode($row['training']);
                $row['distribution'] = json_decode($row['distribution']);
                $row['revision'] = json_decode($row['revision']);
                
                $data1 = Array();
                $trainings = $row["distribution"];
                for ($i = 0; $i < count($trainings); $i++) {
                    $training = $trainings[$i];
                    
                    $temp = Array();
                    $temp["department"] = $training;
                    $temp["copy"] = 0;
                    $temp["status"] = "pending";
                    $temp["entry_by"] = $_GET["emp_id"];
                    $temp["entry_date"] = $entry_date;
                    $temp["received_by"] = "";
                    $temp["received_date"] = "";
                    $data1[] = $temp;
                }
                $row['distribution'] = $data1;
                
    		    $data[] = $row;
    		}
    	}
    	echo json_encode($data);
    } else if ($_GET["type"] == "allocateTraining") {
        $training_no = 1;
        $sql = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'training_needs'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $training_no = $row["AUTO_INCREMENT"];
            }
        }
        $sql = "INSERT INTO training_needs (department, subject, other_subject, reference_document, proposed_trainer, training_need, proposed_date, entry_by, entry_date) VALUES ('".$input["department"]."','SOP Training','', '','".$input["trainer"]."','".$input["training_needs"]."','".$input["training_date"]."', '".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql) === TRUE) {
            $trainings = $input["training"];
            for ($i = 0; $i < count($trainings); $i++) {
                $department = $trainings[$i];
                $employees = $department["employees"];
                
                for ($j = 0; $j < count($employees); $j++) {
                    $employee = $employees[$j];
                    $sql1 = "INSERT INTO tn_employees (tn_no, emp_id) VALUES ($training_no, '".$employee['emp_id']."')";
                    $conn->query($sql1);
                }
            }
            $sql = "UPDATE sop_generate SET training_status='inprocess', training_no='$training_no' WHERE id='".$input["id"]."'";
            $conn->query($sql);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "implementSOP") {
        $sql = "UPDATE sop_generate SET implementation_status='done', effective_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "saveDistribution") {
        $sql = "UPDATE sop_generate SET distribution_status='done' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            $distributions = $input["distribution"];
            for ($i = 0 ; $i < count($distributions); $i++) {
                $department = $distributions[$i];
                $sql1 = "INSERT INTO sop_distribution (sop_no, department, copy, entry_by, entry_date) VALUES ('".$input["sop_no"]."', '".$department["department"]."', '".$department["copy"]."', '".$_GET["emp_id"]."', '$entry_date')";
                $conn->query($sql1);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingSOPDeptDistribution") {
        $output = Array();
        $sql = "SELECT * FROM sop_distribution WHERE status='pending' AND department='".$_GET["department"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM sop_generate WHERE sop_no='".$row["sop_no"]."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                $row["title"] = $row1["title"];
                $row["department"] = $row1["department"];
                $row["sop_for"] = $row1["sop_for"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "receiveDistributionSOP") {
        $sql = "UPDATE sop_distribution SET status='".$_GET["status"]."', received_by='".$_GET["emp_id"]."', received_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getSOPDeptDistributionLog") {
        $output = Array();
        $sql = "SELECT * FROM sop_distribution WHERE status='receive' AND department='".$_GET["department"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM sop_generate WHERE sop_no='".$row["sop_no"]."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                $row["title"] = $row1["title"];
                $row["department"] = $row1["department"];
                $row["sop_for"] = $row1["sop_for"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getApprovedDeptSOPs") {
        $output = Array();
        $sql = "SELECT * FROM sop_generate WHERE status='approve' ";
        //AND department='".$_GET["department"]."' AND sop_no NOT IN (SELECT sop_no from sop_revision WHERE status IN ('pending', 'inprocess'))";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['role'] = json_decode($row['role']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "reviseRequest") {
        $sql = "INSERT INTO sop_revision (sop_no, description, request_by, request_date) VALUES ('".$input["sop_no"]."', '".$input["description"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getRevisionRequests") {
        $output = Array();
        $sql = "SELECT * FROM sop_revision";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM sop_generate WHERE sop_no='".$row["sop_no"]."' AND department='".$_GET["department"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["title"] = $row1["title"];
                        $row["sop_for"] = $row1["sop_for"];
                        $row["effective_date"] = $row1["effective_date"];
                    }
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingRevisionRequests") {
        $output = Array();
        $sql = "SELECT * FROM sop_revision WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               $sql1 = "SELECT * FROM sop_generate WHERE sop_no='".$row["sop_no"]."'"; 
                //AND department='".$_GET["department"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["title"] = $row1["title"];
                        $row["sop_for"] = $row1["sop_for"];
                        $row["effective_date"] = $row1["effective_date"];
                    }
                    $output[] = $row;
                }
            } 
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "checkSOPRevisionRequest") {
        $sql = "UPDATE sop_revision SET status='".$input["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date', check_remark='".$input["comment"]."' WHERE sop_no='".$input["sop_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getInprocessRevisionRequests") {
        $output = Array();
        $sql = "SELECT * FROM sop_revision WHERE status='inprocess' AND change_status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM sop_generate WHERE sop_no='".$row["sop_no"]."' AND department='".$_GET["department"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["title"] = $row1["title"];
                        $row["sop_for"] = $row1["sop_for"];
                        $row["effective_date"] = $row1["effective_date"];
                    }
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRevisionActiveRequests") {
        $output = Array();
        $sql = "SELECT * FROM sop_revision WHERE status='inprocess' AND change_status='close'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM sop_generate WHERE sop_no='".$row["sop_no"]."' AND department='".$_GET["department"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["title"] = $row1["title"];
                        $row["sop_for"] = $row1["sop_for"];
                        $row["effective_date"] = $row1["effective_date"];
                        $row["purpose"] = $row1["purpose"];
                        $row["scope"] = $row1["scope"];
                        $row["definition"] = $row1["definition"];
                        $row["reference"] = $row1["reference"];
                        $row["process"] = $row1["process"];
                        $row["procedures"] = $row1["procedures"];
                        $row["abbreviation"] = $row1["abbreviation"];
                        $row['role'] = json_decode($row1['role']);
                        $row["training"] = json_decode($row1["training"]);
                        $row["distribution"] = json_decode($row1["distribution"]);
                        $row["revision"] = json_decode($row1["revision"]);
                    }
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "revisionChangeControl") {
        $c_id1 = 0;
        $sql = "SELECT IFNULL(max(c_no1),0) as c_no1 FROM changecontrol LIMIT 1";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()) {
    		    $c_id1 = $row["c_no1"];
                break;
    		}
    	}
    	
    	$ctrl_no = "";
    	$c_id1++;
    	$ctrl_no = "CC-".$c_id1;
    	
        $export = "no";
        $domastic = "no";
    	if ($input["export"] == true) {
    	    $market_details = "yes";
    	}
    	if ($input["domastic"] == true) {
    	    $market_details = "yes";
        }
        
        $product = Array();
        if ($input["impact_quality"] == "Yes") {
            $product["product_name"] = $input["product_name"];
            $product["batch_no"] = $input["batch_no"];
        }
    	
        $sql = "INSERT INTO changecontrol (ctrl_no,department,change_related,change_title,existing_procedure,proposed_change,change_reason, export, domastic, impact_product, product_details, entry_by, entry_date, c_no1) VALUES ('".$ctrl_no."','".$_GET["department"]."','".$input["change_related"]."','".$input["change_title"]."','".$input["existing_procedure"]."','".$input["proposed_change"]."','".$input["reason_for_changes"]."','".$export."','$domastic', '".$input["impact_quality"]."', '".json_encode($product)."', '".$_GET["emp_id"]."', '".$entry_date."', ".$c_id1.")";
    	if($conn->query($sql)===TRUE) {
    	    $data = $input["departments"];
            for ($i = 0; $i < count($data); $i++) {
                $sql = "INSERT INTO change_comments (ctrl_no,department) VALUES ('$ctrl_no','".$data[$i]."')";
                $conn->query($sql);
            }
            
            $sql = "UPDATE sop_revision SET change_status='inprocess', ctrl_no='$ctrl_no' WHERE id='".$input["id"]."'";
            $conn->query($sql);
    
            $sql = "INSERT INTO pendingdocument (entry_id, form, formname, purpose, department, entry_by, entry_date, checker, approver) VALUES ('$ctrl_no', 'changecontrol', 'Change Control', 'Checking', '".$_GET["department"]."', '".$_GET["emp_id"]."', '$entry_date', 'true', 'true')";
            $conn->query($sql);
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getDesignations") {
        $output = Array();
        $sql = "SELECT * FROM designation WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDirectSOP") {
        $input = $_POST;
        
        $sql = "SELECT IFNULL(MAX(i_no), 0) as i_no FROM sop_generate";
        $result = $conn->query($sql);
        $sop_no = "";
        $i_no = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no = $row["i_no"];
            }
        }
        $i_no++;
        $num = strlen($i_no);
        $words = explode(" ", $input['department']);
        $acronym = "";
        foreach ($words as $w) {
          $dp .= $w[0];
        }
            
        if ($num == 1) {
            $sop_no = 'SOP/'.$dp.'/00'.$i_no.'/00';
        } else if ($num == 2) {
            $sop_no = 'SOP/'.$dp.'/0'.$i_no.'/00';
        } else if ($num == 3) {
            $sop_no = 'SOP/'.$dp.'/'.$i_no.'/00';
        }
        
        $reference = Array();
        $reference["external"] = $input["external"];
        $reference["associated"] = $input["associated"];
        
        $sql = "INSERT INTO sop_generate (department, equipment_code, sop_no, version_no, title, sop_for, purpose, scope, role, definition, reference, process, procedures, abbreviation, training, distribution, revision, entry_by, entry_date) VALUES ('".$input["department"]."', '".$input["equipment_code"]."', '$sop_no', '".$input["version_no"]."', '".$input["title"]."', '".$input["sop_for"]."', '".$input["purpose"]."', '".$input["scope"]."', '".json_encode($input["role"])."', '".json_encode($input["definition"])."', '".json_encode($reference)."',
        '".$input["process"]."', '".$input["procedures"]."', '".$input["abbreviation"]."', '".json_encode($input["training"])."', '".json_encode($input["distribution"])."', '".json_encode($input["revision"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "saveDirectSOPDraft") {
        $input = $_POST;
        
        $sql = "SELECT IFNULL(MAX(i_no), 0) as i_no FROM sop_generate_drafts";
        $result = $conn->query($sql);
        $sop_no = "";
        $i_no = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $i_no = $row["i_no"];
            }
        }
        $i_no++;
        $num = strlen($i_no);
        $words = explode(" ", $input['department']);
        $acronym = "";
        foreach ($words as $w) {
          $dp .= $w[0];
        }
            
        if ($num == 1) {
            $sop_no = 'SOP/'.$dp.'/00'.$i_no.'/00';
        } else if ($num == 2) {
            $sop_no = 'SOP/'.$dp.'/0'.$i_no.'/00';
        } else if ($num == 3) {
            $sop_no = 'SOP/'.$dp.'/'.$i_no.'/00';
        }
        
        $reference = Array();
        $reference["external"] = $input["external"];
        $reference["associated"] = $input["associated"];
        
        $sql = "INSERT INTO sop_generate_drafts (department, equipment_code, sop_no, version_no, title, sop_for, purpose, scope, role, definition, reference, process, procedures, abbreviation, training, distribution, revision, entry_by, entry_date) VALUES ('".$input["department"]."', '".$input["equipment_code"]."', '$sop_no', '".$input["version_no"]."', '".$input["title"]."', '".$input["sop_for"]."', '".$input["purpose"]."', '".$input["scope"]."', '".json_encode($input["role"])."', '".json_encode($input["definition"])."', '".json_encode($reference)."',
        '".$input["process"]."', '".$input["procedures"]."', '".$input["abbreviation"]."', '".json_encode($input["training"])."', '".json_encode($input["distribution"])."', '".json_encode($input["revision"])."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
     else if ($_GET["type"] == "intiiate_checking") {
	    $output = array();
	    $sql = "SELECT * FROM sopinitiation WHERE status='pending' order by id desc";
	    $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	           
	            $output[] = $row;
	        }
	    }
	    echo json_encode($output);
	}
	
	  else if ($_GET["type"] == "download_Sop") {
	       // amardeep
	   
 		$_GET['pdftype']= 'onlyheader';  include('pdfimp2.php');
 		
 		  $sql22 = "SELECT * FROM sop_views limit 1";
        $result22 = $conn->query($sql22);
	if($result22->num_rows > 0){
	
		while($row22 = $result22->fetch_assoc()){
		    
		$sql="select * from sopinitiation where id='".$_GET["id"]."'";
		  $result = $conn->query($sql);
	    if ($result->num_rows > 0) {
	        while ($row = $result->fetch_assoc()) {
	            if($row['purpose'] !=''){
	                 $html.='Purpose:<br>'. $row['purpose'] .'<br>';
	            }
     if($row['scope'] !=''){
     $html.='scope:<br>' . $row['scope'] . '<br>';
     }
     if($row['role'] !=''){
         $html.='Role and Responsibility:<br>' . $row['role'] . '<br>';
     }
     if($row['definition'] !=''){
         $html.='Definition:<br>' . $row['definition'] . '<br>';
    
     }
     if($row['reference'] !=''){
 $html.='References:<br>' . $row['reference'] . '<br>';

    
}
if($row['process'] !=''){
     $html.='<br pagebreak="true"/><br>Process:<br><br>';
    $html.=' <img src="../../upload/Sops/' . $row['process'] . '" alt="Image description" width="540" height="540"> <br>';
}
    if($row['procedures'] !=''){
     $html.='Procedure:<br>' . $row['procedures'] . '<br>';
    }
    if($row['abbreviation'] !=''){
          $html.='abbreviation:<br>' . $row['abbreviation'] . '<br>';
    
    }
    if($row['training'] !=''){
            $html.='training:<br>' . $row['training'] . '<br>';
    }
    if($row['distribution'] !=''){
            $html.='distribution:<br>' . $row['distribution'] . '<br>';
     
    }
    if($row['attachments'] !=''){
     $html.='<br pagebreak="true"/><br>attachments:<br><br>';
    $html.=' <img src="../../upload/Sops/' . $row['attachments'] . '" alt="Image description" width="540" height="540"> <br>';
}
    if($row['revision'] !=''){
          $html.='revision:<br>' . $row['revision'] . '<br>';
     
    }
     if($row['adds1'] !=''){
	                 $html.='adds1:<br>'. $row['adds1'] .'<br>';
	            }
   if($row['adds2'] !=''){
       $html.='adds2:<br>'. $row['adds2'] .'<br>';
                }
     if($row['adds3'] !=''){
	                 $html.='adds3:<br>'. $row['adds3'] .'<br>';
	            }
	   if($row['adds4'] !=''){
	                 $html.='adds4:<br>'. $row['adds4'] .'<br>';
	            }
	   if($row['adds5'] !=''){
	                 $html.='adds5:<br>'. $row['adds5'] .'<br>';
	            }
     
	        }
	     }
		}
	}
		$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	   
	   //}
        }
	   
	  
	  else if ($_GET["type"] == "check_initiate_req") {

	    $sql = "UPDATE sopinitiation SET status='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
	    if ($conn->query($sql)) {
	        echo "{\"status\":\"success\"}";
	    } else {
	        echo "{\"status\":\"".$conn->error."\"}";
	    }
	
	            
	        }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>