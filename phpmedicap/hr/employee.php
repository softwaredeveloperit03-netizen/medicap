<?php



//require '../authMiddleware.php';

//   ini_set('display_errors', 1);
// error_reporting(E_ALL);

    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");

/** Map emp_rights.department values to login dashboard card names (spacing / QA vs Quality Assurance). */
function dashboard_rights_resolve_department($deptRaw)
{
    if ($deptRaw === null || trim((string)$deptRaw) === '') {
        return '';
    }
    $d = strtolower(trim((string)$deptRaw));
    $d = str_replace(array('.', '&'), array('', ' and '), $d);
    $d = preg_replace('/\s*department\s*$/', '', $d);
    $d = preg_replace('/\s+/', ' ', trim($d));
    $compact = str_replace(' ', '', $d);

    $toCard = array(
        'qa' => 'Quality Assurance',
        'qualityassurance' => 'Quality Assurance',
        'quality assurance' => 'Quality Assurance',
        'q a' => 'Quality Assurance',
        'qc' => 'Quality Control',
        'qualitycontrol' => 'Quality Control',
        'quality control' => 'Quality Control',
        'q c' => 'Quality Control',
        'hr' => 'Human Resource',
        'humanresource' => 'Human Resource',
        'human resources' => 'Human Resource',
        'human resource' => 'Human Resource',
        'rnd' => 'R AND D',
        'r and d' => 'R AND D',
        'r&d' => 'R AND D',
        'research and development' => 'R AND D',
        'product development' => 'R AND D',
        'npd' => 'NPD',
        'new product development' => 'NPD',
        'accounts' => 'Account',
        'account' => 'Account',
        'it' => 'IT',
        'information technology' => 'IT',
        'qhead' => 'Q-Head',
        'q head' => 'Q-Head',
        'q-head' => 'Q-Head',
        'quality head' => 'Q-Head',
        'security' => 'Security',
        'material management' => 'Security',
        'materials management' => 'Security',
        'materialmanagement' => 'Security',
        'materialsmanagement' => 'Security',
        'material managment' => 'Security',
        'exports' => 'Exports',
        'import and export' => 'Exports',
        'import export' => 'Exports',
        'enginering store' => 'Enginering Store',
        'engineering store' => 'Enginering Store',
        'general store' => 'Enginering Store',
        'eng store' => 'Enginering Store',
    );

    if (isset($toCard[$d])) {
        return $toCard[$d];
    }
    if (isset($toCard[$compact])) {
        return $toCard[$compact];
    }
    if (strpos($d, 'quality assurance') !== false) {
        return 'Quality Assurance';
    }
    if (strpos($d, 'quality control') !== false) {
        return 'Quality Control';
    }
    if (strpos($d, 'material') !== false && strpos($d, 'management') !== false) {
        return 'Security';
    }
    if ($d === 'security' || strpos($d, 'security') === 0) {
        return 'Security';
    }

    return trim(preg_replace('/\s+/', ' ', (string)$deptRaw));
}

function fetchDesignationsForDepartment($conn, $deptId, $deptName, $plantId = '')
{
    $output2 = array();
    $deptId = $conn->real_escape_string(trim((string)$deptId));
    $deptName = $conn->real_escape_string(trim((string)$deptName));
    $plantFilter = '';
    if ($plantId !== '') {
        $plantId = $conn->real_escape_string(trim((string)$plantId));
        $plantFilter = " AND (plant_id IS NULL OR TRIM(plant_id) = '' OR plant_id = '".$plantId."')";
    }
    $sql2 = "SELECT id, designation FROM designation
        WHERE (
            TRIM(COALESCE(dept_id,'')) = '".$deptId."'
            OR TRIM(COALESCE(dept_id,'')) = '".$deptName."'
        )
        AND (status IS NULL OR TRIM(status) = '' OR LOWER(TRIM(status)) IN ('active', 'pending', 'approve', 'approved'))
        ".$plantFilter."
        ORDER BY designation";
    $result2 = $conn->query($sql2);
    if ($result2 && $result2->num_rows > 0) {
        while ($row2 = $result2->fetch_assoc()) {
            $output2[] = $row2;
        }
    }
    return $output2;
}
    
 
    $token = $_GET["token"];
    $timestamp = time(); 
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $entry_date1 = date("Y-m-d");
    $entry_month1 = date("Y-m");
    $input = json_decode(file_get_contents('php://input'),true);
 
 $user_no = '';
    
    
 

    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    // Standalone Admin (Employee Accounts) endpoints are allowed without a DB token
    $adminBypassTypes = array('getAllUserAccounts', 'getStandardLoginMatrix', 'createStandardLogin');
    $isAdminBypass = in_array(($_GET["type"] ?? ''), $adminBypassTypes);
    if($result->num_rows > 0 || $isAdminBypass){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
     
    
    
    
    if ($_GET["type"] == "getDepartments") {
        $output = Array();
        $sql = "SELECT * FROM department ORDER by department_name ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getDesignations") {
        $output = Array();
    // $sql = "SELECT * FROM designation ORDER by department_name DESC";
    $sql = "SELECT * FROM designation ORDER by designation DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_trolly") {
        $output = Array();
        $sql = "SELECT * FROM std_weight where department='".$_GET["t_dep"]."';";
        //$sql = "SELECT DISTINCT(sampling_plan) as sampling_plan FROM specification";
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
               $row["std_wt_dtl"] = json_decode($row["std_wt_dtl"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "update_salary") {
        $sql = "SELECT * from salary_annexure where applicable_from ='$entry_date1'";
    	$result = $conn->query($sql);
    	if ($result->num_rows > 0) {
    		while ($row = $result->fetch_assoc()) {
    		    $sql1 = "update salary_annexure set status='Pending' where applicable_from ='$entry_date1'";
    	$result1 = $conn->query($sql1);
    		}
    	}
    }
    else if ($_GET["type"] == "get_department") {
        $output = Array();
        $sql = "SELECT department FROM std_weight GROUP BY department;";
        //$sql = "SELECT DISTINCT(sampling_plan) as sampling_plan FROM specification";
       
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDashboardData") {
        $output = Array();
        
        
        $sql = "SELECT 
  TRIM(CONCAT_WS(' ', firstname, lastname)) AS empname,
  department
FROM employee
WHERE DATE_FORMAT(birthdate, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d') AND plant_id='".$_GET["plant_id"]."';
";
        
       $birthday = Array();
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $birthday[] = $row;
            }
        }
        
        $sql1 = "SELECT count(*) as TotalEmp from employee where  plant_id='".$_GET["plant_id"]."'";  $empCOunt = 0;
        $result = $conn->query($sql1);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $empCOunt = $row['TotalEmp'];
            }
        }
        
        $deptCount = 0;
        $sql2 = "SELECT count(*) as deptCount from department ";  
        $result = $conn->query($sql2);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $deptCount = $row['deptCount'];
            }
        }
        
        
        $on_leave_count = 0;
        $sql3 = "SELECT COUNT(*) AS on_leave_count FROM leaveform WHERE CURDATE() BETWEEN leave_from AND leave_to AND plant_id='".$_GET["plant_id"]."'";  
        $result = $conn->query($sql3);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $on_leave_count = $row['on_leave_count'];
            }
        }
        
        
        $totalLabourInfac = 0;
         $sql3 = "SELECT COUNT(*) AS totalLabourInfac FROM labour_attendance WHERE DATE(in_time) = CURDATE() AND status != 'exit'  AND plant_id='".$_GET["plant_id"]."'";  
        $result = $conn->query($sql3);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $totalLabourInfac = $row['totalLabourInfac'];
            }
        }
        
        
        $labourDate = Array();

         $sql24 = "SELECT a.labour_no,l.labour_name,l.contractor_name FROM labour_attendance a left join labour l ON a.labour_no = l.labour_no 
        WHERE DATE(a.in_time) = CURDATE() AND a.status != 'exit' AND a.plant_id='".$_GET["plant_id"]."'";  
 
        $result = $conn->query($sql24);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $labourDate[] = $row;
            }
        }
        
        $leaveData = Array();

        $sql4 = "SELECT TRIM(CONCAT_WS(' ', e.firstname, e.lastname)) AS empname, e.department, l.leave_from, l.leave_to FROM leaveform l JOIN employee e ON 
        l.emp_id = e.emp_id WHERE CURDATE() BETWEEN l.leave_from AND l.leave_to AND l.plant_id='".$_GET["plant_id"]."'";  
        
        $result = $conn->query($sql4);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $leaveData[] = $row;
            }
        }
        
        $holidays = Array();

        $sql5 = "SELECT holiday_name, holiday_date 
        FROM mst_holidays 
        WHERE MONTH(holiday_date) = MONTH(CURDATE())  
        AND YEAR(holiday_date) = YEAR(CURDATE())   AND plant_id='".$_GET["plant_id"]."'
        ORDER BY holiday_date >= CURDATE() DESC, holiday_date ASC";  
        
        $result = $conn->query($sql5);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $holidays[] = $row;
            }
        }
        
        $agreement = Array();

        $sql6 = "SELECT agreement_title, company_name FROM employee_agreement WHERE MONTH(upToDate) = MONTH(CURDATE())  
         AND YEAR(upToDate) = YEAR(CURDATE())  ";  
        
        $result = $conn->query($sql6);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $agreement[] = $row;
            }
        }
        
        
        $dueAgrement = 0;
        $sql3 = "SELECT COUNT(*) AS dueAgrement FROM employee_agreement WHERE MONTH(upToDate) = MONTH(CURDATE()) AND YEAR(upToDate) = YEAR(CURDATE())  ";  
        $result = $conn->query($sql3);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $dueAgrement = $row['dueAgrement'];
            }
        }
         
        
        
        $output['birthday'] = $birthday;
        $output['TotalEmp'] = $empCOunt;
        $output['deptCount'] = $deptCount;
        $output['on_leave_count'] = $on_leave_count;
        $output['leaveData'] = $leaveData;
        $output['holidays'] = $holidays;
        $output['agreement'] = $agreement;
        $output['dueAgrement'] = $dueAgrement;
        $output['totalLabourInfac'] = $totalLabourInfac;
        $output['labourData'] = $labourDate;
        
        
        echo json_encode($output);
    }
    
    
    
    else if ($_GET["type"] == "get_department_by_designation") {
        $output = Array();
        $plantId = isset($_GET["plant_id"]) ? $_GET["plant_id"] : '';
        $sql = "SELECT id,department_name,department_type FROM department order by department_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["designations"] = fetchDesignationsForDepartment($conn, $row["id"], $row["department_name"], $plantId);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getDesignationsByDepartment") {
        $deptName = isset($_GET["department_name"]) ? trim($_GET["department_name"]) : '';
        $output = array();
        if ($deptName !== '') {
            $deptId = '';
            $deptEsc = $conn->real_escape_string($deptName);
            $deptResult = $conn->query("SELECT id FROM department WHERE department_name = '".$deptEsc."' LIMIT 1");
            if ($deptResult && $deptResult->num_rows > 0) {
                $deptRow = $deptResult->fetch_assoc();
                $deptId = $deptRow['id'];
            }
            $plantId = isset($_GET["plant_id"]) ? $_GET["plant_id"] : '';
            $output = fetchDesignationsForDepartment($conn, $deptId, $deptName, $plantId);
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_department_by_designationMeha") {
        $output = Array();
        $data = array();
            $output = Array();
            $sql = "SELECT id,department_name,department_type FROM department order by department_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $output2 = Array();
                    $sql2 = "SELECT id,designation FROM designation WHERE dept_id='".$row["department_name"]."' order by designation";
                     
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output2[] = $row2;
                        }
                    }
                    $row["designations"] = $output2;
                     
                    $output[] = $row;
                }
            }
            $data["departments"] = $output;
        echo json_encode($output);
    }
       else if ($_GET["type"] == "get_departmentMeha") {
        $output = Array();
        $data = array();
            $output = Array();
            $sql = "SELECT id,department_name,department_type FROM department order by department_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $output2 = Array();
                    $sql2 = "SELECT id,designation FROM designation WHERE dept_id='".$row["department_name"]."' order by designation";
                     
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output2[] = $row2;
                        }
                    }
                    $row["designations"] = $output2;
                     
                    $output[] = $row;
                }
            }
            $data["departments"] = $output;
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "get_designation_by_dept") {
  
        $output2 = Array();
$sql2 = "SELECT d.designation, dd.department_name 
         FROM designation d 
         LEFT JOIN department dd ON d.dept_id = dd.id  
         WHERE d.plant_id ='".$_GET["plant_id"]."' 
         AND dd.department_name ='".$_GET["department_name1"]."' 
         GROUP BY d.designation";
         
        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $output2[] = $row2;
            }
        }
              
        echo json_encode($output2);
    }
    
     else if ($_GET["type"] == "getEmployeesList_increment_letter") {
  
        $output= Array();
        $sql = "SELECT e.*  from employee e INNER JOIN salary_annexure sa on sa.emp_id=e.emp_id where sa.status='Waiting'";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
              
        echo json_encode($output);
    }
     else if ($_GET["type"] == "HOgetEmployeesList_increment_letter") {
  
        $output= Array();
        $sql = "SELECT e.*  from employee e INNER JOIN salary_annexure sa on sa.emp_id=e.emp_id where sa.status='Waiting'
        AND e.plant_id = '".$_GET["plantID"]."'";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
              
        echo json_encode($output);
    }
    
    
     else if ($_GET["type"] == "getEmployeesSalaryAnnexure") {
  
        $output= Array();
       $sql = "SELECT sa.* ,e.emp_id,e.firstname, e.department,e.designation from salary_annexure sa  INNER JOIN employee e  on sa.emp_id=e.emp_id  where sa.emp_id='".$_GET["empid"]."'";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
              
        echo json_encode($output);
    }
    
       else if ($_GET["type"] == "getEmployeeSalaryAnnexureDetails")
       {
  
        $output= Array();
       $sql = "SELECT * from salary_annexure_details where salary_annexure_id='".$_GET["id"]."'";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
              
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getResignedEmployeeList"){
        
       $output = Array();
         
        $sql = "SELECT * FROM employee WHERE status = 'Exit' and plant_id = '".$_GET["plant_id"]."' order by id desc ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output2 = Array();
                $sql2 = "SELECT * FROM emp_document WHERE emp_id='".$conn->real_escape_string($row["emp_id"])."' AND  plant_id='".$_GET["plant_id"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2[] = $row2;
                    }
                }
                    
                $row["documents"] = $output2;
                    
                $row["academics"] = json_decode($row["academics"]);
                $row["languages"] = json_decode($row["languages"]);
                $row["employeement"] = json_decode($row["employeement"]);
                
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    
    }
    else if ($_GET["type"] == "HOgetResignedEmployeeList")
       {
  
        $output= Array();
       $sql = "SELECT * from employee where status='Exit' AND plant_id='".$_GET["plantID"]."'";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
              
        echo json_encode($output);
    }
    
    
    
    else if ($_GET["type"] == "getQualifications") {
        $output = Array();
        $sql = "SELECT * FROM qualification WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_EMP_by_designation") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status = 'active' AND department='".$_GET["department1"]."' and designation='".$_GET["designation1"]."' and plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_EMP_by_department") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status = 'active' AND department='".$_GET["department1"]."' and plant_id='".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_EMP_by_ID") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status = 'active' AND emp_id='".$_GET["emp_id"]."' and plant_id='".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getEmployeesss") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status = 'active'   and plant_id='".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "HOgetEmployeesss") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status = 'active'   and plant_id='".$_GET["plantID"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "get_last_emp_code") {  
        
            $plant_id = isset($_GET["plant_id"]) ? intval($_GET["plant_id"]) : 0;

            $sql = "SELECT emp_id FROM employee WHERE plant_id = '$plant_id' ORDER BY id DESC LIMIT 1";

            $result = $conn->query($sql);
        
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo json_encode(["emp_id" => $row["emp_id"]]);
            } else {
                echo json_encode(["emp_id" => null]);
            }
        
    } 
    else if ($_GET["type"] == "HOget_last_emp_code") {
        $output = Array();
        $sql = "SELECT * from employee where   order by 1 desc limit 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
     else if ($_GET["type"] == "changeDepartment") {
 
            $sql = "UPDATE employee set status = 'changedept' ,change_dept_status = 'YES' ,change_department = '".$input["change_department"]."' ,
            change_designation = '".$input["change_designation"]."'  ,change_description = '".$input["change_description"]."', changeDeptBy = '".$_GET['emp_id']."', changeDeptOn = '$entry_date'
            where emp_id = '".$input["emp_id123"]."'"; 
            
             if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        
    }
 
     else if ($_GET["type"] == "changeDepartmentApprove") {
         
         if($input["status"] == 'Approved'){
             
            $sql = "UPDATE employee set status = 'Active'   ,department = '".$input["change_department"]."' , changeDeptAppBy = '".$_GET['emp_id']."', changeDeptAppOn = '$entry_date',
            designation = '".$input["change_designation"]."' , change_designation = '".$input["Olddesignation"]."', change_department = '".$input["Olddepartment"]."'  where emp_id = '".$input["emp_id123"]."'";
            
         }else{
            $sql = "UPDATE employee set status = 'Active' , change_dept_status = 'NO',  changeDeptAppBy = '".$_GET['emp_id']."', changeDeptAppOn = '$entry_date' where emp_id = '".$input["emp_id123"]."'"; 
         }
         
         
  
             if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            } 
        
    }
 
    else if ($_GET["type"] == "getdeptChangeemp") {
        
        $output = Array();
         
        $sql = "SELECT id,plant_id,emp_id,firstname,middlename,lastname,department,designation,status,change_department,change_designation,change_description,changeDeptBy,changeDeptOn,changeDeptAppBy,changeDeptAppOn FROM employee WHERE 
        change_dept_status = 'YES' and plant_id = '".$_GET["plant_id"]."' order by changeDeptOn DESC";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
 
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
     
    }
     else if ($_GET["type"] == "saveLanguage") {
       echo $sql="select * from salary_types where language='".$input["language"]."' and plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
                echo "{\"status\":\"language Already Exists. Duplicates Not Allowed!\"}";
        }else{
            $output = Array();
            $sql = "INSERT INTO salary_types(plant_id, payroll_type,entry_by) VALUES 
            ('".$_GET["plant_id"]."',  '".$input["language_type"]."',  '".$_GET["emp_id"]."')";
             if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
    }
     else if ($_GET["type"] == "getlanguage") {
        $output = Array();
        $sql = "SELECT * FROM language_type order by 2 ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "get_importantDoc") {
        $output = Array();
        $sql = "SELECT * FROM important_document order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getUploadedDocByEmpId") {
        $output = Array();
        $empIdEsc = $conn->real_escape_string(trim((string)($_GET["emp_idForDoc"] ?? '')));
        $plantIdEsc = $conn->real_escape_string((string)($_GET["plant_id"] ?? ''));
        $sql = "SELECT * FROM emp_document WHERE TRIM(emp_id)='".$empIdEsc."' AND (plant_id='".$plantIdEsc."' OR plant_id='0' OR plant_id='' OR plant_id IS NULL)";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = medicap_normalize_emp_document_row($row);
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "add_doc") {
        header('Content-Type: application/json; charset=UTF-8');
        $input = $_POST;
        $plant_id = isset($_GET["plant_id"]) ? trim((string)$_GET["plant_id"]) : '';
        $emp_id = isset($input["emp_id"]) ? trim((string)$input["emp_id"]) : '';
        $document_name = isset($input["documentName"]) ? trim((string)$input["documentName"]) : '';

        if ($emp_id === '' || $document_name === '') {
            echo json_encode(array("status" => "Employee Code and Document Name are required"));
            exit;
        }
        if (!isset($_FILES["doc"]) || $_FILES["doc"]["error"] !== UPLOAD_ERR_OK) {
            echo json_encode(array("status" => "Please select a valid file to upload"));
            exit;
        }

        $allowed_ext = array("pdf", "jpg", "jpeg", "png", "doc", "docx");
        $original_name = basename($_FILES["doc"]["name"]);
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext, true)) {
            echo json_encode(array("status" => "Invalid file type"));
            exit;
        }

        $safe_doc_name = preg_replace("/[^a-zA-Z0-9_\-]/", "_", $document_name);
        $file_name = "emp_" . preg_replace("/[^a-zA-Z0-9_\-]/", "_", $emp_id) . "_" . $safe_doc_name . "_" . time() . "." . $file_ext;

        $upload_dirs = array(
            __DIR__ . "/../../../upload/employee/",
            __DIR__ . "/../upload/employee/",
        );
        $saved = false;
        $saved_name = '';
        foreach ($upload_dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (is_dir($dir) && is_writable($dir) && move_uploaded_file($_FILES["doc"]["tmp_name"], $dir . $file_name)) {
                $saved = true;
                $saved_name = $file_name;
                break;
            }
        }
        if (!$saved) {
            echo json_encode(array("status" => "File upload failed. Check upload/employee folder permissions."));
            exit;
        }

        $colMap = array();
        $colRes = $conn->query("SHOW COLUMNS FROM emp_document");
        if ($colRes) {
            while ($c = $colRes->fetch_assoc()) {
                $colMap[strtolower($c['Field'])] = $c['Field'];
            }
        }
        $plantCol = isset($colMap['plant_id']) ? $colMap['plant_id'] : 'plant_id';
        $empCol = isset($colMap['emp_id']) ? $colMap['emp_id'] : 'emp_id';
        $nameCol = isset($colMap['documentname']) ? $colMap['documentname'] : (isset($colMap['document_name']) ? $colMap['document_name'] : 'documentName');
        $fileCol = isset($colMap['filename']) ? $colMap['filename'] : (isset($colMap['file_name']) ? $colMap['file_name'] : 'fileName');

        $sql = "INSERT INTO emp_document (`".$plantCol."`, `".$empCol."`, `".$nameCol."`, `".$fileCol."`) VALUES ('".
            $conn->real_escape_string($plant_id)."','".
            $conn->real_escape_string($emp_id)."','".
            $conn->real_escape_string($document_name)."','".
            $conn->real_escape_string($saved_name)."')";

        if ($conn->query($sql)) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => $conn->error));
        }
    }
     
     
      else if ($_GET["type"] == "important_doc") {
                 $input = $_POST;
                 $d_name = $input["d_name"];
                  $random_id = bin2hex(random_bytes(3));  
                 $unique_id = substr($random_id, 0, 5);
                 
                 	if(isset($_FILES["doc"])) {
            $file_tmp =$_FILES['doc']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['doc']['name'])));
            $file_name = $d_name.$unique_id."doc.".$file_ext;
            $doc = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/important_document/".$file_name);
        }

           $sql = "INSERT INTO important_document(d_name, d_type, other_doc, d_description,effective_date,validity,doc) 
         VALUES ('".$input["d_name"]."','".$input["d_type"]."','".$input["other_doc"]."','".$input["d_description"]."',
         '".$input["effective_date"]."','".$input["validity"]."','$doc')";
       	    
       	    if($conn->query($sql)){    
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
        	
     }
     
   
     else if($_GET["type"]=="saveEmployee") {
        header('Content-Type: application/json; charset=UTF-8');

        $input = $_POST;
        $plant_id = isset($_GET['plant_id']) ? (int) $_GET['plant_id'] : 0;

        $saveFieldDefaults = array(
            'emp_id' => '', 'employee_type' => '', 'emp_work_type' => '', 'firstname' => '', 'middlename' => '',
            'lastname' => '', 'contact_no' => '', 'emp_email' => '', 'department' => '', 'designation' => '',
            'operator_category' => '', 'joining_status' => '', 'trainee_period' => '', 'probation_period' => '',
            'gender' => '', 'birthdate' => '', 'joining_date' => '', 'emp_level' => '', 'adhar' => '', 'pan' => '',
            'qualification' => '', 'experience' => '', 'nationality' => '', 'marital_status' => '', 'blood_group' => '',
            'isinduction' => '', 'epf_app' => 'No', 'pf_no' => '', 'esic_app' => 'No', 'esic_no' => '',
            'referenceName' => '', 'emergency_contact_name' => '', 'emergency_contact_relation' => '',
            'emergency_contact_email' => '', 'doctor_contact_no' => '',
            'permanent_flat' => '', 'permanent_country' => '', 'permanent_state' => '', 'permanent_city' => '',
            'permanent_pincode' => '', 'tempflat_no' => '', 'temp_country' => '', 'temp_state' => '',
            'temp_city' => '', 'temp_pincode' => '', 'acc_no' => '', 'bank_name' => '', 'branch_name' => '',
            'ifsc_neft' => '', 'acc_type' => '', 'academics' => '[]', 'languages' => '[]', 'employeement' => '[]',
            'handicap' => ''
        );
        foreach ($saveFieldDefaults as $key => $defaultVal) {
            $raw = isset($input[$key]) ? $input[$key] : $defaultVal;
            if (is_array($raw)) {
                $raw = $defaultVal;
            }
            $input[$key] = trim((string) $raw);
        }

        if ($input['emp_id'] === '') {
            echo json_encode(array('status' => 'Employee Code is required'));
            exit;
        }

        $empIdEsc = $conn->real_escape_string($input['emp_id']);
        $sql = "SELECT 1 FROM employee WHERE emp_id='{$empIdEsc}' AND plant_id='{$plant_id}'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo json_encode(array('status' => 'Employee Code Already Exists. Duplicate Values are not allowed'));
            exit;
        }

        $photo = 'null';
        if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
            $allowedExt = array('jpg', 'jpeg', 'png', 'gif');
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExt)) {
                echo json_encode(array('status' => 'Invalid file type. Allowed: jpg, jpeg, png, gif'));
                exit;
            }

            $fileName = $input['emp_id'] . '_' . $plant_id . '_photo.' . $fileExt;
            $targetPath = '../../../upload/employee/' . basename($fileName);
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                echo json_encode(array('status' => 'File upload failed'));
                exit;
            }
            $photo = $conn->real_escape_string($fileName);
        }

        $entry_by = isset($_GET['emp_id']) ? $_GET['emp_id'] : '';

        $rowData = array(
            'plant_id' => isset($_GET['plant_id']) ? $_GET['plant_id'] : (string) $plant_id,
            'emp_id' => $input['emp_id'],
            'employee_type' => $input['employee_type'],
            'emp_work_type' => $input['emp_work_type'],
            'firstname' => $input['firstname'],
            'middlename' => $input['middlename'],
            'lastname' => $input['lastname'],
            'contact_no' => $input['contact_no'],
            'emp_email' => $input['emp_email'],
            'department' => $input['department'],
            'designation' => $input['designation'],
            'operator_category' => $input['operator_category'],
            'joining_status' => $input['joining_status'],
            'trainee_period' => $input['trainee_period'],
            'probation_period' => $input['probation_period'],
            'gender' => $input['gender'],
            'birthdate' => $input['birthdate'],
            'joining_date' => $input['joining_date'],
            'emp_level' => $input['emp_level'],
            'adhar' => $input['adhar'],
            'pan' => $input['pan'],
            'qualification' => $input['qualification'],
            'experience' => $input['experience'],
            'nationality' => $input['nationality'],
            'marital_status' => $input['marital_status'],
            'blood_group' => $input['blood_group'],
            'isinduction' => $input['isinduction'],
            'epf_app' => $input['epf_app'],
            'pf_no' => $input['pf_no'],
            'esic_app' => $input['esic_app'],
            'esic_no' => $input['esic_no'],
            'photo' => $photo,
            'referenceName' => $input['referenceName'],
            'emergency_contact_name' => $input['emergency_contact_name'],
            'emergency_contact_relation' => $input['emergency_contact_relation'],
            'emergency_contact_email' => $input['emergency_contact_email'],
            'doctor_contact_no' => $input['doctor_contact_no'],
            'permanent_flat' => $input['permanent_flat'],
            'permanent_country' => $input['permanent_country'],
            'permanent_state' => $input['permanent_state'],
            'permanent_city' => $input['permanent_city'],
            'permanent_pincode' => $input['permanent_pincode'],
            'tempflat_no' => $input['tempflat_no'],
            'temp_country' => $input['temp_country'],
            'temp_state' => $input['temp_state'],
            'temp_city' => $input['temp_city'],
            'temp_pincode' => $input['temp_pincode'],
            'acc_no' => $input['acc_no'],
            'bank_name' => $input['bank_name'],
            'branch_name' => $input['branch_name'],
            'ifsc_neft' => $input['ifsc_neft'],
            'acc_type' => $input['acc_type'],
            'academics' => $input['academics'],
            'languages' => $input['languages'],
            'employeement' => $input['employeement'],
            'entry_by' => $entry_by,
            'entry_date' => $entry_date,
            'handicap' => $input['handicap'],
        );

        $existingCols = array();
        $colMaxLen = array();
        $colCheck = $conn->query('SHOW COLUMNS FROM `employee`');
        if ($colCheck) {
            while ($colRow = $colCheck->fetch_assoc()) {
                $existingCols[$colRow['Field']] = true;
                if (preg_match('/^(?:var)?char\((\d+)\)/i', $colRow['Type'], $m)) {
                    $colMaxLen[$colRow['Field']] = (int) $m[1];
                }
            }
        }

        $insertCols = array();
        $insertVals = array();
        foreach ($rowData as $col => $val) {
            if (!isset($existingCols[$col])) {
                continue;
            }
            $strVal = (string) $val;
            if (isset($colMaxLen[$col]) && strlen($strVal) > $colMaxLen[$col]) {
                $strVal = substr($strVal, 0, $colMaxLen[$col]);
            }
            $insertCols[] = '`' . $col . '`';
            $insertVals[] = "'" . $conn->real_escape_string($strVal) . "'";
        }

        if (count($insertCols) === 0) {
            echo json_encode(array('status' => 'Employee table columns could not be read'));
            exit;
        }

        $sql = 'INSERT INTO `employee` (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';

        if ($conn->query($sql)) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => $conn->error));
        }

    }
    
     else if($_GET["type"]=="saveHoUser") {
         

        $input = $_POST;
        $plant_id = isset($_GET['plant_id']) ? (int) $_GET['plant_id'] : 0;
         
        // Escape all input to prevent SQL injection
        foreach ($input as $key => $value) {
            $input[$key] = $conn->real_escape_string(trim($value));
        }
        
         
        // Check duplicate emp_id
        $sql = "SELECT 1 FROM employee WHERE emp_id='{$input["emp_id"]}' AND plant_id='{$plant_id}'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo json_encode(["status" => "Employee Code Already Exists. Duplicate Values are not allowed"]);
            exit;
        }
        
     
     
     	$sql = "INSERT INTO `employee` (plant_id ,emp_id, employee_type, firstname, middlename, lastname, contact_no, emp_email, department, 
     	designation, operator_category, gender, birthdate,entry_by,entry_date) VALUES('".$_GET["plant_id"]."','".$input["emp_id"]."',
     	'".$input["employee_type"]."','".$input["firstname"]."','".$input["middlename"]."','".$input["lastname"]."','".$input["contact_no"]."',
     	'".$input["emp_email"]."','".$input["department"]."','".$input["designation"]."','Staff','Confirmed', '".$input["gender"]."','".$input["birthdate"]."', '".$_GET["emp_id"]."', '$entry_date')";
     	
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        	 
        
    }
 
    else if($_GET["type"]=="savedevloper") {
      
        $sql = "SELECT COUNT(id) as   max_id FROM employee";
        $result = $conn->query($sql);
        
        $nextId = 1; 
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $nextId = intval($row['max_id']) + 1;
        }
        
        $newEmpId = 'ART' . str_pad($nextId, 5, '0', STR_PAD_LEFT);  
      
          $sql = "INSERT INTO employee (plant_id,employee_type,emp_id,firstname,lastname,contact_no,emp_email,gender,status,password,ISNEW)
         VALUES( '".$_GET["plant_id"]."','Artwork Devloper','$newEmpId',
         '".$input["firstname"]."','".$input["lastname"]."','".$input["contact_no"]."','".$input["emp_email"]."',
         '".$input["gender"]."','active','devloper123','NO')";
        	     
    	if($conn->query($sql)){
    	    
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        	 
    }
    
    else if($_GET["type"]=="updatePhoto") {
         

        $input = $_POST;
        $plant_id = isset($_GET['plant_id']) ? (int) $_GET['plant_id'] : 0;
       
        // Handle photo upload
        $photo = null;
        if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
            $allowedExt = ["jpg", "jpeg", "png", "gif"];
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        
            if (!in_array($fileExt, $allowedExt)) {
                echo json_encode(["status" => "Invalid file type. Allowed: jpg, jpeg, png, gif"]);
                exit;
            }
            
            $fileName = $input["emp_id"] . "_" . $plant_id . "_photo." . $fileExt;
            $targetPath = "../../../upload/employee/" . basename($fileName);
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                echo json_encode(["status" => "File upload failed"]);
                exit;
            }
            $photo = $conn->real_escape_string($fileName);
        }
     
     
         	$sql = "Update employee set photo = '$photo' WHERE emp_id = '".$input["emp_id"]."' and plant_id ='".$_GET["plant_id"]."' ";
         	
        	if($conn->query($sql)){
        		echo "{\"status\":\"success\"}";
        	} else {
        		echo "{\"status\":\"".$conn->error."\"}";
        	}
        	 
        
    }
    else if ($_GET["type"] == "updateManditoryDetails") {
        
        $sql= " Update employee set employee_type = '".$input["employee_type"]."', firstname = '".$input["firstname"]."', middlename = '".$input["middlename"]."', 
        lastname = '".$input["lastname"]."', contact_no = '".$input["contact_no"]."', emp_email = '".$input["emp_email"]."', 
        department = '".$input["department"]."', designation = '".$input["designation"]."', operator_category = '".$input["operator_category"]."', 
        joining_status = '".$input["joining_status"]."', trainee_period = '".$input["trainee_period"]."', 
        probation_period = '".$input["probation_period"]."', gender = '".$input["gender"]."', birthdate = '".$input["birthdate"]."', 
        joining_date = '".$input["joining_date"]."', emp_level = '".$input["emp_level"]."', adhar = '".$input["adhar"]."', handicap = '".$input["handicap"]."'
        WHERE emp_id = '".$input["emp_id"]."' and plant_id ='".$_GET["plant_id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
    else if ($_GET["type"] == "updateOtherDetails") {
        
        $sql= " Update employee set pan = '".$input["pan"]."',  qualification = '".$input["qualification"]."',  experience = '".$input["experience"]."',  
        nationality = '".$input["nationality"]."',  marital_status = '".$input["marital_status"]."',  blood_group = '".$input["blood_group"]."',  
        isinduction = '".$input["isinduction"]."',  epf_app = '".$input["epf_app"]."',  pf_no = '".$input["pf_no"]."',  
        esic_app = '".$input["esic_app"]."',  esic_no = '".$input["esic_no"]."',  referenceName = '".$input["referenceName"]."'
        WHERE emp_id = '".$input["emp_id"]."' and plant_id ='".$_GET["plant_id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
    else if ($_GET["type"] == "updateAddress") {
        
        $sql= " Update employee set  permanent_flat = '".$input["permanent_flat"]."',  permanent_country = '".$input["permanent_country"]."',  permanent_state = '".$input["permanent_state"]."',  
        permanent_state = '".$input["permanent_state"]."',  permanent_city = '".$input["permanent_city"]."',  permanent_pincode = '".$input["permanent_pincode"]."',  
        tempflat_no = '".$input["tempflat_no"]."',  temp_country = '".$input["temp_country"]."',  temp_state = '".$input["temp_state"]."',  
        temp_state = '".$input["temp_state"]."',  temp_city = '".$input["temp_city"]."',  temp_pincode = '".$input["temp_pincode"]."'
        WHERE emp_id = '".$input["emp_id"]."' and plant_id ='".$_GET["plant_id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
         
    }
    else if ($_GET["type"] == "updateBankDetails") {
        
        $sql= " Update employee set  acc_no = '".$input["acc_no"]."', bank_name = '".$input["bank_name"]."', 
        branch_name = '".$input["branch_name"]."', ifsc_neft = '".$input["ifsc_neft"]."', acc_type = '".$input["acc_type"]."'
        WHERE emp_id = '".$input["emp_id"]."' and plant_id ='".$_GET["plant_id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
    else if ($_GET["type"] == "updateAcademicsDetails") {
        
        $input = $_POST;
        
        $sql= " Update employee set  academics = '".$input["academics"]."' WHERE emp_id = '".$input["emp_id"]."' and plant_id ='".$_GET["plant_id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
    else if ($_GET["type"] == "updateLanguages") {
        
        $input = $_POST;
        
        $sql= " Update employee set  languages = '".$input["languages"]."' WHERE emp_id = '".$input["emp_id"]."' and plant_id ='".$_GET["plant_id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
    else if ($_GET["type"] == "updateEmployeement") {
        
        $input = $_POST;
        
        $sql= " Update employee set  employeement = '".$input["employeement"]."' WHERE emp_id = '".$input["emp_id"]."' and plant_id ='".$_GET["plant_id"]."' ";
                  
    	if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
    
    
    
    
    
    else if ($_GET["type"] == "getPendingEmployees") {
       $output = Array();
         
        $sql = "SELECT * FROM employee WHERE status ='Pending' and plant_id = '".$_GET["plant_id"]."' order by id desc ";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output2 = Array();
                $empIdEsc = $conn->real_escape_string(trim((string)$row["emp_id"]));
                $plantIdEsc = $conn->real_escape_string((string)$_GET["plant_id"]);
                $sql2 = "SELECT * FROM emp_document WHERE TRIM(emp_id)='".$empIdEsc."' AND (plant_id='".$plantIdEsc."' OR plant_id='0' OR plant_id='' OR plant_id IS NULL)";
                $result2 = $conn->query($sql2);
                if ($result2 && $result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2[] = medicap_normalize_emp_document_row($row2);
                    }
                }
                    
                $row["documents"] = $output2;
                    
                $row["academics"] = json_decode($row["academics"]);
                $row["languages"] = json_decode($row["languages"]);
                $row["employeement"] = json_decode($row["employeement"]);
                
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "updateEmployee") {
        
        $sql = "UPDATE employee SET status = '".$_GET["status"]."',  approve_by = '".$_GET["emp_id"]."', 
        approve_date = '$entry_date' WHERE id='".$_GET["id"]."'";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if ($_GET["type"] == "getartist") {
        $output = Array();
        $sql = "SELECT id,emp_id,firstname,lastname,employee_type,emp_email,contact_no,gender FROM employee WHERE employee_type='Artwork Devloper' AND  plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPlants") {
        $output = Array();
        $sql = "SELECT * FROM `plant` where entry_by != 'MAIN'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getCurrentEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "getResignedEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='resign'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } else if ($_GET["type"] == "getTechnicalEmployees") {
        $output = Array();
         $sql = "SELECT * FROM employee WHERE employee_type='Technical' AND  plant_id='".$_GET["plant_id"]."'  ";
       // $sql = "SELECT * FROM employee WHERE status='resign'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } else if ($_GET["type"] == "delEmployee") {
        $sql = "SELECT * FROM employee WHERE emp_id='".$_GET["emp_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $passcode = $row["emp_password"];
                $password = dec_enc("decrypt",$passcode);
                if($password == $input["password"]) {
                    $sql = "UPDATE employee SET status='delete' WHERE emp_id='".$_GET["emp_code"]."'";
                    if ($conn->query($sql)) {
                        echo "{\"status\":\"success\"}";
                    } else {
                        echo "{\"status\":\"".$conn->error."\"}";
                    }
                } else {
                    echo "{\"status\":\"failed\"}";
                }
            }
        }
    }
    
    else if ($_GET["type"] == "getEmployees") {
        $output = Array();
         
      $sql = "SELECT * FROM employee WHERE emp_id='emp_code'";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $row["familyList"] = json_decode($row["familyList"]);
                $row["result"] = json_decode($row["result"]);
                $row["document_list"] = json_decode($row["document_list"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getEmployeesbydept") {
        $output = Array();
         
       $sql = "SELECT id,plant_id,emp_id,firstname,middlename,lastname,department,joining_date,designation,operator_category,pan,status FROM employee WHERE department =  '".$_GET["department_name"]."' 
      AND status ='Active' and plant_id = '".$_GET["plant_id"]."' order by firstname ASC";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "claculateAnnexureData") {
        
        
            $annexureEmpId = isset($_GET["annexureEmp_id"]) ? $_GET["annexureEmp_id"] : null;
            $netPayableMonthly = isset($_GET["netPayableMOnthly"]) ? floatval($_GET["netPayableMOnthly"]) : null;
            
            // Validate required parameters
            if (!$annexureEmpId || !$netPayableMonthly || $netPayableMonthly <= 0) {
                echo json_encode([
                    "error" => true,
                    "message" => "Invalid or missing parameters. Provide annexureEmp_id and netPayableMOnthly correctly."
                ]);
                exit;
            }
            
            // Calculations
            $basicDAMonthly = $netPayableMonthly * 0.6;
            $basicDAAnnually = $basicDAMonthly * 12;
            
            $hraMonthly = $basicDAMonthly * 0.4;
            $hraAnnually = $hraMonthly * 12;
            
            $employerSharePfMonthly = $basicDAMonthly * 0.13;
            $employerSharePfAnnually = $employerSharePfMonthly * 12;
            
            $gratuityMonthly = $basicDAMonthly * 0.0481;
            $gratuityAnnually = $gratuityMonthly * 12;
            
            $exGratiaMonthly = $basicDAMonthly * 0.0833;
            $exGratiaAnnually = $exGratiaMonthly * 12;
            
            $employeePfMonthly = $basicDAMonthly * 0.12;
            $employeePfAnnually = $employeePfMonthly * 12;
            
            
            $conveyAllowanceMonthly = 1600;
            $conveyAllowanceAnnually = $conveyAllowanceMonthly * 12;
            
            $foodAllowanceMonthly = 1280;
            $foodAllowanceAnnually = $foodAllowanceMonthly * 12;
            
            $dressAllowanceMonthly = 2200;
            $dressAllowanceAnnually = $dressAllowanceMonthly * 12;
            
            $eduAllowanceMonthly = 0;
            $eduAllowanceAnnually = $eduAllowanceMonthly * 12;

            $medicalAllowanceMonthly = 0;
            $medicalAllowanceAnnually = $medicalAllowanceMonthly * 12;

            $othAllowanceMonthly = 0;
            $othAllowanceAnnually = $othAllowanceMonthly * 12;

            $monthlyBonusMonthly = 0;
            $monthlyBonusAnnually = $monthlyBonusMonthly * 12;
            
            $bonusMonthly = 0;
            $bonusAnnually = $bonusMonthly * 12;

            $latMonthly = 0;
            $latAnnually = $latMonthly * 12;

            $performanceBonusMonthly = 0;
            $performanceBonusAnnually = $performanceBonusMonthly * 12;
            
             
            
            if ($netPayableMonthly <= 21000) {
                $empEsicMonthly  = $netPayableMonthly * 0.0075; // 0.75%
                $empEsicAnnually = $empEsicMonthly * 12;
                $employerShareEsicMonthly  = $netPayableMonthly * 0.0325; // 3.25%
                $employerShareEsicAnnually = $employerShareEsicMonthly * 12;
            } else {
                $empEsicMonthly = 0;
                $empEsicAnnually = 0;
                $employerShareEsicMonthly = 0;
                $employerShareEsicAnnually = 0;
            }
            
            
            $grossSalAMonthly = round($basicDAMonthly) + round($hraMonthly) + round($conveyAllowanceMonthly) + round($eduAllowanceMonthly) +
                                round($foodAllowanceMonthly) + round($dressAllowanceMonthly) + round($medicalAllowanceMonthly) + round($monthlyBonusMonthly) + round($othAllowanceMonthly) ;
            $grossSalAAnnually = $grossSalAMonthly * 12;
            
            $totalRetrialMonthly = round($employerSharePfMonthly) + round($employerShareEsicMonthly) + round($gratuityMonthly) + round($exGratiaMonthly) +
                               + round($bonusMonthly) + round($latMonthly) ;
                               
            $totalRetrialAnnually = $totalRetrialMonthly * 12;
            
             
            $netPayMonthly = $grossSalAMonthly + $performanceBonusMonthly - ( $employeePfMonthly + $empEsicMonthly );
            $netPayAnuually = $netPayMonthly * 12;
            
            $ctcMonthly = $grossSalAMonthly + $totalRetrialMonthly + $performanceBonusMonthly;
            $ctcAnuually = $ctcMonthly * 12;
       
            
            // Build response object with single values (no nested arrays)
            $response = [
                "employeeId" => $annexureEmpId,              "netPayableMonthly" => round($netPayableMonthly),
                "basicDAMonthly" => round($basicDAMonthly),
                "basicDAAnnually" => round($basicDAAnnually),
                "hraMonthly" => round($hraMonthly),
                "hraAnnually" => round($hraAnnually),
                "employerSharePfMonthly" => round($employerSharePfMonthly),
                "employerSharePfAnnually" => round($employerSharePfAnnually),
                "gratuityMonthly" => round($gratuityMonthly),
                "gratuityAnnually" => round($gratuityAnnually),
                "exGratiaMonthly" => round($exGratiaMonthly),
                "exGratiaAnnually" => round($exGratiaAnnually),
                "employeePfMonthly" => round($employeePfMonthly),
                "employeePfAnnually" => round($employeePfAnnually),
                "conveyAllowanceMonthly" => round($conveyAllowanceMonthly),
                "conveyAllowanceAnnually" => round($conveyAllowanceAnnually),
                "foodAllowanceMonthly" => round($foodAllowanceMonthly),
                "foodAllowanceAnnually" => round($foodAllowanceAnnually),
                "dressAllowanceMonthly" => round($dressAllowanceMonthly),
                "dressAllowanceAnnually" => round($dressAllowanceAnnually),
                "empEsicMonthly" => round($empEsicMonthly),
                "empEsicAnnually" => round($empEsicAnnually),
                "employerShareEsicMonthly" => round($employerShareEsicMonthly),
                "employerShareEsicAnnually" => round($employerShareEsicAnnually),
                "eduAllowanceMonthly" => round($eduAllowanceMonthly),
                "eduAllowanceAnnually" => round($eduAllowanceAnnually),
                "medicalAllowanceMonthly" => round($medicalAllowanceMonthly),
                "medicalAllowanceAnnually" => round($medicalAllowanceAnnually),
                "othAllowanceMonthly" => round($othAllowanceMonthly),
                "othAllowanceAnnually" => round($othAllowanceAnnually),
                "monthlyBonusMonthly" => round($monthlyBonusMonthly),
                "monthlyBonusAnnually" => round($monthlyBonusAnnually),
                "bonusMonthly" => round($bonusMonthly),
                "bonusAnnually" => round($bonusAnnually),
                "latMonthly" => round($latMonthly),
                "latAnnually" => round($latAnnually),
                "performanceBonusMonthly" => round($performanceBonusMonthly),
                "performanceBonusAnnually" => round($performanceBonusAnnually),
                "grossSalAMonthly" => round($grossSalAMonthly),
                "grossSalAAnnually" => round($grossSalAAnnually),
                "totalRetrialMonthly" => round($totalRetrialMonthly),
                "totalRetrialAnnually" => round($totalRetrialAnnually),
                "netPayAnuually" => round($netPayAnuually),
                "netPayMonthly" => round($netPayMonthly),
                "ctcAnuually" => round($ctcAnuually),
                "ctcMonthly" => round($ctcMonthly),
            ];
            
            // Return JSON
            echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    else if ($_GET["type"] == "claculateAnnexureDataOnBaicDA") {
        
        
            $annexureEmpId = isset($_GET["annexureEmp_id"]) ? $_GET["annexureEmp_id"] : null;
            $netPayableMonthly = isset($_GET["netPayableMOnthly"]) ? floatval($_GET["netPayableMOnthly"]) : null;
            $basicDAMonthly = isset($_GET["basicDAMonthly"]) ? floatval($_GET["basicDAMonthly"]) : null;
            
            $conveyAllowanceMonthly = isset($_GET["conveyAllowanceMonthly"]) ? floatval($_GET["conveyAllowanceMonthly"]) : null;
            $eduAllowanceMonthly = isset($_GET["eduAllowanceMonthly"]) ? floatval($_GET["eduAllowanceMonthly"]) : null;
            $foodAllowanceMonthly = isset($_GET["foodAllowanceMonthly"]) ? floatval($_GET["foodAllowanceMonthly"]) : null;
            $dressAllowanceMonthly = isset($_GET["dressAllowanceMonthly"]) ? floatval($_GET["dressAllowanceMonthly"]) : null;
            $medicalAllowanceMonthly = isset($_GET["medicalAllowanceMonthly"]) ? floatval($_GET["medicalAllowanceMonthly"]) : null;
            $othAllowanceMonthly = isset($_GET["othAllowanceMonthly"]) ? floatval($_GET["othAllowanceMonthly"]) : null;
            $monthlyBonusMonthly = isset($_GET["monthlyBonusMonthly"]) ? floatval($_GET["monthlyBonusMonthly"]) : null;
            $bonusAnnually = isset($_GET["bonusAnnually"]) ? floatval($_GET["bonusAnnually"]) : null;
            $latAnnually = isset($_GET["latAnnually"]) ? floatval($_GET["latAnnually"]) : null;
            $performanceBonusMonthly = isset($_GET["performanceBonusMonthly"]) ? floatval($_GET["performanceBonusMonthly"]) : null;
            
            // Validate required parameters
            if (!$annexureEmpId || !$netPayableMonthly || $netPayableMonthly <= 0) {
                echo json_encode([
                    "error" => true,
                    "message" => "Invalid or missing parameters. Provide annexureEmp_id and netPayableMOnthly correctly."
                ]);
                exit;
            }
            
            // Calculations
            $basicDAAnnually = $basicDAMonthly * 12;
            
            $hraMonthly = $basicDAMonthly * 0.4;
            $hraAnnually = $hraMonthly * 12;
            
            $employerSharePfMonthly = $basicDAMonthly * 0.13;
            $employerSharePfAnnually = $employerSharePfMonthly * 12;
            
            $gratuityMonthly = $basicDAMonthly * 0.0481;
            $gratuityAnnually = $gratuityMonthly * 12;
            
            $exGratiaMonthly = $basicDAMonthly * 0.0833;
            $exGratiaAnnually = $exGratiaMonthly * 12;
            
            $employeePfMonthly = $basicDAMonthly * 0.12;
            $employeePfAnnually = $employeePfMonthly * 12;
            
            
             $conveyAllowanceAnnually = $conveyAllowanceMonthly * 12;
            
             $foodAllowanceAnnually = $foodAllowanceMonthly * 12;
            
             $dressAllowanceAnnually = $dressAllowanceMonthly * 12;
            
             $eduAllowanceAnnually = $eduAllowanceMonthly * 12;

            $medicalAllowanceAnnually = $medicalAllowanceMonthly * 12;

            $othAllowanceAnnually = $othAllowanceMonthly * 12;

            $monthlyBonusAnnually = $monthlyBonusMonthly * 12;
            
            $bonusMonthly = 0;
          
            $latMonthly = 0;
            
            $performanceBonusAnnually = 0;
            
            
            if ($netPayableMonthly <= 21000) {
                $empEsicMonthly  = $netPayableMonthly * 0.0075; // 0.75%
                $empEsicAnnually = $empEsicMonthly * 12;
                $employerShareEsicMonthly  = $netPayableMonthly * 0.0325; // 3.25%
                $employerShareEsicAnnually = $employerShareEsicMonthly * 12;
            } else {
                $empEsicMonthly = 0;
                $empEsicAnnually = 0;
                $employerShareEsicMonthly = 0;
                $employerShareEsicAnnually = 0;
            }
            
            
            $grossSalAMonthly = round($basicDAMonthly) + round($hraMonthly) + round($conveyAllowanceMonthly) + round($eduAllowanceMonthly) +
                                round($foodAllowanceMonthly) + round($dressAllowanceMonthly) + round($medicalAllowanceMonthly) + round($monthlyBonusMonthly) + round($othAllowanceMonthly) ;
            $grossSalAAnnually = $grossSalAMonthly * 12;
            
            $totalRetrialMonthly = round($employerSharePfMonthly) + round($employerShareEsicMonthly) + round($gratuityMonthly) + round($exGratiaMonthly) +
                               + round($bonusMonthly) + round($latMonthly) ;
                               
            $totalRetrialAnnually = $totalRetrialMonthly * 12;
            
             
            $netPayMonthly = $grossSalAMonthly + $performanceBonusMonthly - ( $employeePfMonthly + $empEsicMonthly );
            $netPayAnuually = $netPayMonthly * 12;
            
            $ctcMonthly = $grossSalAMonthly + $totalRetrialMonthly + $performanceBonusMonthly;
            $ctcAnuually = $ctcMonthly * 12;
       
            
            // Build response object with single values (no nested arrays)
            $response = [
                "employeeId" => $annexureEmpId,
                "PayableMonthly" => round($netPayableMonthly),
                "basicDAMonthly" => round($basicDAMonthly),
                "basicDAAnnually" => round($basicDAAnnually),
                "hraMonthly" => round($hraMonthly),
                "hraAnnually" => round($hraAnnually),
                "employerSharePfMonthly" => round($employerSharePfMonthly),
                "employerSharePfAnnually" => round($employerSharePfAnnually),
                "gratuityMonthly" => round($gratuityMonthly),
                "gratuityAnnually" => round($gratuityAnnually),
                "exGratiaMonthly" => round($exGratiaMonthly),
                "exGratiaAnnually" => round($exGratiaAnnually),
                "employeePfMonthly" => round($employeePfMonthly),
                "employeePfAnnually" => round($employeePfAnnually),
                "conveyAllowanceMonthly" => round($conveyAllowanceMonthly),
                "conveyAllowanceAnnually" => round($conveyAllowanceAnnually),
                "foodAllowanceMonthly" => round($foodAllowanceMonthly),
                "foodAllowanceAnnually" => round($foodAllowanceAnnually),
                "dressAllowanceMonthly" => round($dressAllowanceMonthly),
                "dressAllowanceAnnually" => round($dressAllowanceAnnually),
                "empEsicMonthly" => round($empEsicMonthly),
                "empEsicAnnually" => round($empEsicAnnually),
                "employerShareEsicMonthly" => round($employerShareEsicMonthly),
                "employerShareEsicAnnually" => round($employerShareEsicAnnually),
                "eduAllowanceMonthly" => round($eduAllowanceMonthly),
                "eduAllowanceAnnually" => round($eduAllowanceAnnually),
                "medicalAllowanceMonthly" => round($medicalAllowanceMonthly),
                "medicalAllowanceAnnually" => round($medicalAllowanceAnnually),
                "othAllowanceMonthly" => round($othAllowanceMonthly),
                "othAllowanceAnnually" => round($othAllowanceAnnually),
                "monthlyBonusMonthly" => round($monthlyBonusMonthly),
                "monthlyBonusAnnually" => round($monthlyBonusAnnually),
                "bonusMonthly" => round($bonusMonthly),
                "bonusAnnually" => round($bonusAnnually),
                "latMonthly" => round($latMonthly),
                "latAnnually" => round($latAnnually),
                "performanceBonusMonthly" => round($performanceBonusMonthly),
                "performanceBonusAnnually" => round($performanceBonusAnnually),
                "grossSalAMonthly" => round($grossSalAMonthly),
                "grossSalAAnnually" => round($grossSalAAnnually),
                "totalRetrialMonthly" => round($totalRetrialMonthly),
                "totalRetrialAnnually" => round($totalRetrialAnnually),
                "netPayAnuually" => round($netPayAnuually),
                "netPayMonthly" => round($netPayMonthly),
                "ctcAnuually" => round($ctcAnuually),
                "ctcMonthly" => round($ctcMonthly),
            ];
            
            // Return JSON
            echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    
    else if ($_GET["type"] == "claculateCandidateAnnexureData") {
        
        
            $annexureEmpId = isset($_GET["annexureEmp_id"]) ? $_GET["annexureEmp_id"] : null;
            $netPayableMonthly = isset($_GET["netPayableMOnthly"]) ? floatval($_GET["netPayableMOnthly"]) : null;
            
            // Validate required parameters
            if (!$annexureEmpId || !$netPayableMonthly || $netPayableMonthly <= 0) {
                echo json_encode([
                    "error" => true,
                    "message" => "Invalid or missing parameters. Provide annexureEmp_id and netPayableMOnthly correctly."
                ]);
                exit;
            }
            
            // Calculations
            $basicDAMonthly = $netPayableMonthly * 0.6;
            $basicDAAnnually = $basicDAMonthly * 12;
            
            $hraMonthly = $basicDAMonthly * 0.4;
            $hraAnnually = $hraMonthly * 12;
            
            $employerSharePfMonthly = $basicDAMonthly * 0.13;
            $employerSharePfAnnually = $employerSharePfMonthly * 12;
            
            $gratuityMonthly = $basicDAMonthly * 0.0481;
            $gratuityAnnually = $gratuityMonthly * 12;
            
            $exGratiaMonthly = $basicDAMonthly * 0.0833;
            $exGratiaAnnually = $exGratiaMonthly * 12;
            
            $employeePfMonthly = $basicDAMonthly * 0.12;
            $employeePfAnnually = $employeePfMonthly * 12;
            
            
            $conveyAllowanceMonthly = 1600;
            $conveyAllowanceAnnually = $conveyAllowanceMonthly * 12;
            
            $foodAllowanceMonthly = 1280;
            $foodAllowanceAnnually = $foodAllowanceMonthly * 12;
            
            $dressAllowanceMonthly = 2200;
            $dressAllowanceAnnually = $dressAllowanceMonthly * 12;
            
            $eduAllowanceMonthly = 0;
            $eduAllowanceAnnually = $eduAllowanceMonthly * 12;

            $medicalAllowanceMonthly = 0;
            $medicalAllowanceAnnually = $medicalAllowanceMonthly * 12;

            $othAllowanceMonthly = 0;
            $othAllowanceAnnually = $othAllowanceMonthly * 12;

            $monthlyBonusMonthly = 0;
            $monthlyBonusAnnually = $monthlyBonusMonthly * 12;
            
            $bonusMonthly = 0;
            $bonusAnnually = $bonusMonthly * 12;

            $latMonthly = 0;
            $latAnnually = $latMonthly * 12;

            $performanceBonusMonthly = 0;
            $performanceBonusAnnually = $performanceBonusMonthly * 12;
            
             
            
            if ($netPayableMonthly <= 21000) {
                $empEsicMonthly  = $netPayableMonthly * 0.0075; // 0.75%
                $empEsicAnnually = $empEsicMonthly * 12;
                $employerShareEsicMonthly  = $netPayableMonthly * 0.0325; // 3.25%
                $employerShareEsicAnnually = $employerShareEsicMonthly * 12;
            } else {
                $empEsicMonthly = 0;
                $empEsicAnnually = 0;
                $employerShareEsicMonthly = 0;
                $employerShareEsicAnnually = 0;
            }
            
            
            $grossSalAMonthly = round($basicDAMonthly) + round($hraMonthly) + round($conveyAllowanceMonthly) + round($eduAllowanceMonthly) +
                                round($foodAllowanceMonthly) + round($dressAllowanceMonthly) + round($medicalAllowanceMonthly) + round($monthlyBonusMonthly) + round($othAllowanceMonthly) ;
            $grossSalAAnnually = $grossSalAMonthly * 12;
            
            $totalRetrialMonthly = round($employerSharePfMonthly) + round($employerShareEsicMonthly) + round($gratuityMonthly) + round($exGratiaMonthly) +
                               + round($bonusMonthly) + round($latMonthly) ;
                               
            $totalRetrialAnnually = $totalRetrialMonthly * 12;
            
             
            $netPayMonthly = $grossSalAMonthly + $performanceBonusMonthly - ( $employeePfMonthly + $empEsicMonthly );
            $netPayAnuually = $netPayMonthly * 12;
            
            $ctcMonthly = $grossSalAMonthly + $totalRetrialMonthly + $performanceBonusMonthly;
            $ctcAnuually = $ctcMonthly * 12;
       
            
            // Build response object with single values (no nested arrays)
            $response = [
                "employeeId" => $annexureEmpId,              "netPayableMonthly" => round($netPayableMonthly),
                "basicDAMonthly" => round($basicDAMonthly),
                "basicDAAnnually" => round($basicDAAnnually),
                "hraMonthly" => round($hraMonthly),
                "hraAnnually" => round($hraAnnually),
                "employerSharePfMonthly" => round($employerSharePfMonthly),
                "employerSharePfAnnually" => round($employerSharePfAnnually),
                "gratuityMonthly" => round($gratuityMonthly),
                "gratuityAnnually" => round($gratuityAnnually),
                "exGratiaMonthly" => round($exGratiaMonthly),
                "exGratiaAnnually" => round($exGratiaAnnually),
                "employeePfMonthly" => round($employeePfMonthly),
                "employeePfAnnually" => round($employeePfAnnually),
                "conveyAllowanceMonthly" => round($conveyAllowanceMonthly),
                "conveyAllowanceAnnually" => round($conveyAllowanceAnnually),
                "foodAllowanceMonthly" => round($foodAllowanceMonthly),
                "foodAllowanceAnnually" => round($foodAllowanceAnnually),
                "dressAllowanceMonthly" => round($dressAllowanceMonthly),
                "dressAllowanceAnnually" => round($dressAllowanceAnnually),
                "empEsicMonthly" => round($empEsicMonthly),
                "empEsicAnnually" => round($empEsicAnnually),
                "employerShareEsicMonthly" => round($employerShareEsicMonthly),
                "employerShareEsicAnnually" => round($employerShareEsicAnnually),
                "eduAllowanceMonthly" => round($eduAllowanceMonthly),
                "eduAllowanceAnnually" => round($eduAllowanceAnnually),
                "medicalAllowanceMonthly" => round($medicalAllowanceMonthly),
                "medicalAllowanceAnnually" => round($medicalAllowanceAnnually),
                "othAllowanceMonthly" => round($othAllowanceMonthly),
                "othAllowanceAnnually" => round($othAllowanceAnnually),
                "monthlyBonusMonthly" => round($monthlyBonusMonthly),
                "monthlyBonusAnnually" => round($monthlyBonusAnnually),
                "bonusMonthly" => round($bonusMonthly),
                "bonusAnnually" => round($bonusAnnually),
                "latMonthly" => round($latMonthly),
                "latAnnually" => round($latAnnually),
                "performanceBonusMonthly" => round($performanceBonusMonthly),
                "performanceBonusAnnually" => round($performanceBonusAnnually),
                "grossSalAMonthly" => round($grossSalAMonthly),
                "grossSalAAnnually" => round($grossSalAAnnually),
                "totalRetrialMonthly" => round($totalRetrialMonthly),
                "totalRetrialAnnually" => round($totalRetrialAnnually),
                "netPayAnuually" => round($netPayAnuually),
                "netPayMonthly" => round($netPayMonthly),
                "ctcAnuually" => round($ctcAnuually),
                "ctcMonthly" => round($ctcMonthly),
            ];
            
            // Return JSON
            echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    else if ($_GET["type"] == "claculateCandidateAnnexureDataOnBaicDA") {
        
        
            $annexureEmpId = isset($_GET["annexureEmp_id"]) ? $_GET["annexureEmp_id"] : null;
            $netPayableMonthly = isset($_GET["netPayableMOnthly"]) ? floatval($_GET["netPayableMOnthly"]) : null;
            $basicDAMonthly = isset($_GET["basicDAMonthly"]) ? floatval($_GET["basicDAMonthly"]) : null;
            
            $conveyAllowanceMonthly = isset($_GET["conveyAllowanceMonthly"]) ? floatval($_GET["conveyAllowanceMonthly"]) : null;
            $eduAllowanceMonthly = isset($_GET["eduAllowanceMonthly"]) ? floatval($_GET["eduAllowanceMonthly"]) : null;
            $foodAllowanceMonthly = isset($_GET["foodAllowanceMonthly"]) ? floatval($_GET["foodAllowanceMonthly"]) : null;
            $dressAllowanceMonthly = isset($_GET["dressAllowanceMonthly"]) ? floatval($_GET["dressAllowanceMonthly"]) : null;
            $medicalAllowanceMonthly = isset($_GET["medicalAllowanceMonthly"]) ? floatval($_GET["medicalAllowanceMonthly"]) : null;
            $othAllowanceMonthly = isset($_GET["othAllowanceMonthly"]) ? floatval($_GET["othAllowanceMonthly"]) : null;
            $monthlyBonusMonthly = isset($_GET["monthlyBonusMonthly"]) ? floatval($_GET["monthlyBonusMonthly"]) : null;
            $bonusAnnually = isset($_GET["bonusAnnually"]) ? floatval($_GET["bonusAnnually"]) : null;
            $latAnnually = isset($_GET["latAnnually"]) ? floatval($_GET["latAnnually"]) : null;
            $performanceBonusMonthly = isset($_GET["performanceBonusMonthly"]) ? floatval($_GET["performanceBonusMonthly"]) : null;
            
            // Validate required parameters
            if (!$annexureEmpId || !$netPayableMonthly || $netPayableMonthly <= 0) {
                echo json_encode([
                    "error" => true,
                    "message" => "Invalid or missing parameters. Provide annexureEmp_id and netPayableMOnthly correctly."
                ]);
                exit;
            }
            
            // Calculations
            $basicDAAnnually = $basicDAMonthly * 12;
            
            $hraMonthly = $basicDAMonthly * 0.4;
            $hraAnnually = $hraMonthly * 12;
            
            $employerSharePfMonthly = $basicDAMonthly * 0.13;
            $employerSharePfAnnually = $employerSharePfMonthly * 12;
            
            $gratuityMonthly = $basicDAMonthly * 0.0481;
            $gratuityAnnually = $gratuityMonthly * 12;
            
            $exGratiaMonthly = $basicDAMonthly * 0.0833;
            $exGratiaAnnually = $exGratiaMonthly * 12;
            
            $employeePfMonthly = $basicDAMonthly * 0.12;
            $employeePfAnnually = $employeePfMonthly * 12;
            
            
             $conveyAllowanceAnnually = $conveyAllowanceMonthly * 12;
            
             $foodAllowanceAnnually = $foodAllowanceMonthly * 12;
            
             $dressAllowanceAnnually = $dressAllowanceMonthly * 12;
            
             $eduAllowanceAnnually = $eduAllowanceMonthly * 12;

            $medicalAllowanceAnnually = $medicalAllowanceMonthly * 12;

            $othAllowanceAnnually = $othAllowanceMonthly * 12;

            $monthlyBonusAnnually = $monthlyBonusMonthly * 12;
            
            $bonusMonthly = 0;
          
            $latMonthly = 0;
            
            $performanceBonusAnnually = 0;
            
            
            if ($netPayableMonthly <= 21000) {
                $empEsicMonthly  = $netPayableMonthly * 0.0075; // 0.75%
                $empEsicAnnually = $empEsicMonthly * 12;
                $employerShareEsicMonthly  = $netPayableMonthly * 0.0325; // 3.25%
                $employerShareEsicAnnually = $employerShareEsicMonthly * 12;
            } else {
                $empEsicMonthly = 0;
                $empEsicAnnually = 0;
                $employerShareEsicMonthly = 0;
                $employerShareEsicAnnually = 0;
            }
            
            
            $grossSalAMonthly = round($basicDAMonthly) + round($hraMonthly) + round($conveyAllowanceMonthly) + round($eduAllowanceMonthly) +
                                round($foodAllowanceMonthly) + round($dressAllowanceMonthly) + round($medicalAllowanceMonthly) + round($monthlyBonusMonthly) + round($othAllowanceMonthly) ;
            $grossSalAAnnually = $grossSalAMonthly * 12;
            
            $totalRetrialMonthly = round($employerSharePfMonthly) + round($employerShareEsicMonthly) + round($gratuityMonthly) + round($exGratiaMonthly) +
                               + round($bonusMonthly) + round($latMonthly) ;
                               
            $totalRetrialAnnually = $totalRetrialMonthly * 12;
            
             
            $netPayMonthly = $grossSalAMonthly + $performanceBonusMonthly - ( $employeePfMonthly + $empEsicMonthly );
            $netPayAnuually = $netPayMonthly * 12;
            
            $ctcMonthly = $grossSalAMonthly + $totalRetrialMonthly + $performanceBonusMonthly;
            $ctcAnuually = $ctcMonthly * 12;
       
            
            // Build response object with single values (no nested arrays)
            $response = [
                "employeeId" => $annexureEmpId,
                "PayableMonthly" => round($netPayableMonthly),
                "basicDAMonthly" => round($basicDAMonthly),
                "basicDAAnnually" => round($basicDAAnnually),
                "hraMonthly" => round($hraMonthly),
                "hraAnnually" => round($hraAnnually),
                "employerSharePfMonthly" => round($employerSharePfMonthly),
                "employerSharePfAnnually" => round($employerSharePfAnnually),
                "gratuityMonthly" => round($gratuityMonthly),
                "gratuityAnnually" => round($gratuityAnnually),
                "exGratiaMonthly" => round($exGratiaMonthly),
                "exGratiaAnnually" => round($exGratiaAnnually),
                "employeePfMonthly" => round($employeePfMonthly),
                "employeePfAnnually" => round($employeePfAnnually),
                "conveyAllowanceMonthly" => round($conveyAllowanceMonthly),
                "conveyAllowanceAnnually" => round($conveyAllowanceAnnually),
                "foodAllowanceMonthly" => round($foodAllowanceMonthly),
                "foodAllowanceAnnually" => round($foodAllowanceAnnually),
                "dressAllowanceMonthly" => round($dressAllowanceMonthly),
                "dressAllowanceAnnually" => round($dressAllowanceAnnually),
                "empEsicMonthly" => round($empEsicMonthly),
                "empEsicAnnually" => round($empEsicAnnually),
                "employerShareEsicMonthly" => round($employerShareEsicMonthly),
                "employerShareEsicAnnually" => round($employerShareEsicAnnually),
                "eduAllowanceMonthly" => round($eduAllowanceMonthly),
                "eduAllowanceAnnually" => round($eduAllowanceAnnually),
                "medicalAllowanceMonthly" => round($medicalAllowanceMonthly),
                "medicalAllowanceAnnually" => round($medicalAllowanceAnnually),
                "othAllowanceMonthly" => round($othAllowanceMonthly),
                "othAllowanceAnnually" => round($othAllowanceAnnually),
                "monthlyBonusMonthly" => round($monthlyBonusMonthly),
                "monthlyBonusAnnually" => round($monthlyBonusAnnually),
                "bonusMonthly" => round($bonusMonthly),
                "bonusAnnually" => round($bonusAnnually),
                "latMonthly" => round($latMonthly),
                "latAnnually" => round($latAnnually),
                "performanceBonusMonthly" => round($performanceBonusMonthly),
                "performanceBonusAnnually" => round($performanceBonusAnnually),
                "grossSalAMonthly" => round($grossSalAMonthly),
                "grossSalAAnnually" => round($grossSalAAnnually),
                "totalRetrialMonthly" => round($totalRetrialMonthly),
                "totalRetrialAnnually" => round($totalRetrialAnnually),
                "netPayAnuually" => round($netPayAnuually),
                "netPayMonthly" => round($netPayMonthly),
                "ctcAnuually" => round($ctcAnuually),
                "ctcMonthly" => round($ctcMonthly),
            ];
            
            // Return JSON
            echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    
    else if ($_GET["type"] == "saveSalaryAnnexure") {
        
        $sql = "INSERT INTO `salary_annexure`(`plant_id`, `emp_id`, `basicDAMonthly`, `basicDAAnnually`, `hraMonthly`, `hraAnnually`, 
        `conveyAllowanceMonthly`, `conveyAllowanceAnnually`, `eduAllowanceMonthly`, `eduAllowanceAnnually`, `foodAllowanceMonthly`, 
        `foodAllowanceAnnually`, `dressAllowanceMonthly`, `dressAllowanceAnnually`, `medicalAllowanceMonthly`, `medicalAllowanceAnnually`, 
        `othAllowanceMonthly`, `othAllowanceAnnually`, `monthlyBonusMonthly`, `monthlyBonusAnnually`, `grossSalAMonthly`, 
        `grossSalAAnnually`, `employerSharePfMonthly`, `employerSharePfAnnually`, `employerShareEsicMonthly`, `employerShareEsicAnnually`, 
        `gratuityMonthly`, `gratuityAnnually`, `exGratiaMonthly`, `exGratiaAnnually`, `bonusMonthly`, `bonusAnnually`, `latMonthly`, `latAnnually`, 
        `totalRetrialMonthly`, `totalRetrialAnnually`, `performanceBonusMonthly`, `performanceBonusAnnually`, `employeePfMonthly`, `employeePfAnnually`, 
        `empEsicMonthly`, `empEsicAnnually`, `netPayMonthly`, `netPayAnuually`, `ctcMonthly`, `ctcAnuually`, `entry_by`, `entry_date`, `status`) VALUES 
        ('".$_GET["plant_id"]."','".$input["emp_id"]."','".$input["basicDAMonthly"]."','".$input["basicDAAnnually"]."','".$input["hraMonthly"]."',
        '".$input["hraAnnually"]."','".$input["conveyAllowanceMonthly"]."','".$input["conveyAllowanceAnnually"]."','".$input["eduAllowanceMonthly"]."',
        '".$input["eduAllowanceAnnually"]."','".$input["foodAllowanceMonthly"]."','".$input["foodAllowanceAnnually"]."', '".$input["dressAllowanceMonthly"]."',
        '".$input["dressAllowanceAnnually"]."','".$input["medicalAllowanceMonthly"]."','".$input["medicalAllowanceAnnually"]."','".$input["othAllowanceMonthly"]."',
        '".$input["othAllowanceAnnually"]."','".$input["monthlyBonusMonthly"]."','".$input["monthlyBonusAnnually"]."','".$input["grossSalAMonthly"]."',
        '".$input["grossSalAAnnually"]."','".$input["employerSharePfMonthly"]."','".$input["employerSharePfAnnually"]."',
        '".$input["employerShareEsicMonthly"]."','".$input["employerShareEsicAnnually"]."','".$input["gratuityMonthly"]."',
        '".$input["gratuityAnnually"]."','".$input["exGratiaMonthly"]."','".$input["exGratiaAnnually"]."','".$input["bonusMonthly"]."',
        '".$input["bonusAnnually"]."','".$input["latMonthly"]."','".$input["latAnnually"]."','".$input["totalRetrialMonthly"]."',
        '".$input["totalRetrialAnnually"]."','".$input["performanceBonusMonthly"]."','".$input["performanceBonusAnnually"]."',
        '".$input["employeePfMonthly"]."','".$input["employeePfAnnually"]."','".$input["empEsicMonthly"]."','".$input["empEsicAnnually"]."',
        '".$input["netPayMonthly"]."','".$input["netPayAnuually"]."','".$input["ctcMonthly"]."','".$input["ctcAnuually"]."',
        '".$_GET["emp_id"]."', '$entry_date' , 'Pending')";
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            
            $sq = "UPDATE employee set pan = '".$input["pan"]."' WHERE emp_id = '".$input["emp_id"]."' ";
            $conn->query($sq);
            
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if ($_GET["type"] == "getAnnexureOfEmployeeByDept") {
        $output = Array();
    
        if($_GET["department_name"] == 'ALL'){
            
          $sql = "SELECT a.*,e.firstname,e.middlename,e.lastname,e.department,e.joining_date,e.designation,e.operator_category FROM salary_annexure a 
          LEFT JOIN  employee e ON a.emp_id = e.emp_id WHERE  a.plant_id = '".$_GET["plant_id"]."' order by e.firstname ASC";
          
        }else{
            
          $sql = "SELECT a.*,e.firstname,e.middlename,e.lastname,e.department,e.joining_date,e.designation,e.operator_category FROM salary_annexure a 
          LEFT JOIN  employee e ON a.emp_id = e.emp_id WHERE e.department =  '".$_GET["department_name"]."' 
          AND a.plant_id = '".$_GET["plant_id"]."' order by e.firstname ASC";
          
        }

       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getEmployeesList") {
       $output = Array();
         
        $sql = "SELECT * FROM employee WHERE status != 'Exit' and plant_id = '".$_GET["plant_id"]."' order by id desc ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output2 = Array();
                $sql2 = "SELECT * FROM emp_document WHERE emp_id='".$conn->real_escape_string($row["emp_id"])."' AND  plant_id='".$_GET["plant_id"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2[] = $row2;
                    }
                }
                    
                $row["documents"] = $output2;
                    
                $row["academics"] = json_decode($row["academics"]);
                $row["languages"] = json_decode($row["languages"]);
                $row["employeement"] = json_decode($row["employeement"]);
                
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "getUserListForHo") {
       $output = Array();
         
        $sql = "SELECT * FROM employee WHERE status != 'Exit' and plant_id = '".$_GET["plant_id"]."' order by id desc ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {         
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "getActiveEmpBasedOnDeptOrDesignation") {
       $output = Array();
         
        $sql = "SELECT id,plant_id,firstname,lastname,department,emp_id,status FROM employee WHERE status != 'Exit' AND plant_id = '".$_GET["plant_id"]."' 
        AND ( department = '".$_GET["empDept"]."' OR designation = '".$_GET["empDesig"]."' ) order by id desc ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "getEmployeeACC") {
    
        $output = Array();
        
        $sql = "SELECT *  FROM employee  WHERE status = 'active' and plant_id = '".$_GET["plant_id"]."'  order by id desc";
      
           
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
          
                $output[] = $row;
               
            }
        }
        echo json_encode($output);
    } 
    
      else if ($_GET["type"] == "getDepartmentMeha") {
    
        $output = Array();
        
        $sql = "SELECT *  FROM department order by id desc";
      
           
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
          
                $output[] = $row;
               
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getEmployeesList_increment_letter") {
    
        $output = Array();
    
          $sql = "SELECT a.emp_id, a.*, b_max.id AS annexure_id, b_second_max.ctc_annual AS old_ctc,b_max.applicable_from,b_max.ctc_annual as new_ctc
FROM employee a
                    
                     LEFT JOIN (
                        SELECT emp_id, MAX(id) AS id,increament,applicable_from,ctc_annual
                        FROM salary_annexure
                        WHERE increament <> ''
                        GROUP BY emp_id,increament,applicable_from,ctc_annual
                    ) b_max ON a.emp_id = b_max.emp_id

                    
                     LEFT JOIN (
                        SELECT sa.emp_id, sa.ctc_annual
                        FROM (
                            SELECT emp_id, id, ctc_annual,
                                   ROW_NUMBER() OVER (PARTITION BY emp_id ORDER BY id DESC) AS rn
                            FROM salary_annexure
                            WHERE increament = ''
                        ) sa
                        WHERE sa.rn = 2
                    ) b_second_max ON a.emp_id = b_second_max.emp_id where b_max.increament!='' and a.plant_id='".$_GET["plant_id"]."'";
   
       
           
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $output2 = Array();
                    $sql2 = "SELECT * FROM emp_document WHERE emp_no='".$conn->real_escape_string($row["emp_id"])."'";
                     
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output2[] = $row2;
                        }
                    }
                    $row["document_list1"] = $output2;
                
                
                $row["details"] = json_decode($row["details"]);
                $row["familyList"] = json_decode($row["familyList"]);
                $output[] = $row;
               
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getAllEmployeesList") {
        $output = Array();
        $sql = "SELECT * FROM employee ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "getSalaryTypes") {
        $output = Array();
        $sql = "SELECT * FROM salary_types where plant_id='".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "HogetSalaryTypes") {
        $output = Array();
        $sql = "SELECT * FROM salary_types where plant_id='".$_GET["plantID"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "getDesignationTypes") {
        $output = Array();
        $sql = "select designation from designation ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
        else if ($_GET["type"] == "getright") {
        
        
        $output = array();
        
        $sql = "SELECT * FROM emp_rights WHERE  plant_id = '".$_GET["plant_id"]."' AND emp_id = '".$_GET["emp_id"]."' ";
        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
    
    
    else if ($_GET["type"] == "getRightsData") {
    
        $plant_id = $_GET['plant_id'] ?? '';
        $department = $_GET['search_department'] ?? 'ALL EMP';
    
        $sql = "SELECT 
                    e.id,
                    e.emp_id,
                    e.firstname,
                    e.lastname,
                    e.department,
                    e.designation
                FROM employee e
                LEFT JOIN emp_rights er 
                    ON e.emp_id = er.emp_id
                WHERE e.plant_id = ?
                  AND er.emp_id IS NULL";
    
        if ($department !== 'ALL EMP') {
            $sql .= " AND e.department = ?";
        }
    
        $sql .= " ORDER BY e.id DESC";
    
        $stmt = $conn->prepare($sql);
    
        if ($department !== 'ALL EMP') {
            $stmt->bind_param("ss", $plant_id, $department);
        } else {
            $stmt->bind_param("s", $plant_id);
        }
    
        $stmt->execute();
        $result = $stmt->get_result();
    
        while ($row = $result->fetch_assoc()) {
            $row['isuser'] = 'No';
            $row['ischecker'] = 'No';
            $row['isapprover'] = 'No';
            $row['qms_approver'] = 'No';
            $row['isauditor'] = 'No';
            $row['dept_head'] = 'No';
            $row['training_cordinator'] = 'No';
            $row['shift_allocator'] = 'No';
            $row['task_assigner'] = 'No';
            $row['check'] = false;
    
            $output[] = $row;
        }
    
        echo json_encode($output);
        exit;
    } 
    
    
    else if ($_GET["type"] == "getRightsDataHrApp") {
       // Sanitize inputs to prevent SQL injection
            $plant_id = $conn->real_escape_string($_GET["plant_id"]);
            $search_department = $conn->real_escape_string($_GET["search_department"]);
            
            // Base Query
            $sql = "SELECT e.department, e.id, e.lastname, e.firstname, e.designation, e.emp_id 
                    FROM employee e 
                    LEFT JOIN emp_rights er ON e.emp_id = er.emp_id 
                    WHERE e.plant_id = '$plant_id'";
            
            // Add department filter if not 'ALL EMP'
            if ($search_department !== 'ALL EMP') {
                $sql .= " AND e.department = '$search_department'";
            }
            
            $sql .= " ORDER BY e.id DESC";
            
            // Execute the query     
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['isuser'] = 'Yes';
                $row['ischecker'] = 'Yes';
                $row['isapprover'] = 'Yes';
                $row['qms_approver'] = 'No';
                $row['isauditor'] = 'No';
                $row['dept_head'] = 'No';
                $row['trainig_cordinator'] = 'No';
                 $row['shift_allocator'] = 'No';
                $row['check'] = false;
                
                  $output[] = $row;

            }
        }
        echo json_encode($output);
        
        
        
    } 

        else if ($_GET["type"] == "getRightsData1") {
        
        
        $output = array();
        
        if($_GET["search_department"] == 'ALL EMP'){
        $sql = "SELECT e.department,e.id,e.lastname,e.firstname,e.designation,e.emp_id FROM employee e LEFT JOIN emp_rights er ON
        e.emp_id = er.emp_id WHERE er.emp_id IS NULL AND e.plant_id = '".$_GET["plant_id"]."' order by e.id desc";
        
        }else{
            
        $sql = "SELECT e.department,e.id,e.lastname,e.firstname,e.designation,e.emp_id FROM employee e LEFT JOIN emp_rights er ON
        e.emp_id = er.emp_id WHERE er.emp_id IS NULL AND e.plant_id = '".$_GET["plant_id"]."' AND 
        e.department = '".$_GET["search_department"]."' order by e.id desc";
            
        }
        

        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['isuser'] = 'No';
                $row['ischecker'] = 'No';
                $row['isapprover'] = 'No';
                $row['qms_approver'] = 'No';
                $row['isauditor'] = 'No';
                $row['dept_head'] = 'No';
                $row['trainig_cordinator'] = 'No';
                 $row['shift_allocator'] = 'No';
                $row['check'] = false;
                
                  $output[] = $row;

            }
        }
        echo json_encode($output);
        
        
        
    } 
    
    else if ($_GET["type"] == "getrightsDashboard") {
        
        $output = array();
        $empId = $conn->real_escape_string(trim($_GET["emp_id"] ?? ''));
        if ($empId !== '') {
            // All rights rows for employee (additional dept e.g. QA may differ from login plant_id on old rows)
            $sql = "SELECT * FROM emp_rights WHERE emp_id = '".$empId."' ORDER BY id ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['dashboard_department'] = dashboard_rights_resolve_department($row['department']);
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "checkIfPlantHead") {
        
        $output = array();
        
        $sql = "SELECT plant_head FROM emp_rights WHERE plant_head = 'Yes' AND plant_id = '".$_GET["plant_id"]."' AND emp_id = '".$_GET["emp_id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
    else if ($_GET["type"] == "getrights") {
        
        $output = array();
        
        $sql = "SELECT * FROM emp_rights WHERE  plant_id = '".$_GET["plant_id"]."' AND emp_id = '".$_GET["emp_id"]."' and department='".$_GET["dep_name"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output[] = $row;
            }
        }
        echo json_encode($output);
        
    } 
  
    else if ($_GET["type"] == "save_salary_types") {
        $sql="select * from salary_types where payroll_type='".$input["payroll_type"]."' and plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
                echo "{\"status\":\"Salary Type Already Exists. Duplicates Not Allowed!\"}";
        }else{
            $output = Array();
            $sql = "INSERT INTO salary_types(plant_id, payroll_type,entry_by) VALUES 
            ('".$_GET["plant_id"]."',  '".$input["payroll_type"]."',  '".$_GET["emp_id"]."')";
             if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
        }
    }
    else if ($_GET["type"] == "getSalesEmployees") {
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "get_emp_by_id") {
        $output = null;
        $sql = "SELECT id,employee_type,emp_id, firstname,lastname,contact_no,emp_email,department,ai_data_object FROM employee 
        WHERE emp_id='".$_GET["emp_code"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "get_emp_details_by_id") {
        $output = null;
        // $sql = "SELECT * FROM employee 
        // WHERE emp_id='".$_GET["emp_code"]."'";
          $sql = "SELECT * FROM employee  WHERE emp_id='".$_GET['emp_code']."' and plant_id= '".$_GET['plant_id']."'";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "update_employee_ai_data") {
        $sql = "UPDATE employee SET ai_data_object='".$_GET["ai_data_object"]."', ai_data_object_updated_by='".$_GET["emp_id"]."', ai_data_object_updated_on= CURRENT_TIMESTAMP WHERE id='".$_GET["id"]."'";
       // echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"AI Data Inserted Successfully\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "activateInActivatedEmployee") {
         
        $sql = "UPDATE employee SET status = '".$_GET["status"]."' WHERE id = '".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "exitEmployee") {
         
        $sql = "UPDATE employee SET status = 'EXIT' , lastDateOfWorking = '".$input['lastDateOfWorking']."', reasonForLeaving = '".$input['reasonForLeaving']."' , 
        exitBy = '".$_GET['emp_id']."' , exitOn = '$entry_date' WHERE emp_id = '".$input["emp_id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getIndividualAttendace") {
        $output = Array();
        
        $earlier = new DateTime($_GET["from_date"]);
        $later = new DateTime($_GET["to_date"]);
        
        $diff = $later->diff($earlier)->format("%a");
        
        $fromdate = $earlier->format('Y-m-d');

        for ($i = 1; $i <= $diff; $i++) {
            $temp = Array();
            
            $sql1 = "SELECT * FROM attendence WHERE DATE(indate)='$fromdate' AND emp_id='".$_GET["emp_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $temp["indate"] = $row1["indate"];
                    $temp["outdate"] = $row1["outdate"];
                    $temp[$fromdate] = "P";
                }
            } else {
                $temp[$fromdate] = "A";
            }
            $output[] = $temp;
            $fromdate = date('Y-m-d', strtotime($fromdate. ' + 1 days'));
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMonthlyPerformance") {
        $output = Array();
        $sql = "SELECT emp_id, department, designation, emp_name FROM employee WHERE status='active' AND department LIKE '%".$_GET["department_name"]."%' AND designation LIKE '%".$_GET["designation"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $second = 0;
                $holidays = 0;
                $curdate=strtotime(date("d", $timestamp)."-".date("m", $timestamp)."-".date("Y", $timestamp));
                $days = cal_days_in_month(CAL_GREGORIAN,date("m", $timestamp),date("Y", $timestamp));
                for ($i = 1; $i <= $days; $i++) {
                    
                    $today = date("Y", $timestamp)."-".date("m", $timestamp)."-".$i;
                    $holiday = isWeekend($today);
                    if ($holiday) {
                        $holidays++;
                    }
                    
                    $mydate=strtotime($i."-".date("m", $timestamp)."-".date("Y", $timestamp));
                    
                    if($curdate >= $mydate) {
                        $sql1 = "SELECT indate, outdate, TIMESTAMPDIFF(SECOND, indate,outdate) as second FROM attendence WHERE DATE(indate)='$today' AND emp_id='".$conn->real_escape_string($row["emp_id"])."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $second += +$row1["second"];
                            }
                        }
                    }
                }
                
                $work_days = $days - $holidays;
                $actual_hr = 28800 * $work_days;
                $diff = $actual_hr - $second;
                $per = $diff / $actual_hr *100;
                $row["performace"] = number_format(100 - $per, 2);
                
                if (+$row["performace"] >= 90) {
                    $row["remark"] = "Above Average";
                } else if (+$row["performace"] < 90 && +$row["performace"] >= 75) {
                    $row["remark"] = "Average";
                } else if (+$row["performace"] < 75 && +$row["performace"] >= 60) {
                    $row["remark"] = "Below Average";
                } else if (+$row["performace"] < 60) {
                    $row["remark"] = "Unsatisfactory";
                }
                $row["working_hours"] = gmdate("H:i:s", $second);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getDepartmentEmployees") {
        $output = Array();
        $deptEsc = $conn->real_escape_string(trim($_GET["department_name"] ?? ''));
        $desigEsc = trim($_GET["designation"] ?? '');
        $plantEsc = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $sql = "SELECT id,firstname,middlename,lastname,emp_id,operator_category,designation,status,plant_id,department FROM employee
        WHERE LOWER(TRIM(status)) = 'active' AND TRIM(department) = '".$deptEsc."'
        AND plant_id = '".$plantEsc."'";
        if ($desigEsc !== '') {
            $desigEsc = $conn->real_escape_string($desigEsc);
            $sql .= " AND LOWER(TRIM(designation)) = LOWER(TRIM('".$desigEsc."'))";
        }
        $sql .= " ORDER BY firstname, lastname";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "getDepartmentEmployees1") {
        $output = Array();
        $deptEsc = $conn->real_escape_string(trim($_GET["department_name"] ?? ''));
        $plantEsc = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $sql = "SELECT emp_id, department, lastname, firstname, middlename, designation, status FROM employee
        WHERE LOWER(TRIM(status)) = 'active' AND TRIM(department) = '".$deptEsc."' AND plant_id = '".$plantEsc."'
        ORDER BY firstname, lastname";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "HOgetDepartmentEmployees1") {
        $output = Array();
        // $sql = "SELECT e.emp_id,e.department,e.lastname,e.firstname,s.total_ctc,s.ctc_annual FROM employee e left join salary_annexure s 
        // ON e.emp_id = s.emp_id  WHERE e.status='active' AND e.department='".$_GET["department_name"]."'  ";
        
        $sql = "SELECT e.emp_id, e.department, e.lastname, e.firstname, s.total_ctc, s.ctc_annual FROM employee e LEFT JOIN ( SELECT emp_id, MAX(id) AS max_id 
        FROM salary_annexure  GROUP BY emp_id ) latest_salary ON e.emp_id = latest_salary.emp_id LEFT JOIN salary_annexure s ON latest_salary.max_id = s.id 
        WHERE e.status='active' AND e.department='".$_GET["department_name"]."' AND e.plant_id='".$_GET["plantID"]."'";

        
        
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if($_GET['type']=='generatedappointment'){
       // $sql = "SELECT * FROM employee WHERE isAppointment = 'active'";// ORDER BY id DESC";
        $sql = "SELECT * FROM employee WHERE id in(select emp_id from salary_annexure) ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row; 
    		}
    		echo json_encode($output);
    	} else {
    	   echo "[]";
    	}
    } 
    else if ($_GET["type"] == "modules") {
	$sql = "SELECT * FROM deprt_formname a left JOIN department b on a.department_id=b.department_code WHERE b.department_name='".$_GET["department_name"]."'";
		$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
    else if ($_GET["type"] == "form_type") {
	$sql = "SELECT a. *,b.department_name FROM deprt_formname a left JOIN department b on a.department_id=b.department_code WHERE b.department_name='".$_GET["department_name"]."' and a.module_name='".$_GET["module_name"]."'";
		$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
    else if ($_GET["type"] == "form_Name") {
	$sql = "SELECT a. *,b.department_name FROM deprt_formname a left JOIN department b on a.department_id=b.department_code WHERE b.department_name='".$_GET["department_name"]."' and a.module_name='".$_GET["module_name"]."' and a.form_type='".$_GET["form_type"]."'";
		$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
    else if ($_GET["type"] == "getRights_Log"){
        $output = array();
        
       
        
        if ($_GET["department_name"] == "ALL EMP"){
            
                $sql = "SELECT er.plant_id,e.emp_id,e.isapprover,e.ischecker,e.isuser,e.qms_approver,e.isauditor,e.dept_head,e.trainig_cordinator,e.shift_allocator,er.firstname,e.task_assigner,
                er.lastname,er.department,er.designation,e.department as rightsDept FROM emp_rights e LEFT JOIN employee er ON e.emp_id = er.emp_id AND e.plant_id = er.plant_id  WHERE 
                er.plant_id = '".$_GET["plant_id"]."'   ";
        }else{
            
                $sql = "SELECT er.plant_id,e.emp_id,e.isapprover,e.ischecker,e.isuser,e.qms_approver,e.isauditor,e.dept_head,e.trainig_cordinator,e.shift_allocator,er.firstname,e.task_assigner,
                er.lastname,er.department,er.designation,e.department as rightsDept FROM emp_rights e LEFT JOIN employee er ON e.emp_id = er.emp_id AND e.plant_id = er.plant_id  WHERE 
                er.plant_id = '".$_GET["plant_id"]."' and er.department = '".$_GET["department_name"]."'   ";
        }
      
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;

            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getRights_LogForUpdate"){
        $output = array();
        
       
        
        if ($_GET["department_name"] == "ALL EMP"){
            
                $sql = "SELECT er.plant_id,e.emp_id,e.isapprover,e.ischecker,e.isuser,e.qms_approver,e.isauditor,e.dept_head,e.trainig_cordinator,e.shift_allocator,er.firstname,e.id as urid,e.task_assigner,
                er.lastname,er.department,e.department as rightsDept,er.designation FROM emp_rights e LEFT JOIN employee er ON e.emp_id = er.emp_id AND e.plant_id = er.plant_id  WHERE 
                er.plant_id = '".$_GET["plant_id"]."'  order by e.emp_id ASC ";
        }else{
            
                $sql = "SELECT er.plant_id,e.emp_id,e.isapprover,e.ischecker,e.isuser,e.qms_approver,e.isauditor,e.dept_head,e.trainig_cordinator,e.shift_allocator,er.firstname,e.id as urid,e.task_assigner,
                er.lastname,er.department,e.department as rightsDept,er.designation FROM emp_rights e LEFT JOIN employee er ON e.emp_id = er.emp_id AND e.plant_id = er.plant_id  WHERE 
                er.plant_id = '".$_GET["plant_id"]."' and er.department = '".$_GET["department_name"]."'  order by e.emp_id ASC ";
        }
      
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;

            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getaddDep_data"){
        $output = array();
         
                 $sql = "SELECT distinct e.emp_id,e.isapprover,e.ischecker,e.isuser,e.qms_approver,e.isauditor,e.dept_head,e.shift_allocator,er.firstname,e.trainig_cordinator,e.task_assigner,
     er.lastname,e.department,er.designation FROM emp_rights  e LEFT JOIN employee er ON e.emp_id = er.emp_id WHERE 
         e.plant_id = '".$_GET["plant_id"]."' and  e.emp_id='".$_GET["emp_id1"]."' ";
       
         
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;

            }
        }
        echo json_encode($output);
    } 
             
        else if ($_GET["type"] == "update_empRights") {
    
            $input = file_get_contents("php://input");
            $inputData = json_decode($input, true);
    
            if ($inputData === null) {
                echo '{"status": "JSON decoding failed"}';
            } else {
    
                  $array = $inputData;
                  
                  
                     $k=1;
                     $flaf = 0;
                     
            foreach ($array as $values)
            {
                        
                $sql = "INSERT INTO emp_rights (department,user_no, emp_id, isuser, ischecker, isapprover, entry_by, entry_date, status,dept_head,
                 isauditor, qms_approver,plant_id,shift_allocator,main,trainig_cordinator,task_assigner)VALUES ('".$values["department"]."','$user_no', '".$values["emp_id"]."', '".$values["isuser"]."',
                '".$values["ischecker"]."', '".$values["isapprover"]."', '".$_GET["emp_id"]."', '$entry_date', 'approve',
                '".$values["dept_head"]."', '".$values["isauditor"]."','".$values["qms_approver"]."','".$_GET["plant_id"]."','".$values["shift_allocator"]."','Yes','".$values["trainig_cordinator"]."','".$values["task_assigner"]."')";

                if ($conn->query($sql)) {
                      $flaf = 1;
                
                }else {
                     $flaf = 0;
                }                    
            }
                    
                    
                if ($flaf == 1) {
                    echo '{"status": "success"}';
                
                }else {
                    echo '{"status": "' . $conn->error . '"}';
                }
    
            }
    }
            
    else if ($_GET["type"] == "update_additional_empRights") {
                    $values = is_array($input) ? $input : array();
        $dept = $conn->real_escape_string($values["department"] ?? '');
        $empId = $conn->real_escape_string($values["emp_id"] ?? '');
        $plantId = $conn->real_escape_string($_GET["plant_id"] ?? '');
        if ($dept === '' || $empId === '') {
            echo "{\"status\":\"department and emp_id are required\"}";
        } else {
        $checkSql = "SELECT id FROM emp_rights WHERE emp_id='".$empId."' AND department='".$dept."' AND plant_id='".$plantId."' LIMIT 1";
        $checkRes = $conn->query($checkSql);
        if ($checkRes && $checkRes->num_rows > 0) {
         $sql = "UPDATE emp_rights SET isuser = '".$values["isuser"]."', ischecker = '".$values["ischecker"]."', isapprover = '".$values["isapprover"]."',
                qms_approver = '".$values["qms_approver"]."', isauditor = '".$values["isauditor"]."', dept_head = '".$values["dept_head"]."',
                shift_allocator = '".$values["shift_allocator"]."', trainig_cordinator = '".$values["trainig_cordinator"]."',
                task_assigner = '".($values["task_assigner"] ?? 'No')."', update_by = '".$_GET["emp_id"]."', update_date = '$entry_date'
                WHERE emp_id='".$empId."' AND department='".$dept."' AND plant_id='".$plantId."'";
        } else {
         $sql = "INSERT INTO emp_rights (department,user_no, emp_id, isuser, ischecker, isapprover, entry_by, entry_date, status,dept_head,
                 isauditor, qms_approver,plant_id,shift_allocator,trainig_cordinator,task_assigner)VALUES ('".$dept."','$user_no', '".$empId."', '".$values["isuser"]."',
                '".$values["ischecker"]."', '".$values["isapprover"]."', '".$_GET["emp_id"]."', '$entry_date', 'approve',
                '".$values["dept_head"]."', '".$values["isauditor"]."','".$values["qms_approver"]."','".$plantId."','".$values["shift_allocator"]."','".$values["trainig_cordinator"]."','".($values["task_assigner"] ?? 'No')."')";
        }
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\",\"message\":\"Department rights saved successfully.\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
        
        
    } 
    else if ($_GET["type"] == "changeRights") {
        $rightsDept = $conn->real_escape_string($input["department"] ?? $input["rightsDept"] ?? '');
        $empId = $conn->real_escape_string($input["emp_id"] ?? '');
        $plantId = $conn->real_escape_string($_GET["plant_id"] ?? '');
        if ($rightsDept === '' || $empId === '') {
            echo "{\"status\":\"department and emp_id are required\"}";
        } else {
         $sql = "UPDATE emp_rights  SET  isuser = '".$input["isuser"]."', ischecker = '".$input["ischecker"]."', isapprover = '".$input["isapprover"]."'
        , qms_approver = '".$input["qms_approver"]."', isauditor = '".$input["isauditor"]."', shift_allocator = '".$input["shift_allocator"]."'
        , dept_head = '".$input["dept_head"]."', trainig_cordinator = '".$input["trainig_cordinator"]."', task_assigner = '".($input["task_assigner"] ?? 'No')."'
        , update_by = '".$_GET["id"]."', update_date = '$entry_date'  where emp_id = '".$empId."' AND department = '".$rightsDept."' AND plant_id = '".$plantId."'";
      
        if ($conn->query($sql)) {
            if ($conn->affected_rows > 0) {
            echo "{\"status\":\"success\"}";
            } else {
            echo "{\"status\":\"no matching rights row for this employee and department\"}";
            }
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        }
        
        
    } 
    else if ($_GET["type"] == "getEmployeeDetails") {
       $output = Array();
         
        $sql = "SELECT * FROM employee WHERE  emp_id='".$_GET["emp_code"]."' AND plant_id = '".$_GET["plant_id"]."' order by id desc ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output2 = Array();
                $sql2 = "SELECT * FROM emp_document WHERE emp_id='".$conn->real_escape_string($row["emp_id"])."' AND  plant_id='".$_GET["plant_id"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output2[] = $row2;
                    }
                }
                    
                $row["documents"] = $output2;
                    
                $row["academics"] = json_decode($row["academics"]);
                $row["languages"] = json_decode($row["languages"]);
                $row["employeement"] = json_decode($row["employeement"]);
                
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
        
    } else if($_GET['type']=='pendingappointment') {
        $output = array();
        $sql = "SELECT * FROM employee WHERE isAppointment = 'pending' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row; 
    		}
    	}
    	echo json_encode($output);
    } 
    else if($_GET['type']=='getOperators') {
            $output = Array();
            $sql = "SELECT * FROM employee WHERE (operator_category='Worker / Operator' or operator_category='staff')  AND status='Active' AND plant_id='".$_GET["plant_id"]."' ORDER BY firstname";
           	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		    $output[] = $row; 
    		}
    	}
    	echo json_encode($output);
            
    }
    
    else if($_GET['type']=='getEmpSalaryAnnexure') {
        $output = array();
           $output_data = array();
        $output_earnings = array();
        $output_deductions = array();
        $output_ctc = array();
        
        $sql = "SELECT * FROM salary_annexure WHERE id = '".$_GET["id"]."' order by id desc limit 1";
    	$result1 = $conn->query($sql);
     
    	if($result1->num_rows > 0){
    		while($row1 = $result1->fetch_assoc()){
    		      //  $sql = "UPDATE salary_annexure SET earnings = '$newEarningsData', deductions = '$newDeductionsData', ctc = '$newCtcData' WHERE id = (SELECT MAX(id) FROM salary_annexure)";

                  $sql = "SELECT * FROM salary_annexure_details WHERE salary_annexure_id = '".$_GET["id"]."' and salary_group='Earnings'";
            	$result = $conn->query($sql);
            	if($result->num_rows > 0){
            		while($row = $result->fetch_assoc()){
            		  $output_earnings[] = $row; 
            		}
            	}
            	$row1["earnings"] = $output_earnings;
            	$sql = "SELECT * FROM salary_annexure_details WHERE salary_annexure_id = '".$_GET["id"]."' and salary_group='Deductions'";
            	$result = $conn->query($sql);
            	if($result->num_rows > 0){
            		while($row = $result->fetch_assoc()){
            		    $output_deductions[] = $row; 
            		}
            	}
            		$row1["deductions"] = $output_deductions;
            	$sql = "SELECT * FROM salary_annexure_details WHERE salary_annexure_id = '".$_GET["id"]."' and salary_group='CTC Calculations'";
            	$result = $conn->query($sql);
            	if($result->num_rows > 0){
            		while($row = $result->fetch_assoc()){
            		    $output_ctc[] = $row; 
            		}
            	}
            	$row1["ctc"] = $output_ctc;
             	$sql = "SELECT SUM(a.loan_amt) AS loan_amt
                                            FROM emp_loan a 
                                            WHERE 
                                                a.emp_id = '".$row1['emp_id']."' 
                                                AND YEAR(a.emi_start_from) = YEAR(CURDATE()) 
                                                AND a.emi_start_from <= CURDATE() 
                                                AND a.emi_end >= CURDATE() and a.status='Approved'";
            	$result = $conn->query($sql);
            	if($result->num_rows > 0){
            		while($row = $result->fetch_assoc()){
            		  //  $emp_loan[] = $row; 
            		  	$row1["emp_loan"] = $row["loan_amt"];
            		}
            	}
             	$sql = "SELECT sum(b.interest) as total_intrest_anuual FROM emp_loan a left join emi_schedules b on a.id=b.emp_loan_id where a.emp_id= '".$_GET["emp_idd"]."'    AND YEAR(a.emi_start_from) = YEAR(CURDATE()) 
                                                AND a.emi_start_from <= CURDATE() and a.status='Approved'";
            	$result = $conn->query($sql);
            	if($result->num_rows > 0){
            		while($row = $result->fetch_assoc()){
            		  //  $emp_loan[] = $row; 
            		  	$row1["total_intrest_anuual"] = $row["total_intrest_anuual"];
            		}
            	}
            
            	
            	
            	
            	   
            	
            	
            	
            		
    	 $output[] = $row1; 
    		}
    	}
        
    	echo json_encode($output);
    }
    else if($_GET['type']=='generatenewappointment') {
        // require '../phpmailer/class.phpmailer.php';
        // require '../tcpdf/tcpdf.php';
        $id = $input['emp_id'];
        $sql = "INSERT INTO salary_annexure (emp_id,type,isMetro,isPF,isESIC,basic,hra,conveyance,specialallowance, gross, PF_EMP, c_PF, ESIC, c_ESIC, p_tax, medical, gratuity, bonus, contribution, contribution_annual, inhand, deduction, ctc, ctc_annual, entry_by, entry_date)VALUE('$id','".$input['type']."','".$input['isMetro']."','".$input['isPF']."','".$input['isESIC']."','".$input['basic']."','".$input['hra']."','".$input['conveyance']."','".$input['specialallowance']."','".$input['gross']."','".$input['PF_EMP']."','".$input['c_EMP']."','".$input['ESIC']."','".$input['c_ESIC']."','".$input['p_tax']."','".$input['medical']."','".$input['gratuity']."','".$input['bonus']."','".$input['contribution']."','".$input['contribution_annual']."','".$input['inhand']."','".$input['deduction']."','".$input['ctc']."','".$input['ctc_annual']."','".$_GET["emp_id"]."','$entry_date')"; 
        if($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
            $sql1 = "UPDATE employee SET isAppointment='active' WHERE emp_id='".$input["emp_id"]."'";
            $conn->query($sql1);
            /*$sql2 = "SELECT * FROM employee WHERE emp_id='$id'";
            $result2 = $conn->query($sql2);
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $email = $row2['emp_email'];
                    
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                        class MYPDF extends TCPDF {
                            public function Header() {
                                
                            }
                            public function Footer() {
                                
                            }
                        }
                        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        $pdf->SetCreator(PDF_CREATOR);
                        $pdf->SetTitle('Salary Annexure');
                        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
                        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
                        $pdf->setPrintFooter(false);
                        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
                        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                            require_once(dirname(__FILE__).'/lang/eng.php');
                            $pdf->setLanguageArray($l);
                        }
                        $pdf->AddPage();
                        $pdf->SetY(30);
                        $pdf->SetFont ('times', '', '12');
                        $html='
                        <br><br><br>
                        <h3 style="text-align:center;">APPOINTMENT LETTER</h3>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                            <tr>
                                <td style="width:70%;"><b><label>'.$letter_no.'</label></b></td>
                                <td style="width:30%; text-align:right;"><b>Date : </b><label>'.$newDate.'</label></td>
                            </tr>
                            <tr>
                                <td colspan="3"><b>To,</b></td>
                            </tr>
                            <tr>
                                <td style="width:50%;"><b>'.$title.' '.$row2['emp_name'].' <br>'.$address.'</b></td>
                            <tr>
                        </table>
                        <h3>Dear '.$title.' '.$name.' , </h3><br>
                        <table>
                            <tr>
                                <td style="width:5%;"></td>
                                <td style="width:95%; text-align:justify;">This has reference to your application for employment in our Company; we are pleased to offer you an employment with us as an <b>'.$row2['finaldesignation'].'</b> on '.$newDate.' in <b> GMP Software Pvt ltd based in Pune HQ</b><br>Please note that this is merely an Offer Letter. <br>You are requested to carry the following documents at the time of joining: -<br>&nbsp; &nbsp; 1.	Academic Certificates / Passing Certificate (Original).<br>&nbsp; &nbsp; 2.	Two Passport size photographs.<br>&nbsp; &nbsp; 3.	ID Proof Xerox (Pan Card/Driving License/ Aadhar Card). <br>You are requested to join within 7 days from receipt of this Letter, failing, which this offer of employment stands withdrawn after completion of this period.<br>If employee’s performance found poor, company may ask to extend training period or ask to leave.<br>Kindly confirm your acceptance on the duplicate copy of this letter/or Return Email.<br>Other employment terms will be as per your appointment letter and will be informed within 7 days from your joining.<br></td>
                            </tr>
                        </table>
                        <br><br><br>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                            <tr>
                                <td style="width:40%; text-align:center;"><b>Yours Faithfully,</b></td>
                              </tr>
                              <tr>
                                <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                                <td style="width:10%;"></td>
                                <td style="width:60%;">I accept and agree to the above terms & conditions	</td>
                              </tr>
                              <tr><td></td></tr>
                              <tr>
                                <td style="width:40%; text-align:center;"><b>Mr. Sachin Bhalekar</b></td>
                                <td style="width:10%;"></td>
                                <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                            </tr>
                        </table>
                        <br pagebreak="true"/>
                        <h4 style="text-align:center;">Salary Annexure : </h4>
                        <br></br><br>
                        <table>
                            <tr>
                                <td style="text-align:right; width:100%;">Date: </td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Name: '.$row2['emp_name'].'</td>
                                <td style="width:70%;"></td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Department:</td>
                                <td style="width:70%;">'.$row2['department'].'</td>
                            </tr>
                        </table>
                        <div></div>
                        <table cellpadding="5" style="border: 1px solid #DCDCDC; text-align:left; ">
                            <tr style="background-color:#DCDCDC;">
                                <td border="1" style="width:10%; text-align:center;"><b>Sr No</b></td>
                                <td border="1" style="width:40%; text-align:center;"><b>Particulars</b></td>
                                <td border="1" style="width:25%; text-align:center;"><b>Salary Per Month</b></td>
                                <td border="1" style="width:25%; text-align:center;"><b>Annual Salary</b></td>
                            </tr>
                            <tr>
                                <td border="1" colspan="4"><b>Earnings :</b></td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">1</td>
                                <td border="1">Basic</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['basic'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['basic']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">2</td>
                                <td border="1">HRA</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['hra'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['hra']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">3</td>
                                <td border="1">Conveyance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['conveyance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['conveyance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">4</td>
                                <td border="1">Medical</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['medical'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['medical']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">5</td>
                                <td border="1">Special Allowance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['specialallowance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['specialallowance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="text-align:center;">6</td>
                                <td border="1">Education Allowance</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['educationalallowance'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['educationalallowance']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" colspan="2" style="text-align:right;"><b>Gross Total (A)</b></td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['gross'], 2, '.', '').'</td>
                                <td border="1" style="text-align:right;">'.number_format((float) $input['gross'], 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td colspan="4" border="1"><b>Deductions :</b></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">1.</td>
                                <td border="1" style="width:40%;">PT</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['p_tax'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['p_tax']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">2.</td>
                                <td border="1" style="width:40%;">PF</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">3.</td>
                                <td border="1" style="width:40%;">ESIC</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ESIC'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ESIC']*12, 2, '.', '').'</td>
                            </tr>
                             <tr>
                                
                                <td border="1" colspan="2" style="text-align:right;"><b>Total Deduction (B)</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $input['deduction'], 2, '.', '').'</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $input['deduction']*12, 2, '.', '').'</b></td>
                            </tr>
                            <tr>
                                <td colspan="4" border="1"><b>Company Contribution :</b></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">1.</td>
                                <td border="1" style="width:40%;">PF</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['PF']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:10%;">2.</td>
                                <td border="1" style="width:40%;">ESIC</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['c_ESIC'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['c_ESIC']*12, 2, '.', '').'</td>
                            </tr>
                             <tr>
                                
                                <td border="1" colspan="2" style="text-align:right;"><b>Total Contribution (C)</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $input['contribution'], 2, '.', '').'</b></td>
                                <td border="1" style="text-align:right;"><b>'.number_format((float) $input['contribution']*12, 2, '.', '').'</b></td>
                            </tr>
                            <tr>
                                <td border="1" colspan="4"></td>
                            </tr>
                            <tr>
                                <td border="1" style="width:50%;">NET Salary ( A - B )</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['inhand'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['inhand']*12, 2, '.', '').'</td>
                            </tr>
                            <tr>
                                <td border="1" style="width:50%;">CTC ( A + C )</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ctc'], 2, '.', '').'</td>
                                <td border="1" style="width:25%; text-align:right;">'.number_format((float) $input['ctc']*12, 2, '.', '').'</td>
                            </tr>
                        </table>
                        <br><br><br><br>
                        <table cellpadding="5" style="text-align:left; width:100%;">
                          <tr>
                            <td style="width:40%; text-align:center;"><b>GMP Software Pvt Ltd</b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">Accepted</td>
                          </tr>
                          <tr>
                            <td style="width:40%; text-align:center;"><b></b></td>
                            <td style="width:10%;"></td>
                            <td style="width:60%; text-align:center;">(Signature of an '.$input['type'].')</td>
                          </tr>
                        </table>';
                        EOD;
                        $pdf->writeHTML($html, true, false, false, false, '');
                        $file = $id.'.pdf';
                        $pdf->Output(dirname(__FILE__).'/'.$file, 'F');
                    
                        $mail = new PHPMailer();
                        $mail->IsSMTP();  
                        $mail->Mailer = "smtp";
                        $mail->SMTPDebug = 1;
                        $mail->SMTPAuth = true;
                        $mail->SMTPSecure = 'ssl';
                        $mail->Host = "mail.dnsfine.in";
                        $mail->Port = 465; // or 587
                        $mail->IsHTML(true);
                        $mail->Username = "demo@dnsfine.in";
                        $mail->Password = "2424@Cyclone";
                        $mail->SetFrom("demo@dnsfine.in", "Paperless GMP");
                        $mail->Subject = "Appointment Letter";
                        $mail->Body = "Please find Attachment ";
                        $mail->AddAttachment($file);
                        $mail->AddAddress($email);
                        $mail->Send();
                    }
                    $sql = "UPDATE employee SET isAppointment = 'active' WHERE emp_id = '$id'"; 
                    $conn->query($sql);
                    unlink($id.'.pdf');
                }
            }*/
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "downloadEmployeeList") {
        $_GET['filename'] = 'Employee List'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr</td>
                    <td style="width: 10%;">Emp Id.</td>
                    <td style="width: 15%;">Name</td>
                    <td style="width: 10%;">Department</td>
                    <td style="width: 10%;">Joining Status</td>
                    <td style="width: 15%;">Designation</td>
                    <td style="width: 20%;">Email</td>
                    <td style="width: 15%;">Status</td>
                </tr>
            </thead>';
             $i=1;
        //  $sql = "SELECT * FROM employee WHERE department LIKE '%".$_GET["department_name"]."%' AND designation LIKE '%".$_GET["designation"]."%' AND status LIKE '%".$_GET["status"]."%' order by 1 desc";
        $sql = "SELECT DISTINCT a.emp_id, a.*, b.id AS annexure_id,b.ctc_annual FROM employee a LEFT JOIN ( SELECT sa.* FROM salary_annexure sa JOIN 
        ( SELECT emp_id, MAX(id) AS max_id FROM salary_annexure WHERE increament = '' GROUP BY emp_id ) latest ON sa.emp_id = latest.emp_id 
        AND sa.id = latest.max_id ) b ON a.emp_id = b.emp_id AND a.plant_id = b.plant_id 
        WHERE a.department LIKE '%".$_GET["department_name"]."%' AND a.designation LIKE '%".$_GET["designation"]."%' 
      AND a.status LIKE '%".$_GET["status"]."%' and a.plant_id LIKE '%".$_GET["plant_id"]."%' order by a.id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'</td>
                        <td style="width: 10%;">'.$row['emp_id'].'</td>
                        <td style="width: 15%;">'.$row['firstname'].'</td>
                        <td style="width: 10%;">'.$row['department'].'</td>
                        <td style="width: 10%;">'.$row['joining_status'].'</td>
                        <td style="width: 15%;">'.$row['designation'].'</td>
                        <td style="width: 20%;">'.$row['emp_email'].'</td>
                        <td style="width: 15%;">'.$row['status'].'</td>
                    </tr>';
                $i++;
            }
        }
       
             $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Employee List.pdf', 'I');
        exit;

    } else if ($_GET["type"] == "downloadEmployeesListLog") {
        $_GET['filename'] = 'Employee List';
        $_GET['pdftype'] = 'landscape';
        $_GET['pdffont'] = 'helvetica';
        $_GET['pdffonts'] = 7;
        include("../pdfimp2.php");
        $html = '';

        $empEsc = function ($value) {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };
        $empVal = function ($value) use ($empEsc) {
            $text = trim((string) ($value ?? ''));
            return $text === '' ? 'NA' : $empEsc($text);
        };
        $empDate = function ($value) use ($empVal) {
            if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                return 'NA';
            }
            $ts = strtotime((string) $value);
            return $ts ? date('d-m-Y', $ts) : 'NA';
        };

        $html .= '<h2 style="text-align:center;">Employee List</h2>';
        $html .= '<table border="1" cellpadding="3" cellspacing="0" width="100%">
            <thead>
            <tr style="background-color:#0b6b7a;color:#ffffff;">
                <th width="4%" align="center"><b>Sr</b></th>
                <th width="7%" align="left"><b>Emp ID</b></th>
                <th width="11%" align="left"><b>Name</b></th>
                <th width="10%" align="left"><b>Department</b></th>
                <th width="10%" align="left"><b>Designation</b></th>
                <th width="7%" align="left"><b>Status</b></th>
                <th width="8%" align="left"><b>Entry On</b></th>
                <th width="9%" align="left"><b>Contact</b></th>
                <th width="13%" align="left"><b>Email</b></th>
                <th width="8%" align="left"><b>Join Date</b></th>
                <th width="6%" align="left"><b>Gender</b></th>
                <th width="7%" align="left"><b>Emp Type</b></th>
            </tr>
            </thead>
            <tbody>';

        $plantId = $conn->real_escape_string($_GET['plant_id'] ?? '');
        $sql = "SELECT * FROM employee WHERE status != 'Exit' AND plant_id = '" . $plantId . "' ORDER BY id DESC";
        $result = $conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $fullName = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
                $html .= '<tr nobr="true">
                    <td width="4%" align="center">' . $i . '</td>
                    <td width="7%" align="left">' . $empVal($row['emp_id'] ?? '') . '</td>
                    <td width="11%" align="left">' . $empVal($fullName) . '</td>
                    <td width="10%" align="left">' . $empVal($row['department'] ?? '') . '</td>
                    <td width="10%" align="left">' . $empVal($row['designation'] ?? '') . '</td>
                    <td width="7%" align="left">' . $empVal($row['status'] ?? '') . '</td>
                    <td width="8%" align="left">' . $empDate($row['entry_date'] ?? '') . '</td>
                    <td width="9%" align="left">' . $empVal($row['contact_no'] ?? '') . '</td>
                    <td width="13%" align="left">' . $empVal($row['emp_email'] ?? '') . '</td>
                    <td width="8%" align="left">' . $empDate($row['joining_date'] ?? '') . '</td>
                    <td width="6%" align="left">' . $empVal($row['gender'] ?? '') . '</td>
                    <td width="7%" align="left">' . $empVal($row['employee_type'] ?? '') . '</td>
                </tr>';
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="12" align="center">No employee records found.</td></tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Employee_List.pdf', 'I');
        exit;
        
    } else if ($_GET["type"] == "downloadEmployeeform") {
        $_GET['filename'] = ' ';
        $_GET['pdftype'] = 'onlyheader';
        include('../pdfimp2.php');
        $html = '';

        $empEsc = function ($value) {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };
        $empDate = function ($value) use ($empEsc) {
            if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                return 'NA';
            }
            $ts = strtotime((string) $value);
            return $ts ? date('d-m-Y', $ts) : 'NA';
        };
        $empVal = function ($value) use ($empEsc) {
            $text = trim((string) ($value ?? ''));
            return $text === '' ? 'NA' : $empEsc($text);
        };
        $empJson = function ($row, $keys) {
            foreach ((array) $keys as $key) {
                if (!isset($row[$key]) || $row[$key] === '' || $row[$key] === null) {
                    continue;
                }
                $decoded = json_decode($row[$key], true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            return array();
        };
        $empField = function ($item, $keys, $default = '') {
            foreach ((array) $keys as $key) {
                if (isset($item[$key]) && $item[$key] !== '' && $item[$key] !== null) {
                    return $item[$key];
                }
            }
            return $default;
        };
        $isCanada = function ($country) {
            return trim((string) ($country ?? '')) === 'Canada';
        };
        $isIndia = function ($country) {
            return trim((string) ($country ?? '')) === 'India';
        };
        $getTransitNo = function ($routing) {
            $routing = trim((string) ($routing ?? ''));
            if ($routing === '') {
                return '';
            }
            $parts = explode('-', $routing, 2);
            return $parts[0] ?? $routing;
        };
        $getInstitutionNo = function ($routing) {
            $routing = trim((string) ($routing ?? ''));
            if ($routing === '') {
                return '';
            }
            $parts = explode('-', $routing, 2);
            return isset($parts[1]) ? $parts[1] : '';
        };
        $getEmergencyContact = function ($row) {
            if (!empty($row['branch_name'])) {
                return $row['branch_name'];
            }
            if (!empty($row['emergency_contact'])) {
                return $row['emergency_contact'];
            }
            return '';
        };
        $buildAddressLine = function ($row, $prefix = 'permanent') use ($empVal, $empEsc) {
            $parts = array();
            foreach (array($prefix . '_flat', $prefix . '_city', $prefix . '_state', $prefix . '_pincode', $prefix . '_country') as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value !== '') {
                    $parts[] = $value;
                }
            }
            if (count($parts) === 0 && !empty($row['address'])) {
                return $empVal($row['address']);
            }
            return count($parts) === 0 ? 'NA' : htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
        };
        $sectionHeader = function ($title) {
            return '<table cellpadding="2" border="0.1"><tr style="background-color:#808080;"><td style="width:100%;"><b>' . $title . '</b></td></tr></table><br>';
        };
        $detailRow = function ($label1, $value1, $label2 = '', $value2 = '') {
            if ($label2 === '') {
                return '<tr>
                    <td style="width:20%;background-color:#ADD8E6;"><b>' . $label1 . '</b></td>
                    <td style="width:80%;" colspan="3">' . $value1 . '</td>
                </tr>';
            }
            return '<tr>
                <td style="width:20%;background-color:#ADD8E6;"><b>' . $label1 . '</b></td>
                <td style="width:30%;">' . $value1 . '</td>
                <td style="width:20%;background-color:#ADD8E6;"><b>' . $label2 . '</b></td>
                <td style="width:30%;">' . $value2 . '</td>
            </tr>';
        };

        $empIdEsc = $conn->real_escape_string($_GET['id'] ?? '');
        $plantIdEsc = $conn->real_escape_string($_GET['plant_id'] ?? '');
        $sql = "SELECT * FROM employee WHERE emp_id = '" . $empIdEsc . "'";
        if ($plantIdEsc !== '') {
            $sql .= " AND plant_id = '" . $plantIdEsc . "'";
        }
        $sql .= " ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);

        $html .= '<h1 style="text-align:center;color:#a52a2a;">EMPLOYMENT FORM</h1><br>';

        if (!$result || $result->num_rows === 0) {
            $html .= '<p style="text-align:center;">No employee record found.</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Employee form.pdf', 'I');
            exit;
        }

        $row = $result->fetch_assoc();
        $fullName = trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        $country = $row['permanent_country'] ?? '';
        $canada = $isCanada($country);
        $india = $isIndia($country);
        $pinLabel = 'Postal Code';
        $govIdLabel = $canada ? 'Passport / Driver\'s License No' : 'Government Issued ID';
        $taxLabel = $canada ? 'SIN No' : 'PAN No';
        $taxValue = $row['pan'] ?? '';

        $html .= $sectionHeader('EMPLOYEE DETAILS');
        $html .= '<table border="0.1" cellpadding="5">';
        $html .= $detailRow('Employment Source', $empVal($row['employement_type'] ?? ''), 'Employee Code', $empVal($row['emp_id'] ?? ''));
        $html .= $detailRow('Employee Type', $empVal($row['employee_type'] ?? ''), 'Employee Category', $empVal($row['employee_category'] ?? ''));
        $html .= $detailRow('Employee Name', $empVal($fullName), 'Status', $empVal($row['status'] ?? ''));
        $html .= $detailRow('Email', $empVal($row['emp_email'] ?? ''), 'Contact No', $empVal($row['contact_no'] ?? ''));
        $html .= $detailRow('Emergency Contact', $empVal($getEmergencyContact($row)), 'Emergency Contact Name', $empVal($row['emergency_contact_name'] ?? ''));
        $html .= $detailRow('Emergency Relation', $empVal($row['emergency_contact_relation'] ?? ''), 'Emergency Email', $empVal($row['emergency_contact_email'] ?? ''));
        $html .= $detailRow('Department', $empVal($row['department'] ?? ''), 'Designation', $empVal($row['designation'] ?? ''));
        $html .= $detailRow('Joining Status', $empVal($row['joining_status'] ?? ''), 'Employee Level', $empVal($row['emp_level'] ?? ''));
        if (($row['joining_status'] ?? '') === 'Trainee') {
            $html .= $detailRow('Training Period (Months)', $empVal($row['trainee_period'] ?? ''), 'Joining Date', $empDate($row['joining_date'] ?? ''));
        } elseif (($row['joining_status'] ?? '') === 'Probation') {
            $html .= $detailRow('Probation Period (Months)', $empVal($row['probation_period'] ?? ''), 'Joining Date', $empDate($row['joining_date'] ?? ''));
        } else {
            $html .= $detailRow('Joining Date', $empDate($row['joining_date'] ?? ''), 'Gender', $empVal($row['gender'] ?? ''));
        }
        if (($row['joining_status'] ?? '') === 'Trainee' || ($row['joining_status'] ?? '') === 'Probation') {
            $html .= $detailRow('Gender', $empVal($row['gender'] ?? ''), 'Date Of Birth', $empDate($row['birthdate'] ?? ''));
        } else {
            $html .= $detailRow('Date Of Birth', $empDate($row['birthdate'] ?? ''), 'Nationality', $empVal($row['nationality'] ?? ''));
        }
        if (($row['joining_status'] ?? '') === 'Trainee' || ($row['joining_status'] ?? '') === 'Probation') {
            $html .= $detailRow('Nationality', $empVal($row['nationality'] ?? ''), 'Marital Status', $empVal($row['marital_status'] ?? ''));
        } else {
            $html .= $detailRow('Marital Status', $empVal($row['marital_status'] ?? ''), 'Handicap', $empVal($row['handicap'] ?? ''));
        }
        if (($row['joining_status'] ?? '') === 'Trainee' || ($row['joining_status'] ?? '') === 'Probation') {
            $html .= $detailRow('Handicap', $empVal($row['handicap'] ?? ''), $govIdLabel, $empVal($row['adhar'] ?? ''));
        } else {
            $html .= $detailRow($govIdLabel, $empVal($row['adhar'] ?? ''), $taxLabel, $empVal($taxValue));
        }
        if (($row['joining_status'] ?? '') === 'Trainee' || ($row['joining_status'] ?? '') === 'Probation') {
            $html .= $detailRow($taxLabel, $empVal($taxValue), 'Induction Training', $empVal($row['isinduction'] ?? ''));
        } else {
            $html .= $detailRow('Induction Training', $empVal($row['isinduction'] ?? ''), 'Reference Name', $empVal($row['referenceName'] ?? ''));
        }
        if ($india) {
            $html .= $detailRow('EPF Applicable', $empVal($row['epf_app'] ?? ''), 'UAN No', $empVal($row['pf_no'] ?? ''));
            $html .= $detailRow('ESIC Applicable', $empVal($row['esic_app'] ?? ''), 'ESIC No', $empVal($row['esic_no'] ?? ''));
        }
        if (($row['joining_status'] ?? '') === 'Trainee' || ($row['joining_status'] ?? '') === 'Probation') {
            $html .= $detailRow('Reference Name', $empVal($row['referenceName'] ?? ''), 'Permanent Address', $buildAddressLine($row, 'permanent'));
        } else {
            $html .= $detailRow('Permanent Address', $buildAddressLine($row, 'permanent'));
        }
        $html .= '</table><br>';

        $html .= $sectionHeader('PRESENT ADDRESS DETAILS');
        $html .= '<table cellpadding="4" border="0.1">
            <tr style="background-color:#ADD8E6;">
                <td style="width:20%;text-align:center;"><b>Flat/House No.</b></td>
                <td style="width:20%;text-align:center;"><b>Country</b></td>
                <td style="width:20%;text-align:center;"><b>State</b></td>
                <td style="width:20%;text-align:center;"><b>City</b></td>
                <td style="width:20%;text-align:center;"><b>' . $pinLabel . '</b></td>
            </tr>
            <tr>
                <td style="width:20%;text-align:center;">' . $empVal($row['tempflat_no'] ?? '') . '</td>
                <td style="width:20%;text-align:center;">' . $empVal($row['temp_country'] ?? '') . '</td>
                <td style="width:20%;text-align:center;">' . $empVal($row['temp_state'] ?? '') . '</td>
                <td style="width:20%;text-align:center;">' . $empVal($row['temp_city'] ?? '') . '</td>
                <td style="width:20%;text-align:center;">' . $empVal($row['temp_pincode'] ?? '') . '</td>
            </tr>
            <tr>
                <td style="width:33%;"><b>Telephone(R):</b> ' . $empVal($row['temp_telephone'] ?? '') . '</td>
                <td style="width:33%;"><b>Mobile No.:</b> ' . $empVal($row['temp_mobile'] ?? '') . '</td>
                <td style="width:34%;"><b>Emergency No.:</b> ' . $empVal($row['temp_contact'] ?? '') . '</td>
            </tr>
        </table><br>';

        $html .= $sectionHeader('PERMANENT ADDRESS DETAILS');
        $html .= '<table cellpadding="4" border="0.1">
            <tr style="background-color:#ADD8E6;">
                <td style="width:20%;text-align:center;"><b>Flat/House No./Area</b></td>
                <td style="width:20%;text-align:center;"><b>Country</b></td>
                <td style="width:20%;text-align:center;"><b>State/Province</b></td>
                <td style="width:20%;text-align:center;"><b>City</b></td>
                <td style="width:20%;text-align:center;"><b>' . $pinLabel . '</b></td>
            </tr>
            <tr>
                <td style="width:20%;text-align:center;">' . $empVal($row['permanent_flat'] ?? '') . '</td>
                <td style="width:20%;text-align:center;">' . $empVal($row['permanent_country'] ?? '') . '</td>
                <td style="width:20%;text-align:center;">' . $empVal($row['permanent_state'] ?? '') . '</td>
                <td style="width:20%;text-align:center;">' . $empVal($row['permanent_city'] ?? '') . '</td>
                <td style="width:20%;text-align:center;">' . $empVal($row['permanent_pincode'] ?? '') . '</td>
            </tr>
            <tr>
                <td style="width:33%;"><b>Telephone(R):</b> ' . $empVal($row['permanent_telephone'] ?? '') . '</td>
                <td style="width:33%;"><b>Mobile No.:</b> ' . $empVal($row['permanent_mobile_no'] ?? '') . '</td>
                <td style="width:34%;"><b>Emergency No.:</b> ' . $empVal($row['permanent_contact'] ?? '') . '</td>
            </tr>
        </table><br>';

        $familyList = $empJson($row, array('familyList'));
        $html .= $sectionHeader('FAMILY DETAILS');
        $html .= '<table cellpadding="4" border="0.1">
            <tr style="background-color:#ADD8E6;">
                <td style="width:10%;text-align:center;"><b>Sr No.</b></td>
                <td style="width:25%;text-align:center;"><b>Name</b></td>
                <td style="width:20%;text-align:center;"><b>Relationship</b></td>
                <td style="width:20%;text-align:center;"><b>Occupation</b></td>
                <td style="width:25%;text-align:center;"><b>Date of Birth</b></td>
            </tr>';
        if (count($familyList) === 0) {
            $html .= '<tr><td colspan="5" style="text-align:center;">No records found</td></tr>';
        } else {
            $idx = 1;
            foreach ($familyList as $values) {
                $html .= '<tr>
                    <td style="width:10%;text-align:center;">' . $idx . '</td>
                    <td style="width:25%;">' . $empVal($empField($values, array('name'))) . '</td>
                    <td style="width:20%;">' . $empVal($empField($values, array('relationship'))) . '</td>
                    <td style="width:20%;">' . $empVal($empField($values, array('occupation'))) . '</td>
                    <td style="width:25%;">' . $empDate($empField($values, array('dob'))) . '</td>
                </tr>';
                $idx++;
            }
        }
        $html .= '</table><br>';

        $academics = $empJson($row, array('academics', 'qualiList'));
        $html .= $sectionHeader('ACADEMIC DETAILS');
        $html .= '<table cellpadding="4" border="0.1">
            <tr style="background-color:#ADD8E6;">
                <td style="width:18%;text-align:center;"><b>Degree/Diploma</b></td>
                <td style="width:16%;text-align:center;"><b>Specialization</b></td>
                <td style="width:22%;text-align:center;"><b>Institute/University</b></td>
                <td style="width:12%;text-align:center;"><b>Marks</b></td>
                <td style="width:16%;text-align:center;"><b>From</b></td>
                <td style="width:16%;text-align:center;"><b>To</b></td>
            </tr>';
        if (count($academics) === 0) {
            $html .= '<tr><td colspan="6" style="text-align:center;">No records found</td></tr>';
        } else {
            foreach ($academics as $values) {
                $html .= '<tr>
                    <td style="width:18%;">' . $empVal($empField($values, array('qualification', 'qualification1'))) . '</td>
                    <td style="width:16%;">' . $empVal($empField($values, array('specialization'))) . '</td>
                    <td style="width:22%;">' . $empVal($empField($values, array('institute_name', 'board'))) . '</td>
                    <td style="width:12%;">' . $empVal($empField($values, array('obtained_mark'))) . '</td>
                    <td style="width:16%;">' . $empDate($empField($values, array('year_from'))) . '</td>
                    <td style="width:16%;">' . $empDate($empField($values, array('year_to'))) . '</td>
                </tr>';
            }
        }
        $html .= '</table><br>';

        $languages = $empJson($row, array('languages', 'Languages'));
        $html .= $sectionHeader('LANGUAGES');
        $html .= '<table cellpadding="4" border="0.1">
            <tr style="background-color:#ADD8E6;">
                <td style="width:10%;text-align:center;"><b>SL No</b></td>
                <td style="width:22%;text-align:center;"><b>Language</b></td>
                <td style="width:23%;text-align:center;"><b>Speak</b></td>
                <td style="width:22%;text-align:center;"><b>Read</b></td>
                <td style="width:23%;text-align:center;"><b>Write</b></td>
            </tr>';
        if (count($languages) === 0) {
            $html .= '<tr><td colspan="5" style="text-align:center;">No records found</td></tr>';
        } else {
            $idx = 1;
            foreach ($languages as $values) {
                $html .= '<tr>
                    <td style="width:10%;text-align:center;">' . $idx . '</td>
                    <td style="width:22%;">' . $empVal($empField($values, array('language', 'select_data'))) . '</td>
                    <td style="width:23%;">' . $empVal($empField($values, array('speak'))) . '</td>
                    <td style="width:22%;">' . $empVal($empField($values, array('read'))) . '</td>
                    <td style="width:23%;">' . $empVal($empField($values, array('write'))) . '</td>
                </tr>';
                $idx++;
            }
        }
        $html .= '</table><br>';

        $employeement = $empJson($row, array('employeement', 'employeement_list'));
        $html .= $sectionHeader('EMPLOYMENT HISTORY');
        $html .= '<table cellpadding="4" border="0.1">
            <tr style="background-color:#ADD8E6;">
                <td style="width:22%;text-align:center;"><b>Company Name</b></td>
                <td style="width:18%;text-align:center;"><b>Position</b></td>
                <td style="width:18%;text-align:center;"><b>From</b></td>
                <td style="width:18%;text-align:center;"><b>To</b></td>
                <td style="width:24%;text-align:center;"><b>Reason For Leaving</b></td>
            </tr>';
        if (count($employeement) === 0) {
            $html .= '<tr><td colspan="5" style="text-align:center;">No records found</td></tr>';
        } else {
            foreach ($employeement as $values) {
                $html .= '<tr>
                    <td style="width:22%;">' . $empVal($empField($values, array('company_name'))) . '</td>
                    <td style="width:18%;">' . $empVal($empField($values, array('position'))) . '</td>
                    <td style="width:18%;">' . $empDate($empField($values, array('year_from', 'year_from1'))) . '</td>
                    <td style="width:18%;">' . $empDate($empField($values, array('year_to', 'year_to1'))) . '</td>
                    <td style="width:24%;">' . $empVal($empField($values, array('reason'))) . '</td>
                </tr>';
            }
        }
        $html .= '</table><br>';

        $html .= $sectionHeader('REFERENCES');
        $html .= '<table cellpadding="4" border="0.1">
            <tr><td style="width:100%;text-align:center;background-color:#FFFAFA;">Whether Known To Any Person Employed In This Organization</td></tr>
            <tr style="background-color:#ADD8E6;">
                <td style="width:25%;text-align:center;"><b>Name</b></td>
                <td style="width:25%;text-align:center;"><b>Designation</b></td>
                <td style="width:25%;text-align:center;"><b>Department</b></td>
                <td style="width:25%;text-align:center;"><b>Relationship</b></td>
            </tr>
            <tr>
                <td style="width:25%;text-align:center;">' . $empVal($row['name'] ?? '') . '</td>
                <td style="width:25%;text-align:center;">' . $empVal($row['designation1'] ?? '') . '</td>
                <td style="width:25%;text-align:center;">' . $empVal($row['department1'] ?? '') . '</td>
                <td style="width:25%;text-align:center;">' . $empVal($row['relation'] ?? '') . '</td>
            </tr>
            <tr><td style="width:100%;text-align:center;background-color:#FFFAFA;">Reference (Other Than Relative)</td></tr>
            <tr style="background-color:#87CEEB;">
                <td style="width:25%;text-align:center;"><b>Name</b></td>
                <td style="width:25%;text-align:center;"><b>Present Employment</b></td>
                <td style="width:25%;text-align:center;"><b>Address</b></td>
                <td style="width:25%;text-align:center;"><b>Telephone</b></td>
            </tr>
            <tr>
                <td style="width:25%;text-align:center;">' . $empVal($row['ref_name'] ?? '') . '</td>
                <td style="width:25%;text-align:center;">' . $empVal($row['emp_present'] ?? '') . '</td>
                <td style="width:25%;text-align:center;">' . $empVal($row['ref_add'] ?? '') . '</td>
                <td style="width:25%;text-align:center;">' . $empVal($row['tel_no'] ?? '') . '</td>
            </tr>
        </table><br>';

        $documents = array();
        $docSql = "SELECT documentName, fileName FROM emp_document WHERE emp_id='" . $conn->real_escape_string($row['emp_id']) . "'";
        if ($plantIdEsc !== '') {
            $docSql .= " AND plant_id='" . $plantIdEsc . "'";
        }
        $docResult = $conn->query($docSql);
        if ($docResult && $docResult->num_rows > 0) {
            while ($docRow = $docResult->fetch_assoc()) {
                $documents[] = $docRow;
            }
        }
        if (count($documents) === 0) {
            $legacyDocs = $empJson($row, array('document_list'));
            foreach ($legacyDocs as $legacyDoc) {
                $documents[] = array(
                    'documentName' => $empField($legacyDoc, array('document_type', 'documentName')),
                    'fileName' => $empField($legacyDoc, array('description', 'fileName')),
                );
            }
        }

        $html .= $sectionHeader('DOCUMENTS');
        $html .= '<table cellpadding="4" border="0.1">
            <tr style="background-color:#ADD8E6;">
                <td style="width:50%;text-align:center;"><b>Document Name</b></td>
                <td style="width:50%;text-align:center;"><b>File Name</b></td>
            </tr>';
        if (count($documents) === 0) {
            $html .= '<tr><td colspan="2" style="text-align:center;">No records found</td></tr>';
        } else {
            foreach ($documents as $doc) {
                $html .= '<tr>
                    <td style="width:50%;text-align:center;">' . $empVal($doc['documentName'] ?? '') . '</td>
                    <td style="width:50%;text-align:center;">' . $empVal($doc['fileName'] ?? '') . '</td>
                </tr>';
            }
        }
        $html .= '</table><br>';

        $salaryList = $empJson($row, array('salary_list'));
        if (count($salaryList) > 0) {
            $html .= $sectionHeader('LAST 3 COMPANY SALARY DETAILS');
            $html .= '<table cellpadding="4" border="0.1">
                <tr style="background-color:#ADD8E6;">
                    <td style="width:25%;text-align:center;"><b>Company Name</b></td>
                    <td style="width:25%;text-align:center;"><b>Department</b></td>
                    <td style="width:25%;text-align:center;"><b>Inhand Salary</b></td>
                    <td style="width:25%;text-align:center;"><b>CTC per Annum</b></td>
                </tr>';
            foreach ($salaryList as $values) {
                $html .= '<tr>
                    <td style="width:25%;text-align:center;">' . $empVal($empField($values, array('last_company_name'))) . '</td>
                    <td style="width:25%;text-align:center;">' . $empVal($empField($values, array('department2'))) . '</td>
                    <td style="width:25%;text-align:center;">' . $empVal($empField($values, array('basic'))) . '</td>
                    <td style="width:25%;text-align:center;">' . $empVal($empField($values, array('per_annum'))) . '</td>
                </tr>';
            }
            $html .= '</table><br>';
        }

        $html .= $sectionHeader('BANK ACCOUNT DETAILS');
        if ($canada) {
            $html .= '<table cellpadding="4" border="0.1">
                <tr style="background-color:#ADD8E6;">
                    <td style="width:20%;text-align:center;"><b>Bank Name</b></td>
                    <td style="width:20%;text-align:center;"><b>Transit #</b></td>
                    <td style="width:20%;text-align:center;"><b>Institution #</b></td>
                    <td style="width:20%;text-align:center;"><b>Account #</b></td>
                    <td style="width:20%;text-align:center;"><b>Account Type</b></td>
                </tr>
                <tr>
                    <td style="width:20%;text-align:center;">' . $empVal($row['bank_name'] ?? '') . '</td>
                    <td style="width:20%;text-align:center;">' . $empVal($getTransitNo($row['ifsc_neft'] ?? '')) . '</td>
                    <td style="width:20%;text-align:center;">' . $empVal($getInstitutionNo($row['ifsc_neft'] ?? '')) . '</td>
                    <td style="width:20%;text-align:center;">' . $empVal($row['acc_no'] ?? '') . '</td>
                    <td style="width:20%;text-align:center;">' . $empVal($row['acc_type'] ?? '') . '</td>
                </tr>
            </table>';
        } else {
            $html .= '<table cellpadding="4" border="0.1">
                <tr style="background-color:#ADD8E6;">
                    <td style="width:20%;text-align:center;"><b>Account Number</b></td>
                    <td style="width:20%;text-align:center;"><b>Bank Name</b></td>
                    <td style="width:20%;text-align:center;"><b>Branch Name</b></td>
                    <td style="width:20%;text-align:center;"><b>IFSC/NEFT Code</b></td>
                    <td style="width:20%;text-align:center;"><b>Account Type</b></td>
                </tr>
                <tr>
                    <td style="width:20%;text-align:center;">' . $empVal($row['acc_no'] ?? '') . '</td>
                    <td style="width:20%;text-align:center;">' . $empVal($row['bank_name'] ?? '') . '</td>
                    <td style="width:20%;text-align:center;">' . $empVal($row['branch_name'] ?? '') . '</td>
                    <td style="width:20%;text-align:center;">' . $empVal($row['ifsc_neft'] ?? '') . '</td>
                    <td style="width:20%;text-align:center;">' . $empVal($row['acc_type'] ?? '') . '</td>
                </tr>
            </table>';
        }

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Employee form.pdf', 'I');
        exit;
    }
    else if ($_GET["type"] == "appointment") {
        //$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table>
                    <tr>
                        <td style="width:20%;text-align:center;">An ISO 9001-2015 Company <br> CIN U24100 <br> MH2005 <br>PTC 156121</td>
                        <td style="width:80%;text-align:center;font-weight:bold;">
                            <span style="font-family:times;font-size:17px;">DNS FINE CHEMICALS & LABORATORIES(P) Ltd.</span><br>
                            <span style="font-family:times;font-size:14px;">|REAL ESTATE|SPECIALIY CHEMICALS & API\'S|SIPOREX SOLUTIONS|</span><br>
                            <span style="font-size:9px;">Corporate Off:-Ghanshyam Krupa,R.B.Kadam Marg,Bhatwadi,Ghatkopar(W),Mumbai 400084,india.<br>Tel-+91-22-2515/25133164 Fax-+91-22-2509-1719 Mob-9967606855/57<br><b>Email-sales@dnsfine.com Website-www.dnsfine.com/www.dnsgrp.com</b></span>
                        </td>
                    </tr>
                </table>
                ';
            $this->SetY('5'); 
            $this->writeHTML($table, true, false, false, false, '');  
            }
            public function Footer() {
                
            }
        }
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetHeaderData(10,5,10,10);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT,35, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage();
        $pdf->SetY(35);
        $pdf->SetFont ('times', '', '12');
        $html= "";
        $id = $_GET['id'];
        $sql = "SELECT * FROM employee WHERE emp_id='$id'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='
                <h3 style="text-align:center;">APPOINTMENT LETTER</h3>
                <table cellpadding="3" style="text-align:left; width:100%;">
                    <tr>
                        <td style="width:100%; text-align:right;"><b>Date:</b><label>'.$row['joining_date'].'</label></td>
                    </tr>
                    <tr>
                        <td colspan="3"><b>To,</b></td>
                    </tr>
                    <tr>
                        <td style="width:50%;"><b>'.$title.' '.$row['emp_name'].' <br>'.$row['address_temporary'].'</b></td>
                    </tr>
                </table>
                <h3>Dear '.$title.' '.$row['emp_name'].',</h3><br>
                 <table>
                    <tr>
                        <td style="width:100%; text-align:justify;">We are very pleased to inform you that you have selected to work as “<b> '.$row["department"].'-'.$row['designation'].'</b> ” with us. The terms & conditions are as follows.</td>
                    </tr>
                    <tr>
                        <ol>
                            <li>You will work as Quality Assurance Trainee looking after all the documentation of plant, auditing the process caried in plant on daily basis, ensuring the process been followed from start to end, report preparation daily as and when required.</li>
                            <li>You will provide solution to respective dept post conducting the required audits, whenever it is necessary; at the same time, you will provide process technology for assuring the process been adhered in the plant.</li>
                            <li>You will be working in accordance with policies, which are made by management.</li>
                            <li>You are posted at present at <b>DNS Fine Chemicals & Laboratories Pvt Ltd</b> situated at W-15, MIDC Badlapur(E), Thane-421503. </li>
                            <li>Initially, you will be based in the Badlapur Thane area. The job is transferable, and depending on the exigencies of business, we may transfer or relocate you elsewhere in the country. The job will involve touring in India if required abroad to prompt business of company.</li>
                            <li>You will provide complete technology for <b>';$html.=''.$row['department'].'';$html.='</b> of the all the products. You will generate & use it for company’s benefit.</li>
                            <li>This is composite job. While you will be in employment of this company, we shall be free to loan your services to or concurrently utilize them otherwise for any of the establishments of our associate, subsidiary or sister concerns.</li>
                            <li>You shall devote the whole of your time, energy and attention to our business, as directed by us and you shall not devote or apply yourself to any other work or activity either as a source of income or so as to interfere in any way the performance of your duties. In particular, you shall not in any way associate yourself with any political activity, local or otherwise.</li>
                            <li>In all your communication with the outside world, you will represent us only to the extent you have been specifically authorized. When expressing outside your personal views on any matter concerning/ affecting us, you will abundantly clarify that your views may not necessarily our thinking or views in that behalf. </li>
                            <li>With your confirmation, the employer-employee relationship shall be subject to termination by either side giving to the other written notice of not less than 30 days, provided that, at our option, any Privilege Leave then due shall be adjusted with notice period. Besides, we shall have the further option of paying you in lieu of notice.</li>
                            <li>In case any difference/ dispute arising out of or in connection with the terms and conditions of your service, leads to litigation. It shall be subject to jurisdiction of the appropriate Court exclusively in the district of Thane & State of Maharashtra.</li>
                            <li>The company will pay Rs.';
                            $sql1 = "SELECT * FROM salary_annexure WHERE emp_id='".$_GET["id"]."' Group By emp_id"; 
                            $result1 = $conn->query($sql1);
                            if ($result1->num_rows > 0) {
                                while ($row1 = $result1->fetch_assoc()) {
                                $html.='<b>'.$row1['ctc'].'/Month</b>';
                                }
                            }//Rs. 10,500/Month</b>
                            $html.='&nbsp;(all inclusive) as remuneration for your services to company. Your salary will be paid in every 7th to 10thday of month. Your joining date in this organization will be from &nbsp;<b>'.$row['joining_date'].'</b>&nbsp;We look forward to your joining in our company.</li>
                            <li>You will be strictly liable & entitled to keep all the company information confidential. If anyone found any sharing such information with any third party the company have all the rights to terminate his or her service & you will be liable to legal action at any point of time without any prior notice. </li>
                        </ol>
                    </tr>
                    <tr>
                        <td style="width:100%; text-align:justify;">We hope you will give us your fullest co-operation & we hope to have long lasting & fruit full relationship with you. </td>
                    </tr>
                    <tr>
                        <td style="width:100%; text-align:justify;">Please sign one copy of the letter as acknowledgment of appointment. </td>
                    </tr>
                    <tr>
                        <td style="width:100%; text-align:justify;">Thanking you,</td>
                    </tr>
                    <tr>
                        <td style="width:100%; text-align:justify;">Sudhir D. Sawant</td>
                    </tr>
                    <tr>
                        <td style="width:100%; text-align:justify;">Managing Director </td>
                    </tr>
                </table>';
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('appintment Letter.pdf', 'I');
    } else if ($_GET["type"] == "downloadsalaryslip") {
        //$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {}
            public function Footer() {}
        }
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetHeaderData(10,5,10,10);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT,35, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
       
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage();
        $pdf->SetY(5);
        $pdf->SetFont ('times', '', '12');
        $html= "";
        $date = $_GET['month'];
        $time=strtotime($date);
        $month=date("m",$time);
        $year=date("Y",$time);
        $output = Array();
        $sql = "SELECT * FROM employee WHERE status='active' AND isAppointment = 'active'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                    $html.='<h3 style="text-align:center;">Salary Slip</h3>
                    <table>
                        <tr>
                            <td style="width:50%;">DNS Fine CHemicals & laboretories Pvt Ltd<br>
                            W-15,Mankivali MIDC near telephone<br>
                            Exchange badlapur East 421503</td>
                            <td style="width:50%;">Salary Slip Month Of May 2021</td>
                        </tr>
                        <div></div>
                        <tr>
                            <td style="width:50%;">Employee Name:'.$row["firstname"].'<br>
                            Department:'.$row['department'].'<br>Salary for May 2021</td>
                            <td style="width:50%;">Designation:'.$row['designation'].'<br>Days:</td>
                        </tr>
                    </table>
                    <div></div>';
                    $sql1 = "SELECT * FROM salary_annexure WHERE emp_id='".$conn->real_escape_string($row["emp_id"])."' AND status='active'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["gross"] = $row1['gross'];
                            $row["p_tax"] = $row1['p_tax'];
                            $row["canteen"] = $row1['canteen'];
                            $row["other"] = $row1['other'];
        
                        // $sql2 = "SELECT COUNT(id) as present_days FROM attendence WHERE emp_id='".$conn->real_escape_string($row["emp_id"])."' AND status='pending'";
                        // $result2 = $conn->query($sql2);
                        // if ($result2->num_rows > 0) {
                        //     while ($row2 = $result2->fetch_assoc()) {
                        //         $row['present_days'] = $row2["present_days"];
                        //     }
                        // } else {
                        //     $row['present_days'] = 0;
                        // }
        
                        // $sql2 = "SELECT COUNT(id) as shortleave FROM leave_application WHERE emp_id='".$conn->real_escape_string($row["emp_id"])."' AND leave_type='Short Leave' AND status='pending'";
                        // $result2 = $conn->query($sql2);
                        // if ($result2->num_rows > 0) {
                        //     while ($row2 = $result2->fetch_assoc()) {
                        //         $row['shortleave'] = $row2["shortleave"];
                        //     }
                        // } else {
                        //     $shortleave = 0;
                        // }
                        // $sql2 = "SELECT COUNT(id) as halfday FROM leave_application WHERE emp_id='".$conn->real_escape_string($row["emp_id"])."' AND leave_type='Half Day' AND status='pending'";
                        // $result2 = $conn->query($sql2);
                        // if ($result2->num_rows > 0) {
                        //     while ($row2 = $result2->fetch_assoc()) {
                        //         $row['halfday'] = $row2["halfday"];
                        //     }
                        // } else {
                        //     $row['halfday'] = 0;
                        // }
                
                        // $sql2 = "SELECT count(DATEDIFF(leavefrom, leaveto)) as total_leave FROM `leave_application` WHERE emp_id='SBHR001' AND status='pending' AND leave_type NOT IN ('Short Leave', 'Half Day')";
                        // $result2 = $conn->query($sql2);
                        // if ($result2->num_rows > 0) {
                        //     while ($row2 = $result2->fetch_assoc()) {
                        //         $row['total_leave'] = $row2["total_leave"];
                        //     }
                        // } else {
                        //     $row['total_leave'] = 0;
                        // }
                        
                        // $date = strtotime($_GET['month']);
                        // $month=date("m",$date);
                        // $year=date("Y",$date);
                        // $row['month_days'] = cal_days_in_month(CAL_GREGORIAN, date("m", $date), date("Y", $date));
                        // $row['working_days'] = calculateWorkingDaysInMonth(date("Y", $date),date("m", $date));
                        // $row['absent_days'] = $row['working_days'] - $row['present_days'];
                        // $row['salary_per_day'] = $row["gross"] / $row['month_days'];
                        // $row['paid_days'] = $row['present_days']*1 + $row['total_leave']*1 ;
                        // $row['earned_gross'] = $row['salary_per_day'] * $row['paid_days'];
                        
                        // $row['basic'] = $row['earned_gross'] * 0.40 ;
                        // $row['hra'] = $row['basic'] * 0.40;
                       
                        // $row['conveyance'] = $row['earned_gross'] * 0.10;
                        // $row['medical'] = $row['earned_gross'] * 0.10;
                        // $row['educational'] = $row['earned_gross'] * 0.10;
                        // $row['special'] = $row['earned_gross'] * 0.10;
                        // if ($row["isPF"] == 'Yes') {
                        //     $row['pf'] = $row['basic'] * 13.601;
                        // } else {
                        //     $row['pf'] = 0;
                        // }
                        // if ($row["isESIC"] == 'Yes') {
                        //     $row['esic'] = $row['earned_gross'] * 0.075;
                        // } else {
                        //     $row['esic'] = 0;
                        // }
                        // $row['deduction'] = $row['pf'] + $row['esic'] + $row['p_tax']+ $row['canteen']+ $row['other'];
                        // $row['inhand'] = $row['earned_gross'] - $row['deduction'];
                        // $output[] = $row;
                        $html.='
                        <table cellpadding="3" border="1">
                            <tr style="font-weight:bold;">
                                <td style="width:30%;">Particulars</td>
                                <td style="width:20%;">Actual</td>
                                <td style="width:20%;">Deduction</td>
                                <td style="width:30%;"></td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Fixed Basic Pay</td>
                                <td style="width:20%;"></td>
                                <td style="width:20%;">PF</td>
                                <td style="width:30%;"></td>
                            </tr>
                            <tr>
                                <td style="width:30%;">House Rent Allowance</td>
                                <td style="width:20%;"></td>
                                <td style="width:20%;">ESIC</td>
                                <td style="width:30%;"></td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Conveyance Allowance</td>
                                <td style="width:20%;"></td>
                                <td style="width:20%;">TDS</td>
                                <td style="width:30%;"></td>
                            </tr>
                            <tr>
                                <td style="width:30%;">other</td>
                                <td style="width:20%;"></td>
                                <td style="width:20%;">P.TAX</td>
                                <td style="width:30%;"></td>
                            </tr>
                            <tr>
                                <td style="width:30%;">Washing Allowance</td>
                                <td style="width:20%;"></td>
                                <td style="width:20%;">other</td>
                                <td style="width:30%;"></td>
                            </tr>
                            <tr style="font-weight:bold;">
                                <td style="width:30%;">Total CTC</td>
                                <td style="width:20%;"></td>
                                <td style="width:20%;">Net Take(Home)</td>
                                <td style="width:30%;"></td>
                            </tr>
                        </table>';
                        }
                    }
                    $html.='
                        <div></div>
                        <table>
                            <tr>
                                <td style="width:50%; font-weight:bold;"> Net Amount:</td>
                                <td style="width:50%;"></td>
                            </tr>
                            <tr>
                                <td style="width:50%;"></td>
                                <td style="width:50%;">Authorised Signatory</td>
                            </tr>
                        </table>
                        ';
                //     }
                // }
            }
        }
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('SalarySlip.pdf', 'I');
    
    }else if ($_GET["type"] == "downloadnewappointment_old") {
        //$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
         class MYPDF extends TCPDF {
            public function Header() {
                $table.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table>
                    <tr>
                        <td style="width:80%;text-align:center;font-weight:bold;">
                            <span style="font-family:times;font-size:17px;">Amardeep Pharmaceuticals Pvt. Ltd</span><br>
                            <span style="font-size:9px;">Location:Plot Number A 2/8, Phase-1, Near UPL, Daman Ganga Road, GIDC, <br>VAPI, Gujarat 396195, India.<br></span>
                        </td>
                        <td style="width:20%;">';
                            //$this->Image('@'.file_get_contents('../gmptotal/upload/User/DNS.jpg'),162,8,39);
                         // $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/assets/amardeep-logo.jpg'),160,10,30);
                             //$this->Image('@'.file_get_contents('http://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/dns.png'),169,8,25);
                            $table.='
                        </td>
                    </tr>
                </table>';
                $this->SetY('5'); 
                $this->writeHTML($table, true, false, false, false, '');  
            }
            public function Footer() {
                
            }
        }
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetHeaderData(10,5,10,10);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT,35, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage();
        $pdf->SetY(35);
        $pdf->SetFont ('times', '', '12');
   
        $html.="";
        
           
            $html.='
            <h3 style="text-align:center;">APPOINTMENT LETTER</h3>
                <table>
                    <tr>
                        <td style="width:100%; font-weight:bold;"> To,<br>
                   
                        </td>
                    </tr>
                    <div></div>
                    <tr>
                        <td><b>Dear,</b></td>
                     </tr>
                    <tr>
                        <td style="width:100%;">Further to our letter offer / interview dated <b>'.date('d/m/Y',strtotime($row['joining_date'])).'</b>, we are pleased to inform you that you are hereby appointed as <b>“'.$row['designation'].'”</b> in <b>AMARDEEP FINE CHEMICALS & LABORATORIES(P) Ltd</b> based in Pune HQ as per term and conditions discussed and agreed upon as under:</td>
                    </tr>
                        <ol>
                            <li>This appointment is effective from &nbsp;&nbsp; the date of your joining the Organization</li>
                            <li><b>Probation Period:</b></li></ol>
                                <ul>
                                    <li>You will be placed on <b> probation</b> for a period of <b>six months></b></li>
                                    <li>During probation, the notice period for termination / resignation will be 45 days from either side, if notice period has not served by employee or if terminated for any reason by company employee has to pay <b>45 days</b> salary as a compensation ,you are agreed that if  because of in disciplinary  behaviors ,misbehavior with any staff ,abusive behavior or any damage to company property or reputation by any means during probation company may ask you to leave with immediate effect in that case your dues will not be cleared and it will be compensated as loss of company . </li>
                                    <li>If Employee is leaving job without notice during probation or during training period company is not liable to pay any dues or pending salary it will be compensated from employee as training expenses.</li>
                                    <li>Any absenteeism without notice or prior permission during Probation/Training period for more than 4 days except medical reason or any other emergency reason, shall be proved with evidences; your services will be ending without notice and company will not liable to pay any kind of dues or pending salaries and this will be considered as irresponsible behaviors.</li>
                                </ul>
                            <li><b>Confirmation of  Employment :</b></li>
                                <ul>
                                    <li>After successful completion of your probation, you will be confirmed in writing as a permanent employee of the company. You will be entitled to statutory and service benefit and be governed by discipline and other rules existing or many come into existence from time to time, as and when applicable as per rules of the Company and such other benefits as applicable to employed in force from time to time to the location / place wherever you are working. The decision are totally depend on the management and not mandatory to company</li>
                                    <li>Your future increments or promotion or any other salary increase shall be based on merit and performance considering your periodic and consistent overall performance, business condition and other  parameter fixed from time to time at the discretion of the management and shall not be consider merely as a mattered right.</li>
                                    <li>During the period of service with the company, you shall not indulge and/ or take part in any activity of formation of council and / or association or become a member being part of management staffs which are found to be determine in the interest of the company in any way. Such an action shall be deemed as infringement to service condition of the company and amount to causing damaged to its interest and shall call or disciplinary action being taken against you, as it may deem fit and appropriate.</li>
                                    <li>You shall retire from the service of the company on attending 58 years of age.</li>
                                    <li>During the tenure of your services, you will wholly devote yourself to the work assigned to you and will not undertake any other employment either on fu11 or part time basis,or undertake any similar kind of business which company runs, without prior permission of the company in writing. Any contravention of this condition will entail termination of your services from the company</li>
                                </ul>
                            <li><b>Legal:</b></li>
                                <ul>
                                    <li>Your services are liable to be transferred or loaned or assigned with / without transfer, wholly or partially, from one department to another or to office /branch and vice-versa or office branch to another office/ branch of an associate company, existing or to come into existence in future or any of the company’s branch office or location anywhere in India or abroad or any other concern where this company has any interest. In such case, you will abide by responsibilities expressly vested or implied or communicated and shall follow rules and regulations of the department / office established, jointly or separately, without any compensation or extra remuneration or provision of accommodation. You thereupon, may be governed by service condition and other terms of the said concern as may be applicable</li>
                                    <li>The above said clause (i) will not give you any right to claim employment in any associate or sister concern or ask for a common seniority with the employee of the sister associate concern.</li>
                                    <li>In the event you are absent from duty without information or permission of leave for more than 4 days or you overstay your sanctions leave more than 4 days , the management will treat you as having voluntarily abandons the services of the company and you cannot claim any dues or pending salaries from company .</li>
                                </ul>   
                            <li><b>Your service liable to be terminated at any time:</b></li>
                                <ul>
                                    <li>During probation or after confirmation, In case you are found to be medically unfit by the Company\'s Authored Medical practitioner, on examination.</li>
                                    <li>As and when the company come to know of any conviction by the Court of Law during the tenure of your service with us or conviction and / or any bad record in the past under the previous employer, or because of your giving false information at the time of your appointment or cancelled any material information or given any false details in the applicable form or otherwise as regard age, education qualification, experience, salary etc.</li>
                                    <li>if you are found to be not possessing desired qualification which do not conform to custom authority and / govt. regulation as may to require from time to time and necessary for continuation of business or its exigencies or on account of redundancy.</li>
                                    <li>In any circumstance, your act found harmful for company reputation and company assets or employees.</li>
                                    <li>If you found to be involved in any other employment, directorship, business related to company nature of business, involved in commercial or commission relation with client.</li>
                                    <li>If any of outside person in your relation, family member, friends are found to be interfering in your work or in your company matters, or threatening to company employee’s management on your behalf this will be considered as indiscipline.</li>
                                    <li>If it is found that you are not performing your duties as per your job responsibilities or you are not completing the given task, you refused to work, you refused to give support to client, you refused to perform your duty</li>
                                </ul>
                            <li>You will keep the company informed of any change in our residential address that may happen during the course of employment of your service with the company.</li>
                            <li>All document, plans, drawing, prints trade secrets, technical information, report, statement, corresponding, source code, database, website codes or any other software information etc, written and also information and instruction that pass through you or come to your knowledge shall be treated as confidential. You shall not utilize them for your own use or disclose to other person during or after your employment. During the course of employment with the company, you will acquire, gain generate, gather and development knowledge of and be given access to business information about product activities, know-how, methods for refinement and business secrets and other information concerning the products/ business of the company, and hereinafter called the "SECRETS". You will be liable for prosecution for damages for divulgence, sharing or parting any of such information during course of employment and on cessation for at least 2 years period.</li>
                            <li>You shall faithfully and to the best of your ability perform your duties the may be entrusted to you from time to time by the management. You will be bound by rules, regulation and orders promulgated by the management in relation to conduct, discipline and policy matter, You will not give out to by one, by word of mouth or otherwise, particulars of our business or administrative or organization matters of a confidential nature which may be your privilege to know by virtue of your being our employee.</li>
                            <li>While you are in employment of the company, you may be given or handed over company property and/ or equipment for official use and you shall take care of them including their upkeep. On cessation of employment with the Company, you shall return all documents, books, papers relating to the affairs of the Company, purchase with the Companies money, which may have come to you, and also any property of the company in your possession.</li>
                            <li>Any balance of advance or loan taken by you from the Company, shall be fully recovered from your salary and any other legal dues Including Gratuity, at the time your leaving the services in your possession.</li>
                            <li>While working as an employee If you enter Into any business transaction with any party on behalf of the company within your permissible limits, It shall be your responsibility to ensure recovery of outstanding. If any outstanding remains at the time of leaving the service of the company, It shall be your responsibility to recover for remittance to the company before you proceed to settle your legal duel in full and final statement of your account.</li>
                            <li>The company is obliged to deduct Income Tax at source as per provision of Income Tax Act/ Rules. Accordingly, you are required to submit all required proof of permitted saving / investment and other details from time to time to enable the company to comply with the provisions of law. In the event of non compliance by you as aforesaid if the company is required to pay any interest or payment under income Tax Act, it shall the amount as may be paid or payable from your salary or other payment and you shall allow the company to amply within the company to comply the prevision of the law. In the event of non compliance by you as aforesaid if the company is required to pay any interest or payment under Income Tax Act, it shall deduct to amount as may in paid or payable from your salary or other payment and you shall the company to comply with these requirements without objection.</li>
                            <li><b>Salary Deductions or compensation recovery :</b></li>
                                <ol>
                                    <li>Company has right reserved for deducting or keeping on hold or recovering loses or recovering as compensation of losses, or expenses in following circumstances.</li>
                                        <ul>
                                            <li>If you are not serving notice period and leaving company during notice period </li>
                                            <li>Company is paying for holidays also as per norms but if you are leaving company during training period in first two months without notice period your all holiday payment will be recovered by company. </li>
                                            <li>You will not liable to get any dues or salaries if you are not completing 3 Months tenure in company </li>
                                            <li>Salary will be paid to employee on bank accounts on or before 7th of every month however if any financial crisis circumstance arise it may get delayed by 30 days to 45 days and will be paid immediately on crisis overcome. </li>
                                        </ul>
                                </ol>
                            <li><b>Notice Period : </b></li>
                                <ul>
                                    <li>Your Notice Period for resignation and relieving is of 45 days however if you are working on any project or module independently or in clients support you will be relieved in 45 days or after completion of or after handing over complete status to any other employee whichever is later </li>
                                    <li>If there is any ongoing project or any clients pending work is going on if you are leaving company without any intimation or without completing notice period company may file prosecution against you in court of law for recovering company losses particular to project assigned to you ,it also involves if any refunds to be given to client because of your non support to client.</li>
                                </ul>
                            <li><b>Resignation and Relieving :</b></li>
                                <ul>
                                    <li>Whenever you are willing to leave the job you have to tender your resignation in writing or on companies official email ID, resignations without any acknowledgement from appropriate authority of company will be considered as invalid. </li>
                                    <li>Your notice period will be counted from the date of acceptance of resignation</li>
                                    <li>During Notice period you are not allowed to take leaves except medical or extreme emergency leave and you have to submit evidences for the same, the leave days will not be considered in notice period day count.</li>
                                    <li>If you wish to continue the job and change your decision of resignation you have to send application to management in writing for the same, subjected to acceptance and approval by management.</li>
                                    <li>Your one month salary (First 30 days salary from the date of start of notice period ) will be retained by company and will be paid as Post dated cheque of 30 days from the date of relieving, you have to give support related to your work after relieving if it was observed that you are not giving support  the given cheque will stands to cancel and this will be considered as non support recovery of the work loss by company. </li>
                                    <li>If employee leave company within 8th month from joining for any reason the payment of paid holiday and paid weekly off will be deducted from final settlement.</li>
                                </ul>
                            <li>All disputes arising out of this letter will be subject to the jurisdiction of the Pune Court. And that to courts tribunals and/or authorities at Pune shall have or pertaining to this contract of employment, irrespective of your working HQ being elsewhere at that times. You are requested to return the enclosed copy duly signed as a token of your acceptance of the term and condition of your employment.</li>
                            <li>Hope that this will be the beginning of a long and successful career with us.</li>
                        </ol>
                    <tr>
                        <td style="width:100%;"><b>Yours Faithfully,</b></td>
                    </tr>
                    <tr>
                        <td style="width:50%;">AMARDEEP FINE CHEMICALS & LABORATORIES(P) Ltd <br><br>Authorized Signatory</td>
                        <td style="width:50%; text-align:center;">I accept and agree to the above terms & conditions <br>(Signature of an Employee)</td>
                    </tr>
                </table>';
                
            
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('appintment Letter.pdf', 'I');
        
          
 
        
        
        
    }else if ($_GET['type'] == 'downloadManagementEmployee') {
        $_GET['filename'] = 'Employees List'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html.="";
        $html.='<table cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:15%;">Emp Id</td>
                        <td style="width:15%;">Name</td>
                        <td style="width:20%;">Department</td>
                        <td style="width:15%;">Designation</td>
                        <td style="width:20%;">Email</td>
                        <td style="width:15%;">Status</td>
                    </tr>';
                    $sql = "SELECT * FROM employee";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["details"] = json_decode($row["details"]);
            $html.='<tr>
                        <td>'.$row['emp_id'].'</td>
                        <td>'.$row['lastname'].'</td>
                        <td>'.$row['department'].'</td>
                        <td>'.$row['designation'].'</td>
                        <td>'.$row['email'].'</td>
                        <td>'.$row['status'].'</td>
                    </tr>';
                }
            }
            $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Employees List.pdf', 'I');
        
    }
    else if ($_GET["type"] == "download_salary_annexure") {
  
        $_GET['filename'] = 'Leave Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
      
        $html="";
        $sql = "SELECT * FROM employee  WHERE emp_id='".$_GET['emp_code']."' 
        and plant_id= '".$_GET['plant_id']."'";
      
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		
    		     $salary_details;
    		   $date =date('Y-m-d',strtotime($row['interview_date']));
    		   $sql1="SELECT * From salary_annexure WHERE emp_id='".$row["id"]."'";
    		  
    		   $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		   $salary_details_id = $row1[id];
            		   $s_id=$row1['id'];
            		    //$ctc=$row1['ctc']*12;
            		    //$hra=$row1['hra']*12;
                        //$basic=$row1['basic']*12;
                       // $specialallowance=$row1['specialallowance']*12;
                        //$education=$row1['specialallowance']*12;
                        $conveyance=$row1['conveyance']*12;
                        $gross=$row1['gross']*12;
                        $ptax=$row1['ptax']*12;
                        $deduction=$row1['deduction']*12;
                        $bonus=$row1['bonus']*12;
                        $contribution=$row1['contribution']*12;
                        $medical=$row1['medical']*12;
                        $c=$bonus+$contribution+$medical;
                        $c_month=$row1['bonus']+$row1['contribution']+$row1['medical'];
                        $net_total=$row1['gross']-$row1['deduction'];
                        $net_annual=$gross-$deduction;
                        $ac_annual= $gross+$deduction;
                        $ac=$row1['gross']+$row1['deduction'];
            		}}
            		
        
          
       $html.='
      
            <table cellpadding="1" border="0.1">
        
      <tr style="text-align:center;background-color:#DDDAD9;">
        <th style="width:100%;" align="center"><b>Salary Authorization Form (SAF):</b></th></tr>
       <tr style="text-align:center;background-color:#DDDAD9;">
         <th style="width:100%;"align="cenetr"><b>Annexure A to Appointment Letter dated:</b></th> 
        </tr>
         </table>
          <table style="width:100%;"cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;"><b>Name</b></td>
          <td style="width:50%;">'.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].'</td>
         </tr>
         
         <tr>
         <td style="width:50%;"><b>Designation</b></td>
          <td style="width:50%;">'.$row['designation'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Department</b></td>
          <td style="width:50%;">'.$row['department'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Location</b></td>
          <td style="width:50%;">'.$row['location'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Probation Period </b></td>
          <td style="width:50%;"><b>'.$row1[''].'</b></td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Monthly CTC Rs.</b></td>
          <td style="width:25%;"><b>'.$salary_details['total_ctc'].'</b></td>
          <td style="width:25%;"><b></b></td>
          </tr>
          <tr>
         <td style="width:100%;"><b>Cost To Company (CTC)</b></td>
         </tr>
         <tr>
         <th style="width:50%;"><b>Salary Heads </b></th>
          <th style="width:25%;"><b>INR Per Month </b></th>
          <th style="width:25%;"><b>INR Per Annum</b></th>
         </tr>';
         
         $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= 
         '" . $salary_details["id"]."' AND  salary_group ='Earnings' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:50%;">'.$row2['description'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
            		}
            		
            	$html.=' 	<tr>
          <td style="width:50%;"><strong>Gross Salary</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['total_earnings'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['total_earnings'] * 12).'</strong></td>
         </tr>
         <tr>
          <td style="width:50%;"><strong>Employer Benefits </strong></td>
          <td style="width:25%; text-align:right;"></td>
          <td style="width:25%; text-align:right;"></td>
         </tr>
         ';
            		
                }
           
               $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='CTC Calculations' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:50%;">'.$row2['description'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
            		}
            		
            	$html.=' 	<tr>
          <td style="width:50%;"><strong>Fixed CTC</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['total_ctc'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['total_ctc'] * 12).'</strong></td>
         </tr>
          
         	<tr>
          <td style="width:50%;"><strong>Employee Deduction  </strong></td>
          <td style="width:25%; text-align:right;"></td>
          <td style="width:25%; text-align:right;"></td>
         </tr>
         ';
            		
                }
           
          
                       $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='Deductions' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:50%;">'.$row2['description'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
            		}
            		
            	$html.=' 	<tr>
          <td style="width:50%;"><strong>Total Deductions</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['ctc_deductions'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['ctc_deductions'] * 12).'</strong></td>
         </tr>';
            		
                }
                
        	$html.=' 	<tr>
          <td style="width:50%;"><strong>Net Take Home Salary after PF & Tax deduction</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['take_home_salary'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['take_home_salary'] * 12).'</strong></td>
         </tr>';
       
    	
          
          $html.='  </table>
          <div></div>
         For,  <b>'.$row['company_name'].'</b>';
         }
         }
        
          $html.='  <br><br>
          <table>
        <tr>
     <td style="width:100%; font-size: 10px;"><b>Mr.Dharmendra Patel</b> </td>
      </tr>
      <tr>
      <td style="width:100%;  font-size: 10px;"><b>Managing Director</b></td>
       </tr>
       <tr>
       <td style="width:100%;  font-size: 10px;"><b>Vapi & Panoli Unit</b></td>
       </tr>
        </table>
       
         <h4 style="text-align: center;">Employee Acknowledgement</h4>
         <table>
         <tr>
       <td style="width:100%;font-size: 10px;"> I accept all terms and Conditions of the company as stipulated above.</td>
        </tr>
        <tr>
       <td style="width:100%;font-size: 10px;"> I hereby accept the position on the terms and conditions of employment offered.</td>
      </tr>
          </table><div></div>          
         <table>
         <tr>
            < td style="width:100%;font-size: 10px;">Name:</td>
         </tr>
        <br>
         
         <tr>
        <td style="width:8%;text-align:right;font-size: 10px;">Signature:</td>
         <td style="width:50%;text-align:right;font-size: 10px;">Date:</td>
        </tr>
        
        </table>';
              	
          $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('salary_annexur.pdf', 'I');
        
         
         
 
     }
    else if ($_GET["type"] == "download_Increment_letter") {
  
        $_GET['filename'] = 'Leave Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
      
        $html="";
     $sql = "SELECT a.*,b.old_annum_ctc,b.new_designation,b.entry_date,b.applicable_from FROM employee a left join salary_annexure b on a.emp_id=b.emp_id WHERE a.emp_id='".$_GET["emp_code"]."' and b.id='".$_GET["annexure_id"]."'";
    	$result = $conn->query($sql);
    	$result->num_rows > 0;
    		$row = $result->fetch_assoc();
    		    	$oldctc=$row['old_annum_ctc']*12;
    		    	$gender = $row['gender'];
    		    	$married = $row['marital_status'];
    		    	if ($gender == 'Female') {
    $salutation = 'Miss';
} 
else if($gender == 'Female' && $married=='Married'){
    $salutation = 'Mrs';
    }else {
    $salutation = 'Mr';
}
$oldctc;
$newCTC=$_GET['new_ctc'];
$inc=$newCTC-$oldctc;

        $html.='
 <table>
 <tr>
     <td style="line-height:20px;width: 270px;text-align:left;"> TO</td>
     <td style="line-height:20px;width: 270px;text-align:right;"> Date: '.date("d-m-Y", strtotime($row['entry_date'])).' </td>
 </tr>
 <tr>
 <td style="line-height:20px;width: 540px;text-align:left;">   '.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].' ('.$_GET['emp_code'].')</td>
</tr>
 
<tr>
<td style="line-height:20px;width: 540px;text-align:left;">   Department- '.$row['department'].' </td>
</tr>
<tr>
<td style="line-height:20px;width: 540px;text-align:left;">   Designation-'.$row['designation'].' </td>
</tr>
<tr>
<td style="line-height:20px;width: 540px;text-align:left;">  Olive Healthcare </td>
</tr>
<tr>
<td style="line-height:20px;width: 540px;text-align:center;"> Sub: Increment Letter</td>
</tr>
<tr>
<td style="line-height:20px;width: 540px;text-align:left;"> <b>Dear,</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 540px;text-align:left;"> <b> ' . $salutation . '  '.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].' ('.$_GET['emp_code'].') ,</b></td>
</tr>
<tr>
<td style="line-height:20px;width: 540px;text-align:left;"> We congratulate you for your hard work, enthusiasm, dedication and continuous effort in meeting the
 organization objective.
 On reviewing your performance for the year, we are glad to announce an increment of Rs, '.$inc.'/-
P.A. i.e. your current package comes to '.$_GET['new_ctc'].'/- P.A. with effect from '.$row['applicable_from'].'
 We expect you to keep up your performance in the years to come and grow with the organization.
 Please sign and return the duplicate copy in token of your acceptance, for your records.
 Wish you all the best</td>
</tr>
 
 
<tr>
<td style="line-height:20px;width: 540px;text-align:left;"><b>  For, OLIVE HEALTHCAR </b></td>
</tr><div></div><div></div><div></div>
<tr>
<td style="line-height:20px;width: 540px;text-align:left;"><b> Authority Signatory. </b></td>
</tr><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
</table>

';

               	
          $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('INCREMENT LETTER.pdf', 'I');
        
         
         
 
     }
    else if ($_GET["type"] == "download_task") {
  
        $_GET['filename'] = 'Leave Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
      
        
          $sql = "SELECT t.*,e.firstname,e.middlename,e.lastname FROM task t left join employee e on e.emp_id = t.employee  WHERE t.id='".$_GET['id']."' ";
        
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
     		while($row = $result->fetch_assoc()){
    		
    	 
            		
        
          
       $html.='
      
           <table class="table"  "cellpadding="3" >
        
             <tr>
                  <td style="width:50%;"><b>Employee Id:</b></td>
                  <td style="width:50%;">'.$row['employee'].'</td>
             </tr>
             <tr>
                  <td style="width:50%;"><b>Employee Name:</b></td>
                  <td style="width:50%;">'.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].'</td>
             </tr>
         
          
         
         
          
      </table>
         ';
         
            		 $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('salary_annexur.pdf', 'I');
        
                }
            
    	}
                
      
      
     }
     else if ($_GET["type"] == "download_appoinment_letter") {
  
             if($_GET["plant_id"] == 67) { //Saipro
	   
	    $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
		 $html="";
        $sql = "SELECT e. *,p.plant_id,p.plant_full_name FROM employee e left JOIN plant p on e.plant_id =p.plant_id WHERE e.emp_id='".$_GET['emp_code']."' and p.plant_id= '".$_GET['plant_id']."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		
     $date = date('Y-m-d');
    		   
    		  
    		   $sql1="SELECT * From salary_annexure WHERE emp_id='".$row["id"]."'";
    		    $salary_details;
    		   $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		   $salary_details = $row1;
                        $conveyance=$row1['conveyance']*12;
                        $gross=$row1['gross']*12;
                        $ptax=$row1['ptax']*12;
                        $deduction=$row1['deduction']*12;
                        $bonus=$row1['bonus']*12;
                        $contribution=$row1['contribution']*12;
                        $medical=$row1['medical']*12;
                        $c=$bonus+$contribution+$medical;
                        $c_month=$row1['bonus']+$row1['contribution']+$row1['medical'];
                        $net_total=$row1['gross']-$row1['deduction'];
                        $net_annual=$gross-$deduction;
                        $ac_annual= $gross+$deduction;
                        $ac=$row1['gross']+$row1['deduction'];
            		}}
             $html.='<h3 style="text-align:center;color:brown"><u>APPOINTMENT LETTER</u></h3>
                         <div></div>
                 <table>
       
        <td style="width:65%;">Ref.:- SIPL/HRD/2022/</td>
        <td style="width:30%;"> Date:<b>'.date('d/m/Y',strtotime($row['joining_date'])).'</b> </td>
        <div></div>
          <tr>
        <td style="width:50%;"><br>';
         if($row['gender']=='Female'){$html.='Miss';}else{$html.='Mr';}
        $html.='&nbsp;
       <b> '.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].'</b></td><br>
    </tr> 
    <tr>
         <td style="width:12%;font-size:12px">Address </td> <td style="width: 1%; text-align:right;" >&#58;</td>
         <td style="width:13%; text-align: right; font-size:12px">  '.$row['permanent_city'].' ,</td>
          <td style="width:33%; text-align: left; font-size:12px"> '.$row['permanent_state'].' - '.$row['permanent_pincode'].' </td>
         </tr>
         
      <tr>
         <td style="width:12%;font-size:12px">Mobile No.</td><td style="width:1%; text-align:right;" >&#58;</td> 
         <td style="width:14%; text-align: right; font-size:12px">'.$row['contact_no'].' </td>
       </tr>
       <tr>
       <td style="width:12%;font-size:12px">Email ID</td> <td style="width: 1%; text-align:right;" >&#58;</td>
       <td style="width:22%; text-align: right; font-size:12px">'.$row['emp_email'].'</td>
       </tr><br><br>
       <tr>
      <td style="width:100%; text-align: center;">Sub:<b>Appointment Letter</b></td>
       </tr><br>
       
       <tr>
         <td style="width: 540px; ">With reference to the interview you had with us, we are pleased to offer you the following position at
             Saipro Industries Pvt. Ltd. [the "Company"], on the following terms and conditions:</td>
     </tr> 
       </table>';
       
       $html.='
      <div></div>
            <table cellpadding="1" border="0.1">
        
         <tr style="background-color:#DDDAD9;">
         <th style="width:100%;"align="center"></th> 
        </tr>
         </table>
          <table style="width:100%;"cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;"><b>Name</b></td>
          <td style="width:50%;">'.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].'</td>
         </tr>
         
         <tr>
         <td style="width:50%;"><b>Designation</b></td>
          <td style="width:50%;">'.$row['designation'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Department</b></td>
          <td style="width:50%;">'.$row['department'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Location</b></td>
          <td style="width:50%;">'.$row['permanent_city'].'</td>
         </tr>
         
         </table>';
         
        $html.='  <table>
        <br><br>
     <tr>
         <td style="width: 540px;">CTC = Rs '.($salary_details['total_ctc'] * 12).'. Per yearly & Payment In Hand Rs'.$salary_details['take_home_salary'].'. per month. (Detailed Salary Structure
             Shared in Mail).
             </td>
     </tr> <br><br>
     <tr>
         <td style="width: 540px;"> Your appointment shall be governed by following terms: -</td>
     </tr>
     <br>
     <tr>
         <td style="width: 540px;"> 1. The official working hours of the company are from 8:30 am to 6.00 pm. With a lunch break of 45
             min,on a six-day week basis. Thursday is weekly off. …….. paid leaves per Year & …… Paid
             Holidays per year (National + Festivals).</td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 2. You will strictly adhere to the rules and regulation of employment as determined by the company
         </td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 3. During your employment with the company, the company may, at its sole discretion and at any
             time,transfer / depute you to any department, subsidiary or affiliate of the company including
             transfer to any of its offices in India and abroad, at no extra emoluments.</td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 4. During your employment, should you be guilty of mis-conduct and or be in breach of the terms of
             employment and or should our work not be to the satisfaction of the company, the company shall
             withoutprejudice to any of the rights herein contained, be entitled to terminate your employment
             forthwith, without notice or payment in lieu of notice.</td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 5. Your salary will be reviewed periodically as per prevailing company policy and increments are
             discre- tionary and subject to your performance. Increments are not a matter of right and shall be
             given at thesole discretion of the company.</td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 6. The retirement age in the company is 58 years. The actual date of retirement will be last working
             dayof month of the year in which you 58th birthday falls.
         </td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 7. The employment offer contained herein stands void at the end of business of the Date of Appointment
             contained herein, if you do not report for joining at the commencement of the business hours of the said date
             of appointment, unless otherwise agreed to by the company in writing.</td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 8. And you will be liable for lawful punishment if there is misuse of any company secret documents / poli-cies /
             agreements / recipes / formulations / process / client information / Suppliers information / ma- chine
              <br>information / Accounts & finance related info etc. for personal use or any other purpose.</td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 9. As Store Incharge following are your responsibilities: -  ……………………………………………………………………………………………………………………………………………………………………………………………………………………………………………………………………</td>
     </tr> <br> 
     <br>
     <br>
     <br>
     <br>
     <tr>
         <td style="width: 540px;"> 10. During working hours, personal chatting on what’s aap, Facebook, Instagram, twitter, hike etc. not allowed. Personal calling messaging also not allowed. Only personal / family emergency messages & calls are
             allowed. Chatting related to work & calling related to work is allowed.</td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 11. We need written resignation letter Two month in advance while leaving the organization.</td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 12. You can’t join immediate competitor company minimum for 18 months after reliving this job at Saipro&
             also need to submit offer letter of immediate next joining company before leaving this job.</td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> 13. You shall arrange to submit / furnish, the copy of the following documents on or before your joiningduty:
         </td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> a. Self-Certified Xerox copies of Certificates [Education Graduation & Post graduation]
         </td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> b. Relieving order / letter from previous employer</td>
     </tr>
     <tr> <br>
         <td style="width: 540px;"> c. Self-Certified Xerox copies of Proof regarding date of birth & permanent address
         </td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> d. Recent photograph 2 (passport)
         </td>
     </tr> <br>
     <tr>
         <td style="width: 540px;"> e. Latest Three Months’ Salary Slip of last Job
         </td>
     </tr> <br>
    
     <tr>
     <td style="width: 540px;"> If the above terms and conditions are acceptable to you, kindly sign on the duplicate copy of this letter as your
         acceptance hereof.
         </td><br>
    </tr>
    <tr>
     <td style="width: 540px;"> We welcome you and look forward to a long and fruitful</td>
    </tr><br>
    <tr>
     <td style="width: 540px;"> association.Yours sincerely,</td>
    </tr><br>
     <br>
     <br>
     <br>
    <tr>
     <td style="width: 270px; text-align: center;"> For Saipro Industries Pvt. Ltd. </td>
    </tr>
     <br>
     <br>
     <br>
     <br>
     <tr>
     <td style="width: 270px; text-align: center;">Director</td>
     <td style="width: 270px; text-align: center;"></td>
    </tr>
    <tr>
     <td style="width: 270px; text-align: center;">Maruti V. Walekar</td>
     <td style="width: 270px; text-align: center;">'; if($row['gender']=='Female'){$html.='Miss';}else{$html.='Mr';}
        $html.='&nbsp;
       <b> '.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].'</b></td>
    </tr>
    
    </table>';
            		
            		
            	
    		}
    		
    	}
        
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
         } else {
             {
        $_GET['filename'] = 'Leave Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
      
      
        $html="";
        $sql = "SELECT e. *,p.plant_id,p.plant_full_name FROM employee e left JOIN plant p on e.plant_id =p.plant_id WHERE e.emp_id='".$_GET['emp_code']."' and p.plant_id= '".$_GET['plant_id']."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		
     $date = date('Y-m-d');
    		   
    		  
    		   $sql1="SELECT * From salary_annexure WHERE emp_id='".$row["id"]."'";
    		    $salary_details;
    		   $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		   $salary_details = $row1;
                        $conveyance=$row1['conveyance']*12;
                        $gross=$row1['gross']*12;
                        $ptax=$row1['ptax']*12;
                        $deduction=$row1['deduction']*12;
                        $bonus=$row1['bonus']*12;
                        $contribution=$row1['contribution']*12;
                        $medical=$row1['medical']*12;
                        $c=$bonus+$contribution+$medical;
                        $c_month=$row1['bonus']+$row1['contribution']+$row1['medical'];
                        $net_total=$row1['gross']-$row1['deduction'];
                        $net_annual=$gross-$deduction;
                        $ac_annual= $gross+$deduction;
                        $ac=$row1['gross']+$row1['deduction'];
            		}}
            		
            	
                         $html.='<h3 style="text-align:center;color:brown"><u>APPOINTMENT LETTER</u></h3>
                         <div></div>
                 <table>
       
        <td style="width:65%;">File No.:- ALL/HR/PANOLI/04</td>
        <td style="width:30%;"> Date:<b>'.date('d/m/Y',strtotime($row['joining_date'])).'</b> </td>
        <div></div>
          <tr>
        <td style="width:50%;"><br>';
         if($row['gender']=='Female'){$html.='Miss';}else{$html.='Mr';}
        $html.='&nbsp;
       <b> '.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].'</b></td><br>
    </tr> <div></div>
    <tr>
         <td style="width:12%;font-size:12px">Address </td> <td style="width: 1%; text-align:right;" >&#58;</td>
         <td style="width:88%; text-align: left; font-size:12px"> '.$row['permanent_city'].' - '.$row['permanent_state'].' - '.$row['permanent_pincode'].' </td>
         </tr>
         
      <tr>
         <td style="width:12%;font-size:12px">Mobile No.</td><td style="width:1%; text-align:right;" >&#58;</td> 
         <td style="width:88%; text-align: left; font-size:12px"> '.$row['contact_no'].' </td>
       </tr>
       <tr>
       <td style="width:12%;font-size:12px">Email ID</td> <td style="width: 1%; text-align:right;" >&#58;</td>
       <td style="width:88%; text-align: left; font-size:12px"> '.$row['emp_email'].'</td>
       </tr><br><br>
       <tr>
      <td style="width:100%;">Sub:<b>Appointment Letter</b></td>
       </tr><br>
       <tr>
       <tr>
         <td style="width:100%;">Dear, <b>'.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].'</b></td> 
       </tr>
       
        This has reference to your interview <b>'.$row['interview_date'].'</b>  we are pleased to inform 
          you that you are appointed in <b>'.$row['department'].' Department</b> 
         As a <b>'.$row['designation'].'</b> at <b>Panoli Unit</b> based at <b>Panoli (Gujarat)</b>. Your date of joining is <b>'.date('d/m/Y',strtotime($row['joining_date'])).'</b></font>
        <h4 style="color:brown"><u>Salary Authorization from (SAF):</u></h4>
       <span>The compensation and benefits you are entitled to have been detailed in the Salary Authorization form 
      (SAF)attached herewith at <b>Annexure ‘A’</b>. The entitlements detailed in the SAF are subject to change from time 
      to time. Any changes in your compensation and benefits will be communicated to you by the company in writing 
      by issuing you a revised SAF.</span>
         <h4 style="color:brown"><u>Confirmation of Services:</u></h4>
         <span>Your services will be confirmed after satisfactory completion of 6 months’ probation.</span>
         <h4 style="color:brown"><u>Other Terms and Conditions:</u></h4>
       <span> Detailed terms & conditions at <b>Annexure ‘B’</b>.</span><br>
        <span style="font-size:9px" >Please signify your acceptance by signing and returning the copy of this appointment order, along with the annexure ‘B’.
         </span><br><br>
         <table>
         <tr>
         <td style="width:100%;  font-size: 10px;">Yours Sincerely,</td>
         </tr><br>
         <tr>
        <td style="width:100%;  font-size: 10px;"><b> For, '.$row['company_name'].'</b>.</td>
        </tr><br>
        <tr>
     <td style="width:100%; font-size: 10px;"> '.$row['plant_full_name'].' </td>
      </tr><br><br><br><br>
       <tr>
        <td style="width:100%; font-size: 10px;"><b>Authorised Signatory</b></td>
       </tr>
     
        </table>
        <br pagebreak="true"/>';
          
     
      
            $html.='
      <div></div>
            <table cellpadding="1" border="0.1">
        
         <tr style="background-color:#DDDAD9;">
        <th style="width:100%;" align="center"><b>Salary Authorization Form (SAF):</b></th></tr>
       <tr style="background-color:#DDDAD9;">
         <th style="width:100%;"align="center"><b>Annexure A to Appointment Letter dated:</b></th> 
        </tr>
         </table>
          <table style="width:100%;"cellpadding="3" border="0.1">
         <tr>
         <td style="width:50%;"><b>Name</b></td>
          <td style="width:50%;">'.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].'</td>
         </tr>
         
         <tr>
         <td style="width:50%;"><b>Designation</b></td>
          <td style="width:50%;">'.$row['designation'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Department</b></td>
          <td style="width:50%;">'.$row['department'].'</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Location</b></td>
          <td style="width:50%;">Panoli</td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Probation Period </b></td>
          <td style="width:50%;"><b>'.$row['probation_period'].'</b></td>
         </tr>
         <tr>
         <td style="width:50%;"><b>Monthly CTC Rs.</b></td>
          <td style="width:25%;"><b>'.$salary_details['total_ctc'].'</b></td>
          <td style="width:25%;"><b></b></td>
          </tr>
          <tr>
         <td style="width:50%;"align="center"><b>Cost To Company (CTC)</b></td>
         </tr>
         <tr>
         <th style="width:50%;"><b>Salary Heads </b></th>
          <th style="width:25%;"><b>INR Per Month </b></th>
          <th style="width:25%;"><b>INR Per Annum</b></th>
         </tr>';
         
         $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='Earnings' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:50%;">'.$row2['description'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
            		}
            		
            	$html.=' 	<tr>
          <td style="width:50%;"><strong>Gross Salary</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['total_earnings'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['total_earnings'] * 12).'</strong></td>
         </tr>
         <tr>
          <td style="width:50%;"><strong>Employer Benefits </strong></td>
          <td style="width:25%; text-align:right;"></td>
          <td style="width:25%; text-align:right;"></td>
         </tr>
         ';
            		
                }
           
              $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='CTC Calculations' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:50%;">'.$row2['description'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
            		}
            		
            	$html.=' 	<tr>
          <td style="width:50%;"><strong>Fixed CTC</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['total_ctc'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['total_ctc'] * 12).'</strong></td>
         </tr>
          
         	<tr>
          <td style="width:50%;"><strong>Employee Deduction  </strong></td>
          <td style="width:25%; text-align:right;"></td>
          <td style="width:25%; text-align:right;"></td>
         </tr>
         ';
            		
                }
           
          
                       $sql2="SELECT * FROM salary_annexure_details WHERE  salary_annexure_id= '" .$salary_details["id"]."' AND  salary_group ='Deductions' ";
    	   $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
            		while($row2= $result2->fetch_assoc()){	
                
      
        $html.=' <tr>
         <td style="width:50%;">'.$row2['description'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_month'].'</td>
          <td style="width:25%; text-align:right;">'.$row2['per_annum'].'</td>
         </tr>';
            		}
            		
            	$html.=' 	<tr>
          <td style="width:50%;"><strong>Total Deductions</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['ctc_deductions'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['ctc_deductions'] * 12).'</strong></td>
         </tr>';
            		
                }
                
        	$html.=' 	<tr>
          <td style="width:50%;"><strong>Net Take Home Salary after PF & Tax deduction</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.$salary_details['take_home_salary'].'</strong></td>
          <td style="width:25%; text-align:right;"><strong>'.($salary_details['take_home_salary'] * 12).'</strong></td>
         </tr>';
         
        

    	
      $html.=' </table><div></div><div></div><div></div>
         <tr>
        <td style="width:100%; font-size: 10px;"><b>Authorised Signatory</b></td>
       </tr>
           <br pagebreak="true"/>';
          $html.='
        <table>
          <h2 style="text-align: center;color:brown">‘Annexure B’</h2>
         <span style="text-align:justify;"> In continuation to our offer of employment with Amardeep Chemicals 
         Industries Pvt Ltd .,a summary of the major benefits available to all employees is detailed below along with other 
         terms and conditions of employment.</span>
           <h4 style="width:25%;color:brown">WORKING HOURS</h4>
           The standard work-week will be Monday through Saturday from 09:15 hours to 17:45 hours. Depending on the nature 
           of the work schedule the standard work hours may be different for employees in some functions or practices.
           <h4 style="width:100%;color:brown">PROBATION</h4>
            You will be on probation for period of Six months from the date of joining and your services are deemed to be on 
            probation till your services are confirmed in writing.<br>
            Probation period can be extended for a period of three months or more on the advice of your reporting manager, 
            and at the discretion of the Company depending on your performance.
          
           <h4 style="width:100%;color:brown">WORK RULES</h4>
           You will also be entitled to and governed at all times by the policies, procedures, regulations and rules of the company in effect from time to time whether such policies are specified in the letter of appointment or elsewhere. You would be required to apply & maintain the highest standards of personal conduct and integrity and comply with all the policies and procedures of the company with punctuality.
          <h4 style="width:100%;color:brown">EMPLOYMENT</h4>
         <span>You will devote your whole working time to the service of the company and will not engage in any other employment. Failure to comply with the above will subject you to immediate termination without notice or payment in lieu of notice.</span>
           <h4 style="width:100%;color:brown">BACKGROUND REFERENCE CHECK</h4>
          <span>The Company, at any time (or as part of the joining formalities) conduct reference/ background check (including but not limited to the previous employers, education qualifications etc.) in the event the statements / particulars furnished by you is found to be false or misleading, Company reserves its right to terminate your services forthwith on the grounds of misrepresentation of the facts. Further in the event if it is found that you had indulged / been indulging in drugs and narcotics abuse or any other criminal activities or had any criminal records, Company shall have the right to terminate your services forthwith. You shall have no objection if the company makes it’s inquires in this regard as a pre-employment check.</span>
          
          <h4 style="text-align: center;color:brown">EMPLOYEE BENEFITS</h4>
            <h4 style="width:100%;color:brown">HOLIDAYS</h4>
          <span>We observe 10 National and Festival Holidays per year.4 National Holidays are observed every year and you would be entitled to 6 other Festival Holidays from an Optional List.</span>
         <h4 style="width:100%;color:brown">LEAVE</h4>
          <span> On completing one year’s continuous service with Amardeep every employee will be eligible for 30 days of leave 
          (inclusive of 07 casual leave and 07 sick leaves).The leave will be proportionate to the number of days actually worked 
          during the calendar year.</span>
       <span>In the event, if you are absent from work for 24 hours or more then you are forthwith required to notify 
       AMARDEEP about your absence along with reasons for the absence from work.</span>
         
         <h4 style="width:100%;color:brown">GRATUITY</h4>
       <span>As per the Payment of gratuity act 1972 , upon completing 5 years of continuous Service with AMARDEEP every employee 
       will be eligible for the receipt of Gratuity a social security measure. The amount, equivalent to half month’s basic pay 
       for every completed year of service will be paid to you at the time of your separation from</span>
       <span>AMARDEEP,be it by resignation, termination or retirement. AMARDEEP will not be liable to pay Gratuity to any 
       employee</span>
        <span>who causes damage to the company through willful negligence & omission, destruction of Company property or misconduct including leaving the services of the company without proper notice.</span><div></div>
         <h4 style="width:100%;color:brown">COMPENSATION PACKAGE</h4>
         <span>We aim at paying attractive and competitive salaries to all our employees. Your compensation & benefits will be reviewed and revised annually, and any adjustments will be based on a thorough review of market conditions. Your individual performance and your contributions to AMARDEEP and Organization performance.</span>
           <h4 style="width:100%;color:brown">PERFORMANCE REVIEW</h4>
         <span>At the discretion of the Company,your services will be reviewed on quarterly basis on set KRAs.However,
         the salary revisions will be done annually as per Company’s policy. </span>
      
       <h4 style="text-align: center;color:brown">TERMS OF SERVICE</h4>
        <h4 style="width:100%;color:brown">INTEGRITY</h4>
         <span>It must be specifically understood that this offer is made based on the professional skills. You have declared to possess as per your resume.</span>
          <span>During the term of your employment with AMARDEEP currently or in the future or may be in conflict with the terms of your employment with AMARDEEP either directly or indirectly. This includes personal details viz, name, age, father name, contact address or professional information like qualification. Ability or previous or any other matter germane to employment at the time of employment or during the course of employment.</span>
          <span>Should AMARDEEP at a later date during the term of your employment become aware that you have either suppressed any 
          particulars or relevant information required to be disclosed by you or that you have furnished false/misleading information 
          AMARDEEP reserves the right to terminate your services forthwith without any notice and without any obligation or liability 
          to pay any remuneration or other dues to you irrespective of the period that you may have been employed by AMARDEEP.</span><br>
           <span>Every employee is expected to follow the taxation laws rules and philosophy of compensation and benefits in the letter and spirit and uphold the values of honesty and integrity in all his/her actions in the course of doing so. Every employee shall claim only actual expenses and ensure compliance with the tax laws of the land in letter and spirit</span>
            
            <h4 style="width:100%;color:brown">CONFIDENTIALITY</h4>
         <span>You are expected to maintain utmost secrecy with regard to the affairs of AMARDEEP and shall keep any information, instruments, manuals, relating to the company that may come to your professional knowledge as on associate of the company.</span><br>
          <span>The position held by you is of a strictly confidential nature. As a result of employment at AMARDEEP the company may from time to time need to impart you with certain information/material pertaining to its business or its associate companies or any company. Firm or person with whom AMARDEEP or its associate companies may at any time be in technical. Commercial or financial cooperation or association. Which is to be treated as secret and confidential.</span>
          <span>You shall not disclose to either during or after employment with the company any information about the interests or business of the company or any affiliated company or client.</span>
          <span>During your employment or at any time after the termination of employment. you will not divulge to any unauthorized person any trade of manufacturing process or any knowledge or information concerning any matter or thing relating to the business or interests of AMARDEEP and its subsidiaries/associate companies or of any company firm or person with whom the AMARDEEP or its subsidiaries /associate companies may it any time be in technical commercial or financial cooperation or association. You will not utilize any secret or confidential information or knowledge acquired in consequence of your employment.</span>
          <span>You shall keep confidential any information or manuals relating to the Company’s compensation and benefits schemes that may come to your professional knowledge as an associate of the Company. You should maintain</span>
           <span>utmost secrecy with regard to compensation and benefits package and treat it as a highly individual and confidential matter not to be discussed with any colleague, other than your Manager.</span>
           <span>You shall not except in accordance with any general or special order of the Company of in the performance, in good faith of the duties assigned to you communicate directly or indirectly any official document or any part thereof of information (including your salary to any other Officer or other associate or any other person to whom you are reporting).</span>
          <span>You shall not either during employment with AMARDEEP or for a period of two years thereafter approach AMARDEEP business contacts. Business partners or customers for business of a similar nature either individually or as a company or organization where you have an investment an advisory role or whole –time employment in a decision-making capacity</span>
           <span>You will be required to execute and be bound by a Non-Disclosure Agreement given to you along with the Employment Letter and such Agreement shall be co-extensive with this Employment Letter.</span>
          
           <h4 style="width:100%;color:brown">AUTHORIZATION</h4>
        <span>The management of AMARDEEP shall be the only authorized signatory to sign any legal documents and shall only at its discretion may speak about the company, its business plans & current projects.</span>
        <h4 style="width:100%;color:brown">SECURITY</h4>
        <span>The data/information held on organization’s systems is deemed to be the property of AMARDEEP. You shall be responsible for the protection of data/information and security of passwords. The data/information/passwords should not be shared even with your colleagues. You shall use the company’s email for official purpose only.</span>
        <span>Information shall be available to you on a need-to-know basis/based on the roles and responsibilities. You shall be provided with a worktable and storage space which you shall ensure that such storage spaces are locked when attended. Duplicate keys will be maintained with security/Administration, you may take a duplicate key after signing for it for your own or a team member’s table or storage.</span>
         <span>In case you work outside Office hours on the premises you are requested to produce your identity card to the Security personnel on demand. Any equipment taken out of the Office premises will require a gate pass duly authorized by the appropriate authority.</span>
           
          <h4 style="width:100%;color:brown"><b>USE OF COMPANY RESOURCES</b></h4>
          <span>You shall be responsible for the safekeeping and good condition and order of all the AMARDEEP property entrusted to 
          your care and charge. You may use the AMARDEEP resources only for Official purposes.</span>
           
          <h4 style="width:100%;color:brown">RETIREMENT AGE</h4>
          <span>The age of retirement for every associate of AMARDEEP is 60 years. You shall however during the tenure of the services be required to be medically fit for work. AMARDEEP may at its discretion request you to undergo periodic medical examination to enable professional determination of medical fitness for employment.</span>
          <h4 style="width:100%;color:brown">TERMINATION</h4>
         <span>Your Service with the company may be terminated at anytime, after confirmation or during probation by giving written notice of 30 days or payment of one month’s salary if your performance is not upto to the satisfaction or expectation or if there is any misconduct against to the Policies, and interest of the company.</span>
        <h4 style="width:100%;color:brown">HEALTH INSURANCE</h4>
           <span>All the employees are eligible for the medical benefit under Medi-claim Policy. It covers employee, spouse and two children (Dependents as per the policy) will be covered under the Company Medi-Claim.</span>
          <span>All the employees are covered under the Group Term Life Insurance Policy.</span>
           <span>Company has right to discontinue it, without giving any reasonable justification/reason.</span>
          <h4 style="width:100%;color:brown">CODE OF CONDUCT</h4>
         <span>It is condition of this Appointment letter and your acceptance that in terms of your business activities and personal endeavors, your conduct will be in accordance with Company’s policies and code of conduct. You should comply with the legal requirements of each State in which, the Company conducts business and shall enjoy the highest ethical standards in any business dealings.</span>
          <span>You will treat your colleagues, subordinates, superiors and female co workers with respect and dignity at the workplace.</span>
          <span>Violation of these or any of the codes of conduct & discipline of the Company will result in immediate termination.</span>
          <span>Whenever you change your present or local residence, or permanent address for any reason, you shall intimate the change to the Management immediately.</span>
          <span>You will not leave the station of your place of employment without prior intimation to the immediate superior or Officer in charge of your department, as the case may be.</span>
         
           <h4 style="width:100%;color:brown">ALLOWANCES & PERQUISITE</h4>
         <span>The Company will reimburse authorized reasonable expenses you incur on Company business during the course of employment. Claims for expenses will be subject to the Company’s Policy from time to time and approval from the Concerned Authority in writing. The Claim should be accompanies by reasonable proof of the expenditure. You will not be entitled to authorize your own expenses.</span>
         <h4 style="width:100%;color:brown">INFRASTRUCTURE AND OFFICE EQUIPMENT</h4>
         <span>You will be provided with the basis Infrastructure facilities like laptop/desktop, SIM Card, Access Card, ID Card etc., depending upon the need and nature of your services. The IT team reserves the right to control & maintain the designed information and access to sites. Access to information will be provided depending upon the specific requirement of the user. Though the access to network is authorized through access privileges approved by the HOD and IT Dept.</span>
         <span>Use of Company resources for personal use is strictly restricted. This includes usage of computer resources, information, internet service, and working time of the Company for any personal use.</span><div></div>
         <h4 style="width:100%;color:brown">NOTICE PERIOD FOR RESIGNATION</h4>
         <span>This employment is directed towards a career at AMARDEEP. However, employment at AMARDEEP will always entail the conditions of satisfactory performance and satisfactory market conditions for AMARDEEP’S products and services (as it may determine at its sole discretion).The employee need to serve 7 days of notice period if leaving withing three months of Probation and 15 days notice period if leaving after three months of Joining during Probation.</span>
        <span>For all the employees post confirmation the notice period for relieving form your services with AMARDEEP shall be 90 days or basic salary in lieu of notice period on part of AMARDEEP only.</span>
        <span>Amardeep reserves the right to terminate your services without any notice or salary in lieu thereof on grounds of misconduct, disloyalty and negligence, commission of any act involving moral turpitude or any act of indiscipline or inefficiency or loss of confidence. In the event of any breach of the code of conduct or non-performance of contractual obligation or the terms and notwithstanding any other terms and conditions stipulated herein. AMARDEEP further reserves the right to invoke other legal remedies as it deems fit to protect its legitimate interests.</span>
         <span>In case of employment termination for any reason the year-end performance incentive (if applicable) a part of your compensation structure will not be processed as part of full & final settlement.</span>
         
         <h4 style="width:100%;color:brown">RETURN OF PROPERTY</h4>
          <span>On Separation of your employment or upon the demand of the Company, you should deliver to the Company all keys, identification cards and other related documents or materials in your possession provided by the Company. Furthermore, the Employee warrants and undertakes that he/ she, or through a third person, will not make, or allow to be made, any copy or records in any form of the above mentioned materials.</span>
          <span>You have to settle all the advances taken by you during 
       
         your employment with the Company or the same shall be recovered / settled during Full & Final calculations.</span>
         <h4 style="width:100%;color:brown">TRANSFERS</h4>
         <span>Every employee of AMARDEEP is liable for transfer/deputation/secondment/training to any office of AMARDEEP or it’s associate companies’ client locations or third parties in India or abroad in such an event you will be governed by the terms and conditions of service applicable to the new assignment.</span>
          <span>In all service matters, including those not specifically covered here such as travel etc. employees will be governed by the rules and policies of AMARDEEP in force from time to time.</span>
         
         <h4 style="width:100%;color:brown">BUSINESS CONDUCT</h4>
         <span>You shall at all times maintain office decorum including in dealing with colleagues both with office premises and at client locations. Practices such as reading newspaper or magazines in the reception having obscene posters/work station screen servers at your work place standing in groups and having refreshments in common areas playing games at your work premises etc. should be strictly avoided.</span>
     
      <h4 style="width:100%;color:brown">SEXUAL HARASSMENT</h4>
        <span>Any act or language with sexual overtones or implications proving offensive to colleague of the opposite or same sex will be construed as sexual misconduct and should be strictly avoided Offensive posters / screen savers/mails or magazines and books at your work place should be strictly avoided.</span>
       <h4 style="width:100%;color:brown">INTELLECTUAL PROPERTY RIGHTS</h4>
         <span>You hereby expressly acknowledge and agree that any work that you may be conducting either on the premises of AMARDEEP or otherwise with regard to patents, improvements discoveries or any other form of intellectual property whether protected under law or not you are working on the express or implied instructions of AMARDEEP and on behalf of AMARDEEP .</span>
        <span>Any invention, development, process, discovery, formulae, plan, specification program component, process adaptation or improvement in procedure or other matters or work including any artistic literary or other work which the subject matter of copyright may be whatsoever made. Developed or discovered by you, either alone or jointly with any person or persons while in employment with AMARDEEP. capable of being used or adapted for use therewith shall forthwith be disclosed to AMARDEEP and shall belong to and be the absolute property of AMARDEEP and shall be deemed to be “work made for hire”.</span>
         <span>You also hereby irrevocably transfer and assign to AMARDEEP and waive and agree never to assert any and all Moral Rights you may have in or with respect to any work, documentations, designs and materials patents copyright or any other form of intellectual property where protected under law or not even after termination of your work during or after the tenure of your employment.</span>
          <span>You shall not communicate to any public papers, journals, pamphlets or leaflets or cause to be disclosed at any time any information or documents official or otherwise relating to AMARDEEP expect with the prior approval (in writing) of the management.</span>
         <div></div>
         <h4 style="width:100%;color:brown">OTHER TERMS AND CONDITIONS</h4>
         <span>In addition, you shall be subjected to such other existing general terms and conditions of service as may be laid down by the Company to govern all members of its staff and to any changes to the terms and conditions of employment that may be introduced by the Company from time to time.</span>
        <span>The terms of its appointment letter do not and or not intended to create either an express and / or implied contract of employment with the Company, and the Board of Directors of the Company reserves the right to change the terms of the letter unconditionally.</span>
         <span>With acceptance of this employment, you accept that the restraints specified in this letter are reasonable in all the circumstances for the protection of the company and its other group company’s legitimate interest.</span>
          <span>By signing this document, you confirm that you have not entered into any other agreement with or undertaken obligations to others, including agreement with and obligation to previous employment that are in conflict with the terms herein.</span>
        <span>All the above briefed terms and conditions are based on AMARDEEP’s policies, procedures and other rules currently applicable.</span>
        <div></div>
        <table>

       <br>
        <tr>
     <td style="width:100%;  font-size: 10px;"><b> For, '.$row['company_name'].'</b>.</td>
        </tr><br>
        <tr>
     <td style="width:100%; font-size: 10px;"> '.$row['plant_full_name'].' </td>
       </tr><br><br><br>
       <tr>
        <td style="width:100%; font-size: 10px;"><b>Authorised Signatory</b></td>
       </tr>
        </table>
       
         <h4 style="text-align: center;color:brown">Employee Acknowledgement</h4>
        <p style="width:40%;  font-size: 10px;">I accept all terms and Conditions of the company as stipulated above.</p>
        <p style="width:40%; font-size: 10px;">I hereby accept the position on the terms and conditions of employment offered.</p>
        <table><div></div>
         <tr>
        <td style="width:100%;font-size: 10px;">Name:</td>
         </tr>
         </table><div></div>
         <table>
         <tr>
        <td style="width:10%;text-align:right;font-size: 10px;">Signature:</td>
         <td style="width:50%;text-align:right;font-size: 10px;">Date:</td>
        </tr>';
       $html.=' </table>
           <br pagebreak="true"/>';
          $html.='
        <table>
        <h3 style="text-align:center ;color:brown"> EMPLOYEE NON-DISCLOSURE AGREEMENT</h3>
           </table>
            <li>In consideration of being employed by M/s Amgis Lifescience Ltd , the undersigned employee hereby agrees and acknowledges:</li>
         <div></div>
          <ol>
          <li> That during the course of my employment I may come across certain trade secrets of the Company; said trade secrets consisting but not necessarily limited to:</li>
         
          <br>
           <ol type="a">
           <li> Technical information: Methods, processes, formulas, compositions, systems, techniques, inventions, documentation, machines, computer programs and research projects.
           </li><br>
         <li>Business information: Customer lists, pricing data, sources of supply, financial data and marketing, production, or merchandising systems or plans.</li>
          </ol>
        <br>
          <li> I assure that I shall not during my employment, or at any time after the last working day of my employment with the Company, use for myself or others, or disclose or divulge to others including future employees, any trade secrets, confidential information, or any other proprietary data of the Company in violation of this agreement.</li>
        <br>
         <li> That upon the relieving of my employment from the Company:</li>
       <br>
        <ol type="a">
         <li>I shall return to the Company all documents and property of the Company, including but not necessarily 
         limited to: drawings, blueprints, reports, manuals, correspondence, customer lists, computer programs, 
         and all other materials and all copies thereof relating in any way to the Companys business, or in any way 
         obtained by me during the course of employ. I further agree that I shall not retain copies, intentionally 
         withhold information notes or abstracts of the fore going.
         </li>
         <br>
         <li> The Company may notify any future or prospective employer or third party of the existence of this agreement, and shall be entitled to full injunctive relief for any breach.</li>
        <br>
        <li> This agreement shall be binding upon me and Company only.</li>
         </ol>
         </ol>
         <div></div>
          <table>
         <tr>
         <td style="width:40%;text-align:center"> Dated: '.$row['approve_date'].'</td>
         </tr>
         <div></div><div></div>
        
         <tr>
         <td style="width:50%;text-align:cenetr"> <b> HR & Admin Manager </b></td>
          <td style="width:40%;text-align:center"> <b> Sales Coordinator   </b> </td>
         </tr>
         <tr>
         <td style="width:50%;text-align:cenetr"> <b> Vapi & Panoli Unit     </b></td>
          <td style="width:40%;text-align:center"> <b>    Panoli  Unit   </b> </td>
         </tr>
         <div></div>
        <tr>
        <td style="width:50%;text-align:center" ><b>'.$row['company_name'].'</b> </td>
      </tr>
  
      
           </table>';
    	
    		}
          }
          
    	
    	
    	
    	
    	
      $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('salary_annexur.pdf', 'I');
        
         
     }
         }
       } else if ($_GET["type"] == "download_salary_annexure_old") {
          class MYPDF extends TCPDF {
            public function Header() {
                $table='
                <table>
                    <tr>
                         <td style="width:20%;">';
                           $this->Image('@'.file_get_contents('https://amardeepgmp.com/assets/deep.png'),15,6,25,18);
                        $table.='
                        </td>
                    </tr>
                </table>';
                 $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
                $this->SetY(29); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0,'', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            
            }
            public function Footer(){}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(10,15,10,10);
        $pdf->SetAutoPageBreak(TRUE,10);
        $pdf->AddPage($_GET['pdfpage']);
       $pdf->SetY(25);
       $pdf->SetFont('helvetica', '', 10);
      
       $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
        $html="";
        $sql = "SELECT * FROM employee  WHERE emp_id='".$_GET['id']."' ORDER BY id DESC";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    		
    		    
    		   $date =date('Y-m-d',strtotime($row['interview_date']));
    		   $sql1="SELECT * From salary_annexure WHERE emp_id='".$conn->real_escape_string($row["emp_id"])."'";
    		   $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
            		while($row1 = $result1->fetch_assoc()){
            		    //$ctc=$row1['ctc']*12;
            		    $hra=$row1['hra']*12;
                        $basic=$row1['basic']*12;
                        $specialallowance=$row1['specialallowance']*12;
                        $education=$row1['specialallowance']*12;
                        $conveyance=$row1['conveyance']*12;
                        $gross=$row1['gross']*12;
                        $ptax=$row1['ptax']*12;
                        $deduction=$row1['deduction']*12;
                        $bonus=$row1['bonus']*12;
                        $contribution=$row1['contribution']*12;
                        $medical=$row1['medical']*12;
                        $c=$bonus+$contribution+$medical;
                        $c_month=$row1['bonus']+$row1['contribution']+$row1['medical'];
                        $net_total=$row1['gross']-$row1['deduction'];
                        $net_annual=$gross-$deduction;
                        $ac_annual= $gross+$deduction;
                        $ac=$row1['gross']+$row1['deduction'];
                        
                         $html.='
                   <table>
       
        <td style="width:65%;">File No.:- ALL/HR/PANOLI/04</td>
        <td style="width:30%;"> Date:<b>'.date('d/m/Y',strtotime($row['interview_date'])).'</b> </td>
        
         <div></div>
          <tr>
                        <td style="width:50%;"><br>';
                        if($row['gender']=='Female'){$html.='Miss';}else{$html.='Mr';}
                        $html.='&nbsp;
                       <b> '.$row['firstname'].' '.$row['middlename'].' '.$row['lastname'].'</b></td><br>
                       </tr> <div></div>
                        
        <tr>
         <td style="width:12%;font-size:12px">Address </td> <td style="width: 1%; text-align:right;" >&#58;</td><td style="width:13.3%; text-align: right; font-size:12px">  '.$row['address_permanent'].' </td>
         </tr>
         <tr>
         <td style="width:25.3%; text-align: right; font-size:12px"> '.$row['permanant_state'].' </td>
         </tr>
         <tr>
          <td style="width:25.8%; text-align: right; font-size:12px"> '.$row['permanant_district'].' </td>
         </tr>
      <tr>
         <td style="width:12%;font-size:12px">Mobile No.</td><td style="width:1%; text-align:right;" >&#58;</td> <td style="width:14.4%; text-align: right; font-size:12px">'.$row['emp_contact'].' </td>
       </tr>
       <tr>
       <td style="width:12%;font-size:12px">Email ID</td> <td style="width: 1%; text-align:right;" >&#58;</td><td style="width:22.3%; text-align: right; font-size:12px">'.$row['email'].'</td>
       </tr><br><br>
       <tr>
      <td style="width:100%;"><b>Sub: Appointment Letter</b></td>
       </tr><br><br>
       <tr>
         <td style="width:100%;">Dear '.$row['emp_name'].',</td> 
       </tr><br>
        
          
          This has reference to your interview <b>'.date('d/m/Y',strtotime($row['interview_date'])).'</b>  we are pleased to inform 
          you that you are appointed <b>'.$row['department'].'</b>
            As a<b> General Manager</b> at <b>Panoli Unit</b> based at <b>Panoli (Gujarat)</b>. Your date of joining is <b>'.date('d/m/Y',strtotime($row['joining_date'])).'</b></font>
            
            <h4 style="width:100%;"><u>Salary Authorization from (SAF):</u></h4>
       <span style="text-align:justifiy">
       The compensation and benefits you are entitled to have been detailed in the Salary Authorization form 
      (SAF)attached herewith at <b>Annexure ‘A’</b>. The entitlements detailed in the SAF are subject to change from time 
      to time. Any changes in your compensation and benefits will be communicated to you by the company in writing 
      by issuing you a revised SAF.</span><br><br>
      
   <span><u><b>Confirmation of Services:</u></b>
        <p> Your services will be confirmed after satisfactory completion of 6 months’ probation.</p></span><br>
         
         <h4 style="width:100%;"><u>Other Terms and Conditions:</u></h4>
       
       <span>Detailed terms & conditions at <b>Annexure ‘B’</b>.</span><br><br>
        
       <span style="font-size:9px">Please signify your acceptance by signing and returning the copy of this appointment order, along with the annexure ‘B’.
         </span><br><br>
         <table>
         <tr>
         <td style="width:100%;  font-size: 10px;">Yours Sincerely,</td>
         </tr><br>
         <tr>
        <td style="width:100%;  font-size: 10px;"><b>For Amardeep Chemical Industries Pvt Ltd</b>.</td>
        </tr><br><br>
        <tr>
     <td style="width:100%; font-size: 12px;">Mr.Dharmendra Patel </td>
      </tr><br>
      <tr>
      <td style="width:100%;  font-size: 12px;">Managing Director</td>
       </tr><br>
       <tr>
       <td style="width:100%;  font-size: 12px;">Vapi & Panoli Unit</td>
       </tr>
        </table>
        
        <br pagebreak="true"/>';
          
       $html.='
      
            <table cellpadding="1" border="0.1">
        
         <tr>
        <th style="width:95%;" align="center"><b>Salary Authorization Form (SAF):</b></th></tr>
        <tr>
         <th style="width:95%;"align="cenetr"><b>Annexure A to Appointment Letter dated:</b></th> 
        </tr>
         </table><br><br>
          <table style="width:100%;"cellpadding="3" border="0.1">
         <tr>
         <td style="width:55%;"><b>Name</b></td>
          <td style="width:40%;">'.$row['emp_name'].'</td>
         </tr>
         
         <tr>
         <td style="width:55%;"> <b>Designation</b></td>
          <td style="width:40%;">'.$row['designation'].'</td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Department</b></td>
          <td style="width:40%;">'.$row['department'].'</td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Location</b></td>
          <td style="width:40%;">'.$row['location'].'</td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Probation Period </b></td>
          <td style="width:40%;"><b>'.$row['probation_period'].'</b></td>
         </tr>
         <tr>
         <td style="width:55%;"><b>Monthly CTC Rs.</b></td>
          <td style="width:20%;"><b>'.$row1['ctc'].'</b></td>
          <td style="width:20%;"><b></b></td>
          </tr>
          <tr>
         <td style="width:55%;"align="center"><b>Cost To Company (CTC)</b></td>
         </tr>
         <tr>
         <th style="width:55%;"><b>Salary Heads </b></th>
          <th style="width:20%;"><b>INR Per Month </b></th>
          <th style="width:20%;"><b>INR Per Annum</b></th>
          </tr>
          <tr>
         <td style="width:55%;">Basic</td>
          <td style="width:20%; text-align:right;">'.$row1['basic'].'</td>
          <td style="width:20%; text-align:right;">'.$basic.'</td>
          </tr>
          <tr>
         <td style="width:55%;">House Rent Allowance</td>
          <td style="width:20%;  text-align:right;">'.$row1['hra'].'</td>
          <td style="width:20%;  text-align:right;">'.$hra .'</td>
          </tr>
          <tr>
         <td style="width:55%;">Conveyance Allowance</td>
          <td style="width:20%;  text-align:right;">'.$row1['conveyance'].'</td>
          <td style="width:20%;  text-align:right;">'.$conveyance.'</td>
          </tr>
          <tr>
         <td style="width:55%;  text-align:right;">Education Allowance </td>
          <td style="width:20%;">'.$row1[''].'</td>
          <td style="width:20%;  text-align:right;"></td>
          </tr>
          <tr>
         <td style="width:55%;">Special Allowance</td>
          <td style="width:20%;  text-align:right;">'.$row1['specialallowance'].'</td>
          <td style="width:20%;  text-align:right;">'.$specialallowance.'</td>
          </tr>
          <tr>
         <td style="width:55%;  text-align:right;"><b>Gross Salary</b></td>
          <td style="width:20%;  text-align:right;"><b>'.$row1['gross'].'</b></td>
          <td style="width:20%;  text-align:right;"><b>'.$gross.'</b></td>
          </tr>
           <tr>
         <td style="width:55%;"><b></b></td>
          <td style="width:20%;"><b></b></td>
          <td style="width:20%;"><b></b></td>
          </tr>
           <tr>
         <td style="width:55%;"><b>Employer Benefits </b></td>
          <td style="width:20%;  text-align:right;">'.$row1[''].'</td>
          <td style="width:20%;  text-align:right;"></td>
          </tr>
           <tr>
         <td style="width:55%;">Mediclaim Contribution</td>
          <td style="width:20%;  text-align:right;">'.$row1['medical'].'</td>
          <td style="width:20%;"></td>
          </tr>
           <tr>
         <td style="width:55%;">EPF Contribution (Employers_Basic *13%)</td>
          <td style="width:20%;">'.$row1[''].'</td>
          <td style="width:20%;  text-align:right;"></td>
          </tr>
           <tr>
         <td style="width:55%;">Bonus (As Per Govt. Rules)</td>
          <td style="width:20%;  text-align:right;">'.$row1['gratuity'].'</td>
          <td style="width:20%;  text-align:right;">'.$gratuity.'</td>
          </tr>
           <tr>
         <td style="width:55%;">Gratuity</td>
          <td style="width:20%;  text-align:right;">'.$row1['gratuity'].'</td>
          <td style="width:20%;  text-align:right;">'.$gratuity.'</td>
          </tr>
           <tr>
         <td style="width:55%;">Total Deduction </td>
          <td style="width:20%;">'.$row1[''].'</td>
          <td style="width:20%;"></td>
          </tr>
           <tr>
         <td style="width:55%;"><b>Fixed CTC</b></td>
          <td style="width:20%;  text-align:right;"><b>'.$row1['ctc'].'</b></td>
          <td style="width:20%;  text-align:right;"><b>'.$ctc.'</b></td>
          </tr>
           <tr>
         <td style="width:95%;"><b>Employee Deduction </b></td>
         </tr>
          <tr>
         <td style="width:55%;">PF Cont 12%</td>
          <td style="width:20%;  text-align:right;"><b>'.$row1['PF_EMP'].'</b></td>
          <td style="width:20%;  text-align:right;"><b>'.$PF_EMP.'</b></td>
          </tr>
           <tr>
         <td style="width:55%;">Professional Tax</td>
          <td style="width:20%;  text-align:right;">'.$row1[''].'</td>
          <td style="width:20%;"></td>
          </tr>
            <tr>
         <td style="width:55%;"><b>Total Cost to Company</b></td>
          <td style="width:20%; "><b>'.$row1[''].'</b></td>
          <td style="width:20%;"><b></b></td>
          </tr>
          </table><br><br>
          <table cellpadding="1" border="0.1">
           <tr>
         <td style="width:55%;"><b>Net Take Home Salary after PF & Tax deduction </b></td>
          <td style="width:20%;"><b>'.$row1[''].'</b></td>
          </tr>';
         
              	}
                }
    		
    		}
    	}
          $html.=' </table>
          <br pagebreak="true"/>';
         
          $html.='
          <table>
          <h2 style="text-align: center;">‘Annexure B’</h2><br>
        <span style="text-align:justify;"> In continuation to our offer of employment with Amardeep Chemicals Industries Pvt Ltd .,a summary of the major benefits available to all employees is detailed below along with other terms and conditions of employment.</span><br>
           <h5 style="width:25%;">WORKING HOURS</h5><br>
           The standard work-week will be Monday through Saturday from 09:15 hours to 17:45 hours. Depending on the nature of the work schedule the standard work hours may be different for employees in some functions or practices.
           <br>
           <h5 style="width:100%;">PROBATION</h5><br>
            You will be on probation for period of Six months from the date of joining and your services are deemed to be on probation till your services are confirmed in writing .<br>
            Probation period can be extended for a period of three months or more on the advice of your reporting manager, and at the discretion of the Company depending on your performance.
           <br>
           <h5 style="width:100%;">WORK RULES</h5><br>
           You will also be entitled to and governed at all times by the policies, procedures, regulations and rules of the company in effect from time to time whether such policies are specified in the letter of appointment or elsewhere. You would be required to apply & maintain the highest standards of personal conduct and integrity and comply with all the policies and procedures of the company with punctuality.
          <br>
          <h5 style="width:100%;">EMPLOYMENT</h5><br>
         <span>You will devote your whole working time to the service of the company and will not engage in any other employment. Failure to comply with the above will subject you to immediate termination without notice or payment in lieu of notice.</span>
          <br>
           <h5 style="width:100%;">BACKGROUND REFERENCE CHECK</h5><br>
          <span>The Company, at any time (or as part of the joining formalities) conduct reference/ background check (including but not limited to the previous employers, education qualifications etc.) in the event the statements / particulars furnished by you is found to be false or misleading, Company reserves its right to terminate your services forthwith on the grounds of misrepresentation of the facts. Further in the event if it is found that you had indulged / been indulging in drugs and narcotics abuse or any other criminal activities or had any criminal records, Company shall have the right to terminate your services forthwith. You shall have no objection if the company makes it’s inquires in this regard as a pre-employment check.</span>
           <br>
          
            <h5 style="text-align: center;">EMPLOYEE BENEFITS</h5><br>
            <h5 style="width:100%;">HOLIDAYS</h5><br>
          <span>We observe 10 National and Festival Holidays per year.4 National Holidays are observed every year and you would be entitled to 6 other Festival Holidays from an Optional List.</span>
          <br>
          <h5 style="width:100%;">LEAVE</h5><br>
          <span> On completing one year’s continuous service with Amardeep every employee will be eligible for 30 days of leave (inclusive of 07 casual leave and 07 sick leaves). The leave will be proportionate to the number of days actually worked during the calendar yea</span><br>
         <br>
          <span>In the event, if you are absent from work for 24 hours or more then you are forthwith required to notify AMARDEEP about your absence along with reasons for the absence from work.</span><br>
         
         <h4 style="width:100%;color:brown">GRATUITY</h4><br>
       <span>As per the Payment of gratuity act 1972 , upon completing 5 years of continuous Service with AMARDEEP every employee will be eligible for the receipt of Gratuity a social security measure. The amount, equivalent to half month’s basic pay for every completed year of service will be paid to you at the time of your separation from</span><br>
         <div></div>   
            <span>AMARDEEP, be it by resignation, termination or retirement. AMARDEEP will not be liable to pay Gratuity to any employee</spa><br>
         <br>
         <span>who causes damage to the company through willful negligence & omission, destruction of Company property or misconduct including leaving the services of the company without proper notice.</span>
            <div></div>
          <h4 style="width:100%;color:brown">COMPENSATION PACKAGE</h><br>
         <span>We aim at paying attractive and competitive salaries to all our employees. Your compensation & benefits will be reviewed and revised annually, and any adjustments will be based on a thorough review of market conditions. Your individual performance and your contributions to AMARDEEP and Organization performance.</span>
         <br>
         <h5 style="width:100%;">PERFORMANCE REVIEW</h5><br>
         <span>At the discretion of the Company, your services will be reviewed on quarterly basis on set KRAs. However, the salary revisions will be done annually as per Company’s policy.</span>
        <br>
          <h4 style="width:100%;" align="center color:brown">TERMS OF SERVICE</h4><br>
         <h5 style="width:100%;">INTEGRITY</h5><br>
         <span>It must be specifically understood that this offer is made based on the professional skills. You have declared to possess as per your resume.</span>
            <br>
            <span>During the term of your employment with AMARDEEP currently or in the future or may be in conflict with the terms of your employment with AMARDEEP either directly or indirectly. This includes personal details viz, name, age, father name, contact address or professional information like qualification. Ability or previous or any other matter germane to employment at the time of employment or during the course of employment.</span>
            <br>
            <span>Should AMARDEEP at a later date during the term of your employment become aware that you have either suppressed any particulars or relevant information required to be disclosed by you or that you have furnished false/misleading information AMARDEEP reserves the right to terminate your services forthwith without any notice and without any obligation or liability to pay any remuneration or other dues to you irrespective of the period that you may have been employed by AMARDEEP.</span>
            <br>
            <span>Every employee is expected to follow the taxation laws rules and philosophy of compensation and benefits in the letter and spirit and uphold the values of honesty and integrity in all his/her actions in the course of doing so. Every employee shall claim only actual expenses and ensure compliance with the tax laws of the land in letter and spirit</span>
            <div></div>
            <h5 style="width:100%;">CONFIDENTIALITY</h5><br>
         <span>You are expected to maintain utmost secrecy with regard to the affairs of AMARDEEP and shall keep any information, instruments, manuals, relating to the company that may come to your professional knowledge as on associate of the company.</span><br>
           <br><br>
          <span>The position held by you is of a strictly confidential nature. As a result of employment at AMARDEEP the company may from time to time need to impart you with certain information/material pertaining to its business or its associate companies or any company. Firm or person with whom AMARDEEP or its associate companies may at any time be in technical. Commercial or financial cooperation or association. Which is to be treated as secret and confidential.</span>
          <br><br>
          <span>You shall not disclose to either during or after employment with the company any information about the interests or business of the company or any affiliated company or client.</span>
          <br><br>
          <span>During your employment or at any time after the termination of employment. you will not divulge to any unauthorized person any trade of manufacturing process or any knowledge or information concerning any matter or thing relating to the business or interests of AMARDEEP and its subsidiaries/associate companies or of any company firm or person with whom the AMARDEEP or its subsidiaries /associate companies may it any time be in technical commercial or financial cooperation or association. You will not utilize any secret or confidential information or knowledge acquired in consequence of your employment.</span>
           <br><br>
           <span>You shall keep confidential any information or manuals relating to the Company’s compensation and benefits schemes <div></div><div></div> that may come to your professional knowledge as an associate of the Company. You should maintain</span>
           <span>utmost secrecy with regard to compensation and benefits package and treat it as a highly individual and confidential matter not to be discussed with any colleague, other than your Manager.</span>
          <br><br>
          <span>You shall not except in accordance with any general or special order of the Company of in the performance, in good faith of the duties assigned to you communicate directly or indirectly any official document or any part thereof of information (including your salary to any other Officer or other associate or any other person to whom you are reporting).</span>
           <br><br>
           <span>You shall not either during employment with AMARDEEP or for a period of two years thereafter approach AMARDEEP business contacts. Business partners or customers for business of a similar nature either individually or as a company or organization where you have an investment an advisory role or whole –time employment in a decision-making capacity</span>
           <br><br>
           <span>You will be required to execute and be bound by a Non-Disclosure Agreement given to you along with the Employment Letter and such Agreement shall be co-extensive with this Employment Letter.</span>
           <div></div>
           <h5 style="width:100%;">AUTHORIZATION</h5><br>
        <span>The management of AMARDEEP shall be the only authorized signatory to sign any legal documents and shall only at its discretion may speak about the company, its business plans & current projects.</span>
          <br>
        <h5 style="width:100%;">SECURITY</h5><br>
        <span>The data/information held on organization’s systems is deemed to be the property of AMARDEEP. You shall be responsible for the protection of data/information and security of passwords. The data/information/passwords should not be shared even with your colleagues. You shall use the company’s email for official purpose only.</span>
        <br>
        <span>Information shall be available to you on a need-to-know basis/based on the roles and responsibilities. You shall be provided with a worktable and storage space which you shall ensure that such storage spaces are locked when attended. Duplicate keys will be maintained with security/Administration, you may take a duplicate key after signing for it for your own or a team member’s table or storage.</span>
            <br>
            <span>In case you work outside Office hours on the premises you are requested to produce your identity card to the Security personnel on demand. Any equipment taken out of the Office premises will require a gate pass duly authorized by the appropriate authority.</span>
            <br>
          <h5 style="width:100%;"><b>USE OF COMPANY RESOURCES</b></h5><br>
          <span>You shall be responsible for the safekeeping and good condition and order of all the AMARDEEP property entrusted to your care and charge. You may use the AMARDEEP resources only for Official purposes</span>
          <br>
          <h5 style="width:100%;">RETIREMENT AGE</h5><br>
          <span>The age of retirement for every associate of AMARDEEP is 60 years. You shall however during the tenure of the services be required to be medically fit for work. AMARDEEP may at its discretion request you to undergo periodic medical examination to enable professional determination of medical fitness for employment.</span>
         <br>
         <h5 style="width:100%;">TERMINATION</h5><br>
         <span>Your Service with the company may be terminated at anytime, after confirmation or during probation by giving written notice of 30 days or payment of one month’s salary if your performance is not upto to the satisfaction or expectation or if there is any misconduct against to the Policies, and interest of the company.</span>
         <br>
           <h5 style="width:100%;">HEALTH INSURANCE</h5>
           <span>All the employees are eligible for the medical benefit under Medi-claim Policy. It covers employee, spouse and two children (Dependents as per the policy) will be covered under the Company Medi-Claim.</span>
          <br>
          <span>All the employees are covered under the Group Term Life Insurance Policy.</span>
           <br>
           <span>Company has right to discontinue it, without giving any reasonable justification/reason.</span>
          <div></div><div></div><div></div>
          
            <h5 style="width:100%;">CODE OF CONDUCT</h5><br>
            <span>It is condition of this Appointment letter and your acceptance that in terms of your business activities and personal endeavors, your conduct will be in accordance with Company’s policies and code of conduct. You should comply with the legal requirements of each State in which, the Company conducts business and shall enjoy the highest ethical standards in any business dealings.</span>
            <br><br>
            <span>You will treat your colleagues, subordinates, superiors and female co workers with respect and dignity at the workplace.</span>
             <br><br>
             <span>Violation of these or any of the codes of conduct & discipline of the Company will result in immediate termination.</span>
           <br><br>
           <span>Whenever you change your present or local residence, or permanent address for any reason, you shall intimate the change to the Management immediately.</span>
            <br><br>
         <span>You will not leave the station of your place of employment without prior intimation to the immediate superior or Officer in charge of your department, as the case may be.</span>
          <div></div>
           <h5 style="width:100%;">ALLOWANCES & PERQUISITE</h5><br>
         <span>The Company will reimburse authorized reasonable expenses you incur on Company business during the course of employment. Claims for expenses will be subject to the Company’s Policy from time to time and approval from the Concerned Authority in writing. The Claim should be accompanies by reasonable proof of the expenditure. You will not be entitled to authorize your own expenses.</span>
          <br>
          <h5 style="width:100%;">INFRASTRUCTURE AND OFFICE EQUIPMENT</h5><br>
         <span>You will be provided with the basis Infrastructure facilities like laptop/desktop, SIM Card, Access Card, ID Card etc., depending upon the need and nature of your services. The IT team reserves the right to control & maintain the designed information and access to sites. Access to information will be provided depending upon the specific requirement of the user. Though the access to network is authorized through access privileges approved by the HOD and IT Dept.</span>
         <br>
         <span>Use of Company resources for personal use is strictly restricted. This includes usage of computer resources, information, internet service, and working time of the Company for any personal use.</span>
          <br>
        <h5 style="width:100%;">NOTICE PERIOD FOR RESIGNATION</h5><br>
         <span>This employment is directed towards a career at AMARDEEP. However, employment at AMARDEEP will always entail the conditions of satisfactory performance and satisfactory market conditions for AMARDEEP’S products and services (as it may determine at its sole discretion).The employee need to serve 7 days of notice period if leaving withing three months of Probation and 15 days notice period if leaving after three months of Joining during Probation.</span>
         <br><br>
         <span>For all the employees post confirmation the notice period for relieving form your services with AMARDEEP shall be 90 days or basic salary in lieu of notice period on part of AMARDEEP only.</span>
           <br><br>
           <span>Amardeep reserves the right to terminate your services without any notice or salary in lieu thereof on grounds of misconduct, disloyalty and negligence, commission of any act involving moral turpitude or any act of indiscipline or inefficiency or loss of confidence. In the event of any breach of the code of conduct or non-performance of contractual obligation or the terms and notwithstanding any other terms and conditions stipulated herein. AMARDEEP further reserves the right to invoke other legal remedies as it deems fit to protect its legitimate interests.</span>
          <br><br>
          <span>In case of employment termination for any reason the year-end performance incentive (if applicable) a part of your compensation structure will not be processed as part of full & final settlement.</span>
             <br>
          <h5 style="width:100%;">RETURN OF PROPERTY</h5>
          <span>On Separation of your employment or upon the demand of the Company, you should deliver to the Company all keys, identification cards and other related documents or materials in your possession provided by the Company. Furthermore, the Employee warrants and undertakes that he/ she, or through a third person, will not make, or allow to be made, any copy or records in any form of the above mentioned materials.</span>
         <div></div><div></div><div></div>
          <span>You have to settle all the advances taken by you during your employment with the Company or the same shall be recovered / settled during Full & Final calculations.</span>
           <br>
         <h5 style="width:100%;">TRANSFERS</h5><br>
           <span>Every employee of AMARDEEP is liable for transfer/deputation/secondment/training to any office of AMARDEEP or it’s associate companies’ client locations or third parties in India or abroad in such an event you will be governed by the terms and conditions of service applicable to the new assignment.</span>
           <br><br>
           <span>In all service matters, including those not specifically covered here such as travel etc. employees will be governed by the rules and policies of AMARDEEP in force from time to time.</span>
          <br>
         <h5 style="width:100%;">BUSINESS CONDUCT</h5><br>
         <span>You shall at all times maintain office decorum including in dealing with colleagues both with office premises and at client locations. Practices such as reading newspaper or magazines in the reception having obscene posters/work station screen servers at your work place standing in groups and having refreshments in common areas playing games at your work premises etc. should be strictly avoided.</span>
       <br>
      <h5 style="width:100%;">SEXUAL HARASSMENT</h5><br>
        <span>Any act or language with sexual overtones or implications proving offensive to colleague of the opposite or same sex will be construed as sexual misconduct and should be strictly avoided Offensive posters / screen savers/mails or magazines and books at your work place should be strictly avoided.</span>
        <br>
          <h5 style="width:100%;">INTELLECTUAL PROPERTY RIGHTS</h5>
         <span>You hereby expressly acknowledge and agree that any work that you may be conducting either on the premises of AMARDEEP or otherwise with regard to patents, improvements discoveries or any other form of intellectual property whether protected under law or not you are working on the express or implied instructions of AMARDEEP and on behalf of AMARDEEP .</span>
         <br><br>
          <span>Any invention, development, process, discovery, formulae, plan, specification program component, process adaptation or improvement in procedure or other matters or work including any artistic literary or other work which the subject matter of copyright may be whatsoever made. Developed or discovered by you, either alone or jointly with any person or persons while in employment with AMARDEEP. capable of being used or adapted for use therewith shall forthwith be disclosed to AMARDEEP and shall belong to and be the absolute property of AMARDEEP and shall be deemed to be “work made for hire”.</span>
          <br><br>
         <span>You also hereby irrevocably transfer and assign to AMARDEEP and waive and agree never to assert any and all Moral Rights you may have in or with respect to any work, documentations, designs and materials patents copyright or any other form of intellectual property where protected under law or not even after termination of your work during or after the tenure of your employment.</span>
         <br><br>
          <span>You shall not communicate to any public papers, journals, pamphlets or leaflets or cause to be disclosed at any time any information or documents official or otherwise relating to AMARDEEP expect with the prior approval (in writing) of the management.</span>
          <br><br>
         <h5 style="width:100%;">OTHER TERMS AND CONDITIONS</h5><br>
         <span>In addition, you shall be subjected to such other existing general terms and conditions of service as may be laid down by the Company to govern all members of its staff and to any changes to the terms and conditions of employment that may be introduced by the Company from time to time.</span>
         <br>
          <span>The terms of its appointment letter do not and or not intended to create either an express and / or implied contract of employment with the Company, and the Board of Directors of the Company reserves the right to change the terms of the letter unconditionally.</span>
          <br><br>
           <span>With acceptance of this employment, you accept that the restraints specified in this letter are reasonable in all the <div></div><div></div>circumstances for the protection of the company and its other group company’s legitimate interest.</span>
           <br><br>
          <span>By signing this document, you confirm that you have not entered into any other agreement with or undertaken obligations to others, including agreement with and obligation to previous employment that are in conflict with the terms herein.</span>
          <br><br>
          <span>All the above briefed terms and conditions are based on AMARDEEP’s policies, procedures and other rules currently applicable.</span>
         <br>
         <span>For,<b>AMARDEEP CHEMICAL INDUSTRIES PVT. LTD.</b></span>
         <div></div><div></div>
         <table>
         <tr>
             <td style="width:100%;  font-size: 12px;"><b>Mr. Dharmendra Patel</b></td>
             </tr><br>
             <tr>
         
         <td style="width:100%; font-size: 12px;"><b>Managing Director</b></td>
         </tr><br>
         <tr>
          
           <td style="width:100%;font-size: 12px;"><b>Vapi & Panoli</b></td>
        </tr>
         
         <h4 style="text-align: center;">Employee Acknowledgement</h4>
       
        <p style="width:40%;  font-size: 10px;">I accept all terms and Conditions of the company as stipulated above.</p>
                     <p style="width:40%; font-size: 10px;">I hereby accept the position on the terms and conditions of employment offered.</p>
                    
         <table>
         <tr>
            < td style="width:90%;font-size: 10px;">Name:</td>
         </tr>
        
         <tr>
        <td style="width:10%;text-align:right;font-size: 10px;">Signature:</td>
         <td style="width:50%;text-align:right;font-size: 10px;">Date:</td>
        </tr>
        
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('salary_annexur.pdf', 'I');
        
         }
 
    else if ($_GET["type"] == "getAllUserAccounts") {

        $output = array();
        $plant_id = $conn->real_escape_string($_GET["plant_id"]);

        $sql = "SELECT e.id, e.emp_id, e.firstname, e.middlename, e.lastname, e.department, e.designation, e.password, e.status,
                    (SELECT COUNT(*) FROM emp_rights er WHERE er.emp_id = e.emp_id AND er.plant_id = e.plant_id) AS rights_count,
                    (SELECT er2.isuser FROM emp_rights er2 WHERE er2.emp_id = e.emp_id AND er2.plant_id = e.plant_id ORDER BY er2.id DESC LIMIT 1) AS isuser,
                    (SELECT er2.ischecker FROM emp_rights er2 WHERE er2.emp_id = e.emp_id AND er2.plant_id = e.plant_id ORDER BY er2.id DESC LIMIT 1) AS ischecker,
                    (SELECT er2.isapprover FROM emp_rights er2 WHERE er2.emp_id = e.emp_id AND er2.plant_id = e.plant_id ORDER BY er2.id DESC LIMIT 1) AS isapprover,
                    (SELECT er2.dept_head FROM emp_rights er2 WHERE er2.emp_id = e.emp_id AND er2.plant_id = e.plant_id ORDER BY er2.id DESC LIMIT 1) AS dept_head
                FROM employee e
                WHERE e.plant_id = '$plant_id' AND (e.status IS NULL OR e.status != 'delete')
                  AND (e.employee_type IS NULL OR e.employee_type != 'Standard')
                ORDER BY e.id DESC";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['rights_given'] = (intval($row['rights_count']) > 0) ? 'Yes' : 'No';
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "getStandardLoginMatrix") {

        $output = array();
        $plant_id = $conn->real_escape_string($_GET["plant_id"]);

        $roles = array(
            array('label' => 'User', 'suffix' => 'user', 'flag' => 'isuser'),
            array('label' => 'Checker', 'suffix' => 'checker', 'flag' => 'ischecker'),
            array('label' => 'Approver', 'suffix' => 'approver', 'flag' => 'isapprover'),
            array('label' => 'Department Head', 'suffix' => 'head', 'flag' => 'dept_head')
        );

        $departments = array();
        $deptRes = $conn->query("SELECT DISTINCT department_name FROM department WHERE plant_id='$plant_id' AND department_name IS NOT NULL AND TRIM(department_name) != '' AND (status IS NULL OR status != 'delete') ORDER BY department_name ASC");
        if ($deptRes && $deptRes->num_rows > 0) {
            while ($drow = $deptRes->fetch_assoc()) {
                $departments[] = $drow['department_name'];
            }
        }

        // Fallback: if no department matched this plant, list every department in the table
        if (count($departments) == 0) {
            $deptResAll = $conn->query("SELECT DISTINCT department_name FROM department WHERE department_name IS NOT NULL AND TRIM(department_name) != '' AND (status IS NULL OR status != 'delete') ORDER BY department_name ASC");
            if ($deptResAll && $deptResAll->num_rows > 0) {
                while ($drow = $deptResAll->fetch_assoc()) {
                    $departments[] = $drow['department_name'];
                }
            }
        }

        foreach ($departments as $dept) {
            {
                $deptKey = preg_replace('/\s+/', '_', trim($dept));
                $accounts = array();
                foreach ($roles as $role) {
                    $emp_id = $deptKey . '_' . $role['suffix'];
                    $empEsc = $conn->real_escape_string($emp_id);
                    $exists = 'No';
                    $rights = 'No';
                    $eRes = $conn->query("SELECT emp_id FROM employee WHERE emp_id='$empEsc' AND plant_id='$plant_id' LIMIT 1");
                    if ($eRes && $eRes->num_rows > 0) {
                        $exists = 'Yes';
                    }
                    $rRes = $conn->query("SELECT " . $role['flag'] . " AS f FROM emp_rights WHERE emp_id='$empEsc' AND plant_id='$plant_id' ORDER BY id DESC LIMIT 1");
                    if ($rRes && $rRes->num_rows > 0) {
                        $rr = $rRes->fetch_assoc();
                        if ($rr['f'] == 'Yes') {
                            $rights = 'Yes';
                        }
                    }
                    $accounts[] = array(
                        'role' => $role['label'],
                        'suffix' => $role['suffix'],
                        'flag' => $role['flag'],
                        'emp_id' => $emp_id,
                        'password' => 'Med@2026',
                        'exists' => $exists,
                        'rights' => $rights
                    );
                }
                $output[] = array('department' => $dept, 'accounts' => $accounts);
            }
        }
        echo json_encode($output);
    }

    else if ($_GET["type"] == "createStandardLogin") {

        $department = isset($input["department"]) ? trim($input["department"]) : '';
        $roleSuffix = isset($input["role_suffix"]) ? trim($input["role_suffix"]) : '';
        $roleFlag   = isset($input["role_flag"]) ? trim($input["role_flag"]) : '';
        $roleLabel  = isset($input["role_label"]) ? trim($input["role_label"]) : '';

        $allowedFlags = array('isuser', 'ischecker', 'isapprover', 'dept_head');

        if ($department === '' || $roleSuffix === '' || !in_array($roleFlag, $allowedFlags)) {
            echo json_encode(array("status" => "invalid", "message" => "Missing department or role information."));
        } else {
            $plant_id  = $conn->real_escape_string($_GET["plant_id"]);
            $deptKey   = preg_replace('/\s+/', '_', $department);
            $emp_id    = $deptKey . '_' . $roleSuffix;
            $empEsc    = $conn->real_escape_string($emp_id);
            $deptEsc   = $conn->real_escape_string($department);
            $password  = 'Med@2026';
            $firstname = $conn->real_escape_string($department . ' ' . $roleLabel);
            $desigEsc  = $conn->real_escape_string($roleLabel);

            $eRes = $conn->query("SELECT emp_id FROM employee WHERE emp_id='$empEsc' AND plant_id='$plant_id' LIMIT 1");
            if (!$eRes || $eRes->num_rows == 0) {
                $conn->query("INSERT INTO employee (plant_id, employee_type, emp_id, firstname, department, designation, password, status, ISNEW, entry_by, entry_date)
                    VALUES ('$plant_id', 'Standard', '$empEsc', '$firstname', '$deptEsc', '$desigEsc', '$password', 'Active', 'NO', '".$_GET["emp_id"]."', '$entry_date')");
            }

            $isuser     = ($roleFlag == 'isuser') ? 'Yes' : 'No';
            $ischecker  = ($roleFlag == 'ischecker') ? 'Yes' : 'No';
            $isapprover = ($roleFlag == 'isapprover') ? 'Yes' : 'No';
            $dept_head  = ($roleFlag == 'dept_head') ? 'Yes' : 'No';

            $rRes = $conn->query("SELECT id FROM emp_rights WHERE emp_id='$empEsc' AND plant_id='$plant_id' LIMIT 1");
            if ($rRes && $rRes->num_rows > 0) {
                $ok = $conn->query("UPDATE emp_rights SET isuser='$isuser', ischecker='$ischecker', isapprover='$isapprover', dept_head='$dept_head',
                    status='approve', update_by='".$_GET["emp_id"]."', update_date='$entry_date' WHERE emp_id='$empEsc' AND plant_id='$plant_id'");
            } else {
                $ok = $conn->query("INSERT INTO emp_rights (department, user_no, emp_id, isuser, ischecker, isapprover, dept_head, isauditor, qms_approver, shift_allocator, trainig_cordinator, task_assigner, status, plant_id, main, entry_by, entry_date)
                    VALUES ('$deptEsc', '".$_GET["emp_id"]."', '$empEsc', '$isuser', '$ischecker', '$isapprover', '$dept_head', 'No', 'No', 'No', 'No', 'No', 'approve', '$plant_id', 'Yes', '".$_GET["emp_id"]."', '$entry_date')");
            }

            if ($ok) {
                echo json_encode(array("status" => "success", "emp_id" => $emp_id, "password" => $password));
            } else {
                echo json_encode(array("status" => $conn->error));
            }
        }
    }

}



$conn->close();

function medicap_normalize_emp_document_row($row) {
    if (!is_array($row)) {
        return $row;
    }
    $name = '';
    foreach (array('documentName', 'document_name', 'DocumentName', 'doc_name') as $k) {
        if (!empty($row[$k])) {
            $name = $row[$k];
            break;
        }
    }
    $file = '';
    foreach (array('fileName', 'file_name', 'filename', 'FileName', 'doc') as $k) {
        if (!empty($row[$k])) {
            $file = $row[$k];
            break;
        }
    }
    $row['documentName'] = $name;
    $row['fileName'] = $file;
    return $row;
}

function isWeekend($date) {
    $weekDay = date('w', strtotime($date));
    return ($weekDay == 0);
}
?>
