<?php
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

    if($_GET["type"] == "savepqChecklist"){
       $sql="INSERT INTO  pq_checklist (perticular,checkpoint ,remark)VALUES('".$input["perticular"]."','".$input["checkpoint"]."', '".$input["remark"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
    if($_GET["type"] == "deleteData"){
        $table=$_GET['Table'];
        $sql="delete from $table where id='".$_GET['id']."'";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
    else if($_GET["type"]=="getpqChecklist"){
        $output = Array();
        $sql = "SELECT * FROM pq_checklist";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM aircompressor_operation WHERE id < ".$row["id"]." ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["AC02_diff"] = round(+$row["AC02"] - +$row1["AC02"], 2);
                            $row["AC05_diff"] = round(+$row["AC05"] - +$row1["AC05"], 2);
                            $row["AC06_diff"] = round(+$row["AC06"] - +$row1["AC06"], 2);
                        }
                    } else {
                        $row["AC02_diff"] = 0.00;
                        $row["AC05_diff"] = 0.00;
                        $row["AC06_diff"] = 0.00;
                    }
                    
                    if ($row["firstname"] == '') {
                        $row["firstname"] = $row["operator"];
                    }
                    
                    $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    
    
    if($_GET["type"] == "saveperformance_calibration"){
       $sql="INSERT INTO  performance_calibration (test,frequency)VALUES('".$input["test"]."','".$input["frequency"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
 
    else if($_GET["type"]=="getperformance_calibration"){
        $output = Array();
        $sql = "SELECT * FROM performance_calibration";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM aircompressor_operation WHERE id < ".$row["id"]." ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["AC02_diff"] = round(+$row["AC02"] - +$row1["AC02"], 2);
                            $row["AC05_diff"] = round(+$row["AC05"] - +$row1["AC05"], 2);
                            $row["AC06_diff"] = round(+$row["AC06"] - +$row1["AC06"], 2);
                        }
                    } else {
                        $row["AC02_diff"] = 0.00;
                        $row["AC05_diff"] = 0.00;
                        $row["AC06_diff"] = 0.00;
                    }
                    
                    if ($row["firstname"] == '') {
                        $row["firstname"] = $row["operator"];
                    }
                    
                    $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    
    
    if($_GET["type"] == "saveEnvironment"){
       $sql="INSERT INTO  Environment_checks (area,date,hr,temp)VALUES('".$input["area"]."','".$input["date"]."','".$input["hr"]."','".$input["temp"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
 
    else if($_GET["type"]=="getEnvironment_checks"){
        $output = Array();
        $sql = "SELECT * FROM Environment_checks";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM aircompressor_operation WHERE id < ".$row["id"]." ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["AC02_diff"] = round(+$row["AC02"] - +$row1["AC02"], 2);
                            $row["AC05_diff"] = round(+$row["AC05"] - +$row1["AC05"], 2);
                            $row["AC06_diff"] = round(+$row["AC06"] - +$row1["AC06"], 2);
                        }
                    } else {
                        $row["AC02_diff"] = 0.00;
                        $row["AC05_diff"] = 0.00;
                        $row["AC06_diff"] = 0.00;
                    }
                    
                    if ($row["firstname"] == '') {
                        $row["firstname"] = $row["operator"];
                    }
                    
                    $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    if($_GET["type"] == "savevariable_to_met"){
       $sql="INSERT INTO  variable_to_met ( `variable`, `criteria`, `observation`)VALUES('".$input["variable"]."','".$input["criteria"]."','".$input["observation"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
 
    else if($_GET["type"]=="getvariable_to_met"){
        $output = Array();
        $sql = "SELECT * FROM variable_to_met";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM aircompressor_operation WHERE id < ".$row["id"]." ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["AC02_diff"] = round(+$row["AC02"] - +$row1["AC02"], 2);
                            $row["AC05_diff"] = round(+$row["AC05"] - +$row1["AC05"], 2);
                            $row["AC06_diff"] = round(+$row["AC06"] - +$row1["AC06"], 2);
                        }
                    } else {
                        $row["AC02_diff"] = 0.00;
                        $row["AC05_diff"] = 0.00;
                        $row["AC06_diff"] = 0.00;
                    }
                    
                    if ($row["firstname"] == '') {
                        $row["firstname"] = $row["operator"];
                    }
                    
                    $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    if($_GET["type"] == "saveMaterialList"){
       $sql="INSERT INTO  materialList  ( `name_material`, `quantity`, `unit`)VALUES('".$input["name_material"]."','".$input["quantity"]."','".$input["unit"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
 
    else if($_GET["type"]=="getMaterialList"){
        $output = Array();
        $sql = "SELECT * FROM materialList";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM aircompressor_operation WHERE id < ".$row["id"]." ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["AC02_diff"] = round(+$row["AC02"] - +$row1["AC02"], 2);
                            $row["AC05_diff"] = round(+$row["AC05"] - +$row1["AC05"], 2);
                            $row["AC06_diff"] = round(+$row["AC06"] - +$row1["AC06"], 2);
                        }
                    } else {
                        $row["AC02_diff"] = 0.00;
                        $row["AC05_diff"] = 0.00;
                        $row["AC06_diff"] = 0.00;
                    }
                    
                    if ($row["firstname"] == '') {
                        $row["firstname"] = $row["operator"];
                    }
                    
                    $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    
    
    if($_GET["type"] == "saveEngineeringProcedure"){
       $sql="INSERT INTO  EngineeringProcedure  ( `procedure`)VALUES('".$input["procedure"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
 
    else if($_GET["type"]=="getEngineeringProcedure"){
        $output = Array();
        $sql = "SELECT * FROM EngineeringProcedure";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM aircompressor_operation WHERE id < ".$row["id"]." ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["AC02_diff"] = round(+$row["AC02"] - +$row1["AC02"], 2);
                            $row["AC05_diff"] = round(+$row["AC05"] - +$row1["AC05"], 2);
                            $row["AC06_diff"] = round(+$row["AC06"] - +$row1["AC06"], 2);
                        }
                    } else {
                        $row["AC02_diff"] = 0.00;
                        $row["AC05_diff"] = 0.00;
                        $row["AC06_diff"] = 0.00;
                    }
                    
                    if ($row["firstname"] == '') {
                        $row["firstname"] = $row["operator"];
                    }
                    
                    $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    
    if($_GET["type"] == "savePerformanceReport"){
       $sql="INSERT INTO `PerformanceReport`( `text`, `observation`, `check`, `verified`, `report`) VALUES('".$input["text"]."','".$input["observation"]."','".$input["check"]."','".$input["verified"]."','".$input["report"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
 
    else if($_GET["type"]=="getPerformanceReport"){
        $output = Array();
        $sql = "SELECT * FROM PerformanceReport";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM aircompressor_operation WHERE id < ".$row["id"]." ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["AC02_diff"] = round(+$row["AC02"] - +$row1["AC02"], 2);
                            $row["AC05_diff"] = round(+$row["AC05"] - +$row1["AC05"], 2);
                            $row["AC06_diff"] = round(+$row["AC06"] - +$row1["AC06"], 2);
                        }
                    } else {
                        $row["AC02_diff"] = 0.00;
                        $row["AC05_diff"] = 0.00;
                        $row["AC06_diff"] = 0.00;
                    }
                    
                    if ($row["firstname"] == '') {
                        $row["firstname"] = $row["operator"];
                    }
                    
                    $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    
    if($_GET["type"] == "saveTrailNoLoad"){
       $sql="INSERT INTO `TrailNoLoad`( `time`, `temp`, `done_by`, `check_by`)  VALUES('".$input["time"]."','".$input["temp"]."','".$input["done_by"]."','".$input["check_by"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
 
    else if($_GET["type"]=="getTrailNoLoad"){
        $output = Array();
        $sql = "SELECT * FROM TrailNoLoad";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                     $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    
    if($_GET["type"] == "saveUtilityFunctions"){
       $sql="INSERT INTO `UtilityFunctions`(`utility`, `fun_ass`, `actual_obs`, `remark`)  VALUES('".$input["utility"]."','".$input["fun_ass"]."','".$input["actual_obs"]."','".$input["remark"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
 
    else if($_GET["type"]=="getUtilityFunctions"){
        $output = Array();
        $sql = "SELECT * FROM UtilityFunctions";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                     $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    
    if($_GET["type"] == "saveDevList"){
       $sql="INSERT INTO `DevList`( `deviation_no`, `Deviation_title`, `Status`, `Comments`, `Reviewed_by`, `date`)  VALUES('".$input["deviation_no"]."','".$input["Deviation_title"]."','".$input["Status"]."','".$input["Comments"]."','".$input["Reviewed_by"]."','".$input["date"]."')";
       if($conn->query($sql)){
           echo "{ \"status\":\"success\" }";
       }else{
          echo "{ \"status\":\"failed\" }";
       }
    }
 
    else if($_GET["type"]=="getDevList"){
        $output = Array();
        $sql = "SELECT * FROM DevList";
        $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                     $output[] = $row;
                }
            }
         echo json_encode($output);
    } 
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    else if($_GET["type"]=="DownloadTrailNoLoad"){
        
        
        $_GET['filename'] = 'Trials-No Load Condition'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html.='
        <h2 style="text-align:center">Trials-No Load Condition</h2>
        <table cellpadding="3">
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td >Sr.</td>
                    <td >Time.</td>
                    <td >Temprature.</td>
                    <td >Done By.</td>
                    <td >Checked By.</td>
                   
                </tr>';
             $count=1;
               $sql = "Select * from TrailNoLoad";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
  $html.='
                    <tr nobr="true">
                        <td >'.$count++.'</td>
                        <td  >'.$row["time"].'</td>
                        <td  >'.$row["temp"].'</td>
                        <td  >'.$row["done_by"].'</td>
                        <td  >'.$row["check_by"].'</td>
                      
                    </tr>';
                      $count++;
            }
        }
                  
      $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
            
        
        
        
    
    } 
    else if($_GET["type"]=="DownloadUtilityFunctions"){
        
        
        $_GET['filename'] = 'Utility Functions'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html.='
        <h2 style="text-align:center">Utility Functions</h2>
        <table cellpadding="3">
                <tr>
                            <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Sr.</th>
                            <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Utility
                                Supplied</th>
                            <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Function
                                Assigned</th>
                            <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Actual
                                Observation</th>
                            <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Remark</th>
                            
                        </tr>';
             $count=1;
               $sql = "Select * from TrailNoLoad";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
  $html.='
                    <tr nobr="true">
                        <td >'.$count++.'</td> 
                            <td  >'.$row["utility"].'</td>
                            <td  >'.$row["fun_ass"].'</td>
                            <td  >'.$row["actual_obs"].'</td>
                            <td  >'.$row["remark"].'</td>
                      
                    </tr>';
                      $count++;
            }
        }
                  
      $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
            
        
        
        
    
    } 
    else if($_GET["type"]=="DownloadDevList"){
        
        
        $_GET['filename'] = 'Deviation OQ'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html.='
        <h2 style="text-align:center">Deviation OQ</h2>
        <table cellpadding="3">
                <tr>
                              <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Sr.</th>
                        <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Deviation No.
                        </th>
                        <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Deviation Title
                        </th>
                        <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Status
                        </th>
                        <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Comments
                        </th>
                        <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Reviewed By
                        </th>
                        <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Date</th>
                             
                        </tr>';
             $count=1;
               $sql = "Select * from DevList";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
  $html.='
                    <tr nobr="true">
                        <td >'.$count++.'</td> 
                            <td  >'.$row["deviation_no"].'</td>
                            <td  >'.$row["Deviation_title"].'</td>
                            <td  >'.$row["Status"].'</td>
                            <td  >'.$row["Comments"].'</td>
                            <td  >'.$row["Reviewed_by"].'</td>
                            <td  >'.$row["date"].'</td>
                      
                    </tr>';
                      $count++;
            }
        }
                  
      $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
            
        
        
        
    
    } 
    else if($_GET["type"]=="Downloadpq_checklist"){
        
        
        $_GET['filename'] = 'Deviation OQ'; $_GET['pdftype'] = 'onlyheader';  include('../pdfimp2.php');
        $html.='
        <h2 style="text-align:center">Deviation OQ</h2>
        <table cellpadding="3">
                <tr>
                           <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Sr.</th>
                    <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Particular</th>
                    <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Checkpoint</th>
                    <th style="text-align: left; background-color: rgb(14, 67, 112); color: white;">Remark</th>
                             
                        </tr>';
             $count=1;
               $sql = "Select * from pq_checklist";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
  $html.='
                    <tr nobr="true">
                        <td >'.$count++.'</td> 
                            <td  >'.$row["perticular"].'</td>
                            <td  >'.$row["checkpoint"].'</td>
                            <td  >'.$row["remark"].'</td> 
                      
                    </tr>';
                      $count++;
            }
        }
                  
      $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
            
        
        
        
    
    } 
    
    
}

$conn->close();
?>