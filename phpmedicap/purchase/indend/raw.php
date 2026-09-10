<?php
    require '../../db.php';
    require '../../token.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

// ini_set('display_errors', 1);
// error_reporting(E_ALL);



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
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    
    if ($_GET["type"] == "saveIndend") { 
        
        
        $no = date("YmdHis", $timestamp);
        $flag = 0;
         
        $sql = "SELECT request_no FROM indend_raw ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['request_no'];
        }
        if($last_id==null){
            $last_id=1;
        }else{
            $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
            //  echo $int_var;
            $last_id = $int_var+1;
        }
        $number = substr(str_repeat(0, 4).$last_id, - 4);
        $ind_no = "RQ".$number;
       // echo $ind_no;
        for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];
            if ($temp["required_for"] == "Own") {
                $temp["client_code"] = "";
            }
             $sql = "INSERT INTO indend_raw (plant_id,user_no,request_no,no, material_type,material_subtype,material_id,material_code, req_qty, unit, 
             requirement, purpose, expected_vendor, 
             required_for, client_code,gst,vendor_type,manufacturer_no,vendor_no,specific_vendor,material_nature,entry_by, entry_date, 
             department ,exp_date,other_purpose) VALUES 
            ( '".$_GET["plant_id"]."','".$_GET["user_no"]."','$ind_no','$no','".$temp["material_type"]."','".$temp["material_subtype"]."',
            '".$temp["material_id"]."','".$temp["material_code"]."','".$temp["qty"]."','".$temp["unit"]."','".$temp["requirement"]."',
            '".$temp["purpose"]."','".$temp["vendor"]."','".$temp["required_for"]."','".$temp["client_code"]."','".$temp["gst"]."',
            '".$temp["vendor_type"]."','".$temp["manufacturer_no"]."','".$temp["vendor_no"]."','".$temp["specific_vendor"]."',
            '".$temp["material_nature"]."','".$_GET["emp_id"]."','".$entry_date."','".$temp["department"]."' ,'".$temp["exp_date"]."',
            '".$temp["other_purpose"]."')";
            
         //   echo $sql;
           $conn->query($sql);
           
        }
       
        echo "{\"status\":\"success\"}";
    
        
    } else if ($_GET["type"] == "getPendingIndends") {
        $output = array();
        $sql = "SELECT i.*, m.material_subtype, m.material_name,m.material_code, m.grade FROM indend_raw i 
        LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' ";
        //AND i.department='".$_GET["department"]."' AND i.status='pending'";
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateIndend") {
        $sql = "UPDATE indend_raw SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getIndendsLog") {
        $output = array();
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_raw i 
        LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getMaterials") {
        $output = array();
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code GROUP BY i.material_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getCheckedIndends") {
        $output = Array();
        //$sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name,m.material_name,m.material_subtype,m.material_type FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='pending' ORDER BY i.id DESC";
        $sql = "SELECT max(i.id) as id,i.indend_no,i.no,i.entry_date,i.entry_by,i.status FROM indend_raw i
        WHERE i.user_no='".$_GET["user_no"]."' AND i.status='pending'
        GROUP BY i.indend_no,i.no,i.entry_date,i.entry_by ,i.status ORDER BY max(i.id) DESC";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                //$sql8 = "SELECT i.*,m.material_name FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.no='".$row["no"]."' ";
                $sql8 = "SELECT i.*,m.material_name FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.no='".$row["no"]."' and i.status='pending' ";
                $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."' ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                       // $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $sql1 = "SELECT a.id,a.quotation_no,c.vendor_name,b.material_code, b.quotation_type,b.quotation_amt,b.quotation_per from quotation_dtl b
                        JOIN quotation_hdr a on b.quotation_hdr_id = a.id LEFT JOIN vendor c on a.vendor_id = c.id and a.plant_id = c.plant_id
                         where a.status='approve' and b.material_code = '".$row8["material_code"]."' ";     
                         echo $sql1;
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                
                                 
                            }
                        }
                        
                        $row8["vendors"] = $output1;
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "approveIndend") {
        $flag = 0;
        for($i = 0; $i < count($input); $i++) {
            $indend = $input[$i];
            $sql = "UPDATE indend_raw SET vendor_no='".$indend["vendor_no"]."', quotation_no='".$indend["quotation_no"]."', quotation_amt='".$indend["quotation_amt"]."',
            quotation_per='".$indend["quotation_per"]."', order_qty='".$indend["order_qty"]."', gst='".$indend["gst"]."', gross_total='".$indend["gross_total"]."', 
            gst_total='".$indend["gst_total"]."', net_total='".$indend["net_total"]."',status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', 
            approve_date='$entry_date' WHERE id='".$indend["id"]."'";
            if ($conn->query($sql) === FALSE) {
                $flag = 1;
            }
        }
        if ($flag == 0) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }  else if ($_GET["type"] == "getRejectedIndends") {
        $output = Array();
        
       //$sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_name FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='reject'  GROUP BY i.no";
       $sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_name FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='reject'ORDER BY id DESC";
       // echo $sql;
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                $sql8 = "SELECT i.*,m.material_name FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code 
                WHERE i.no='".$row["no"]."' ";
                // $sql8 = "SELECT * FROM indend_raw WHERE no='".$row["no"]."'";
                $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."' ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                        $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                
                                $materials = json_decode($row1["materials"]);
                                for ($i = 0; $i < count($materials); $i++) {
                                    $material = $materials[$i];
                                    if ($material->material_code == $row8["material_code"]) {
                                        $row1["quotation_amt"] = $material->quotation_amt;
                                        $row1["quotation_per"] = $material->quotation_per;
                                        $output1[] = $row1;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $row8["vendors"] = $output1;
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getPurchaseIndendsLog") {
       $output = Array();
        //$sql = "SELECT distinct i.*,v.vendor_name,v2.vendor_name as manufacturer_name,m.material_name,m.material_subtype,m.material_type FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' ORDER BY i.id DESC";
        //$sql = "SELECT i.entry_date,i.indend_no,no,i.entry_by FROM indend_raw i WHERE i.user_no='".$_GET["user_no"]."'        group by indend_no,entry_date,no,entry_by order by no desc";
        //echo $sql;
         $sql="SELECT i.entry_date,i.indend_no,no,i.entry_by FROM indend_raw i 
        WHERE i.user_no='".$_GET["user_no"]."' 
        AND DATE(entry_date) between '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'
        group by indend_no,entry_date,no,entry_by order by no desc";
        
        $result = $conn->query($sql);

        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                $sql8 = "SELECT i.*,m.material_name FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code 
                WHERE i.no='".$row["no"]."' ";
               
                $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."' ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                        $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                
                                $materials = json_decode($row1["materials"]);
                                for ($i = 0; $i < count($materials); $i++) {
                                    $material = $materials[$i];
                                    if ($material->material_code == $row["material_code"]) {
                                        $row1["quotation_amt"] = $material->quotation_amt;
                                        $row1["quotation_per"] = $material->quotation_per;
                                        $output1[] = $row1;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $row8["vendors"] = $output1;
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getAllPurchaseIndendsLog") {
       $output = Array();
        $sql = "SELECT i.*, v.vendor_name, m.material_type, m.material_name FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve'GROUP BY i.no";
        $result = $conn->query($sql);

        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                 $sql8 = "SELECT i.*,m.material_name FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.no='".$row["no"]."' ";
                $result8 = $conn->query($sql8);
                if ($result8->num_rows > 0) {
                    while ($row8 = $result8->fetch_assoc()) {
                        $lowest = array();
                        $highest = array();
        
                        $lowest["rate"] = 0;
                        $highest["rate"] = 0;
                        $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row8["material_code"]."' ORDER BY id DESC";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $sql2 = "SELECT c.*, v.vendor_name FROM challan c LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.challan_no='".$row1["challan_no"]."'";
                                $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $row["last_purchase"] = $row2["vendor_no"];
                                        
                                        $flag = 0;
                                        if ($lowest["rate"] < $lowest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $lowest["rate"] = $row1["rate"];
                                            $lowest["vendor_name"] = $row2["vendor_name"];
                                            $lowest["vendor_no"] = $row2["vendor_no"];
                                            $lowest["challan_no"] = $row1["challan_no"];
                                            $lowest["challan_date"] = $row2["challan_date"];
                                        }
                                        
                                        $flag = 0;
                                        if ($highest["rate"] < $highest["rate"]) {
                                            $flag = 1;
                                        }
                                        if ($flag == 0) {
                                            $highest["rate"] = $row1["rate"];
                                            $highest["vendor_name"] = $row2["vendor_name"];
                                            $highest["vendor_no"] = $row2["vendor_no"];
                                            $highest["challan_no"] = $row1["challan_no"];
                                            $highest["challan_date"] = $row2["challan_date"];
                                        }
                                    }
                                }
                            }
                        } else {
                            $row8["last_purchase"] = "";
                        }
                        
                        $row8["lowest"] = $lowest;
                        $row8["highest"] = $highest;
                        
                        $output1 = Array();
                        $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["material_code"]."%' AND q.status='approve'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                
                                if ($row1["vendor_no"] == $row["expected_vendor"]) {
                                    $row1["last_purchase"] = "yes";
                                } else {
                                    $row1["last_purchase"] = "no";
                                }
                                
                                $materials = json_decode($row1["materials"]);
                                for ($i = 0; $i < count($materials); $i++) {
                                    $material = $materials[$i];
                                    if ($material->material_code == $row["material_code"]) {
                                        $row1["quotation_amt"] = $material->quotation_amt;
                                        $row1["quotation_per"] = $material->quotation_per;
                                        $output1[] = $row1;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $row8["vendors"] = $output1;
                        $output8[] = $row8;
                    }
                }
                $row["materials"] = $output8;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET['type'] == 'downloadIndendsLog'){
        require '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Indend Of Raw Material'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.= "";

        $html.='
        <h2 style="text-align:center">Indend Of Raw Material</h2>
        <table cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Indend No.</td>
                    <td style="width: 10%;">vendor No</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 10%;">Material code</td>
                    <td style="width: 10%;">Material Name</td>
                    <td style="width: 10%;">req. qty</td>
                    <td style="width: 10%;">Required For</td>
                    <td style="width: 15%;">Requirement</td>
                    <td style="width: 10%;">Status</td>
                </tr>
            </thead>
            <tbody>';
            $i=1;
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_raw i LEFT JOIN material m ON i.material_code=m.material_code WHERE  i.user_no='".$_GET["user_no"]."' AND i.material_code LIKE '%".$_GET["material_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'</td>
                        <td style="width: 10%;">'.$row['indend_no'].'</td>
                        <td style="width: 10%;">'.$row['expected_vendor'].'</td>
                        <td style="width: 10%;">'.$row['material_subtype'].'</td> 
                        <td style="width: 10%;">'.$row['material_code'].'</td>
                        <td style="width: 10%;">'.$row['material_name'].'</td>
                        <td style="width: 10%;text-align:right;">'.$row['req_qty'].'</td>
                        <td style="width: 10%;">'.$row['required_for'].'</td>
                        <td style="width: 15%;">'.$row['requirement'].'</td>
                        <td style="width: 10%;">'.$row['status'].'</td>
                    </tr>
                </tbody>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Raw Material Indend Log.pdf', 'I');
    }
   else if ($_GET['type'] == 'downloadPurchaseIndendsLog') {
        require '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Indent / Requisition Of Raw Material'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.= "";
      
        $html.='
        <h2 style="text-align:center">Indent / Requisition Of Raw Material</h2>
        <table cellpadding="5" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;"><b>Sr.</b></td>
                    <td style="width: 20%;"><b>Date</b></td>
                    <td style="width: 20%;"><b>Request NO.</b></td>
                    <td style="width: 20%;"><b>No Of Items</b></td>
                    <td style="width: 20%;"><b>Entry By</b></td>
                </tr>';
                $i=1;
                $R=1;
        //$sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name,m.material_name,m.material_subtype,m.material_type FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' ORDER BY i.id DESC";
             $sql="SELECT i.entry_date,i.request_no,no,i.entry_by FROM indend_raw i 
        WHERE i.user_no='".$_GET["user_no"]."'  and plant_id='".$_GET["plant_id"]."'  
       
        group by request_no,entry_date,no,entry_by order by no desc";
        //  echo $sql = "SELECT i.entry_date,i.request_no,no,i.entry_by FROM indend_raw i 
      // WHERE i.user_no='".$_GET["user_no"]."'  and plant_id='".$_GET["plant_id"]."'
       
        //group by request_no,entry_date,no,entry_by ";
        
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
            $html.='
            <tr>
                <td style="width: 20%;">'.$i.'.</td>
                <td style="width: 20%;">'.date('d-m-Y', strtotime($row['entry_date'])).'</td>
                <td style="width: 20%;">'.$row['request_no'].'</td>
                <td style="width: 20%;">'.$R.'</td>
                <td style="width: 20%;">'.$row['entry_by'].'</td>
            </tr>';
            $i++;
            }
        }
        $html.='</table>
        
        <div></div>
          <hr>
    
    
        
        ';


        $pdf->writeHTML($html, true, false, false, false, '');
      $pdf->Output('Raw Material Indend Log.pdf', 'I');
    }
    
    
    else if ($_GET['type'] == 'downloadPurchaseIndend') {
        require '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Purchase Requisition';
        $_GET['pdftype'] = 'onlyheader';
        $_GET['pdffont'] = 'helvetica';
        $_GET['pdffonts'] = 9;
        $_GET['pdfy'] = 40;
        $_GET['pdftop'] = 12;
        $_GET['pdfleft'] = 12;
        $_GET['pdfright'] = 12;
        $_GET['pdfbottom'] = 12;
        $_GET['pdfpage'] = 'P';
        include('../../pdfimp2.php');

        $prEsc = function ($value) {
            return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
        };
        $prDate = function ($value) use ($prEsc) {
            if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                return '';
            }
            $ts = strtotime($value);
            return $ts ? date('m/d/Y', $ts) : $prEsc($value);
        };
        $prMoney = function ($value) {
            if ($value === null || $value === '' || !is_numeric($value)) {
                return '';
            }
            return number_format((float) $value, 2, '.', '');
        };

        $requestNo = isset($_GET['request_no']) ? $conn->real_escape_string($_GET['request_no']) : '';
        $prNotesCol = @$conn->query("SHOW COLUMNS FROM indend_raw LIKE 'pr_notes'");
        if (!$prNotesCol || $prNotesCol->num_rows === 0) {
            @$conn->query("ALTER TABLE indend_raw ADD pr_notes TEXT NULL DEFAULT NULL");
        }
        if ($requestNo === '') {
            echo 'Missing request number.';
        } else {
            $header = array(
                'vendor' => '', 'contact' => '', 'entry_date' => '', 'department' => '', 'project' => '',
                'pr_no' => $requestNo, 'po_no' => '', 'entry_by' => '', 'approve_by' => '', 'approve_date' => '', 'notes' => '',
            );
            $materials = array();
            $vendors = array();
            $departments = array();
            $cadTotal = 0.0;

            $sqlHdr = "SELECT i.*, v.vendor_name, v.contact_person, v.contact_number
                FROM indend_raw i
                LEFT JOIN vendor v ON i.vendor_no = v.vendor_no
                WHERE i.request_no = '" . $requestNo . "'
                ORDER BY i.id ASC";
            $resHdr = $conn->query($sqlHdr);
            if ($resHdr && $resHdr->num_rows > 0) {
                while ($row = $resHdr->fetch_assoc()) {
                    if ($header['entry_date'] === '' && !empty($row['entry_date'])) { $header['entry_date'] = $row['entry_date']; }
                    if ($header['entry_by'] === '' && !empty($row['entry_by'])) { $header['entry_by'] = $row['entry_by']; }
                    if ($header['approve_by'] === '' && !empty($row['approve_by'])) { $header['approve_by'] = $row['approve_by']; }
                    if ($header['approve_date'] === '' && !empty($row['approve_date'])) { $header['approve_date'] = $row['approve_date']; }
                    if ($header['notes'] === '' && !empty($row['pr_notes'])) { $header['notes'] = $row['pr_notes']; }
                    if (!empty($row['indend_no'])) { $header['pr_no'] = $row['indend_no']; }
                    if (!empty($row['department'])) { $departments[$row['department']] = true; }
                    if (!empty($row['vendor_name'])) { $vendors[$row['vendor_name']] = true; }
                    if ($header['contact'] === '') {
                        if (!empty($row['contact_person'])) {
                            $header['contact'] = $row['contact_person'];
                            if (!empty($row['contact_number'])) { $header['contact'] .= ' / ' . $row['contact_number']; }
                        } elseif (!empty($row['contact_number'])) {
                            $header['contact'] = $row['contact_number'];
                        }
                    }
                }
            }
            $header['vendor'] = implode(', ', array_keys($vendors));
            $header['department'] = implode(', ', array_keys($departments));
            if ($header['contact'] === '' && $header['entry_by'] !== '') { $header['contact'] = $header['entry_by']; }

            $sqlMat = "SELECT i.*, m.material_name, g.material_name AS gm_material, m.grade
                FROM indend_raw i
                LEFT JOIN my_view m ON i.material_code = m.material_code
                LEFT JOIN general_material g ON i.material_code = g.material_code
                WHERE i.request_no = '" . $requestNo . "'
                ORDER BY i.id ASC";
            $resMat = $conn->query($sqlMat);
            if ($resMat && $resMat->num_rows > 0) {
                while ($row = $resMat->fetch_assoc()) {
                    $gradeName = '';
                    if (!empty($row['grade'])) {
                        $gradeIds = $conn->real_escape_string($row['grade']);
                        $qGrade = "SELECT GROUP_CONCAT(grade) AS gradeName FROM grade WHERE id IN ('" . $gradeIds . "')";
                        $resGrade = $conn->query($qGrade);
                        if ($resGrade && ($g = $resGrade->fetch_assoc()) && !empty($g['gradeName'])) { $gradeName = $g['gradeName']; }
                    }
                    $desc = trim(($row['material_name'] ?? '') . ' ' . ($row['gm_material'] ?? ''));
                    if ($gradeName !== '') { $desc = trim($desc . ' (' . $gradeName . ')'); }
                    $unitPrice = $row['quotation_amt'] ?? '';
                    if (!empty($row['quotation_per']) && $unitPrice !== '') { $unitPrice = $unitPrice . ' / ' . $row['quotation_per']; }
                    $lineTotal = '';
                    if (!empty($row['net_total']) && is_numeric($row['net_total'])) {
                        $lineTotal = (float) $row['net_total'];
                        $cadTotal += $lineTotal;
                    } elseif (!empty($row['gross_total']) && is_numeric($row['gross_total'])) {
                        $lineTotal = (float) $row['gross_total'];
                        $cadTotal += $lineTotal;
                    }
                    $qty = trim(($row['req_qty'] ?? '') . (!empty($row['unit']) ? ' ' . $row['unit'] : ''));
                    $materials[] = array(
                        'catalogue' => $row['material_code'] ?? '',
                        'description' => $desc,
                        'gl_code' => $row['material_code'] ?? '',
                        'qty' => $qty,
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal === '' ? '' : $prMoney($lineTotal),
                    );
                }
            }

            $base = 'font-family:helvetica,arial,sans-serif;font-size:8.5px;line-height:1.5;';
            $border = 'border:1px solid #000;';
            $cellPad = 'padding:7px 6px;';
            $cell = $border . 'vertical-align:middle;' . $cellPad . $base;
            $cellLbl = $cell . 'font-weight:bold;background-color:#f5f8fc;';
            $cellHdr = $border . 'vertical-align:middle;padding:8px 5px;text-align:center;font-weight:bold;background-color:#d9e2f3;line-height:1.4;' . $base;
            $cellEmpty = $border . 'vertical-align:middle;padding:7px 6px;height:22px;line-height:1.5;' . $base;
            $tbl = 'border-collapse:collapse;width:100%;';
            $sectionGap = 'margin-top:8px;';
            $titleBlue = 'color:#1f4e9a;';

            $html .= '<table cellpadding="3" cellspacing="0" style="' . $tbl . $base . 'margin-bottom:8px;">
            <tr>
              <td align="center" style="padding:6px 8px;' . $titleBlue . 'font-size:16px;font-weight:bold;line-height:1.7;">PURCHASE REQUISITION</td>
            </tr>
            </table>';

            $html .= '<table cellpadding="3" cellspacing="0" style="' . $tbl . $sectionGap . $base . '">
            <tr>
              <td width="14%" style="' . $cellLbl . '">VENDOR</td>
              <td width="36%" style="' . $cell . '">' . $prEsc($header['vendor']) . '</td>
              <td width="14%" style="' . $cellLbl . '">P.R. #:</td>
              <td width="36%" style="' . $cell . '">' . $prEsc($header['pr_no']) . '</td>
            </tr>
            <tr>
              <td style="' . $cellLbl . '">CONTACT</td>
              <td style="' . $cell . '">' . $prEsc($header['contact']) . '</td>
              <td style="' . $cellLbl . '">P.O. #:</td>
              <td style="' . $cell . '">' . $prEsc($header['po_no']) . '<br><span style="font-size:7px;line-height:1.6;">FOR PURCHASING DEPT. USE ONLY</span></td>
            </tr>
            <tr>
              <td style="' . $cellLbl . '">DATE</td>
              <td style="' . $cell . '">' . $prEsc($prDate($header['entry_date'])) . '</td>
              <td style="' . $cellLbl . 'vertical-align:middle;" rowspan="3">ATTENTION LEVEL</td>
              <td style="' . $cell . '">Routine</td>
            </tr>
            <tr>
              <td style="' . $cellLbl . '">DEPARTMENT</td>
              <td style="' . $cell . '">' . $prEsc($header['department']) . '</td>
              <td style="' . $cell . '">Immediate</td>
            </tr>
            <tr>
              <td style="' . $cellLbl . '">PROJECT</td>
              <td style="' . $cell . '">' . $prEsc($header['project']) . '</td>
              <td style="' . $cell . '">&nbsp;</td>
            </tr>
            </table>';

            $html .= '<table cellpadding="3" cellspacing="0" style="' . $tbl . $sectionGap . $base . '">
            <tr><td colspan="3" style="' . $cellHdr . '">MATERIAL CODES (if applicable)</td></tr>
            <tr>
              <td width="33%" style="' . $cell . '"><b>4230:</b> Active Raw Materials</td>
              <td width="33%" style="' . $cell . '"><b>4240:</b> Reference Products</td>
              <td width="34%" style="' . $cell . '"><b>4800:</b> Equipment Maintenance</td>
            </tr>
            <tr>
              <td style="' . $cell . '"><b>4250:</b> Excipient Raw Materials</td>
              <td style="' . $cell . '"><b>4300:</b> Chemicals</td>
              <td style="' . $cell . '"><b>4400:</b> Supplies</td>
            </tr>
            </table>';

            $html .= '<table cellpadding="3" cellspacing="0" style="' . $tbl . $sectionGap . $base . '">
            <tr>
              <td width="6%" style="' . $cellHdr . '">ITEM #</td>
              <td width="12%" style="' . $cellHdr . '">CATALOGUE NUMBER</td>
              <td width="28%" style="' . $cellHdr . '">DESCRIPTION</td>
              <td width="14%" style="' . $cellHdr . '">GENERAL LEDGER / MATERIAL CODE</td>
              <td width="12%" style="' . $cellHdr . '">QUANTITY</td>
              <td width="14%" style="' . $cellHdr . '">UNIT PRICE</td>
              <td width="14%" style="' . $cellHdr . '">TOTAL PRICE</td>
            </tr>';

            $rowNo = 1;
            foreach ($materials as $mat) {
                $html .= '<tr>
                  <td style="' . $cell . 'text-align:center;">' . $rowNo . '</td>
                  <td style="' . $cell . 'text-align:center;">' . $prEsc($mat['catalogue']) . '</td>
                  <td style="' . $cell . 'text-align:left;">' . $prEsc($mat['description']) . '</td>
                  <td style="' . $cell . 'text-align:center;">' . $prEsc($mat['gl_code']) . '</td>
                  <td style="' . $cell . 'text-align:center;">' . $prEsc($mat['qty']) . '</td>
                  <td style="' . $cell . 'text-align:right;">' . $prEsc($mat['unit_price']) . '</td>
                  <td style="' . $cell . 'text-align:right;">' . $prEsc($mat['total_price']) . '</td>
                </tr>';
                $rowNo++;
            }
            $html .= '<tr>
              <td colspan="3" style="' . $cellEmpty . '">&nbsp;</td>
              <td style="' . $cellLbl . 'text-align:center;">Total</td>
              <td style="' . $cellLbl . 'text-align:center;">USD</td>
              <td style="' . $cellLbl . 'text-align:center;">CAD</td>
              <td style="' . $cell . 'text-align:right;font-weight:bold;">' . $prEsc($prMoney($cadTotal)) . '</td>
            </tr></table>';

            $html .= '<table cellpadding="3" cellspacing="0" style="' . $tbl . $sectionGap . $base . '">
            <tr>
              <td width="12%" style="' . $cellLbl . 'vertical-align:top;">NOTE(S)</td>
              <td width="88%" style="' . $border . 'vertical-align:top;padding:10px 8px;height:56px;line-height:1.6;">' . nl2br($prEsc($header['notes'])) . '</td>
            </tr></table>';

            $html .= '<table cellpadding="3" cellspacing="0" style="' . $tbl . 'margin-top:12px;' . $base . '">
            <tr>
              <td width="18%" style="' . $cellLbl . '">REQUESTED BY:</td>
              <td width="32%" style="' . $cell . '">' . $prEsc($header['entry_by']) . '</td>
              <td width="10%" style="' . $cellLbl . 'text-align:center;">DATE:</td>
              <td width="40%" style="' . $cell . '">' . $prEsc($prDate($header['entry_date'])) . '</td>
            </tr>
            <tr>
              <td style="' . $cellLbl . '">APPROVED BY:</td>
              <td style="' . $cell . '">' . $prEsc($header['approve_by']) . '</td>
              <td style="' . $cellLbl . 'text-align:center;">DATE:</td>
              <td style="' . $cell . '">' . $prEsc($prDate($header['approve_date'])) . '</td>
            </tr></table>';

            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Purchase_Requisition_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $requestNo) . '.pdf', 'I');
        }
    }


}





else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>