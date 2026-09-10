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

    if($_GET["type"] == "getEarthingPoints"){
        $output = Array();
        $sql = "SELECT * FROM earthing_points";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if($_GET["type"]=="saveEarthingTest"){
        $sql="INSERT INTO earthing_test(test_date,locations,done_by,check_by)VALUES('$entry_date' ,'".json_encode($input)."' , '','".$_GET["emp_id"]."')";
        if($conn->query($sql)){
           echo "{\"status\":\"success\"}";
        }else{
            echo "{\"status\":\"failed\",\"\error\":\"".$conn->error."\"}";
        }
    }else if($_GET["type"]=="getEarthingTests"){
        $output = Array();
        $sql = "SELECT * FROM earthing_test";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["locations"] = json_decode($row["locations"]);
                $locations = $row["locations"];
                for ($i = 0; $i < count($locations); $i++) {
                    $location = $locations[$i];
                    $sql1 = "SELECT firstname FROM employee WHERE emp_id='".$location->done_by."'";
                    $result1 = $conn->query($sql1);
                    while ($row1 = $result1->fetch_assoc()) {
                        $location->done_by = $row1["firstname"];
                    }
                    $locations[$i] = $location;
                }
                $row["locations"] = $locations;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "downloadEarthingPoints") {
        $_GET['filename'] = 'Earthing Points'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 10%;">Id No</td>
                    <td style="width: 80%;">Location</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM earthing_points";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 10%;">'.$row['id_no'].'</td>
                        <td style="width: 80%;">'.$row['location'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('EarthingPoints.pdf', 'I');
    }
     else if ($_GET["type"] == "downloadTestEarthingPoints") {
        $_GET['filename'] = 'Test Earthing Points'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 25%;">Sr.</td>
                    <td style="width: 25%;">Test Date</td>
                    <td style="width: 25%;">Test No</td>
                    <td style="width: 25%;">Check By</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM earthing_test";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 25%;">'.$i.'.</td>
                        <td style="width: 25%;">'.$row['test_date'].'</td>
                        <td style="width: 25%;">'.$row['test_no'].'</td>
                        <td style="width: 25%;">'.$row['check_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Test Earthing Points.pdf', 'I');
    }
    
}

$conn->close();
?>