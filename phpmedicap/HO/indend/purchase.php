<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

require '../db.php';
 
// require '../tcpdf/tcpdf.php';


 

 
 
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input'),true);

 
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if($_GET["type"]=="Get_IndentFor_directorApproval") {
        
        $output = array();

$sql = "SELECT Director_data FROM WO_deductions 
        WHERE director_approval='For Approval' 
        AND plant_id='$plantid'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // Decode JSON stored in Director_data
        $directorData = json_decode($row['Director_data'], true);

        // Add to output array
        $output[] = $directorData;
    }
}

echo json_encode($output);

    } 
    
     else  if ($_GET["type"] == "Update_Director_Status") {
        
        $orders = $input["orders"];   // Array of order_no values: ["FO1000035","FO1000036B"]
$entry_date = date("Y-m-d H:i:s");

 

     $sql = "UPDATE WO_deductions  
            SET 
                director_approval='".$input['status']."',
               
                director_approved_by='".$_GET['emp_id']."',
                director_approved_on='$entry_date'
            WHERE id='".$input['id']."'";

    $conn->query($sql);
   
    
    
    
 

echo json_encode(["status" => "success"]);

    }
    
     if ($_GET["type"] == "ReceivingForcastQty") {
         
         
           $output=Array();
            
        
          $sql = "SELECT  a.ordered_qty,p.purchase_type,p.approve_date as po_approve_date,c.approve_date as recevingDate, ir.indend_no,ir.approve_date as indend_date,
          p.po_no,p.approve_date as po_date,p.approve_date as actual_del_date,(select material_name from material where material_code=a.material_code limit 1) as material_name,
                        a.appxDelivery,a.appxPurchase,a.apprxIndend,a.responsiblePerson,m.Purchase_prepare_date,m.PurchaseDeliveryTime,m.material_type,m.material_code,
                        (select workorder_no from WO_deductions where id=a.WO_deductions_id limit 1) as workorder_no
                        FROM 
                        mrp_raised_indnd_qty a    left join indend_raw ir on a.indend_id=ir.id and a.material_code=ir.material_code
                        left join purchaseorder p on ir.indend_no=p.indent_no
                        left join challan c on c.po_no=p.po_no left join material m on a.material_code=m.material_code and ir.plant_id='$plantid'";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                            
                            
                            $date = new DateTime($row['apprxIndend']);
                            // Add days
                            $date->modify("+{$row['Purchase_prepare_date']} days");
                            // Store back in array as formatted date
                            $row['forcastPurchase'] = $date->format("Y-m-d");
                            
                            
                            
                            $date1 = new DateTime($row['forcastPurchase']);
                            // Add days
                            $date1->modify("+{$row['PurchaseDeliveryTime']} days");
                            // Store back in array as formatted date
                            $row['DeliveyDate'] = $date1->format("Y-m-d");
                          
                          
                          
                            $date2 = new DateTime($row['DeliveyDate']);
                            // Add days
                            $date2->modify("+{$row['Sampling_prepare_date']} days");
                            // Store back in array as formatted date
                            $row['samplingDate'] = $date2->format("Y-m-d");
                          
                          
                          
                            $date3 = new DateTime($row['releaseDate']);
                            // Add days
                            $date3->modify("+{$row['release_prepare_date']} days");
                            // Store back in array as formatted date
                            $row['releaseDate'] = $date3->format("Y-m-d");
                            
                            
                $output[] = $row;
            }
        }
        //  header('Content-Type: application/json');
        echo json_encode($output);
     
     }
 

$conn->close();
?>