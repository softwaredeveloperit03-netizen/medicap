<?php 
require '../db.php';
require '../token.php';
$output = Array();
$token = $_GET["token"];
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
    
if ($_GET["type"] == "getPendingCAPA") {
    $output = Array();
    $sql = "SELECT * FROM capa WHERE status='pending'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveCAPA") {
    
   $sql = "UPDATE capa SET required_for='".$input["required_for"]."', capa_category='".$input["capa_category"]."', 
    implement_need='".$input["implement_need"]."', planned_correction='".$input["planned_correction"]."', 
    corrective_action='".$input["corrective_action"]."', prepare_by='".$_GET["emp_id"]."', 
    prepare_date='$entry_date', status='inprocess' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getInprocessCAPA") {
    $output = Array();
    $sql = "SELECT * FROM capa WHERE status='inprocess'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
        	  else if ($_GET["type"] == "save_extededCAPANEW_Final") {
// Allow CORS
header("Access-Control-Allow-Origin: *"); // Replace * with specific domains if needed
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Specify allowed methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Specify allowed headers

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Return 200 OK for the preflight request
    http_response_code(200);
    exit();
}


	              $sql = "UPDATE capa_extention SET
                    status = 'closed'
                WHERE id='".$_GET['extend_capa_id']."'";
  if ($conn->query($sql)) {
          $sql1 = "UPDATE capa SET status='closed'  WHERE id='".$_GET['capa_id']."'";
         $conn->query($sql1); 
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
 	 }
	  



else if ($_GET["type"] == "getDepartments") {
    $output = Array();
    $sql = "SELECT * FROM department WHERE status='active' AND department !='Quality Assurance'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["status"] = false;
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "checkCAPA") {
    $sql = "UPDATE capa SET status='".$input["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        $data = $input["departments"];
        for ($i = 0; $i < count($data); $i++) {
            $dept = $data[$i];
            $sql = "INSERT INTO capa_comments (capa_no, department) VALUES ('".$input["capa_no"]."', '$dept')";
            $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getcapalog") {
    $output = Array();
    if($_GET["fromdate"] != '' && $_GET["todate"] != ''){
        $sql = "SELECT * FROM capa WHERE DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' 
        AND '".$_GET["todate"]."'";
    }else{
      $sql = "SELECT * FROM capa"; 
    }
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            $sql1 = "SELECT * FROM capa_comments WHERE capa_no='".$row["capa_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            $row["departments"] = $output1;
            if ($row["status"] == "approve" || $row["status"] == "reject") {
                $row["files"] = json_decode($row["files"]);
                $files = (array)$row["files"];
                for ($i = 0; $i < count($files); $i++) {
                    $file = $files[$i];
                    $file->url = "upload/capa/".$file->file_name;
                    $files[$i] = $file;
                }
                $row["files"] = $files;
            }
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getReviewCAPA") {
    $output = Array();
    $sql = "SELECT * FROM capa WHERE status='active'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql1 = "SELECT * FROM capa_comments WHERE capa_no='".$row["capa_no"]."' 
            AND department='".$_GET["department"]."' AND status='pending'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row["dept_id"] = $row1["id"];
                    $output[] = $row;
                }
            }
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveDeptReview") {
    $sql = "UPDATE capa_comments SET comment='".$input["comment"]."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date', status='active' WHERE id='".$input["id"]."'";
    if ($conn->query($sql)) {
        $sql = "SELECT * FROM capa_comments WHERE capa_no='".$input["capa_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows == 0) {
            $sql = "UPDATE capa SET status='checked' WHERE capa_no='".$input["capa_no"]."'";
            $conn->query($sql);
        }
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"] == "getCheckedCAPA") {
    $output = Array();
    $sql = "SELECT * FROM capa WHERE status='checked'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output1 = Array();
            $sql1 = "SELECT * FROM capa_comments WHERE capa_no='".$row["capa_no"]."'";
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
} else if ($_GET["type"] == "updateCheckedCAPA") {
    $input = $_POST;
    $files = Array();
    if ($input["document_required"] == "Yes") {
        $target_dir = "../upload/capa/";
        for ($i = 0; $i < +$input["files"]; $i++) {
            $path_parts = pathinfo($_FILES["file-".$i]["name"]);
            $extension = $path_parts['extension'];
            $target_file = $target_dir."".$input["capa_no"]."file-$i".$extension;
            $file_name = $input["capa_no"]."file-$i".$extension;
            move_uploaded_file($_FILES["file-".$i]["tmp_name"], $target_file);
            $temp = Array();
            $temp["file_name"] = $file_name;
            $temp["upload_name"] = $path_parts;
            $files[] = $temp;
        }
    }
    $sql = "UPDATE capa SET status='".$input["proposed_change"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date', preventive_proposal='".$input["preventive_proposal"]."', regular_clearance='".$input["regular_clearance"]."', info_sent='".$input["info_sent"]."', proposal_evaluation='".$input["proposal_evaluation"]."', document_required='".$input["document_required"]."', files='".json_encode($files)."' WHERE capa_no='".$input["capa_no"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}

}else {
    echo "Invalid Token";
}

$conn->close();
?>