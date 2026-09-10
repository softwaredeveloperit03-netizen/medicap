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

    if($_GET["type"] == "saveAdminRecord"){
      $sql ="INSERT INTO premises_preventive(area ,floor ,checkpoints ,entry_by , entry_date ,check_by)VALUES('Administration block' ,'".$input["floor"]."' ,'".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','".$entry_date."','' )";
      if($conn->query($sql)){
          echo "{\"status\":\"success\" }";
      } else{
          echo "{\"status\":\"failed\" }";
      }       
    }else if($_GET["type"] == "pendingAdminRecord"){
        $output=Array();
        $sql="SELECT * FROM premises_preventive WHERE area='Administration block' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["checkpoints"] = json_decode($row["checkpoints"]);
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if($_GET["type"] == "saveCanteen"){
       $sql ="INSERT INTO premises_preventive(area ,floor ,checkpoints ,entry_by , entry_date,check_by)VALUES('Canteen' ,'".$input["floor"]."' ,'".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','".$entry_date."' ,'')";
      if($conn->query($sql)){
          echo "{\"status\":\"success\" }";
      } else{
          echo "{\"status\":\"failed\" }";
      }       
    }else if($_GET["type"] == "getCanteenRecord"){
        $output=Array();
        $sql="SELECT * FROM premises_preventive WHERE area='Canteen' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["checkpoints"] = json_decode($row["checkpoints"]);
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if($_GET["type"] =="saveETP"){
      $sql ="INSERT INTO premises_preventive(area ,floor ,checkpoints ,entry_by , entry_date ,check_by)VALUES('Effluent Treatment Plant' ,'".$input["floor"]."' ,'".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','".$entry_date."','' )";
      if($conn->query($sql)){
          echo "{\"status\":\"success\" }";
      } else{
          echo "{\"status\":\"failed\" }";
      }       
    }else if($_GET["type"] == "getETP"){
        $output=Array();
        $sql="SELECT * FROM premises_preventive WHERE area='Effluent Treatment Plant' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["checkpoints"] = json_decode($row["checkpoints"]);
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if($_GET["type"] =="saveSecurity"){
      $sql ="INSERT INTO premises_preventive(area ,floor ,checkpoints ,entry_by , entry_date ,check_by)VALUES('Factory & Security office' ,'".$input["floor"]."' ,'".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','".$entry_date."','' )";
      if($conn->query($sql)){
          echo "{\"status\":\"success\" }";
      } else{
          echo "{\"status\":\"failed\" }";
      }       
    }else if($_GET["type"] == "getSecurity"){
        $output=Array();
        $sql="SELECT * FROM premises_preventive WHERE area='Factory & Security office' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["checkpoints"] = json_decode($row["checkpoints"]);
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if($_GET["type"] =="saveProductionPlant"){
      $sql ="INSERT INTO premises_preventive(area ,floor ,checkpoints ,entry_by , entry_date ,check_by)VALUES('Production Plant' ,'".$input["floor"]."' ,'".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','".$entry_date."','' )";
      if($conn->query($sql)){
          echo "{\"status\":\"success\" }";
      } else{
          echo "{\"status\":\"failed\" }";
      }       
    }else if($_GET["type"] == "getProductionPlant"){
        $output=Array();
        $sql="SELECT * FROM premises_preventive WHERE area='Production Plant' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["checkpoints"] = json_decode($row["checkpoints"]);
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if($_GET["type"] =="saveSolventRecovery"){
      $sql ="INSERT INTO premises_preventive(area ,floor ,checkpoints ,entry_by , entry_date ,check_by)VALUES('Solvent Recovery Plant' ,'".$input["floor"]."' ,'".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','".$entry_date."','' )";
      if($conn->query($sql)){
          echo "{\"status\":\"success\" }";
      } else{
          echo "{\"status\":\"failed\" }";
      }       
    }else if($_GET["type"] == "getSolventRecovery"){
        $output=Array();
        $sql="SELECT * FROM premises_preventive WHERE area='Solvent Recovery Plant' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["checkpoints"] = json_decode($row["checkpoints"]);
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if($_GET["type"] =="saveRawMaterial"){
      $sql ="INSERT INTO premises_preventive(area ,floor ,checkpoints ,entry_by , entry_date ,check_by)VALUES('Raw Material Warehouse' ,'".$input["floor"]."' ,'".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','".$entry_date."','' )";
      if($conn->query($sql)){
          echo "{\"status\":\"success\" }";
      } else{
          echo "{\"status\":\"failed\" }";
      }       
    }else if($_GET["type"] == "getRawMaterial"){
        $output=Array();
        $sql="SELECT * FROM premises_preventive WHERE area='Raw Material Warehouse' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["checkpoints"] = json_decode($row["checkpoints"]);
                    $output[] = $row;
                }
            }
        echo json_encode($output);
    }else if($_GET["type"] =="saveFinishGoods"){
        $sql ="INSERT INTO premises_preventive(area ,floor ,checkpoints ,entry_by , entry_date ,check_by)VALUES('Raw Material Warehouse' ,'".$input["floor"]."' ,'".json_encode($input["checkpoints"])."','".$_GET["emp_id"]."','".$entry_date."','' )";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        }else{
            echo "{ \"status\" : \"failed\" }";
        }
    }else if($_GET["type"] =="getFinishGoods"){
        $sql="SELECT * FROM premises_preventive WHERE area='Raw Material Warehouse' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["checkpoints"]=json_decode($row["checkpoints"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadSecurity") {
        $_GET['filename'] = 'Admin Block Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;">Sr.</td>
                    <td style="width: 20%;">Entry By	</td>
                    <td style="width: 20%;">Entry Date	</td>
                    <td style="width: 20%;">Floor</td>
                    <td style="width: 20%;">Check By	</td>
                    
                </tr>
            </thead>';
            $i=1;
        $sql="SELECT * FROM premises_preventive WHERE area='Factory & Security office' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$i.'.</td>
                        <td style="width: 20%;">'.$row['entry_by'].'</td>
                        <td style="width: 20%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Admin Block Log.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadFinishGoods") {
        $_GET['filename'] = 'Admin Block Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;">Sr.</td>
                    <td style="width: 20%;">Entry By	</td>
                    <td style="width: 20%;">Entry Date	</td>
                    <td style="width: 20%;">Floor</td>
                    <td style="width: 20%;">Check By	</td>
                    
                </tr>
            </thead>';
        $sql="SELECT * FROM premises_preventive WHERE area='Administration block' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$i.'.</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                        <td style="width: 20%;">'.$row[''].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Admin Block Log.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadgetRawMaterial") {
        $_GET['filename'] = 'RM Warehouse'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;">Sr.</td>
                    <td style="width: 20%;">Entry By	</td>
                    <td style="width: 20%;">Entry Date	</td>
                    <td style="width: 20%;">Floor</td>
                    <td style="width: 20%;">Check By	</td>
                    
                </tr>
            </thead>';
            $i=1;
        $sql="SELECT * FROM premises_preventive WHERE area='Raw Material Warehouse' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$i.'.</td>
                        <td style="width: 20%;">'.$row['entry_by'].'</td>
                        <td style="width: 20%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 20%;">'.$row['floor'].'</td>
                        <td style="width: 20%;">'.$row['check_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Admin Block Log.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadgetSolventRecovery") {
        $_GET['filename'] = 'SRP Form'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;">Sr.</td>
                    <td style="width: 20%;">Entry By	</td>
                    <td style="width: 20%;">Entry Date	</td>
                    <td style="width: 20%;">Floor</td>
                    <td style="width: 20%;">Check By	</td>
                    
                </tr>
            </thead>';
            $i=1;
        $sql="SELECT * FROM premises_preventive WHERE area='Solvent Recovery Plant' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$i.'.</td>
                        <td style="width: 20%;">'.$row['entry_by'].'</td>
                        <td style="width: 20%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 20%;">'.$row['floor'].'</td>
                        <td style="width: 20%;">'.$row['check_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Admin Block Log.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadProductionPlant") {
        $_GET['filename'] = 'Admin Block Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;">Sr.</td>
                    <td style="width: 20%;">Entry By	</td>
                    <td style="width: 20%;">Entry Date	</td>
                    <td style="width: 20%;">Floor</td>
                    <td style="width: 20%;">Check By	</td>
                    
                </tr>
            </thead>';
            $i=1;
        $sql="SELECT * FROM premises_preventive WHERE area='Production Plant' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$i.'.</td>
                        <td style="width: 20%;">'.$row['entry_by'].'</td>
                        <td style="width: 20%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 20%;">'.$row['floor'].'</td>
                        <td style="width: 20%;">'.$row['check_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Admin Block Log.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadEtpRecord") {
        $_GET['filename'] = 'Etp Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;">Sr.</td>
                    <td style="width: 20%;">Entry By	</td>
                    <td style="width: 20%;">Entry Date	</td>
                    <td style="width: 20%;">Floor</td>
                    <td style="width: 20%;">Check By	</td>
                    
                </tr>
            </thead>';
            $i=1;
       $sql="SELECT * FROM premises_preventive WHERE area='Effluent Treatment Plant' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
         $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$i.'.</td>
                        <td style="width: 20%;">'.$row['entry_by'].'</td>
                        <td style="width: 20%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 20%;">'.$row['floor'].'</td>
                        <td style="width: 20%;">'.$row['check_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Admin Block Log.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadAdminRecord") {
        $_GET['filename'] = 'Admin Block Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;">Sr.</td>
                    <td style="width: 20%;">Entry By	</td>
                    <td style="width: 20%;">Entry Date	</td>
                    <td style="width: 20%;">Floor</td>
                    <td style="width: 20%;">Check By	</td>
                    
                </tr>
            </thead>';
        $sql="SELECT * FROM premises_preventive WHERE area='Administration block' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 20%;">'.$i.'.</td>
                        <td style="width: 20%;">'.$row['entry_by'].'</td>
                        <td style="width: 20%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                        <td style="width: 20%;">'.$row['floor'].'</td>
                        <td style="width: 20%;">'.$row['check_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Admin Block Log.pdf', 'I');
    }else if ($_GET["type"] == "downloadCanteenRecord") {
        $_GET['filename'] = 'Canteen Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 25%;">Entry By</td>
                    <td style="width: 25%;">Entry Date</td>
                    <td style="width: 25%;">Floor</td>
                    <td style="width: 20%;">Check By</td>
                </tr>
            </thead>';
        $sql="SELECT * FROM premises_preventive WHERE area='Canteen' AND DATE(entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  ";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'.</td>
                        <td style="width: 25%;">'.$row['entry_by'].'</td>
                        <td style="width: 25%;">'.$row['entry_date'].'</td>
                        <td style="width: 25%;">'.$row['floor'].'</td>
                        <td style="width: 20%;">'.$row['check_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Canteen Log.pdf', 'I');
    }
    
    
}

$conn->close();
?>