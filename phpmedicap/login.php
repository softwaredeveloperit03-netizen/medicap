<?php
require 'db.php';
require 'token.php';




ini_set('display_errors', 1);
error_reporting(E_ALL);


if ($_GET["type"] == "newLogin") {
     $plant_id = $_GET["plant_id"];
     $user_name = $_GET["username"];
     $password = $_GET["password"];
        // echo $plant_id+","+$user_name+","+$password;
    $sql = "SELECT e.*,p.is_corporate FROM employee e LEFT JOIN plant p ON p.id=e.plant_id  WHERE e.status='active' AND e.emp_id='$user_name' 
    AND e.password='$password'AND e.plant_id='$plant_id' ";
 
    $result = $conn->query($sql);
    $username = "";
    $flag = 0;
    if($result->num_rows > 0) {
    	while($row = $result->fetch_assoc()){
            //if($_GET["username"]==$row["emp_id"]) {
              //  if($row["password"] == $_GET["password"]) {
                    $myString = $_GET["username"]."$".$row["department"];
                    $username = $row["firstname"]." (".$row["emp_id"].")";
                    $string = $row["emp_id"]."$".$row["department"]."$".$entry_date;
                    $key1 = generateRandomString();
                    $key2 = generateRandomString();
                    $passcode = encrypt('encrypt',$string,$key1,$key2);
                    
                    $txt = '{"emp_id": "'.$row["emp_id"].'", "department": "'.$row["department"].'","token": "'.$passcode.'", "key1": "'.$key1.'", "key2": "'.$key2.'", "entry_date": "'.$entry_date.'"}';
                    $myfile = file_put_contents('token.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
                    $sql6 = "INSERT INTO token (emp_id,department,token,key1,key2,entry_date) VALUES 
                    ('".$row["emp_id"]."','".$row["department"]."','$passcode','$key1','$key2','$entry_date')";
                    if($conn->query($sql6)) {
                        
                        $flag = 1;
                    } else {
                        echo "{\"status\":\"failed\"}";
                    }
                    break;
              //  }
           // }
    	}
    }
    
    if($flag == 1) {
        
        echo "{\"status\":\"success\",\"token\":\"".$passcode."\",\"department\":\"".$row["department"]."\",\"designation\":\"".$row["designation"]."\",
        \"user\":\"".$row["isuser"]."\",\"checker\":\"".$row["ischecker"]."\",\"approver\":\"".$row["isapprover"]."\",\"username\":\"$username\",
        \"emp_email\":\"".$row["email"]."\",\"emp_id\":\"".$row["emp_id"]."\", \"ISNEW\":\"".$row["ISNEW"]."\",\"is_corporate\":\"".$row["is_corporate"]."\",
        \"plant_id\":\"".$row["plant_id"]."\", \"type\":\"employee\",\"client_code\":\"CL-004\"}";
        
    }else if ($flag == 0) {
        
         $sql = "SELECT * FROM vendor WHERE vendor_no='".$_GET["username"]."' AND password='".$_GET["password"]."'  ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $emp_id = $row["vendor_no"];
                $username = $row["vendor_name"]." (".$row["vendor_no"].")";
                $string = $row["vendor_no"]."$"."Vendor$".$entry_date;
                $key1 = generateRandomString();
                $key2 = generateRandomString();
                $passcode = encrypt('encrypt',$string,$key1,$key2);
                $sql6 = "INSERT INTO token (emp_id,department,token,key1,key2,entry_date) VALUES ('".$row["vendor_no"]."','Vendor','$passcode','$key1','$key2','$entry_date')";
                if($conn->query($sql6)===TRUE) {
                    $token = $passcode;
                    echo "{\"status\":\"success\",\"token\":\"".$token."\",\"department\":\"Vendor\",\"designation\":\"Vendor\",\"user\":\"false\",\"checker\":\"false\",
                    \"approver\":\"false\",\"username\":\"$username\",\"emp_id\":\"$emp_id\",\"type\":\"vendor\", \"plant_id\":\"".$row["plant_id"]."\"}";
                }
                break;
    	    }
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
}

if ($_GET["type"] == "resetPassword") {
    $sql = "UPDATE employee SET password='".$input["new_password"]."' WHERE emp_id='".$input["emp_id"]."'";
    if ($conn->query($sql)) {
        $sql = "UPDATE employee SET ISNEW='NO' WHERE emp_id='".$input["emp_id"]."'";
        $conn->query($sql);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} 
else if ($_GET["type"] == "checkDigiSIgn") {
    require_once __DIR__ . '/shared/auth_helper.php';
    $credential = trim((string) ($_GET["mpin"] ?? ''));
    $targetEmp = trim((string) ($_GET["emp_id"] ?? ''));
    $plant_id = trim((string) ($_GET["plant_id"] ?? ''));
    header('Content-Type: application/json; charset=utf-8');
    if ($credential === '') {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid password or PIN'));
        exit;
    }
    if ($targetEmp !== '') {
        $auth = zuma_auth_verify_employee($conn, $targetEmp, $credential);
        if ($auth['ok']) {
            echo json_encode(array('status' => 'success', 'auth_method' => $auth['method']));
        } else {
            echo json_encode(array('status' => 'error', 'message' => $auth['message'] ?: 'Invalid password or PIN'));
        }
        exit;
    }
    $credEsc = $conn->real_escape_string($credential);
    $plantClause = $plant_id !== '' ? " AND plant_id='" . $conn->real_escape_string($plant_id) . "'" : '';
    $sql = "SELECT emp_id, password, mpin, status FROM employee WHERE status='active' $plantClause";
    $result = $conn->query($sql);
    $ok = false;
    $method = '';
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $match = zuma_auth_password_matches($row['password'] ?? '', $credential, $row['mpin'] ?? '');
            if ($match['ok']) {
                $ok = true;
                $method = $match['method'];
                break;
            }
        }
    }
    if ($ok) {
        echo json_encode(array('status' => 'success', 'auth_method' => $method));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid password or PIN'));
    }
}
else if ($_GET["type"] == "checkDigiSIgntraining") {
    
     $sql = "select * from employee where  ( mpin = '".$_GET["mpin"]."' OR password = '".$_GET["mpin"]."')  AND  plant_id='".$_GET["plant_id"]."'";
   $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        
            $sql1 = "select * from employee where  emp_id = '".$_GET["training_emp_id"]."' AND  password = '".$_GET["employee_password"]."' AND  plant_id='".$_GET["plant_id"]."'";
           $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"Wrong Training Employee Password\"}";
            }
    
    
    } else {
        echo "{\"status\":\"Wrong Training Cordinator Password!!!!\"}";
    }
}




else if ($_GET["type"] == "validateUser") {
    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()){
            $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
            $string = explode("$",$string);
            $_GET["emp_id"] = $string[0];
            $_GET["department"] = $string[1];
        }
        
        $_GET["emp_email"] = "";
        $sql = "SELECT * FROM employee WHERE emp_id='".$_GET["emp_id"]."' limit 1";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET["emp_email"] = $row["emp_email"];
            }
        }
        
        echo "{\"status\":\"success\",\"token\":\"$token\", \"emp_email\":\"".$_GET["emp_email"]."\"}";
    } else {
        echo "{\"status\":\"invalid\"}";
    }
}
?>