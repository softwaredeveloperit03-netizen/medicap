<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

require 'db.php';
require 'token.php';
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
    
while($row = $result->fetch_assoc()) {
	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	$string = explode("$",$string);
	$_GET["emp_id"] = $string[0];
	$_GET["department"] = $string[1];
	break;
}

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
// $conn->query($sql);

if ($_GET["type"]=="saveControlSample") {
    $sql = "SELECT IFNULL(MAX(cs_id1), 0) as  cs_id1 FROM control_sample";
    $cs_id1 = 0;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cs_id1 = $row["cs_id1"];
            break;
        }
    }
    
    $cs_id1++;
    
    $num_length = strlen((string)$cs_id1);
    
    if($num_length == 1) {
        $cs_id = "CSID00".$cs_id1;
    } else if($num_length == 2) {
        $cs_id = "CSID0".$cs_id1;
    } else {
       $cs_id = "CSID".$cs_id1; 
    }
    $sql = "INSERT INTO control_sample(cs_id, material_type, material_name, batch_no, batch_size, grade, ar_no, analysis_date, release_date,  mfg_date, exp_date, done_by, sampling_date, sample_quantity, pack_no, rack_no, checked_by, room_temp, humidity, department, cs_id1) VALUES
    ('$cs_id', '".$_POST['material_type']."','".$_POST['material_name']."', '".$_POST['batch_no']."', '".$_POST['batch_size']."', '".$_POST['grade']."', '".$_POST['ar_no']."', '".$_POST['analysis_date']."', '".$_POST['release_date']."', '".$_POST['mfg_date']."', '".$_POST['exp_date']."', '".$_POST['done_by']."', '".$_POST['sampling_date']."', '".$_POST['sample_quantity']."', '".$_POST['pack_no']."', '".$_POST['rack_no']."', '".$_GET['emp_id']."', '".$_POST['room_temp']."', '".$_POST['humidity']."', '".$_GET['department']."', $cs_id1)";
    if ($conn->query($sql) === TRUE) {
       echo "{\"status\":\"success\"}";
    } else {
       echo "{\"status\":\"".$conn->error."\"}";
    }
}   else if($_GET["type"]=="getBatchbyId") {
    $sql = "SELECT * FROM control_sample a left join material b on a.material_code=b.material_code WHERE b.material_name='".$_GET["selectedmaterial_name"]."' ";
    // $sql = "SELECT DISTINCT batch_no FROM control_sample WHERE material_name='".$_GET["selectedmaterial_name"]."' ";
    $result = $conn->query($sql);
    $output = Array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
            $output[] = $row;
        }
        echo json_encode($output);
    }
    else {
        echo "[]";
    }
} else if ($_GET["type"] == "getControlsamples") {
    
    $sql = "SELECT * FROM control_sample where status = 'approve' AND plant_id = '".$_GET["plant_id"]."' AND material_type = '".$_GET["material_type"]."' ORDER BY id DESC";
    $output = array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
           if ($row["material_type"] == "Raw Material") {
                    $sql1 = "SELECT material_code, material_subtype, material_name, grade FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_name"] = $row1["material_name"];
               
                         }
                    }
                } else if ($row["material_type"] == "Packing Material") {
                    $sql1 = "SELECT material_code, material_subtype, material_name, grade FROM material WHERE material_code='".$row["material_code"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_name"] = $row1["material_name"];
                    
                        }
                    }
                } else if ($row["material_type"] == "Finish Product") {
                     $sql1 = "SELECT * FROM product WHERE product_code='".$row["material_code"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_name"] = $row1["product_name"];
                   
                        }
                    }
                }
                $output[] = $row;
        }
    }
    echo json_encode($output);
}  else if ($_GET["type"] == "getControlsamples1") {
    // $sql = "SELECT * FROM control_sample where department = '".$_GET["department"]."'";
    
    if($_GET["material_type"] == 'Finish Product'){
       $sql = "SELECT a.*,b.product_name as material_name FROM control_sample a left join product b on a.material_code=b.product_code  where a.material_type = '".$_GET["material_type"]."'";

    }else{
        
     $sql = "SELECT * FROM control_sample a left join material b on a.material_code=b.material_code  where a.material_type = '".$_GET["material_type"]."'";

    }
    
     
    $output = array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sql2 = "SELECT SUM(required_qty) as required_qty FROM sample_withdrawal WHERE cs_id = '".$row["cs_id"]."' ";
            $result2 = $conn->query($sql2);
            $withdrawal = 0;
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $withdrawal = $row2["required_qty"];
                }
            }
            
            $sql2 = "SELECT SUM(quantity) as destroyed_qty FROM control_sample_distruction WHERE cs_id = '".$row["cs_id"]."'";
            $result2 = $conn->query($sql2);
            $destroy = 0;
            if ($result2->num_rows > 0) {
                while ($row2 = $result2->fetch_assoc()) {
                    $destroy = $row2["destroyed_qty"];
                }
            }
            
            $row["balance"] = $row["sample_quantity"] - $withdrawal - $destroy;
            
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "getControlsamplesByID") {
    $sql = "SELECT * FROM control_sample a left join material b on a.material_code=b.material_code where a.cs_id = 
    '".$_GET["cs_id"]."' ";// ORDER BY id DESC";
//   echo  $sql = "SELECT * FROM control_sample where cs_id = 
//     '".$_GET["cs_id"]."' && department = '".$_GET["department"]."'";// ORDER BY id DESC";

    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"]=="updateStatusCheck") {
    $sql = "UPDATE sample_withdrawal SET ischecker='true' WHERE id=".$_GET["id"];
    if ($qa->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="updateStatus") {
    $sql = "UPDATE sample_withdrawal SET isapprover='true' WHERE id=".$_GET["id"];
    if ($qa->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if ($_GET["type"]=="saveControlSampleWithdrawals") {

    $sample_quantity = 0;    
     $sql = "SELECT sample_quantity FROM control_sample WHERE cs_id = '".$input["cs_id"]."' ";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sample_quantity = $row["sample_quantity"];
            break;
        }
    }
    
    $available = 0;
    
     $sql = "SELECT * FROM sample_withdrawal WHERE cs_id = '".$input["cs_id"]."' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $available = $available + $row["required_qty"];
        }
    }
    
    $balance = $sample_quantity - $available - $input['required_qty'];
        echo($sample_quantity - $available - $input['required_qty']);

    
    if ($balance >= 0) {
        $sql = "SELECT IFNULL(MAX(sw_id1), 0) as  sw_id1 FROM sample_withdrawal";
        $sw_id1 = 0;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sw_id1 = $row["sw_id1"];
                break;
            }
        }
        
        $sw_id1++;
        
        $num_length = strlen((string)$sw_id1);
        
        if($num_length == 1) {
            $sw_id = "SWID00".$sw_id1;
        } else if($num_length == 2) {
            $sw_id = "SWID0".$sw_id1;
        } else {
           $sw_id = "SWID".$sw_id1; 
        }
        
          $sql = "INSERT INTO sample_withdrawal(cs_id, sw_id, product_name,material_code, batch_no, date, department_name,request_by_employee, reason_for_request,
        required_qty, required_pack, balance,checked_by, department, sw_id1,plant_id) VALUES('".$input['cs_id']."', '$sw_id', '".$input['material_name']."','".$input['material_code']."',
        '".$input['batch_no']."', '$entry_date',  '".$input['department_name']."', '".$input['request_by_employee']."', '".$input['reason_for_request']."',
        '".$input['required_qty']."', '".$input['required_pack']."', '".$balance."', '".$_GET['emp_id']."' , '".$_GET['department']."' , '$sw_id1', 
        '".$_GET['plant_id']."')";
            
            
        if ($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
        } else {
           echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } else {
        echo "{\"status\":\"low\", \"reason\":\"balance is low\"}";
    }
    
}  




else if ($_GET["type"] == "getControlsampleWithdrawals") {
     $sql = "SELECT s.* FROM sample_withdrawal s left join control_sample c ON s.material_code = c.material_code where c.material_type = '".$_GET["material_type"]."' ";// where department = '".$_GET["department"]."' ORDER BY id DESC";
    $output = Array();
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
    
    
}  else if ($_GET["type"] == "getControlsampleWithdrawalsbyID") {
      $output = Array();
    $sql = "SELECT * FROM sample_withdrawal where cs_id = '".$_GET["cs_id"]."'  ";// ORDER BY id DESC";
    // $sql = "SELECT * FROM sample_withdrawal where cs_id = '".$_GET["cs_id"]."' 
    // AND department = '".$_GET["department"]."'";// ORDER BY id DESC";
  
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);
} else if ($_GET["type"] == "saveDistruction") {
    
    $sample_quantity = 0;    
    $sql = "SELECT sample_quantity FROM control_sample WHERE cs_id = '".$input["cs_id"]."'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sample_quantity = $row["sample_quantity"];
            break;
        }
    }
    
    $available = 0;
    
    $sql = "SELECT * FROM sample_withdrawal WHERE cs_id = '".$input["cs_id"]."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $available = $available + $row["required_qty"];
        }
    }
    
    $balance = $sample_quantity - $available - $input['quantity'];
    
    if ($balance >= 0) {
    $sql = "SELECT IFNULL(MAX(i_no1), 0) as  i_no1 FROM control_sample_distruction";
    $i_no1 = 0;
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $i_no1 = $row["i_no1"];
            break;
        }
    }
    
    $i_no1++;
    $num_length = strlen((string)$i_no1);
    
    if($num_length == 1) {
        $distruction_id = "DID00".$i_no1;
    } else if($num_length == 2) {
        $distruction_id = "DID0".$i_no1;
    } else {
       $distruction_id = "DID".$i_no1; 
    }
    
    $sql = "INSERT INTO control_sample_distruction(plant_id,cs_id, distruction_id, entry_date, product_name, batch_no, quantity, department_name, request_by_employee,
    equipment, balance, distruction_loc, department, i_no1) 
    VALUES ('".$_GET["plant_id"]."','".$input['cs_id']."','$distruction_id', '$entry_date', '".$input['product_name']."', '".$input['batch_no']."', '".$input['quantity']."',
    '".$input["distruction_loc"]."', '".$input["request_by_employee"]."', '".$input["equipment"]."', '".$balance."', '".$input["distruction_loc"]."', '".$input["distruction_loc"]."',  $i_no1)";
    if ($conn->query($sql) === TRUE) {
        
        $flag = 0;
        
        
         
              $json_obj = json_encode($input["steps"]);
              $array = json_decode($json_obj, true);
                 $k=1;
                foreach ($array as $values)
                {
        
     
            	    $sql = "INSERT INTO sample_distruction (distruction_id, step_name) VALUES ('$distruction_id', '".$values['step_name']."')";
            	    if ($conn->query($sql) === TRUE) {
            	        $flag = 0;
            	    } else {
            	        $flag = 1;
            	    }
            	}
    	
    	
    	if ($flag == 0) {
            echo "{\"status\":\"success\"}";
    	} else {
    	    echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else {
       echo "{\"status\":\"".$conn->error."\"}";
    }
    }
    else {
        echo "{\"status\":\"low\", \"reason\":\"balance is low\"}";
    }
    
} 
else if($_GET["type"]=="getDistructionDetails") {
    
        // $sql = "SELECT * from control_sample_distruction where department = '".$_GET["department"]."' ORDER BY id DESC";
        $sql = "SELECT * from control_sample_distruction where plant_id = '".$_GET["plant_id"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        $output = array(); 
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $sql1 = "select step_name, distruction_id from sample_distruction where distruction_id = '".$row['distruction_id']."'";
                $result1 = $conn->query($sql1);
                $data = array();
                
                if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        $data[] = $row1;
                    }
                }
                
                $row["steps"] = $data;
                $output[] = $row;
            }
        }
    
        echo json_encode($output);
        
 }   
 else if($_GET["type"]=="getDistructionDetailsById") {
    
        $sql = "SELECT * from control_sample_distruction where cs_id = '".$_GET["cs_id"]."' && plant_id = '".$_GET["plant_id"]."' ORDER BY id DESC";
        $result = $conn->query($sql);
        $output = array(); 
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $sql1 = "select step_name, distruction_id from sample_distruction where distruction_id = '".$row['distruction_id']."'";
                $result1 = $conn->query($sql1);
                $data = array();
                if($result1->num_rows > 0){
                    while($row1 = $result1->fetch_assoc()){
                        $data[] = $row1;
                    }
                }
                
                $row["steps"] = $data;
                $output[] = $row;
            }
        }
    
        echo json_encode($output);
} else if ($_GET["type"]=="updateDistruction") {
    $sql = "UPDATE control_sample_distruction SET action='".$_GET["action"]."' WHERE id=".$_GET["id"];
    if ($qa->query($sql)===TRUE) {
        echo "{\"status\":\"success\"}";
    } else {
        echo "{\"status\":\"failed\"}";
    }
} else if($_GET["type"] == "getProductInfo"){
        $sql = "SELECT * FROM batch_release ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

} else {
    echo "[]";
}

$conn->close();
// $qc->close();
// $store->close();
// $purchase->close();
// $security->close();
// $qa->close();
// $hr->close();
?>