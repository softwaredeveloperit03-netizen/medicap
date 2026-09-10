<?php
    require '../../db.php';
    require '../../token.php';
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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveIndend") {
      $no = date("YmdHis", $timestamp);
        $flag = 0;
         
        $sql = "SELECT indend_no FROM indend_packing ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['indend_no'];
        }
        if($last_id==null){
            $last_id=1;
        }else{
            $int_var = (int)filter_var($last_id, FILTER_SANITIZE_NUMBER_INT);
            //  echo $int_var;
            $last_id = $int_var+1;
        }
        $number = substr(str_repeat(0, 4).$last_id, - 4);
        $ind_no = "PM".$number;
        
        
        for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];

            if ($temp["required_for"] == "Own") {
                $temp["client_code"] = "";
            }
            $sql = "INSERT INTO indend_packing (user_no,indend_no, no, material_code, req_qty, unit, requirement, purpose, expected_vendor, required_for, client_code,gst,vendor_type,manufacturer_no,vendor_no,specific_vendor,entry_by, entry_date, department, make,other_purpose) VALUES 
            ('".$_GET["user_no"]."','$ind_no','$no','".$temp["material_code"]."','".$temp["qty"]."','".$temp["unit"]."','".$temp["requirement"]."','".$temp["purpose"]."','".$temp["vendor"]."','".$temp["required_for"]."','".$temp["client_code"]."','".$temp["gst"]."','".$temp["vendor_type"]."','".$temp["manufacturer_no"]."','".$temp["vendor_no"]."','".$temp["specific_vendor"]."','".$_GET["emp_id"]."','".$entry_date."','".$temp["department"]."', '".$temp["make"]."', '".$temp["other_purpose"]."')";
            //  echo $sql;
           
              $conn->query($sql);
           
        }
       
        echo "{\"status\":\"success\"}";
    } else if ($_GET["type"] == "getPendingIndends") {
        $output = array();
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.department='".$_GET["department"]."' AND i.status='pending' GROUP BY i.no";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateIndend") {
        $sql = "UPDATE indend_packing SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getIndendsLog") {
        $output = array();
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade, v.vendor_name FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."'AND i.material_code LIKE '%".$_GET["material_code"]."%'";
        //$sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getMaterials") {
        $output = array();
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code GROUP BY i.material_code";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getCheckedIndends") {
        $output = Array();
        // $sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_name FROM indend_packing i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='pending'  GROUP BY i.no ORDER BY i.id DESC";
        $sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_name FROM indend_packing i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='pending' AND m.material_type='Packing Material' ORDER BY i.id DESC";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                $sql8 = "SELECT i.*,m.material_name FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.no='".$row["no"]."'  and i.status='pending' ";
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
    }
   else if ($_GET["type"] == "approveIndend") {
        $flag = 0;
        for($i = 0; $i < count($input); $i++) {
            $indend = $input[$i];
            $sql = "UPDATE indend_packing SET vendor_no='".$indend["vendor_no"]."', quotation_no='".$indend["quotation_no"]."',
            quotation_amt='".$indend["quotation_amt"]."', quotation_per='".$indend["quotation_per"]."', qty='".$indend["order_qty"]."',
            gst='".$indend["gst"]."', gross_total='".$indend["gross_total"]."', gst_total='".$indend["gst_total"]."', 
            net_total='".$indend["net_total"]."',status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', 
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
    }else if ($_GET["type"] == "getRejectedIndends") {
        $output = array();
        // $sql = "SELECT i.*, v.vendor_name, m.material_type, m.material_name FROM indend_packing i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='reject' GROUP BY i.no";
        $sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_name FROM indend_packing i
        LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no 
        LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."'
        AND i.status='reject' AND m.material_type='Packing Material' ORDER BY i.id DESC";
       
      $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                $sql8 = "SELECT i.*,m.material_name FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code WHERE i.no='".$row["no"]."' ";
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
    }
    else if ($_GET["type"] == "getPurchaseIndendsLog") {
       /* $output = array();
        // $sql = "SELECT i.*, m.material_subtype,m.material_name, m.grade, v.vendor_name FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."' AND i.department LIKE '%".$_GET["department_name"]."%' AND DATE(i.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND i.status='approve' ORDER BY i.id DESC";
        $sql = "SELECT i.*, m.material_subtype,m.material_name, m.grade, v.vendor_name FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."' AND i.department LIKE '%".$_GET["department_name"]."%' AND DATE(i.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY i.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);*/
        $output = Array();
        //$sql = "SELECT i.*, v.vendor_name, m.material_type, m.material_name FROM indend_raw i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' AND i.status='approve' AND department LIKE '%".$_GET["department_name"]."%' AND Date(i.approve_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY i.no ORDER BY i.id DESC ";
        //$sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name,m.material_name,m.material_subtype,m.material_type FROM indend_packing i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN material m ON i.material_code=m.material_code WHERE i.user_no='".$_GET["user_no"]."' ORDER BY i.id DESC";
        $sql = "SELECT i.entry_date,i.indend_no,no,i.entry_by,i.status FROM indend_packing i WHERE i.user_no='".$_GET["user_no"]."'
        AND DATE(entry_date) between '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'
        group by indend_no,entry_date,no,entry_by,i.status order by 1 desc";
        $result = $conn->query($sql);

        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                 $sql8 = "SELECT i.*,m.material_name,v.vendor_name  FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code LEFT JOIN vendor v ON v.vendor_no=i.vendor_no WHERE i.no='".$row["no"]."' ";
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
        
    }else if ($_GET["type"] == "getAllPurchaseIndendsLog") {
        $output = array();
        $sql = "SELECT i.*, m.material_subtype,m.material_name, m.grade, v.vendor_name FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.status='approve' ORDER BY i.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET['type'] == 'downloadIndendsLog'){
        require '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Indend Of Packing Material'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.= "";

        $html.='
        <h2 style="text-align:center">Indend Of Packing Material</h2>
<table cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;">Indend No.</td>
                    <td style="width: 8%;">Vendor No</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 7%;">Material Code</td>
                    <td style="width: 20%;">Material Name</td>
                    <td style="width: 15%;">Req.Qty</td>
                    <td style="width: 8%;">Required For</td>
                    <td style="width: 10%;">Requirement</td>
                    <td style="width: 7%;">Status</td>
                </tr>
            </thead>
             <tbody>';
      $i=1;
        $sql = "SELECT i.*, m.material_subtype, m.material_name, m.grade, v.vendor_name FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."'AND i.material_code LIKE '%".$_GET["material_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 5%;">'.$i.'</td>
                        <td style="width: 10%;">'.$row['indend_no'].'</td>
                        <td style="width: 8%;">'.$row['expected_vendor'].'</td>
                        <td style="width: 10%;">'.$row['material_subtype'].'</td> 
                        <td style="width: 7%;">'.$row['material_code'].'</td>
                        <td style="width: 20%;">'.$row['material_name'].'</td>
                        <td style="width: 15%;">'.$row['req_qty'].'</td>
                        <td style="width: 8%;">'.$row['required_for'].'</td>
                        <td style="width: 10%;">'.$row['requirement'].'</td>
                        <td style="width: 7%;">'.$row['status'].'</td>
                    </tr>
                       </tbody>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Packing Material IndendLog.pdf', 'I');
    
    } 
    else if ($_GET['type'] == 'downloadPurchaseIndendsLog'){
        require '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Indend / Requisition Of Packing Material'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html.= "";

          $html.='
          <h2 style="text-align:center">Indend / Requisition Of Packing Material</h2>
          <table cellpadding="5" border="1">
           
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 20%;"><b>Sr.</b></td>
                    <td style="width: 20%;"><b>Date</b></td>
                    <td style="width: 20%;"><b>Indend No.</b></td>
                    <td style="width: 20%;"><b>No Of Items</b></td>
                    <td style="width: 20%;"><b>Entry By</b></td>
                </tr>';
                  $ii=1; 
       //$sql = "SELECT i.*, m.material_subtype,m.material_name, m.grade, v.vendor_name FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.user_no='".$_GET["user_no"]."' AND i.department LIKE '%".$_GET["department_name"]."%' AND DATE(i.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY i.id DESC";
        //$sql = "SELECT i.*, m.material_subtype,m.material_name, m.grade, v.vendor_name FROM indend_packing i LEFT JOIN material m ON i.material_code=m.material_code LEFT JOIN vendor v ON i.vendor_no=v.vendor_no WHERE i.department LIKE '%".$_GET["department_name"]."%' AND DATE(i.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' AND i.status='approve'";
       $sql = "select a.*,b.no_items from ( SELECT i.entry_date,i.indend_no,no,i.entry_by FROM indend_packing i WHERE DATE(entry_date) between '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' group by i.entry_date,i.indend_no,no,i.entry_by )a left join (select indend_no as id,count(indend_no) as no_items FROM indend_packing i WHERE DATE(entry_date) between '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  group by indend_no) b on a.indend_no = b.id order by a.indend_no desc";
                 $result = $conn->query($sql);
               if($result->num_rows > 0) {
               while($row = $result->fetch_assoc()) {
            $html.='
            <tr>
                <td style="width: 20%;">'.$ii.'.</td>
               <td style="width: 20%;">'.date('d-m-Y', strtotime($row['entry_date'])).'</td>
                <td style="width: 20%;">'.$row['indend_no'].'</td>
                <td style="width: 20%;">'.$row['no_items'].'</td>
                <td style="width: 20%;">'.$row['entry_by'].'</td>
            </tr>';
            $ii++;
            }
        }
     
        $html.="</table>";

         $pdf->writeHTML($html, true, false, false, false, '');
          
        $pdf->Output('Packing Material Indend Log.pdf', 'I');
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>