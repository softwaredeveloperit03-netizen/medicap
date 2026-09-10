<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);


    require '../db.php';
    require '/tcpdf/tcpdf.php';
    require '../token.php';
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
    
    if ($_GET["type"] == "get_incoming_courier") {
        $output = Array();
        $sql = "SELECT * FROM outgoing_courier";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
        else if ($_GET["type"] == "outgoingdownload") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
 <table border="1" cellpadding="2">
 
                    <tr>
                        <td style="  width: 420px; font-size: 8;  text-align: left; "> DOCUMENT NAME :- PERSONAL HYGIENE REPORT</td>
                        <td style="  width: 320px; font-size: 8;  "> DOCUMENT NO :- SIPL/SOP/AD/01</td>

                    </tr>

                   <tr>
                        <td style="width: 15px; font-size: 7; text-align: center; ">sr</td>
                        <td style="width: 125px; font-size: 7; text-align: center; ">NAME</td>
                        <td style="width: 33px; font-size: 7; text-align: center; ">Clothing</td>
                        <td style="width: 40px; font-size: 7; text-align: center; ">Hairs</td>
                        <td style="width: 45px; font-size: 7; text-align: center; ">Nails</td>
                        <td style="width: 35px; font-size: 7; text-align: center; ">Jewelary</td>
                        <td style="width: 35px; font-size: 7; text-align: center; ">Cuts & woden</td>
                        <td style="width: 44px; font-size: 7; text-align: center; ">Beard</td>
                        <td style="width: 34px; font-size: 7; text-align: center; ">Body Clean</td>
                        <td style="width: 29px; font-size: 7; text-align: center; ">Teeth</td>
                        <td style="width: 39px; font-size: 7; text-align: center; ">Footware</td>
                        <td style="width: 45px; font-size: 7; text-align: center; ">Mask</td>
                        <td style="width: 45px; font-size: 7; text-align: center; ">Cap</td>
                        <td style="width: 60px; font-size: 7; text-align: center; ">Gloves</td>
                        <td style="width: 45px; font-size: 7; text-align: center; ">Appron</td>
                        <td style="width: 70px; font-size: 7; text-align: center; ">Overall Remark</td>
                   </tr>

';
         
            $sql = "SELECT p.*,e.firstname,e.middlename,e.lastname FROM personal_hygiene p 
        LEFT JOIN employee e ON p.emp_id = e.emp_id where p.plant_id = '".$_GET['plant_id']."'";
           $i=1;
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row = $result1->fetch_assoc()) {

                    $html.='<tr>
                         <td style="width: 15px; font-size: 7; ">'.$i.'</td>
                        <td style="width: 125px;font-size: 7; ">'.$row["firstname"].'</td>
                        <td style="width: 33px; font-size: 7; ">'.$row["clothing"].'</td>
                        <td style="width: 40px; font-size: 7; ">'.$row["hairs"].'</td>
                        <td style="width: 45px; font-size: 7; ">'.$row["nails"].'</td>
                        <td style="width: 35px; font-size: 7; ">'.$row["jewelry"].'</td>
                        <td style="width: 35px; font-size: 7; ">'.$row["cuts_wound"].'</td>
                        <td style="width: 44px; font-size: 7; ">'.$row["beard"].'</td>
                        <td style="width: 34px; font-size: 7; ">'.$row["body_clean"].'</td>
                        <td style="width: 29px; font-size: 7; ">'.$row["teeth"].'</td>
                        <td style="width: 39px; font-size: 7; ">'.$row["footware"].'</td>
                        <td style="width: 45px; font-size: 7; ">'.$row["mask"].'</td>
                        <td style="width: 45px; font-size: 7; ">'.$row["cap"].'</td>
                        <td style="width: 60px; font-size: 7; ">'.$row["gloves"].'</td>
                        <td style="width: 45px; font-size: 7; ">'.$row["appron"].'</td>
                        <td style="width: 70px; font-size: 7; ">'.$row["overall_remark"].'</td>

                   </tr>';
                    $i++;
                }
            } 
        
        $html.='</table>
                           <div></div>
      <table border="1" cellpadding="4">

                <tr>
                    <td style="width: 246px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 246px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 246px; font-size: 9;text-align: center;"> </td>

                </tr>
                <tr>
                    <td style="width: 246px; font-size: 9;text-align: center;"> HYGIENE SUPERVISOR</td>
                    <td style="width: 246px; font-size: 9;text-align: center;">QUALITY MANAGER</td>
                    <td style="width: 246px; font-size: 9;text-align: center;"> PLANT MANAGER</td>

                </tr>
         </table> ';
         
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Attaendence.pdf', 'I');
    }

    else if ($_GET["type"] == "outgoinglog") {
        $output = Array();
        $sql = "SELECT * FROM outgoing_courier";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 =  "SELECT * FROM outgoing_courier";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["trainer_name"] = $row1["trainer_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 =  "SELECT * FROM outgoing_courier";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM outgoing_courier";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            $row1["firstname"] = $row2["firstname"];
                                $row1["lastname"] = $row2["lastname"];
                                $row1["department"] = $row2["department"];
                                $row1["designation"] = $row2["designation"];
                            }
                        }
                        $output1[] = $row1;
                    }
                }
                $row["employees"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveIncoming") {
        $sql = "INSERT INTO incoming_courier(plant_id, date, party_name, courier_name, docket_no, receiver_name) VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["party_name"]."','".$input["courier_name"]."','".$input["docket_no"]."','".$input["receiver_name"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveOutgoing") {
        $sql = "INSERT INTO outgoing_courier(plant_id, date, party_name, description, department, courier_agency,docket_no) VALUES('".$_GET['plant_id']."','".$input["date"]."',
        '".$input["party_name"]."','".$input["description"]."','".$input["department"]."','".$input["courier_agency"]."','".$input["docket_no"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "get_outgoing_courier"){
        
        $output = Array();
        $sql = "SELECT * FROM outgoing_courier";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    
    }


}

$conn->close();
?>