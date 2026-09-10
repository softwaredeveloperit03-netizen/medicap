<?php 
require '../db.php';
require '../token.php';
$output = Array();
$token = $_GET["token"];
 $currentUrl =$_GET["description"];

// ini_set('display_errors', 1);
// error_reporting(E_ALL);



$today = date("Y-m-d");

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
  $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "saveContent4") {
        
        if($input['Review_Applicable']=='Yes' && $input['Approval_Applicable']=='No'){
            $status='Send for Review';
        }else if($input['Review_Applicable']=='No' && $input['Approval_Applicable']=='Yes'){
            $status='Send for Approval';
        }else if($input['Review_Applicable']=='No' && $input['Approval_Applicable']=='No'){
            $status='Approved';
        }else if($input['Review_Applicable']=='Yes' && $input['Approval_Applicable']=='Yes'){
           $status='Send for Review';
        }
        
        $sql = "INSERT INTO bmr_master (product_code, content_id,content, content_data, entry_by, entry_date,  
             status,Approval_Applicable,Review_Applicable) VALUES
             ('".$_GET['product_code']."', '".$_GET['content_id']."', '".$_GET['content']."', '".json_encode($input['form_data'])."', '$today', '".$_GET['emp_id']."',
              '$status','".$input['Approval_Applicable']."', '".$input['Review_Applicable']."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if($_GET["type"] == "saveContent6") {
        
        if($input['Review_Applicable']=='Yes' && $input['Approval_Applicable']=='No'){
            $status='Send for Review';
        }else if($input['Review_Applicable']=='No' && $input['Approval_Applicable']=='Yes'){
            $status='Send for Approval';
        }else if($input['Review_Applicable']=='No' && $input['Approval_Applicable']=='No'){
            $status='Approved';
        }else if($input['Review_Applicable']=='Yes' && $input['Approval_Applicable']=='Yes'){
           $status='Send for Review';
        }
        
        $sql = "INSERT INTO bmr_master (product_code, content_id,content, content_data, entry_by, entry_date,  
             status,Approval_Applicable,Review_Applicable) VALUES
             ('".$_GET['product_code']."', '".$_GET['content_id']."', '".$_GET['content']."', '".json_encode($input['form_data'])."', '$today', '".$_GET['emp_id']."',
              '$status','".$input['Approval_Applicable']."', '".$input['Review_Applicable']."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "saveContent5") {
        
        $input  = $_POST;
        if($input['Review_Applicable']=='Yes' && $input['Approval_Applicable']=='No'){
            $status='Send for Review';
        }else if($input['Review_Applicable']=='No' && $input['Approval_Applicable']=='Yes'){
            $status='Send for Approval';
        }else if($input['Review_Applicable']=='No' && $input['Approval_Applicable']=='No'){
            $status='Approved';
        }else if($input['Review_Applicable']=='Yes' && $input['Approval_Applicable']=='Yes'){
           $status='Send for Review';
        }
        
            $chid= $_GET["product_code"];
            $pId= $_GET["plant_id"];
           
             
        if (isset($_FILES["flow_chart"])) {
            $file_tmp = $_FILES['flow_chart']['tmp_name'];
            $file_ext = strtolower(end(explode('.', $_FILES['flow_chart']['name'])));
            $flow_chart = $pId.$chid.".".$file_ext;
            move_uploaded_file($file_tmp, "../../../upload/zumaFlow/" . $flow_chart);
        }else{
            $flow_chart=$input['flow_chart'];
        }
        
        $sql = "INSERT INTO bmr_master (product_code, content_id,content, content_data, entry_by, entry_date,  
             status,Approval_Applicable,Review_Applicable) VALUES
             ('".$_GET['product_code']."', '".$_GET['content_id']."', '".$_GET['content']."', '$flow_chart', '$today', '".$_GET['emp_id']."',
              '$status','".$input['Approval_Applicable']."', '".$input['Review_Applicable']."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getMasterBmrData") {
        $output = array();
        $sql = "SELECT * FROM bmr_master where product_code='".$_GET['product_code']."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output[]=$row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getDatacontent4") {
        // $output = array();
        $sql = "SELECT * FROM bmr_master where product_code='".$_GET['product_code']."' and content_id='".$_GET['content_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output['content_data'] = json_decode($row['content_data']);
                $output=$row;
            }
        }
        echo json_encode($output);
    } 
      else if ($_GET["type"] == "getUnitFormulaLog_for_bmr") {
      
        $output = Array();
        // $sql = "SELECT u.id,bom_type,master_formula_type,u.product_type,u.mfr_no,u.product_code,p.product_code1,u.formula_for,
        // u.average_weight,u.raw_materials,u.batch_size,u.unit,
        // p.product_name, p.grade, p.generic_name,p.product_type, p.dosage_form, p.shelf_life, 
        // p.label_claim,p.generic_name FROM unitformula u LEFT JOIN product p ON u.product_code=p.product_code 
        // where u.plant_id='".$_GET["plant_id"]."' order by u.id DESC";
      $sql = "SELECT DISTINCT  u.id,u.bom_type,u.master_formula_type,u.product_type,u.mfr_no,u.product_code,u.formula_for, u.average_weight,u.raw_materials,u.batch_size,u.unit,
(select product_name from product p where u.product_code=p.product_code limit 1) as product_name,
(select grade from product p where u.product_code=p.product_code limit 1) as grade,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select color_index from product p where u.product_code=p.product_code limit 1) as color_index,
(select product_type from product p where u.product_code=p.product_code limit 1) as product_type,
(select dosage_form from product p where u.product_code=p.product_code limit 1) as dosage_form,
(select shelf_life from product p where u.product_code=p.product_code limit 1) as shelf_life,
(select label_claim from product p where u.product_code=p.product_code limit 1) as label_claim,
(select generic_name from product p where u.product_code=p.product_code limit 1) as generic_name,
(select product_code1 from product p where u.product_code=p.product_code limit 1) as product_code1
FROM unitformula u  where u.plant_id='".$_GET["plant_id"]."' and product_code='".$_GET["product_code"]."' order by u.id DESC;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
                            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                $sql1 = "SELECT id,unit_formula_id,market_type,country_specific,country_name,packing_type,
                pack_size,batch_size,unit from unitformula_pm_dtl where unit_formula_id ='".$row["id"]."' ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        $output2 = Array();
                        $sql2 = "SELECT * from unitformula_packing_materials where unit_formula_dtl_id ='".$row1["id"]."' ";
                        
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                
                                  $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row2["grade"]."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row2['gradeName'] = $prodLatest['gradeName']; 
                    
                                
                                 $output2[] = $row2;
                            }
                        }
                        $row1['packing_materials'] = $output2;

                        $output1[] = $row1;
                    }
                    
                }
                
                 $output3 = Array();
                $sql3 = "select product_name from product where product_code ='".$row["product_code"]."' ";
               
                $result3 = $conn->query($sql3);
                if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $output3 = $row3;
                    }
                }
                
                
                $row['packing_configuration'] =$output1;
                $row['product_name1'] =$output3;
                $row["raw_materials"] = json_decode($row["raw_materials"]); 
                $row["product_name2"] = json_decode($row["product_name"][0]); 
                $output = $row;
            }
        }
        echo json_encode($output);
    }
    // else if ($_GET["type"] == "getInstructions") {
    //     $output = array();
    //     $sql = "SELECT dosage_form FROM dosage_form";
    //     $result = $conn->query($sql);
    //     if ($result->num_rows > 0) {
    //         while ($row = $result->fetch_assoc()) {
    //             $sql1 = "SELECT instructions, status FROM instructions WHERE dosage_form='".$row["dosage_form"]."'";
    //             $result1 = $conn->query($sql1);
    //             if ($result1->num_rows > 0) {
    //                 while ($row1 = $result1->fetch_assoc()) {
    //                     $row["instructions"] = $row1["instructions"];
    //                     $row["status"] = $row1["status"];
    //                 }
    //             } else {
    //                 $row["status"] = "na";
    //             }
    //             $output[] = $row;
    //         }
    //     }
    //     echo json_encode($output);
    // } 
    // else if ($_GET["type"] == "updateInstruction") {
    //     $sql = "UPDATE instructions SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
    //     if ($conn->query($sql)) {
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // }

}

$conn->close();
?>