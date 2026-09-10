<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    
//         ini_set('display_errors', 1);
// error_reporting(E_ALL);
    
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    
 
    
    
    
    

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
      $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
      $string = explode("$",$string);
      $_GET["emp_id"] = $string[0];
      $_GET["department"] = $string[1];
      break;
    }

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "getAwaitingReceivingRawLabels") {
        $output = Array();
         $sql = "SELECT l.*,v.vendor_name,c.vendor_no,c.receiving_no,c.receiving_date,c1.po_no,m.material_name,m.grade FROM sampling_batches l 
         LEFT JOIN material m ON l.material_code = m.material_code 
         LEFT JOIN challan_materials c ON l.challan_no = c.challan_no  AND l.material_code = c.material_code 
         LEFT JOIN challan c1 ON l.challan_no = c1.challan_no 
         LEFT JOIN vendor v ON c.vendor_no = v.vendor_no order by l.id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 

    else if ($_GET["type"] == "getReceivingLabels") {
        $output = array();
        $sql = "SELECT l.*, m.material_name, m.grade FROM label l LEFT JOIN material m ON l.material_code=m.material_code ";
       // WHERE l.label_type='Quarantine'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getAwaitingGRNRawLabels") {
      
        $output = Array();
 
        $sql = "SELECT s.id, s.plant_id, s.trackingId, s.material_code, s.batch_no, s.ar_no, s.grn_no, s.status, s.mfg_date, s.exp_date, s.mfg_by, s.pack_size, s.total_containers, s.qty_received, 
        s.container_no, s.challan_no, s.ch_no,m.material_name,m.material_type,s.unit FROM `sampling_batches` s 
        LEFT JOIN material m ON s.material_code = m.material_code  
        left join  challan_materials c ON c.grn_no = s.grn_no AND s.material_code = c.material_code 
        WHERE s.plant_id= '".$_GET["plant_id"]."'  AND c.grn = 'Approved' AND m.material_type= '".$_GET["material_type"]."' ORDER BY s.id DESC";      
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
         
    }
    else if ($_GET["type"] == "getBatchesForBarcodePrintingFOrWMS") {
      
        $output = Array();
 
        $sql = "SELECT s.*, m.material_name,m.material_type,s.unit FROM `sampling_batches` s 
        LEFT JOIN my_view m ON s.material_code = m.material_code  
        left join  challan_materials c ON c.grn_no = s.grn_no AND s.material_code = c.material_code 
        WHERE s.plant_id= '".$_GET["plant_id"]."'   AND m.material_type= '".$_GET["material_type"]."' ORDER BY s.id DESC";      
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $output[] = $row;
            }
        }
        echo json_encode($output);
         
    }
    	else if ($_GET["type"] == "grnLabelsPDF2") {
    if($_GET["plant_id"] == 59) { //amerdeep
        $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
         $html= "";
           $output = Array();
      
        //   $sql = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name, 
        // DATE(c.receiving_date) as receiving_date FROM challan_materials c LEFT JOIN material m 
        // ON c.material_code=m.material_code  WHERE 
        // m.material_type='Raw Material   ";
   
           $sql = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name,DATE(c.receiving_date) as receiving_date   
          FROM challan_materials c LEFT JOIN material m ON c.material_code = m.material_code   WHERE m.material_type = 'Raw Material' 
          AND c.challan_no = '".$_GET["challan_no"]."'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $fullRangeCalibrationJson = $row['grn_details'];
$fullRangeCalibration = json_decode($fullRangeCalibrationJson, true);
          
    		       $html='  

 <table border="1">
  
 <tr>
    <td style="line-height:20px;width: 780px;text-align:center;">Warehouse</td>
</tr>
<tr>
    <td style="line-height:20px;width: 780px;text-align:center;"> RAW MATERIAL AND PACKING MATERIAL INWARD REGISTER</td>
</tr>
<tr>
     <td style="line-height:20px;width: 390px;border-bottom:none;text-align:left;"> Format No.: </td>
     <td style="line-height:20px;width: 390px;text-align:left;"> Change Control No.: </td>
 </tr>
 <tr>
     <td style="line-height:20px;width: 390px;border-bottom:none;text-align:left;"> Effective Date:</td>
     <td style="line-height:20px;width: 390px;text-align:left;"> Review Date:</td>
 </tr>
 <tr>
    <td style="line-height:20px;width: 780px;text-align:left;"> Reference SOP No.: </td>
</tr>
</table>
<div>
</div>
<table border="1">
<tr>
     <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> GIR No.</td>
     <td style="line-height:20px;width: 43px;text-align:center;"rowspan="2";> Date</td>
     <td style="line-height:20px;width: 78px;text-align:center;"rowspan="2";> Name of Party</td>
     <td style="line-height:20px;width: 63px;text-align:left;"colspan="2";>Payment Slip No/Invoice No.</td>
     <td style="line-height:20px;width: 58px;text-align:center;"rowspan="2";> Po No & Date</td>
     <td style="line-height:20px;width: 58px;text-align:center;"rowspan="2";> GRN No.</td>
     <td style="line-height:20px;width: 78px;text-align:center;"rowspan="2";> Description</td>
     <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> Pack</td>
     <td style="line-height:20px;width: 53px;text-align:center;"rowspan="2";> Quantity</td>
     <td style="line-height:20px;width: 48px;text-align:center;"rowspan="2";> Rate</td>
     <td style="line-height:20px;width: 48px;text-align:center;"rowspan="2";> GST</td>
     <td style="line-height:20px;width: 93px;text-align:center;"colspan="3";> Mode of Transport</td>
     <td style="line-height:20px;width: 54px;text-align:center;"rowspan="2";> remarks</td>
</tr>
<tr>
<td style="line-height:20px;width: 31px;text-align:center;"> No.</td>
<td style="line-height:20px;width: 32px;text-align:center;"> Date</td>
<td style="line-height:20px;width: 40px;text-align:center;"> Name</td>
<td style="line-height:20px;width: 25px;text-align:center;"> LR No.</td>
<td style="line-height:20px;width: 28px;text-align:center;"> Vehicle No.</td>
</tr>
<tr>
     <td style="line-height:20px;width: 53px;text-align:center;">  '.$row[" "].'</td>
     <td style="line-height:20px;width: 43px;text-align:center;"> '.$row[" "].' </td>
     <td style="line-height:20px;width: 78px;text-align:center;">  '.$row[" "].'</td>
     <td style="line-height:20px;width: 31px;text-align:left;"> '.$row["challan_no"].'</td>
     <td style="line-height:20px;width: 32px;text-align:left;">'.$row[" "].' </td>
     <td style="line-height:20px;width: 58px;text-align:center;">'.$row[" "].' </td>
     <td style="line-height:20px;width: 58px;text-align:center;"> '.$row["grn_no"].'</td>
     <td style="line-height:20px;width: 78px;text-align:center;"> '.$row[" "].' </td>
     <td style="line-height:20px;width: 53px;text-align:center;"> '.$row["pack_size"].'</td>
     <td style="line-height:20px;width: 53px;text-align:center;"> '.$row["qty"].'</td>
     <td style="line-height:20px;width: 48px;text-align:center;"> '.$row["rate"].'</td>
     <td style="line-height:20px;width: 48px;text-align:center;"> '.$row["gst"].'</td>
     <td style="line-height:20px;width: 40px;text-align:center;">'.$row[" "].' </td>
     <td style="line-height:20px;width: 25px;text-align:center;"> '.$row[" "].'</td>
     <td style="line-height:20px;width: 28px;text-align:center;"> '.$row[" "].'</td>
     <td style="line-height:20px;width: 54px;text-align:center;"> '.$fullRangeCalibration['remark'].' </td>
</tr>
 

</table>
<div></div>   
<div></div>   
<div></div>   

<tr>
    <td style="line-height:20px;width: 390px;text-align:left;"> Checked By-</td>
    <td style="line-height:20px;width: 390px;text-align:center;"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Verified By-</td>
</tr>
<tr>
    <td style="line-height:20px;width: 390px;text-align:left;"> (Sign/Date)</td>
    <td style="line-height:20px;width: 390px;text-align:center;"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Sign/Date)</td>
</tr>


<div></div>   

  

<table border="1">
<tr>
    <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;">Sign/Date</td>
    <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;">Sign/Date </td>
    <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;">Sign/Date </td>
</tr>
<tr>
    <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;"> Prepared By Warehouse</td>
    <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;"> Reviewed By Warehouse</td>
    <td style="line-height:30px;width: 260px;border-bottom:none;text-align:center;"> Approved By QA</td>
</tr>
</table>
';
}
}
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
    	}
    
    
    
    
    
    
  else if ($_GET["type"] == "printReceivingLabel") {
      
        $_GET['filename'] = 'printReceivingLabel'; $_GET['pdftype'] = 'noheader'; include("../pdfimp2.php");
        
         $sql = "SELECT l.*,v.vendor_name,c.vendor_no,c.receiving_no,c.receiving_date,c1.po_no,m.material_name,m.grade,m.storage_condition,p.plant_full_name,p.plant_full_address,p.logo_path FROM sampling_batches l 
         LEFT JOIN material m ON l.material_code = m.material_code 
         LEFT JOIN challan_materials c ON l.challan_no = c.challan_no 
         LEFT JOIN challan c1 ON l.challan_no = c1.challan_no 
         LEFT JOIN vendor v ON c.vendor_no = v.vendor_no
         LEFT JOIN plant p on m.plant_id = p.plant_id WHERE  l.id='".$_GET['id']."' order by l.id desc";
        
 
         
          $j =1;
          $result = $conn->query($sql);
        if ($result->num_rows > 0) { 
            while ($row = $result->fetch_assoc()) {
                          $count = $row['total_containers']; 
                    
          for ($i=1; $i <= $count; $i++) {
                    
        $html.='<h2 style="text-align:center">Receiving Label</h2> &nbsp;<br>
 
                 <table border="1">
                 
                     <tr>
                           <td style="font-size:10px;text-align:center;width: 108px;" rowspan="3"><img src="../logos/'.$row['logo_path'].'" style="width: 80px; height: 48px;" ></td>
                            <td style="font-size:10px;text-align:center;width: 432px;"><h3>Material Status Label</h3></td>
                     </tr>
                     <tr>
                            <td style="font-size:10px;text-align:center;width: 432px;"><h3>'.$row['plant_full_name'].'</h3></td>
                     </tr>
                     <tr>
                            <td style="font-size:10px;text-align:center;line-height:15px;width: 432px;"> <b> '.$row['plant_full_address'].'</b></td>
                     </tr>
                      <tr>
                          <td style="line-height:20px;width: 108px;text-align:center;" rowspan="9"><br><br><br><br><b>Received</b></td>
                           <td style="line-height:20px;width: 216px;">
                           <b> Material Name: '.$row["material_name"].'</b> </td>
                            <td style="line-height:20px;width: 216px;">
                           <b> Storage Condition: '.$row["storage_condition"].'</b></td>
                     </tr>
                      <tr>  
                           <td style="line-height:20px;width: 216px;">
                           <b> M.Code: '.$row["material_code"].'</b></td>
                           <td style="line-height:20px;width: 216px;">
                           <b> Grade: '.$row["grade"].'</b></td>
                     </tr>
                      <tr>  
                           <td style="line-height:20px;width: 216px;">
                           <b> Mfg.Dt: '.$row["mfg_date"].'</b></td>
                           <td style="line-height:20px;width: 216px;">
                           <b> Exp.Dt: '.$row["exp_date"].'</b></td>
                     </tr>
                      <tr>  
                           <td style="line-height:20px;width: 216px;">
                           <b> Vendor: '.$row["vendor_name"].'</b></td>
                           <td style="line-height:20px;width: 216px;">
                           <b> Mfg.By: '.$row["vendor_name"].'</b></td>
                     </tr>
                      <tr>  
                           
                           <td style="line-height:20px;width: 216px;">
                           <b> No.of.container:   '.$j++.' /  '.$count.'</b></td>
                           <td style="line-height:20px;width: 216px;">
                           <b> Received Qty: '.$row["qty_received"].' '.$row["unit"].'  </b></td>
                     </tr>
                      <tr>  
                           <td style="line-height:20px;width: 216px;">
                           <b> Received Dt: '.$row["receiving_date"].'</b></td>
                           <td style="line-height:20px;width: 216px;">
                           <b> Pack Size: '.$row["pack_size"].'</b></td>
                           
                     </tr>
                     <tr>  
                           <td style="line-height:20px;width: 216px;">
                           <b> Received By: '.$row["received_by"].'</b></td>
                           <td style="line-height:20px;width: 216px;">
                           <b> Checked By: '.$row["received_by"].'</b></td>
                     </tr>
                     <tr>
                     <td style="width: 216px;"><b>   SOP No:</b></td>
                     <td style="width: 216px;"><b>   Format No:</b></td>
                     </tr>
                
                 </table>
                 
                ';   
         } }    
         }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Labels.pdf', 'I');
    }
    
    
    
    else if ($_GET["type"] == "downloadLabels") {
        $_GET['filename'] = 'Labels'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Labels</h2>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%;"><b>Sr.</b></td>
                <td style="width:10%;"><b>Date</b></td>
                <td style="width:20%;"><b>Material Type</b></td>
                <td style="width:20%;"><b>Material Name	</b></td>
                <td style="width:10%;"><b>Batch No.</b></td>
                <td style="width:10%;"><b>No.of Labels.</b></td>
                <td style="width:10%;"><b>Reason.</b></td>
                 <td style="width:10%;"><b>Print By</b></td>
            </tr>';
              $i=1;
              $sql = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name, c1.inward_no, DATE(c.receiving_date) as receiving_date FROM challan_materials c LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN challan c1 ON c.inward_no=c1.inward_no WHERE m.material_type='Raw Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
             $html.='<tr nobr="true">
                <td style="width:10%;">'.$i.'</td>
                <td style="width:10%;">'.date('d-m-Y',strtotime($row['receiving_date'])).'</td>
                <td style="width:20%;">'.$row['material_type'].'</td>
                <td style="width:20%;">'.$row['material_name'].'</td>
                <td style="width:10%;">'.$row['batch_no'].'</td>
                <td style="width:10%;">'.$row['total_containers'].'</td>
                <td style="width:10%;">'.$row['reason'].'</td>
                <td style="width:10%;">'.$row['print_by'].'</td>
            </tr>';
            $i++;
            }
        }
        $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Labels.pdf', 'I');
        }
}

$conn->close();
?>