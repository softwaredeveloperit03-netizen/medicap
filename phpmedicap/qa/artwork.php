<?php 

//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);

require '../db.php';
require '../token.php';
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
    
    
    
    if ($_GET["type"] == "getProduct") {
        $output = array();
          $sql = "SELECT id,product_code,product_name,plant_id,grade,status,dosage_form,dosage_type FROM
        product where plant_id = '".$_GET["plant_id"]."'";
        // echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPrintedMaterials") {
    
                $output1 = array();
                $sql1 = "SELECT id,material_name,material_subtype,material_code,material_type,sub_type,product_n FROM material 
                WHERE   artwork = 'YES'    AND plant_id = '".$_GET["plant_id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
        echo json_encode($output1);
    } 
    else if ($_GET["type"] == "getUnit") {
    
                $output1 = array();
                $sql1 = "SELECT * FROM unit WHERE  plant_id = '".$_GET["plant_id"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
        echo json_encode($output1);
    } 
    
    
    else if ($_GET["type"] == "saveArtwork") {
        
        
                    $plant_id = $_GET["plant_id"];
                    $m_code = $_POST["material_code"];
                    $p_code = $_POST["product_code"];

    $target_dir = "../../../upload/artwork/";
    
    $file_name = "";
    if(isset($_FILES["structure_file"]["name"])){
        
        $target_file = $target_dir.$m_code.$p_code."_".basename($_FILES["structure_file"]["name"]);
        $structure_file = $m_code.$p_code."_".basename($_FILES["structure_file"]["name"]);
        move_uploaded_file($_FILES["structure_file"]["tmp_name"], $target_file);
    }
        
        
        
        
         $sql = "INSERT INTO artwork (plant_id, product_code, material_code,file, entry_by, entry_date
        )VALUES ('".$_GET["plant_id"]."', '".$_POST["product_code"]."', '".$_POST["material_code"]."', '$structure_file',
        '".$_GET["emp_id"]."','$entry_date')";
             
     
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
        
        
    }
    
    
     else if ($_GET["type"] == "saveExistingArtwork") {
         
         
         $input = $_POST;
         
         
    $sql = "SELECT id FROM artwork ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {  $last_id = $row['id'];  }
    }else{  $last_id = 1;  }
    
    $last_id = $last_id +1;
    $artWorkNo = "ART00".$last_id;
    
    $ver = $input["version_no"];
    $mat = $input["material_code"];
    
        if(isset($_FILES["artwork"]["name"])) {
            $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
            $artwork = $artWorkNo.$ver.$mat.'.'.$file_ext;
    	}
    	
        if(isset($_FILES["fileCdr"]["name"])) {
            $file_ext1=strtolower(end(explode('.',$_FILES['fileCdr']['name'])));
            $fileCdr = $artWorkNo.$ver.$mat.'cdr.'.$file_ext1;
    	}
    	
           
        $sql = "INSERT INTO artwork (product_code, material_code, artwork_no,version_no,entry_by, entry_date,art_status,plant_id) 
        VALUES ('" . $input["product_code"] . "', '" . $input["material_code"] . "', '$artWorkNo', 
        '" . $input["version_no"] . "','".$_GET["emp_id"]."', '$entry_date','Active', '".$_GET["plant_id"]."')";

        if ($conn->query($sql)) {
             
            $sql = "INSERT INTO artwork_files (plant_id, artwork_no, version_no, artwork_file,cdrFile, status,
            qc_status, qa_status,prod_status,plant_head_status,packing_status,regu_status,mark_status) 
            VALUES ('".$_GET["plant_id"]."', '$artWorkNo', '" . $input["version_no"] . "', '$artwork','$fileCdr','Active',
            'Approved', 'Approved','Approved','Approved','Approved','Approved','Approved')";
        
                if ($conn->query($sql)) {
                    
                    echo "{\"status\":\"success\"}";
                    move_uploaded_file($_FILES["artwork"]["tmp_name"], "../../../upload/artwork/".$artWorkNo.$ver.$mat.'.'.$file_ext);
                    move_uploaded_file($_FILES["fileCdr"]["tmp_name"], "../../../upload/artwork/".$artWorkNo.$ver.$mat.'cdr.'.$file_ext1);

                } else {
                    
                    echo "{\"status\":\"" . $conn->error . "\"}";
                    
                }
            
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
         
    }
    
    else if ($_GET["type"] == "getActiveArtwork") {
        $output = array();
     $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
          left join artwork b on b.artwork_no = a.artwork_no
          left join material m on b.material_code = m.material_code
          left join product p on p.product_code = b.product_code
          where a.status ='Active' AND a.plant_id='".$_GET["plant_id"]."'";
          
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getInActiveArtwork") {
        $output = array();
     $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
          left join artwork b on b.artwork_no = a.artwork_no
          left join material m on b.material_code = m.material_code
          left join product p on p.product_code = b.product_code
          where a.status ='In Active' AND a.plant_id='".$_GET["plant_id"]."'";
          
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "getArtworkForDeptApproval") {
        $output = array();
 
          $sql ="select * from artwork_files where status = 'jadugar'";
          
          if($_GET["dept_name"] == 'Quality Control'){
                $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
          left join artwork b on b.artwork_no = a.artwork_no
          left join material m on b.material_code = m.material_code
          left join product p on p.product_code = b.product_code
          where a.status ='InProcess' AND a.plant_id='".$_GET["plant_id"]."' AND a.qc_status = 'Pending'";
          }
          else if($_GET["dept_name"] == 'plant_head_status'){
              $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
          left join artwork b on b.artwork_no = a.artwork_no
          left join material m on b.material_code = m.material_code
          left join product p on p.product_code = b.product_code
          where a.status ='InProcess' AND a.plant_id='".$_GET["plant_id"]."' AND a.plant_head_status = 'Pending'";
          }
          else if($_GET["dept_name"] == 'Production'){
               $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
          left join artwork b on b.artwork_no = a.artwork_no
          left join material m on b.material_code = m.material_code
          left join product p on p.product_code = b.product_code
          where a.status ='InProcess' AND a.plant_id='".$_GET["plant_id"]."' AND a.prod_status = 'Pending'";
          }
          else if($_GET["dept_name"] == 'Packing'){
               $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
          left join artwork b on b.artwork_no = a.artwork_no
          left join material m on b.material_code = m.material_code
          left join product p on p.product_code = b.product_code
          where a.status ='InProcess' AND a.plant_id='".$_GET["plant_id"]."' AND a.packing_status = 'Pending'";
          }
          else if($_GET["dept_name"] == 'Regulatory'){
              $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
          left join artwork b on b.artwork_no = a.artwork_no
          left join material m on b.material_code = m.material_code
          left join product p on p.product_code = b.product_code
          where a.status ='InProcess'   AND a.regu_status = 'Pending'";
          }
          else if($_GET["dept_name"] == 'Marketing'){
             $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
          left join artwork b on b.artwork_no = a.artwork_no
          left join material m on b.material_code = m.material_code
          left join product p on p.product_code = b.product_code
          where a.status ='InProcess'   AND a.mark_status = 'Pending'";
          }
           
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    else if ($_GET["type"] == "GetQAApproval") {
        $output = array();
        
         $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
        left join artwork b on b.artwork_no = a.artwork_no
        left join material m on b.material_code = m.material_code
        left join product p on p.product_code = b.product_code
        where  a.plant_id='".$_GET["plant_id"]."' AND a.qc_status != 'Pending'
        AND a.prod_status != 'Pending' AND a.packing_status != 'Pending' AND a.regu_status != 'Pending'
        AND a.mark_status != 'Pending' AND a.plant_head_status != 'Pending' AND a.qa_status = 'Pending' ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
     else if ($_GET["type"] == "initiateArtwork") {
          
    $sql = "SELECT id FROM artwork ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {  $last_id = $row['id'];  }
    }else{  $last_id = 1;  }
    
    $last_id = $last_id +1;
    $artWorkNo = "ART00".$last_id;
    
    $ver = $input["version_no"];
    $mat = $input["material_code"];
    
        $sql = "INSERT INTO artwork (product_code, material_code, artwork_no,version_no,entry_by, entry_date,art_status,plant_id) 
        VALUES ('" . $input["product_code"] . "', '" . $input["material_code"] . "', '$artWorkNo', 
        '1','".$_GET["emp_id"]."', '$entry_date','Pending', '".$_GET["plant_id"]."')";

        if ($conn->query($sql)) {
            
            $sql = "INSERT INTO artwork_files (plant_id, artwork_no, version_no,Developer, status,
            qc_status, qa_status,prod_status,plant_head_status,packing_status,regu_status,mark_status) 
            VALUES ('".$_GET["plant_id"]."', '$artWorkNo', '1','" . $input["Developer"] . "','TO_UPLOAD',
            'Pending', 'Pending','Pending','Pending','Pending','Pending','Pending')";
        
                if ($conn->query($sql)) {
                    
                    echo "{\"status\":\"success\"}";
 
                } else {
                    
                    echo "{\"status\":\"" . $conn->error . "\"}";
                    
                }    
              
        } else {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
         
    }
     else if ($_GET["type"] == "saveInitiateArtworkUpload") {
         
         $input = $_POST;
          
       $ver = $input["version_no"];
    $mat = $input["material_code"];
    $artWorkNo = $input["artwork_no"];
    
        if(isset($_FILES["artwork"]["name"])) {
            $file_ext=strtolower(end(explode('.',$_FILES['artwork']['name'])));
            $artwork = $artWorkNo.$ver.$mat.'.'.$file_ext;
    	}
     
            
             $sql = "UPDATE artwork_files  SET artwork_file = '$artwork' , status = 'InProcess' 
             , mark_status = 'Pending' , regu_status = 'Pending' , packing_status = 'Pending' , plant_head_status = 'Pending'
             , prod_status = 'Pending' , qa_status = 'Pending' , qc_status = 'Pending' where id =  '".$input["id"]."' ";
        
                if ($conn->query($sql)) {
                    move_uploaded_file($_FILES["artwork"]["tmp_name"], "../../../upload/artwork/".$artWorkNo.$ver.$mat.'.'.$file_ext);

                    echo "{\"status\":\"success\"}";
 
                } else {
                    
                    echo "{\"status\":\"" . $conn->error . "\"}";
                    
                }
    }
     else if ($_GET["type"] == "approveArtworkDeptLevel") {
         
           
         
          if($_GET["dept_name"] == 'Quality Control'){
              $sql = "UPDATE artwork_files  SET qc_status = '".$_GET["status"]."' , qc_remark = '".$input["remark"]."' ,
              qcAppBy = '".$_GET["emp_id"]."' , qcAPPOn = '$entry_date' where id =  '".$_GET["ID"]."' ";
          }
          else if($_GET["dept_name"] == 'plant_head_status'){
              $sql = " UPDATE artwork_files  SET plant_head_status = '".$_GET["status"]."' , plant_remark = '".$input["remark"]."',
              plantAppBy = '".$_GET["emp_id"]."' , plantAPPOn = '$entry_date' where id =  '".$_GET["ID"]."' ";
          }
          else if($_GET["dept_name"] == 'Production'){
              $sql = " UPDATE artwork_files  SET prod_status = '".$_GET["status"]."' , prod_remark = '".$input["remark"]."' ,
              prodAppBy = '".$_GET["emp_id"]."' , prodAPPOn = '$entry_date' where id =  '".$_GET["ID"]."' ";
          }
          else if($_GET["dept_name"] == 'Packing'){
              $sql = "UPDATE artwork_files  SET packing_status = '".$_GET["status"]."' , pack_remark = '".$input["remark"]."',
              packAppBy = '".$_GET["emp_id"]."' , packAPPOn = '$entry_date' where id =  '".$_GET["ID"]."' ";
          }
          else if($_GET["dept_name"] == 'Regulatory'){
              $sql = "UPDATE artwork_files  SET regu_status = '".$_GET["status"]."' , regu_remark = '".$input["remark"]."' ,
              reguAppBy = '".$_GET["emp_id"]."' , reguAPPOn = '$entry_date' where id =  '".$_GET["ID"]."' ";
          }
          else if($_GET["dept_name"] == 'Marketing'){
              $sql = "UPDATE artwork_files  SET mark_status = '".$_GET["status"]."' , mark_remark = '".$input["remark"]."' ,
              markAppBy = '".$_GET["emp_id"]."' , markAPPOn = '$entry_date' where id =  '".$_GET["ID"]."' ";
          }
         
          
        
            if ($conn->query($sql)) {
                echo "{\"status\":\"success\"}";
            } else {
                echo "{\"status\":\"" . $conn->error . "\"}";
            }
            
    }
     else if ($_GET["type"] == "ArtworkQaApproval") {
         
           
         $status = 'Active';
        
          
          if($_GET["status"] == 'Rejected'){
             $status = 'TO_UPLOAD';
          }
        
          
      $sql = "UPDATE artwork_files  SET qa_status = '".$_GET["status"]."' , status = '$status' ,
      qaAppBy = '".$_GET["emp_id"]."' , qaAPPOn = '$entry_date' where id =  '".$_GET["ID"]."' ";
          
          
            if ($conn->query($sql)) {
                
                    $sql1 = "UPDATE artwork  SET version_no = '".$input["version_no"]."' where artwork_no =  '".$input["artwork_no"]."' ";
                  
                    if ($conn->query($sql1)) {
                        echo "{\"status\":\"success\"}";
                    } else {
                        echo "{\"status\":\"" . $conn->error . "\"}";
                    }
                
            } else {
                echo "{\"status\":\"" . $conn->error . "\"}";
            }
            
    }
    
    else if ($_GET["type"] == "getInitiateArtwork") {
        $output = array();
    $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
          left join artwork b on b.artwork_no = a.artwork_no
          left join material m on b.material_code = m.material_code
          left join product p on p.product_code = b.product_code
          where a.status ='TO_UPLOAD' AND a.plant_id='".$_GET["plant_id"]."'";
          
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getArtWorkStatusLog") {
        $output = array();
     $sql = "SELECT a.*,b.material_code,b.product_code,m.material_name,p.product_name from artwork_files a
          left join artwork b on b.artwork_no = a.artwork_no
          left join material m on b.material_code = m.material_code
          left join product p on p.product_code = b.product_code
          where    a.plant_id='".$_GET["plant_id"]."'";
          
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
    else if ($_GET["type"] == "reuploadArtwork") {
        
        $sql = "UPDATE artwork_files SET status ='In Active'  WHERE id='".$_GET["ID"]."'";
      
        if ($conn->query($sql)) {
           
           
           $version_no = $input["version_no"] + 1;
           
             $sql = "INSERT INTO artwork_files (plant_id, artwork_no, version_no,Developer, status,
            qc_status, qa_status,prod_status,plant_head_status,packing_status,regu_status,mark_status) 
            VALUES ('".$_GET["plant_id"]."', '" . $input["artwork_no"] . "', '$version_no','" . $input["Developer"] . "','TO_UPLOAD',
            'Pending', 'Pending','Pending','Pending','Pending','Pending','Pending')";
        
                if ($conn->query($sql)) {
                    
                    echo "{\"status\":\"success\"}";
 
                } else {
                    
                    echo "{\"status\":\"" . $conn->error . "\"}";
                    
                }  
           
           
           
           
           
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } 
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
   
    else if ($_GET["type"] == "getUploadedArtwork") {
        $output = array();
        $sql = "SELECT a.*,m.material_name,p.product_name FROM artwork a left join product p ON a.product_code = p.product_code
        left join material m on m.material_code = a.material_code WHERE a.plant_id='".$_GET["plant_id"]."' order by a.id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getInprocessArtworks") {
        $output = array();
        $sql = "SELECT * FROM artwork WHERE user_no='".$_GET["user_no"]."' AND status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/artwork/".$row["file"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
    
     else if ($_GET["type"] == "getArtworkLogProducts") {
        $output = array();
        $sql = "SELECT * FROM product";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 
     else if ($_GET["type"] == "getPrintingType") {
        $output = array();
        $sql = "SELECT * FROM material where material_name='".$_GET["materialValue"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);  
    }
     else if ($_GET["type"] == "getClientNamesLog") {
        $output = array();
    $sql = "SELECT c.* FROM material m LEFT JOIN client c ON m.client_code=c.client_code where m.material_name='".$_GET["materialValue"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    
     
      
     
     
    
     
     
     
     
     
      
       
          
      
    
    
       
     
    
    
       
      
    else if ($_GET["type"] == "getPendingDesigns") {
        $output = array();
        $sql = "SELECT a.*, p.product_name, p.grade, p.dosage_form, m.material_name, v.vendor_name FROM artwork a LEFT JOIN product p ON a.product_code=p.product_code LEFT JOIN material m ON a.material_code=m.material_code LEFT JOIN vendor v ON a.vendor_no=v.vendor_no WHERE a.user_no='".$_GET["user_no"]."' AND a.status='approve' AND a.design='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/artwork/".$row["file"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getUploadedDesigns") {
        $output = array();
        $sql = "SELECT a.*, p.product_name, p.grade, p.dosage_form, m.material_name, v.vendor_name FROM artwork a LEFT JOIN product p ON a.product_code=p.product_code LEFT JOIN material m ON a.material_code=m.material_code LEFT JOIN vendor v ON a.vendor_no=v.vendor_no WHERE a.user_no='".$_GET["user_no"]."' AND a.status='approve' AND a.design='upload'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/artwork/".$row["file"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveArtworkDesign") {
        $file = "";
        $id = date("Ymdhis", $timestamp);
        if(isset($_FILES['design'])) {
            $file_ext=strtolower(end(explode('.',$_FILES['design']['name'])));
            if ($file_ext == "pdf") {
                $file_tmp =$_FILES['design']['tmp_name'];
                $file = $id.".pdf";
                move_uploaded_file($file_tmp,"../upload/artwork/".$file);
                
                $sql = "UPDATE artwork SET design='upload',design_file='$file', design_by='".$_GET["emp_id"]."', design_date='$entry_date' WHERE id='".$_GET["id"]."'";
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
            } else {
                echo "{\"status\":\"Invalid File Type\"}";
            }
        } else {
            echo "{\"status\":\"Upload File\"}";
        }
    }
    
    else if ($_GET["type"] == "updateArtworkDesign") {
        $sql = "UPDATE artwork SET design='".$_GET["status"]."', design_approve_by='".$_GET["emp_id"]."', design_approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    
    else if ($_GET["type"] == "getPendingPrinting") {
        $output = array();
        $sql = "SELECT a.*, p.product_name, p.grade, p.dosage_form, m.material_name, v.vendor_name FROM artwork a LEFT JOIN product p ON a.product_code=p.product_code LEFT JOIN material m ON a.material_code=m.material_code LEFT JOIN vendor v ON a.vendor_no=v.vendor_no WHERE a.user_no='".$_GET["user_no"]."' AND a.status='approve' AND a.design='approve' AND a.printing='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/artwork/".$row["file"];
                $row["design_file"] = "upload/artwork/".$row["design_file"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "sendforPrinting") {
        $sql = "UPDATE artwork SET printing='send', printing_send_by='".$_GET["emp_id"]."', printing_send_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getAwaitingPrinting") {
        $output = array();
        $sql = "SELECT a.*, p.product_name, p.grade, p.dosage_form, m.material_name, v.vendor_name FROM artwork a 
        LEFT JOIN product p ON a.product_code=p.product_code LEFT JOIN material m ON a.material_code=m.material_code 
        LEFT JOIN vendor v ON a.vendor_no=v.vendor_no WHERE a.user_no='".$_GET["user_no"]."' AND a.status='approve' 
        AND a.design='approve' AND a.printing='send'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/artwork/".$row["file"];
                $row["design_file"] = "upload/artwork/".$row["design_file"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "uploadPrinting") {
        $file = "";
        $id = date("Ymdhis", $timestamp);
        if(isset($_FILES['printing'])) {
            $file_ext=strtolower(end(explode('.',$_FILES['printing']['name'])));
            if ($file_ext == "pdf") {
                $file_tmp =$_FILES['printing']['tmp_name'];
                $file = $id.".pdf";
                move_uploaded_file($file_tmp,"../upload/artwork/".$file);
                
                $sql = "UPDATE artwork SET printing='upload',printing_file='$file', printing_upload_by='".$_GET["emp_id"]."', printing_upload_date='$entry_date' WHERE id='".$_GET["id"]."'";
                if ($conn->query($sql)) {
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
            } else {
                echo "{\"status\":\"Invalid File Type\"}";
            }
        } else {
            echo "{\"status\":\"Upload File\"}";
        }
    } else if ($_GET["type"] == "getUploadedPrintings") {
        $output = array();
        $sql = "SELECT a.*, p.product_name, p.grade, p.dosage_form, m.material_name, v.vendor_name FROM artwork a LEFT JOIN product p ON a.product_code=p.product_code LEFT JOIN material m ON a.material_code=m.material_code LEFT JOIN vendor v ON a.vendor_no=v.vendor_no WHERE a.user_no='".$_GET["user_no"]."' AND a.status='approve' AND a.design='approve' AND a.printing='upload'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/artwork/".$row["file"];
                $row["design_file"] = "upload/artwork/".$row["design_file"];
                $row["printing_file"] = "upload/artwork/".$row["printing_file"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "approvePrinting") {
        $sql = "UPDATE artwork SET printing='".$_GET["status"]."', printing_approve_by='".$_GET["emp_id"]."', printing_approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getApprovedPrintings") {
        $output = array();
        $sql = "SELECT a.*, p.product_name, p.grade, p.dosage_form, m.material_name, v.vendor_name FROM artwork a LEFT JOIN product p ON a.product_code=p.product_code LEFT JOIN material m ON a.material_code=m.material_code LEFT JOIN vendor v ON a.vendor_no=v.vendor_no WHERE a.user_no='".$_GET["user_no"]."' AND a.status='approve' AND a.design='approve' AND a.printing='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["file"] = "upload/artwork/".$row["file"];
                $row["design_file"] = "upload/artwork/".$row["design_file"];
                $row["printing_file"] = "upload/artwork/".$row["printing_file"];
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>