<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('response_token: test123456');

  
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

$output = Array();
$token = $_GET["token"];
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
    
    if($_GET["type"] == "getEquipmentNames") {
        $output = Array();
        $sql = "SELECT equipment_name FROM equipment_names";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 else if ($_GET["type"] == "getequiptmentBatchNuber") {
        
        $output = Array();
      $sql = "SELECT equipment_name FROM equipment ";
        if($result = $conn->query($sql))
        {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                
                $output[] = $row;
            }
        }
        }
        echo json_encode($output);
    
        
    
        
    }


    else if($_GET["type"] == "getEquipmentNames1") {
        $output = Array();
        $sql = "SELECT equipment_name,equipment_code FROM equipment";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "saveEquipment") {
        
       $sql = "INSERT INTO equipment (plant_id,equipment_category,equipment_type,equipment_name,serial_no,make,model,department,location,least_count,
       capacity_applicable,capacity,from_range,to_range,unit,purchase_date,installation_date,preventive_maintenance,calibration_required,reqv_required,
       description,qualification_status,entry_by,entry_date,status) VALUES ('".$_GET["plant_id"]."','".$input["equipment_category"]."','".$input["equipment_type"]."',
       '".$input["equipment_name"]."','".$input["serial_no"]."', '".$input["make"]."', '".$input["model"]."', '".$input["department"]."',
       '".$input["location"]."', '".$input["least_count"]."', '".$input["capacity_applicable"]."', '".$input["capacity"]."', '".$input["from_range"]."',
       '".$input["to_range"]."', '".$input["unit"]."', '".$input["purchase_date"]."', '".$input["installation_date"]."','".$input["preventive_maintenance"]."',
       '".$input["calibration_required"]."','".$input["reqv_required"]."','".$input["description"]."', '".$input["qualification_status"]."','".$_GET["emp_id"]."',
       '".$entry_date."','Pending')";
       
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        }  else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    else if($_GET["type"] == "get_std_weight") {
        $output = Array();
      
        $sql = "SELECT * FROM equipment_standard_weight a LEFT JOIN equipment b on b.id=a.equipment_id WHERE b.id='".$_GET["id"]."'"; 
     
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getEquipments") {
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE plant_id='".$_GET["plant_id"]."' AND equipment_category != 'New Purchase'  ORDER BY id DESC"; 
      
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){

                $output2 = Array();  
                $sql2 = "SELECT material_code , material_name FROM others_material  where equipment =  '".$row["equipment_code"]."' ";
                $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
                    while($row2 = $result2->fetch_assoc()){
                        $output2[] = $row2;
                    }
                }
                
                $output21 = Array();  
                $sql21 = "SELECT * FROM equipment_status_history  where equipment_id =  '".$row["id"]."' ";
                $result21 = $conn->query($sql21);
                if($result21->num_rows > 0){
                    while($row21 = $result21->fetch_assoc()){
                        $output21[] = $row21;
                    }
                }
                
                $sql2 = "SELECT 
                       (SELECT CONCAT(firstname, ' ', lastname, '  ( ', emp_id, ' )') AS entryByName FROM employee  where emp_id = '".$row['entry_by']."' LIMIT 1) AS entryByName,
                       (SELECT CONCAT(firstname, ' ', lastname, '  ( ', emp_id, ' )') AS approvedByName FROM employee  where emp_id = '".$row['approve_by']."' LIMIT 1) AS approvedByName
                       FROM dual"; 
        
                $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
                    while($row2 = $result2->fetch_assoc()){
                        $row['entryByName'] = $row2['entryByName'] ;
                        $row['approvedByName'] = $row2['approvedByName'] ;
                    }
                }
                
                $row['parts_data'] = $output2;
                $row['status_changed_history'] = $output21;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
 


    else if($_GET["type"]=="changeEquipmentStatus") {
        
        $sql="UPDATE equipment SET status1='".$input["change_status"]."',status= 'Status Changed' ,status_change_date ='".$input["status_change_date"]."' , 
        change_status_remark = '".$input["change_status_remark"]."' , status_change_by='".$_GET["emp_id"]."', status_change_on='$entry_date'   
        WHERE id='".$_GET["id"]."' ";
        
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
            
            $sql2 = "INSERT INTO equipment_status_history (plant_id,equipment_id,change_status,status_change_date,change_status_remark,status_change_by,status_change_on)
            VALUE('".$_GET["plant_id"]."','".$_GET["id"]."','".$input["change_status"]."','".$input["status_change_date"]."','".$input["change_status_remark"]."',
            '".$_GET["emp_id"]."','$entry_date')";
            
            $conn->query($sql2);
            
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if($_GET["type"] == "getequipment_type_data") {
        $output = Array();
        $sql = "SELECT * FROM equipment_types where plant_id = '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 


    else if($_GET["type"] == "get_eqp_for_qualification") {
        $output = Array();
        $plant=$_GET["plant_id"];
       
          $sql = "SELECT * FROM equipment WHERE equipment_category = 'New' AND plant_id='".$_GET["plant_id"]."' ORDER BY id DESC"; 
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $row["calibration_frequency_inhouse"] = json_decode($row["calibration_frequency_inhouse"]);
                $row["weights"] = json_decode($row["weights"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "searcheqp") {
        $output = Array();
        $plant=$_GET[plant_id];
        if($plant==0){
        $sql = "SELECT * FROM equipment  ORDER BY id*1 DESC";
        }else{
          $sql = "SELECT * FROM equipment WHERE plant_id='".$_GET["plant_id"]."' AND (department LIKE '%".$_GET["value"]."%' OR equipment_name LIKE '%".$_GET["value"]."%' OR 
          equipment_code LIKE '%".$_GET["value"]."%' ) ORDER BY id*1 DESC"; 
        }
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $row["calibration_frequency_inhouse"] = json_decode($row["calibration_frequency_inhouse"]);
                $row["weights"] = json_decode($row["weights"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if($_GET["type"] == "getPreventEquipments") {
        $output = Array();
         $sql = "SELECT * FROM equipment where preventive_maintenance = 'Applicable' and plant_id='".$_GET["plant_id"]."' ORDER BY 1 DESC "; 
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row['inpection_freequency'] = json_decode($row['inpection_freequency']);
                $row['prev_maint_frequency'] = json_decode($row['prev_maint_frequency']);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getEquipmentsforapproval") {
        $output = Array();
     
        $sql = "SELECT * FROM equipment WHERE plant_id='".$_GET["plant_id"]."' AND ( status = 'Pending' OR status = 'Status Changed' ) AND equipment_category != 'New Purchase' ORDER BY id DESC"; 
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                        
                $sql2 = "SELECT 
                       (SELECT CONCAT(firstname, ' ', lastname, '  ( ', emp_id, ' )') AS entryByName FROM employee  where emp_id = '".$row['entry_by']."' LIMIT 1) AS entryByName,
                       (SELECT CONCAT(firstname, ' ', lastname, '  ( ', emp_id, ' )') AS approvedByName FROM employee  where emp_id = '".$row['approve_by']."' LIMIT 1) AS approvedByName
                       FROM dual"; 
        
                $result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
                    while($row2 = $result2->fetch_assoc()){
                        $row['entryByName'] = $row2['entryByName'] ;
                        $row['approvedByName'] = $row2['approvedByName'] ;
                    }
                }
                
                
                $output[] = $row;
            }
        }
        
        echo json_encode($output);
    }

    else if($_GET["type"]=="update_equipment_status") {
        
        $sql="UPDATE equipment SET status='Active',approve_by='".$_GET["emp_id"]."', approve_date='$entry_date'  WHERE id='".$_GET["id"]."' ";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    }
    else if($_GET["type"]=="save_equipment_type") {
         $sql="insert into equipment_types(plant_id,equipment_type) values('".$_GET["plant_id"]."','".$input["equipment_type"]."') ";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    
    else if($_GET["type"] == "getallequipment") {
        $output = Array();

        $sql = "SELECT department,description,serial_no,entry_date,equipment_code,equipment_name,
        location,sop_no,relese_date,decommi_date FROM equipment WHERE plant_id='".$_GET["plant_id"]."'  ORDER BY id DESC"; 
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row['parent_code'] = 'N/A';
                $row['address'] = 'N/A';
                $output[] = $row;
            }
        }
 
  
        $output1 = Array(); 
        $sql1 = "SELECT o.material_name as equipment_name, o.material_code as equipment_code,o.part_no as serial_no ,e.equipment_code as parent_code,
        e.location,e.sop_no,e.department FROM others_material o left join equipment e ON o.equipment = e.equipment_code WHERE o.plant_id='".$_GET["plant_id"]."' AND o.equipment LIKE 'A%' "; 
        $result1 = $conn->query($sql1);
        if($result1->num_rows > 0){
            while($row1 = $result1->fetch_assoc()){
                
                $row1['decommi_date'] = 'N/A';
                 $row1['relese_date'] = 'N/A';
                $row1['address'] = 'N/A';
              
                $output1[] = $row1;
            }
        }
        
        $output_merged = array_merge($output, $output1);

         
        echo json_encode($output_merged);
    }


    else if($_GET["type"]=="editEquipment") {
        
       $sql = "UPDATE equipment SET equipment_type = '".$input['equipment_type']."', equipment_name = '".$input['equipment_name']."', 
       serial_no = '".$input['serial_no']."', make = '".$input['make']."', model = '".$input['model']."',department = '".$input['department']."', 
       location = '".$input['location']."', least_count = '".$input['least_count']."', capacity_applicable = '".$input['capacity_applicable']."', 
       capacity = '".$input['capacity']."', from_range = '".$input['from_range']."',to_range = '".$input['to_range']."', unit = '".$input['unit']."', 
       purchase_date = '".$input['purchase_date']."', installation_date = '".$input['installation_date']."', 
       preventive_maintenance = '".$input['preventive_maintenance']."',calibration_required = '".$input['calibration_required']."', 
       reqv_required = '".$input['reqv_required']."', description = '".$input['description']."', qualification_status = '".$input['qualification_status']."' 
       status = 'Pending' WHERE id='".$input["id"]."' ";
         
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
     else if($_GET["type"]=="update_equipment_frequency") {
        $sql="SELECT * FROM equipment_maintenance WHERE equipment_id= '".$input["id"]."' ";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Frequency Already Set and Schedule prepared for this Equipment.\"}";
        }
        else {
        
            $sql="UPDATE equipment SET inpection_freequency='".json_encode($input["inspection"])."',
            prev_maint_frequency='".json_encode($input["preventive"])."'
            WHERE id='".$input["id"]."' ";
                if($conn->query($sql)){  
                     echo "{\"status\":\"success\"}";
                } else {
                     echo "{\"status\":\"".$conn->error."\"}";
                }
        }
    }
    else if($_GET["type"]=="update_equipment_schedule") {
        $sql="SELECT * FROM equipment_maintenance WHERE equipment_id= '".$input["id"]."' ";
        $result =$conn->query($sql);
        if ($result->num_rows > 0) {
          	echo "{\"status\":\"Schedule Already Prepared for this Equipment.\"}";
        }
        else {
        $sql="UPDATE equipment SET inpection_freequency='".json_encode($input["inspection"])."',
        prev_maint_frequency='".json_encode($input["preventive"])."'
        WHERE id='".$input["id"]."' ";
         
        if($conn->query($sql)){
            $insp_array = $input["inspection"];
            $sql1='';
            $sql1 = "insert into equipment_maintenance(equipment_id,due_date,due_type,frequency) values";
            foreach ($insp_array as $value) {
             //   echo $value['particular'];
                if(trim($value['particular']) =='Daily'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<365;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 1 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Daily'),";
                    }
                }else if(trim($value['particular']) =='Weekly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<52;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 7 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Weekly'),";
                    }
                }else if(trim($value['particular']) =='FortNightly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<26;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 15 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Daily'),";
                    }
                }else if(trim($value['particular']) =='Monthly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<12;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 30 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Monthly'),";
                    }
                }else if(trim($value['particular']) =='Quarterly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<4;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 90 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Quarterly'),";
                    }
                }else if(trim($value['particular']) =='Half-Yearly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<2;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 180 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Half-Yearly'),";
                    }
                }else if(trim($value['particular']) =='Annually'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<1;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 364 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Inspection','Annually'),";
                    }
                }
            }
             $prevent_array = $input["preventive"];
            foreach ($prevent_array as $value) {
              //  echo $value['particular'];
                if(trim($value['particular']) =='Daily'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<365;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 1 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Daily'),";
                    }
                }else if(trim($value['particular']) =='Weekly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<52;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 7 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Weekly'),";
                    }
                }else if(trim($value['particular']) =='FortNightly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<26;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 15 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Daily'),";
                    }
                }else if(trim($value['particular']) =='Monthly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<12;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 30 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Monthly'),";
                    }
                }else if(trim($value['particular']) =='Quarterly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<4;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 90 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Quarterly'),";
                    }
                }else if(trim($value['particular']) =='Half-Yearly'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<2;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 180 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Half-Yearly'),";
                    }
                }else if(trim($value['particular']) =='Annually'){
                    $date = $value['last_insp_date'];
                    for($i=0; $i<1;$i++){
                          $date = date('Y-m-d', strtotime($date. ' + 364 days'));  
                          $sql1 =$sql1."('".$input["id"]."','".$date."','Preventive','Annually'),";
                    }
                }
            }
            $sql1= rtrim($sql1, ',');
           // echo $sql1;
            $conn->query($sql1);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
            
        }
    }
     else if($_GET["type"]=="update_last_dates") {
        $sql="UPDATE equipment SET last_inspection_date='".$input["last_inspection_date"]."',last_prev_maint_date='".$input["last_prev_maint_date"]."'
        WHERE id='".$input["id"]."' ";
      //  echo $sql;
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if($_GET["type"]=="ObsoluteEquipment") {
        $sql="UPDATE equipment SET status='OBSOLUTE' WHERE id='".$_GET["id"]."' ";
        if($conn->query($sql)){
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "deleteEquipment") {
        $sql = "UPDATE equipment SET status='Deleted' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
       
        }
    }else if ($_GET["type"] == "getSectionsUnderDepartment") {
        $output = Array();
        $sql = "SELECT max(id) as id, department FROM section GROUP by department ORDER by department";
         $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
            $output1 = Array();
            $sql1 = "SELECT max(id) as id, section_name FROM section where department='".$row['department']."' GROUP by section_name ORDER by section_name";
            $result1 = $conn->query($sql1);
            if($result1->num_rows > 0){
                while($row1 = $result1->fetch_assoc()){
                 $output1[]=$row1;
                    
                }
                $row['sections'] = $output1;
            }
            $output[] = $row;  
        }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "get_monthly_schedule") {
        $output = Array();
        $sql = "SELECT a.id,a.frequency,a.due_date, CONCAT(b.equipment_code, '-', b.equipment_name,'-',a.frequency) as equipment_name
         FROM equipment_maintenance a JOIN equipment b on a.equipment_id= b.id WHERE month(due_date)='".$_GET["month"]."' and year(due_date)='".$_GET["year"]."'
         and a.due_type = '".$_GET["rpt_type"]."' order by due_date"; 
        $result = $conn->query($sql);
    
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
     
    else if ($_GET["type"] == "downloadEquipmentsView") {
        $_GET['filename'] = 'Equipments '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $output = Array();
        $sql = "SELECT * FROM equipment WHERE id='".$_GET["id"]."' ";
        $result = $conn->query($sql);
        $i=1;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row["calibration_frequency"] = json_decode($row["calibration_frequency"]);
                $calibrations=$row["calibration_frequency"];
                $row["weights"] = json_decode($row["weights"]);
                $weights=$row["weights"];
        $html.='
        <h2 style="text-align:center">Equipments</h2>
        <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:25%;"><b>Equipment Code:</b></td>
                        <td style="width:75%;">'.$row['equipment_code'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>Equipment Name:</b></td>
                        <td style="width:25%;">'.$row['equipment_name'].'</td>
                        <td style="width:25%;"><b>Max.Capacity:</b></td>
                        <td style="width:25%;">'.$row['capacity'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>Make:</b></td>
                        <td style="width:25%;">'.$row['make'].'</td>
                        <td style="width:25%;"><b>Capacity Unit:</b></td>
                        <td style="width:25%;">'.$row['unit'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>Type:</b></td>
                        <td style="width:25%;">'.$row['type'].'</td>
                        <td style="width:25%;"><b>Department:</b></td>
                        <td style="width:25%;">'.$row['department'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>Location:</b></td>
                        <td style="width:25%;">'.$row['location'].'</td>
                        <td style="width:25%;"><b>Model:</b></td>
                        <td style="width:25%;">'.$row['model'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>Purchase Date:</b></td>
                        <td style="width:25%;">'.$row['purchase_date'].'</td>
                        <td style="width:25%;"><b>Installation Date:</b></td>
                        <td style="width:25%;">'.$row['installation_date'].'</td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>Calibration:</b></td>
                        <td style="width:75%;">'.$row['calibration'].'</td>
                    </tr>
                </table>
                <div></div>
                <h3>Calibration Frequency:</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:50%;"><b>Sr No</b></td>
                        <td style="width:50%;"><b>Name</b></td>
                    </tr>';
                    $j=1;
                    for($i=0;$i<count($calibrations);$i++){
                      $calibration=$calibrations[$i];  
                    
            $html.='<tr>
                        <td style="width:50%;">'.$j++.'</td>
                        <td style="width:50%;">'.$calibration.'</td>
                    </tr>';
                    }
        $html.='</table>
                <div></div>';
               
         $html.='<h3>Working Range:</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <td style="width:25%;"><b>From Range:</b></td>
                        <td style="width:25%;">'.$row['from_range'].'</td>
                        <td style="width:25%;"><b>From range Uom:</b></td>
                        <td style="width:25%;"></td>
                    </tr>
                    <tr>
                        <td style="width:25%;"><b>To range :</b></td>
                        <td style="width:25%;">'.$row['to_range'].'</td>
                        <td style="width:25%;"><b>To Range Uom:</b></td>
                        <td style="width:25%;"></td>
                    </tr>    
                </table>';
            }
        }

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('equipmentlog.pdf', 'I');
        
    }
    else if ($_GET["type"] == "downloadEquipmentsLog") {
         $_GET['filename'] = 'Equipment List'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Equipment List</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 20%;">Depatment</td>
                    <td style="width: 20%;">Equipment Code</td>
                    <td style="width: 20%;">Equipment Name	</td>
                    <td style="width: 20%;">Capacity</td>
                    <td style="width: 10%;">Unit</td>

                </tr>
            </thead>';
          $sql = "SELECT * FROM equipment WHERE status='approve' AND department LIKE '%".$_GET["department_name"]."%' AND equipment_name LIKE '%".$_GET["equipment_name"]."%' AND plant_name LIKE '%".$_GET["plant_name"]."%' ORDER BY id*1 DESC";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            $i=1;
            while($row = $result->fetch_assoc()){
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$i.'.</td>
                        <td style="width: 20%;">'.$row['department'].'</td>
                        <td style="width: 20%;">'.$row['equipment_code'].'</td>
                       <td style="width: 20%;">'.$row['equipment_name'].'</td>
                       <td style="width: 20%;">'.$row['capacity'].'</td>
                       <td style="width: 10%;">'.$row['unit'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('EquipmentsLog.pdf', 'I');
    } 

}

$conn->close();
?>