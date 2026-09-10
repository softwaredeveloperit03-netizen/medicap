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

    if($_GET["type"] == "saveGaussTest"){
        $sql="INSERT INTO gaussmeter_test(plant_name,equipment_code ,test_date,due_date ,details ,entry_by)VALUES('".$input["plant_name"]."' , '".$input["equipment_code"]."' ,'".$input["test_date"]."' ,'".$input["due_date"]."' ,'".json_encode($input["details"])."' ,'".$_GET["emp_id"]."')";
          if($conn->query($sql)){
           echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"failed\",\"\error\":\"".$conn->error."\"}";
        }
        
    }else if($_GET["type"] == "getGaussTest"){
        $output = Array();
        $sql="SELECT * FROM gaussmeter_test WHERE status='APPROVE' ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"] == "saveTestReport"){
       $sql="INSERT INTO gaussmeter_test(plant_name,equipment_code,rod_id,magnetic_field ,entry_by ,entry_date)VALUES('".$input["plant_name"]."' ,'".$input["equipment_code"]."' ,'".$input["rod_id"]."' ,'".$input["magnetic_field"]."' ,'".$_GET["emp_id"]."' ,'".$entry_date."')";
        if($conn->query($sql)){
           echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"failed\",\"\error\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"] == "getTest"){
        $output = Array();
        $sql="SELECT * FROM gaussmeter_test ";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadTest") {
        $_GET['filename'] = 'Test Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 15%;">Plant Name</td>
                    <td style="width: 15%;">Equipment Code</td>
                    <td style="width: 15%;">ROD Id</td>
                    <td style="width: 15%;">Magnetic Field In gauss</td>
                    <td style="width: 15%;">Done By(Eng.Dept)</td>
                    <td style="width: 15%;">Check By(User.Dept)</td>
                </tr>
            </thead>';
        $sql="SELECT * FROM gaussmeter_test ";
        $result = $conn->query($sql);
        $i=1;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $html.='<tr nobr="true">
                    <td style="width: 10%;">'.$i.'.</td>
                    <td style="width: 15%;">'.$row['plant_name'].'</td>
                    <td style="width: 15%;">'.$row['equipment_code'].'</td>
                    <td style="width: 15%;">'.$row['rod_id'].'</td>
                    <td style="width: 15%;">'.$row['magnetic_field'].'</td>
                    <td style="width: 15%;">'.$row['entry_by'].'</td>
                    <td style="width: 15%;">'.$row['check_by'].'</td>
                </tr>';
            $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Test Record.pdf', 'I');
    }

    
}

$conn->close();
?>