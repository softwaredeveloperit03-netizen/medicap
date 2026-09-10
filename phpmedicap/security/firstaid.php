<?php

//   ini_set('display_errors', 1);
//     error_reporting(E_ALL); 

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

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);

if($_GET["type"]=="getFisrtAidDetails"){
	$output = Array();
	$sql = "SELECT f.*, (SELECT firstname from employee e where e.emp_id=f.employee limit 1 ) as firstname FROM fisrt_aid f WHERE f.status='pending' group by f.id ,firstname ORDER BY id DESC;";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
} else if($_GET["type"]=="saveFirtsAid") {

	$input = json_decode(file_get_contents('php://input'),true);

	define('UPLOAD_DIR', 'images/');
    $image_parts = explode(";base64,", $input['photo']);
    $image_base64 = base64_decode($image_parts[1]);
    $file = UPLOAD_DIR . uniqid() . '.png';
	file_put_contents($file, $image_base64);
	$photo = $file;

	$entry_date = date('Y-m-d H:i:s');

	$sql = "INSERT INTO fisrt_aid  
	(qty,product,department,aider,employee,designation,incident,category,location,details,treatment,treatment_accept,reason,incident_date,entry_date,entry_by,plant_id,bal_qty) 
	VALUES 
	('".$input["qty"]."','".$input["Medicine"]."','".$input["department"]."','".$input["aider"]."','".$input["employee"]."','".$input["designation"]."','".$input["incident"]."','".$input["category"]."','".$input["location"]."','".$input["details"]."','".$input["treatment"]."','".$input["treatment_accept"]."','".$input["reason"]."','".$input["incident_date"]."','".$entry_date."','".$_GET["emp_id"]."','".$_GET["plant_id"]."','".$input["bal_qty"]."')";

	if($conn->query($sql)){	
		echo json_encode(["status"=>"success"]);
	}else{
		echo json_encode(["status"=>$conn->error]);
	}
}else if($_GET["type"]=="updateFirstAid"){
	$sql = "UPDATE fisrt_aid SET item_name='".$input["item_name"]."',available_qty='".$input["available_qty"]."',consum_qty='".$input["consum_qty"]."',bal_qty='".$input["bal_qty"]."', status='active' WHERE id ='".$_GET["id"]."'";
	if($conn->query($sql)){	
		echo "{\"status\":\"success\"}";
	} else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
if($_GET["type"]=="getAidConsumption"){
	$output = Array();
	$sql = "SELECT * FROM fisrt_aid  ORDER BY id DESC";
// 	$sql = "SELECT * FROM fisrt_aid WHERE status='active' ORDER BY id DESC";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
		   $row['avbl_qty'] = (int)$row['qty'] + (int)$row['bal_qty'];

			$output[] = $row;
		}
	}
	echo json_encode($output);
}
if($_GET["type"]=="getSaveKitProd"){
    
    $output = Array();
$sql = "SELECT 
    a.product, 
    SUM(CASE WHEN a.exp_date >= CURDATE() THEN a.qty ELSE 0 END) AS qty,  -- Only sum non-expired products
    (SELECT SUM(b.qty) 
     FROM fisrt_aid b 
     WHERE b.product = a.product) AS used_qty
FROM first_aid_data a
GROUP BY a.product
ORDER BY a.product ASC;;
";

$result = $conn->query($sql);

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        // Prepare an array for related data
        $output1 = Array();
        
        // Get detailed information for the product
        $sql1 = "SELECT * FROM first_aid_data WHERE product = '".$conn->real_escape_string($row["product"])."' ORDER BY product ASC";
        $result1 = $conn->query($sql1);
        
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                // Add each row from the second query to the output array
                $output1[] = $row1;
            }
        }
        
        // Add the related data (output1) to the main result row
        
        $row["bal_qty"] =  $row["qty"]- $row["used_qty"];
        $row["data"] = $output1;
        
        // Add the main row with its related data to the final output
        $output[] = $row;
    }
}

// Output the final result as JSON
echo json_encode($output);

}
else if($_GET["type"]=="SaveKitProd"){
	$sql = "insert into  first_aid_data (type,product,exp_date,qty,batch_no)values('".$input["type"]."','".$input["Medicine"]."','".$input["exp_date"]."','".$input["qty"]."','".$input["batch_no"]."')";
	if($conn->query($sql)){	
		echo "{\"status\":\"success\"}";
	} else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="exitVechile"){
	$sql = "UPDATE vechile_entry SET out_time='".$entry_date."',outentry_by='".$_GET["emp_id"]."', status='inprocess' WHERE id ='".$_GET["id"]."'";
	if($conn->query($sql)){	
		echo "{\"status\":\"success\"}";
	} else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getMaterialOutList"){
	$sql = "SELECT * FROM materialout WHERE DATE(entry_date)=CURDATE()";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if($_GET["type"]=="saveMaterialOutForm"){
	if(!isset($input["vehicle_no"])){
		$input["vehicle_no"] = '';
	}
	$sql = "INSERT INTO materialout (invoice_no,material_name,vendor_name,reason,quantity,returnable,request_by,transport,driverName,driverMobile,vehicle_no,entry_by,entry_date) 
	VALUES ('".$input["invoiceNo"]."','".$input["materialName"]."','".$input["vendorName"]."','".$input["reason"]."','".$input["quantity"]."','".$input["returnable"]."','".$input["request_by"]."','".$input["transportCompany"]."','".$input["driverName"]."','".$input["driverMobile"]."','".$input["vehicle_no"]."','".$_GET["emp_id"]."','$entry_date')"; 

	if($conn->query($sql)===TRUE){	
		echo "{\"status\":\"success\"}";
	}
	else{
		echo "{\"status\":\"".$conn->error."\"}";
	}
}
else if($_GET["type"]=="getDepartmentEmployees"){
	$sql = "SELECT * FROM employee WHERE department='".$_GET["selectedDepartment"]."'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}
else if($_GET["type"]=="getCandidates"){
	$sql = "SELECT * FROM employee WHERE status='active'";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}
else if ($_GET["type"] == "saveRequisition") {
        $sql = "INSERT INTO requisition (item_name,stock_qty,indend_qty,entry_by,entry_date,plant_id) VALUES ('".$input["item_name"]."','".$input["stock_qty"]."','".$input["indend_qty"]."','".$_GET["emp_id"]."','$entry_date','".$_GET["plant_id"]."')";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    }else if($_GET["type"]=="getRequisition"){
	$output = Array();
	$sql = "SELECT * from requisition";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
	}
	echo json_encode($output);
}

else if($_GET["type"]=="getVisitors") {
	$sql = "SELECT name FROM gatepass";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		$output = Array();
		while($row = $result->fetch_assoc()){
			$output[] = $row;
		}
		echo json_encode($output);
	}
	else {
		echo "[]";
	}
}else if ($_GET["type"] == "downloadFirstAidLog") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'First Aid Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">First Aid Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 5%;">Date Of Accident</td>
                    <td style="width: 10%;">Department</td>
                    <td style="width: 10%;">Name Of First Aider	</td>
                    <td style="width: 10%;">Name Of Employee</td>
                    <td style="width: 10%;">Designation	</td>
                    <td style="width: 10%;">Accidenet/Incident On Job</td>
                    <td style="width: 5%;">Category</td>
                    <td style="width: 5%;">Location/Area</td>
                   <td style="width: 5%;">Details</td>
                    <td style="width: 10%;">Treatment Given</td>
                    <td style="width: 10%;">Have Person Accepted Tratment</td>
                     <td style="width: 5%;">Reason</td>
                </tr>
            </thead>';
           $i = 1;
            $sql = "SELECT f.*, e.firstname 
                    FROM fisrt_aid f 
                    LEFT JOIN employee e ON f.employee = e.emp_id 
                    WHERE f.status = 'pending' 
                    ORDER BY f.id DESC";
            
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
   
                $html.='<tr>
                    

    <td style="width: 5%; ">'.$i.'</td>
                     <td style="width: 5%; ">'.date('d-m-y',strtotime($row['incident_date'])).'</td>
                <td style="width: 10%; ">'.$row['department'].'</td>
                <td style="width: 10%; ">'.$row['aider'].'</td>
                <td style="width: 10%; ">'.$row['firstname'].'</td>
                <td style="width: 10%; ">'.$row['designation'].'</td>
                <td style="width: 10%; ">'.$row['incident'].'</td>
                <td style="width: 5%; ">'.$row['category'].'</td>
                <td style="width: 5%; ">'.$row['location'].'</td>
                <td style="width: 5%; ">'.$row['details'].'</td>
                <td style="width: 10%; ">'.$row['treatment'].'</td>
                <td style="width: 10%; ">'.$row['treatment_accept'].'</td>
                <td style="width: 5%; ">'.$row['reason'].'</td>
                </tr>';
                $i++;
            }
        }
         $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('First Aid.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadRequisition") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'First Aid Item Requisition'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">First Aid Item Requisition</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 25%;">Sr.</td>
                    <td style="width: 25%;">Name Of Item</td>
                    <td style="width: 25%;">Quantity In Stock</td>
                    <td style="width: 25%;">Indend Quantity	</td>
                
                </tr>
            </thead>';
            $i=1;
         $sql = "SELECT * FROM requisition ";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
                $html.='<tr>
                    

                <td style="width: 25%; ">'.$i.'</td>
                <td style="width: 25%; ">'.$row['item_name'].'</td>
                <td style="width: 25%; ">'.$row['stock_qty'].'</td>
                <td style="width: 25%; ">'.$row['indend_qty'].'</td>
               
                </tr>';
                $i++;
            }
        }
         $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Requistion.pdf', 'I');
        
    }
     else if ($_GET["type"] == "downloadAidConsumption") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'STOCK CARD FOR FIRST AID ITEMS'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
          $html= "";

        $html.='
                <h2 style="text-align:center">First Aid Consumption Record</h2>

        <div></div>';
               $html.=' <table border="1"cellpadding="3" >
              <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                 <th style="text-align: left;">Sr</th>
                 <th style="text-align: left;">Date</th>
                 <th style="text-align: left;">Name Of Item</th>
                 <th style="text-align: left;">Department</th>
                 <th style="text-align: left;">Available Qty</th>
                 <th style="text-align: left;">Consumed Qty</th>
                 <th style="text-align: left;">Balanced Qty</th>
                </tr>
              </thead>
              <tbody>
               </tbody>';
        	 $i=1;
	$sql = "SELECT * FROM fisrt_aid  ORDER BY id DESC";
        	$result = $conn->query($sql);
        	if($result->num_rows > 0){
        		while($row = $result->fetch_assoc()){
                        $html.='<tr>
                            
        
                        <td >'.$i.'</td>
                      <td style="text-align:center;">'.date('H:i',strtotime($row['entry_date'])).'</td>
                        <td >'.$row['product'].'</td>
                        <td >'.$row['department'].'</td>
                        <td >'.$row['bal_qty'].'</td>
                        <td >'.$row['qty'].'</td>
                         <td >'.$row['bal_qty'].'</td>
                       
                        </tr>';
                        $i++;
                    }
                }
                 $html.="</table>";

	
	
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output(' Consumption Record.pdf', 'I');
    
    }
   
    
//     else if ($_GET["type"] == "downloadAidConsumption") {
//         include '../tcpdf/tcpdf.php';
//         $_GET['filename'] = 'STOCK CARD FOR FIRST AID ITEMS'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp6.php");
//           $html= "";

//         $html.='
//         <div></div><div></div> <div></div>';
//       	$sql = "SELECT f.*, s.mfg_date,s.exp_date,s.stock_type FROM fisrt_aid f LEFT JOIN fg_stock_book s 
//       	ON f.id=s.id WHERE f.status='active' ";
// 	$result = $conn->query($sql);
// 	if($result->num_rows > 0){
// 		while($row = $result->fetch_assoc()){
		
		
	
//          $html.=' <tr>
//         <td style="width:100%"><b>NAME OF ITEM</b>:  '.$row['item_name'].' </td>
//         </tr><div></div>
//         <table cellpadding="8" border="0.1">
//         <tr style="text-align: center; background-color:#DDDAD9;">
//         <td style="width:12%;text-align:center"><b>DATE</b></td>
//          <td style="width:10%;text-align:center"><b>OPENING STOCK</b></td>
//           <td style="width:10%;text-align:center"><b>RECEIPT QTY</b></td>
//           <td style="width:12%;text-align:center"><b>MFG DATE</b></td>
//             <td style="width:12%;text-align:center"><b>EXP DATE</b></td>
//              <td style="width:17%;"><b>PARTICULAR/DISTRIBUTION </b></td>
//               <td style="width:8%;"><b>USED QTY</b></td>
//               <td style="width:10%;text-align:center"><b>CLOSING STOCK</b></td>
//                 <td style="width:9%;text-align:center"><b>SIGN</b></td>
//               </tr>
//               <tr>
      
//         <td style="width:12%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
//          <td style="width:10%;">'.$row['stock_type'].'</td>
//           <td style="width:10%;">'.$row['available_qty'].'</td>
//           <td style="width:12%;">'.$row['mfg_date'].'</td>
//             <td style="width:12%;">'.$row['exp_date'].'</td>
//              <td style="width:17%;">'.$row['department'].'</td>
//               <td style="width:8%;">'.$row['consum_qty'].'</td>
//               <td style="width:10%;">'.$row['closing_stock'].'</td>
//                 <td style="width:9%;"></td>
//         </tr>';
		
		
	
        
//          $html.=' </table><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
//       <div></div><div></div><div></div>
//         <table cellpadding="5" border="0.1">
//       <tr style="text-align: center; background-color:#DDDAD9;">
//         <td style="width:35%;"><b>Sign/Date</b></td>
//          <td style="width:30%;"><b>Sign/Date</b></td>
//           <td style="width:35%;"><b>Sign/Date</b></td>
//           </tr>
//           <tr >
//         <td style="width:35%;"></td>
//          <td style="width:30%;"></td>
//           <td style="width:35%;"></td>
//           </tr>
//          <tr style="text-align: center; background-color:#DDDAD9;">
//           <td style="width:35%;"><b>Prepared By</b></td>
//             <td style="width:30%;"><b>Checked By</b></td>
//              <td style="width:35%;"><b>Approved By</b></td>
//               </tr>
//               <tr  >
//         <td style="width:35%;"></td>
//          <td style="width:30%;"></td>
//           <td style="width:35%;"></td>
//           </tr>
//         </table>';
// 		}}
	
	
	
//         $pdf->writeHTML($html, true, false, false, false, '');
//         $pdf->Output(' Consumption Record.pdf', 'I');
    
//     }
    else if ($_GET["type"] == "downloadAidConsumption_old") {
        include '../tcpdf/tcpdf.php';
        $_GET['filename'] = 'First Aid Consumption Record'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">First Aid Consumption Record</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 10%;">Date</td>
                    <td style="width: 20%;">Name Of Item</td>
                    <td style="width: 20%;">Department	</td>
                     <td style="width: 15%;">Available Qty	</td>
                     <td style="width: 15%;">Consumed Qty	</td>  
                    <td style="width: 10%;">Balanced Qty	</td>
                
                </tr>
            </thead>';
            $i=1;
         $sql = "SELECT * FROM fisrt_aid ";
	$result = $conn->query($sql);
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
                $html.='<tr>
                    

                <td style="width: 10%; ">'.$i.'</td>
              <td style="width: 10%; text-align:center;">'.date('H:i',strtotime($row['entry_date'])).'</td>
                <td style="width: 20%; ">'.$row['item_name'].'</td>
                <td style="width: 20%; ">'.$row['department'].'</td>
                <td style="width: 15%; ">'.$row['available_qty'].'</td>
                <td style="width: 15%; ">'.$row['consum_qty'].'</td>
                 <td style="width: 10%; ">'.$row['bal_qty'].'</td>
               
                </tr>';
                $i++;
            }
        }
         $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output(' Consumption Record.pdf', 'I');
    }


}

 else {
    echo "{\"status\":\"invalid\"}";
}


$conn->close();
?>