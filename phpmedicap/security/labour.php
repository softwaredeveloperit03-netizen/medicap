<?php
    require '../db.php';
    require '../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    try{
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d H:i:s", $timestamp);
     $entry_date_1 = date("Y-m-d", $timestamp);
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
    
    if ($_GET["type"] == "getActiveLabours") {
        $output = Array();
        $sql = "SELECT * FROM labour WHERE labour_no NOT IN
        (SELECT labour_no FROM labour_attendance WHERE DATE(entry_date)=CURDATE()) ORDER BY labour_name DESC";
        // $sql = "SELECT * FROM labour WHERE status='approve' AND labour_no NOT IN
        // (SELECT labour_no FROM labour_attendance WHERE DATE(entry_date)=CURDATE()) ORDER BY labour_name DESC";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"] == "labourEntry"){
        $time_in = $entry_date;
        if($input["entry_time"] != null){
           $time_in=$entry_date_1.' '.$input["entry_time"];
        }
      
        
        $sql = "INSERT INTO labour_attendance (plant_id,labour_no, in_time,entry_by, entry_date) VALUES 
        ('".$_GET["plant_id"]."','".$input["labour_id"]."','".$time_in."','".$_GET["emp_id"]."','".$entry_date."')";
       // echo $sql;
        if($conn->query($sql)){
             
            echo "{\"status\":\"success\"}";
        }
        else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if($_GET["type"]=="getTodaysLabors") {
        $output = Array();
       $sql = "SELECT a.id as entry_id,a.in_time,a.out_time, b.* FROM labour_attendance a join labour b on a.labour_no = b.labour_no 
       AND date(a.in_time)='".$entry_date_1."' AND a.status = 'pending' AND a.plant_id = '".$_GET["plant_id"]."' ";
    //   echo $sql;
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $sql1 = "SELECT * FROM labour WHERE labour_no='".$row["labour_id"]."'";
                $result1 = $conn->query($sql1);
                $output1 = Array();
                if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        $row["labour_no"] = $row1["labour_no"];
                        $row["labour_name"] = $row1["labour_name"];
                        $row["category"] = $row1["category"];
                        break;
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if($_GET["type"] == "exitLabour"){
        $sql = "UPDATE labour_attendance SET out_time='".$entry_date."', status = 'exit',
        out_by='".$_GET["emp_id"]."',out_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
      //  echo $sql;
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        }
        else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "downloadTodaysLaborsLog") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Labour Entry Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Labour Entry Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;">Labour No.</td>
                     <td style="width: 20%;">Labour Name</td>
                     <td style="width: 20%;">category</td>
                    <td style="width: 20%;">In Time</td>
                    <td style="width: 20%;">Out Time</td>
                   
                  
                </tr>
            </thead>';
            $i=1;
        //  $sql = "SELECT l.*,a.in_time,a.out_time FROM labour l left join labour_attendance a on l.user_no=a.user_no ";
               $sql = "SELECT a.id as entry_id,a.in_time,a.out_time, b.* FROM labour_attendance a join labour b on a.labour_no = b.labour_no  AND date(a.in_time)='".$entry_date_1."' ";

	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
                $html.='<tr>
                    

                <td style="width: 20%; ">'.$row['labour_no'].'</td>
                <td style="width: 20%; ">'.$row['labour_name'].'</td>
                <td style="width: 20%; ">'.$row['category'].'</td>
                <td style="width: 20%; ">'.$row['in_time'].'</td>
                <td style="width: 20%; ">'.$row['out_time'].'</td>
                
               
                </tr>';
                $i++;
            }
        }
         $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Labour Entry Log.pdf', 'I');
    }
   


} else {
    echo "{\"status\":\"invalid\"}";
}
  } catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
             
            }
$conn->close();
?>