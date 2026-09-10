<?php


 

    ini_set('display_errors', 1);
    error_reporting(E_ALL);



require 'db.php';
require 'token.php';

    require 'vendor/autoload.php';
    use \Firebase\JWT\JWT;
    use \Firebase\JWT\Key;



if ($_GET["type"] == "newLogin") {


    $secretKey = 'e5b4a9098f7c4d41c6efc15c7de5020a9a90ab01d5e6b3cd1377a6a9fc3f3f1a'; // Strong secret key

    $plant_id = $_GET["plant_id"];
    $user_name = $_GET["username"];
    $password = $_GET["password"];
    $entry_date = date("Y-m-d H:i:s");

    $sql = "SELECT e.*, p.is_corporate, p.licence_no, p.logo_path 
            FROM employee e 
            LEFT JOIN plant p ON p.plant_id = e.plant_id 
            LEFT JOIN emp_rights r ON e.emp_id = r.emp_id 
            WHERE e.emp_id = '$user_name' AND BINARY e.password = '$password'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row["status"] == 'active') {
                $username = $row["firstname"] . " (" . $row["emp_id"] . ")";
                $loger_id = $row["emp_id"];

                // JWT payload
                $payload = [
                    'iss' => 'http://192.168.1.28',  // issuer
                    'iat' => time(),                   // issued at
                    'exp' => time() + 3600,            // expires in 1 hour
                    'data' => [
                        'emp_id' => $row["emp_id"],
                        'username' => $username,
                        'department' => $row["department"],
                        'plant_id' => $plant_id
                    ]
                ];

                // Create JWT token
                $jwt = JWT::encode($payload, $secretKey, 'HS256');

                // Output
                $output = [
                    'status' => 'success',
                    'token' => $jwt,
                    'department' => $row["department"],
                    'designation' => $row["designation"],
                    'username' => $username,
                    'emp_email' => $row["email"],
                    'ISNEW' => $row['ISNEW'],
                    'is_corporate' => $row['is_corporate'],
                    'plant_id' => $row["plant_id"],
                    'licence_no' => $row["licence_no"],
                    'logo_path' => $row["logo_path"],
                    'loger_id' => $loger_id,
                    'type' => 'employee',
                    'client_code' => 'CL-004'
                ];

                // Check user rights
                $sql1 = "SELECT * FROM emp_rights WHERE emp_id='" . $row["emp_id"] . "'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $output['access'] = '1';
                    echo json_encode($output);
                } else {
                    echo json_encode(["status" => "Please Give User Rights..."]);
                }
                exit;
            } else {
                echo json_encode(["status" => "User is not active"]);
                exit;
            }
        }
    } else {
        echo json_encode(["status" => "Invalid username or password"]);
    }
}


else if ($_GET["type"] == "newLoginOld") {

 

     $plant_id = $_GET["plant_id"];
     $user_name = $_GET["username"];
     $password = $_GET["password"];
   
   
        $sql = "SELECT e.*,p.is_corporate,IFNULL(r.isuser,false) as isuser,IFNULL(r.ischecker,false) as ischecker,
        IFNULL(r.isapprover,false) as isapprover,IFNULL(r.qms_approver,false) as qms_approver, IFNULL(r.dept_head,dept_head)
        as dept_head,p.licence_no,p.logo_path FROM employee e LEFT JOIN plant p ON p.plant_id=e.plant_id LEFT JOIN
        emp_rights r on e.emp_id = r.emp_id WHERE  e.emp_id='$user_name' AND BINARY  e.password='$password'  ";
                    
        $result = $conn->query($sql);

    $username = "";
   
    if($result->num_rows > 0) {
    
    	while($row = $result->fetch_assoc()){  
      
               
                if($row["status"] =='active') {
                    
                   
                    $username = $row["firstname"]." (".$row["emp_id"].")";
                    $loger_id =    $row["emp_id"];
                    $string = $row["emp_id"]."$".$row["department"]."$".$entry_date;
                    $key1 = generateRandomString();
                    $key2 = generateRandomString();
                    $passcode = encrypt('encrypt',$string,$key1,$key2);
                    
                     
                    $sql6 = "INSERT INTO token (emp_id,plant_id,department,token,key1,key2,entry_date) VALUES ('".$row["emp_id"]."','".$_GET["plant_id"]."',
                    '".$row["department"]."','$passcode','$key1','$key2','$entry_date')";
                  
                    if($conn->query($sql6)) {
                         
                            $output = Array();
                            $output['status'] = 'success';
                            $output['token'] = $passcode;
                            $output['department'] = $row["department"];
                            $output['designation'] = $row["designation"];
                            $output['username'] = $username;
                            $output['emp_email'] = $row["email"];
                            $output['ISNEW'] = $row['ISNEW'];
                            $output['is_corporate'] = $row['is_corporate'];
                            $output['plant_id'] = $row["plant_id"];
                            $output['licence_no'] = $row["licence_no"];
                            $output['logo_path'] = $row["logo_path"];
                            $output['loger_id'] = $loger_id;
                            $output['type'] = 'employee';
                            $output['client_code']='CL-004';
                    
                     
                            $sql1 = "SELECT * FROM emp_rights WHERE emp_id='".$row["emp_id"]."'";
                            $result = $conn->query($sql1);
                            if ($result->num_rows > 0) 
                            {
                                     $output['status'] = 'success';
                                     $output['access'] = '1';
                                     echo json_encode($output);
                                     exit;
                            }else{
                                echo "{\"status\":\"'Please Gives User Rights....'\"}";
                            }
                        
                    }
                    else {
                        echo "{\"status\":\"'Token Is Not Generated'\"}";
                    }
                    break;
                    
                }else{
                     echo "{\"status\":\"'User Is Not Active'\"}";
                }
                
    	}
    	
    }else{
        echo "{\"status\":\"'Invalid user name or password 1'\"}";
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


else if ($_GET['type'] == 'forgotPassword'){
    
    $date = new DateTime();
    $formattedDate = $date->format('Y-m-d H:i:s'); // Format the date first

  
    $sql1 = "SELECT * FROM employee WHERE emp_id='".$input['emp_id']."'";
    $result1 = $conn->query($sql1);
    if ($result1->num_rows > 0) {
 
         $sql= "Update employee set tempPassword = '".$input["new_password"]."' ,passStatus = 'Inprocess' ,
        lastModifiedOn = '$formattedDate'  where emp_id = '".$input["emp_id"]."'";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";    
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
	
    }else{
        echo "{\"status\":\"NotFound\"}";
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