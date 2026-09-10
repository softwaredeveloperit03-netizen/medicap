<?php


// error_reporting(E_ALL);
// ini_set('display_errors', 1);

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

    $timestamp = time();
    $entry_date = date("Y-m-d", $timestamp);

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
     if ($_GET["type"] == "saveMasterMedia") {
        $sql = "INSERT INTO master_media (id,media_name,media_code,media_description,hsn_code,gst_per,entered_by,entry_date) 
        VALUES('".$input["media_name"]."','".$input["media_code"]."','".$input["media_description"]."','".$input["hsn_code"]."',
        '".$input["gst_per"]."','".$input["entered_by"]."','".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "saveMediaStock") {
         $input = $_POST;  
//   $data = json_decode($input["data"], true);
         $sql1 = "SELECT MAX(id) as id FROM glassware ";
          $result1 = $conn->query($sql1);
         $row1 = $result1->fetch_assoc();
         $last_id=$row1["id"]; 
         $target_dir = "../../../upload/product/";
           if(isset($_FILES["signture"]["name"])) {
            	$target_file = $target_dir.$last_id."_".basename($_FILES["signture"]["name"]);
            	$structure_file =$last_id."_".basename($_FILES["signture"]["name"]);
        	    move_uploaded_file($_FILES["signture"]["tmp_name"], $target_file);
           }
    
              
         $sql ="INSERT INTO media_stock ( signture,plant_id, media_code, vendor_name, batch_no,qty_received,bottle_qty,before_date,
        expiry_date,receive_by,qty_issue,balance_qty,make,unit,media_stock,rec_date,consume_qua,balance_qua,entry_by ,specif_weight,specifVol) 
        VALUES ( '".$structure_file."','".$_GET["plant_id"]."','".$input["media_code"]."','".$input["vendor_no"]."' ,
        '".$input["batch_no"]."','".$input["qty_received"]."','".$input["bottle_qty"]."','".$input["before_date"]."',
        '".$input["expiry_date"]."','".$_GET["emp_id"]."','".$input["qty_issue"]."','".$input["balance_qty"]."','".$input["make"]."',
        '".$input["unit"]."','".$input["media_stock"]."','$entry_date','".$input["consume_qua"]."','".$input["balance_qua"]."',
        '".$_GET["emp_id"]."','".$input["specif_weight"]."' ,'".$input["specifVol"]."' )";
          
        
          if ($conn->query($sql)) {
              
                            
             $sql1 = "UPDATE challan_materials SET grn='approve'  WHERE id='".$_GET["id"]."'";
           $conn->query($sql1);
              
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
    
    }
    else if($_GET["type"] == "getDehydratedMediaStock"){
        $output=Array();
            $sql="SELECT DISTINCT  s.*, m.material_name FROM media_stock s LEFT JOIN others_material m ON s.media_code=m.material_code   ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        
      echo json_encode($output);
        }
    } else if ($_GET["type"] == "getMediaStock") {
       
        $output=Array();
        
        $sql="SELECT s.*, m.material_name as media_name FROM media_stock s LEFT JOIN others_material m 
        ON s.media_code=m.material_code where s.plant_id = '".$_GET["plant_id"] ."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output = array();
             $output[] = $row;
            }
         }
        
        echo json_encode($output);
        
    } 
    
    else if ($_GET["type"] == "saveMediaPrepartion") {
        $sql = "INSERT INTO media_prepartion (plant_id,mediaFor,natureOfMedia,media_code, batch_no, lot_no, qty_taken, volume_prepared, containers,qty_per_containers,volume_unit,ph_value,entry_by,entry_date)
        VALUES('".$_GET["plant_id"]."','".$input["mediaFor"]."','".$input["natureOfMedia"]."','".$input["media_code"]."','".$input["batch_no"]."', '".$input["lot_no"]."','".$input["qty_taken"]."',
        '".$input["volume_prepared"]."',  '".$input["containers"]."',  '".$input["qty_per_containers"]."', '".$input["volume_unit"]."', '".$input["ph"]."', '".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if($_GET["type"] == "getApprovedVendor") {
        $output=Array();
         $sql="SELECT id,vendor_type,vendor_no,vendor_name,plant_id FROM vendor  WHERE status = 'Approved' AND vendor_type = '".$_GET["vendor_type"]."' 
        AND  plant_id = '".$_GET["plant_id"]."' ";
        $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
             $output[] = $row;
            }
         }
        
        echo json_encode($output);
    }
    else if($_GET["type"] == "getMediaWithBatchNo") { 
                $output = array();
     
         $sql = "SELECT * FROM  my_view  where material_subtype = 'Media'  AND plant_id = '".$_GET["plant_id"]."'";
         
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 
            $output1 = array();
            $sql1 = "SELECT * FROM  media_stock  where   media_code = '".$row["material_code"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1; 
                }
            }
                     
               $row['batches']  = $output1;
                 
                $output[] = $row; 
            }
        }
        echo json_encode($output);
    }
        
  
        
        
    else if($_GET["type"] == "getMediaPrepartion") {
        $output=Array();
        $sql="SELECT DISTINCT p.*, DATE(p.entry_date) as entry_date, m.material_name  FROM media_prepartion p LEFT JOIN my_view m ON 
        p.media_code=m.material_code  WHERE  m.plant_id = '".$_GET["plant_id"]."' 
        order by 1 desc ";
        $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
             $output[] = $row;
            }
         }
        
        echo json_encode($output);
        }
    else if($_GET["type"] == "getMediaPrepartionForSterActivity") {
        $output=Array();
        $sql="SELECT DISTINCT p.*, DATE(p.entry_date) as entry_date, m.material_name  FROM media_prepartion p LEFT JOIN my_view m ON 
        p.media_code=m.material_code  WHERE  m.plant_id = '".$_GET["plant_id"]."'  AND p.status = 'Pending'
        order by 1 desc ";
        $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
             $output[] = $row;
            }
         }
        
        echo json_encode($output);
        }
        
        
     else if ($_GET["type"] == "saveSterStartActivity") {
        
         $sql = "UPDATE  media_prepartion  SET  sterButtonAction = 'END' , tempToSet =  '".$input["tempToSet"]."' , steam_pressure =  '".$input["steam_pressure"]."'
        , ph_medium_before =  '".$input["ph_medium_before"]."', indicator =  '".$input["indicator"]."', hold_time =  '".$input["hold_time"]."', cycle_no =  '".$input["cycle_no"]."'
        , autoclave_id =  '".$input["autoclave_id"]."', ASstartTime =  '".$input["ASstartTime"]."' where id  =  '".$_GET["id"]."'";
        
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
     else if ($_GET["type"] == "saveSterEndActivity") {
        
       echo  $sql1 = "UPDATE  media_prepartion  SET  sterButtonAction = 'Complete' , achieved_time =  '".$input["achieved_time"]."' , 
         ph_medium_after =  '".$input["ph_medium_after"]."', unloadTime =  '".$input["unloadTime"]."',
         end_hold_time =  '".$input["end_hold_time"]."', ASendTime =  '".$input["ASendTime"]."', where id  =  '".$_GET["id"]."'";
        
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    
    
    
    
     else if ($_GET["type"] == "saveMediaConsumption") {
        // echo json_encode($input);
        $sql = "INSERT INTO media_consumption (plant_id,stock_id,media_code,batch_no,lot_no,prepare_on,use_before,used_on,quantity,used_for,remaining_qty,used_by,
        entry_by,entry_date) VALUES( '".$_GET["plant_id"]."','".$input["id"]."','".$input["media_code"]."','".$input["batch_no"]."','".$input["lot_no"]."',
       '".$input["prepare_on"]."','".$input["use_before"]."',
        '".$input["used_on"]."','".$input["qty_taken"]."','".$input["used_for"]."','".$input["remaining_qty"]."',
        '".$input["used_by"]."', '".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    
    
    else if($_GET["type"] == "getMediaConsumption"){
        $output=Array();
         $sql="SELECT a.id,a.media_code,a.batch_no,a.lot_no,a.qty_taken,b.material_name FROM media_prepartion a 
        left join others_material b on a.plant_id = b.plant_id and a.media_code = b.material_code
        WHERE DATE(a.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' 
        and a.id not in(select stock_id from media_consumption)
       Order by b.material_name";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output1 = array();
             $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDecontaminationRequest") {
        $sql = "INSERT INTO media_decontamination (media_code, batch_no, lot_no, decontaminated_cycle_no, incubator_id, incubator_temp, incubation_date, entry_by, entry_date) VALUES ('".$input["media_code"]."', '".$input["batch_no"]."', '".$input["lot_no"]."', '".$input["decontaminated_cycle_no"]."', '".$input["incubator_id"]."', '".$input["incubator_temp"]."', '".$input["incubation_date"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingDecontaminationRequests") {
        $output=Array();
        $sql="SELECT * FROM media_decontamination WHERE status !='COMPLETE'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveObseravation24") {
        $sql = "UPDATE media_decontamination SET obseravation24='".$input["obseravation24"]."', obseravation24_negative='".$input["obseravation24_negative"]."', status='OBSERVATION 24 COMPLETE', obseravation24_by='".$_GET["emp_id"]."', obseravation24_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveObseravation48") {
        $sql = "UPDATE media_decontamination SET obseravation48='".$input["obseravation48"]."', obseravation48_negative='".$input["obseravation48_negative"]."', conclusion='".$input["conclusion"]."', status='COMPLETE', obseravation48_by='".$_GET["emp_id"]."', obseravation48_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } else if ($_GET["type"] == "saveDecontaminationRecords") {
       $sql = "INSERT INTO media_decontamination (media_code, batch_no, lot_no, decontaminated_cycle_no, incubator_id, incubator_temp, incubation_date, entry_by, entry_date) VALUES ('".$input["media_code"]."', '".$input["batch_no"]."', '".$input["lot_no"]."', '".$input["decontaminated_cycle_no"]."', '".$input["incubator_id"]."', '".$input["incubator_temp"]."', '".$input["incubation_date"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getDecontaminationRecords") {
        $output=Array();
         $sql="SELECT d.*, m.media_name FROM media_decontamination d LEFT JOIN 
         media m ON d.media_code=m.media_code WHERE d.status='COMPLETE' ";
         //AND d.obseravation48_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                $output[] = $row;
            }
        
      echo json_encode($output);
        }
        
    } else if ($_GET["type"] == "downloadMediaStock") {
        $_GET['filename'] = 'MediaStock'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">MediaStock</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:15%;">Date</td>
                    <td style="width:15%;">Name of media</td>
                    <td style="width:15%;">Code No.</td>
                    <td style="width:15%;">Batch No.</td>
                    <td style="width:15%;">Quantity Received</td>
                    <td style="width:15%;">Use Before Date</td>
                    <td style="width:10%;">Received By</td>
                </tr>
            </thead>';
        //$sql="SELECT s.*, m.media_name FROM media_stock s LEFT JOIN media m ON s.media_code=m.media_code WHERE DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        //$result = $conn->query($sql);
       // if ($result->num_rows > 0) {
            //while ($row = $result->fetch_assoc()) {
             //$sql="SELECT * FROM  media_stock order by 1 desc";
                $sql="SELECT s.*, m.material_name FROM media_stock s LEFT JOIN others_material m ON s.media_code=m.material_code order by 1 desc; ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 15%;">'.$row['entry_date'].'</td>
                        <td style="width: 15%;">'.$row['material_name'].'</td>
                        <td style="width: 15%;">'.$row['media_code'].'</td>
                        <td style="width: 15%;">'.$row['batch_no'].'</td>
                        <td style="width: 15%;">'.$row['qty_received'].'</td>
                        <td style="width: 15%;">'.$row['before_date'].'</td>
                        <td style="width: 10%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MediaStock.pdf', 'I');
    } else if ($_GET["type"] == "downloadMediaStockbook") {
        $_GET['filename'] = 'MediaStock'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:15%;">Date</td>
                    <td style="width:15%;">Name of media</td>
                    <td style="width:15%;">Code No.</td>
                    <td style="width:15%;">Batch No.</td>
                    <td style="width:15%;">Quantity Received</td>
                    <td style="width:15%;">Use Before Date</td>
                    <td style="width:10%;">Received By</td>
                </tr>
            </thead>';
      $sql="SELECT s.*, m.media_name FROM media_stock s LEFT JOIN media m ON s.media_code=m.media_code 
     WHERE DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               
                $html.='<tr nobr="true">
                        <td style="width: 15%;">'.$row['entry_date'].'</td>
                        <td style="width: 15%;">'.$row['media_name'].'</td>
                        <td style="width: 15%;">'.$row['media_code'].'</td>
                        <td style="width: 15%;">'.$row['batch_no'].'</td>
                        <td style="width: 15%;">'.$row['qty'].'</td>
                        <td style="width: 15%;">'.$row['before_date'].'</td>
                        <td style="width: 10%;">'.$row['entry_by'].'</td>
                    </tr>';
               
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MediaStock.pdf', 'I');
    }else if ($_GET["type"] == "downloadMediaPrepartion") {
        $_GET['filename'] = 'MediaPrepartion'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Media Preparation</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:10%;">Entry Date</td>
                        <td style="width:10%;">Media Code</td>
                        <td style="width:8%;">Media Name</td>
                        <td style="width:8%;">Lot No</td>
                        <td style="width:8%;">B.No Dehydrated Media</td>
                        <td style="width:8%;">Quantity Taken in gm</td>
                        <td style="width:8%;">Volume prepared in ml or Liters</td>
                        <td style="width:8%;">Cumulative Quantity in gm</td>
                        <td style="width:8%;">pH medium of Before Serilization</td>
                        <td style="width:8%;">AutoCave Cycle No</td>
                        <td style="width:8%;">pH medium of After Serilization</td>
                        <td style="width:8%;">Prepared By</td>
                    </tr>
                </thead>';
        $sql="SELECT p.*, DATE(p.entry_date) as entry_date, m.media_name FROM media_prepartion p LEFT JOIN 
        media m ON p.media_code=m.media_code WHERE DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                    <td style="width: 10%;">'.$row['entry_date'].'</td>
                    <td style="width: 10%;">'.$row['media_code'].'</td>
                    <td style="width: 8%;">'.$row['media_name'].'</td>
                    <td style="width: 8%;">'.$row['lot_no'].'</td>
                    <td style="width: 8%;">'.$row['batch_no'].'</td>
                    <td style="width: 8%;">'.$row['qty_taken'].'</td>
                    <td style="width: 8%;">'.$row['volume_prepared'].'</td>
                    <td style="width: 8%;">'.$row['cumm_qty'].'</td>
                    <td style="width: 8%;">'.$row['ph_medium_before'].'</td>
                    <td style="width: 8%;">'.$row['cycle_no'].'</td>
                    <td style="width: 8%;">'.$row['ph_medium_after'].'</td>
                    <td style="width: 8%;">'.$row['entry_by'].'</td>
                </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MediaPrepartion.pdf', 'I');
    }else if ($_GET["type"] == "downloadMediaConsumption") {
        $_GET['filename'] = 'MediaConsumption'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">MediaConsumption</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:13%;">No of Plates/Tube/Flask prepared</td>
                        <td style="width:13%;">prepared on</td>
                        <td style="width:13%;">Use Before</td>
                        <td style="width:13%;">Used on</td>
                        <td style="width:13%;">Quantity used</td>
                        <td style="width:13%;">Used for</td>
                        <td style="width:11%;">Remaining Quantity</td>
                        <td style="width:11%;">Used By</td>
                    </tr>
                </thead>';
        $sql="SELECT * FROM media_consumption WHERE DATE(entry_date) BETWEEN '".$_GET["from_date"]."' 
        AND '".$_GET["to_date"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                    <td style="width: 13%;">'.$row['plate_no'].'</td>
                    <td style="width: 13%;">'.$row['prepare_on'].'</td>
                    <td style="width: 13%;">'.$row['use_before'].'</td>
                    <td style="width: 13%;">'.$row['used_on'].'</td>
                    <td style="width: 13%;">'.$row['quantity'].'</td>
                    <td style="width: 13%;">'.$row['used_for'].'</td>
                    <td style="width: 11%;">'.$row['remaining_qty'].'</td>
                    <td style="width: 11%;">'.$row['used_by'].'</td>
                </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MediaConsumption.pdf', 'I');
        
             $pdf->Output('MediaStock.pdf', 'I');
    }
   else if ($_GET["type"] == "downloadMediaPrepartion_new") {
        $_GET['filename'] = 'MediaPrepartion'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
      <div></div>
          <table cellpadding="2" border="1">
          <tr>
              <td style="width:70%">Bottle/in-house lot No:SCDA. :</td>
              <td style="width:30%;">Date :</td>
              </tr>
              </table><div></div>
             <table border="1" cellpadding="3">
              <tr>
               <td style="width:60px; text-align: center;">M-Code</td>
               <td style="width:100px;text-align: center;">Lot/ B.No.</td>
                <td style="width:80px;text-align: center;">Use before</td>
                 <td style="width:60px;text-align: center;">.g / 1</td>
                 <td style="width:110px;text-align: center;">Volume(ml)</td>
                 <td style="width:130px;text-align: center;">Media Weight(g)</td>
                  </tr>';
              //$sql="SELECT p.*, s.volume_unit,s.volume_prepared,s.containers,s.qty_per_containers,s.volume_unit 
              //FROM media_prepartion s LEFT JOIN media_stock p ON s.media_code=p.media_code
              // WHERE s.id ='".$_GET["id"]."'order by 1 desc limit 1 ";
             $sql="SELECT s.*, p.before_date FROM media_prepartion s LEFT JOIN media_stock p ON s.media_code=p.media_code
                  order by 1 desc limit 1"; 
                // WHERE s.id ='".$_GET["id"]."'order by 1 desc limit 1 ";
                   $result = $conn->query($sql);
                   if ($result->num_rows > 0) {
                   while ($row = $result->fetch_assoc()) {
                
                   $html.='<tr>
                   <td style="width:60px; text-align: center;">'.$row['media_code'].'</td>
                   <td style="width:100px;text-align: center;height:50px;">'.$row['batch_no'].'</td>
                   <td style="width:80px;text-align: center;height:50px;">'.$row['before_date'].'</td>
                   <td style="width:60px;text-align: center;height:50px;">'.$row['unit'].'</td>
                   <td style="width:110px;text-align: center;height:50px;">'.$row['volume_unit'].'</td>
                   <td style="width:130px;text-align: center;height:50px;">'.$row['qty_taken'].' </td>
                    </tr>';
                    $html.='</table >
                    <div></div>
                    <table border="1" cellpadding="3">
                    <tr>
                    <td style="width:180px;text-align: center; "  colspan="2">pH(Limit:'.$row['ph_value'].')</td>
                    <td style="width:360px;text-align: center;text-align: center;"  colspan="3">Distribution</td>
                    </tr> 
                    <tr>
                    <td style="width:90px;text-align: center;" rowspan="2">After</td>
                    <td style="width:90px;text-align: center;" rowspan="2"></td>
                     <td style="width:120px;text-align: center;">Container Type</td>
                      <td style="width:120px;text-align: center;">No. of container  </td>
                      <td style="width:120px;text-align: center;">ml/container</td>
                      </tr>
                       <tr>
                       <td style="width:120px;height:60px;text-align: center;">'.$row['qty_per_containers'].'</td>
                       <td style="width:120px;height:60px;text-align: center;">'.$row['containers'].'</td>
                        <td style="width:120px;height:60px;text-align: center;">'.$row['volume_unit'].'</td>
                         </tr>';
                         }
                         }
                         $html.='</table >
                     <div></div>
                      <table border="1" cellpadding="3">
                    <tr>
                    <td style="width:160px; text-align: center;" rowspan="2">Autoclave No./Load</td>
                    <td style="width:140px;text-align: center;" colspan="2">Sterlization</td>
                    <td style="width:80px;text-align: center;" rowspan="2">Duration</td>
                   <td style="width: 80px;text-align: center;" rowspan="2">Prepared by</td>
                    <td style="width: 80px;text-align: center;" rowspan="2">Remarks</td>
                  </tr>
                  <tr>
                  <td style="width: 70px;;text-align: center;">Temp &deg;C</td>
                  <td style="width:70px;text-align: center;">Pressure psi</td>
                  </tr>
                  <tr>
                  <td style="width: 160px;;text-align: center;"></td>
                  <td style="width:70px;text-align: center;">121.5&deg;C</td>
                  <td style="width:70px;text-align: center;">15 psi</td>
                  <td style="width:80px;text-align: center;"></td>
                  <td style="width:80px;text-align: center;"></td>
                  </tr>
                  </table>
                  <div></div>
                                       <div></div>

                  <table border="1" cellpadding="3">
                  <tr>
                  <td colspan="7" style="width:540px; text-align:center;">Positive Control / Growth Promotion Test</td>
                  </tr>
                  <tr>
                  <td style="width:60px;text-align:center;">Plates used</td>
                  <td style="width:100px;text-align:center;">Container used</td>
                  <td style="width:80px;text-align:center;">incubated at/ For </td>
                  <td style="width:60px;text-align:center;">cfu/ml added</td>
                  <td style="width:60px;text-align:center;">cfu/ml recovered</td>
                  <td style="width:90px;text-align:center;">%of recovery (50%-200%)</td>
                  <td style="width:90px;text-align:center;">Remark/Sign (C/NC)</td>
                  </tr>
                  <tr>
                  <td style="width:60px;text-align:center;"></td>
                  <td style="width:100px;text-align:center;"></td>
                  <td style="width:80px;text-align:center;"rowspan="2"></td>
                  <td style="width:60px;text-align:center; "></td>
                  <td style="width:60px;text-align:center;"></td>
                  <td style="width:90px;text-align:center;"></td>
                  <td style="width:90px;text-align:center;"></td>
                  </tr>
                  </table>
                  <div></div>                     <div></div>

                  <table border="1" cellpadding="3">
                  <tr>
                  <td colspan="7" style="width:540px; text-align:center;">Sterility result of prepared media / Negative Control</td>
                  </tr>
                  <tr>
                  <td style="text-align:center;width:80px;">Date</td>
                  <td style="text-align:center;width:90px;">Container Used</td>
                  <td style="text-align:center;width:80px;">Incubated at / For</td>
                  <td style="text-align:center;width:110px;">Obervation</td>
                  <td style="text-align:center;width:90px;">Remarks (C/NC)</td>
                  <td style="text-align:center;width:90px;">Sign</td>
                  </tr>
                  <tr>
                  <td style="text-align:center;width:80px;"></td>
                  <td style="text-align:center;width:90px;"></td>
                  <td style="text-align:center;width:80px;"></td>
                  <td style="text-align:center;width:110px;"></td>
                  <td style="text-align:center;width:90px;"> </td>
                  <td style="text-align:center;width:90px;"></td>
                  </tr>
                  </table><div></div>
                  <table border="1" cellpadding="3">
                  <tr>
                  <td style="text-align:center;width: 140px;height:30px;"></td>
                  <td style="text-align:center;width: 200px;height:30px;"><b>ANALYSED BY/ON</b></td> 
                  <td style="text-align:center;width: 200px;height:30px;"><b>CHECKED BY/ON</b></td>
                  </tr>
                  <tr>
                  <td style="text-align:left;width: 140px;height:30px;font-weight:bold;">Name</td>
                  <td style="text-align:center;width: 200px;height:30px;"></td>
                  <td style="text-align:center;width: 200px;height:30px;"></td>
                  </tr>
                   <tr>
                  <td style="text-align:left;width: 140px;height:30px;font-weight:bold;">User Id</td>
                  <td style="text-align:center;width: 200px;height:30px;"></td>
                  <td style="text-align:center;width: 200px;height:30px;"></td>
                  </tr>
                   <tr>
                  <td style="text-align:left;width: 140px;height:30px;font-weight:bold;">Date</td>
                  <td style="text-align:center;width: 200px;height:30px;"></td>
                  <td style="text-align:center;width: 200px;height:30px;"></td>
                  </tr>
                   <tr>
                  <td style="text-align:left;width: 140px;height:30px;font-weight:bold;">Time</td>
                  <td style="text-align:center;width: 200px;height:30px;"></td>
                  <td style="text-align:center;width: 200px;height:30px;"></td>
                  </tr>
                  </table><div></div><div>
                  <table border="1" cellpadding="5">
                  <tr>
                  <td style="text-align:center;width: 25%;"></td>
                  <td style="text-align:center;width: 25%;"><b>PREPARED BY</b></td>
                  <td style="text-align:center;width: 25%;"><b>REVIEWED BY</b></td>
                  <td style="text-align:center;width: 25%;"><b>APPROVED BY</b></td>
                  </tr>
                  <tr>
                  <td style="width: 25%;">Name</td>
                  <td style="width: 25%;"></td>
                  <td style="width: 25%;"></td>
                  <td style="width: 25%;"></td>
                  </tr>
                  <tr>
                  <td style="width: 25%;">Sign/Date</td>
                  <td style="width: 25%;height:30px;"></td>
                  <td style="width: 25%;height:30px;"></td>
                  <td style="width: 25%;height:30px;"></td>
                  </tr>
                  <tr>
                  <td style="width: 25%;">Designation</td>
                  <td style="width: 25%;"></td>
                  <td style="width: 25%;"></td>
                  <td style="width: 25%;"></td>
                  </tr>
                  <tr>
                  <td style="width: 25%;">Department</td>
                  <td style="width: 25%;"></td>
                  <td style="width: 25%;"></td>
                  <td style="width: 25%;"></td>
                  </tr>
                  </table>';
                  $pdf->writeHTML($html, true, false, false, false, '');
                  $pdf->Output('MediaPrepartion.pdf', 'I'); 
        
        
        
        
        
        
    } else if ($_GET["type"] == "saveStock") {
        $sql = "INSERT INTO media_stock (media_code, batch_no, qty, before_date, entry_by, entry_date) VALUES ('".$input["media_code"]."','".$input["batch_no"]."','".$input["qty"]."','".$input["before_date"]."','".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    
    else if ($_GET["type"] == "getAvailableStock") {
        $output = array();
         $sql = "SELECT * FROM  my_view  where material_subtype = 'Media'  AND plant_id = '".$_GET["plant_id"]."' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                 $sql1 ="select a.*, IFNULL(b.used_qty,0) as used_qty,(a.qty_received - IFNULL(b.used_qty,0)) as avbl_qty from 
                 (SELECT specifVol,qty_received,specif_weight,media_code,batch_no,sum(qty_received) as receive_qty FROM media_stock where plant_id= '".$_GET["plant_id"]."'
                 and media_code = '".$row["material_code"]."'  GROUP by media_code,batch_no,specifVol,specif_weight,qty_received) a left join 
                (SELECT media_code,batch_no,IFNULL(sum(qty_taken),0) as used_qty FROM media_prepartion 
                where plant_id= '".$_GET["plant_id"]."'  and media_code = '".$row["material_code"]."' GROUP by media_code,batch_no)
                b on  a.media_code = b.media_code and a.batch_no=b.batch_no";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    
    else if ($_GET["type"] == "getAvailableStockFORPREPARATION") {
        $output = array();
         $sql = "SELECT * FROM  my_view  where material_subtype = 'Media'  AND plant_id = '".$_GET["plant_id"]."' ";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = array();
                 $sql1 ="select a.*, IFNULL(b.used_qty,0) as used_qty,(a.qty_received - IFNULL(b.used_qty,0)) as avbl_qty from 
                 (SELECT specifVol,qty_received,specif_weight,media_code,batch_no,sum(qty_received) as receive_qty FROM media_stock where plant_id= '".$_GET["plant_id"]."' AND status= '".$_GET["status"]."'
                 and media_code = '".$row["material_code"]."'  GROUP by media_code,batch_no,specifVol,specif_weight,qty_received) a left join 
                (SELECT media_code,batch_no,IFNULL(sum(qty_taken),0) as used_qty FROM media_prepartion 
                where plant_id= '".$_GET["plant_id"]."'  and media_code = '".$row["material_code"]."' GROUP by media_code,batch_no)
                b on  a.media_code = b.media_code and a.batch_no=b.batch_no";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
    else if ($_GET["type"] == "getMediaStockBook") {
      
                $output = array();
                // $sql1 ="SELECT s.*, IFNULL(b.used_qty,0) as used_qty, (s.qty_received - IFNULL(b.used_qty,0)) as avbl_qty, m.material_name 
                // From media_stock s left join (SELECT media_code,batch_no,IFNULL(sum(qty_taken),0) as used_qty FROM media_prepartion  where 
                // plant_id= '".$_GET["plant_id"]."'   GROUP by media_code,batch_no) b on  s.media_code = b.media_code and s.batch_no=b.batch_no
                // left join  my_view m ON m.material_code  = s.media_code where s.plant_id = '".$_GET["plant_id"]."' ";
                
                   $sql1 ="SELECT s.* ,m.material_name From media_stock s  left join  my_view m ON m.material_code  = s.media_code 
                   where s.plant_id = '".$_GET["plant_id"]."' ";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row = $result1->fetch_assoc()) {
                        
                    $output1 = array();
                    $sql12 = "select * from media_prepartion WHERE media_code='".$row["media_code"]."' AND  batch_no='".$row["batch_no"]."'";
                    $result12 = $conn->query($sql12);
                    if ($result12->num_rows > 0) {
                        while ($row12 = $result12->fetch_assoc()) {
                            $output1[] = $row12;
                        }
                    }
                    
                    $sql1 = "select IFNULL(sum(qty_taken),0) as used_qty from media_prepartion WHERE media_code='".$row["media_code"]."' AND  batch_no='".$row["batch_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["used_qty"] = $row1["used_qty"];
                        }
                    }
                    
                    
                $row["avbl_qty"] = number_format($row["qty_received"]-  $row["used_qty"] , 2, '.', '');

                     
                        $row["cunsumption"] = $output1;
                        $output[] = $row;
                    }
                }
         
        echo json_encode($output);
    } 
    
    
    
    else if ($_GET["type"] == "updateMedia") {
        $sql = "UPDATE media SET ph='".$input["ph"]."', sterilization='".$input["sterilization"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "saveGrowthSolidMedia") {
        $sql = "INSERT INTO solid_media (batch_no,inc_date,inc_id,inc_temp,incubation_complete,lot_no,media_code,observations,prepared_date,suspension_no,satisfactory,entry_by,entry_date) VALUES('".$input["batch_no"]."','".$input["inc_date"]."','".$input["inc_id"]."','".$input["inc_temp"]."','".$input["incubation_complete"]."','".$input["lot_no"]."','".$input["media_code"]."','".json_encode($input["observations"])."','".$input["prepared_date"]."','".$input["suspension_no"]."','".$input["satisfactory"]."',  '".$_GET["emp_id"]."','$entry_date')";
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getGrowthSolidMediaLog"){
        $output=Array();
        $sql="SELECT s.*, DATE(s.entry_date) as entry_date, m.media_name  FROM solid_media s LEFT JOIN media m ON s.media_code=m.media_code WHERE DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
               // $row["observations"] = json_decode($row["observations"]);
                $output[] = $row;
            }
        }
      echo json_encode($output);
    }else if ($_GET["type"] == "saveGrowthLiquidMedia") {
        $sql = "INSERT INTO liquid_media (batch_no,inc_date,inc_id,inc_temp,incubation_complete,lot_no,media_code,observations,prepared_date,suspension_no,satisfactory,entry_by,entry_date) 
        VALUES('".$input["batch_no"]."','".$input["inc_date"]."','".$input["inc_id"]."','".$input["inc_temp"]."','".$input["incubation_complete"]."','".$input["lot_no"]."','".$input["media_code"]."','".json_encode($input["observations"])."','".$input["prepared_date"]."','".$input["suspension_no"]."','".$input["satisfactory"]."',  '".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getGrowthLiquidMediaLog"){
        $output=Array();
        $sql="SELECT l.*, DATE(l.entry_date) as entry_date, m.media_name  FROM liquid_media l LEFT JOIN media m 
        ON l.media_code=m.media_code"; //WHERE DATE(l.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            //    $row["observations"] = json_decode($row["observations"]);
                $output[] = $row;
            }
        }
      echo json_encode($output);
      
    }else if ($_GET["type"] == "saveDisposalRecords") {
       $sql = "INSERT INTO media_prepartion (media_code, batch_no, lot_no, qty_taken, volume_prepared, volume_unit,entry_by,entry_date) VALUES('".$input["media_code"]."','".$input["batch_no"]."', '".$input["lot_no"]."','".$input["qty_taken"]."','".$input["volume_prepared"]."', '".$input["volume_unit"]."', '".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getDisposalRecords"){
        $output=Array();
         $sql="SELECT p.*, DATE(p.entry_date) as entry_date, m.media_name FROM media_prepartion p 
         LEFT JOIN media m ON p.media_code=m.media_code";// WHERE DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
               $result = $conn->query($sql);
               if ($result->num_rows > 0) {
               while ($row = $result->fetch_assoc()) {
                
                $output[] = $row;
            }
        }
      echo json_encode($output);
      
      
    }else if ($_GET["type"] == "downloadDecontaminationRecord") {
        $_GET['filename'] = 'DecontaminationRecord'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $sql="SELECT d.*, m.media_name FROM media_decontamination d LEFT JOIN media m ON d.media_code=m.media_code WHERE d.id='".$_GET["id"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='
        <h2 style="text-align:center">DecontaminationRecord</h2>
        <table border="1" cellpadding="8">
                    <tr>
                        <td style="width:25%;font-weight:bold;">Media Name:</td>
                        <td style="width:25%;">'.$row['media_name'].'</td>
                        <td style="width:25%;font-weight:bold;">Batch No:</td>
                        <td style="width:25%;">'.$row['batch_no'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Lot No:</td>
                        <td style="width:25%;">'.$row['lot_no'].'</td>
                        <td style="width:25%;font-weight:bold;">Decontaminated Cycle No</td>
                        <td style="width:25%;">'.$row['decontaminated_cycle_no'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold">Incubator Id No</td>
                        <td style="width:25%;">'.$row['incubator_id'].'</td>
                        <td style="width:25%;font-weight:bold">Incubation Temp</td>
                        <td style="width:25%;">'.$row['incubator_temp'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Date Of Incubation</td>
                        <td style="width:25%;">'.$row['incubation_date'].'</td>
                        <td style="width:25%;font-weight:bold;">Observation of 48 Date</td>
                        <td style="width:25%;">'.$row["obseravation48_date"].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Procedure</td>
                        <td style="width:75%;">Take 10 ml decontaminated sample add in 90 ml soyabean casein digest medium and incubator the tubed 30 to 35 c for 24 to 48 hrs</td>
                    </tr>
                </table>
                <div></div> <div></div> <div></div>';
        $html.='<table border="1" cellpadding="8">
                    <tr>
                        <td style="width:100%;font-weight:bold; text-align:center;">OBSERVATION</td>
                    </tr>
                    <tr>
                        <td style="width:40%;font-weight:bold;">Observation After 24 Hrs</td>
                        <td style="width:10%;">'.$row['obseravation24'].'</td>
                        <td style="width:40%;font-weight:bold;">Negative Control After 24 Hrs</td>
                        <td style="width:10%;">'.$row['Obseravation24_negative'].'</td>
                    </tr>
                    <tr>
                        <td style="width:40%;font-weight:bold;">Observation After 48Hrs</td>
                        <td style="width:10%;">'.$row['obseravation48'].'</td>
                        <td style="width:40%;font-weight:bold;">Negative Control After 48 Hrs</td>
                        <td style="width:10%;">'.$row['obseravation48_negative'].'</td>
                    </tr>
               
        
      
                    <tr>
                        <td style="width:15%;font-weight:bold;">Limit</td>
                        <td style="width:85%;">No Should be Observed intubes</td>
                    </tr>
                    <tr>
                        <td style="width:15%;font-weight:bold;">Conculsion</td>
                        <td style="width:85%;">'.$row['conclusion'].'</td>
                    </tr>
                    <tr>
                        <td style="width:15%;font-weight:bold;">Remark</td>
                        <td style="width:85%;">If no growth Observed in form turbidity means decontaminition of media Cucle runing  Properly</td>
                    </tr>
               </table>
                <div></div>';
            
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('DecontaminationRecord.pdf', 'I');
            }
        }
    }else if ($_GET["type"] == "downloadDecontaminationRecords") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<h3 style="text-align:center;">DECONTAMINATED MEDIA VERIFICATION RECORD</h3>
                <table border="1" cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:11%;">Date</td>
                        <td style="width:8%;">Media Name</td>
                        <td style="width:8%;">Media Code</td>
                        <td style="width:7%;">Batch No</td>
                        <td style="width:6%;">Lot No</td>
                        <td style="width:11%;">Decontaminated Cycle No</td>
                        <td style="width:10%;">Incubator No</td>
                        <td style="width:11%;">Incubation Temp</td>
                        <td style="width:11%;">Date Of Incubation</td>
                        <td style="width:9%;">Done By</td>
                        <td style="width:8%;">Status</td>
                    </tr>';
         $sql="SELECT d.*, m.media_name FROM media_decontamination d LEFT JOIN media m ON d.media_code=m.media_code WHERE d.status='COMPLETE' AND d.obseravation48_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {   
        $html.='<tr>
                    <td style="width:11%;">'.$row['entry_date'].'</td>
                    <td style="width:8%;">'.$row['media_name'].'</td>
                    <td style="width:8%;">'.$row['media_code'].'</td>
                    <td style="width:7%;">'.$row['batch_no'].'</td>
                    <td style="width:6%;">'.$row['lot_no'].'</td>
                    <td style="width:11%;">'.$row['decontaminated_cycle_no'].'</td>
                    <td style="width:10%;">'.$row['incubator_id'].'</td>
                    <td style="width:11%;">'.$row['incubator_temp'].'</td>
                    <td style="width:11%;">'.$row['incubation_date'].'</td>
                    <td style="width:9%;">'.$row['entry_by'].'</td>
                    <td style="width:8%;">'.$row['status'].'</td>
                </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('DecontaminationRecords.pdf', 'I');
   
    }else if ($_GET["type"] == "downloadCalibrationRecord") {
        $_GET['filename'] = 'CalibrationRecord'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">CalibrationRecord</h2>
        <table border="1" cellpadding="3">
                    <tr>
                        <td style="width:25%; font-weight:bold;">Date</td>
                        <td style="width:25%;"></td>
                        <td style="width:25%;font-weight:bold;">Instrument Id</td>
                        <td style="width:25%;"></td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Conclusion</td>
                        <td style="width:25%;"></td>
                        <td style="width:25%;font-weight:bold;">Status</td>
                        <td style="width:25%;"></td>
                    </tr>
                    <tr>
                        <td style="width:25%;font-weight:bold;">Entry By</td>
                        <td style="width:25%;"></td>
                        <td style="width:25%;font-weight:bold;"></td>
                        <td style="width:25%;"></td>
                    </tr>
                </table>
                <div></div>
                <table border="1" cellpadding="5">
                    <tr>
                        <td rowspan="3" style="width:11%;">Sr No</td>
                        <td style="width:90%;Text-align:center;">No of time Pressed</td>
                    </tr>
                    <tr>
                        <td style="width:30%;text-align:center;">(10 Time)</td>
                        <td style="width:30%;text-align:center;">(50Time)</td>
                        <td style="width:30%;text-align:center;">(100times)</td>
                    </tr>
                    <tr>
                        <td style="width:15%;">Manually</td>
                        <td style="width:15%;">Display</td>
                        <td style="width:15%;">Manually</td>
                        <td style="width:15%;">Display</td>
                        <td style="width:15%;">Manually</td>
                        <td style="width:15%;">Display</td>
                    </tr>
                    <tr>
                        <td style="width:11%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:11%;">Acceptance Ceritria Calibration</td>
                        <td style="width:90%;"></td>
                    </tr>
                </table>';

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CalibrationRecord.pdf', 'I');
    }else if ($_GET["type"] == "downloadDisposalRecords") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<h3 style="text-align:center;">Disposal Records</h3>
                <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:5%;">Date	</td>
                        <td style="width:10%;">Autoclave Cycle No	</td>
                        <td style="width:8%;">Material</td>
                        <td style="width:8%;">Cycle started at	</td>
                        <td style="width:8%;">121°C Achieved time	</td>
                        <td style="width:13%;">Chamber Steam Pressure (1.5 lbs)	</td>
                        <td style="width:8%;">Cycle End Time	</td>
                        <td style="width:8%;">Hold Time (Minutes)	</td>
                        <td style="width:8%;">Chemical Indicator	</td>
                        <td style="width:8%;">Remarks</td>
                        <td style="width:8%;">Done By	</td>
                        <td style="width:8%;">Checked By</td>
                    </tr>
                </thead>';
               $sql="SELECT p.*, DATE(p.entry_date) as entry_date, m.media_name FROM media_prepartion p LEFT 
               JOIN media m ON p.media_code=m.media_code WHERE DATE(p.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ";
               $result = $conn->query($sql);
               if ($result->num_rows > 0) {
               while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                    <td style="width: 5%;">'.$row['entry_date'].'</td>
                    <td style="width: 10%;">'.$row['cycle_no'].'</td>
                    <td style="width: 8%;">'.$row[''].'</td>
                    <td style="width: 8%;">'.$row[''].'</td>
                    <td style="width: 8%;">'.$row[''].'</td>
                    <td style="width: 13%;">'.$row[''].'</td>
                    <td style="width: 8%;">'.$row[''].'</td>
                    <td style="width: 8%;">'.$row[''].'</td>
                    <td style="width: 8%;">'.$row[''].'</td>
                    <td style="width: 8%;">'.$row[''].'</td>
                    <td style="width: 8%;">'.$row[''].'</td>
                    <td style="width: 8%;">'.$row[''].'</td>
                </tr>';
            
               }
               }

        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Disposal Records.pdf', 'I');
               
               
    } else if ($_GET["type"] == "downloadGrowthSolidMediaLog") {
        $_GET['formatno'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<h3 style="text-align:center;">Growth Solid Media Log</h3>
            <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Date</td>
                    <td style="width:10%;">Media Code</td>
                    <td style="width:10%;">Media Name</td>
                    <td style="width:10%;">Media Batch No.</td>
                    <td style="width:5%;">Lot No</td>
                    <td style="width:10%;">Culture Suspension Number</td>
                    <td style="width:10%;">Prepared Date</td>
                    <td style="width:10%;">Incubation Temp</td>
                    <td style="width:10%;">Incubator ID No</td>
                    <td style="width:10%;">Date of Incubation</td>
                    <td style="width:10%;">Incubation Completed On</td>
                </tr>
            </thead>';
             $sql="SELECT s.*, DATE(s.entry_date) as entry_date, m.media_name  FROM solid_media s LEFT JOIN media m ON s.media_code=m.media_code WHERE DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
             $result = $conn->query($sql);
             if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                            <td style="width:5%;">'.date("d/m/Y",($row['entry_date'])).'</td>
                            <td style="width:10%;">'.$row['media_code'].'</td>
                            <td style="width:10%;">'.$row['media_name'].'</td>
                            <td style="width:10%;">'.$row['batch_no'].'</td>
                            <td style="width:5%;">'.$row['lot_no'].'</td>
                            <td style="width:10%;">'.$row['suspension_no'].'</td>
                            <td style="width:10%;">'. date("d/m/Y",($row['prepared_date'])).'</td>
                            <td style="width:10%;">'.$row['inc_temp'].'</td>
                            <td style="width:10%;">'.$row['inc_id'].'</td>
                            <td style="width:10%;">'. date("d/m/Y",($row['inc_date'])).'</td>
                            <td style="width:10%;">'. date("d/m/Y",( $row['incubation_complete'])).'</td>
                        </tr>';
                
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Growth Solid Media Log.pdf', 'I');
    }else if ($_GET["type"] == "downloadGrowthLiquidMediaLog") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='<h3 style="text-align:center;">Growth Liquid Media Log</h3>
            <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width:7%;">Entry Date</td>
                        <td style="width:8%;">Media Code</td>
                        <td style="width:10%;">Media Name</td>
                        <td style="width:10%;">Media Batch No.</td>
                        <td style="width:5%;">Lot No</td>
                        <td style="width:10%;">Culture Suspension Number</td>
                        <td style="width:10%;">Prepared Date</td>
                        <td style="width:10%;">Incubation Temp</td>
                        <td style="width:10%;">Incubator ID No</td>
                        <td style="width:10%;">Date of Incubation</td>
                        <td style="width:10%;">Incubation Completed On</td>
                    </tr>
                </thead>';
      
    //   echo $sql="SELECT l.*, DATE(l.entry_date) as entry_date, m.media_name  FROM liquid_media l LEFT JOIN media m ON l.media_code=m.media_code WHERE DATE(l.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
              $sql = "SELECT * FROM media";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width:7%;">'.$row['entry_date'].'</td>
                        <td style="width:8%;">'.$row['media_code'].'</td>
                        <td style="width:10%;">'.$row['media_name'].'</td>
                        <td style="width:10%;">'.$row['batch_no'].'</td>
                        <td style="width:5%;">'.$row['lot_no'].'</td>
                        <td style="width:10%;">'.$row['suspension_no'].'</td>
                        <td style="width:10%;">'.$row['prepared_date'].'</td>
                        <td style="width:10%;">'.$row['inc_temp'].'</td>
                        <td style="width:10%;">'.$row['inc_id'].'</td>
                        <td style="width:10%;">'.$row['inc_date'].'</td>
                        <td style="width:10%;">'.$row['incubation_complete'].'</td>
                </tr>';
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('GrowthLiquidMediaLog.pdf', 'I');
    }


}


$conn->close();
?>