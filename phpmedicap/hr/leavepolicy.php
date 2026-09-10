<?php 



// ini_set('display_errors', 1);
// error_reporting(E_ALL);



require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
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
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveLeaveType") {
        $sql="SELECT * FROM leave_types WHERE leave_type='".$input["leave_type"]."' AND plant_id ='".$_GET["plant_id"]."' ";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Leave Type Exists. Duplicate Values are not allowed\"}";
        }
        else {
        $sql = "INSERT INTO leave_types (plant_id,leave_type,entry_by,entry_date) VALUES ('".$_GET["plant_id"]."','".$input["leave_type"]."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        }
    } 
    else if ($_GET["type"] == "getLeaveTypesEmployee") {
        
           $sql1 = "SELECT *,(select operator_category from employee where emp_id = '".$_GET["emp_id"]."' limit 1) as operator_category FROM leaveform WHERE emp_id = '".$_GET["emp_id"]."' AND leave_type like '%half day%' 
AND MONTH(leave_from) = MONTH(CURRENT_DATE()) 
AND YEAR(leave_from) = YEAR(CURRENT_DATE())";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            $halfday=1;
            // echo ('half=1');
        }else{
            $halfday=0;
            //   echo ('half=0');
        }
             $sql1 = "SELECT * ,(select operator_category from employee where emp_id = '".$_GET["emp_id"]."' limit 1) as operator_category  FROM leaveform WHERE emp_id = '".$_GET["emp_id"]."' AND leave_type like '%Short Leave%' AND MONTH(leave_from) = MONTH(CURRENT_DATE()) 
AND YEAR(leave_from) = YEAR(CURRENT_DATE())";
        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            $short_leave=1;
            //   echo ('short=1');
        }else{
            $short_leave=0;
            // echo ('short=1');
        }
        
        
        
           $output = array();
        if($halfday==1 && $short_leave==1){
            
         $sql = "SELECT * ,(select operator_category from employee where emp_id = '".$_GET["emp_id"]."' limit 1) as operator_category  FROM leave_types WHERE (leave_type NOT LIKE '%short leave%' and leave_type NOT LIKE '%half day%')";
        }else if($halfday==1){
         $sql = "  SELECT *  ,(select operator_category from employee where emp_id = '".$_GET["emp_id"]."' limit 1) as operator_category FROM leave_types WHERE leave_type NOT LIKE '%half day%'";
            
        }else if($short_leave==1){
          $sql = "  SELECT *  ,(select operator_category from employee where emp_id = '".$_GET["emp_id"]."' limit 1) as operator_category FROM leave_types WHERE leave_type NOT LIKE '%short leave%'";
        
            
        }
        else{
             $sql = "  SELECT *  ,(select operator_category from employee where emp_id = '".$_GET["emp_id"]."' limit 1) as operator_category FROM leave_types   ";
        }
        
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                
            }
        }
    echo json_encode($output);
       
    }
    else if ($_GET["type"] == "getJoiningDateEmployee") {
        $output = array();
$sql = "SELECT joining_date FROM employee WHERE emp_id = '" . $_GET["emp_id"] . "'  ";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    $joining_date = new DateTime($row['joining_date']); // Convert string to DateTime
    $current_date = new DateTime(); // Get the current date

    // Calculate the difference in months
    $interval = $joining_date->diff($current_date);
    $months = ($interval->y * 12) + $interval->m; // Convert years to months and add remaining months

    $output = [
        'joining_date' => $row['joining_date'],
        'months_since_joining' => $months
    ];
}
     echo json_encode($output);   
    }
    else if ($_GET["type"] == "getLeaveTypes") {
        
         
        
        
        
        
        
          $output = array();
        $sql = "SELECT * FROM leave_types  ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                
            }
        }
    echo json_encode($output);
       
    }
    else if ($_GET["type"] == "saveLeavePolicy") {
         
         $sql = "INSERT INTO leavepolicy (plant_id,designation_heading,emp_type,Eefective_from,Eefective_to,total_leave,allow_leave,leave_deduct,
        salary_day,over_time,leaveList,entry_by,entry_date) VALUES ('".$_GET["plant_id"]."','".$input["designation_heading"]."',
        '".$input["emp_type"]."','".$input["Eefective_from"]."','".$input["Eefective_to"]."','".$input["total_leave"]."',
        '".$input["allow_leave"]."','".$input["leave_deduct"]."','".$input["salary_day"]."','".$input["over_time"]."',
        '".json_encode($input["leaveList"])."','".$_GET["emp_id"]."','$entry_date')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
            
    
    }
    else if ($_GET["type"] == "saveLeavePolicyMeha") {
        
        
                $json_obj = json_encode($input["leaveList"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
      $sql = "INSERT INTO leavepolicy (leave_days,leave_title,plant_id,designation_heading,emp_type,Eefective_from,Eefective_to,total_leave,allow_leave,leave_deduct,
        salary_day,over_time,leaveList,entry_by,entry_date) VALUES ('".$values["days"]."','".$values["leave_type"]."','".$_GET["plant_id"]."','".$input["designation_heading"]."',
        '".$input["emp_type"]."','".$input["Eefective_from"]."','".$input["Eefective_to"]."','".$input["total_leave"]."',
        '".$input["allow_leave"]."','".$input["leave_deduct"]."','".$input["salary_day"]."','".$input["over_time"]."',
        '".json_encode($input["leaveList"])."','".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
             $status1 = true;
        } else {
            $status1 = false;
        }
                    
                }
        
         if ($status1) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
 
            
    
    }
    else if ($_GET["type"] == "getLeavePolicy") {
        $output = array();
       $sql="SELECT * From leavepolicy  where plant_id='".$_GET["plant_id"]."'";
                
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["leaveList"] = json_decode($row["leaveList"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "updateLeavePolicy"){
        $sql = "UPDATE leavepolicy SET leave_taken='".$input["leave_taken"]."' , leave_balance='".$input["leave_balance"]."' WHERE id ='".$_GET["id"]."' AND status = 'active' ";
        if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }
    else if ($_GET["type"] == "deleteBonus") {
        $sql = "UPDATE bonus SET status='Deleted' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getRateMrp") {
        $output = array();
        $sql = "SELECT r.*,c.LglNm,p.product_name FROM ratemrp r LEFT JOIN  client c ON r.client_code=c.client_code LEFT JOIN product p ON r.product_code=p.product_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "editRateMrp") {
        $sql = "UPDATE ratemrp SET product_code='".$_GET["product_code"]."',category='".$_GET['category']."',client_code='".$_GET['client_code']."',rate='".$_GET['rate']."', entry_by='".$_GET["emp_id"]."', entry_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "downloadRateMrp") {
        $_GET['filename'] = 'Rate MRP Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $html.='
        <h2 style="text-align:center">Rate MRP Log</h2>
        <table border="1" cellpadding="3">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;">Sr.</td>
                    <td style="width: 20%;">Product Name</td>
                    <td style="width: 20%;">Client  Name</td>
                    <td style="width: 20%;">Category</td>
                    <td style="width: 20%;">rate</td>
                </tr>
            </thead>';
            $i=1;
            $sql = "SELECT r.*,c.LglNm,p.product_name FROM ratemrp r LEFT JOIN  client c ON r.client_code=c.client_code LEFT JOIN product p ON r.product_code=p.product_code";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                    <td style="width: 20%;">'.$i++.'.</td>
                    <td style="width: 20%;">'.$row['product_name'].'</td>
                    <td style="width: 20%;">'.$row['LglNm'].'</td>
                    <td style="width: 20%;">'.$row['category'].'</td>
                    <td style="width: 20%;">'.$row['rate'].'</td>
                </tr>';
                }
            }
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Rate Mrp.pdf', 'I');
    }
    
    else if ($_GET["type"] == "getleave_cardempdash"){
        $output = Array();
        $sql = "SELECT l.*, e.firstname as afirstname,e.lastname as alastname FROM leave_card l left join employee e ON e.emp_id = l.emp_id where l.emp_id = '".$_GET["emp_id"]."' and e.plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                //  $row["leaveList"] = json_decode($row["leaveList"]);
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getleave"){
        $output = Array();
        $sql = "SELECT l.*, e.firstname as afirstname,e.lastname as alastname FROM leave_card l left join employee e 
        ON e.emp_id = l.emp_id where l.plant_id = '".$_GET["plant_id"]."' and e.plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                //  $row["leaveList"] = json_decode($row["leaveList"]);
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "HOgetleave"){
        $output = Array();
        $sql = "SELECT l.*, e.firstname as afirstname,e.lastname as alastname FROM leave_card l left join employee e
        ON e.emp_id = l.emp_id where l.plant_id = '".$_GET["plantID"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                //  $row["leaveList"] = json_decode($row["leaveList"]);
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "save_leave_card") {
        
        $sql ="INSERT INTO leave_card (plant_id,firstname,emp_id,leave_title,designation_heading,Eefective_from,Eefective_to,total_leave,
        allow_leave,leave_deduct,salary_day,over_time, leave_taken, leave_balance,leaveList) 
        VALUES ('".$_GET["plant_id"]."','".$input["firstname"]."','".$input["emp_id"]."','".$input["leave_title"]."','".$input["designation_heading"]."',
        '".$input["Eefective_from"]."','".$input["Eefective_to"]."','".$input["total_leave"]."','".$input["allow_leave"]."','".$input["leave_deduct"]."',
        '".$input["salary_day"]."','".$input["over_time"]."','".$input["leave_taken"]."','".$input["leave_balance"]."',
        '".json_encode($input["leaveList"])."')";

        if ($conn->query($sql)) 
        {
            echo "{\"status\":\"success\"}";
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 

}

$conn->close();
?>