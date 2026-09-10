<?php 


require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';



// ini_set('display_errors', 1);
// error_reporting(E_ALL);



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

 

    if ($_GET["type"] == "fgWithdrawalAndRetrivalERegister") {
   
        $_GET['filename'] = 'ENTRY REGISTER FOR FINISHED PRODUCT CONTROL SAMPLE'; $_GET['pdftype'] ='landscape';  include("../pdfimp2.php");
         
        $html.='
          <h3 style="text-align:center">ENTRY REGISTER FOR FINISHED PRODUCT CONTROL SAMPLE</h3>
          <div></div>
          
         <table cellpadding="5" border="1">
         
           <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="font-size:9px; width:40px; text-align:left;"><b>Sr.No.</b></td>
                <td style="font-size:9px; width:140px; text-align:left;"><b>Product Name</b></td>
                <td style="font-size:9px; width:65px; text-align:centre;"><b>Batch No.</b></td>
                <td style="font-size:9px; width:65px; text-align:centre;"><b>Control Sample No.</b></td>
                <td style="font-size:9px; width:65px; text-align:centre;"><b>Qty. Withdrawal</b></td>
                <td style="font-size:9px; width:90px; text-align:left;"><b>Reason For Withdrawal</b></td>
                <td style="font-size:9px; width:75px; text-align:centre;"><b>Control Sample Given By</b></td>
                <td style="font-size:9px; width:75px; text-align:centre;"><b>Control Sample Taken By</b></td>
                <td style="font-size:9px; width:75px; text-align:centre;"><b>Control Sample Returned By</b></td>
                <td style="font-size:9px; width:95px; text-align:left;"><b>Remarks</b></td>
              </tr>'; 
             
            $i=1;
            
            
        $sql = "SELECT c.*,p.product_name FROM controlSampleWithdrawal c LEFT JOIN product p ON c.material_code = p.product_code
        WHERE  c.plant_id = '".$_GET["plant_id"]."' order by c.id desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                 
                $html.='
                    <tr> 
                        <td style=" font-size:9px; text-align:centre;"><b>'.$i.'</b></td>
                        <td style=" font-size:9px; text-align:left;"><b>'.$row["product_name"].' - '.$row["material_code"].'</b></td>
                        <td style=" font-size:9px; text-align:centre;"><b>'.$row["batch_no"].'</b></td>
                        <td style=" font-size:9px; text-align:centre;"><b>'.$row["cs_id"].'</b></td>
                        <td style=" font-size:9px; text-align:centre;"><b>'.$row["withdeawalQty"].'-'.$row["unit"].'</b></td>
                        <td style=" font-size:9px; text-align:left;"><b>'.$row["reasoneForWithdrawal"].'</b></td>
                        <td style=" font-size:9px; text-align:centre;"><b>'.$row["controlSampleGivenBy"].'</b></td>
                        <td style=" font-size:9px; text-align:centre;"><b>'.$row["controlSampleTakenBy"].'</b></td>
                        <td style=" font-size:9px; text-align:centre;"><b>'.$row["controlSampleReturnBy"].'</b></td>
                        <td style=" font-size:9px; text-align:left;"><b>'.$row["remark"].'</b></td>
                      </tr>';
                $i++;
            }
        }
        
        
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('ENTRY REGISTER FOR FINISHED PRODUCT CONTROL SAMPLE.pdf', 'I');
  
    }
    
    else if ($_GET["type"] == "FgregisterOfControlSample") {
   
        $_GET['filename'] = 'CONTROL SAMPLE WITHDRAWAL AND RETRIEVAL REGISTER '; $_GET['pdftype'] ='landscape';  include("../pdfimp2.php");
         
        $html.='
          <h3 style="text-align:center">CONTROL SAMPLE WITHDRAWAL AND RETRIEVAL REGISTER</h3>
          <div></div>
          
         <table cellpadding="5" border="1">
         
           <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="font-size:9px; width:35px; text-align:left;"><b>Sr.No.</b></td>
                <td style="font-size:9px; width:110px; text-align:left;"><b>Product Name</b></td>
                <td style="font-size:9px; width:56px; text-align:centre;"><b>Batch No.</b></td>
                <td style="font-size:9px; width:56px; text-align:centre;"><b>Batch Size</b></td>
                <td style="font-size:9px; width:65px; text-align:centre;"><b>Mfg/Exp. Date</b></td>
                <td style="font-size:9px; width:56px; text-align:centre;"><b>Control Sample Qty.</b></td>
                <td style="font-size:9px; width:60px; text-align:centre;"><b>Control Sample Kept On</b></td>
                <td style="font-size:9px; width:56px; text-align:centre;"><b>Control Sample No.</b></td>
                <td style="font-size:9px; width:65px; text-align:left;"><b>Qty. Verified By/On</b></td>
                <td style="font-size:9px; width:60px; text-align:left;"><b>Due Date For Distruction</b></td>
                <td style="font-size:9px; width:65px; text-align:left;"><b>Qty. Destroyed By/Date</b></td>
                <td style="font-size:9px; width:100px; text-align:left;"><b>Remark</b></td>
             </tr>'; 
             
            $i=1;
            
            
        $sql = "SELECT c.*,p.product_name FROM control_sample c LEFT JOIN product p ON c.material_code = p.product_code
        WHERE c.material_type = 'Finish Product' AND c.plant_id = '".$_GET["plant_id"]."' order by c.id desc ";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                 
                $html.='
                    <tr> 
                        <td style="font-size:9px; text-align:centre;"><b>'.$i.'</b></td>
                        <td style="font-size:9px; text-align:left;"><b>'.$row["product_name"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["batch_no"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["batch_size"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.date("m-Y", strtotime($row["mfg_date"])).' / '.date("m-Y", strtotime($row["exp_date"])).'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["sample_quantity"].'-'.$row["unit"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.date("d-m-Y", strtotime($row["sampling_date"])).'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["cs_id"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["approve_by"].' / '.date("d-m-Y", strtotime($row["approve_date"])).'</b></td>
                        <td style="font-size:9px; text-align:left;"><b>'.date("d-m-Y", strtotime($row["dueDateForDistruction"])).'</b></td>
                        <td style="font-size:9px; text-align:left;"><b>'.$row["qtyDistroy"].'-'.$row["unit"].' / '.$row["distroyBy"].' - '.date("d-m-Y", strtotime($row["distroyDate"])).'</b></td>
                        <td style="font-size:9px; text-align:left;"><b>'.$row["distroyRemark"].'</b></td>
                     </tr>';
                $i++;
            }
        }
        
        
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CONTROL SAMPLE WITHDRAWAL AND RETRIEVAL REGISTER .pdf', 'I');
  
    }
    
    
    
    
    else if ($_GET["type"] == "fgControlSampleForVerification") {
   
        $_GET['filename'] = 'FINISHED PRODUCT CONTROL SAMPLE VERIFICATION '; $_GET['pdftype'] ='landscape';  include("../pdfimp2.php");
         
        $html.='
          <h3 style="text-align:center">FINISHED PRODUCT CONTROL SAMPLE VERIFICATION </h3>
          <div></div>
  
         <table cellpadding="5" border="1">
                 
           <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="font-size:9px; width:35px; text-align:left;"><b>Sr.No.</b></td>
                <td style="font-size:9px; width:70px; text-align:left;"><b>Product Name</b></td>
                <td style="font-size:9px; width:36px; text-align:centre;"><b>Batch No.</b></td>
                <td style="font-size:9px; width:46px; text-align:centre;"><b>Mfg/Exp. Date</b></td>
                <td style="font-size:9px; width:55px; text-align:centre;"><b>Date of Charging</b></td>
                <td style="font-size:9px; width:36px; text-align:centre;"><b>Control Sample No.</b></td>
                <td style="font-size:9px; width:50px; text-align:centre;"><b>Initial Appearance and odor Sign/Date</b></td>
                <td style="font-size:9px; width:56px; text-align:centre;"><b>12M Appearance & odor OK/Not OK Sign/Date</b></td>
                <td style="font-size:9px; width:65px; text-align:left;"><b>24M Appearance & odor OK/Not OK Sign/Date</b></td>
                <td style="font-size:9px; width:60px; text-align:left;"><b>36M Appearance & odor OK/Not OK Sign/Date</b></td>
                <td style="font-size:9px; width:65px; text-align:left;"><b>48M Appearance & odor OK/Not OK Sign/Date</b></td>
                <td style="font-size:9px; width:55px; text-align:left;"><b>60M Appearance & odor OK/Not OK Sign/Date</b></td>
                <td style="font-size:9px; width:55px; text-align:left;"><b>Quantity Destroyed by/Date</b></td>
                <td style="font-size:9px; width:100px; text-align:left;"><b>Remark</b></td>
             </tr>'; 
             
            $i=1;
            
            
        $sql = "SELECT c.*,p.product_name FROM control_sample c LEFT JOIN product p ON c.material_code = p.product_code
        WHERE c.material_type = 'Finish Product' AND c.plant_id = '".$_GET["plant_id"]."' order by c.id desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                 
                $html.='
                    <tr> 
                        <td style="font-size:9px; text-align:centre;"><b>'.$i.'</b></td>
                        <td style="font-size:9px; text-align:left;"><b>'.$row["product_name"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["batch_no"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.date("d-m-Y", strtotime($row["mfg_date"])).' / '.date("d-m-Y", strtotime($row["exp_date"])).'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.date("d-m-Y", strtotime($row["dateOfCharging"])).'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["cs_id"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["initialAppearance"].'/ '.$row["initialVerificationBy"]. ' / '.date("d-m-Y", strtotime($row["initialVerificationOn"])).'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["12MonAppearance"].'  / '.$row["12MonOder"]. ' / '.$row["12MonVerificationBy"]. ' / ' .date("d-m-Y", strtotime($row["12MonVerificationOn"])).'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["24MonAppearance"].'  / '.$row["24MonOder"]. '/ '.$row["24MonVerificationBy"]. ' / ' .date("d-m-Y", strtotime($row["24MonVerificationOn"])).'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["36MonAppearance"].'  / '.$row["36MonOder"]. '/ '.$row["36MonVerificationBy"]. ' / ' .date("d-m-Y", strtotime($row["36MonVerificationOn"])).'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["48MonAppearance"].'  / '.$row["48MonOder"]. '/ '.$row["48MonVerificationBy"]. ' / ' .date("d-m-Y", strtotime($row["48MonVerificationOn"])).'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["60MonAppearance"].'  / '.$row["60MonOder"]. '/ '.$row["60MonVerificationBy"]. ' / ' .date("d-m-Y", strtotime($row["60MonVerificationOn"])).'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["distroyBy"]. ' / ' .date("d-m-Y", strtotime($row["distroyDate"])).'</b></td>
                        <td style="font-size:9px; text-align:left;"><b>'.$row["distroyRemark"].'</b></td>
                     </tr>';
                $i++;
            }
        }
        
        
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CONTROL SAMPLE WITHDRAWAL AND RETRIEVAL REGISTER .pdf', 'I');
  
    }
    
    
    
    else if ($_GET["type"] == "fgControlSampleForDistruction") {
   
        $_GET['filename'] = 'FINISHED PRODUCT CONTROL SAMPLE DUE FOR DESTRUCTION '; $_GET['pdftype'] ='landscape';  include("../pdfimp2.php");
         
        $html.='
          <h3 style="text-align:center">FINISHED PRODUCT CONTROL SAMPLE DUE FOR DESTRUCTION</h3>
          <div></div>
          
         <table cellpadding="5" border="1">
         
           <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                <td style="font-size:9px; width:35px; text-align:left;"><b>Sr.No.</b></td>
                <td style="font-size:9px; width:200px; text-align:center;"><b>Product Name</b></td>
                <td style="font-size:9px; width:156px; text-align:centre;"><b>Control Sample No.</b></td>
                <td style="font-size:9px; width:96px; text-align:centre;"><b>Quantity</b></td>
                <td style="font-size:9px; width:150px; text-align:centre;"><b>Due Date of Destruction</b></td>
                <td style="font-size:9px; width:150px; text-align:centre;"><b>Action</b></td>
                
             </tr>'; 
             
            $i=1;
            
            
        $sql = "SELECT c.*,p.product_name FROM control_sample c LEFT JOIN product p ON c.material_code = p.product_code
        WHERE c.material_type = 'Finish Product' AND c.plant_id = '".$_GET["plant_id"]."' order by c.id desc";
        
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            while ($row = $result->fetch_assoc()) {
                 
                $html.='
                    <tr> 
                        <td style="font-size:9px; text-align:centre;"><b>'.$i.'</b></td>
                        <td style="font-size:9px; text-align:center;"><b>'.$row["product_name"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["cs_id"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.$row["sample_quantity"].'</b></td>
                        <td style="font-size:9px; text-align:centre;"><b>'.date("d-m-Y", strtotime($row["dueDateForDistruction"])).'</b></td>
                        <td style="font-size:9px; text-align:center;"><b>'.$row["status"].'</b></td>
                     </tr>';
                $i++;
            }
        }
        
        
        $html.="</table>";
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('CONTROL SAMPLE WITHDRAWAL AND RETRIEVAL REGISTER .pdf', 'I');
  
    }



 







}


?>