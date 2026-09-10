<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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


if($_GET["type"]=="getDesignations") {
    $output = Array();
	$sql = "SELECT * FROM designation WHERE status='active'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $row["responsibilities"] = json_decode($row["responsibilities"]);
			$output[] = $row;
		}
	}
	echo json_encode($output);
} 
else if($_GET["type"]=="getdepartments") {
    $output = Array();
 	$sql = "SELECT * FROM department  ";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
 			$output[] = $row;
		}
	}
	echo json_encode($output);
} 


else if ($_GET["type"] == "getPendingEmployees") {
    $output = Array();
 	$sql = "SELECT id,emp_id,firstname,middlename,lastname,emp_level,department,designation,plant_id
	FROM employee WHERE responsibility= 'Pending'   AND plant_id = '".$_GET["plant_id"]."' 
	 AND department = '".$_GET["jaduDept"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
	 
			$output[] = $row;
		}
	}
	echo json_encode($output);
} 
else if ($_GET["type"] == "getJobResLog") {
    $output = Array();
    
    if($_GET["jaduDept"] == 'ALL'){
            
  	$sql = "SELECT a.id,a.emp_id,TRIM(CONCAT_WS(' ', a.firstname, a.lastname)) AS empName,a.emp_level,a.department,a.designation,a.plant_id,a.responsibilities,
 	a.designee,a.reportedTo,a.effectiveDate,
 	    (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS designeeName From employee e WHERE e.emp_id = a.designee) as designeeName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS reportedToName From employee e WHERE e.emp_id = a.reportedTo) as reportedToName
 	FROM employee a WHERE a.responsibility != 'Pending'   AND a.plant_id = '".$_GET["plant_id"]."'   ";
        
    }else{
            
  	$sql = "SELECT a.id,a.emp_id,TRIM(CONCAT_WS(' ', a.firstname, a.lastname)) AS empName,a.emp_level,a.department,a.designation,a.plant_id,a.responsibilities,
 	a.designee,a.reportedTo,a.effectiveDate,
 	    (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS designeeName From employee e WHERE e.emp_id = a.designee) as designeeName,
        (SELECT TRIM(CONCAT_WS(' ', firstname, lastname)) AS reportedToName From employee e WHERE e.emp_id = a.reportedTo) as reportedToName
 	    FROM employee a WHERE a.responsibility != 'Pending'   AND a.plant_id = '".$_GET["plant_id"]."'  AND department = '".$_GET["jaduDept"]."' ";
    }
    
    
 
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
	 	 	$row["responsibilities"] = json_decode($row["responsibilities"]);
			$output[] = $row;
		}
	}
	echo json_encode($output);
} 
else if ($_GET["type"] == "getALLDeptEmployees") {
    $output = Array();
 	$sql = "SELECT id,emp_id,firstname,middlename,lastname,emp_level,department,designation,plant_id
	FROM employee WHERE  plant_id = '".$_GET["plant_id"]."' AND department = '".$_GET["jaduDept"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
	 
			$output[] = $row;
		}
	}
	echo json_encode($output);
} 
else if ($_GET["type"] == "respGetLog") {
    $output = Array();
	$sql = "SELECT e.id,e.emp_id,e.firstname,e.middlename,e.lastname,e.emp_level,e.department,e.designation,e.plant_id,e.responsibilities,e.responsibility,e.designee,
	e.reportedTo,e.effectiveDate,
	 (SELECT CONCAT(d.firstname, ' ', d.lastname) 
                FROM employee d 
                WHERE e.designee = d.emp_id 
                LIMIT 1) AS designee_name, 
               (SELECT CONCAT(r.firstname, ' ', r.lastname) 
                FROM employee r 
                WHERE e.reportedTo = r.emp_id 
                LIMIT 1) AS reportedTo_name
                FROM employee e WHERE responsibility != 'Pending'   AND plant_id = '".$_GET["plant_id"]."' 
	 AND department = '".$_GET["jaduDept"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
	 	    $row["responsibilities"] = json_decode($row["responsibilities"]);
			$output[] = $row;
		}
	}
	echo json_encode($output);
} 
else if ($_GET["type"] == "getJdForQaLog") {
    $output = Array();
	$sql = "SELECT e.id,e.emp_id,e.firstname,e.middlename,e.lastname,e.emp_level,e.department,e.designation,e.plant_id,e.responsibilities,e.responsibility,e.designee,
	e.reportedTo,e.effectiveDate,
	 (SELECT CONCAT(d.firstname, ' ', d.lastname) 
                FROM employee d 
                WHERE e.designee = d.emp_id 
                LIMIT 1) AS designee_name, 
               (SELECT CONCAT(r.firstname, ' ', r.lastname) 
                FROM employee r 
                WHERE e.reportedTo = r.emp_id 
                LIMIT 1) AS reportedTo_name
                FROM employee e WHERE responsibility = 'Approved'   AND plant_id = '".$_GET["plant_id"]."' 
	 AND department = '".$_GET["jaduDept"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
	 	    $row["responsibilities"] = json_decode($row["responsibilities"]);
			$output[] = $row;
		}
	}
	echo json_encode($output);
} 


else if ($_GET["type"] == "saveJobResponsibilities") {
 
     $sql = "UPDATE employee SET responsibilities='".json_encode($input["resp"])."', responsibility='TO_EMPLOYEE' , 
    designee='".$input["designee"]."', reportedTo='".$input["reportedTo"]."',effectiveDate='".$input["effectiveDate"]."'
       WHERE emp_id='".$input["emp_code"]."'";
    if($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 



else if($_GET["type"] == "jobRespLog") {

$_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
 $sql = "SELECT * FROM employee WHERE id='" . $_GET["id"] . "'";

$html = "";
$html.='
<table style="width:540px;border: none;">
    <tr style="text-align:left; font-weight: bold; height:30px;">
        <td style="text-align:center; width:540px;border: none;"><h2>Specimen Copy of Job Responsibilty</h2></td>
    </tr>';
    
    $html.='</table>';
    
    
     $html.='<div></div>';
     $html.='<div></div>';
 $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

$html.='<table style="width: 540px;">
  <tr style="text-align:left;height:25px;">
        <td style="text-align:left; width:100px; font-weight: bold;height:25px;">Name</td>
        <td style="text-align:left; width:10px;height:25px;">:</td>   
        <td style="text-align:left; width:135px;height:25px;">' . $row['firstname'] . ' ' . $row['lastname'] . '</td>
         <td style="text-align:left; width:140px;font-weight: bold;height:25px;">Job Responsibility No.</td>
        <td style="text-align:left; width:10px;height:25px;">:</td>   
        <td style="text-align:left; width:145px;height:25px;"></td>   
    </tr>
     <tr style="text-align:left;height:25px;">
        <td style="text-align:left; width:100px; font-weight: bold;height:25px;">Department</td>
        <td style="text-align:left; width:10px;height:25px;">:</td>   
        <td style="text-align:left; width:135px;height:25px;">' . $row['department'] . '</td>   
        <td style="text-align:left; width:140px;font-weight: bold;height:25px;">Date of Joining</td>
        <td style="text-align:left; width:10px;height:25px;">:</td>   
        <td style="text-align:left; width:145px;height:25px;">' . $row['joining_date'] . '</td>   
    </tr>
     <tr style="text-align:left;height:25px;">
        <td style="text-align:left; width:100px; font-weight: bold;height:25px;">Designation</td>
        <td style="text-align:left; width:10px;height:25px;">:</td>   
        <td style="text-align:left; width:135px;height:25px;">' . $row['designation'] . '</td>   
        <td style="text-align:left; width:140px;font-weight: bold;height:25px;">Employee Code</td>
        <td style="text-align:left; width:10px;height:25px;">:</td>   

        <td style="text-align:left; width:145px">' . $row['firstname'] . '</td>   
    </tr>
     <tr style="text-align:left;height:30px;">
        <td style="text-align:left; width:100px; font-weight: bold;">Reporting To</td>
        <td style="text-align:left; width:10px">:</td>   
        <td style="text-align:left; width:135px">' . $row['reportedTo'] . '</td>   
        <td style="text-align:left; width: 140px;font-weight: bold;">With Effective from</td>
        <td style="text-align:left; width:10px">:</td>   
        <td style="text-align:left; width:145px">' . $row['effectiveDate'] . '</td>   
    </tr>';
            }}
$html.='</table>';
   
$html.='<div></div>';

$html.='<div></div>';
 $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

$html.=' <table border="1">
 <tr style="width: 540px;">
 <td style="text-align:center; width:50px; font-weight: bold;height:25px;"><b>Sr.No</b></td>
              <td style="text-align:center; width:490px; font-weight: bold;height:25px;"><b>Description</b></td>
          </tr> ';

                $json_obj = $row['responsibilities'];
                $array = json_decode($json_obj, true);
                $k=1;
                foreach ($array as $values)
                {
                    $term_heading = $values['description'];
          $html.='
          <tr>
          <td style=" text-align:center;height:25px;"> ' . $k++ . '</td>
          <td style="text-align:left;height:25px;"> ' . $term_heading . '</td>
      </tr>';
                }
}
            
        }
     $html.=' </table>';

    
    
    
    
    
    
    
 $html.='<div></div>';
     $html.='<div></div>';
     $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
$html.='<table style="width:540px;border:none;">
 <tr style="text-align:left;height:30px;border:none;">
        <td style="text-align:left; width:540px;border: none;height:30px;">I '. $row['firstname'].' ' . $row['lastname'] . ' have read and accepted the above Job responsibilities. 
        In the absence of Mr. / Ms. ' . $row['firstname'] . ' ' . $row['lastname'] . ' , the assigned responsibilities can be
handled by his / her designee  '.$row['designee'].'   or as approved by the undersigned.</td>
    </tr>';
            }}
    
    $html.='</table>';
   

$html.='<div></div>';
     $html.='<div></div>';


$result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
$html.='<table style="width: 54px0px" border="1">
    <tr style="text-align:left; font-weight: bold;">
        <th style="width:80px"px></th>
        <th style="width:160px;text-align:center;height:25px;">Accepted By Employee</th>
        <th style="width:150px;text-align:center;height:25px;">Accepted By Designee</th>
        <th style="width:150px;text-align:center;height:25px;">HOD</th>
          
    </tr>
    <tr>
        <th style="text-align:center; font-weight: bold;">Name</th>
        <td style="width:160px;text-align:center;height:25px;">' . $row['firstname'] . ' ' . $row['lastname'] . '</td>
        <td style="width:150px;text-align:center;height:25px;">' . $row['AcceptByDesigneeBy'] . '</td>
        <td style="width:150px;text-align:center;height:25px;">' . $row['approveHodBy'] . '</td>
    </tr>
    <tr>
        <th style="text-align:center; font-weight: bold;">User Id</th>
        <td style="width:160px;text-align:center;height:25px;"></td>
        <td style="width:150px;text-align:center;height:25px;"></td>
        <td style="width:150px;text-align:center;height:25px;"></td>
    </tr>
    <tr>
        <th style="text-align:center; font-weight: bold;">Signature</th>
        <td style="width:160px;text-align:center;height:25px;"></td>
        <td style="width:150px;text-align:center;height:25px;"></td>
        <td style="width:150px;text-align:center;height:25px;"></td>
    </tr>

     <tr>
        <th  style="text-align:center; font-weight: bold;">Date/Time</th>
        <td style="width:160px;text-align:center;height:25px;">' . $row['acceptByEmpOn'] . '</td>
        <td style="width:150px;text-align:center;height:25px;">' . $row['AcceptByDesigneeOn'] . '</td>
        <td style="width:150px;text-align:center;height:25px;">' . $row['approveHodOn'] . '</td>
    </tr>';
 }}
$html.='</table>';

$pdf->writeHTML($html, true, false, false, false, '');

$pdf->Output('downloadjoblog.pdf', 'I');
    

}



else if ($_GET["type"] == "AcceptByEmployee") {
 
    $sql = "UPDATE employee SET   responsibility = 'TO_DESIGNEE' ,acceptByEmpBy = '".$_GET["emp_id"]."',
    acceptByEmpOn = '$entry_date' WHERE emp_id = '".$_GET["emp_code"]."'";
     
    if($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 
else if ($_GET["type"] == "AcceptByDesignee") {
 
    $sql = "UPDATE employee SET   responsibility = 'TO_HOD' ,AcceptByDesigneeBy = '".$_GET["emp_id"]."',
    AcceptByDesigneeOn = '$entry_date' WHERE emp_id = '".$_GET["emp_code"]."'";
     
    if($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 
else if ($_GET["type"] == "ApproveFromHod") {
 
    $sql = "UPDATE employee SET   responsibility = '".$_GET["status"]."' ,approveHodBy = '".$_GET["emp_id"]."',
    approveHodOn = '$entry_date' WHERE emp_id = '".$_GET["emp_code"]."'";
     
    if($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 
 
else if ($_GET["type"] == "getInprocessResponsibilities") {
    $output = Array();
	$sql = "SELECT * FROM employee WHERE responsibility ='inprocess'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $row["responsibilities"] = json_decode($row["responsibilities"]);
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if ($_GET["type"] == "approveResponsibilities") {
    $input["approve_by"] = $_GET["emp_id"];
    $input["approve_date"] = $entry_date;
    $sql = "UPDATE employee SET responsibilities='".json_encode($input)."', responsibility='".$input["status"]."' WHERE emp_id='".$_GET["emp_code"]."'";
    if($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} 

else if ($_GET["type"] == "getMyJD") {
    $output = Array();
 	$sql = "SELECT e.id,e.emp_id,e.firstname,e.middlename,e.lastname,e.emp_level,e.department,e.designation,e.plant_id,e.responsibilities,
 	e.responsibility,e.designee,
	e.reportedTo,e.effectiveDate,
	 (SELECT CONCAT(d.firstname, ' ', d.lastname) 
                FROM employee d 
                WHERE e.designee = d.emp_id 
                LIMIT 1) AS designee_name, 
               (SELECT CONCAT(r.firstname, ' ', r.lastname) 
                FROM employee r 
                WHERE e.reportedTo = r.emp_id 
                LIMIT 1) AS reportedTo_name
                FROM employee e WHERE e.responsibility != 'Pending' AND e.emp_id='".$_GET["emp_id"]."' AND e.plant_id = '".$_GET["plant_id"]."' ";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $row["responsibilities"] = json_decode($row["responsibilities"]);
				$output[] = $row;
		}
	}  
	echo json_encode($output);
} 
else if ($_GET["type"] == "getFroDesigneeAcceptance") {
    $output = Array();
 	$sql = "SELECT e.id,e.emp_id,e.firstname,e.middlename,e.lastname,e.emp_level,e.department,e.designation,e.plant_id,e.responsibilities,e.responsibility,e.designee,
	e.reportedTo,e.effectiveDate,
	 (SELECT CONCAT(d.firstname, ' ', d.lastname) 
                FROM employee d 
                WHERE e.designee = d.emp_id 
                LIMIT 1) AS designee_name, 
               (SELECT CONCAT(r.firstname, ' ', r.lastname) 
                FROM employee r 
                WHERE e.reportedTo = r.emp_id 
                LIMIT 1) AS reportedTo_name
                FROM employee e WHERE e.responsibility = 'TO_DESIGNEE' AND e.designee='".$_GET["emp_id"]."' AND e.plant_id = '".$_GET["plant_id"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $row["responsibilities"] = json_decode($row["responsibilities"]);
				$output[] = $row;
		}
	}  
	echo json_encode($output);
} 
else if ($_GET["type"] == "getResForDeptHead") {
    $output = Array();
  	$sql = "SELECT e.id,e.emp_id,e.firstname,e.middlename,e.lastname,e.emp_level,e.department,e.designation,e.plant_id,e.responsibilities,e.responsibility,e.designee,
	e.reportedTo,e.effectiveDate,
	 (SELECT CONCAT(d.firstname, ' ', d.lastname) 
                FROM employee d 
                WHERE e.designee = d.emp_id 
                LIMIT 1) AS designee_name, 
               (SELECT CONCAT(r.firstname, ' ', r.lastname) 
                FROM employee r 
                WHERE e.reportedTo = r.emp_id 
                LIMIT 1) AS reportedTo_name
                FROM employee e WHERE e.responsibility = 'TO_HOD' AND e.department='".$_GET["jaduDept"]."' AND e.plant_id = '".$_GET["plant_id"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()) {
		    $row["responsibilities"] = json_decode($row["responsibilities"]);
				$output[] = $row;
		}
	}  
	echo json_encode($output);
} 

else if ($_GET["type"] == "acceptResponsibilities") {
    $input["status"] = "accept";
    $input["accept_by"] = $_GET["emp_id"];
    $input["accept_date"] = $entry_date;
    $sql = "UPDATE employee SET responsibilities='".json_encode($input)."', responsibility='accept' WHERE emp_id='".$_GET["emp_id"]."'";
    if($conn->query($sql)) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
}


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>