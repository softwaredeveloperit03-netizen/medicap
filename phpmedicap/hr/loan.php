<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
    require '../db.php';
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
    
    if ($_GET["type"] == "save_emp_loan") {

            // Convert the input date to a timestamp
            $date_timestamp = strtotime($input['emiStartDate']);
            
            // Add the specified number of months to the input date
            $end_date_timestamp = strtotime("+" . $input['no_month'] . " months", $date_timestamp);
            
            // Format the end date as dd/mm/yyyy
            $end_date = date('Y-m-d', $end_date_timestamp);
                    
        $status1 = false;
        
        $sql = "INSERT INTO `emp_loan`( emi_schedules, `plant_id`, `emp_id`, `loan_amt`,`loan_bal`, `monthly_emi`,`emi_start_from`,
        `emi_end`,total_month,entry_date,entry_by) VALUES( '".json_encode($input["emi_schedules"])."','".$_GET["plant_id"]."',
        '".$_GET["emp"]."','".$input["principal"]."','".$input["principal"]."','".$input["emi"]."','".$input["emiStartDate"]."',
        '$end_date','".$input["no_month"]."','".$_GET["emp_id"]."','$entry_date') ";
        
    	if($conn->query($sql)){
    	    
    	    $product_id = $conn->insert_id;    
    	    $json_obj = json_encode($input["emi_schedules"]);
            $array = json_decode($json_obj, true);
                
                foreach ($array as $values)
                {
                     $sql = "INSERT INTO emi_schedules (emp_loan_id, emp_id, month, emiStartDate,principal,interest)
                    VALUES ('$product_id','".$_GET["emp"]."','".$values["month"]."','".$values["emiStartDate"]."',
                    '".$values["principal"]."','".$values["interest"]."')";
                    
                    if ($conn->query($sql)) {
                         $status1 = true;
                    } else {
                        $status1 = false;
                    }
                }
     
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        if ($status1){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
         
    
    } 
  else  if ($_GET["type"] == "update_save_emp_loan") {
        
         
        
        $sql = "update emp_loan set status='".$input["status"]."',approve_by='".$_GET["emp_id"]."' ,approve_date='$entry_date' where id='".$input["id"]."'  ";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    
    } 
    else    if ($_GET["type"] == "get_save_emp_loan") {
        
     $output = array();
        $sql = "select * from emp_loan  where emp_id ='".$_GET["employee"]."'";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
    
    }
    else    if ($_GET["type"] == "get_save_emp_loan_log") {
        
     $output = array();
        $sql = "select a.*,b.firstname from emp_loan a left join employee b on a.emp_id = b.emp_id AND a.plant_id = b.plant_id 
        where b.plant_id='".$_GET["plant_id"]."'";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                 
                 
        //           $output2 = array();
        //     $sql2 = "select a.*,b.firstname from emp_loan a left join employee b on a.emp_id = b.emp_id AND a.plant_id = b.plant_id where a.emp_id='".$row["emp_id"]."' and a.id ! ='".$row["id"]."' ";
        //   $output2 = array();
        //   $result2 = $conn->query($sql2);
        //      if ($result2->num_rows > 0) {
        //          while ($row2 = $result2->fetch_assoc()) {
        //                  $output2[] = $row2;
        //          }
        //      }
                    
                 
        //           $row["previous"] = $output2;
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
    
    }
    else    if ($_GET["type"] == "HOget_save_emp_loan_log") {
        
     $output = array();
        $sql = "select a.*,b.firstname from emp_loan a left join employee b on a.emp_id = b.emp_id AND a.plant_id = b.plant_id
         where b.plant_id='".$_GET["plantID"]."'";
       $output = array();
      $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             while ($row = $result->fetch_assoc()) {
                 
                 
        //           $output2 = array();
        //     $sql2 = "select a.*,b.firstname from emp_loan a left join employee b on a.emp_id = b.emp_id AND a.plant_id = b.plant_id where a.emp_id='".$row["emp_id"]."' and a.id ! ='".$row["id"]."' ";
        //   $output2 = array();
        //   $result2 = $conn->query($sql2);
        //      if ($result2->num_rows > 0) {
        //          while ($row2 = $result2->fetch_assoc()) {
        //                  $output2[] = $row2;
        //          }
        //      }
                    
                 
        //           $row["previous"] = $output2;
                $output[] = $row;
                }
            }
             
         
            echo json_encode($output);
    
    }
    else    if ($_GET["type"] == "loan_application") {
        
// 	    ini_set('display_errors', 1);
// error_reporting(E_ALL);
// echo('hi');
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= '
<table>
    <tr>
        <td style="width: 540; text-align:center;"><h2><strong>OLIVE HEALTHCARE <br> Mumbai</strong></h2></td>
    </tr>
   
    <br>

    <tr>
        <td style="width: 540; text-align:center;"><h3>Sub: Application for sanction of loan.</h3></td>
    </tr>
    <br>

    <tr>
        <td style="text-align:right; width:400;">Date:</td>
    </tr>    <br>

    <tr>
    <td style="width:540;">To,</td>
    </tr>

    <tr>
    <td style="width:540;">The Partner,</td>
    </tr>
    <tr>
    <td style="width:540;">Olive Healthcare <br>Mumbai</td>
    </tr><br>
    
    <br>

    <tr>
        <td style="width:540;">Dear Sir,</td>
    </tr>

    <br>

    <tr>
        <td style="width:540;">I, Mr./Miss./Mrs._______working as an_____since past_____years hereby request to grant me a loan of Rs. ______________Reason: _ _____________________
        I hereby give request you to deduct Rs______/- per month from my salary with effect from next month’s salary i.e. _____ 
        </td>
    </tr>
    <br>

    <tr>
        <td style="width:540;">My previous loan details are as under:</td>
    </tr><br>

    </table>
    <table border="1">
    <tr>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>
        <td style="width:75;text-align: left;"></td>

    </tr> 
    </table>

    <table>
    <tr>
        <td style="width:540;text-align: left;">______________</td>
    </tr> <br>
    <tr>
        <td style="width:540;text-align: left;">Loan Received by </td>
    </tr>    <br>

    <tr>
        <td style="width:540; text-align: left; font-weight: bold;">Sign: ________________</td>
        
    </tr> <br>
    <tr>
        <td style="width:540; text-align: left; font-weight: bold;">Name: ________________</td>
        
    </tr> <br>
    </table>';
         

         
       
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('loan_application.pdf', 'I');
	
	 
   
    }


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>