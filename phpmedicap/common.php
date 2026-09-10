<?php
// require 'authMiddleware.php';

function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } else if (is_string($mixed)) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
        }
        return utf8_encode($mixed);
    }
    return $mixed;
}

function safe_json_echo($data) {
    header('Content-Type: application/json; charset=UTF-8');
    $data = utf8ize($data);
    $flags = JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    $json = json_encode($data, $flags);
    echo ($json === false) ? '[]' : $json;
}
require 'db.php';
require 'token.php';

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);
 
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }

  
    switch($_GET["type"]) {
         case "get_mobile_app_master_data":
            // $output = Array();
           // $sql = "SELECT department_code as Key_Id ,department_name as Key_Name  FROM department order by department_name";
               $output = Array();
            $sql = "SELECT department_code as Key_Id ,department_name as Key_Name  FROM department order by department_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
             $data["departments"] = $output;
             
               $output = Array();
            $sql = "SELECT id as Key_Id ,designation as Key_Name  FROM designation order by designation";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["designations"] = $output;
            
            $output = Array();
            $sql ="select 1 as Key_Id, 'Clients' as Key_Name union ALL select 2 as Key_Id, 'Vendors' as Key_Name union ALL select 3 as Key_Id, 'Visitors' as Key_Name union ALL select 4 as Key_Id, 'Contractors' as Key_Name" ;
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["categories"] = $output;
            
            
            $output = Array();
            $sql ="select 1 as Key_Id, 'By Courier' as Key_Name union ALL select 2 as Key_Id, 'By Rail' as Key_Name union ALL select 3 as Key_Id, 'By Air' as Key_Name union ALL select 4 as Key_Id, 'By Road' as Key_Name union ALL select 4 as Key_Id, 'Inhand' as Key_Name" ;
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["transport_types"] = $output;
            
            $output = Array();
            $sql ="select 1 as Key_Id, 'Water Extinguisher' as Key_Name union ALL select 2 as Key_Id, 'Foam Extinguisher' union ALL select 3 as Key_Id, 'Powder Extinguisher' as Key_Name union ALL select 4 as Key_Id, 'CO2 Extinguisher' as Key_Name union ALL select 5 as Key_Id, 'Wet Chemical Extinguisher' as Key_Name " ;
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["extinguisher_types"] = $output;
            
            
             $output = Array();
            $sql ="select 1 as Key_Id, 'Bus' as Key_Name union ALL select 2 as Key_Id, 'Car' as Key_Name union ALL select 3 as Key_Id, 'Bike' as Key_Name " ;
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["vehicle_types"] = $output;
            
            
            $output = Array();
            $sql ="select Id as Key_Id, transport_company  as Key_Name from transport_master order by transport_company" ;
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["transporters"] = $output;
            
            $output = Array();
             $sql = "SELECT vendor_no as Key_Id,vendor_name as Key_Name  FROM vendor v  WHERE v.status='Approved' ORDER BY v.vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);

                    $output[] = $row;
                }
            }
             $data["vendors"] = $output;
             
              $output = Array();
             $sql = "SELECT Id as Key_Id,card_number as Key_Name  FROM visitor_card_tags";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);

                    $output[] = $row;
                }
            }
             $data["visitor_card_tags"] = $output;
             
             $output = Array();
             $sql = "SELECT emp_id as Key_Id,CONCAT(`firstname`, ' ', `lastname`) as Key_Name,department as filter_key ,
                    designation as filter_key_one
                    from employee order by department,firstname";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);

                    $output[] = $row;
                }
            }
             $data["employee"] = $output;
             $output = Array();
             $sql = "select Id as Key_Id,Country as Key_Name from country  order by country";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);

                    $output[] = $row;
                }
            }
             $data["country_master"] = $output;
             
            $output = Array();
            $sql = "select state_code as Key_Id,state_name as Key_Name,Country as filter_key from state_master order by State_Name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);

                    $output[] = $row;
                }
            }
             $data["state_master"] = $output;
             
             $output = Array();
             $sql = "select Distinct Id as Key_Id,City as Key_Name,State_Name as filter_key from city order by State_Name,City";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);

                    $output[] = $row;
                }
            }
             $data["city_master"] = $output;
             
              $output = Array();
             $sql = "select Distinct Id as Key_Id,qualification as Key_Name from qualification where status='Active' order by qualification";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);

                    $output[] = $row;
                }
            }
             $data["qualifications"] = $output;
            
            echo json_encode($data);
            break;
                    case "getMaterialsByTypeBOM":
            $output = Array();
            $a=$_GET["material_type"];
             $a;
                
           if($_GET["material_subtype"]=='Consumables')  {
 
                $sql = "select * from others_material where material_type='Manufacturing Materials' and material_subtype='Consumables'";
           }
    else if(empty($_GET["material_type"]) || $_GET["material_type"]=="Raw Material" || $_GET["material_type"]=="Packing Material") {

            $sql = "SELECT m.*,v.vendor_name  FROM material m left join vendor v ON v.vendor_no = m.vendor_no WHERE m.plant_id='".$_GET["plant_id"]."' 
            AND m.material_subtype like '".$_GET["material_subtype"]."%' ";
    }
//     else{
//         $sql = "select * from others_material where material_type='Manufacturing Material' and material_subtype='Consumables'";
   
//     }
               
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    
                    if($row["equivalent"] !=Null && $row['grade'] !=Null){
                    $row["equivalent"] = json_decode($row["equivalent"]);
                    
                    
             
                    }
                    
                     $output2 = Array();
                        $sql1 = "SELECT a.supplier_code,b.vendor_name FROM mst_vendor_materials a left join  vendor b on a.supplier_code=b.vendor_no where a.material_code='".$row["material_code"]."' ";
                           $result2 = $conn->query($sql1);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                    $row['vendorList'] = $output2; 
                    
                    
             $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
                     
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
              case "getMaterialsByTypeforspec":
            $output = Array();
            $a=$_GET["material_type"];
             $a;
              
            if(empty($_GET["material_type"]) || $_GET["material_type"]=="Raw Material" || $_GET["material_type"]=="Packing Material" || $_GET["material_type"]=="Pharma Raw Material") {

             echo  $sql = "SELECT *  FROM material WHERE plant_id='".$_GET["plant_id"]."' AND material_type like '".$_GET["material_type"]."%'  ";
            
            } 
               
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    if($row["equivalent"] !=Null && $row['grade'] !=Null){
                    $row["equivalent"] = json_decode($row["equivalent"]);
             
            }
             $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
                     
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getvendormapdata":
            $output = Array();
             $sql = "SELECT m.supplier_code as vendor_no ,m.manufacturer_data, m.material_code,v.id, v.vendor_name FROM mst_vendor_materials m left join vendor v on v.vendor_no= m.supplier_code  WHERE  m.material_code= '".$_GET["material_code"]."' AND  m.plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['manufacturer_data']   = json_decode( $row['manufacturer_data']);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getvendormapdataHo":
            $output = Array();
             $sql = "SELECT m.supplier_code as vendor_no ,m.manufacturer_data, m.material_code,v.id, v.vendor_name FROM 
             mst_vendor_materials m left join vendor v on v.vendor_no= m.supplier_code  WHERE  m.material_code= '".$_GET["material_code"]."' 
             AND  m.plant_id= '".$_GET["plantID"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['manufacturer_data']   = json_decode( $row['manufacturer_data']);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getAlertsAndNotifications":
            $output = Array();
             $sql = "SELECT a.*, CONCAT(e.firstname,' ',e.lastname) as empName FROM  alertNotification a left join employee e on a.emp_id= e.emp_id  WHERE a.plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "saveAlertAndNotification":
             
            $sql1 = "SELECT * FROM  alertNotification WHERE plant_id= '".$_GET["plant_id"]."' AND emp_id= '".$input["emp_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                 echo "{\"status\":\"failed\",\"msg\":\"Contact Already Added !!!!!\"}";
            }else{
                
                $sql="INSERT INTO `alertNotification`(`plant_id`, `department_name`, `emp_id`, `whatsappNO`, `entryBy`, `entryOn`) VALUES (
                '".$_GET["plant_id"]."', '".$input["department_name"]."','".$input["emp_id"]."','".$input["whatsappNO"]."',
                '".$_GET["emp_id"]."', '".$entry_date."')";   
               
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"failed\",\"msg\":\"Please Try Again !!!!!\"}";
                }
                
            }
            
             
            break;
        case "editAlertAndNotification":
            
                $sql="UPDATE `alertNotification` SET  `whatsappNO` = '".$input["whatsappNO"]."', `entryBy` = '".$_GET["emp_id"]."', 
                `entryOn` = '".$entry_date."' WHERE id = '".$input["id"]."'";   
               
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"failed\"}";
                }
                
            break;
            
            
        case "getexpiryManagementData":
            $output = array();
            $plantId = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : "");
            $sql = "SELECT s.material_code, s.ar_no, s.qty, s.exp_date, s.batch_no, s.id, s.status,
                    m.material_name, m.material_type, m.unit
                FROM stock_book s
                LEFT JOIN material m ON s.material_code = m.material_code AND (m.plant_id = '".$plantId."' OR m.plant_id IS NULL OR m.plant_id = '')
                WHERE s.plant_id = '".$plantId."'
                AND LOWER(IFNULL(s.status,'')) NOT IN ('expired','rejected','destroy','destroyed','discarded')
                ORDER BY s.exp_date ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $expRaw = isset($row['exp_date']) ? trim((string)$row['exp_date']) : '';
                    if ($expRaw === '' || $expRaw === '0000-00-00' || $expRaw === '0000-00-00 00:00:00') {
                        $row['days_left_to_expiry'] = 'Expired';
                        $output[] = $row;
                        continue;
                    }
                    try {
                        $current_date = new DateTime();
                        $exp_date = new DateTime($expRaw);
                        $interval = $current_date->diff($exp_date);
                        $days_left = (int)$interval->format("%r%a");
                        $row['days_left_to_expiry'] = ($days_left < 0) ? 'Expired' : $days_left;
                    } catch (Exception $e) {
                        $row['days_left_to_expiry'] = 'Expired';
                    }
                    $output[] = $row;
                }
            }
            safe_json_echo($output);
            break;
        case "updateStockStatus":
            
            
      
      
        $sql = "UPDATE stock_book SET status='".$_GET["status"]."', expiredQty='".$_GET["expiredQty"]."'  WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    
     
     
            break;
              case "getEngineeringEmployee":
            $output = array();
            $sql = "SELECT * FROM employee WHERE department='Engineering'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "expiredCountAPI":
                        $output = array();
                        $total_expired_count = 0;
                        $plantId = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : "");
                        $sql = "SELECT s.exp_date, s.status
                                FROM stock_book s
                                WHERE s.plant_id = '".$plantId."'
                                AND LOWER(IFNULL(s.status,'')) NOT IN ('rejected','destroy','destroyed','discarded')";
                        $result = $conn->query($sql);
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $st = strtolower(trim(isset($row['status']) ? (string)$row['status'] : ''));
                                if ($st === 'expired') {
                                    $total_expired_count++;
                                    continue;
                                }
                                $expRaw = isset($row['exp_date']) ? trim((string)$row['exp_date']) : '';
                                if ($expRaw === '' || $expRaw === '0000-00-00' || $expRaw === '0000-00-00 00:00:00') {
                                    $total_expired_count++;
                                    continue;
                                }
                                try {
                                    $current_date = new DateTime();
                                    $exp_date = new DateTime($expRaw);
                                    $interval = $current_date->diff($exp_date);
                                    $days_left = (int)$interval->format("%r%a");
                                    if ($days_left < 0) {
                                        $total_expired_count++;
                                    }
                                } catch (Exception $e) {
                                    $total_expired_count++;
                                }
                            }
                        }
                        $output['total_expired_count'] = $total_expired_count;
                        echo json_encode($output);
            break;
            
            
        case "getMonthWIseData":
            $output = array();
            $plant_id = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : "");
            $months = isset($_GET["months"]) ? (int)$_GET["months"] : 1;
            if ($months < 1) { $months = 1; }
            $future_date = new DateTime();
            $future_date->modify("+{$months} months");

            $sql = "SELECT s.material_code, s.ar_no, s.qty, s.exp_date, s.batch_no, s.id, m.material_name, m.material_type
                    FROM stock_book s
                    LEFT JOIN material m ON s.material_code = m.material_code AND (m.plant_id = '".$plant_id."' OR m.plant_id IS NULL OR m.plant_id = '')
                    WHERE s.plant_id = '".$plant_id."'
                    AND LOWER(IFNULL(s.status,'')) NOT IN ('expired','rejected','destroy','destroyed','discarded')";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $expRaw = isset($row['exp_date']) ? trim((string)$row['exp_date']) : '';
                    if ($expRaw === '' || $expRaw === '0000-00-00' || $expRaw === '0000-00-00 00:00:00') {
                        $row['days_left_to_expiry'] = 'Expired';
                        $output[] = $row;
                        continue;
                    }
                    try {
                        $current_date = new DateTime();
                        $exp_date = new DateTime($expRaw);
                        $interval = $current_date->diff($exp_date);
                        $days_left = (int)$interval->format("%r%a");
                        $row['days_left_to_expiry'] = ($days_left < 0) ? 'Expired' : $days_left;
                        if ($exp_date <= $future_date) {
                            $output[] = $row;
                        }
                    } catch (Exception $e) {
                        $row['days_left_to_expiry'] = 'Expired';
                        $output[] = $row;
                    }
                }
            }

            safe_json_echo($output);
            break;
            
            
            
        case "getallmaterialsbyType":
            $output = Array();
            
            
            if($_GET["material_type"] == 'Raw Material' || $_GET["material_type"] == 'Packing Material'){
                
            $sql = "SELECT mv.*, m.order_qty,m.inventory,mv.uom   FROM my_view mv    left join material m ON
            m.material_code=mv.material_code where   mv.plant_id= '".$_GET["plant_id"]."' AND mv.material_type= '".$_GET["material_type"]."'";

            }else{
                
            $sql = "SELECT mv.*, m.order_qty,m.inventory,mv.uom   FROM my_view mv    left join others_material m ON
            m.material_code=mv.material_code where   mv.plant_id= '".$_GET["plant_id"]."' AND mv.material_type= '".$_GET["material_type"]."'";
                
            }
            
           
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    
                $sql1 = "SELECT sum(qty) as og_qty FROM stock_book where material_code = '".$row['material_code']."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row['og_qty'] = $row1['og_qty'];
                    }
                }
                $sql2 = "SELECT sum(qty) as did_qty FROM material_issue where material_code = '".$row['material_code']."' ";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                         $row['did_qty'] = $row2['did_qty'];
                    }
                }
                    

                  $row['avaliable_stock'] = $row['og_qty'] - $row['did_qty'];
                  $row['avaliable_stock'] = number_format((float)$row['avaliable_stock'], 2, '.', '');

                  
                  
$availableStock = (float)$row['available_stock'];
$inventory = (float)$row['inventory'];

// Comparison logic
if ($availableStock > $inventory) {
    $row['inventory_status'] = 'OK';
} else {
    $row['inventory_status'] = 'LOW';
}                 
                  
                $sql22 = "SELECT AVG(pm.qty) AS  average_pur FROM po_material pm JOIN purchaseorder po ON pm.po_no = po.id WHERE 
                pm.material_code = '".$row['material_code']."' AND po.entry_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 MONTH)";
                $result22 = $conn->query($sql22);
                if ($result22->num_rows > 0) {
                    while ($row22 = $result22->fetch_assoc()) {
                         
                         
                         if (isset($row22['average_pur'])) {
    $row['average_pur'] = number_format((float)$row22['average_pur'], 2);
} else {
    $row['average_pur'] = '0.00'; // or handle the null case as needed
}

                    }
                }
                  
                   
                  $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
        case "savetrigger_data":
            $output = Array();
            if($_GET["material_type"] == 'Raw Material' || $_GET["material_type"] == 'Packing Material'){
            
              $sql = "UPDATE material SET inventory = '".$_GET["inventory"]."' , order_qty = '".$_GET["order_qty"]."' 
             WHERE  id = '".$_GET["id"]."'";
            }else{
                
             $sql = "UPDATE others_material SET inventory = '".$_GET["inventory"]."' , order_qty = '".$_GET["order_qty"]."' 
             WHERE  id = '".$_GET["id"]."'";
            }
             

             
   
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"failed\"}";
            }
            
            
            break;
            
            
            
        case "getallmatdata":
            $output = Array();
             $sql = "SELECT * FROM my_view WHERE  plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getMaterialForMapping":
            $output = Array();
                
             $sql = "SELECT * FROM my_view WHERE status = 'Approved' AND  material_type = '".$_GET["matType"]."' AND plant_id= '".$_GET["plant_id"]."'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
                    $output[] = $row;
                }
            }
                // echo json_encode($output);
                $output = utf8ize($output);
                echo json_encode($output);
            break;
            
            
        case "getallmatdataForIndent":
            $output = Array();
            
        
             $sql = "SELECT m.*,m.uom as unit  ,v.vendor_name FROM my_view m left join vendor v ON m.vendor_no = v.vendor_no WHERE  m.plant_id = '".$_GET["plant_id"]."'
            AND m.material_type like '%".$_GET["material_type"]."%' ";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $gradeRaw = isset($row['grade']) ? trim((string)$row['grade']) : '';
                    $row['gradeName'] = '';
                    if ($gradeRaw !== '') {
                        $idPart = preg_replace('/[^0-9,]/', '', $gradeRaw);
                        if ($idPart !== '' && preg_match('/^\d+(,\d+)*$/', $idPart)) {
                            $resQ = $conn->query("SELECT GROUP_CONCAT(grade) AS gradeName FROM grade WHERE id IN (".$idPart.")");
                            if ($resQ && ($g = $resQ->fetch_assoc()) && !empty($g['gradeName'])) {
                                $row['gradeName'] = $g['gradeName'];
                            }
                        }
                        if ($row['gradeName'] === '') {
                            $row['gradeName'] = $gradeRaw;
                        }
                    }
                    $output[] = $row;
                }
            }
            // echo json_encode($output);
            
            $output = utf8ize($output);
            echo json_encode($output);
            break;
            
        case "getMaterialByTypeAndSubtype":
            $output = Array();
            
        
            $sql = "SELECT m.*,m.unit as uom,v.vendor_name FROM my_view m left join vendor v ON m.vendor_no = v.vendor_no WHERE  m.plant_id = '".$_GET["plant_id"]."'
            AND m.material_type = '".$_GET["material_type"]."' AND m.material_type = '".$_GET["material_type"]."' ";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            // echo json_encode($output);
            
            $output = utf8ize($output);
            echo json_encode($output);
            break;
 
            
            
        case "Hogetallmatdata":
            $output = Array();
             $sql = "SELECT * FROM my_view WHERE  plant_id= '".$_GET["plantID"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
           
        case "getDepartments":
            $output = Array();
            $sql = "SELECT * FROM department order by department_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT * FROM section WHERE department='".$row["department_name"]."' order by section_name";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["sections"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getSECTIONS":
            $output = Array();
        $sql = "SELECT * FROM section WHERE department='".$_GET["department1"]."' order by section_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getTempratureHumidityCheckingLog":
           
            $output = Array();
             $sql = "SELECT * FROM temperhumrec WHERE department= '".$_GET["depart"]."' and  plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getTempratureHumidityChecking":
           
            $output = Array();
             $sql = "SELECT * FROM temperhumrec WHERE department= '".$_GET["depart"]."' and status= 'pending' and  plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "get_Eqgetemployee_byDeptipments":
                $output = Array();
   
                $sql = "SELECT CONCAT(firstname, ' ' , lastname) as emp_name,emp_id,department,designation FROM employee WHERE status='Active' AND 
               plant_id='".$_GET["plant_id"]."' and department='".$_GET["depart"]."' ORDER BY firstname asc";

                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
                break;
  
       $output = array();
            $sql = "SELECT * FROM equipment WHERE plant_id='".$_GET["plant_id"]."' AND   equipment_code='".$_GET["equipment_code"]."'  Order By equipment_name";
            $result = $conn->query($sql);
             if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo json_encode($row);   // return single object
            } else {
                echo "{}";   // empty JSON object if not found
            }
            // echo json_encode($output);
            break;
        case "Getunit":
            $output = Array();
        $sql = "SELECT * FROM unit  order by id ASC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getbal_id":
            $output = Array();
            $dept = isset($_GET['department1']) ? $conn->real_escape_string(trim((string)$_GET['department1'])) : '';
            $location = isset($_GET['location']) ? $conn->real_escape_string(trim((string)$_GET['location'])) : '';
            $plantId = isset($_GET['plant_id']) ? $conn->real_escape_string(trim((string)$_GET['plant_id'])) : '';
            $sql = "SELECT * FROM equipment WHERE 1=1";
            if ($plantId !== '') {
                $sql .= " AND plant_id='".$plantId."'";
            }
            if ($dept !== '') {
                $sql .= " AND (department='".$dept."' OR department='QC' OR department LIKE '%Quality Control%')";
            }
            if ($location !== '') {
                $sql .= " AND (location='".$location."' OR section='".$location."' OR TRIM(location)=TRIM('".$location."'))";
            }
            $sql .= " ORDER BY equipment_name, equipment_code";
            $result = @$conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
                   case "getbal_idForCOnsu":
            $output = Array();
        $sql = "SELECT id,equipment_category,equipment_code,equipment_name,department,serial_no,make FROM equipment 
        WHERE department='".$_GET["department1"]."'  AND plant_id='".$_GET["plant_id"]."' ";
        
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getDesignations":
            $output = Array();
            $sql = "SELECT * FROM department order by department_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                     $sql1 = "SELECT * FROM section WHERE designation='".$row["designation"]."' order by section_name";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["sections"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getDepartmentSections":
            $output1 = Array();
            $sql1 = "SELECT * FROM section WHERE department='".$_GET["department"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
        case "get_Designationss":
            $output1 = Array();
             $sql1 = "SELECT * FROM designation  WHERE dept_id='".$_GET["department1"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    
                    $output1[] = $row1;
                    
                }
            }
            echo json_encode($output1);
            break;
            
        case "getAvaliableStockByMaterialCode":
            $output1 = Array();
             $sql1 = "SELECT * FROM vw_available_stock  WHERE plant_id = '".$_GET["plant_id"]."' AND material_code = '".$_GET["material_code"]."'  ";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
            
            
        case "returanable_gatepassNo":
            $output1 = Array();
           //  $sql1 = "SELECT * FROM outword  WHERE outword_type LIKE '%Returnable%' AND status LIKE '%verify%'";
                          $sql1 = "SELECT * FROM outword  WHERE outword_type = 'Returnable' AND status LIKE '%verify%'";

            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $row1["materials"] = json_decode($row1["materials"]);
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
            
            
            
        case "getCities":
            $output1 = Array();
            $sql1 = "SELECT * FROM city";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
        case "get_transports":
            $output1 = Array();
            $sql1 = "SELECT * FROM transport_master";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
            
        case "getVendorsByType":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='".$_GET["vendor_type"]."' AND status='Approved'   and plant_id= '".$_GET["plant_id"]."'";
            // echo $sql;
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);

                    $output[] = $row;
                }
            }
            echo json_encode($output);
        break;
        case "getNormalMaterialMaster":
            $output = Array();
            $sql = "SELECT  `id`, `plant_id`, `material_type`, `matIs`, `material_code`, `material_subtype`, `material_name`, `material_name_report`, `cas_name`, `material_nature`, `storage_condition`, 
            `inventory`, `order_qty`, `order_unit`, `density`, `unit`, `retest`, `lead_time`, `category`, `hsn`, `tax_type`, `gst`, `tax`, `status`, `sub_type`, `equivalancy_applicable`, `equivalent_to`, 
            `material_appearance`, `retest_month`, `description`, `client_code`, `art_work`, `grade`, `cas_no`, `uom`, `alternate_uom`, `pack_size`, `packing_requirement`, `safety`, `color_index`, `type`, 
            `product_n`, `artwork`, `artwork_file`, `artwork_no`, `leverages`, `mainGroupSeries`, `specificGravity`, `texture`, `madeOf`, `dimension`, `Artwork_Order_No`, `Artwork_Version_No`, `plant_code`, 
            `materialTypeCode`, `materialSubTypeCode`, `packSizeCode`, `moisture`, `moq`, `plasticType`, `inventoryValueMax`, `premixItem`, `testingRequired`, `controlSample`, `sampleForTesting`, `samplingUnit`
            FROM material WHERE material_type = '".$_GET["material_type"]."' AND  material_subtype = '".$_GET["material_subtype"]."' AND status='Approved' AND  plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);

                    $output[] = $row;
                }
            }
            echo json_encode($output);
        break;
        case "getVendorByMaterialType":
            $output = Array();
            
            if($_GET["typev"] == 'all'){
                
                $sql = "SELECT `id`, `plant_id`, `status`, `vendor_no`, `material_type`, `vendor_type`, `vendor_name`,currency FROM vendor WHERE  status='Approved'   and plant_id= '".$_GET["plant_id"]."'";
                
            }else{
                
                $sql = "SELECT `id`, `plant_id`, `status`, `vendor_no`, `material_type`, `vendor_type`, `vendor_name`,currency FROM vendor WHERE material_type = '".$_GET["material_type"]."' AND status='Approved'   and plant_id= '".$_GET["plant_id"]."'";
                
            }
            
            // echo $sql;
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                     $row["currency"] = json_decode($row["currency"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        break;
        case "getApprovedVendorsWithoutDivision":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE status = 'Approved'  AND  vendor_Is = 'Vendor' AND plant_id= '".$_GET["plant_id"]."'";
            // echo $sql;
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
        break;
            
            case "getvendorByType":
                
                    $output = array();
                    $sql = "SELECT * FROM vendor WHERE status='Approved' AND vendor_type = '".$_GET["vendor_type"]."' AND plant_id= '".$_GET["plant_id"]."' ";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $output[] = $row;
                        }
                    }
                    
                    echo json_encode($output);
            break;
            
            case "getVendorForMaterialMappaing":
                
                    $output = array();
                    $sql = "SELECT id,plant_id,status,vendor_no,material_type,vendor_type,vendor_name,vendorFor FROM vendor WHERE status='Approved' AND ( material_type = 'Raw Material' OR material_type = 'Packing Material' ) AND plant_id= '".$_GET["plant_id"]."' ";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $output[] = $row;
                        }
                    }
                    
                    echo json_encode($output);
            break;
        
        case "getManufacturers":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE status = 'Approved' AND vendor_type IN ('Manufacturer', 'Supplier')  and plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
               case "AllEmployeeList":
                $output = Array();
   
               $sql = "SELECT firstname as emp_name,emp_id,a.department,a.designation,a.joining_date FROM  employee a WHERE a.status='Active' AND a.plant_id='".$_GET["plant_id"]."' ORDER BY firstname asc";

                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
                break;
        case "getVendorForgenIndednt":
            $output = Array();
            $sql = "SELECT * 
            FROM vendor 
            WHERE status = 'Approved' 
             AND plant_id = '" . $_GET["plant_id"] . "' 
             AND material_type NOT IN ('Raw Material', 'Packing Material')";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getManufacturersForIndent":
            $output = Array();
               $sql = "SELECT m.*,v.vendor_name,v.vendor_no FROM mst_vendor_materials m left join  vendor v ON m.manufacturer_code = v.vendor_no OR
             m.supplier_code = v.vendor_no  WHERE m.material_code =  '".$_GET["material_code"]."' and m.plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
                
              
            } 
            echo json_encode($output);
            break;
        case "HogetManufacturers":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer'  and plant_id= '".$_GET["plantID"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getRAWSupplierByType":
            $output = Array();
              $sql = "SELECT * FROM vendor WHERE vendor_type='Supplier' AND material_type='".$_GET["material_type"]."'  and plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break; 
            case "getRAWSupplier":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Supplier' AND material_type='Raw Material'  and plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break; 
            
            
             case "getGlasswareSupplier":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Supplier' AND material_type='Glassware Material'  and plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break; 
            case "getChemicalSupplier" :   
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Supplier' AND material_type='Chemical Material' 
            and plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            case "getDosageformForMarketing" :   
            $output = Array();
            $sql = "SELECT * FROM master_fg_types ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            case "getDosageform" :   
            $output = Array();
            $sql = "SELECT * FROM master_fg_types where plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) { 
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            case "HOgetDosageform" :   
            $output = Array();
            $sql = "SELECT * FROM master_fg_types ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            
            
            echo json_encode($output);
            break;
            case "getChemicalManufacturer" :   
            $output = Array();
            $plant = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';
            $seenNo = array();
            $seenName = array();

            $addVendorRow = function ($vendorNo, $vendorName, $source) use (&$output, &$seenNo, &$seenName) {
                $name = trim((string)$vendorName);
                $no = trim((string)$vendorNo);
                if ($name === '') {
                    return;
                }
                $nameKey = strtolower($name);
                $noKey = strtolower($no);
                if ($noKey !== '' && isset($seenNo[$noKey])) {
                    return;
                }
                if (isset($seenName[$nameKey])) {
                    return;
                }
                if ($noKey !== '') {
                    $seenNo[$noKey] = true;
                }
                $seenName[$nameKey] = true;
                $output[] = array(
                    'vendor_no' => $no !== '' ? $no : $nameKey,
                    'vendor_name' => $name,
                    'source' => $source,
                );
            };

            if ($plant !== '') {
                // Same vendor master as Purchase > Vendor Log (plant-scoped).
                $sqlVendor = "SELECT vendor_no, vendor_name, vendor_type, material_type, status
                    FROM vendor
                    WHERE plant_id='".$plant."'
                    AND vendor_name IS NOT NULL AND TRIM(vendor_name) <> ''
                    AND COALESCE(status,'') NOT IN ('BlackList', 'Temparory Block')
                    ORDER BY vendor_name";
                $resultVendor = $conn->query($sqlVendor);
                if ($resultVendor && $resultVendor->num_rows > 0) {
                    while ($row = $resultVendor->fetch_assoc()) {
                        $addVendorRow($row['vendor_no'], $row['vendor_name'], 'vendor');
                    }
                }
            }

            $sql = "SELECT id, mfg_name FROM chem_vendor ORDER BY mfg_name";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $code = 'CV-'.str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT);
                    $addVendorRow($code, $row['mfg_name'], 'chem_vendor');
                }
            }

            echo json_encode($output);
            break;
             
             case "getGeneralSupplier":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Supplier' AND material_type='General Material'  and plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break; 
            
            case "getRAWManufacturesByType":
            $output = Array();
            //   $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer'  AND vendor_status='Approved' 
            // AND material_type='".$_GET["material_type"]."' and plant_id= '".$_GET["plant_id"]."' ";
                $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer'   
            AND material_type='".$_GET["material_type"]."' and plant_id= '".$_GET["plant_id"]."' ";
            //     $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer'  AND vendor_status='Approved' 
            // AND material_type='".$_GET["material_type"]."' and plant_id= '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
            case "getRAWManufactures":
            $output = Array();
            // $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer' AND material_type='Raw Material' 
            // and plant_id= '".$_GET["plant_id"]."' ";
            $sql = "SELECT * FROM vendor WHERE    plant_id='" . $_GET["plant_id"] . "' ORDER BY vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            case "getPAKINGManufactures":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer' AND material_type='Packing Material' 
            and plant_id= '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
            case "getGeneralManufactures":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer' AND material_type='General Material'  and plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
             case "getChemicalManufactures":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer' AND material_type='Chemical Material'  and plant_id= '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            case "getGlasswareManufactures":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer' AND material_type='Glassware Material'  and plant_id= '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
            
            
            case "getAllRawVendors":
            $output = array();
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.material_type='Raw Material' 
             and v.plant_id= '".$_GET["plant_id"]."'
            ORDER BY v.vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
            
             case "getPackingManufactures":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer' AND material_type='Packing Material'  and plant_id= '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
         case "getshelfLife":
            $output1 = Array();
            $sql1 = "SELECT * FROM shelf_life where  plant_id= '".$_GET["plant_id"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
            
         case "getFormulas":
            $output = array();
            $sql = "SELECT * FROM formulas";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["selectImage"] = 'https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/formula/'.$row["image"];
                    $row["parameters"] = json_decode($row["parameters"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
         case "getRetest":
            $output1 = Array();
            $sql1 = "SELECT * FROM retest";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
            
         case "getVendors":
            $output = array();
             $sql = "SELECT * FROM vendor  WHERE status='Approved' AND plant_id= '".$_GET["plant_id"]."' ORDER BY vendor_name asc";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;

            case "getGeneralMatVendor":
                
                $output = array();
                $sql = "SELECT id,plant_id,status,vendor_no,material_type,vendor_type,vendor_name,client_code,scode,gst_no,currency,vendorFor FROM vendor WHERE status = 'Approved'  AND plant_id= '".$_GET["plant_id"]."' 
                AND material_type NOT IN ('Raw Material', 'Packing Material') ";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $row['currency'] = json_decode($row["currency"]);
                        $output[] = $row;
                    }
                }
                
                echo json_encode($output);
            
            break;
            
            
             case "HogetVendorByMaterialType":
            $output = array();
            
            
            if(isset($_GET["typev"]))
            {            
        $sql = "SELECT  * FROM vendor where   plant_id= '".$_GET["plantID"]."' ORDER BY vendor_name "; 
            
            }
            else{
                
          $sql = "SELECT DISTINCT v.id, v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
            WHERE v.status='approve' or v.status='Approved' AND v.material_type like'%".$_GET["material_type"]."'
            and v.plant_id= '".$_GET["plant_id"]."'
            and (v.vendor_type='Manufacturer' or v.vendor_type='Supplier'or  v.vendor_type='Service') ";//ORDER BY v.vendor_name";  
            }
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
             case "getAllVendor":
            $output = array();
            
            $sql = " SELECT `id`, `plant_id`, `status`, `vendor_no`, `material_type`, `vendor_type`, `vendor_name`, `contact_person`, `contact_number`, `contact_email`, `address` FROM vendor 
            WHERE plant_id= '".$_GET["plant_id"]."' AND status = 'Approved' ";

            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            
             case "geRmPmVendorsByType":
                 
            $output = array();
            
            $sql = "SELECT `id`, `plant_id`, `status`, `vendor_no`, `material_type`, `vendor_type`, `vendor_name`, `contact_person`, `contact_number`, `contact_email`, `address` FROM vendor 
            WHERE plant_id = '".$_GET["plant_id"]."' AND status = 'Approved' AND material_type = '".$_GET["material_type"]."'";

            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
           
        case "getVendorByMaterialCode":
            $output = array();
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
            WHERE v.status='Approved' AND  v.vendor_no in(select supplier_code from mst_vendor_materials plant_id= '".$_GET["plant_id"]."' and material_code =  )
            and v.plant_id= '".$_GET["plant_id"]."' ORDER BY v.vendor_name";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getRawVendors":
            $output = array();
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
            WHERE v.status='Approved' AND v.material_type='Raw Material' ORDER BY v.vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        
        case "getPackingVendors":
            $output = array();
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='Approved' AND v.material_type='Packing Material' ORDER BY v.vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        
        case "getGeneralVendors":
            $output = array();
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='Approved' AND v.material_type='General Material' ORDER BY v.vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getChemicalVendors":
            $output = array();
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='Approved' AND v.material_type='Chemical Material' ORDER BY v.vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        
        case "getGlasswareVendors":
            $output = array();
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='Approved' AND v.material_type='Glassware Material' ORDER BY v.vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
             case "getApprovedTrainers":
            $output = array();
           $sql = "SELECT * FROM externaltrainer WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
           $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["certificate"] = "upload/".$row["certificate"];
                $row["resume"] = "upload/".$row["resume"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
            break;
        
        case "getFinishVendors":
            $output = array();
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='Approved' AND v.material_type='Finish Product' ORDER BY v.vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        
             
        case "getStorage":
            $output1 = Array();
             $sql1 = "SELECT * FROM storage_conditions where  plant_id='".$_GET["plant_id"]."' ";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
        case "getPackSizes":
            $output1 = Array();
            $sql1 = "SELECT a.id,a.pack_size,a.unit FROM pack_size a 
                 where  a.plant_id='".$_GET["plant_id"]."'  order by a.id desc";
            
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
         case "getPackSizesmar":
            $output1 = Array();
            $sql1 = "SELECT a.id,a.pack_size,a.unit FROM pack_size a
                 where  a.plant_id='".$_GET["plantID"]."'  order by a.id desc";
            
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
        case "getPackSize":
            $output1 = Array();
            $sql1 = "SELECT a.*  FROM pack_size a  where  a.plant_id='".$_GET["plant_id"]."'  order by a.pack_size ASC";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    $output1[] = $row1;
                }
            }
            echo json_encode($output1);
            break;
            
        case "getDosages":
            $output = Array();
             $sql = "SELECT  dosage_form_type FROM master_fg_types where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
                    
        case "getproductTypeBmr":
            $output = Array();
            $sql = "SELECT dosage_form FROM product where  plant_id='".$_GET["plant_id"]."' group by dosage_form  order by id desc  ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getproductType":
            $output = Array();
            $sql = "SELECT dosage_form_type FROM master_fg_types where  plant_id='".$_GET["plant_id"]."' order by id desc ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getproductName":
            $output = Array();
            $sql = "SELECT product_name,product_code,grade FROM product where  plant_id='".$_GET["plant_id"]."'   ";
            // $sql = "SELECT product_name,product_code1,grade FROM product where  plant_id='".$_GET["plant_id"]."' and product_type='".$_GET["product_type"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getDosagesByType":
            $output = Array();
            $sql = "SELECT type, dosage_form FROM dosage_form where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "devGetProduct":
            $output = Array();
             $sql = "SELECT * FROM product  WHERE plant_id='".$_GET["plant_id"]."' ORDER BY product_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                     $output1 = Array();
                        $sql1 = "SELECT * FROM batch_planning a left join mfg_work_order_hdr b on a.id=b.batch_plan_id where a.product_code='".$row["product_code"]."' and b.batch_number is not null ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                    $row["batch_nos"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProducts":
            $output = Array();
            //  $sql = "SELECT p.*  FROM enquiryProduct  p
             $sql = "SELECT p.*  FROM product  p
              WHERE  p.product_code is not null AND p.plant_id='".$_GET["plant_id"]."'
            ORDER BY product_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { 
                     $output[] = $row;
                     
                }
            }
            echo json_encode($output);
            break;
        case "getProductsss":
            $output = Array();
             $sql = "SELECT p.*,m.plant_name FROM product p
            LEFT JOIN plant m ON p.plant_id=m.plant_id WHERE    p.plant_id='".$_GET["plantID"]."'
            ORDER BY product_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1="SELECT b.pack_size,b.unit  FROM unitformula a left join unitformula_pm_dtl b on a.id=b.unit_formula_id WHERE a.product_code='".$row['product_code']."'";
                       $result1 = $conn->query($sql1);
                                    if ($result1->num_rows > 0) {
                                        while ($row1 = $result1->fetch_assoc()) {
                                             $output1[] = $row1;
                                        }
                                    }
                    $row['pack_size']=$output1;
                    $output2 = Array();
                    $sql2="SELECT *,a.batch_formula_weight as batch_size,a.rm_batch_size_unit as batch_unit from batch_formula_info a  WHERE a.product_code='".$row['product_code']."'";
                       $result2 = $conn->query($sql2);
                                    if ($result2->num_rows > 0) {
                                        while ($row2 = $result2->fetch_assoc()) {
                                             $output2[] = $row2;
                                        }
                                    }
                    $row['batch_size']=$output2;
                    $row['pack_size']=$output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProductsssZuma":
            $output = Array();
             $sql = "SELECT p.*,m.plant_name FROM product p
            LEFT JOIN plant m ON p.plant_id=m.plant_id WHERE  p.status='approve' AND p.plant_id='".$_GET["plant_id"]."'
            ORDER BY product_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
             case "getProductsssUnit2":
            $output = Array();
             $sql = "SELECT p.*,m.plant_name FROM product p
            LEFT JOIN plant m ON p.plant_id=m.plant_id WHERE  p.status='approve' AND p.plant_id='".$_GET["plant_id"]."'
            ORDER BY product_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getAllProducts":
            $output = Array();
             $sql = "SELECT p.*,m.plant_name FROM product p
            LEFT JOIN plant m ON p.plant_id=m.plant_id WHERE  p.status='approve'  
            ORDER BY product_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                                        $row["label_claim"] = json_decode($row["label_claim"]);

                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProductHO":
            $output = Array();
             $sql = "SELECT p.*,m.plant_name FROM product p
            LEFT JOIN plant m ON p.plant_id=m.plant_id WHERE  p.status='approve' 
            ORDER BY product_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProduct":
            $output = Array();
             $sql = "SELECT id,plant_id,product_code,product_name,grade FROM product  WHERE 
             status='approve' AND plant_id='".$_GET["plant_id"]."' ORDER BY product_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProducts_for_client":
            $output = Array();
          $sql = "SELECT a.*, a.product_name, a.product_code  FROM product a LEFT JOIN ratemrp b ON a.manufactured_for = b.client_code WHERE a.manufactured_under != 'Own'  and  a.product_name='".$_GET["productName"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
               
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProductsForChangeMrp":
            $output = Array();
          $sql = "SELECT * from product where (manufactured_for='".$_GET["client_code"]."' or manufactured_under='".$_GET["client_code"]."')";
     
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
              case "getProductsList":
            $output = Array();
         $sql = "SELECT * from product";

            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                   
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "delete_tax_heading":
        $sql="delete from tax_headings where id ='".$input['id']."'";   
       
        if ($conn->query($sql)) {
    
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        break;
        case "save_product_rate":
        $sql="Insert into ratemrp(product_code,category,client_code,rate,entry_by,entry_date,plant_id) values(
        '".$_GET["product_type1"]."', '".$_GET["product_code1"]."','".$_GET["client_code"]."','".$_GET["mrprates"]."',
        '".$_GET["emp_id"]."', '".$entry_date."','".$_GET["plant_id"]."')";   
       
        if ($conn->query($sql)) {
    
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        break;
        case "saveItConsumableLog":
            
        $sql="Insert into itConsumableLog(plant_id,u_type,user_loc,department,types,make,ip_series,entry_by,entry_date) values(
        '".$_GET["plant_id"]."', '".$input["u_type"]."','".$input["user_loc"]."','".$input["department"]."','".$input["types"]."','".$input["make"]."',
        '".$input["ip_series"]."','".$_GET["emp_id"]."', '".$entry_date."')";   
       
        if ($conn->query($sql)) {
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    
            break;
        case "save_chem_mfg":
        $sql="Insert into  chem_vendor(mfg_name) values('".$input["mfg_name"]."')";   
       
        if ($conn->query($sql)) {
    
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    
            break;
        case "save_tax_heading":
        $sql="Insert into  tax_headings(heading) values('".$input["heading"]."')";   
       
        if ($conn->query($sql)) {
    
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    
            break;
        case "getProducts_copy":
            $output = Array();
           // $sql = "SELECT p.product_code, p.product_name , p.grade, p.shelf_life, p.hsn, p.gst,p.packing_style,m.mrp FROM product p
            // $sql = "SELECT p.*,m.mrp FROM product p
            // LEFT JOIN mrp m ON p.product_code=m.product_code WHERE user_no='".$_GET["user_no"]."' AND p.status='approve' AND p.plant_id='".$_GET["plant_id"]."'
            // ORDER BY product_name";
            $sql = "SELECT p.*,m.mrp FROM product p LEFT JOIN mrp m ON p.product_code=m.product_code WHERE
                user_no='gmpdemo1' AND p.status='approve' AND p.plant_id='29' AND p.dosage_type='".$_GET["dosage_type"]."' AND p.dosage_form='".$_GET["dosage_form"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT * FROM product_mrp WHERE product_code='".$row["product_code"]."' ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["mrp"] = $row1["mrp"];
                            $row["rate"] = $row1["rate"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "get_tax_heading":
            $output = Array();
           // $sql = "SELECT p.product_code, p.product_name , p.grade, p.shelf_life, p.hsn, p.gst,p.packing_style,m.mrp FROM product p
            $sql = "select * from tax_headings ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
                $output[] = $row;
            }
        }
            echo json_encode($output);
            break;
        case "Product_name":
            $output = Array();
           // $sql = "SELECT p.product_code, p.product_name , p.grade, p.shelf_life, p.hsn, p.gst,p.packing_style,m.mrp FROM product p
            $sql = "SELECT * FROM `product` WHERE p.product_type='".$_GET["product_type"]."'  and p.plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
                $output[] = $row;
            }
        }
            echo json_encode($output);
            break;
            
        case "geti8tConsumableLog":
            $output = Array();
             $sql = "SELECT * FROM `itConsumableLog` WHERE plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
                $output[] = $row;
            }
        }
            echo json_encode($output);
            break;
            
        case "getMaterialsByTypes":
            $output = Array();
            $sql = "SELECT * FROM my_view WHERE plant_id = '".$_GET["plant_id"]."' AND 
            material_type = '".$_GET["material_type"]."' ORDER BY material_name ASC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $row['gross_total'] = 0;
                    $row['gst_total'] = 0;
                    $row['net_total'] = 0;
                    $row['qty'] = 0;
                    $row['rate'] = 0;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getProductsByDosage":
            $output = Array();
         
              $sql="SELECT DISTINCT p.dosage_form, p.*,ps.stage as packing_stages 
             FROM product p LEFT JOIN manufacturing_process m ON p.dosage_form=m.dosage_form 
             left join packing_manufacturing_process ps on p.dosage_form=ps.dosage_form WHERE p.plant_id='".$_GET["plant_id"]."'
             AND p.product_type='".$_GET["product_type"]."' or p.dosage_form='".$_GET["product_type"]."'";
             
            
            
    
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                        $sql1 = "SELECT min(batch_size) as min_batch_size, max(batch_size) as max_batch_size 
                    FROM batch_formula WHERE product_code='".$row["product_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["min_batch_size"] = $row1["min_batch_size"];
                            $row["max_batch_size"] = $row1["max_batch_size"];
                        }
                    } else {
                        $row["min_batch_size"] = 0;
                        $row["max_batch_size"] = 0;
                    }
                    
                    $output1 = array();
                    $sql1 = "SELECT * FROM product_mrp WHERE product_code='".$row["product_code"]."'
                    ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["mrp"] = $row1["mrp"];
                        }
                    }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProductsByDosage_stability":
            $output = Array();
         
               $sql="SELECT id,plant_id,product_code,product_type,product_name,generic_name,grade,category,status,unit,copy_from,
              dosage_type,dosage_form,label_claim,shelf_life,brand_generic FROM product   WHERE plant_id='".$_GET["plant_id"]."'
             AND ( product_type='".$_GET["dosage_form"]."' or dosage_form='".$_GET["dosage_form"]."' ) ";
             
             
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    
                    $sql1 = "SELECT specification_no FROM specification WHERE product_code='".$row["product_code"]."'
                    ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["specification_no"] = $row1["specification_no"];
                        }
                    }else{
                        
                        $row["specification_no"] = 'NA';
                    }
                
                 
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProductsByproduct_naturestabilityAPi":
            $output = Array();
         
               $sql="SELECT id,plant_id,product_code,product_type,product_name,generic_name,grade,category,status,unit,copy_from,
              dosage_type,dosage_form,label_claim,shelf_life,brand_generic FROM product   WHERE plant_id='".$_GET["plant_id"]."'
             AND  product_nature='".$_GET["product_nature"]."'   ";
             
             
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    
                    $sql1 = "SELECT specification_no FROM specification WHERE product_code='".$row["product_code"]."'
                    ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["specification_no"] = $row1["specification_no"];
                        }
                    }else{
                        
                        $row["specification_no"] = 'NA';
                    }
                
                 
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getInitiatByData":
            $output = Array();
         
              $sql="SELECT  emp_id,lastname,firstname FROM employee  WHERE emp_id='".$_GET["emp_id"]."' AND
              plant_id='".$_GET["plant_id"]."' limit 1";
              
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $identifiedBy = $row['firstname']." ".$row['lastname']." ( ".$row['emp_id']." )";
                }
            }
            
            $output['identifiedBy'] = $identifiedBy;
            echo json_encode($output);
            break;
        case "getProductsByCategory":
            $output = Array();
         
              $sql="SELECT * FROM product p  
              WHERE p.category='".$_GET["category"]."'";
             
            
             
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProductsByDosageForMarketing":
            $output = Array();
         
              $sql="SELECT p.*,pl.plant_name FROM product p left join plant pl ON p.plant_id = pl.plant_id 
              WHERE p.product_type='".$_GET["product_type"]."' or p.dosage_form='".$_GET["product_type"]."'";
             
            
             
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProductsByDosageForMarketingMeha":
            $output = Array();
         
            $sql="SELECT p.*,pl.plant_name FROM product p left join plant pl ON p.plant_id = pl.plant_id";
             
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProductsForMarketing":
            $output = Array();
         
              $sql="SELECT p.*,pl.plant_name FROM product p left join plant pl ON p.plant_id = pl.plant_id  ";
             
            
             
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
            
            
            
        case "getTests":
            $output = Array();
            $sql = "SELECT * FROM test WHERE test_type = '".$_GET["test_type"]."' AND status = 'Active'  AND plant_id = '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT * FROM subtest WHERE test_id = '".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["subtests"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getTestsMeha":
                 $output = Array();
            $sql = "SELECT * FROM test WHERE test_type = '".$_GET["test_type"]."' AND status = 'Active'  AND plant_id = '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT * FROM subtest WHERE test_id = '".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["subtests"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getsubTests":
            $output = Array();
            $sql = "SELECT * FROM subtest WHERE test_type='".$_GET["test_type"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT * FROM subtest WHERE test_type='".$_GET["test_type"]."'";
                    //AND test='".$row["test"]."'";
                    $row["subtests"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "get_iqpc_tests":
            $output = Array();
            $sql = "SELECT * FROM specification a left JOIN spec_tests b on a.specification_no=b.specification_no 
                            WHERE a.product_code='".$_GET["product_code"]."' and a.spec_type='Inprocess Specification' ";
            // $sql = "SELECT * FROM test WHERE test_type='IPQC' Order by test";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT * FROM subtest WHERE test='".$row["test"]."' and  subtest !='' ";
                    //AND test='".$row["test"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["subtests"] = $output1;
                    
                    
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;    
        case "get_packing_tests":
            $output = Array();
            $sql = "SELECT * FROM test WHERE test_type='Packing' Order by test";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT * FROM subtest WHERE test='".$row["test"]."' and  subtest !='' ";
                    //AND test='".$row["test"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["subtests"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getDossageform":
             $output = Array();
            $sql = "SELECT dosage_form FROM product GROUP BY dosage_form";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            
            break;
        case "getRawMaterials":
            $output = Array();
            $sql = "SELECT * FROM material WHERE status='approve' AND material_type='Raw Material' ORDER BY material_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            } 
            echo json_encode($output);
            break;
        case "getRawMaterialsCoa":
            $output = Array();
            $sql = "SELECT * FROM testing b left join  material a on a.material_code=b.material_code WHERE a.user_no='".$_GET["user_no"]."' AND a.status='approve' AND a.material_type='Raw Material' ORDER BY a.material_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            } 
            echo json_encode($output);
            break;

          case "getMaterialsforstock":
            $output = Array();
            $plantId = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : "");
            $materialType = $conn->real_escape_string(isset($_GET["material_type"]) ? $_GET["material_type"] : "");
            $table = isset($_GET["table"]) ? $_GET["table"] : "";
            $subTypes = isset($_GET["sub_types"]) ? $_GET["sub_types"] : "";

            // Legacy callers (table + sub_types)
            if ($table == "material" && ($subTypes == "Raw Material" || $subTypes == "Packing Material")) {
                $subEsc = $conn->real_escape_string($subTypes);
                $sql = "SELECT id, material_type, material_name, material_code, unit FROM material
                        WHERE plant_id='".$plantId."'
                        AND (material_type='".$subEsc."' OR material_subtype='".$subEsc."')";
            } else if ($materialType != "") {
                // Store opening stock: load from material master (not RND view)
                $sql = "SELECT id, material_type, material_name, material_code, unit FROM material
                        WHERE plant_id='".$plantId."'
                        AND (material_type='".$materialType."' OR material_subtype='".$materialType."')";
            } else {
                $sql = "";
            }

            if ($sql != "") {
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $row['batch_no']='';
                        $row['pack_size']='';
                        $row['qty'] = 0;
                        $row['ar_no']='';
                        $row['grn_no']='';
                        $row['grn_date']='';
                        $row['assay']='';
                        $row['mfg_date']='';
                        $row['exp_date']='';
                        $row['release_date']='';
                        $row['vendor_no']='';
                        $row['selected'] = false;
                        $output[] = $row;
                    }
                }
            }

            echo json_encode($output);
            
            break;

          case "getMaterialsforstockGenMaterials":
            $output = Array();
                    
            $sql = "select id,material_type,material_name,material_code,unit from my_view where material_type = '".$_GET["material_type"]."'  and plant_id='".$_GET["plant_id"]."' ";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['batch_no']='#Autogenerated';
                    $row['pack_size']='';
                    $row['qty'] = 0;
                    $row['ar_no']='';
                    $row['grn_no']='';
                    $row['grn_date']='';
                    $row['assay']='';
                    $row['mfg_date']='';
                    $row['exp_date']='';
                    $row['release_date']='';
                    $row['vendor_no']='';
                    $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
            break;
        case "getMaterialsByTypeMangment":
            
        $output = array();

$sql = "SELECT DISTINCT material_type FROM my_view";
$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {

    $output1 = array();

    $sql1 = "SELECT DISTINCT material_subtype 
             FROM my_view 
             WHERE material_type = '".$conn->real_escape_string($row["material_type"])."'";

    $result1 = $conn->query($sql1);

    if ($result1->num_rows > 0) {
        while ($row1 = $result1->fetch_assoc()) {

            $output11 = array();

            $sql11 = "SELECT * 
                      FROM my_view 
                      WHERE material_type = '".$conn->real_escape_string($row["material_type"])."' 
                      AND material_subtype = '".$conn->real_escape_string($row1["material_subtype"])."'";

            $result11 = $conn->query($sql11);

            if ($result11->num_rows > 0) {
                while ($row11 = $result11->fetch_assoc()) {
                    $output11[] = $row11;
                }
            }

            $row1["materials"] = $output11;
            $output1[] = $row1;
        }
    }

    $row["material_subtypes"] = $output1;
    $output[] = $row; // ← IMPORTANT
}

echo json_encode($output, JSON_PRETTY_PRINT);

 break;
        case "getMaterialsByType":
            
            $output = Array();
            $matType = $conn->real_escape_string(trim($_GET["material_type"] ?? ''));
            $matSub = $conn->real_escape_string(trim($_GET["material_subtype"] ?? ''));
            $plantEsc = $conn->real_escape_string($_GET["plant_id"] ?? '');
            // Same active filter as Master → Material (exclude In-Active / Absolute only).
            $statusFilter = "(status IS NULL OR status = '' OR (LOWER(TRIM(status)) NOT IN ('in-active','in active','inactive','absolute','obsolete')))";
            $sql = '';
            
            if($matType=="Raw Material" || $matType=="Packing Material" || $matType=="Pharma Raw Material"){
                
                $sql = "SELECT id,plant_id,material_type,material_code,material_subtype,material_name,material_name_report,material_nature,storage_condition,
                unit,unit as uom,status,grade,sampleForTesting,samplingUnit FROM material WHERE plant_id = '".$plantEsc."' AND material_type = '".$matType."'
                AND ".$statusFilter;
                if ($matSub !== '' && strcasecmp($matSub, 'Base') !== 0) {
                    $sql .= " AND LOWER(TRIM(material_subtype)) = LOWER('".$matSub."')";
                }
                $sql .= " ORDER BY material_name ASC";
                
            }else if($matType=="Semi Finished Goods" || $matType=="Finish Product" || ($matType=="Base" && $matSub=='Base')){
                
                 $sql = "SELECT id,plant_id,product_code as material_code,product_type as material_type,product_name as material_name,generic_name,
                category,dosage_form as material_subtype,dosage_type,shelf_life,storage_condition,status
                FROM product WHERE plant_id = '".$plantEsc."' AND product_type = '".$matType."'
                AND dosage_form = '".$matSub."' AND (status = 'Approved' OR status = 'approve' OR status IS NULL OR status = '') ";
                
            } else if ($matSub !== '') {
                // Callers often pass only material_subtype (unit formula / store / planning)
                $sql = "SELECT id,plant_id,material_type,material_code,material_subtype,material_name,material_name_report,material_nature,storage_condition,
                unit,unit as uom,status,grade,sampleForTesting,samplingUnit FROM material WHERE plant_id = '".$plantEsc."'
                AND LOWER(TRIM(material_subtype)) = LOWER('".$matSub."')
                AND ".$statusFilter."
                ORDER BY material_name ASC";
            }

            if ($sql !== '') {
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);
            break;
        case "getMaterialsByTypeByCategory":
            
            $output = Array();
             
            $sql = "SELECT id,plant_id,product_code as material_code,product_type as material_type,product_name as material_name,generic_name,
            category,dosage_form as material_subtype,dosage_type,shelf_life,storage_condition,status
            FROM product WHERE plant_id = '".$_GET["plant_id"]."' AND category = '".$_GET["category"]."'
            AND dosage_form = '".$_GET["dosage_form"]."' AND status = 'Approved' ";
                
       
 
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getRndMaterialsByType":
            
            $output = Array();
            
            $sql = "SELECT a.id,a.plant_id,b.material_type,a.material_code,b.material_subtype,b.material_name,b.material_name_report,b.material_nature,b.storage_condition,b.density,
            b.unit,b.status,b.grade FROM formulaMatCodes a LEFT JOIN rndMaterial b ON a.material_code = b.material_code WHERE a.plant_id = '".$_GET["plant_id"]."' AND b.material_type = '".$_GET["material_type"]."'  AND b.status = 'Approved'  order by b.material_name ASC";
                
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getRndMaterialsByTypeByfeasibilityFormNo":
            
            $output = Array();
            
            $sql = "SELECT a.id,a.plant_id,b.material_type,a.material_code,b.material_subtype,b.material_name,b.material_name_report,b.material_nature,b.storage_condition,b.density,
            b.unit,b.status,b.grade FROM formulaMatCodes a LEFT JOIN materialMasterViewRndNormal b ON a.material_code = b.material_code WHERE a.plant_id = '".$_GET["plant_id"]."' AND b.material_type = '".$_GET["material_type"]."'  
            AND a.feasibilityFormNo = '".$_GET["feasibilityFormNo"]."'  
            AND b.status = 'Approved'  order by b.material_name ASC";
                
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
            
            
            
            
            
    
            
        case "getMaterialsByType1":
            $output = Array();
            $a=$_GET["material_type"];
             $a;
            //$sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND material_subtype LIKE '%".$_GET["material_subtype"]."'  ORDER BY material_name";
           // $sql = "SELECT * FROM material WHERE plant_id='".$_GET["plant_id"]."' and user_no='".$_GET["user_no"]."' AND status='approve' AND material_subtype LIKE '%".$_GET["material_subtype"]."'  AND material_nature LIKE '%".$_GET["material_nature"]."'   ORDER BY material_name";
               if(empty($_GET["material_type"]) || $_GET["material_type"]=="Raw Material" || $_GET["material_type"]=="Packing Material") {
           
                    $sql = "SELECT *  FROM material WHERE plant_id='".$_GET["plant_id"]."' AND material_subtype like '%".$_GET["material_subtype"]."'";//  ORDER BY material_name";
        //   echo   $sql = "SELECT material.* , grade.grade as gradeName FROM material left join grade on material.grade = grade.id
        //      WHERE material.plant_id='".$_GET["plant_id"]."' AND material_subtype = '".$_GET["material_subtype"]."' ";//  ORDER BY material_name";
           
           
                            //chngr on 14/4/23 by vivek
            //  $sql = "SELECT material.* , grade.grade as gradeName FROM material 
             
            //     left join grade on material.grade = grade.id
            //  WHERE material.plant_id='".$_GET["plant_id"]."' 
            //  AND material_type LIKE '%".$_GET["material_type"]."' 
            //  AND material_subtype LIKE '%".$_GET["material_subtype"]."' ";//  ORDER BY material_name";
               }else{
                       $sql = "select * from(SELECT * FROM general_material  WHERE  plant_id='".$_GET["plant_id"]."' 
         AND material_subtype LIKE '%".$_GET["material_subtype"]."')";
               }
               
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $row["equivalent"] = json_decode($row["equivalent"]);
                     $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row["grade"]."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
           case "getVendor":
               
               $sql="SELECT v. *,a.vendor_material_code FROM mst_vendor_materials a left join vendor v on a.supplier_code=v.vendor_no or a.manufacturer_code=v.vendor_no where a.material_code='".$_GET["material_code"]."'";
               	$result = $conn->query($sql);
            	if($result->num_rows > 0){
	        	$output = Array();
	        	while($row = $result->fetch_assoc()){
	    		$output[] = $row;
		}
	}
	echo json_encode($output);
            break;
            
           case "getMaterialsBySubType":
            $output = Array();
           // $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND material_subtype LIKE '%".$_GET["material_subtype"]."'  ORDER BY material_name";
           // $sql = "SELECT * FROM material WHERE plant_id='".$_GET["plant_id"]."' and user_no='".$_GET["user_no"]."' AND status='approve' AND material_subtype LIKE '%".$_GET["material_subtype"]."'  AND material_nature LIKE '%".$_GET["material_nature"]."'   ORDER BY material_name";
        $sql = "SELECT * FROM material WHERE plant_id='".$_GET["plant_id"]."' and 
      user_no='".$_GET["user_no"]."' AND status='approve' AND material_subtype LIKE '%".$_GET["material_subtype"]."%'    
      ORDER BY material_name";

            //  $sql = "SELECT general_material.*  FROM general_material
                    
            //   WHERE general_material.plant_id='".$_GET["plant_id"]."' 
            //  AND material_type LIKE '%".$_GET["material_type"]."' 
            //  AND material_subtype LIKE '%".$_GET["material_subtype"]."' ";//  ORDER BY material_name";
               
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $row["equivalent"] = json_decode($row["equivalent"]);
                     $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
                    $output[] = $row;
                }
            }
            else{

        //      $sql = "SELECT  material.*  FROM material
        //       LEFT JOIN grade on grade.id = material.grade
        //       WHERE material.plant_id='".$_GET["plant_id"]."' 
        //      AND material_type LIKE '%".$_GET["material_type"]."' 
        //      AND material_subtype LIKE '%".$_GET["material_subtype"]."' ";//  ORDER BY material_name";
               
        //     $result = $conn->query($sql);
        //       if ($result->num_rows > 0) {
        //         while ($row = $result->fetch_assoc()) {
        //             $row = array_map('utf8_encode', $row);
        //             $row["equivalent"] = json_decode($row["equivalent"]);
        //              $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['grade']."')";
        //      $resQ = $conn->query($q);
        //       $prodLatest = $resQ->fetch_assoc(); 
         
        //   $row['gradeName'] = $prodLatest['gradeName']; 
        //             $output[] = $row;
        //         }
        //     }
            
            }
            echo json_encode($output);
            break;
           case "getMaterialsBySubType_GM":
                  
       $output = Array();
        
        $sql = "SELECT * FROM my_view  WHERE plant_id='".$_GET["plant_id"]."' AND 
        material_subtype LIKE '%".$_GET["material_subtype"]."%'  AND material_type LIKE '%".$_GET["material_type"]."%'  ORDER BY material_name";

            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     
                    $row["pack_size"] = json_decode($row["pack_size"]);
                    $row["chem_manufacturer"] = json_decode($row["chem_manufacturer"]);

                     $output[] = $row;
                }
            }
            
            echo json_encode($output);
            
            break;
            
                    case "get_Product_subtype":
            $output = Array();
          
              $sql = "SELECT dosage_form as material_subtype FROM product GROUP by dosage_form; ";
            
    
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
              
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
                    case "get_material_subtype":
            $output = Array();
          
              $sql = "SELECT material_subtype FROM material_type GROUP by material_subtype; ";
            
    
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
              
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
                    case "get_service_subtype":
            $output = Array();
          
              $sql = "SELECT service_type  FROM service WHERE plant_id='".$_GET["plant_id"]."' group by service_type";
            
    
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
              
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
         case "getMaterialsByTypeAndSubType":
            $output = Array();
            //$sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND material_subtype LIKE '%".$_GET["material_subtype"]."'  ORDER BY material_name";
           // $sql = "SELECT * FROM material WHERE plant_id='".$_GET["plant_id"]."' and user_no='".$_GET["user_no"]."' AND status='approve' AND material_subtype LIKE '%".$_GET["material_subtype"]."'  AND material_nature LIKE '%".$_GET["material_nature"]."'   ORDER BY material_name";
             $sql = "SELECT * FROM material WHERE plant_id='".$_GET["plant_id"]."'"; 
             //AND material_type LIKE '%".$_GET["material_type"]."'  
            // AND material_subtype LIKE '%".$_GET["material_subtype"]."'";// ORDER BY material_name";
               
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $row["equivalent"] = json_decode($row["equivalent"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;    
        case "getFgProductsByType":
            $output = Array();
             $sql = "SELECT * FROM master_fg_types WHERE plant_id='".$_GET["plant_id"]."' AND dosage_form_type LIKE '%".$_GET["dosage_form_type"]."'  AND product_nature LIKE '%".$_GET["material_nature"]."'   ORDER BY material_name";
               
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $row["equivalent"] = json_decode($row["equivalent"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;    
        case "getMaterialByType":
            $output = Array();
             $sql = "SELECT * FROM material WHERE   status='Approved' AND material_type='".$_GET["material_type"]."' ORDER BY material_name";
         
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $row["equivalent"] = json_decode($row["equivalent"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
         case "getMaterialsByNature":
            $output = Array();
            $sql = "SELECT * FROM product WHERE user_no='".$_GET["user_no"]."' AND plant_id='".$_GET["plant_id"]."'  AND status='approve' AND product_nature LIKE '%".$_GET["material_nature"]."' ORDER BY product_name";
         
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $row["equivalent"] = json_decode($row["equivalent"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
              case "getMaterialByType":
            $output = Array();
            $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND plant_id='".$_GET["plant_id"]."' AND status='approve' AND material_type='".$_GET["material_type"]."' ORDER BY material_name";
         
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $row["equivalent"] = json_decode($row["equivalent"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;    
            
        case "getVendor":
            $output = Array();
            $sql = "SELECT * FROM material_type"; //WHERE status='approve' AND material_subtype=
            //'".$_GET["material_subtype"]."' AND sub_type='".$_GET["sub_type"]."' 
            //and plant_id = '".$_GET["plant_id"]."' ORDER BY material_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "sub_type":
            $output = Array();
                $sql = "SELECT * FROM material_type WHERE material_type='".$_GET["material_type"]."' AND plant_id =  '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProductsByType":
            $output = Array();
            $sql = "SELECT * FROM material WHERE status='approve' AND material_type='".$_GET["material_subtype"]."' and plant_id = '".$_GET["plant_id"]."' ORDER BY material_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getPackingMaterials":
            $output = Array();
            $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND material_type='Packing Material' AND plant_id='".$_GET["plant_id"]."' ORDER BY material_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getMaterialsFormDeviation":
            $output = Array();
            $sql = "SELECT * FROM material WHERE plant_id='".$_GET["plant_id"]."' ORDER BY material_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                        $sql1 = "SELECT  batch_no  FROM challan_materials WHERE material_code='".$row["material_code"]."' and batch_no is not null group by  batch_no  ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                         $row["batch_nos"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "get_audit_log":
            $output = Array();
             $sql = "SELECT * FROM log where frontend_url!='' ORDER BY actiontime desc";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;    
            
        case "getOperators":
            $output = Array();
            //$sql = "SELECT * FROM labour WHERE user_no='".$_GET["user_no"]."'  AND category='Operator' 
           // AND status='approve' AND plant_id='".$_GET["plant_id"]."' ORDER BY labour_name";
           $sql = "SELECT * FROM employee WHERE operator_category='Worker / Operator' AND status='Active' AND plant_id='".$_GET["plant_id"]."' ORDER BY firstname";
           
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;

        case "getOperatorsWorkerList":
                $output = Array();
                //$sql = "SELECT * FROM labour WHERE user_no='".$_GET["user_no"]."'  AND category='Operator' 
               // AND status='approve' AND plant_id='".$_GET["plant_id"]."' ORDER BY labour_name";
               $sql = "SELECT * FROM employee WHERE status='Active' and department='Quality Control'  AND plant_id='".$_GET["plant_id"]."' ORDER BY firstname";
            //   $sql = "SELECT * FROM employee WHERE status='Active' and department='Quality Control' AND operator_category='Worker / Operator' AND plant_id='".$_GET["plant_id"]."' ORDER BY firstname";
               
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
                break;
                 case "getOperatorsWorker":
                $output = Array();
                //$sql = "SELECT * FROM labour WHERE user_no='".$_GET["user_no"]."'  AND category='Operator' 
               // AND status='approve' AND plant_id='".$_GET["plant_id"]."' ORDER BY labour_name";
               $sql = "SELECT * FROM employee WHERE status='Active' and department='Quality Control'  AND plant_id='".$_GET["plant_id"]."' ORDER BY firstname";
            //   $sql = "SELECT * FROM employee WHERE status='Active' and department='Quality Control' AND operator_category='Worker / Operator' AND plant_id='".$_GET["plant_id"]."' ORDER BY firstname";
               
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
                break;
        case "getUsers":
                $output = Array();
   
               $sql = "SELECT * FROM employee WHERE status='Active' AND plant_id='".$_GET["plant_id"]."' ORDER BY firstname asc";

                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
                break;
        case "getemployee":
                $output = Array();
   
               $sql = "SELECT * FROM employee WHERE status='Active' AND plant_id='".$_GET["plant_id"]."' ORDER BY firstname asc";

                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
                break;
        case "getprodOperatorsWorkerList":
                $output = Array();
                //$sql = "SELECT * FROM labour WHERE user_no='".$_GET["user_no"]."'  AND category='Operator' 
               // AND status='approve' AND plant_id='".$_GET["plant_id"]."' ORDER BY labour_name";
               $sql = "SELECT * FROM employee WHERE status='Active' and department='Production' AND operator_category='Worker / Operator' AND plant_id='".$_GET["plant_id"]."' ORDER BY firstname";
               
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
                break;
        case "getOperatorsWorker":
                $output = Array();
                //$sql = "SELECT * FROM labour WHERE user_no='".$_GET["user_no"]."'  AND category='Operator' 
               // AND status='approve' AND plant_id='".$_GET["plant_id"]."' ORDER BY labour_name";
               $sql = "SELECT * FROM employee WHERE (operator_category='Worker / Operator'  or  operator_category='employee' or operator_category='QC Chemist')   AND status='Active' AND plant_id='".$_GET["plant_id"]."' ORDER BY firstname";
               
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
                break;
            
        case "getLabours":
            $output = Array();
            $plantId = isset($_GET["plant_id"]) ? $conn->real_escape_string($_GET["plant_id"]) : '';
            $sql = "SELECT * FROM labour WHERE category='Labour'
                AND (status='approve' OR status='Approved' OR status='active' OR status='Active')
                AND plant_id='".$plantId."' ORDER BY labour_name";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        
        case "getStages":
            $output = Array();
            $sql = "SELECT * FROM stages WHERE status='approve'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        
         case "get_qc_testing_persons":
             $sql = "SELECT emp_id,firstname,middlename,lastname  FROM employee WHERE department='Quality Control' AND designation !='Manager'";
             $output = array();
            //$sql = "SELECT * FROM employee WHERE user_no='".$_GET["user_no"]."' AND status='active' AND department='Store'  AND plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
          case "getStoreEmployee":
            $output = array();
            $sql = "SELECT * FROM employee WHERE user_no='".$_GET["user_no"]."' AND status='active' AND department='Store'  AND plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;  
        
            
            
        case "getUnits":
            $output = Array();
            $sql = "SELECT * FROM unit  WHERE plant_id = '".$_GET["plant_id"]."' ";
             $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    //$row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getClients":
            $output = array();
            $sql = "SELECT id,plant_id,client_code,status,LglNm,TrdNm,client_type,category FROM client WHERE status = 'Active' ORDER BY LglNm ASC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getClientsSubGroup":
            
            $output = array();
           $sql = "SELECT id,plant_id,client_code,status,LglNm,TrdNm,client_type,category FROM client where clientGroup = '".$_GET['emp_id']."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            
            echo json_encode($output);
            break;
            
        case "getClientsforMarketing":
            $output = array();
            $sql = "SELECT id,plant_id,client_code,status,LglNm,TrdNm,client_type,category FROM client WHERE status = 'Active' ORDER BY LglNm ASC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
        case "getVendors":
            $output = array();
             $sql = "SELECT * FROM vendor WHERE status='Approved'  AND plant_id='".$_GET["plant_id"]."'  ORDER BY vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getVendorsByMatType":
            $output = array();
             $sql = "SELECT `id`, `plant_id`, `status`, `vendor_no`, `material_type`, `vendor_type`, `vendor_name` FROM vendor WHERE status = 'Approved'  AND material_type = '".$_GET["material_type"]."' AND plant_id='".$_GET["plant_id"]."'  ORDER BY vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getVendorsByMatTypeForGenOpening":
            $output = array();
             $sql = "SELECT `id`, `plant_id`, `status`, `vendor_no`, `material_type`, `vendor_type`, `vendor_name` FROM vendor WHERE status = 'Approved'  AND material_type NOT IN ('Raw Material', 'Packing Material') AND plant_id='".$_GET["plant_id"]."'  ORDER BY vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        
        case "getClientBranches":
            $output = array();
            $sql = "SELECT * FROM client WHERE client_code='".$_GET["client_code"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output); 
         break;
         
        case "getGrades":
            $output = array();
            $sql = "SELECT * FROM grade Where  plant_id='".$_GET["plant_id"]."'  order by id desc";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
              case "getGrades1":
            $output = array();
            $sql = "SELECT * FROM spcl_grade Where  plant_id='".$_GET["plant_id"]."'  order by id desc";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
         case "getStorageConditions":
         case "get_storage_conditions":
            $output = array();
            $plant_id = isset($_GET["plant_id"]) ? trim($_GET["plant_id"]) : '';
            if ($plant_id !== '') {
                $pid = $conn->real_escape_string($plant_id);
                $sql = "SELECT * FROM storage_conditions WHERE CAST(plant_id AS CHAR)='".$pid."' ORDER BY id DESC";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
            }
            if (count($output) === 0) {
                $sql = "SELECT * FROM storage_conditions ORDER BY id DESC";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
            }
            safe_json_echo($output);
            break;
            
        case "getDesignationHeading":
            $output = array();
            $sql = "SELECT * FROM designation";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        
        case "getQualifications":
            $output = array();
            $sql = "SELECT * FROM qualification WHERE status='active'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getChemicals":
            $output = array();
            $sql = "SELECT * FROM chemical WHERE user_no='".$_GET["user_no"]."' AND status='approve'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["make"]=json_decode($row["make"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
            case "getChemicalsForMoa":
            $output = array();
             $sql = "SELECT a.*, (SELECT SUM(qty) FROM engi_stock WHERE material_code = a.material_code) AS total_quantity_in_stock FROM
            others_material   a  where (a.material_subtype = 'Chemicals' or a.material_subtype='Reagents')";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["make"]=json_decode($row["make"]);
                       $output1 = Array();
                        $sql1 = "SELECT batch_no FROM engi_stock WHERE material_code='".$row["material_code"]."'   ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                            $row["batch_no"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getGlasswares":
            $output = array();
            $sql = "SELECT * FROM glassware WHERE user_no='".$_GET["user_no"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
     
            
        case "getEquipments":
            $output = array();
            $sql = "SELECT * FROM equipment WHERE plant_id='".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "get_BalanceEquipments":
            $output = array();
            $sql = "SELECT * FROM equipment WHERE plant_id='".$_GET["plant_id"]."' AND   department='".$_GET["depart"]."'   AND   equipment_type='Balance(Weighing)'   Order By equipment_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "get_Equipments":
            $output = array();
            $sql = "SELECT * FROM equipment WHERE plant_id='".$_GET["plant_id"]."' AND   department='".$_GET["depart"]."'  Order By equipment_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
           case "get_save_equipment_usage_cleaning_recordLog" :
                $output = Array();
                 $sql = "SELECT * FROM equipment_usage_cleaning_record where  department='".$_GET['depart']."' and activity='".$_GET['Activity_type']."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
           break;
           case "get_save_equipment_usage_cleaning_record" :
                $output = Array();
                 $sql = "SELECT * FROM equipment_usage_cleaning_record where status='pending' and department='".$_GET['depart']."' and activity='".$_GET['Activity_type']."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
           break;
        case "get_Equipments_sampling2":
            $output = array();
            $sql = "SELECT id,plant_id,equipment_code,equipment_name,department,location,status FROM equipment WHERE   status='Active' AND department = 'Quality Control' AND equipment_type LIKE '%samp%' AND plant_id = '".$_GET["plant_id"]."'"; 
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getLafForSamp":
            $output = array();
            $sql = "SELECT id,plant_id,equipment_code,equipment_name,department,location,status FROM equipment WHERE   status='Active' AND department = 'Quality Control' AND ( equipment_type LIKE '%LAF%' OR equipment_type LIKE '%RLAF%' ) AND plant_id = '".$_GET["plant_id"]."'"; 
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "get_Equipments_sampling":
            $output = array();
            $sql = "SELECT id,plant_id,equipment_code,equipment_name,department,location,status FROM equipment WHERE   status='Active' AND department = 'Quality Control' AND equipment_type LIKE '%samp%' AND plant_id = '".$_GET["plant_id"]."'"; 
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getLabs":
            $output = array();
            $sql = "SELECT * FROM labs WHERE user_no='".$_GET["user_no"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getTestingPersons":
            $output = array();
            $sql = "SELECT id,status,designation,operator_category,department,lastname,middlename,firstname,emp_id,plant_id FROM employee 
            WHERE plant_id='".$_GET["plant_id"]."' AND status='active' AND ( department='Quality Control' OR department='master' )";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getProductionPersons":
            $output = array();
            $sql = "SELECT * FROM employee WHERE user_no='".$_GET["user_no"]."' AND status='active' AND department='PRODUCTION'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
         case "getMicrobiologyPersons":
            $output = array();
            $sql = "SELECT id,CONCAT(`firstname`, ' ', `lastname`) as emp_name,emp_id FROM employee WHERE user_no='".$_GET["user_no"]."' AND status='active' AND department='Microbiology'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;    
        
        case "getBanks":
            $output = array();
            $sql = "SELECT * FROM banks WHERE user_no='".$_GET["user_no"]."' 
            GROUP BY bank_name ORDER BY bank_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT * FROM banks WHERE user_no='".$_GET["user_no"]."' 
                    AND bank_name='".$row["bank_name"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output2 = array();
                            $sql2 = "SELECT * FROM checkbook WHERE 
                            user_no='".$_GET["user_no"]."' AND account_no='".$row1["account_no"]."'
                            GROUP BY book_no";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                    $output3 = array();
                                    $sql3 = "SELECT * FROM checkbook WHERE user_no='".$_GET["user_no"]."' 
                                    AND account_no='".$row1["account_no"]."' AND book_no='".$row2["book_no"]."' AND status='pending'";
                                    $result3 = $conn->query($sql3);
                                    if ($result3->num_rows > 0) {
                                        while ($row3 = $result3->fetch_assoc()) {
                                            $output3[] = $row3;
                                        }
                                    }
                                    $row2["cheques"] = $output3;
                                    $output2[] = $row2;
                                }
                            }
                            $row1["chequebooks"] = $output2;
                            $output1[] = $row1;
                        }
                    }
                    $row["accounts"] = $output1;
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getGST":
            $output = array();
            $sql = "SELECT * FROM gst_per  order by 1 desc";
            // $sql = "SELECT * FROM gst_per where  plant_id='".$_GET["plant_id"]."'  order by 1 desc";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getCess":
            $output = array();
            $sql = "SELECT * FROM cess_per";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getStates":
            $output = Array();
            $sql = "SELECT state_name FROM state ";//where plant_id ='".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            
            echo json_encode($output);
            break;
            
        case "getUnitsList":
            $output = Array();
            $sql = "SELECT * FROM unitformula where  plant_id='".$_GET["plant_id"]."' " ;
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
          case "getUnits_List":
            $output = Array();
             $sql = "SELECT * FROM unit  where  plant_id='".$_GET["plant_id"]."'    order by unit";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getCountries":
            $output = Array();
            $sql = "SELECT * FROM countries";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getPlantdetail":
            $output = Array();
            $sql = "SELECT * FROM plant where plant_id='".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
 
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getPorts":
            $output = Array();
            $sql = "SELECT * FROM ports";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo $row["port_code"]."<br>";
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getNonTechnicalDepartments":
            $output = Array();
            $sql = "SELECT * FROM department WHERE department_type='Non Technical'  AND  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getTechnicalDepartments":
            $output = Array();
            $sql = "SELECT * FROM department WHERE department_type='Technical' AND  plant_id='".$_GET["plant_id"]."'  ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getFG_type":
            $output = array();
            $sql = "SELECT dosage_form_type FROM master_fg_types GROUP by dosage_form_type";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        
        case "getProductStock":
            $output = Array();
            $sql = "SELECT f.*, p.product_name, p.grade, p.dosage_form FROM finish_product f LEFT JOIN product p ON f.product_code=p.product_code WHERE f.user_no='".$_GET["user_no"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT mrp, rate FROM product_mrp WHERE product_code='".$row["product_code"]."' ORDER BY id DESC LIMIT 1";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["mrp"] = $row1["mrp"];
                            $row["rate"] = $row1["rate"];
                        }
                    }
                    
                    $row["stock_value"] = +$row["rate"] * +$row["qty"];
                    $row["stock_value"] = number_format($row["stock_value"], 2);
                    
                    $row["mrp_value"] = +$row["mrp"] * +$row["qty"];
                    $row["mrp_value"] = number_format($row["mrp_value"], 2);
                    
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
   
            
        case "getCompanyAddresses":
            $output = array();
              $sql = "SELECT * FROM addresses   order by 1 desc";
            //$sql = "SELECT * FROM addresses";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
         case "getEngineeringVendors":
            $output = array();
            $sql = "SELECT * FROM vendor WHERE material_type='Engineering' AND vendor_status='Approved'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
        case "getCommonDetails":
            $data = array();
            $output = Array();
            $sql = "SELECT * FROM department  order by department_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT id,section_name FROM section WHERE department='".$row["department_name"]."' AND plant_id ='".$_GET["plant_id"]."' order by section_name";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["sections"] = $output1;
                  //  $output[] = $row;
                    
                     $output2 = Array();
                    $sql2 = "SELECT id,designation FROM designation WHERE dept_id='".$row["id"]."' AND plant_id ='".$_GET["plant_id"]."' order by designation";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output2[] = $row2;
                        }
                    }
                    $row["designations"] = $output2;
                    $output[] = $row;
                }
            }
            $data["departments"] = $output;
            
         
            $output = Array();
            $sql = "SELECT * FROM unit";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            $data["units"] = $output;
             
            
            $output = array();
            $sql = "SELECT * FROM grade where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["grades"] = $output;
          
          
            $output = array();
            $sql = "SELECT max(id) as id, material_type FROM material_type where plant_id = '".$_GET["plant_id"]."' group by material_type order by material_type";
            $result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT max(id) as id,material_subtype FROM material_type WHERE material_type='".$row["material_type"]."' and plant_id = '".$_GET["plant_id"]."' Group By material_subtype order by material_subtype ";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output2 = Array();
                                $sql2 ="SELECT id FROM material_type WHERE material_type = '".$row["material_type"]."'  AND  material_subtype='".$row1["material_subtype"]."' and plant_id = '".$_GET["plant_id"]."' order by material_type ";
                                
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $output2[] = $row2;
                                    }
                                }   
                                $row1['categories'] = $output2;
                                $output1[] = $row1;
                            }
                        }
                        $row["sub_materials"] = $output1;
                        $output[] = $row;
                }
            }
             $data["material_types"] = $output;
             
              
            echo json_encode($data);
            break;
        case "ForHrgetCommonDetails":
            $data = array();
            $output = Array();
            $sql = "SELECT * FROM department  order by department_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = Array();
                    $sql1 = "SELECT id,section_name FROM section WHERE department='".$row["department_name"]."' AND plant_id ='".$_GET["plant_id"]."' order by section_name";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $output1[] = $row1;
                        }
                    }
                    $row["sections"] = $output1;
                  //  $output[] = $row;
                    
                     $output2 = Array();
                    $sql2 = "SELECT id,designation FROM designation WHERE dept_id='".$row["id"]."' AND plant_id ='".$_GET["plant_id"]."' order by designation";
                    $result2 = $conn->query($sql2);
                    if ($result2->num_rows > 0) {
                        while ($row2 = $result2->fetch_assoc()) {
                            $output2[] = $row2;
                        }
                    }
                    $row["designations"] = $output2;
                    $output[] = $row;
                }
            }
            $data["departments"] = $output;
            
         
        
            $data["products"] = [];
            
            $output = Array();
            $sql = "SELECT * FROM unit";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            $data["units"] = $output;
            
            $output = array();
            $sql = "SELECT * FROM state";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["states"] = $output;
            
            $output = array();
            $sql = "SELECT * FROM grade where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["grades"] = $output;
            
       
            
            $output = array();
            $sql = "SELECT * FROM state";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["states"] = $output;
            
          
            
            $output = array();
            $sql = "SELECT * FROM gst_per where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["gst"] = $output;
            
             
             
              
            
            
            echo json_encode($data);
            break;
            
        case "getSolvents":
            $output = array();
            $sql = "SELECT * FROM material WHERE material_subtype='Solvents'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "get_prod_data":
            $output = array();
            $sql = "SELECT a.*, b.mrp FROM product a LEFT JOIN salesmrp b on a.product_code=b.product_code and a.manufactured_for=b.client_code where a.manufactured_for='".$_GET["cliant"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["amount_usd"]=0;
                    $row["amount_inr"]=0;
                    $row["qty"]=0;
                    $row["currency"]='';
                    $row["rate"]=0;
                  
                    $output[] = $row;
                    
                }
            }
            echo json_encode($output);
            break;
        case "getSubMaterials":
           
             $output = array();
            $sql = "SELECT max(id) as id, material_type FROM material_type where plant_id = '".$_GET["plant_id"]."' group by material_type order by material_type";
            $result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT max(id) as id,material_subtype FROM material_type WHERE material_type='".$row["material_type"]."' and plant_id = '".$_GET["plant_id"]."' Group By material_subtype order by material_subtype ";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output2 = Array();
                                $sql2 ="SELECT id FROM material_type WHERE material_type = '".$row["material_type"]."'  AND  material_subtype='".$row1["material_subtype"]."' and plant_id = '".$_GET["plant_id"]."' order by material_type ";
                                
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $output2[] = $row2;
                                    }
                                }   
                                $row1['categories'] = $output2;
                                $output1[] = $row1;
                            }
                        }
                        $row["sub_materials"] = $output1;
                        $output[] = $row;
                }
            }
             $data["material_types"] = $output;
            break;
            
        default:
            echo "Invalid Type";
            break;
    }

}else{
     echo "Invalid Type";
}

$conn->close();
?>