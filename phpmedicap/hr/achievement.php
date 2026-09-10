<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';

    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);

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
    
    if ($_GET["type"] == "getachievement") {
        $output = Array();
        $sql = "SELECT s.*, c.firstname, c.lastname FROM achievement s LEFT JOIN employee c ON c.emp_id = s.emp_id;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    if ($_GET["type"] == "getachievementMeha") {
        $output = Array();
        $sql = "SELECT s.*, c.firstname, c.lastname FROM achievement s LEFT JOIN employee c ON c.emp_id = s.emp_id where s.department='".$_GET["department"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "saveAchievement") {
            $sql = "INSERT INTO achievement (plant_id,emp_id,section, department, description, from_date, to_date, benifit, outcome) 
        VALUES ('".$_GET['plant_id']."','".$input["emp_id"]."', '".$input["section"]."', '".$input["department"]."', '".$input["description"]."', '".$input["from_date"]."', '".$input["to_date"]."', '".$_GET["benifit"]."', '".$_GET["outcome"]."' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "saveRinse") {
            $sql = "INSERT INTO rinse_analysis (plant_id,equip_name,batch_code,doa,dor) 
              VALUES ('".$_GET["plant_id"]."','".$input["equip_name"]."','".$input["batch_code"]."','".$input["doa"]."','".$input["dor"]."')";
           if($conn->query($sql)){
         $sql = "SELECT * FROM rinse_analysis order by id  desc limit 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {
                $id= $row["id"];
            }
        }
    
            foreach($input["analysislist"] as $Data) {
        		
        	 $sql="INSERT INTO rinse_analysis_dtl (micro_organism ,observation_control ,incubation_period,ra_id)
                                    value(
                                    '".$Data["micro_organism"]."',
                                          '".$Data["observation_control"]."',
                                          '".$Data["incubation_period"]."' ,'".$id."')";
        	 $conn->query($sql);
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }

else if ($_GET["type"] == "getRinse") {
        $output = Array();
        $sql = "SELECT * FROM rinse_analysis";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                              $output1 = Array();

                $sqlChkDtl = "select * from rinse_analysis_dtl where ra_id='".$row["id"]."'";
                $result1 = $conn->query($sqlChkDtl);
                while ($row1 = $result1->fetch_assoc()) {  
                      $output1[]  = $row1;
                }
                
                $row['analysislist'] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
     else if ($_GET["type"] == "getEnvironment") {
        $output = Array();
        $sql = "SELECT * FROM Environment";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveEnvironment") {
     echo   $sql = "INSERT INTO Environment (plant_id, incubation_start,sampling_date,medium_used, media_no, incubation_temp, incubation_id, incubation_end) 
        VALUES ('".$_GET['plant_id']."','".$input["incubation_start"]."','".$input["sampling_date"]."', '".$input["medium_used"]."', '".$input["media_no"]."', '".$input["incubation_temp"]."', '".$input["incubation_id"]."', '".$input["incubation_end"]."' )";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
     else if ($_GET["type"] == "getSwab") {
        $output = Array();
        $sql = "SELECT * FROM swab_analysis";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveSwab") {
        $sql = "INSERT INTO swab_analysis (plant_id,area_name,equipment_name, total_plate, total_fungal, checked_by) 
        VALUES ('".$_GET['plant_id']."','".$input["area_name"]."', '".$input["equipment_name"]."', '".$input["total_plate"]."', '".$input["total_fungal"]."', '".$input["checked_by"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
     else if ($_GET["type"] == "saveSample") {
         
         
        $sql = "INSERT INTO sample_request (plant_id, name_client, requirement, type, avd_country, grade, testing, unit, solvent_system, color, core_tablet, enter, equip_avail, expected, requried, recommeded) 
        VALUES ('".$_GET['plant_id']."','".$input["client_code"]."','".$input["requirement"]."','".$input["type"]."', '".$input["avd_country"]."','".$input["grade"]."', '".$input["testing"]."',
        '".$input["unit"]."','".$input["solvent_system"]."','".$input["color"]."','".$input["core_tablet"]."','".$input["enter"]."','".$input["equip_avail"]."','".$input["expected"]."','".$input["requried"]."','".$input["recommeded"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    } 
    
    else if ($_GET["type"] == "getSample") {
        $output = Array();
        $sql = "SELECT s.id,s.status,s.equip_avail,s.color,s.solvent_system,s.unit,s.testing,s.grade,s.avd_country,s.type,s.requirement,
        c.c_country as temp_country ,c.c_city as temp_address, c.email as temp_email , c.phone as temp_no ,c.LglNm , c.cr_address as address 
        FROM sample_request s left Join client c On 
        c.client_code = s.name_client where s.plant_id='" . $_GET["plant_id"] . "' AND s.status = 'Pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getSampleLog") {
        $output = Array();
        $sql = "SELECT s.id,s.status,s.equip_avail,s.color,s.solvent_system,s.unit,s.testing,s.grade,s.avd_country,s.type,s.requirement,
        c.c_country as temp_country ,c.c_city as temp_address, c.email as temp_email , c.phone as temp_no ,c.LglNm , c.cr_address as address FROM sample_request s left Join client c On 
        c.client_code = s.name_client where s.plant_id='" . $_GET["plant_id"] . "'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "updateApprove") {

        $sql = "UPDATE sample_request SET status='Approve'  WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
     else if ($_GET["type"] == "updateReject") {

        $sql = "UPDATE sample_request SET status='Reject'  WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
    else if ($_GET["type"] == "get__approve"){
        
	$output = Array();
	
    	$sql = "select * from sample_request where status='Approve' ";
    	
    	$result = $conn->query($sql);
    	
    	if($result->num_rows > 0)
    	{
    		while($row = $result->fetch_assoc())
    		{
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

    }
     else if ($_GET["type"] == "getDmf") {
        $output = Array();
        $sql = "SELECT * FROM dossier_dmf";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDmf") {
        $sql = "INSERT INTO dossier_dmf (plant_id,request,product_name, material_name, country, product_comp, dossage_form, for_client, client_name, client_country, urgency,sample_req, qty, unit, person_name) 
        VALUES ('".$_GET['plant_id']."','".$input["request"]."', '".$input["product_name"]."', '".$input["material_name"]."', '".$input["country"]."', '".$input["product_comp"]."',
        '".$input["dossage_form"]."','".$input["for_client"]."', '".$input["client_name"]."', '".$input["client_country"]."', '".$input["urgency"]."' , '".$input["sample_req"]."', '".$input["qty"]."', '".$input["unit"]."', '".$input["person_name"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }  else if ($_GET["type"] == "rejectdmf"){
       
         $sql = "UPDATE dossier_dmf SET status='Rejected'  WHERE id='" . $_GET["id"] . "'";
      
        $conn->query($sql);

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }else if ($_GET["type"] == "approvedmf"){

        $sql = "UPDATE dossier_dmf SET status='Approved'  WHERE id='" . $_GET["id"] . "'";

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
    }
     else if ($_GET["type"] == "get_dossier_dmf"){
        
	$output = Array();
	
    	$sql = "select * from dossier_dmf where status='Approved' OR  status='Rejected'";
    	
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
	
	echo json_encode($output);

    }
    else if($_GET['type'] == 'downloadachivment') {
        $_GET['filename'] = 'Reraining Schedule Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <table cellpadding="3">
            <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="width:5%;">Sr.</td>
                 <td style="width:25%">	Employee Code</td>
                <td style="width:27%">	Department</td>
                  <td style="width:23%">Start Date</td>
                <td style="width:20%">	End Date</td>
              </tr>';
            $sql = "SELECT s.*, c.firstname, c.lastname FROM achievement s LEFT JOIN employee c ON c.emp_id = s.emp_id;";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                    $result1 = $conn->query($sql);
                    $row = $result1->fetch_assoc();
                    $html.='
                    <tr>
                        <td>'.$counter++.'</td>
                         <td>'.$row["emp_id"].'</td>
                         <td>'.$row["section"].'</td>
                         <td>'.date('d-m-Y',strtotime($row['from_date'])).'</td>  
                         <td>'.date('d-m-Y',strtotime($row['to_date'])).'</td>  
                       </tr>';
                }
            }
            $html.='</table><div></div>';

            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Retrainingneeds.pdf', 'I');
    }
    

}

$conn->close();
?>