<?php

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getasset_issued") {
        $output = Array();
 $sql = "SELECT * FROM asset_issued";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveAsset_issued") {
        $sql = "INSERT INTO asset_issued (plant_id,asset_name,asset_id,department_name) VALUES ('".$_GET['plant_id']."','".$input["asset_name"]."', '".$input["asset_id"]."', '".$input["department"]."' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "saveAsset") {
        $sql = "INSERT INTO laundry (plant_id,LglNm,person,Trdnm,state_code,gst_type,gst_no,country,address,pincode,contact_no,email) VALUES ('".$_GET['plant_id']."','".$input["LglNm"]."'
        , '".$input["person"]."', '".$input["Trdnm"]."' 
        , '".$input["state_code"]."', '".$input["gst_type"]."'
        , '".$input["gst_no"]."', '".$input["country"]."', '".$input["address"]."'
        , '".$input["pincode"]."', '".$input["contact_no"]."', '".$input["email"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "mastersave") {
        $assetName = $conn->real_escape_string(isset($input["asset_name"]) ? $input["asset_name"] : "");
        $assetId = $conn->real_escape_string(isset($input["asset_id"]) ? $input["asset_id"] : "");
        $department = $conn->real_escape_string(isset($input["department"]) ? $input["department"] : "");
        $assetType = $conn->real_escape_string(isset($input["asset_type"]) ? $input["asset_type"] : "");
        $description = $conn->real_escape_string(isset($input["description"]) ? $input["description"] : "");

        $sql = "INSERT INTO savemaster (plant_id,asset_name,asset_id,department,asset_type,description) VALUES ('".$_GET['plant_id']."','".$assetName."'
        , '".$assetId."', '".$department."' 
        , '".$assetType."', '".$description."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getAssetMasters") {
        $output = Array();
        $sql = "SELECT * FROM savemaster WHERE plant_id='".$_GET['plant_id']."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET['type'] == 'downloadAssetMasterLog') {
        $_GET['filename'] = 'Asset Master Log';
        $_GET['pdftype'] = 'onlyheader';
        include("../pdfimp2.php");
        $html= "";

        $html.='
        <h3 style="text-align:center">Asset Master Log</h3>
        <table cellpadding="5" border="0.1">
        <tr>
          <td style="width:10%;text-align:center"><b>Sr No.</b></td>
          <td style="width:20%;text-align:center"><b>Asset Name</b></td>
          <td style="width:15%;text-align:center"><b>ID</b></td>
          <td style="width:20%;text-align:center"><b>Department</b></td>
          <td style="width:15%;text-align:center"><b>Type</b></td>
          <td style="width:20%;text-align:center"><b>Description</b></td>
        </tr>';

        $sql = "SELECT * FROM savemaster WHERE plant_id='".$_GET['plant_id']."' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $html.='
                <tr>
                  <td style="width:10%;text-align:center">'.$i.'</td>
                  <td style="width:20%;text-align:center">'.$row['asset_name'].'</td>
                  <td style="width:15%;text-align:center">'.$row['asset_id'].'</td>
                  <td style="width:20%;text-align:center">'.$row['department'].'</td>
                  <td style="width:15%;text-align:center">'.$row['asset_type'].'</td>
                  <td style="width:20%;text-align:center">'.$row['description'].'</td>
                </tr>';
                $i++;
            }
        } else {
            $html.='
            <tr>
              <td colspan="6" style="text-align:center">No Records Found</td>
            </tr>';
        }

        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('asset_master_log.pdf', 'I');
    }
    else if($_GET['type'] == 'downloadassert_log') {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:10%;text-align:center"><b>Sr No.</b></td>
          <td style="width:30%;text-align:center"><b>Asset Name</b></td>
           <td style="width:30%;text-align:center"><b>Asset id</b></td>
            <td style="width:30%;text-align:center"><b>Department</b></td>
         
         </tr>';
         
 $sql = "SELECT * FROM asset_issued";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
 $sql1 = "SELECT * FROM externaltrainer WHERE id=".$row["proposed_trainer"];
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row0 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row0["trainer_name"];
                    }
                }
              $html.='  <tr>
         <td style="width:10%;text-align:center">'.$i.'</td>
          <td style="width:30%;text-align:center">'.$row['asset_name'].'</td>
           <td style="width:30%;text-align:center">'.$row['asset_id'].'</td>
            <td style="width:30%;text-align:center">'.$row['department_name'].'</td>
          
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
    else if ($_GET["type"] == "savegowntest") {
        $sql = "INSERT INTO gown (gown_type,fabric_type,color,gst_type) VALUES ('".$input["gown_type"]."', '".$input["fabric_type"]."', '".$input["color"]."', '".$input["gst_type"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }


}

$conn->close();
?>