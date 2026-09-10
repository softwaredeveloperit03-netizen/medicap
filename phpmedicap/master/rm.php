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

if($_GET["type"]=="getMaterials") {
 
        $output = Array();
        $plant = $_GET["plant_id"];
        if($plant==0){
        $sql = "SELECT DISTINCT m.*,c.LglNm FROM material m LEFT JOIN client c ON m.client_code=c.client_code 
        WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve' ORDER BY m.id DESC";
        //$sql = "SELECT m.*,g.material_name,g.material_sub_type_id,g.general_material_type,g.material_subtype FROM material m LEFT JOIN general_material g ON m.client_code=c.client_code 
      // WHERE plant_id='".$_GET["plant_id"]."' and general_material_type='".$_GET["material_type"]."'  order by 1 desc";
            
      }
      else if($_GET["plant_id"]==58 || $_GET["plant_id"]==59){
          $plant=$_GET["plant_id"];
       
         $sql = "SELECT DISTINCT m.*,p.m_photo ,p.structure_file_path,p.msds_file_path FROM material m left join product_other_information_api p 
         on m.id=p.product_id  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve'
         ORDER BY m.id DESC"; 
        
        }
      else{
         $sql = "SELECT DISTINCT m.*,p.m_photo ,p.structure_file_path,p.msds_file_path FROM material m left join product_other_information_api p 
         on m.id=p.product_id  WHERE m.plant_id= '".$_GET["plant_id"]."' AND m.material_type='".$_GET["material_type"]."' AND m.status='approve'
         and p.product_code='' ORDER BY m.id DESC "; 
        
        }
        
        $result = $conn->query($sql);
             if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                // $row = array_map('utf8_encode', $row);

              //  print_r($row);
          
          
             $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM grade where id in ('".$row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
          
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
}

 else {
    echo "{\"status\":\"invalid\"}";
}


$conn->close();
?>