<?php
    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    
//   ini_set('display_errors', 1);
// error_reporting(E_ALL);
    
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

if($_GET["type"]=="save_calibration"){
    
    if($input["comp_check1"]["remark"]==1){
        $remark='complies ';
    }else{
        $remark='non complies';
    }

	$sql = "INSERT INTO daily_caibration (plant_id, actual_qt1, date, remark, spirit, equipment_id, location) 
          VALUES ('" . $_GET["plant_id"] . "', '" .json_encode( $input["comp_check1"]["actual_qt1"]) . "', '" . $input["comp_check1"]["date"] . "', ' $remark', '" . $input["comp_check1"]["spirit"] . "', '" . $input["equipment_id"] . "', '" . $input["location"] . "')"; 

	if($conn->query($sql)===TRUE){	
		echo "{\"status\":\"success\"}";
	}
	else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}

   else if ($_GET["type"] == "save_finish") {
  $sql = "INSERT INTO save_finish ( plant_id,ref_staderd,ana_proc,source,ref_name,max_cap,model,equipment_code,equipment_name,ana_date1,instr_used1,des_method1ana_date,product_name,batch_no) VALUES 
  ( '".$_GET["plant_id"]."','".$input["ref_staderd"]."','".$input["ana_proc"]."', '".$input["source"]."', '".$input["ref_name"]."', '".$input["max_cap"]."', 
        '".$input["model"]."', '".$input["equipment_code"]."', '".$input["equipment_name"]."', '".$input["ana_date1"]."', '".$input["instr_used1"]."'
        , '".$input["des_method1ana_date"]."', '".$input["product_name"]."', '".$input["batch_no"]."')";       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
else if($_GET["type"]=="save_full_range_wt"){
    
  

	$sql = "INSERT INTO full_range_calibration_weight (plant_id, unit, range_to, range_from, standard_weight) 
          VALUES ('" . $_GET["plant_id"] . "',  '" . $input["unit"] . "', '" . $input["range_to"]. "', '" . $input["range_from"] . "', '" . $input["standard_weight"] . "')"; 

	if($conn->query($sql)===TRUE){	
		echo "{\"status\":\"success\"}";
	}
	else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="get_standard_weights"){
	$sql = "SELECT * FROM daily_caibration where equipment_id='".$_GET["equipment_code"]."'";
		$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
		     $row["actual_qt1"] = json_decode($row["actual_qt1"]);
			$output[] = $row;
		}
	}
	echo json_encode($output);
}

else if($_GET["type"]=="getfull_range_calibration_weight"){
	$output = Array();

     	  $sql = "select * from full_range_calibration_weight where plant_id='".$_GET["plant_id"]."' ";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
 }
 
 else if($_GET["type"] == "downloagdmonthly13") {
    
        require '../tcpdf/tcpdf.php';
        
        $_GET['filename'] = "Unit Formula Log"; $_GET['pdftype'] = "landscape"; include("../pdfimp2.php");
                $sql22="SELECT * FROM monthly_calibration WHERE plant_id='".$_GET["plant_id"]."'AND id = '".$_GET["id"]."' ";

   //     $sql22 = "SELECT * FROM equipment_standard_weight a LEFT JOIN equipment b on b.id=a.equipment_id WHERE b.id='".$_GET["id"]."'"; 
            $result22 = $conn->query($sql22);
            $result22->num_rows > 0;
               $row22 = $result22->fetch_assoc();
             $rows = $result22->fetch_all(MYSQLI_ASSOC);

    // Get the total number of rows
    $numRows = count($rows)+1;
   
      
          
                    
               
$fullRangeCalibrationJson = $row['Full_Range_calibration'];
$fullRangeCalibration = json_decode($fullRangeCalibrationJson, true);


$Corner_Load_TestJson = $row['Corner_Load_Test'];
$Corner_Load_Test = json_decode($Corner_Load_TestJson, true);


 $Repetability_Test= $row['Repetability_Test'];
  $Repetability_Test_DATA= json_decode($Repetability_Test, true);
                
                
                    $html.='

<table border="1">
<tr>
     <td style="line-height:20px;width: 120px;border-bottom:none;text-align:center;"rowspan="2"> </td>
     <td style="line-height:30px;width: 270px;text-align:center;"rowspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;   NOVO EXCIPIENTS PVT.LTD.,NAVI MUMBAI   QUALITY CONTROL DEPARTMENT </td>
     <td style="line-height:30px;width: 90px;border-bottom:none;text-align:left;"> Issued By/ On:</td>
     <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">  </td>
 </tr>
 <tr>
     <td style="line-height:20px;width: 90px;border-bottom:none;text-align:left;"> No. of Copies:</td>
     <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"> </td>
 </tr>

<tr>
     <td style="line-height:20px;width: 120px;border-bottom:none;text-align:left;"> Format Title:</td>
     <td style="line-height:20px;width: 420px;text-align:left;"> Monthly Calibration Of Weighing Balance</td>
 </tr>
<tr>
     <td style="line-height:20px;width: 120px;border-bottom:none;text-align:left;"> Format No:</td>
     <td style="line-height:20px;width: 420px;text-align:left;"> F/SOP/QC/013/02-01</td>
 </tr>
<tr>
     <td style="line-height:20px;width: 120px;text-align:left;"> Reference SOP No:</td>
     <td style="line-height:20px;width: 420px;text-align:left;"> SOP/QC/013/</td>
 </tr>
<tr>
     <td style="line-height:20px;width:540px;text-align:left;"></td>
 </tr>
 <tr>
     <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> ID No:</td>
     <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['plant_id'].'</td>
     <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Capacity:</td>
     <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['max_capacity'].'</td>
 </tr>
 <tr>
 <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Make:</td>
 <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['make'].' </td>
 <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Least Count:</td>
 <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['Least_Count'].'</td>
</tr>
<tr>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Model No:</td>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['model'].'</td>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Location:</td>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['location'].'</td>
</tr>
<tr>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Sr No:</td>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['equipment_sr_no'].'</td>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Weight Box ID No.:</td>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['weight_box_id'].'</td>
</tr>
<tr>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Date of Calibration:</td>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['calibration_date'].'</td>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Next Calibration due Date:</td>
<td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;">  '.$row['next_calibration_date'].' </td>
</tr>
 </table>
<div></div>
<div></div>

<table>
<tr>
<td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;">  1) Full Range Calibration</td>
</tr>
</table>
<table border="1">
<tr>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">Standard Weights</td>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Observed Weights </td>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Acceptance Criteria </td>
</tr>
<tr>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">
    ';
   foreach ($fullRangeCalibration as $innerRow) {
    $html .= $innerRow['std_wt'] . '<br> ';
   }
    $html.='
    </td>

    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">
  ';
   foreach ($fullRangeCalibration as $innerRow) {
      $html .= $innerRow['observe_weight'] . '<br> ';
   }
    $html.='
    </td>
    
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">
    ';
   foreach ($fullRangeCalibration as $innerRow) {
        $html .= $innerRow['range_from'] . ' - ' . $innerRow['range_to'] . '<br>';
   }
    $html.='
    </td>
    
</tr>
</table>
<div></div>


<table border="1">
<tr>
    <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Done By/on: </td>
    <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Checked By/on </td>
</tr>
</table>
<div></div>
<br pagebreak="true">
<table>
<tr>
<td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;">  2) Corner Load Test</td>
</tr>
</table>
<table border="1">
<tr>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Location </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Standard Wt.1.00g </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Std Deviation</td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Acceptance Criteria</td>
</tr>';
foreach ($Corner_Load_Test as $innerRow1) {
    foreach ($innerRow1 as $innerRow2) {
       
        $html .= '
        <tr>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> ' . $innerRow2['location'] . ' </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> ' . $innerRow2['standard_deviation'] . ' </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> ' . $innerRow2['standard_weight2'] . ' </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> ' . $innerRow2['standard_weight2'] . ' </td>
        </tr>';
    }
}

$html.='</table>
<div></div>

<table border="1">
<tr>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Location </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Standard Wt.200g </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Std Deviation</td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Acceptance Criteria</td>
</tr>
<tr>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
</tr>
</table>
<div></div>

<table border="1">
<tr>
    <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Done By/on: </td>
    <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Checked By/on </td>
</tr>
</table>
<div></div>
<br pagebreak="true">
<table>
<tr>
<td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;">  3) Repeatability Test</td>
</tr>
</table>
<table border="1">
<tr>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">Sr.No.</td>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Standard Weight </td>
    <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Displayed Weight </td>
</tr>
';
$i=1;
foreach ($Repetability_Test_DATA as $innerRow11) {
   
       
        $html .= '
        <tr>
            <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> ' .$i++ . ' </td>
            <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> ' . $innerRow11['standard_weight2'] . ' </td>
            <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> ' . $innerRow11['Displayed_weight'] . ' </td>
        </tr>';
  
}

$html.='</table>
</table>
<div></div>

<table border="1">
<tr>
    <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Done By/on: </td>
    <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Checked By/on </td>
</tr>
</table>
<div></div>
<div></div>
<div></div>




 <table border="1">
<tr>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> PREPARED BY </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> REVIEWED BY </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> APPROVED BY </td>
</tr>
<tr>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Name</td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
</tr>
<tr>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Sign/Date</td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
</tr>
<tr>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Designation</td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
</tr>
<tr>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Department</td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
</tr>
</table>


 ';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
 }
 
else if($_GET["type"] == "downloadmonthly") {
    
    
   
             require '../tcpdf/tcpdf.php';
        
        $_GET['filename'] = "Unit Formula Log"; $_GET['pdftype'] = "landscape"; include("../pdfimp2.php");
                
        $sql="SELECT * FROM monthly_calibration WHERE plant_id='".$_GET["plant_id"]."'AND id = '".$_GET["id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                
                while ($row = $result->fetch_assoc()) {
                
                //  var_dump($row);
                
                $fullRangeCalibrationJson = $row['Full_Range_calibration'];
                $fullRangeCalibration = json_decode($fullRangeCalibrationJson, true);
                
                
                $Corner_Load_TestJson = $row['Corner_Load_Test'];
                $Corner_Load_Test = json_decode($Corner_Load_TestJson, true);
                 
                $Repetability_Test= $row['Repetability_Test'];
                $Repetability_Test_DATA= json_decode($Repetability_Test, true);
                
                
                    $html.='

<table border="1">
    <tr>
         <td style="line-height:20px;width: 120px;border-bottom:none;text-align:center;"rowspan="2"> </td>
         <td style="line-height:30px;width: 270px;text-align:center;"rowspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;   NOVO EXCIPIENTS PVT.LTD.,NAVI MUMBAI   QUALITY CONTROL DEPARTMENT </td>
         <td style="line-height:30px;width: 90px;border-bottom:none;text-align:left;"> Issued By/ On:</td>
         <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;">  </td>
    </tr>
    <tr>
         <td style="line-height:20px;width: 90px;border-bottom:none;text-align:left;"> No. of Copies:</td>
         <td style="line-height:20px;width: 60px;border-bottom:none;text-align:center;"> </td>
    </tr>
    <tr>
         <td style="line-height:20px;width: 120px;border-bottom:none;text-align:left;"> Format Title:</td>
         <td style="line-height:20px;width: 420px;text-align:left;"> Monthly Calibration Of Weighing Balance</td>
    </tr>
    <tr>
         <td style="line-height:20px;width: 120px;border-bottom:none;text-align:left;"> Format No:</td>
         <td style="line-height:20px;width: 420px;text-align:left;"> F/SOP/QC/013/02-01</td>
    </tr>
    <tr>
         <td style="line-height:20px;width: 120px;text-align:left;"> Reference SOP No:</td>
         <td style="line-height:20px;width: 420px;text-align:left;"> SOP/QC/013/</td>
    </tr>
    <tr>
         <td style="line-height:20px;width:540px;text-align:left;"></td>
    </tr>
     <tr>
         <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> ID No:</td>
         <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['plant_id'].'</td>
         <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Capacity:</td>
         <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['max_capacity'].'</td>
    </tr>
    <tr>
         <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Make:</td>
         <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['make'].' </td>
         <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Least Count:</td>
         <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['Least_Count'].'</td>
    </tr>
    <tr>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Model No:</td>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['model'].'</td>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Location:</td>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['location'].'</td>
    </tr>
    <tr>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Sr No:</td>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['equipment_sr_no'].'</td>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Weight Box ID No.:</td>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['weight_box_id'].'</td>
    </tr>
    <tr>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Date of Calibration:</td>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> '.$row['calibration_date'].'</td>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;"> Next Calibration due Date:</td>
        <td style="line-height:20px;width: 135px;border-bottom:none;text-align:left;">  '.$row['next_calibration_date'].' </td>
    </tr>
</table>

<div></div>
<div></div>

<table>
    <tr>
        <td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;">  1) Full Range Calibration</td>
    </tr>
</table>

<table border="1">
    <tr>
        <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">Standard Weights</td>
        <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Observed Weights </td>
        <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Acceptance Criteria </td>
    </tr>
    <tr>
        <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">';
        
               foreach ($fullRangeCalibration as $innerRow) {
                $html .= $innerRow['std_wt'] . '<br> ';
               }
    $html.='
        </td>
        <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> ';
        
               foreach ($fullRangeCalibration as $innerRow) {
                  $html .= $innerRow['observe_weight'] . '<br> ';
               }
    $html.='
        </td>
        <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">';
    
           foreach ($fullRangeCalibration as $innerRow) {
                $html .= $innerRow['range_from'] . ' - ' . $innerRow['range_to'] . '<br>';
           }
    $html.='
        </td>
    </tr>
</table>

<div></div>
 
<table border="1">
    <tr>
        <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Done By/on: </td>
        <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Checked By/on </td>
    </tr>
</table>

<div></div>

<br pagebreak="true">

<table>
    <tr>
        <td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;">  2) Corner Load Test</td>
    </tr>
</table>

<table border="1">
    <tr>
        <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Location </td>
        <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Standard Wt.1.00g </td>
        <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Std Deviation</td>
        <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Acceptance Criteria</td>
    </tr>';
    
    
    foreach ($Corner_Load_Test as $innerRow1) {
        foreach ($innerRow1 as $innerRow2) {
           
            $html .= '
            <tr>
                <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> ' . $innerRow2['location'] . ' </td>
                <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> ' . $innerRow2['standard_deviation'] . ' </td>
                <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> ' . $innerRow2['standard_weight2'] . ' </td>
                <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> ' . $innerRow2['standard_weight2'] . ' </td>
            </tr>';
        }
    }

$html.='
    </table>
    
    <div></div>

    <table border="1">
        <tr>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Location </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Standard Wt.200g </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Std Deviation</td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Acceptance Criteria</td>
        </tr>
        <tr>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
        </tr>
    </table>
    
<div></div>

    <table border="1">
        <tr>
            <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Done By/on: </td>
            <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Checked By/on </td>
        </tr>
    </table>
    
<div></div>

<br pagebreak="true">

    <table>
        <tr>
            <td style="line-height:30px;width: 540px;border-bottom:none;text-align:left;">  3) Repeatability Test</td>
        </tr>
    </table>
    
<table border="1">
    <tr>
        <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;">Sr.No.</td>
        <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Standard Weight </td>
        <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> Displayed Weight </td>
    </tr>
';

    $i=1;
    foreach ($Repetability_Test_DATA as $innerRow11) {
       
           
            $html .= '
            <tr>
                <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> ' .$i++ . ' </td>
                <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> ' . $innerRow11['standard_weight2'] . ' </td>
                <td style="line-height:30px;width: 180px;border-bottom:none;text-align:center;"> ' . $innerRow11['Displayed_weight'] . ' </td>
            </tr>';
      
    }

$html.=' 
</table>

    <div></div>
    
    <table border="1">
        <tr>
            <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Done By/on: </td>
            <td style="line-height:30px;width: 270px;border-bottom:none;text-align:center;"> Checked By/on </td>
        </tr>
    </table>
    
<div></div>
<div></div>
 
 <table border="1">
        <tr>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> PREPARED BY </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> REVIEWED BY </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> APPROVED BY </td>
        </tr>
        <tr>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Name</td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
        </tr>
        <tr>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Sign/Date</td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
        </tr>
        <tr>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Designation</td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
        </tr>
        <tr>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> Department</td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
            <td style="line-height:30px;width: 135px;border-bottom:none;text-align:center;"> </td>
        </tr>
</table>


 ';

 
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
    }
            }
}

else if($_GET["type"] == "downloaddaily") {
    
        require '../tcpdf/tcpdf.php';
        
        $_GET['filename'] = "Unit Formula Log"; $_GET['pdftype'] = "landscape"; include("../pdfimp2.php");
        $sql22 = "SELECT * FROM equipment_standard_weight a LEFT JOIN equipment b on b.id=a.equipment_id WHERE b.id='".$_GET["id"]."'"; 
            $result22 = $conn->query($sql22);
            $result22->num_rows > 0;
               $row22 = $result22->fetch_assoc();
             $rows = $result22->fetch_all(MYSQLI_ASSOC);

    // Get the total number of rows
    $numRows = count($rows)+1;
   
      
                    $html.='
                    

<table border="1">
 

<tr>
     <td style="line-height:24px;width: 185px;border-bottom:none;text-align:left;"> Format Title:</td>
     <td style="line-height:24px;width: 600px;text-align:left;"> Daily Verification Of Weighing Balance</td>
 </tr>
 
<tr>
     <td style="line-height:24px;width: 185px;border-bottom:none;text-align:left;"> Format No:</td>
     <td style="line-height:24px;width: 600px;text-align:left;"> F/SOP/QC/013/01-01</td>
 </tr>
<tr>
     <td style="line-height:24px;width: 185px;text-align:left;"> Reference SOP No:</td>
     <td style="line-height:24px;width: 600px;text-align:left;"> SOP/QC/013/<b>' . $row22['sop_no'] . '</b></td>
 </tr>
<tr>
     <td style="line-height:24px;width:785px;text-align:left;"></td>
 </tr>
<tr>
     <td style="line-height:24px;width: 185px;border-bottom:none;text-align:left;"> ID No:<b>' . $row22['id'] . '</b></td>
     
     
     
     <td style="line-height:24px;width: 200px;border-bottom:none;text-align:left;"> Capacity:<b>' . $row22['capacity'] . '</b></td>
     
     
     
     <td style="line-height:24px;width: 200px;border-bottom:none;text-align:left;"> Sr.No.:<b>' . $row22['equipment_sr_no'] . '</b></td>
     
     
     <td style="line-height:24px;width: 200px;border-bottom:none;text-align:left;"> Least Count:' . $row22[' '] . '</td>
     
     
     
     
 </tr>
<tr>
    <td style="line-height:24px;width: 185px;border-bottom:none;text-align:left;"> Make:<b>' . $row22['make'] . '</b></td>
    
    
    
    <td style="line-height:24px;width: 200px;border-bottom:none;text-align:left;"> Model No.<b>' . $row22[' '] . '</b></td>
    
    
    
    <td style="line-height:24px;width: 200px;border-bottom:none;text-align:left;"> Location:<b>' . $row22['location'] . '</b></td>
    
    
    <td style="line-height:24px;width: 200px;border-bottom:none;text-align:left;"> Weight Box ID No.:<b>' . $row22[' '] . '</b></td>
 </tr>
</table>
<div></div>

<table border="1">

<tr>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"rowspan="4"> Date</td>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"rowspan="2"> Spirit Level</td>
    <td style="line-height:27px;width: 385px;border-bottom:none;text-align:center;"colspan="4"> Standard Weight</td>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"rowspan="4"> Remark C/NC</td>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"rowspan="4"> Done by/on </td>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"rowspan="4"> Checked by/on</td>
</tr> 



 
<tr>
   ';
    
        $sql = "SELECT * FROM equipment_standard_weight a LEFT JOIN equipment b on b.id=a.equipment_id WHERE b.id='".$_GET["id"]."'"; 
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
    
        $html .= '<td style="line-height:27px;width: 96px;border-bottom:none;text-align:center;">' . $row['standard_weight'] . '</td>';
 
                }
            }

            
$html.='</tr> 
<tr>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"> Acceptance <br>Criteria</td>';
    
        $sql = "SELECT * FROM equipment_standard_weight a LEFT JOIN equipment b on b.id=a.equipment_id WHERE b.id='".$_GET["id"]."'"; 
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
    
        $html .= '<td style="line-height:27px;width: 96px;border-bottom:none;text-align:center;">' . $row['acceptance_criteria_to'] . '-' . $row['acceptance_criteria_from'] . '</td>';
 
                }
            }
            
$html.='</tr> 
<tr>
   
   
</tr> ';


	$sql1 = "SELECT * FROM daily_caibration where equipment_id='".$_GET["equipment_code"]."'";
 
            $result1 = $conn->query($sql1);
if ($result1->num_rows > 0) {
    while ($row1 = $result1->fetch_assoc()) {
           // Decode actual_qt1 if it's stored as JSON
        $actual_qt1 = json_decode($row1['actual_qt1'], true);

     
        
        
        
        $html .= '<tr>';
        $html .= '<td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"> ' . $row1['date'] . '</td>';
        $html .= '<td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"> ' . $row1['spirit'] . '</td>';
        
        // Loop through actual_qt1 array and print values horizontally
        foreach ($actual_qt1 as $value) {
            $html .= '<td style="line-height:27px;width: 96px;border-bottom:none;text-align:center;"> ' . $value . '</td>';
        }
        
        
        $html .= '<td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;">' . $row1['remark'] . ' </td>';
        $html .= '<td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"> ' . $row1['location'] . '</td>';
        $html .= '</tr>';
    }
} 

$html.='
</table>
<div></div>


<table border="1">
<tr>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> PREPARED BY </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> REVIEWED BY </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> APPROVED BY </td>
</tr>
<tr>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> Name</td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
</tr>
<tr>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> Sign/Date</td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
</tr>
<tr>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> Designation</td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
</tr>
<tr>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> Department</td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
    <td style="line-height:27px;width: 196px;border-bottom:none;text-align:center;"> </td>
</tr>
</table>
';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
 }


else if($_GET["type"] == "downloadpdf") {
    
        require '../tcpdf/tcpdf.php';
        
        $_GET['filename'] = "Unit Formula Log"; $_GET['pdftype'] = "landscape"; include("../pdfimp2.php");
                    $html.='
<div></div>

<table border="1">

<tr>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"rowspan="4"> Sr no.</td>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"rowspan="2"> Specification</td>
    <td style="line-height:27px;width: 385px;border-bottom:none;text-align:center;"colspan="4"> Acceptance Criteria</td>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"rowspan="4"> Observation</td>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"rowspan="4"> Test Pass/Fail </td>
    <td style="line-height:27px;width: 80px;border-bottom:none;text-align:center;"rowspan="4"> Date</td>
</tr> 
<tr>
<td></td><td></td>
<td></td>

<td></td>

<td></td>

<td></td>

</tr>
   ';
     $html.='</table>';
      
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
 }
 
else if($_GET["type"]=="del_data"){
	$output = Array();

     	  $sql = "DELETE FROM full_range_calibration_weight  where id='".$_GET["id"]."' ";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);
 }





}

 else {
    echo "{\"status\":\"invalid\"}";
}


$conn->close();
?>