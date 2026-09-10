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
    $conn->query($sql);

 
    if($_GET['type'] == 'saveinitiation'){
         
        $sql = "INSERT INTO sopinitiation (plant_id,sopNo,sopName,sopType,department,entry_by,entry_date,status)
        VALUES ('".$_GET['plant_id']."','".$input['sopNo']."','".$input['sopName']."','".$input['sopType']."',
        '".$input['department']."','".$_GET["emp_id"]."','$entry_date','TO_UPLOAD')";
          if($conn->query($sql)) {
              $last_id = $conn->insert_id;
              
            $sql1 = "INSERT INTO sops (plant_id,sopId,sop_no,supersedNo,version_no,revisionDate,fileName,status)
            VALUES ('".$_GET['plant_id']."','$last_id','".$input['sopNo']."','".$input['supersedNo']."','".$input['version_no']."',
            '1','NA','TO_UPLOAD')";
           
            if($conn->query($sql1)) {
                echo "{\"status\":\"success\"}";
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
                
         }else{
             echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
         }
    }
    else if($_GET['type'] == 'saveExistingSopFile'){
        
        $input = $_POST;
        $sid = $input["sopNo"];
        $sNo = $input["supersedNo"];
        $vNo = $input["version_no"];
        $sNm = $input["sopName"];
        $pid = $_GET["plant_id"];
        
        if (isset($_FILES["sopFile"])) {
            $file_tmp = $_FILES['sopFile']['tmp_name'];
            $file_ext = strtolower(end(explode('.', $_FILES['sopFile']['name'])));
            $sopFile = $pid.$sid.$sNo.$vNo.$sNm.".".$file_ext;
            move_uploaded_file($file_tmp, "../../upload/Sops/" . $sopFile);
        }
        
         
        $sql = "INSERT INTO sopinitiation (plant_id,sopNo,sopName,sopType,department,entry_by,entry_date,status)
        VALUES ('".$_GET['plant_id']."','".$input['sopNo']."','".$input['sopName']."','".$input['sopType']."',
        '".$input['department']."','".$_GET["emp_id"]."','$entry_date','Approved')";
          if($conn->query($sql)) {
              $last_id = $conn->insert_id;
              
            $sql1 = "INSERT INTO sops (plant_id,sopId,sop_no,supersedNo,version_no,revisionDate,effectiveDate,fileName,status,entryBy,
            deptHeadBy,qaBy,qaHeadBy,entryDate,deptHeadOn,qaOn,qaHeadOn)VALUES ('".$_GET['plant_id']."','$last_id','".$input['sopNo']."',
            '".$input['supersedNo']."','".$input['version_no']."','".$input['revisionDate']."','".$input['effectiveDate']."','$sopFile','Active','".$_GET["emp_id"]."','".$_GET["emp_id"]."',
            '".$_GET["emp_id"]."','".$_GET["emp_id"]."','$entry_date','$entry_date','$entry_date','$entry_date')";
           
            if($conn->query($sql1)) {
                echo "{\"status\":\"success\"}";
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
                
         }else{
             echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
         }
    }
    else if($_GET['type'] == 'uploadSop'){
        
        $input = $_POST;
        $sid = $input["sopNo"];
        $sNo = $input["supersedNo"];
        $vNo = $input["version_no"];
        $sNm = $input["sopName"];
        $pid = $_GET["plant_id"];
        
        if (isset($_FILES["sopFile"])) {
            $file_tmp = $_FILES['sopFile']['tmp_name'];
            $file_ext = strtolower(end(explode('.', $_FILES['sopFile']['name'])));
            $sopFile = $pid.$sid.$sNo.$vNo.$sNm.".".$file_ext;
            move_uploaded_file($file_tmp, "../../upload/Sops/" . $sopFile);
        }
        
        
            $sql1 = "Update sopinitiation SET status = 'Pending' WHERE id = '".$_GET['iniId']."'";
           
            if($conn->query($sql1)) {
                
                $sql = "Update sops SET status = 'Pending'  ,fileName = '$sopFile',entryBy = '".$_GET["emp_id"]."' ,
                entryDate =  '$entry_date'  WHERE id = '".$_GET['sopsId']."'";
                
                if($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                }else{
                    echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
                }
                
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
            
            
    }
    else if($_GET['type'] == 'getDepartments') {
        $output = array();
        $sql = "SELECT * FROM department  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getSOpFOrUpload') {
        $output = array();
        $sql = "SELECT a.*,a.id as iniId, b.id as sopsId ,a.status as iniStatus, b.status as sopStatus ,b.* FROM sopinitiation a left join  sops b ON a.id =  b.sopId where
        a.status = 'TO_UPLOAD' AND ( b.status = 'TO_UPLOAD' OR b.status = 'TO_REUPLOAD' )  AND a.plant_id = '".$_GET['plant_id']."' AND a.department = '".$_GET['deptName']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getPendingSopForDeptHeadReview') {
        $output = array();
        $sql = "SELECT a.*,a.id as iniId, b.id as sopsId ,b.* FROM sopinitiation a left join  sops b ON a.id =  b.sopId where a.status = 'Pending' AND 
        b.status = 'Pending' AND a.plant_id = '".$_GET['plant_id']."' AND a.department = '".$_GET['deptName']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getPendingSopForQaReview') {
        $output = array();
        $sql = "SELECT a.*,a.id as iniId, b.id as sopsId ,b.* FROM sopinitiation a left join  sops b ON a.id =  b.sopId 
        where a.status = 'TO_QA' AND 
        b.status = 'Inproccess' AND a.plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getPendingSopForQaHeadReview') {
        $output = array();
        $sql = "SELECT a.*,a.id as iniId, b.id as sopsId ,b.* FROM sopinitiation a left join  sops b ON a.id =  b.sopId 
        where a.status = 'TO_QA_HEAD' AND 
        b.status = 'Inproccess' AND a.plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getPendingSopForFinalUpload') {
        $output = array();
        $sql = "SELECT a.*,a.id as iniId, b.id as sopsId ,a.status as iniStatus, b.status as sopStatus ,b.* FROM sopinitiation a left join  sops b ON a.id =  b.sopId 
        where a.status = 'TO_FINAL_UPLOAD' AND  a.department = '".$_GET['deptName']."' AND
        b.status = 'Inproccess' AND a.plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'getSopLog') {
        $output = array();
        $sql = "SELECT a.*,a.id as iniId, b.id as sopsId ,a.status as iniStatus, b.status as sopStatus ,b.* FROM sopinitiation a left join  sops b ON a.id =  b.sopId 
        where a.status = 'Approved' AND a.department = '".$_GET['deptName']."' AND
        b.status = 'Active' AND a.plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                $output1 = array();
                $sql1 = "SELECT * FROM sops where sopId = '".$row['iniId']."' AND  plant_id = '".$_GET['plant_id']."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                
                $row['revisionHistory'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if($_GET['type'] == 'saveReviewedBydeptHead') {
        
            $sql1 = "Update sopinitiation SET status = 'TO_QA' WHERE id = '".$_GET['iniId']."'";
           
            if($conn->query($sql1)) {
                
                $sql = "Update sops SET status = 'Inproccess',deptHeadBy = '".$_GET["emp_id"]."' ,deptHeadOn =  '$entry_date'
                WHERE id = '".$_GET['sopsId']."'";
                if($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                }else{
                    echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
                }
                
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
    }
    else if($_GET['type'] == 'saveReUploadReviewByDeptHead') {
        
            $sql1 = "Update sopinitiation SET status = 'TO_UPLOAD' WHERE id = '".$_GET['iniId']."'";
           
            if($conn->query($sql1)) {
                
                $sql = "Update sops SET status = 'TO_REUPLOAD', reUploadComment = '".$input['reUploadComment']."',
                reUploadFrom = '".$input['reUploadFrom']."' ,deptHeadBy = '".$_GET["emp_id"]."' ,deptHeadOn =  '$entry_date'
                WHERE id = '".$_GET['sopsId']."'";
                if($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                }else{
                    echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
                }
                
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
    }
    else if($_GET['type'] == 'saveReUploadReviewByQa') {
        
            $sql1 = "Update sopinitiation SET status = 'TO_UPLOAD' WHERE id = '".$_GET['iniId']."'";
           
            if($conn->query($sql1)) {
                
                $sql = "Update sops SET status = 'TO_REUPLOAD', reUploadComment = '".$input['reUploadComment']."',
                reUploadFrom = '".$input['reUploadFrom']."' ,qaBy = '".$_GET["emp_id"]."' ,qaOn =  '$entry_date'
                WHERE id = '".$_GET['sopsId']."'";
                if($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                }else{
                    echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
                }
                
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
    }
    else if($_GET['type'] == 'saveReUploadReviewByQaHead') {
        
            $sql1 = "Update sopinitiation SET status = 'TO_UPLOAD' WHERE id = '".$_GET['iniId']."'";
           
            if($conn->query($sql1)) {
                
                $sql = "Update sops SET status = 'TO_REUPLOAD', reUploadComment = '".$input['reUploadComment']."',
                reUploadFrom = '".$input['reUploadFrom']."' ,qaHeadBy = '".$_GET["emp_id"]."' ,qaHeadOn =  '$entry_date'
                WHERE id = '".$_GET['sopsId']."'";
                if($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                }else{
                    echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
                }
                
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
    }
    else if($_GET['type'] == 'saveReviewedByQa') {
        
            $sql1 = "Update sopinitiation SET status = 'TO_QA_HEAD' WHERE id = '".$_GET['iniId']."'";
           
            if($conn->query($sql1)) {
                
                $sql = "Update sops SET  qaBy = '".$_GET["emp_id"]."' ,qaOn =  '$entry_date'
                WHERE id = '".$_GET['sopsId']."'";
                if($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                }else{
                    echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
                }
                
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
            
    }
    else if($_GET['type'] == 'saveReviewedByQaHead') {
        
            $sql1 = "Update sopinitiation SET status = 'TO_FINAL_UPLOAD' WHERE id = '".$_GET['iniId']."'";
           
            if($conn->query($sql1)) {
                $sql = "Update sops SET  qaHeadBy = '".$_GET["emp_id"]."' ,qaHeadOn =  '$entry_date'
                WHERE id = '".$_GET['sopsId']."'";
                if($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                }else{
                    echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
                }
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
            
    }
    else if($_GET['type'] == 'finalUploadSignedCopy') {
        
        $input = $_POST;
        $sid = $input["sopNo"];
        $sNo = $input["supersedNo"];
        $vNo = $input["version_no"];        $sNm = $input["sopName"];

        $pid = $_GET["plant_id"];
        
        if (isset($_FILES["sopFile"])) {
            $file_tmp = $_FILES['sopFile']['tmp_name'];
            $file_ext = strtolower(end(explode('.', $_FILES['sopFile']['name'])));
            $sopFile = $pid.$sid.$sNo.$vNo.$sNm.".".$file_ext;
            move_uploaded_file($file_tmp, "../../upload/Sops/" . $sopFile);
        }
        
            $sql1 = "Update sopinitiation SET status = 'Approved' WHERE id = '".$_GET['iniId']."'";
           
            if($conn->query($sql1)) {
                
                $sql = "Update sops SET status = 'Active' ,revisionDate = '".$input['revisionDate']."',
                effectiveDate = '".$input['effectiveDate']."',fileName = '$sopFile' WHERE id = '".$_GET['sopsId']."'";
                
                if($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                }else{
                    echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
                }
                
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
    }   
    else if($_GET['type'] == 'saveSopRevisionRequest') {
        
        $sql1 = "INSERT INTO `revisionRequest`( `plant_id`, `refDocNo`, `refDocId`,`refDocName`, `revisionComment`, `reqFor`,
        `entryBy`, `entryOn`,`status`) VALUES ('".$_GET['plant_id']."','".$input['sopNo']."','".$input['iniId']."','".$input['sopName']."',
        '".$input['revisionComment']."','SOP Revision Request','".$_GET["emp_id"]."','$entry_date','Pending')";
      
        //do Not Change reqFor in this api this is have logic while getting data in QA
       
        if($conn->query($sql1)) {
             
        
            $sql11 = "UPDATE `sopinitiation`  SET revisionStatus = 'Inprocess' WHERE id = '".$input['iniId']."'";
            if($conn->query($sql11)) {
                echo "{\"status\":\"success\"}";
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
            
        }else{
            echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
        }
        
    }
    else if ($_GET["type"]=="saveChangeControlSop") {
     
        $sql1="SELECT count(id)+1 as id FROM changecontrol ";
        $result1 = $conn->query($sql1);
        $row1 = $result1->fetch_assoc();
        $last_id=$row1["id"];
        $ctrl_no = "CC-".$last_id;
        
        
     	$sql= "INSERT INTO changecontrol (plant_id,ctrl_no,department,change_related,existing_procedure,proposed_change,reason_for_changes,
    	entry_by,entry_date) VALUES('".$_GET["plant_id"]."','".$ctrl_no."','".$input["department_name"]."','".$input["particular"]."',
    	'".$input["existing_procedure"]."','".$input["proposed_change"]."','".$input["reason_for_changes"]."','".$_GET["emp_id"]."',
    	'$entry_date')";
        
        if ($conn->query($sql)) {
            
                 $sql11 = "UPDATE `sopinitiation`  SET revisionStatus = 'Done' , ccStatus = 'Sent' WHERE id = '".$_GET['iniId']."'";
                if($conn->query($sql11)) {
                    echo "{\"status\":\"success\"}";
                }else{
                    echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
                }
                
        } else {
                echo "{\"status\":\"".$conn->error."\"}";
        }
            
            
    }
    else if($_GET['type'] == 'SentForRevision'){
         

        $sql1 = "Update sopinitiation SET status = 'TO_UPLOAD',ccStatus = 'Pending' , revisionStatus = 'Pending', 
        lastRevisionBy =  '".$_GET["emp_id"]."' , lastRevisionOn =  '$entry_date'  WHERE id = '".$_GET['iniId']."'";
        
          if($conn->query($sql1)) {
             
              
            $sql2 = "INSERT INTO sops (plant_id,sopId,sop_no,supersedNo,version_no,revisionDate,fileName,status)
            VALUES ('".$_GET['plant_id']."','".$_GET['iniId']."','".$input['sopNo']."','".$input['supersedNo']."',
            '".$input['version_no']."','1','NA','TO_UPLOAD')";
           
            if($conn->query($sql2)) {
                
                    $sql3 = "Update sops SET status = 'In-Active', revisionBy =  '".$_GET["emp_id"]."' ,
                    revisionOn =  '$entry_date'  WHERE id = '".$_GET['sopsId']."'";
                   
                    if($conn->query($sql3)) {
                        echo "{\"status\":\"success\"}";
                    }else{
                        echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
                    }
                
            }else{
                echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
            }
                
         }else{
             echo "{\"status\":\"failed\",\"error\":\"$conn->error\"}";
         }
         
         
    }

   


   
    
    
     
	  
 } else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>