<?php
require '../db.php';
require '../token.php';

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
  $currentUrl =$_GET["description"];


$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);
// print_r($_GET);exit;
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
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
   $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES
   ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."',
   '".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
   
    $conn->query($sql);
    
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
             $sql = "SELECT vendor_no as Key_Id,vendor_name as Key_Name  FROM vendor v  WHERE v.status='approve' ORDER BY v.vendor_name";
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
                  case "save_chem_mfg":
        $sql="Insert into  chem_vendor(mfg_name) values('".$input["mfg_name"]."')";   
       
        if ($conn->query($sql)) {
    
            
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    
            break;
              case "getChemicalManufacturer" :   
            $output = Array();
            $sql = "SELECT * FROM  chem_vendor";
            // $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer' AND material_type='Chemical Material'             and plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
                    case "getInprocess_Materials":
            $output = Array();
            $sql = "SELECT * FROM inprocess_yeild";
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

                  
                  
                  if($row['avaliable_stock'] >= $row['inventory']){
                      
                      
                      $row['inventory_status'] = 'OK';
                  }else{
                      $row['inventory_status'] = 'LOW';
                  }
                  
                  
                  
                $sql22 = "SELECT AVG(pm.qty) AS  average_pur FROM po_material pm JOIN purchaseorder po ON pm.po_no = po.id WHERE 
                pm.material_code = '".$row['material_code']."' AND po.entry_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 MONTH)";
                $result22 = $conn->query($sql22);
                if ($result22->num_rows > 0) {
                    while ($row22 = $result22->fetch_assoc()) {
                         $row['average_pur'] = $row22['average_pur'];
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
        case "getbal_id":
            $output = Array();
        $sql = "SELECT * FROM equipment WHERE department='".$_GET["department1"]."' and location='".$_GET["location"]."' ";
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
            $sql1 = "SELECT * FROM section WHERE department='".$_GET["department1"]."'";
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
            $sql = "SELECT * FROM vendor WHERE vendor_type='".$_GET["vendor_type"]."' AND status='approved'   and plant_id= '".$_GET["plant_id"]."'";
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
        
        case "getManufacturers":
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Manufacturer' AND  vendor_status='Approved'   and plant_id= '".$_GET["plant_id"]."'";
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
            case "getSupplier" :   
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE vendor_type='Supplier'  and plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
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
            echo json_encode($output);
            break;
            case "getChemicalManufacturer" :   
            $output = Array();
            $sql = "SELECT * FROM vendor WHERE    plant_id= '".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    $output[] = $row;
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
                $sql = "SELECT * FROM vendor WHERE vendor_type='".$_GET["vendor_type"]."'  AND vendor_status='Approved' 
            AND material_type='".$_GET["material_type"]."' and plant_id= '".$_GET["plant_id"]."' ";
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
            $sql = "SELECT * FROM vendor WHERE material_type='" . $_GET["material_type"] . "' AND plant_id='" . $_GET["plant_id"] . "' ORDER BY vendor_name";
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
            $sql1 = "SELECT * FROM shelf_life";
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
             $sql = "SELECT DISTINCT v.vendor_no, v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
            WHERE v.status='approved' ORDER BY v.vendor_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
         case "getvendorByType":
            $output = array();
            
             $sql = "SELECT * FROM vendor WHERE status='approved' AND vendor_type = '".$_GET["vendor_type"]."' AND plant_id= '".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            
            echo json_encode($output);
            break;
            
          case "getVendorByMaterialType":
            $output = array();
            
            if(isset($_GET["typev"]))
            {            
        $sql = "SELECT  DISTINCT v.id,  v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
            WHERE  v.material_type like'%".$_GET["material_type"]."'
            and v.plant_id= '".$_GET["plant_id"]."'
            and (v.vendor_type='Manufacturer' or v.vendor_type='Supplier' or  v.vendor_type='Service') ";//ORDER BY v.vendor_name";
            
            }
            else{
                
          $sql = "SELECT DISTINCT v.id, v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
            WHERE v.status='approve' or v.status='approved' AND v.material_type like'%".$_GET["material_type"]."'
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
             $sql = " SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
            WHERE v.plant_id= '".$_GET["plant_id"]."'";//ORDER BY v.vendor_name";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["units"] = json_decode($row["units"]);
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
           
        case "getVendorByMaterialCode":
            $output = array();
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code 
            WHERE v.status='approve' AND  v.vendor_no in(select supplier_code from mst_vendor_materials plant_id= '".$_GET["plant_id"]."' and material_code =  )
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
            WHERE v.status='approve' AND v.material_type='Raw Material' ORDER BY v.vendor_name";
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
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='approve' AND v.material_type='Packing Material' ORDER BY v.vendor_name";
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
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='approve' AND v.material_type='General Material' ORDER BY v.vendor_name";
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
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='approve' AND v.material_type='Chemical Material' ORDER BY v.vendor_name";
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
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='approve' AND v.material_type='Glassware Material' ORDER BY v.vendor_name";
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
            $sql = "SELECT v.*, v.state_name FROM vendor v LEFT JOIN state s ON v.state_code=s.state_code WHERE v.status='approve' AND v.material_type='Finish Product' ORDER BY v.vendor_name";
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
            $sql1 = "SELECT a.*,b.unit FROM pack_size a JOIN unit b on a.unit_id = b.id
                 where  a.plant_id='".$_GET["plant_id"]."'  order by a.id desc";
            
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
            $sql1 = "SELECT a.*,b.unit FROM pack_size a join unit  b on a.unit_id = b.id  where  a.plant_id='".$_GET["plant_id"]."'  order by a.id desc";
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
            $sql = "SELECT product_name,product_code1,grade FROM product where  plant_id='".$_GET["plant_id"]."' and product_type='".$_GET["product_type"]."' ";
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
            
        case "getProducts":
            $output = Array();
           // $sql = "SELECT p.product_code, p.product_name , p.grade, p.shelf_life, p.hsn, p.gst,p.packing_style,m.mrp FROM product p
            $sql = "SELECT p.*,m.mrp FROM product p
            LEFT JOIN mrp m ON p.product_code=m.product_code WHERE user_no='".$_GET["user_no"]."' AND p.status='approve' AND p.plant_id='".$_GET["plant_id"]."'
            ORDER BY product_name";
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
        case "getProducts_for_client":
            $output = Array();
           // $sql = "SELECT p.product_code, p.product_name , p.grade, p.shelf_life, p.hsn, p.gst,p.packing_style,m.mrp FROM product p
            $sql = "SELECT * ,a.product_code as a_product_code FROM product a LEFT JOIN ratemrp b ON a.manufactured_for = b.client_code
                WHERE a.manufactured_under != 'Own' and a.product_code NOT IN (SELECT product_code FROM ratemrp) and a.manufactured_for='".$_GET["client_code"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // $output1 = array();
                    // $sql1 = "SELECT * FROM product_mrp WHERE product_code='".$row["product_code"]."' ORDER BY id DESC LIMIT 1";
                    // $result1 = $conn->query($sql1);
                    // if ($result1->num_rows > 0) {
                    //     while ($row1 = $result1->fetch_assoc()) {
                    //         $row["mrp"] = $row1["mrp"];
                    //         $row["rate"] = $row1["rate"];
                    //     }
                    // }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
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
            
        case "getMaterialsByTypes":
            $output = Array();
           // $sql = "SELECT p.product_code, p.product_name , p.grade, p.shelf_life, p.hsn, p.gst,p.packing_style,m.mrp FROM product p
// $sql="SELECT * FROM `material` WHERE material_type='' and plant_id='59'"; 
$sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."'   AND material_type='Raw Material' ORDER BY material_name";

 
 $result = $conn->query($sql);
            if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              
                $output[] = $row;
            }
        }
            echo json_encode($output);
            break;
            
        case "getProductsByDosage":
            $output = Array();
            $sql = "SELECT DISTINCT p.dosage_form, p.*,m.stage as production_stages,ps.stage as packing_stages FROM product p LEFT JOIN manufacturing_process m ON p.dosage_form=m.dosage_form left join packing_manufacturing_process  ps on p.dosage_form=ps.dosage_form WHERE     p.plant_id='".$_GET["plant_id"]."' AND p.product_type='".$_GET["product_type"]."' or p.dosage_form='".$_GET["product_type"]."'
            ";
            // $sql = "SELECT DISTINCT p.dosage_form, p.*,m.stage FROM product p LEFT JOIN manufacturing_process m ON p.dosage_form=m.dosage_form WHERE  p.product_type='".$_GET["product_type"]."' or p.dosage_form='".$_GET["product_type"]."'
            // AND  p.plant_id='".$_GET["plant_id"]."' ";
            
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
            
        case "getProductsByDosage1":
            $output = Array();
            $sql = "SELECT DISTINCT p.dosage_form, p.*,m.stage as production_stages,ps.stage as packing_stages FROM product p LEFT JOIN
            manufacturing_process m ON p.dosage_form=m.dosage_form left join packing_manufacturing_process  ps on p.dosage_form=ps.dosage_form WHERE
            p.plant_id='".$_GET["plant_id"]."'";
            // $sql = "SELECT DISTINCT p.dosage_form, p.*,m.stage FROM product p LEFT JOIN manufacturing_process m ON p.dosage_form=m.dosage_form WHERE  p.product_type='".$_GET["product_type"]."' or p.dosage_form='".$_GET["product_type"]."'
            // AND  p.plant_id='".$_GET["plant_id"]."' ";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // $sql1 = "SELECT min(batch_size) as min_batch_size, max(batch_size) as max_batch_size 
                    // FROM batch_formula WHERE product_code='".$row["product_code"]."'";
                    // $result1 = $conn->query($sql1);
                    // if ($result1->num_rows > 0) {
                    //     while ($row1 = $result1->fetch_assoc()) {
                    //         $row["min_batch_size"] = $row1["min_batch_size"];
                    //         $row["max_batch_size"] = $row1["max_batch_size"];
                    //     }
                    // } else {
                    //     $row["min_batch_size"] = 0;
                    //     $row["max_batch_size"] = 0;
                    // }
                    
                    // $output1 = array();
                    // $sql1 = "SELECT * FROM product_mrp WHERE product_code='".$row["product_code"]."'
                    // ORDER BY id DESC LIMIT 1";
                    // $result1 = $conn->query($sql1);
                    // if ($result1->num_rows > 0) {
                    //     while ($row1 = $result1->fetch_assoc()) {
                    //         $row["mrp"] = $row1["mrp"];
                    //     }
                    // }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
            
            
            
        case "getTests":
            $output = Array();
            $sql = "SELECT * FROM test WHERE test_type='".$_GET["test_type"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output1 = array();
                    $sql1 = "SELECT * FROM subtest WHERE test_type='".$_GET["test_type"]."'";
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
                            WHERE a.product_code='".$_GET["product_code"]."' and b.test_type='IPQC' ";
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
            $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND material_type='Raw Material' ORDER BY material_name";
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
         
            if($_GET["table"]=="material" && $_GET["sub_types"]=='Raw Material'){
                  $sql = "select * from ".$_GET["table"]." where ( material_subtype = '".$_GET["sub_types"]."'  or material_type = '".$_GET["sub_types"]."') and plant_id='".$_GET["plant_id"]."'";
            }
           
           else if($_GET["table"]=="material" && $_GET["sub_types"]=='Packing Material'){
                  $sql = "select * from ".$_GET["table"]." where material_subtype='".$_GET["sub_types"]."'  and plant_id='".$_GET["plant_id"]."' ";
            }
           else if($_GET["table"]=="general_material" && $_GET["sub_types"]=='General Material'){
                  $sql = "select * from ".$_GET["table"]." where general_material_type='".$_GET["sub_types"]."'   and plant_id='".$_GET["plant_id"]."'";
            }
           else if($_GET["table"]=="general_material" && $_GET["sub_types"]=='Engineering Spares'){
                  $sql = "select * from ".$_GET["table"]." where general_material_type='".$_GET["sub_types"]."'  and plant_id='".$_GET["plant_id"]."' ";
            }
           else if($_GET["table"]=="chemical"){
                  $sql = "select * from ".$_GET["table"]."  ";
            }
           else if($_GET["table"]=="equipment" && $_GET["sub_types"]==''){
                  $sql = "select * from ".$_GET["table"]." where plant_id='".$_GET["plant_id"]."' ";
            }
           else if($_GET["table"]=="glassware" && $_GET["sub_types"]==''){
                  $sql = "select * from ".$_GET["table"]." where plant_id='".$_GET["plant_id"]."' ";
            }
           else if($_GET["table"]=="product" && $_GET["sub_types"]==''){
                  $sql = "select * from ".$_GET["table"]." where plant_id='".$_GET["plant_id"]."' ";
            }
         
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    
                if($_GET["sub_types"]=='Packing Material' ||  $_GET["sub_types"]=='Raw Material'){
                    
                $output1 = Array();
                $sql1 = "SELECT v.vendor_name,v.vendor_no FROM mst_vendor_materials m left join vendor v on m.supplier_code = v.vendor_no OR m.manufacturer_code = v.vendor_no
                where m.material_code = '".$row['material_code']."' AND m.plant_id='".$_GET["plant_id"]."' ";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }else{
                     $sql1 = "SELECT vendor_name,vendor_no FROM vendor  where plant_id='".$_GET["plant_id"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                             $output1[] = $row1;
                        }
                    }
                    
                    
                    
                }
                    
                    
                    $row['vendors'] = $output1;
                    
                    
                }else{
                    
                    $output1 = Array();
                    $sql1 = "SELECT vendor_name,vendor_no FROM vendor  where plant_id='".$_GET["plant_id"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                             $output1[] = $row1;
                        }
                    }
                        
                        
                    $row['vendors'] = $output1;
                    
                    
                }
                    
                     $row['pack_size']='';
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getMaterialsByType":
            
            $output = Array();
            $a=$_GET["material_type"];
            $a;

              
            if ($_GET["material_subtype"]=="Service Provider"){
                
                $sql = "SELECT *  FROM service WHERE plant_id='".$_GET["plant_id"]."'";
                
            }
            else  if(empty($_GET["material_type"]) || $_GET["material_type"]=="Raw Material" || $_GET["material_type"]=="Packing Material") {
                
                $sql = "SELECT *  FROM material WHERE plant_id='".$_GET["plant_id"]."' AND material_subtype like 
                '".$_GET["material_subtype"]."%' ";
                
            } 
            else if( $_GET["material_type"]=="Engineering" || $_GET["material_type"]=="Microbiology Materials" || $_GET["material_type"]=="QC Material"
            || $_GET["material_type"]=="Manufacturing Materials"|| $_GET["material_type"]=="General Material")
            {
                $sql = "select *  FROM others_material  WHERE  plant_id='".$_GET["plant_id"]."' 
               AND material_subtype LIKE '%".$_GET["material_subtype"]."'";
            }
               
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
                    
                    if($row["equivalent"] !=Null && $row['grade'] !=Null){
                    $row["equivalent"] = json_decode($row["equivalent"]);
             
                    }
          
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            
            
            
            
            break;
        case "getMaterialsByTypeforspec":
            $output = Array();
            $a=$_GET["material_type"];
             $a;
              
            if(empty($_GET["material_type"]) || $_GET["material_type"]=="Raw Material" || $_GET["material_type"]=="Packing Material") {

              $sql = "SELECT *  FROM material WHERE plant_id='".$_GET["plant_id"]."' AND material_type like '".$_GET["material_type"]."%' ";
            
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
        case "get_service_Name":
            $output = Array();
          
              $sql = "SELECT *  FROM service WHERE plant_id='".$_GET["plant_id"]."' and service_type='".$_GET["material_subtype"]."'";
            
    
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row = array_map('utf8_encode', $row);
              
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
                  echo    $sql = "select * from(SELECT * FROM general_material  WHERE  plant_id='".$_GET["plant_id"]."' 
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
           case "getallmanufacturing":
               
        $output = Array();
        
        
            $sql = "SELECT * FROM vendor WHERE vendor_status='Approved' AND  plant_id= '".$_GET["plant_id"]."' ";

               
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
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
            $sql = "SELECT * FROM material WHERE user_no='".$_GET["user_no"]."' AND status='approve' AND material_type='".$_GET["material_type"]."' ORDER BY material_name";
         
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
                $sql = "SELECT material_subtype FROM material_type WHERE material_type='".$_GET["material_type"]."' ";
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
        case "get_audit_log":
            $output = Array();
        // echo     $sql = "SELECT a.*,b.firstname,b.lastname FROM log a left join employee b on a.emp_id=b.emp_id where a.frontend_url!='' and a.actiontime between like '%".$_GET["from_date"]."%' and '%".$_GET["to_date"]."%' and 
        //             department like '%".$_GET["department1"]."%' and emp_id= like '%".$_GET["emp_name"]."%'  ORDER BY actiontime desc ";
           $sql = "SELECT a.*, b.firstname, b.lastname 
        FROM log a 
        LEFT JOIN employee b ON a.emp_id = b.emp_id 
        WHERE a.frontend_url != '' 
          AND a.actiontime BETWEEN '" . $_GET["from_date"] . " 00:00:00' AND '" . $_GET["to_date"] . " 23:59:59' 
          AND a.department LIKE '%" . $_GET["department1"] . "%' 
          AND a.emp_id LIKE '%" . $_GET["emp_name"] . "%'  and
        a.emp_id!=''";
$result = $conn->query($sql);

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
            
            $sql = "SELECT * FROM labour WHERE category='Labour' AND status='approve' AND plant_id='".$_GET["plant_id"]."' ORDER BY labour_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
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
            $sql = "SELECT * FROM unit  WHERE plant_id='".$_GET["plant_id"]."' ";
            //$sql = "SELECT * FROM unitformula  WHERE plant_id='".$_GET["plant_id"]."' ";
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
               $sql = "SELECT * FROM client ";
            
            $result = $conn->query($sql);
            // print_r($result);exit;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $row["branch"] = json_decode($row["branch"]);
                     if ($row["state_code"] == $row["company_state"]) {
                         $row["gst_type"] = "GST";
                     } else {
                         $row["gst_type"] = "IGST";
                     }
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
            
            
        case "getVendors":
            $output = array();
             $sql = "SELECT * FROM vendor WHERE status='approved'  AND plant_id='".$_GET["plant_id"]."'  ORDER BY vendor_name";
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
            $sql = "SELECT * FROM client_branch WHERE client_code='".$_GET["client_code"]."' ";
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
            $sql = "SELECT * FROM grade order by id desc";
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
            $output = array();
            $sql = "SELECT * FROM storage_conditions  Where  plant_id='".$_GET["plant_id"]."'  order by id desc";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
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
            
        case "getGeneralMaterials":
            $output = array();
            $sql = "SELECT * FROM general_material WHERE user_no='".$_GET["user_no"]."' AND material_subtype='".$_GET["material_subtype"]."'";
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
            $sql = "SELECT * FROM equipment WHERE user_no='".$_GET["user_no"]."' AND status='approve' Order By equipment_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            echo json_encode($output);
            break;
        case "getEquipments_bydep":
            $output = array();
             $sql = "SELECT * FROM equipment WHERE  department='".$_GET["Dep"]."'  Order By equipment_name";
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
            $sql = "SELECT * FROM equipment WHERE user_no='".$_GET["user_no"]."' AND status='approve' and department='".$_GET["depart"]."'  Order By equipment_name";
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
            $sql = "SELECT * FROM equipment WHERE  status='Active' and department='".$_GET["depart"]."' and equipment_type='".$_GET["eq_type"]."' "; 
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
            $sql = "SELECT * FROM employee WHERE user_no='".$_GET["user_no"]."' AND status='active' AND ( department='Quality Control' OR department='master' )";
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
            $sql = "SELECT * FROM gst_per  order by gst ASC";
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
            $sql = "SELECT * FROM state";// plant_id ='".$_GET["plant_id"]."'";
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
            //$sql = "SELECT * FROM unitformula where  plant_id='".$_GET["plant_id"]."'  order by 1 desc";
            $sql = "SELECT * FROM unit";
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
            $sql = "SELECT * FROM department WHERE department_type='Non Technical' 
            AND  plant_id='".$_GET["plant_id"]."' ";
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
        case "getGeneralMaterial":
            $output = Array();
            $sql = "SELECT * FROM general_material where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
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
            $sql = "SELECT product_code, product_name,  grade, shelf_life, hsn, gst, mrp, packing_style FROM product WHERE user_no='".$_GET["user_no"]."' AND status='approve' ORDER BY product_name";
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
            $data["products"] = $output;
            
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
            
            $output = Array();
            $sql = "SELECT type, dosage_form FROM dosage_form where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["dosages"] = $output;
            
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
            $sql = "SELECT * FROM packing_style where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["styles"] = $output;
            
            $output = array();
            $sql = "SELECT * FROM storage_condition where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["storages"] = $output;
            
            $output = array();
            $sql = "SELECT * FROM shelf where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["shelfs"] = $output;
            
            $output = array();
            $sql = "SELECT * FROM gst_per where  plant_id='".$_GET["plant_id"]."' ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
            }
            $data["gst"] = $output;
            
            $output = array();
            $sql = "SELECT max(id) as id, material_type,has_nature_of_material FROM material_type where plant_id = '".$_GET["plant_id"]."' group by material_type,has_nature_of_material order by material_type";
            $result = $conn->query($sql);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                     $output1 = Array();
                        $sql1 = "SELECT max(id) as id,material_subtype FROM material_type WHERE material_type='".$row["material_type"]."' and plant_id = '".$_GET["plant_id"]."' Group By material_subtype order by material_subtype ";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output2 = Array();
                                $sql2 ="SELECT id,category FROM material_type WHERE material_type = '".$row["material_type"]."'  AND  material_subtype='".$row1["material_subtype"]."' and plant_id = '".$_GET["plant_id"]."' order by category ";
                                
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
             
             
             
             $output = array();
             $sql = "Select max(id) as id, product_nature as material_subtype from master_fg_types  where plant_id = '".$_GET["plant_id"]."' group by product_nature order by 1 desc";
             $result = $conn->query($sql);
                if($result->num_rows > 0){
                    while($row = $result->fetch_assoc()){
                         $output1 = Array();
                            $sql1 = "SELECT max(id) as id,dosage_form_type as dosage_form, dosage_sub_form,dosage_sizes,dosage_shapes FROM master_fg_types WHERE product_nature='".$row["material_subtype"]."' and  plant_id = '".$_GET["plant_id"]."' group by dosage_form_type,dosage_sub_form,dosage_sizes,dosage_shapes";

                            $result1 = $conn->query($sql1);
                            if ($result1->num_rows > 0) {
                                while ($row1 = $result1->fetch_assoc()) { 
                                    $output1[] = $row1;
                                    //$output1['dosage_form'] = $row1['dosage_form_type'];
                                    //$output1['dosage_form_sizes'] = json_encode($row1['dosage_sizes']);
                                }
                            }
                            $row["sub_materials"] = $output1;
                            $output[] = $row;
                    }
                }
             $data["fg_types"] = $output;
            
            
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
            
        default:
            echo "Invalid Type";
            break;
    }

} else {
    echo "Invalid Token!";
}

$conn->close();
?>