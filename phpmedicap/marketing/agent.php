<?php




// ini_set('display_errors', 1);
// error_reporting(E_ALL);





require '../db.php';
require '../token.php';
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
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveAgent") {
        
        
         $sql = "INSERT INTO agent (user_no, agent_name, phone, email, percentage_on, percentage, gst_type, gst_no, state_code, pan_no, contact_person, address, entry_by, entry_date) VALUES ('".$_GET["user_no"]."','".$input["agent_name"]."','".$input["phone"]."','".$input["email"]."', '".$input["percentage_on"]."', '".$input["percentage"]."','".$input["gst_type"]."','".$input["gst_no"]."', '".$input["state_code"]."', '".$input["pan_no"]."', '".$input["contact_person"]."', '".$input["address"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    } else if ($_GET["type"] == "getPendingAgents") {
        $output = array();
        $sql = "SELECT * FROM agent WHERE user_no='".$_GET["user_no"]."' AND status='pending' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateAgent") {
        $sql = "UPDATE agent SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getAgentsLog") {
        $output = array();
        $sql = "SELECT * FROM agent   ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
          
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getApprovedAgents") {
        $output = array();
        $sql = "SELECT * FROM agent WHERE  status='approve' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getStatement") {
        $output = array();
        $sql = "SELECT * FROM agent WHERE user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $total_commission = 0;
                $output1 = array();
                $sql1 = "SELECT * FROM client WHERE user_no='".$_GET["user_no"]."' AND agent_no='".$row["agent_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT IFNULL(SUM(net_total), 0) as amount, COUNT(id) as invoice_no FROM tax_invoice WHERE client_code='".$row1["client_code"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $total_commission += +$row2["amount"];
                                $row1["invoice_amount"] = $row2["amount"];
                                $row1["invoice_no"] = $row2["invoice_no"];
                            }
                        } else {
                            $row1["invoice_amount"] = 0.00;
                            $row1["invoice_no"] = 0;
                        }
                        $output1[] = $row1;
                    }
                }
                $row["total_commission"] = $total_commission;
                $row["clients"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}

$conn->close();
?>