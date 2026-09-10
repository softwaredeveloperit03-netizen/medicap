<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];

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
    

     if ($_GET["type"] == "saveMasterMedia") {
          $input = $_POST;  
  $data = json_decode($input["data"], true);
         $sql1 = "SELECT MAX(id) as id FROM glassware ";
          $result1 = $conn->query($sql1);
         $row1 = $result1->fetch_assoc();
         $last_id=$row1["id"]; 
         $target_dir = "../../../upload/product/";
           if(isset($_FILES["coa"]["name"])) {
            	$target_file = $target_dir.$last_id."_".basename($_FILES["coa"]["name"]);
            	$structure_file =$last_id."_".basename($_FILES["coa"]["name"]); 
        	    move_uploaded_file($_FILES["coa"]["tmp_name"], $target_file);
        	   
           }
            if($_GET["plant_id"]==59){
        $sql = "INSERT INTO media (coa,plant_id,media_name, media_code, media_specification, sterilization, entry_date) 
        VALUES ( '".$structure_file."','".$_GET["plant_id"]."','".$input["media_name"]."', '".$input["media_code"]."', '".$input["media_specification"]."', 
        '".$input["sterilization"]."', '$entry_date')";
            }
            else  if($_GET["plant_id"]!=59){
        $sql = "INSERT INTO media (media_name, media_code, media_specification, sterilization, entry_date) 
        VALUES ( '".$input["media_name"]."', '".$input["media_code"]."', '".$input["media_specification"]."', 
        '".$input["sterilization"]."', '$entry_date')"; 
            }
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
   }
   else if ($_GET["type"] == "getMedia") {
             
        $output = array();
     
        $sql = "SELECT * FROM others_material where material_subtype = 'Media'  AND plant_id = '".$_GET["plant_id"]."'";
        
        
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row; 
            }
        }
        echo json_encode($output);
    
   }
   else if ($_GET["type"] == "getsection") {
             
        $output = array();
        $sql = "SELECT * FROM section";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row; 
            }
        }
        echo json_encode($output);
    
   } else if ($_GET["type"] == "getbatches") {
             
        $output = array();
        $sql = "SELECT * FROM media_stock";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row; 
            }
        }
        echo json_encode($output);
    
   }else if ($_GET["type"] == "updateMedia") {
        $sql = "UPDATE media SET ph='".$input["media_specification"]."', sterilization='".$input["sterilization"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   }else if($_GET["type"] == "getSamplingPlans") {
        $output = Array();
        $sql = "SELECT DISTINCT(sampling_plan) as sampling_plan FROM specification";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
               $output[] = $row;  
            }
        }
       
        echo json_encode($output);
        
    } else if($_GET["type"] == "getSampleDrawnFroms") {
        $output = Array();
        $sql = "SELECT DISTINCT(sample_drawn_from) as sample_drawn_from FROM specification";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
              $output[] = $row; 
            }
        }
         
        echo json_encode($output);
    } else if($_GET["type"] == "getSampleDrawnWiths") {
        $output = Array();
        $sql = "SELECT DISTINCT(sample_drawn_with) as sample_drawn_with FROM specification";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"] == "getPrecautions") {
        $output = Array();
        $sql = "SELECT DISTINCT(precautions) as precautions FROM specification";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

     else if ($_GET["type"] == "downloadMedia") {
        $_GET['filename'] = 'Media'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
 
        $html.='
         <h3 style="text-align:center">Media</h3>
        <table border="1" cellpadding="5">
        
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;text-align:center">Sr</td>
                    <td style="width:30%;text-align:center">Media Name</td>
                    <td style="width:30%;text-align:center">Media Code</td>
                    <td style="width:30%;text-align:center">Media Specification</td>
                 </tr>
            </thead>';
             
              
               $sql = "SELECT * FROM media";
            // $sql="SELECT s.*, m.media_name,m.media_code,m.media_specification FROM media_stock s LEFT JOIN media m ON s.media_code=m.media_code"; //order by 1 desc ";
       
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
            
        
             $html.='<tr>
                        <td style="width:10%;">'.$i.'</td>
                        <td style="width: 30%;">'.$row['media_name'].'</td>
                        <td style="width: 30%;">'.$row['media_code'].'</td>
                       <td style="width: 30%;">'.$row['media_specification'].'</td>
                    </tr>';
                 $i++;
            
            }
        }
        
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MediaStock.pdf', 'I');
            
            
    } else if ($_GET["type"] == "downloadMediaParameter") {
        $_GET['filename'] = 'Media Parameter'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Sr</td>
                    <td style="width:20%;">	Media Name</td>
                    <td style="width:20%;">	Media Code</td>
                    <td style="width:20%;">	Ph</td>
                    <td style="width:20%;">	Sterilzation</td>
                 </tr>
            </thead>';
             $i=1;
        $sql="SELECT s.*, m.media_name,m.ph,m.sterilization FROM media_stock s LEFT JOIN media m ON s.media_code=m.media_code order by 1 desc ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width:20%;">'.$i.'</td>
                        <td style="width: 20%;">'.$row['media_name'].'</td>
                        <td style="width: 20%;">'.$row['media_code'].'</td>
                        <td style="width: 20%;">'.$row['ph'].'</td>
                        <td style="width: 20%;">'.$row['sterilization'].'</td>
                    </tr>';
                 $i++;
            }
        }
        
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MediaStock.pdf', 'I');
 
  } else if ($_GET["type"] == "downloadMediaParameter_old") {
        $_GET['filename'] = 'Media Parameter'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:20%;">Sr</td>
                    <td style="width:20%;">	Media Name</td>
                    <td style="width:20%;">	Media Code</td>
                    <td style="width:20%;">	Ph</td>
                    <td style="width:20%;">	Sterilzation</td>
                 </tr>
            </thead>';
             $i=1;
        $sql="SELECT s.*, m.media_name,m.ph,m.sterilization FROM media_stock s LEFT JOIN media m ON s.media_code=m.media_code order by 1 desc ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width:20%;">'.$i.'</td>
                        <td style="width: 20%;">'.$row['media_name'].'</td>
                        <td style="width: 20%;">'.$row['media_code'].'</td>
                        <td style="width: 20%;">'.$row['ph'].'</td>
                        <td style="width: 20%;">'.$row['sterilization'].'</td>
                    </tr>';
                 $i++;
            }
        }
        
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MediaStock.pdf', 'I');
    }
 
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>