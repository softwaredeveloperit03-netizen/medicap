<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$output = Array();
$token = $_GET["token"];
$currentUrl =$_GET["description"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
 $entry_date = date("Y-m-d h:i:s", $timestamp);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0)
{
    while($row = $result->fetch_assoc())
    {
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
 
    
    if ($_GET["type"] == "saveStatutoryDoc"){ 
        
        $input = $_POST;

        $statutoryDoc = "NA";
        
        $pid = $_GET["plant_id"];
        $docNo = $input["formNoDocNo"];
        $rN = mt_rand(10000, 99999);

         
        if (isset($_FILES["statutoryDoc"])) {
            $file_tmp = $_FILES['statutoryDoc']['tmp_name'];
            $file_ext = strtolower(end(explode('.', $_FILES['statutoryDoc']['name'])));
            $statutoryDoc = $pid.$docNo.$rN.".".$file_ext;
            move_uploaded_file($file_tmp, "../../../upload/statutoryDocs/" . $statutoryDoc);
        }
    
        $sql = "INSERT INTO `statutoryDocs`(`plant_id`, `perticular`, `authoGovtAgency`, `docFormName`, `formNoDocNo`, `doc`, 
        `issueDate`, `validTill`, `entryDate`, `EntryBy`) VALUES  ('".$_GET["plant_id"]."','".$input["perticular"]."', 
        '".$input["authoGovtAgency"]."', '".$input["docFormName"]."', '".$input["formNoDocNo"]."', '$statutoryDoc',
        '".$input["issueDate"]."', '".$input["validTill"]."','".$_GET["emp_id"]."','$entry_date' )";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
    }
    else if ($_GET["type"] == "getStatutoryReminders") {
    
        $sql = "SELECT * , DATEDIFF(validTill, CURDATE()) AS rem_days FROM statutoryDocs where plant_id = '".$_GET["plant_id"]."'  order by id desc";
       	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    
  
    
      
}
$conn->close();
?>