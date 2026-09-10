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
        
         $no = date("YmdHis", $timestamp);
        $flag = 0;
         
        $sql = "SELECT indend_no FROM indend_glassware ORDER BY id DESC LIMIT 1";
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
        $ind_no = "G".$number;
        
        for ($i = 0; $i < count($input); $i++) {
            $temp = $input[$i];

            if ($temp["required_for"] == "Own") {
                $temp["client_code"] = "";
            }
            $sql = "INSERT INTO indend_glassware (user_no,indend_no,no, glassware_no,vendor_no,manufacturer_no, qty, unit, requirement, purpose, expected_vendor, required_for, client_code, entry_by, entry_date, department, make) VALUES 
            ('".$_GET["user_no"]."','$ind_no','$no','".$temp["glassware_no"]."','".$temp["vendor_no"]."','".$temp["manufacturer_no"]."','".$temp["qty"]."','".$temp["unit"]."','".$temp["requirement"]."','".$temp["purpose"]."','".$temp["vendor"]."','".$temp["required_for"]."','".$temp["client_code"]."','".$_GET["emp_id"]."','".$entry_date."','".$temp["department"]."', '".$temp["make"]."')";
            if($conn->query($sql)) {
                $flag = 0;
            } else {
                $flag = 1;
                break;
            }
        }
        if ($flag == 0) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getPendingIndends") {
        $output = array();
        $sql = "SELECT i.*, m.name, m.capacity, m.unit, m.glassware_class, m.description FROM indend_glassware i LEFT JOIN glassware m ON i.glassware_no=m.glassware_no WHERE i.user_no='".$_GET["user_no"]."' AND i.department='".$_GET["department"]."' AND i.status='pending'";
       // echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateIndend") {
        $sql = "UPDATE indend_glassware SET status='".$_GET["status"]."', check_by='".$_GET["emp_id"]."', check_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getIndendsLog") {
        $output = array();
        $sql = "SELECT i.*, m.name, m.capacity, m.unit, m.glassware_class FROM indend_glassware i LEFT JOIN glassware m ON i.glassware_no=m.glassware_no WHERE i.user_no='".$_GET["user_no"]."' AND i.department='".$_GET["department"]."' AND DATE(i.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getCheckedIndends") {
        $output = Array();
        // $sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name,m.name FROM indend_glassware i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN glassware m ON i.glassware_no=m.glassware_no WHERE i.user_no='".$_GET["user_no"]."' AND i.status='pending'  GROUP BY i.no ORDER BY i.id DESC";
       // $sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name, g.name as glassware_name  FROM indend_glassware i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN glassware g ON i.glassware_no=g.glassware_no WHERE i.user_no='".$_GET["user_no"]."' AND i.status='pending' ORDER BY i.id DESC";
       
           $sql = "SELECT max(i.id) as id,i.indend_no,i.no,i.entry_date,i.entry_by,i.status FROM indend_glassware i
        WHERE i.user_no='".$_GET["user_no"]."' AND i.status='pending'
        GROUP BY i.indend_no,i.no,i.entry_date,i.entry_by ,i.status ORDER BY max(i.id) DESC";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                $sql8 = "SELECT i.*,m.name FROM indend_glassware i LEFT JOIN glassware m ON i.glassware_no=m.glassware_no WHERE i.no='".$row["no"]."'  and i.status='pending'  ";
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
                        $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["glassware_no"]."%' AND q.status='approve'";
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
                                    if ($material->material_code == $row8["glassware_no"]) {
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
    } else if ($_GET["type"] == "approveIndend") {
        $flag = 0;
        for($i = 0; $i < count($input); $i++) {
            $indend = $input[$i];
            $sql = "UPDATE indend_glassware SET vendor_no='".$indend["vendor_no"]."', quotation_no='".$indend["quotation_no"]."', quotation_amt='".$indend["quotation_amt"]."', quotation_per='".$indend["quotation_per"]."', gst='".$indend["gst"]."', gross_total='".$indend["gross_total"]."', gst_total='".$indend["gst_total"]."', net_total='".$indend["net_total"]."',status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date', order_qty='".$indend["order_qty"]."' WHERE id='".$indend["id"]."'";
            //$sql = "UPDATE indend_glassware SET vendor_no='".$indend["vendor_no"]."', quotation_no='".$indend["quotation_no"]."', quotation_amt='".$indend["quotation_amt"]."', quotation_per='".$indend["quotation_per"]."', order_qty='".$indend["order_qty"]."', gst='".$indend["gst"]."', gross_total='".$indend["gross_total"]."', gst_total='".$indend["gst_total"]."', net_total='".$indend["net_total"]."',status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$indend["id"]."'";
            // echo $sql;
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
        $output = Array();
        $sql = "SELECT i.*, m.name, m.capacity, m.unit, m.glassware_class, m.description, m.gst, v.vendor_name FROM indend_glassware i LEFT JOIN vendor v ON i.expected_vendor=v.vendor_no LEFT JOIN glassware m ON i.glassware_no=m.glassware_no WHERE i.user_no='".$_GET["user_no"]."' AND i.status='reject'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                

                $lowest = array();
                $highest = array();

                $lowest["rate"] = 0;
                $highest["rate"] = 0;
                $sql1 = "SELECT * FROM challan_materials WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row["glassware_no"]."' ORDER BY id DESC";
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
                    $row["last_purchase"] = "";
                }
                
                $row["lowest"] = $lowest;
                $row["highest"] = $highest;
                
                $output1 = Array();
                $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row["glassware_no"]."%' AND q.status='approve'";
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
                            if ($material->material_code == $row["glassware_no"]) {
                                $row1["quotation_amt"] = $material->quotation_amt;
                                $row1["quotation_per"] = $material->quotation_per;
                                $output1[] = $row1;
                                break;
                            }
                        }
                    }
                }
                
                $row["vendors"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPurchaseIndendsLog") {
        $output = Array();
        // $sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name,m.name FROM indend_glassware i LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN glassware m ON i.glassware_no=m.glassware_no WHERE i.user_no='".$_GET["user_no"]."'  GROUP BY i.no ORDER BY i.id DESC";
       
       /* $sql = "SELECT i.*,v.vendor_name,v2.vendor_name as manufacturer_name, g.name as glassware_name FROM indend_glassware i
        LEFT JOIN vendor v ON i.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON i.manufacturer_no=v2.vendor_no LEFT JOIN glassware
        g ON g.glassware_no = i.glassware_no WHERE i.user_no='".$_GET["user_no"]."' 
        AND DATE(entry_date) between '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'
        ORDER BY i.id DESC";*/
          $sql="SELECT i.entry_date,i.indend_no,no,i.entry_by FROM indend_glassware i 
        WHERE i.user_no='".$_GET["user_no"]."' 
        AND DATE(entry_date) between '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'
        group by indend_no,entry_date,no,entry_by order by no desc";
       // echo $sql;
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                $output8 = array();
                $sql8 = "SELECT i.*,m.name FROM indend_glassware i LEFT JOIN glassware m ON i.glassware_no=m.glassware_no WHERE i.no='".$row["no"]."' ";
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
                        $sql1 = "SELECT q.*, v.vendor_name FROM quotation q LEFT JOIN vendor v ON q.vendor_no=v.vendor_no WHERE q.user_no='".$_GET["user_no"]."' AND q.materials LIKE '%".$row8["glassware_no"]."%' AND q.status='approve'";
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
                                    if ($material->material_code == $row8["glassware_no"]) {
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
    } else if ($_GET['type'] == 'downloadPurchaseIndendsLog'){
        require '../../tcpdf/tcpdf.php';
        $_GET['filename'] = 'Glassware Indend '; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        

        $html.='
        <h2 style="text-align:center">Glassware Indend</h2>
        <table cellpadding="5" border="1">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                     <td style="width: 20%;"><b>Sr.</b></td>
                    <td style="width: 20%;"><b>Date</b></td>
                    <td style="width: 20%;"><b>Indend No.</b></td>
                    <td style="width: 20%;"><b>No Of Items</b></td>
                    <td style="width: 20%;"><b>Entry By</b></td>
                </tr>';
                $i=1;
        
        $sql = "select a.*,b.no_items from ( SELECT i.entry_date,i.indend_no,no,i.entry_by FROM indend_glassware i WHERE DATE(entry_date) between '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' group by i.entry_date,i.indend_no,no,i.entry_by )a left join (select indend_no as id,count(indend_no) as no_items FROM indend_glassware i WHERE DATE(entry_date) between '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  group by indend_no) b on a.indend_no = b.id order by a.indend_no desc";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
              
            $html.='
            <tr >
                        <td style="width: 20%;">'.$i.'.</td>
                <td style="width: 20%;">'.date('d-m-Y', strtotime($row['entry_date'])).'</td>
                <td style="width: 20%;">'.$row['indend_no'].'</td>
                <td style="width: 20%;">'.$row['no_items'].'</td>
                <td style="width: 20%;">'.$row['entry_by'].'</td>
               </tr>';
                    $i++;
            }
        }
           
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Glassware Indend Log.pdf', 'I');
    }

} else {
    echo "{\"status\":\"invalids\"}";
}

$conn->close();
?>