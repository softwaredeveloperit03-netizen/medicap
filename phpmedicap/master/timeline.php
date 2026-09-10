<?php 
require '../db.php';
require '../token.php';
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
    
  if($_GET["type"] == "get_material_by_type") {
 
      $output = array();

$sql = "SELECT material_type FROM my_view GROUP BY material_type ORDER BY material_type ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $materials = array();

        $sql1 = "SELECT * 
                 FROM my_view 
                 WHERE material_type = '".$row["material_type"]."' 
                 ORDER BY material_name ASC";

        $result1 = $conn->query($sql1);

        if ($result1 && $result1->num_rows > 0) {

            while ($row1 = $result1->fetch_assoc()) {

                $vendors = array();

                $sql2 = "
                    SELECT 
                        m.id,
                        (SELECT v.vendor_name 
                         FROM vendor v 
                         WHERE v.vendor_no = m.supplier_code 
                         LIMIT 1) AS vendor_name,
                         m.min_delivery_time,
                         m.max_delivery_time
                         
                    FROM mst_vendor_materials m
                    WHERE m.material_code = '".$row1["material_code"]."'
                ";

                $result2 = $conn->query($sql2);

                if ($result2 && $result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $vendors[] = $row2;
                    }
                }

                // ✅ attach vendors to MATERIAL
                $row1["vendors"] = $vendors;

                $materials[] = $row1;
            }
        }

        // ✅ attach materials to MATERIAL TYPE
        $row["materials"] = $materials;

        $output[] = $row;
    }
}

echo json_encode($output);

  }
 
 
else if ($_GET["type"] == "update_timeline") {
        $table=$input["source_table"];
        $min_delivery_time=$input["min_delivery_time"];
        $max_delivery_time=$input["max_delivery_time"];
        $id=$input["id"];
    $sql = "UPDATE mst_vendor_materials SET min_delivery_time='$min_delivery_time', max_delivery_time='$max_delivery_time' WHERE id='$id'";
    if($conn->query($sql)){
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";  
    }
}
}

$conn->close();
?>