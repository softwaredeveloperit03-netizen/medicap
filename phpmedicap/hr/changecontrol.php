<?php
require '../db.php';
require '../token.php';

$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
while($row = $result->fetch_assoc()) {
	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	$string = explode("$",$string);
	$_GET["emp_id"] = $string[0];
	$_GET["department"] = $string[1];
	break;
}

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);

if($_GET["type"]=="getDepartments"){
	$sql = "SELECT * FROM department WHERE status='active' AND department_name !='Quality Assurance'";
	$result = $conn->query($sql);
	$output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
            $row["status"] = false;
			$output[] = $row;
		}
	}
  echo json_encode($output);
    } else if($_GET["type"]=="saveChangControl") {
        $sql = "INSERT INTO designation (department, change_related,change_title,
        export,domastic,impact_product,entry_by, entry_date) 
        VALUES ('".$input["department"]."','".$input["change_related"]."','".$input["change_title"]."',
        '".$input["export"]."','".$input["domastic"]."','".$input["impact_product"]."', '".$entry_date."')";
        if($conn->query($sql)){
        	echo "{\"status\":\"success\"}";
        } else {
        	echo "{\"status\":\"".$conn->error."\"}";
        }
} else if ($_GET["type"] == "getPendingChangeControls") {
    $output = Array();
    $sql = "SELECT * FROM changecontrol WHERE status='pending' AND departments LIKE'%Human Resource%'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["product_details"] = json_decode($row["product_details"]);
            $row["departments"] = json_decode($row["departments"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}
else if ($_GET["type"] == "checkChangeControl") {
    $sql = "UPDATE changecontrol SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date', dept_remark='".$_GET["remark"]."' WHERE id='".$_GET["id"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\": true}";
    } else {
        echo "{\"status\": false}";
    }
 }else if ($_GET["type"] == "getInprocessDept") {
    $output = Array();
    $sql = "SELECT * FROM changecontrol WHERE status='active' AND departments LIKE'%Human Resource%'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["product_details"] = json_decode($row["product_details"]);
            $row["departments"] = json_decode($row["departments"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}else if ($_GET["type"] == "reviewChangeControl") {
    $sql = "UPDATE changecontrol SET status='approve', verify_by='".$_GET["emp_id"]."', verify_date='$entry_date', comments='".$input["comment"]."' WHERE ctrl_no='".$_GET["ctrl_no"]."'";
   echo $sql;
    if ($conn->query($sql)) {
        echo "{\"status\": true}";
    } else {
        echo "{\"status\": false}";
    }
}else if ($_GET["type"] == "getApprovedChangeControls") {
    $output = Array();
    $sql = "SELECT * FROM changecontrol WHERE status='approve' departments LIKE'%Human Resource%'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["product_details"] = json_decode($row["product_details"]);
            $row["departments"] = json_decode($row["departments"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}else if ($_GET["type"] == "closeChangeControl") {
    $sql = "UPDATE changecontrol SET close_by='".$_GET["emp_id"]."', close_date='".$entry_date."', close_remark='".$input["remark"]."', status='".$input["status"]."' WHERE ctrl_no='".$input["ctrl_no"]."'";
    if ($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}else if ($_GET["type"] == "getChangeControlsDept") {
    $output = Array();
    $sql = "SELECT * FROM changecontrol WHERE status='close' departments LIKE'%Human Resource%'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row["product_details"] = json_decode($row["product_details"]);
            $row["departments"] = json_decode($row["departments"]);
            $output[] = $row;
        }
    }
    echo json_encode($output);
}else if ($_GET["type"] == "getAdminDepartments") {
        $output = Array();
        $sql = "SELECT * FROM changecontrol where departments LIKE '%Admin%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}else if($_GET["type"]=="getRegulatoryDepartments"){
	$output = Array();
    $sql = "SELECT * FROM changecontrol where departments LIKE '%Regulatory%'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else {
    echo "[]";
}

$conn->close();
?>