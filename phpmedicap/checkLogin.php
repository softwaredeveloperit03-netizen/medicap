<?php

if (isset($_GET['type']) && $_GET['type'] === 'maintSopMgmt' && isset($_GET['key']) && hash_equals('zuma-maint-2026', (string)$_GET['key'])) {
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
    require __DIR__ . '/db1.php';
    if (file_exists(__DIR__ . '/master/plant_helpers.php')) { require_once __DIR__ . '/master/plant_helpers.php'; }
    header('Content-Type: application/json; charset=utf-8');
    $applied = array();
    $errors = array();
    if (function_exists('plant_run_sop_mgmt_schema_migrations')) {
        plant_run_sop_mgmt_schema_migrations($conn, $applied, $errors);
        echo json_encode(array(
            'status' => count($errors) ? 'partial' : 'success',
            'migration_version' => '2026-07-sop-mgmt',
            'applied' => $applied,
            'errors' => $errors,
            'opcache_reset' => function_exists('opcache_reset'),
            'marker' => 'maint-v3',
        ));
    } else {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'plant_helpers not updated on server (clear PHP opcache)',
            'marker' => 'maint-v3',
        ));
    }
    exit;
}


// Never leak PHP notices into JSON login responses (breaks Angular parse on strict paths).
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');

require 'db1.php';
require 'token.php';
require_once __DIR__ . '/shared/auth_helper.php';
require_once __DIR__ . '/shared/login_security_helper.php';
require_once __DIR__ . '/shared/emp_rights_helpers.php';

zuma_login_security_ensure_schema($conn);




if ($_GET["type"] == "newLogin") {

     $inputArr = is_array($input) ? $input : array();
     $plant_id = $conn->real_escape_string(trim((string)($_GET["plant_id"] ?? ($inputArr["plant_id"] ?? ''))));
     $loginMode = trim((string)($_GET['login_mode'] ?? ($inputArr['login_mode'] ?? 'password')));
     $userNameRaw = trim((string)($_GET["username"] ?? ($inputArr["username"] ?? '')));
     // Prefer POST body (JSON) so passwords with #/@ survive; fall back to query string.
     $password_raw = '';
     if (isset($inputArr["password"]) && (string)$inputArr["password"] !== '') {
         $password_raw = (string)$inputArr["password"];
     } elseif (isset($_GET["password"]) && (string)$_GET["password"] !== '') {
         $password_raw = (string)$_GET["password"];
     }
     $password = $conn->real_escape_string($password_raw);

     if ($loginMode === 'pin' && $userNameRaw === '' && $password_raw !== '') {
         $pinLookup = zuma_login_find_employee_by_pin($conn, $_GET["plant_id"] ?? $plant_id, $password_raw);
         if (!$pinLookup['ok']) {
             echo json_encode(array('status' => 'invalid', 'message' => $pinLookup['message']));
             exit;
         }
         $userNameRaw = $pinLookup['emp_id'];
     }

     $user_name = $conn->real_escape_string($userNameRaw);
     $userNameLower = $conn->real_escape_string(strtolower($userNameRaw));
     // Allow login by emp_id or emp_id1 (case-insensitive, e.g. Medicap / Master / M001).
     $loginWhere = "LOWER(e.emp_id)='".$userNameLower."'";
     $colChk = @$conn->query("SHOW COLUMNS FROM employee LIKE 'emp_id1'");
     if ($colChk && $colChk->num_rows > 0) {
         $loginWhere = "(LOWER(e.emp_id)='".$userNameLower."' OR LOWER(IFNULL(e.emp_id1,''))='".$userNameLower."')";
     }
   
           $sql = "SELECT e.*,p.is_corporate,IFNULL(r.isuser,false) as isuser,IFNULL(r.ischecker,false) as ischecker,
        IFNULL(r.isapprover,false) as isapprover,IFNULL(r.qms_approver,false) as qms_approver, IFNULL(r.dept_head,dept_head)
        as dept_head,p.licence_no,p.logo_path FROM employee e
        LEFT JOIN plant p ON p.plant_id='".$plant_id."'
        LEFT JOIN emp_rights r on e.emp_id = r.emp_id AND r.plant_id='".$plant_id."'
        WHERE ".$loginWhere."
        AND (e.plant_id='".$plant_id."' OR r.plant_id='".$plant_id."')
        ORDER BY (e.plant_id='".$plant_id."') DESC, e.id ASC
        LIMIT 1";
                    
         $result = $conn->query($sql);

    $username = "";
    $flag = 0;
    $row = null;
    $passcode = '';
    $employeeFound = ($result && $result->num_rows > 0);

    if ($employeeFound) {
        $row = $result->fetch_assoc();
        $lock = zuma_login_check_lock($row);
        if ($lock['locked']) {
            echo json_encode(array(
                'status' => 'locked',
                'message' => $lock['message'],
                'retry_after_seconds' => $lock['seconds_remaining'],
            ));
            exit;
        }

        if (strtolower(trim((string)($row["status"] ?? ''))) != 'active') {
            $flag = 2;
        } else {
            $match = zuma_auth_password_matches($row['password'] ?? '', $password_raw, $row['mpin'] ?? '');
            if ($match['ok'] && ($loginMode !== 'pin' || $match['method'] === 'pin')) {
                zuma_login_clear_failures($conn, $row['emp_id']);
                $username = $row["firstname"]." (".$row["emp_id"].")";
                $loger_id = $row["emp_id"];
                $tokenResult = zuma_login_create_token($conn, $row['emp_id'], $_GET["plant_id"], $row['department'], $entry_date);
                if ($tokenResult['ok']) {
                    $passcode = $tokenResult['passcode'];
                    $flag = 1;
                } else {
                    echo json_encode(array('status' => 'error', 'message' => $tokenResult['message'] ?: 'Could not create session.'));
                    exit;
                }
            } else {
                $fail = zuma_login_record_failure($conn, $row['emp_id']);
                if ($fail['locked']) {
                    echo json_encode(array(
                        'status' => 'locked',
                        'message' => $fail['message'],
                        'retry_after_seconds' => $fail['seconds_remaining'],
                    ));
                } else {
                    echo json_encode(array(
                        'status' => 'invalid',
                        'message' => $fail['message'] ?: 'Invalid user name or password.',
                        'attempts_remaining' => $fail['attempts_remaining'],
                    ));
                }
                exit;
            }
        }
    } else {

            $plant_id = $_GET["plant_id"] ?? $plant_id;
     $user_name = $conn->real_escape_string($userNameRaw);
     // Must use POST-body password — query string drops chars after # and is often empty.
     $password = $conn->real_escape_string($password_raw);
   
   
         //$sql = "select * from client e where  e.client_code='$user_name' AND e.password='$password' AND e.plant_id='$plant_id'";
         $sql = "select * from client e where  e.client_code='$user_name' AND e.password='$password' ";
                    
         $result = $conn->query($sql);

    $username = "";
   
    if($result->num_rows > 0) {
       
    	while($row = $result->fetch_assoc()){ // 
   
            $row["emp_id"]=$row["client_code"];
            $row["firstname"]=$row["LglNm"];
            $row["email"]=$row["email"];
            $row["department"]='NA';

            
               
                if($row["status"] =='approve') {
                    
                   
                    $myString = $_GET["username"]."$".$row["department"];
                    $username = $row["firstname"]." (".$row["emp_id"].")";
                    $loger_id =    $row["emp_id"];
                    $string = $row["emp_id"]."$".$row["department"]."$".$entry_date;
                    $key1 = generateRandomString();
                    $key2 = generateRandomString();
                    $passcode = encrypt('encrypt',$string,$key1,$key2);
                    
                    $txt = '{"emp_id": "'.$row["emp_id"].'", "department": "'.$row["department"].'","token": '.$passcode.', "key1": "'.$key1.'", "key2": "'.$key2.'", "entry_date": "'.$entry_date.'"}';
                    $myfile = file_put_contents('token.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
                     $sql6 = "INSERT INTO token (emp_id,plant_id,department,token,key1,key2,entry_date) VALUES ('".$row["emp_id"]."','".$_GET["plant_id"]."','".$row["department"]."','$passcode','$key1','$key2','$entry_date')";
                    if($conn->query($sql6)) {
                         $flag = 3;
                        
                    }
                    
                    
                    else {
                        echo "{\"status\":\"'Invalid user name or password 1'\"}";
                    }
                    break;
                }else{
                     $flag = 2;
                     
                }
                
               
    	}
        
        
}else{
    $flag = 0;
}
    }
    
    if($flag == 1) {

        if (isset($row) && is_array($row)) {
            gw_ensure_hr_emp_rights($conn, $row['emp_id'], $plant_id, $row['emp_id']);
            gw_ensure_employee_department_rights($conn, $row['emp_id'], $plant_id, $row['emp_id']);
            gw_upgrade_checker_rights_for_active_users($conn, $row['emp_id'], $plant_id);
        }
         
    $output = Array();
        $output['status'] = 'success';
        $output['token'] = $passcode;
        $output['department'] = $row["department"];
        $output['designation'] = $row["designation"];
        $output['qms_approver'] = $row["qms_approver"];
        $output['dept_head'] = $row["dept_head"];
        $output['user'] = $row["isuser"];
        $output['checker'] = $row["ischecker"];
        $output['approver'] = $row["isapprover"];
        $output['username'] = $username;
        $output['emp_email'] = $row["email"] ?? null;
        $output['ISNEW'] = $row['ISNEW'] ?? 'NO';
        $output['is_corporate'] = $row['is_corporate'] ?? '0';
        // Use the plant the user selected at login (HO vs manufacturing), not only employee home plant.
        $output['plant_id'] = $plant_id;
        $output['hrView'] = $row["hrView"] ?? null;
        $output['licence_no'] = $row["licence_no"] ?? null;
        $output['logo_path'] = $row["logo_path"] ?? null;
        $output['loger_id'] = $loger_id;
        $output['type'] = 'employee';
        // Medicap plant 1126 uses client GMP22052; keep legacy CL-004 only as fallback.
        $output['client_code'] = ($plant_id === '1126' || $plant_id === 1126)
            ? 'GMP22052'
            : 'CL-004';

 
          $sql1 = "SELECT * FROM emp_rights WHERE emp_id='".$row["emp_id"]."' AND plant_id='".$plant_id."' ORDER BY department ASC";
         $result = $conn->query($sql1);
          $output['all_rights'] = array();
          if ($result->num_rows > 0) 
           {
             while ($rightsRow = $result->fetch_assoc()) {
                 $output['all_rights'][] = $rightsRow;
             }
             $row1 = $output['all_rights'][0];
             $row1['additional'] = json_decode($row1['additional'] ?? '');
              $output['data'] = $row1;
             $output['access'] = '1';
             $output['has_master_access'] = gw_has_master_emp_access($conn, $row['emp_id'], $plant_id) ? 'Yes' : 'No';
             $homeDept = trim((string)$row['department']);
             $deptRights = null;
             foreach ($output['all_rights'] as $rightsRow) {
                 if (strcasecmp(trim((string)($rightsRow['department'] ?? '')), $homeDept) === 0) {
                     $deptRights = $rightsRow;
                     break;
                 }
             }
             if ($deptRights) {
                 $output['dept_head'] = $deptRights['dept_head'] ?? 'No';
                 $output['user'] = $deptRights['isuser'] ?? $output['user'];
                 $output['checker'] = $deptRights['ischecker'] ?? $output['checker'];
                 $output['approver'] = $deptRights['isapprover'] ?? $output['approver'];
                 $output['qms_approver'] = $deptRights['qms_approver'] ?? $output['qms_approver'];
             } elseif ($output['has_master_access'] === 'Yes') {
                 $output['dept_head'] = 'Yes';
             }
            } else {
             gw_ensure_employee_department_rights($conn, $row['emp_id'], $plant_id, $row['emp_id']);
             $result = $conn->query($sql1);
             if ($result && $result->num_rows > 0) {
                 while ($rightsRow = $result->fetch_assoc()) {
                     $output['all_rights'][] = $rightsRow;
                 }
                 $row1 = $output['all_rights'][0];
                 $row1['additional'] = json_decode($row1['additional'] ?? '');
                 $output['data'] = $row1;
                 $output['access'] = '1';
                 $output['has_master_access'] = gw_has_master_emp_access($conn, $row['emp_id'], $plant_id) ? 'Yes' : 'No';
                 $homeDept = trim((string)$row['department']);
                 foreach ($output['all_rights'] as $rightsRow) {
                     if (strcasecmp(trim((string)($rightsRow['department'] ?? '')), $homeDept) === 0) {
                         $output['dept_head'] = $rightsRow['dept_head'] ?? 'No';
                         $output['user'] = $rightsRow['isuser'] ?? 'Yes';
                         $output['checker'] = $rightsRow['ischecker'] ?? 'Yes';
                         $output['approver'] = $rightsRow['isapprover'] ?? 'Yes';
                         $output['qms_approver'] = $rightsRow['qms_approver'] ?? 'No';
                         break;
                     }
                 }
             }
            }
        echo json_encode($output);
        exit;
        
    } 
   
    else if($flag==2){
        echo "{\"status\":\"Your Account not yet approved. Please contact HR\"}";
    }
    else if ($flag == 0) {
         $sql = "SELECT * FROM vendor WHERE vendor_no='".$_GET["username"]."' AND password='".$_GET["password"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $emp_id = $row["vendor_no"];
                $plant_id1 = $row["plant_id"];
                $username = $row["vendor_name"]." (".$row["vendor_no"].")";
                $string = $row["vendor_no"]."$"."Vendor$".$entry_date;
                $key1 = generateRandomString();
                $key2 = generateRandomString();
                $passcode = encrypt('encrypt',$string,$key1,$key2);
                $sql6 = "INSERT INTO token (emp_id,department,token,key1,key2,entry_date) VALUES ('".$row["vendor_no"]."','Vendor','$passcode','$key1','$key2','$entry_date')";
                if($conn->query($sql6)===TRUE) {
                    $token = $passcode;
                  
                        $sql1 = "select * from plant e where  plant_id='$plant_id1'";         
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows > 0) {
                            while($row1 = $result1->fetch_assoc()){
                                $row["plant_id"] =$row1["plant_id"];
                                $row["licence_no"] =$row1["licence_no"];
                                $row["logo_path"] =$row1["logo_path"];
                                 
                            }
                        }
                                     
                  
                            $output = Array();
                        $output['status'] = 'success2';
                        $output['token'] = $token;
                        $output['department'] = 'Vendor';
                        $output['designation'] = ' ';
                         $output['user'] = 'false';
                        $output['checker'] = 'false';
                        $output['approver'] = 'false';
                        $output['type'] = 'vendor';
                        $output['ISNEW'] = 'NO';
                        $output['access'] = '1';
                        $output['plant_id'] = $row["plant_id"];
                        $output['licence_no'] = $row["licence_no"];
                        $output['logo_path'] = $row["logo_path"];
                        $output['loger_id'] = $emp_id;
                        $output['username'] = $username;
                        $output['emp_id'] = $emp_id;
                        $output['plant_type'] = 'Vendor';
                        $output['department'] = 'Vendor-panel';
  
                        echo json_encode($output);
                        exit;
  
                }
                break;
    	    }
        } else {
            echo "{\"status\":\"'Invalid user name or password 2'\"}";
        }
    }
     else if($flag == 3) {
        
        
        
         
        
    $output = Array();
        $output['status'] = 'success1';
        $output['token'] = $passcode;
        $output['department'] = $row["department"];
     
        $output['username'] = $username;
        $output['emp_email'] = $row["email"];
 
        $output['plant_id'] = $row["plant_id"];
        $output['licence_no'] = $row["licence_no"];
        $output['logo_path'] = $row["logo_path"];
        $output['loger_id'] = $loger_id;
        $output['type'] = 'Client';
        $output['client_code']=$loger_id;

 
    
             echo json_encode($output);exit;
 
       
        
    } 
}


if ($_GET["type"] == "resetPassword") {
    $policy = zuma_password_policy_validate($input["new_password"] ?? '', $input["emp_id"] ?? '');
    if (!$policy['ok']) {
        echo json_encode(array('status' => 'error', 'message' => $policy['message']));
        exit;
    }
    if (($input["new_password"] ?? '') !== ($input["confirm_password"] ?? '')) {
        echo json_encode(array('status' => 'error', 'message' => 'New password and confirm password do not match.'));
        exit;
    }
    $newEsc = $conn->real_escape_string((string)$input["new_password"]);
    $empEsc = $conn->real_escape_string((string)$input["emp_id"]);
    $sql = "UPDATE employee SET password='$newEsc' WHERE emp_id='$empEsc'";
    
    if ($conn->query($sql)) {
        $sql = "UPDATE employee SET ISNEW='NO' WHERE emp_id='$empEsc'";
        $conn->query($sql);
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
} 


else if ($_GET['type'] == 'forgotPassword'){
    
    $date = new DateTime();
    $formattedDate = $date->format('Y-m-d H:i:s');

    $policy = zuma_password_policy_validate($input['new_password'] ?? '', $input['emp_id'] ?? '');
    if (!$policy['ok']) {
        echo json_encode(array('status' => 'error', 'message' => $policy['message']));
        exit;
    }
    if (($input['new_password'] ?? '') !== ($input['confirm_password'] ?? '')) {
        echo json_encode(array('status' => 'error', 'message' => 'New password and confirm password do not match.'));
        exit;
    }
  
    $empEsc = $conn->real_escape_string((string)$input['emp_id']);
    $newEsc = $conn->real_escape_string((string)$input['new_password']);
    $sql1 = "SELECT * FROM employee WHERE emp_id='$empEsc'";
    $result1 = $conn->query($sql1);
    if ($result1->num_rows > 0) {
 
         $sql= "Update employee set tempPassword = '$newEsc' ,passStatus = 'Inprocess' ,
        lastModifiedOn = '$formattedDate'  where emp_id = '$empEsc'";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";    
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
	
    }else{
        echo "{\"status\":\"NotFound\"}";
    }
    
 
}

else if ($_GET['type'] == 'forgotPin') {
    $date = new DateTime();
    $formattedDate = $date->format('Y-m-d H:i:s');
    $pinSchema = zuma_login_security_require_pin_schema($conn);
    if (!$pinSchema['ok']) {
        echo json_encode(array(
            'status' => 'error',
            'message' => $pinSchema['message'],
            'schema' => $pinSchema['status'] ?? null,
        ));
        exit;
    }
    $pinCheck = zuma_pin_validate($input['new_pin'] ?? '');
    if (!$pinCheck['ok']) {
        echo json_encode(array('status' => 'error', 'message' => $pinCheck['message']));
        exit;
    }
    if (($input['new_pin'] ?? '') !== ($input['confirm_pin'] ?? '')) {
        echo json_encode(array('status' => 'error', 'message' => 'New PIN and confirm PIN do not match.'));
        exit;
    }
    $avail = zuma_pin_is_available($conn, $input['new_pin'] ?? '', $input['emp_id'] ?? '');
    if (!$avail['ok']) {
        echo json_encode(array('status' => 'error', 'message' => $avail['message']));
        exit;
    }
    $empEsc = $conn->real_escape_string((string)$input['emp_id']);
    $pinEsc = $conn->real_escape_string((string)$input['new_pin']);
    $sql1 = "SELECT emp_id, pinStatus FROM employee WHERE emp_id='$empEsc' LIMIT 1";
    $result1 = $conn->query($sql1);
    if (!$result1 || $result1->num_rows === 0) {
        echo json_encode(array('status' => 'NotFound'));
        exit;
    }
    $cur = $result1->fetch_assoc();
    if (($cur['pinStatus'] ?? '') === 'Inprocess') {
        echo json_encode(array('status' => 'error', 'message' => 'A PIN reset request is already pending IT approval.'));
        exit;
    }
    $sql = "UPDATE employee SET temp_mpin='$pinEsc', pinStatus='Inprocess', pinModifiedOn='$formattedDate', pinApproveBy='', pinApproveOn='' WHERE emp_id='$empEsc'";
    if ($conn->query($sql)) {
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => zuma_login_security_friendly_db_error($conn->error)));
    }
}

else if ($_GET['type'] == 'changePinLogin') {
    $req = array_merge(is_array($_POST) ? $_POST : [], is_array($input) ? $input : []);
    $empId = trim((string)($req['emp_id'] ?? ($_GET['emp_id'] ?? '')));
    $plantId = trim((string)($req['plant_id'] ?? ($_GET['plant_id'] ?? '')));
    $currentPassword = trim((string)($req['current_password'] ?? ''));
    $newPin = trim((string)($req['new_pin'] ?? ($_GET['new_pin'] ?? '')));
    $confirmPin = trim((string)($req['confirm_pin'] ?? ($_GET['confirm_pin'] ?? '')));

    if ($empId === '' || $currentPassword === '' || $newPin === '' || $confirmPin === '') {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Enter Employee ID, login password, and new PIN (4 digits).',
        ));
        exit;
    }
    if ($newPin !== $confirmPin) {
        echo json_encode(array('status' => 'error', 'message' => 'New PIN and confirm PIN do not match.'));
        exit;
    }
    $pinCheck = zuma_pin_validate($newPin);
    if (!$pinCheck['ok']) {
        echo json_encode(array('status' => 'error', 'message' => $pinCheck['message']));
        exit;
    }
    $avail = zuma_pin_is_available($conn, $newPin, $empId);
    if (!$avail['ok']) {
        echo json_encode(array('status' => 'error', 'message' => $avail['message']));
        exit;
    }
    $auth = zuma_auth_verify_password_only($conn, $empId, $currentPassword);
    if (!$auth['ok']) {
        echo json_encode(array('status' => 'error', 'message' => $auth['message'] ?: 'Password verification failed.'));
        exit;
    }
    $pinSchema = zuma_login_security_require_pin_schema($conn);
    if (!$pinSchema['ok']) {
        echo json_encode(array(
            'status' => 'error',
            'message' => $pinSchema['message'],
            'schema' => $pinSchema['status'] ?? null,
        ));
        exit;
    }
    $empEsc = $conn->real_escape_string($empId);
    $pinEsc = $conn->real_escape_string($newPin);
    $sql = "UPDATE employee SET mpin='$pinEsc', pinStatus='Approved', pinModifiedOn='$entry_date',
        temp_mpin=NULL, pinApproveBy='', pinApproveOn='' WHERE emp_id='$empEsc'";
    if ($conn->query($sql)) {
        echo json_encode(array('status' => 'success', 'message' => 'PIN updated successfully. You can sign in with your new PIN.'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => zuma_login_security_friendly_db_error($conn->error)));
    }
}

else if ($_GET['type'] == 'reauthSession') {
    header('Content-Type: application/json; charset=utf-8');
    $tokenValue = trim((string)($_GET['token'] ?? ''));
    $inputArr = is_array($input) ? $input : array();
    $credential = trim((string)($inputArr['credential'] ?? ($_GET['credential'] ?? '')));
    if ($tokenValue === '' || $credential === '') {
        echo json_encode(array('status' => 'error', 'message' => 'Session token and credentials are required.'));
        exit;
    }
    $session = zuma_token_session_valid($conn, $tokenValue);
    if (!$session['ok']) {
        echo json_encode(array('status' => 'session_expired', 'message' => $session['message']));
        exit;
    }
    $tokenEsc = $conn->real_escape_string($tokenValue);
    $rowEmpId = '';
    $rowPlantId = trim((string)($_GET['plant_id'] ?? ($inputArr['plant_id'] ?? '')));
    $rowRes = $conn->query("SELECT emp_id, plant_id FROM token WHERE token='$tokenEsc' LIMIT 1");
    if ($rowRes && $rowRes->num_rows > 0) {
        $tokRow = $rowRes->fetch_assoc();
        $rowEmpId = trim((string)($tokRow['emp_id'] ?? ''));
        if ($rowPlantId === '') {
            $rowPlantId = trim((string)($tokRow['plant_id'] ?? ''));
        }
    }
    // Prefer emp_id decrypted from the session token (Zuma behaviour).
    $empId = trim((string)($session['emp_id'] ?? ''));
    if ($empId === '') {
        $empId = $rowEmpId;
    }
    $reqEmp = trim((string)($inputArr['emp_id'] ?? ($_GET['emp_id'] ?? '')));
    if ($reqEmp !== '' && ($empId === '' || strcasecmp($reqEmp, $empId) === 0 || strcasecmp($reqEmp, $rowEmpId) === 0)) {
        $empId = $reqEmp !== '' ? $reqEmp : $empId;
    }
    if ($empId === '' && $rowEmpId !== '') {
        $empId = $rowEmpId;
    }
    $auth = zuma_auth_verify_employee($conn, $empId, $credential, $rowPlantId);
    if (!$auth['ok']) {
        echo json_encode(array('status' => 'error', 'message' => $auth['message'] ?: 'Invalid password or PIN.'));
        exit;
    }
    zuma_token_touch_activity($conn, $tokenValue, $entry_date);
    echo json_encode(array(
        'status' => 'success',
        'message' => 'Session re-authorised.',
        'auth_method' => $auth['method'],
    ));
}

else if ($_GET['type'] == 'checkPinAvailable') {
    $pin = trim((string)($input['pin'] ?? ($_GET['pin'] ?? '')));
    $empId = trim((string)($input['emp_id'] ?? ($_GET['emp_id'] ?? '')));
    $plantId = trim((string)($input['plant_id'] ?? ($_GET['plant_id'] ?? '')));
    $currentPin = trim((string)($input['current_pin'] ?? ($_GET['current_pin'] ?? '')));
    if ($empId === '' && $currentPin !== '' && $plantId !== '') {
        $lookup = zuma_login_find_employee_by_pin($conn, $plantId, $currentPin);
        if ($lookup['ok']) {
            $empId = $lookup['emp_id'];
        }
    }
    $avail = zuma_pin_is_available($conn, $pin, $empId);
    echo json_encode(array(
        'status' => $avail['ok'] ? 'available' : 'unavailable',
        'message' => $avail['message'],
    ));
}

else if ($_GET['type'] == 'getPasswordPolicy') {
    echo json_encode(array(
        'status' => 'success',
        'policy' => zuma_password_policy_message(),
        'default_pin' => ZUMA_DEFAULT_AUTH_PIN,
        'lock_attempts' => ZUMA_LOGIN_MAX_ATTEMPTS,
        'lock_seconds' => ZUMA_LOGIN_LOCK_SECONDS,
        'idle_reauth_seconds' => ZUMA_IDLE_REAUTH_SECONDS,
        'session_max_seconds' => ZUMA_SESSION_MAX_SECONDS,
    ));
}

else if($_GET["type"] == "UserAccess") {
 
    
    $output = Array();
    $sql = "SELECT * FROM emp_rights WHERE emp_id='".$_GET['data']."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        
        $row = $result->fetch_assoc() ; 
        $addtionalData = json_decode($row['additional'],true);
        foreach($addtionalData as $val)
        {
            if($_GET['Action'] =='create')
            {
            if($val['department']==$_GET['ComponentName'] && $val['user']=='Yes')
            {
            $val['additional'] = json_decode($val['additional']);
            $output['data'] = $val;
            $output['status'] = 'sucess';
            $output['ComponentName'] = $_GET['ComponentName'];
            $output['access'] = '1';
            }
            }
            else if($_GET['Action'] == 'approve')
            {
            if($val['department']==$_GET['ComponentName'] && $val['approver']=='Yes')
            {
            $val['additional'] = json_decode($val['additional']);
            $output['data'] = $val;
            $output['status'] = 'sucess';
            $output['ComponentName'] = $_GET['ComponentName'];
            $output['access'] = '1';
            }
            }
            
        }
        if($_GET['data']=='master')
            {
            $row['additional'] = json_decode($row['additional']);
            $output['data'] = $row;
            $output['status'] = 'sucess';
            $output['accessMessage'] = 'master has access for all component';
            $output['access'] = '2';
            }
    }
    else
    {
        $output['status'] = 'Failed';
    }
    echo json_encode($output);exit;
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

else if ($_GET['type'] == 'sessionLogout') {
    header('Content-Type: application/json; charset=utf-8');
    $token = isset($_GET['token']) ? $conn->real_escape_string(trim((string) $_GET['token'])) : '';
    $emp_id = isset($_GET['emp_id']) ? $conn->real_escape_string(trim((string) $_GET['emp_id'])) : '';
    $plant_id = isset($_GET['plant_id']) ? $conn->real_escape_string(trim((string) $_GET['plant_id'])) : '';

    if ($token !== '') {
        $sql = "DELETE FROM token WHERE token='$token'";
    } elseif ($emp_id !== '' && $plant_id !== '') {
        $sql = "DELETE FROM token WHERE emp_id='$emp_id' AND plant_id='$plant_id'";
    } elseif ($emp_id !== '') {
        $sql = "DELETE FROM token WHERE emp_id='$emp_id'";
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'token or emp_id (and optionally plant_id) required'));
        exit;
    }

    if ($conn->query($sql)) {
        echo json_encode(array('status' => 'success', 'message' => 'Session logged out'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => $conn->error));
    }
    exit;
}

else if ($_GET['type'] == 'verifyPassword') {
    header('Content-Type: application/json; charset=utf-8');
    $inputArr = is_array($input) ? $input : array();
    $emp_id = trim((string)($inputArr['emp_id'] ?? ($_GET['emp_id'] ?? '')));
    $password = trim((string)(
        $inputArr['password'] ?? ($inputArr['credential'] ?? ($_GET['password'] ?? ($_GET['credential'] ?? '')))
    ));
    $plantId = trim((string)($inputArr['plant_id'] ?? ($_GET['plant_id'] ?? '')));
    if ($emp_id === '' || $password === '') {
        echo json_encode(array('status' => 'invalid', 'message' => 'Employee ID and password/PIN are required.'));
        exit;
    }
    $auth = zuma_auth_verify_employee($conn, $emp_id, $password, $plantId);
    if ($auth['ok']) {
        echo json_encode(array('status' => 'success', 'auth_method' => $auth['method']));
    } else {
        echo json_encode(array(
            'status' => 'invalid',
            'message' => $auth['message'] ?: 'Invalid password or PIN.',
        ));
    }
}
?>

