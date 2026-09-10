<?php
 

// ini_set('display_errors', 1);
// error_reporting(E_ALL);


 
    require '../db.php';
    require '../token.php';
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
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "saveJobwork") {
        
            $check = false;
            $sql1 = "SELECT MAX(id) as id FROM jobwork";
        
            $result1 = $conn->query($sql1);
            $row1 = $result1->fetch_assoc();
            $last_id=$row1["id"]+1; 
        
            $jobwork_no='';
            $padded_id = str_pad($last_id, 5, '0', STR_PAD_LEFT);
            $jobwork_no = "JB". $padded_id;
       
        
            $json_obj = json_encode($input["jobWorkData"]);
            $array = json_decode($json_obj, true);
  
            foreach ($array as $values){
        
        
                $sql = "INSERT INTO `jobwork`(`plant_id`, `jobwork_no`, `partyName`, `address`, `purpose`, `return_date`, `material_type`, 
                `material_code`, `batch_no`, `ar_no`, `issued_qty`, `qty_unit`, `mfg_date`, `exp_date`, `rel_date`, `entry_by`, `entry_date`,
                `status`,pack_size,pack_size_unit) VALUES ('".$_GET["plant_id"]."','$jobwork_no', '".$input["partyName"]."','".$input["address"]."','".$input["purpose"]."',
                '".$input["return_date"]."','".$values["material_type"]."','".$values["material_code"]."','".$values["batch_no"]."',
                '".$values["ar_no"]."','".$values["issued_qty"]."','".$values["qty_unit"]."','".$values["mfg_date"]."',
                '".$values["exp_date"]."','".$values["rel_date"]."','".$_GET["emp_id"]."','".$entry_date."','Pending','".$values["pack_size"]."','".$values["pack_size_unit"]."')";
                
                if ($conn->query($sql)) {   
                    $check = true;
                } else {
                     $check = false;
                }
        
            }
            
                if ($check) {   
                    echo "{\"status\":\"success\"}";
                } else {
                    echo "{\"status\":\"".$conn->error."\"}";
                }
        
    }
    else if ($_GET["type"] == "checkJobwork") {
         
        
            $sql2 = "UPDATE `jobwork` SET `status` = 'Approved' , `checkBy` = '".$_GET["emp_id"]."', `checkOn` = '".$entry_date."' 
            where jobwork_no = '".$input["jobwork_no"]."' ";
            
            if ($conn->query($sql2)) {   
                echo "{\"status\":\"success\"}";
                
                $sql = "SELECT * FROM jobwork WHERE jobwork_no = '".$input["jobwork_no"]."' AND plant_id =  '".$_GET["plant_id"]."'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        
                        $sql22 = "INSERT INTO `fg_material_issue`(`plant_id`, `ar_no`, `material_code`, `batch_no`, `issue_for`, `qty`,`unit`,
                        `entry_by`, `entry_date`) VALUES  ('".$_GET["plant_id"]."', '".$row["ar_no"]."' , '".$row["material_code"]."', 
                        '".$row["batch_no"]."', 'Jobwork', '".$row["issued_qty"]."', '".$row["qty_unit"]."','".$_GET["emp_id"]."',
                        '".$entry_date."')";
                        
                        $conn->query($sql22);
                        
                    }
                }
                
            } else {
                echo "{\"status\":\"".$conn->error."\"}";
            }
                
              
    }
    else if($_GET["type"] == "getMaterialSubType") {
        $output = Array();
        $sql = "SELECT * FROM material_type WHERE material_type = '".$_GET["material_type"]."' AND plant_id =  '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getMaterialBySubType") {
        $output = Array();
        $sql = "SELECT * FROM  material WHERE material_subtype = '".$_GET["material_subtype"]."' AND status = 'approve' AND plant_id =  '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getApprovedProducts") {
        $output = Array();
        $sql = "SELECT * FROM  product WHERE status = 'approve' AND plant_id =  '".$_GET["plant_id"]."' order by product_name ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getBatchDataFg") {
        $output = Array();
        $sql = "SELECT * FROM  fg_stock_book WHERE status = 'Approved' AND material_code =  '".$_GET["material_code"]."' AND plant_id =  '".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                    $sql1 = "SELECT IFNULL(SUM(qty), 0) as isshuedQty FROM fg_material_issue WHERE  plant_id =  '".$_GET["plant_id"]."' 
                    AND ar_no =  '".$row["ar_no"]."' AND batch_no =  '".$row["batch_no"]."' AND material_code =  '".$row["material_code"]."' ";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                             $row['isshuedQty'] = $row1['isshuedQty'];
                        }
                    }
                    
                $row["avaliableQty"] = number_format($row["qty"]- $row["isshuedQty"], 2, '.', '');

                 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "getMaterialForJobWork") { }
    else if($_GET["type"] == "getJobworkLog") {
        $output = Array();
        
        
         $sql = "SELECT jobwork_no,partyName,address,purpose,status,return_date FROM jobwork WHERE plant_id  = '".$_GET["plant_id"]."' 
        group by jobwork_no,partyName,address,purpose,status,return_date";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output2 = Array();
                        $sql2 = "SELECT * FROM jobwork WHERE jobwork_no  = '".$row["jobwork_no"]."'  ";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                        $sql1 = "SELECT product_name FROM product WHERE product_code  = '".$row2["material_code"]."'  ";
                                        $result1 = $conn->query($sql1);
                                        if ($result1->num_rows > 0) {
                                            while ($row1 = $result1->fetch_assoc()) {
                                                 $row2['product_name']  = $row1['product_name'] ;
                                            }
                                        }
                                        
                                        $output2[] = $row2;
                            }
                        }
                        
                $row['products'] = $output2;
                        
                $output[] = $row;
            }
        }
        

        
        
        echo json_encode($output);
    }
    else if($_GET["type"] == "getJobworkForChecking") {
        $output = Array();
        
        
         $sql = "SELECT jobwork_no,partyName,address,purpose,status,return_date FROM jobwork WHERE plant_id  = '".$_GET["plant_id"]."' 
        AND status = 'Pending' group by jobwork_no,partyName,address,purpose,status,return_date";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output2 = Array();
                        $sql2 = "SELECT * FROM jobwork WHERE jobwork_no  = '".$row["jobwork_no"]."'  ";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                        $sql1 = "SELECT product_name FROM product WHERE product_code  = '".$row2["material_code"]."'  ";
                                        $result1 = $conn->query($sql1);
                                        if ($result1->num_rows > 0) {
                                            while ($row1 = $result1->fetch_assoc()) {
                                                 $row2['product_name']  = $row1['product_name'] ;
                                            }
                                        }
                                        
                                        $output2[] = $row2;
                            }
                        }
                        
                $row['products'] = $output2;
                        
                $output[] = $row;
            }
        }
        

        
        
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadJobworkReport") {
        require '../tcpdf/tcpdf.php';
        $_GET['pdftype'] = 'invoiceheader'; include("../pdfimp.php");
        
        $html='<table border="1" cellpadding="5">
            <tr>
                <td style="width: 100%;">Job work provisions under section 143 of CGST act 2017 under section 2(68) of the CGST act 2017</td>
            </tr>
            <tr>
                <td style="width: 50%;" rowspan="3">Name & address of Buyer (Recipient) Bill To:<br><br><br><br><br></td>
                <td style="width: 25%;">Delivery Challan No.<br></td>
                <td style="width: 25%;">Date<br></td>
            </tr>
            <tr>
                <td style="width: 25%;">P.O. No. verbal<br></td>
                <td style="width: 25%;">Date<br></td>
            </tr>
            <tr>
                <td style="width: 25%;">Vehicle No.<br></td>
                <td style="width: 25%;">L.R. No.<br></td>
            </tr>
            <tr>
                <td style="width: 50%;" rowspan="4">Consignee Name & address (Shipped To):<br><br><br><br><br></td>
                <td style="width: 25%;">Transporter<br></td>
                <td style="width: 25%;">Date<br></td>
            </tr>
            <tr>
                <td style="width: 25%;">Destination<br></td>
                <td style="width: 25%;">Date<br></td>
            </tr>
            <tr>
                <td style="width: 25%;">Terms of Delivery<br></td>
                <td style="width: 25%;">Mode / Terms of Payment<br></td>
            </tr>
            <tr>
                <td style="width: 25%;">Broker Name<br></td>
                <td style="width: 25%;">Due Date<br></td>
            </tr>
            <tr style="font-size: 8px;">
                <td style="text-align: center;width: 05%;">Sr.<br>No.</td>
                <td style="text-align: center;width: 35%;">Description of Goods</td>
                <td style="text-align: center;width: 12%;">SAC/HSN<br>Classification</td>
                <td style="text-align: center;width: 12%;">Quantity<br>(in KGS)</td>
                <td style="text-align: center;width: 12%;">Rate<br>per Kgs</td>
                <td style="text-align: center;width: 12%;">Per</td>
                <td style="text-align: center;width: 12%;">Value of Supply</td>
            </tr>';
            for ($i = 0; $i < 5; $i++) {
                $html.='<tr style="font-size: 8px;">
                    <td style="width: 05%;"></td>
                    <td style="width: 35%;"></td>
                    <td style="width: 12%;"></td>
                    <td style="width: 12%;"></td>
                    <td style="width: 12%;"></td>
                    <td style="width: 12%;"></td>
                    <td style="width: 12%;"></td>
                </tr>';
            }
            $html.='<tr style="font-size: 8px;">
                    <td style="width: 05%;"></td>
                    <td style="width: 35%;text-align: right;"><b>Total</b></td>
                    <td style="width: 12%;"></td>
                    <td style="width: 12%;"></td>
                    <td style="width: 12%;"></td>
                    <td style="width: 12%;"></td>
                    <td style="width: 12%;"></td>
                </tr>
                <tr style="font-size: 9px;">
                    <td style="width: 100%;"><b>Invoice Value (in Words):</b></td>
                </tr>
                <tr>
                    <td style="width: 100%;">
                        <table border="0">
                            <tr style="font-size: 7px;">
                                <td><b>Tax Amount (In Words): (INR JOBWORKS)</b></td>
                            </tr>
                            <tr style="font-size: 7px;">
                                <td>Declaration:</td>
                            </tr>
                            <tr style="font-size: 8px;">
                                <td>Terms & Condition:</td>
                            </tr>
                        </table>
                    </td>
                </tr>';
        $html.='</table>
            <h5 style="text-align: center;">SUBJECT TO MUMBAI JURISDICTION</h5>
            <h6 style="text-align: center;">This is Computer Generated Invoice</h6>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('JOB WORK INVOICE.pdf', 'I');
    }

}

$conn->close();
?>