<?php 
require 'db.php';
require 'token.php';
require 'tcpdf/tcpdf.php';
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
    
    if($_GET["type"] == "savecapa"){
        $id = 0;
        $sql = "SELECT IFNULL(MAX(id), 0) as id FROM capa";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row["id"];
                break;
            }
        }
        $id++;
        $capa_no = $id;
        $sql = "INSERT INTO capa (capa_no, related_department, required_in_system, category, detail,entry_by,entry_date) VALUES ('$capa_no','".$input['department']."','".$input['capa_required']."','".$input['category']."','".$input['details']."','".$_GET['emp_id']."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"failed\"}";
        }
    }
    else if($_GET["type"] == "getpendingcapa"){
        $sql = "SELECT * FROM capa WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getcapalog"){
        if($_GET['fromdate'] != ''){
            $sql = "SELECT * FROM capa WHERE entry_date between '".$_GET['fromdate']."' AND '".$_GET['todate']."'";
        }else{
            $sql = "SELECT * FROM capa";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "approveCAPA") {
        $sql = "UPDATE capa SET approve_date='$entry_date', approve_by='".$_GET["emp_id"]."', status='approve' WHERE capa_no='".$_GET["capa_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "rejectCAPA") {
        $sql = "UPDATE capa SET approve_date='$entry_date', approve_by='".$_GET["emp_id"]."', status='reject' WHERE capa_no='".$_GET["capa_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if($_GET['type'] == 'getcategorychart'){
        $output = Array();
        $series = Array();
        $lables = Array();
        $sql = "SELECT COUNT(id) as total, category  FROM capa GROUP BY category";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lables[] = $row["category"];
                $series[] = +$row["total"];
            }
        }
        $output["series"] = $series;
        $output["lables"] = $lables;
        echo json_encode($output);
    }

}else {
    echo "Invalid Token";
}

$conn->close();
$qc->close();
$store->close();
$purchase->close();
$security->close();
$qa->close();
$hr->close();
?>