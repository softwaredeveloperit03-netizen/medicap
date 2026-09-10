<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
     $currentUrl =$_GET["description"];



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
    
     $sqlplant = "SELECT * FROM plant WHERE plant_id='".$_GET["plant_id"].  "'";
     $resultplant = $conn->query($sqlplant);
     $rowplant = $resultplant->fetch_assoc();
      $plant_full_name =  $rowplant['plant_full_name'];
      $plant_full_address =  $rowplant['plant_full_address'];
      $status = $rowplant['	status'];
      $logo =  'https://'.$_SERVER['SERVER_NAME'].'public_html/php/gmptotal/logos'.$rowplant['logo_path'];
    
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
   
    
    if ($_GET["type"] == "generate_maintenance_report") {
    
            if($_GET["plant_id"] == 26){ // Amardeep
                
                 $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name,v.address,m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.id='".$_GET['id']."' ";
                        $result = $conn->query($sql);
                      $row = $result->fetch_assoc();
    

        $_GET['filename'] = 'Expenses Log'; $_GET['pdftype'] = 'noheader'; include("../pdfimp2.php");
        $html= "";
      
        $html.=' <h3 style="text-align:center;">Good Receipt Note</h3>
                <table cellpadding="3" style="text-align:left;">
                    <tr>
                        <td style="width:21%">Material Name:	</td>
                        <td style="width:79%">'.$row['material_name'].'</td>
                        
                    </tr>
                    <tr>
                        <td style="width:21%">Vendor Name:</td>
                       <td style="width:79%">'.$row["vendor_name"].'</td>
                        
                    </tr>
                    <tr>
                        <td style="width:21%">Vendor Location:</td>
                        <td style="width:32%">'.$row['address'].'</td>
                        <td style="width:15%">Manufacturer:</td>
                        <td style="width:32%">'.$row['manufacturer'].'</td>
                    </tr>
                    <tr>
                        <td>Challan No.:</td>
                        <td>'.$row['challan_no'].'</td>
                        <td>Challan Date:</td>
                        <td>'.date('d/m/Y',strtotime($row['challan_date'])).'</td>
                    </tr>
                    <tr>
                        <td>PO No.:</td>
                        <td>'.$row['po_no'].'</td>
                        <td>PO Date:</td>
                        <td>'.date('d/m/Y',strtotime($row['po_date'])).'</td>
                    </tr>
                    <tr>
                        <td>Material Type:	</td>
                        <td>'.$row['material_type'].'</td>
                        <td>Material Subtype:</td>
                        <td>'.$row['material_subtype'].'</td>
                    </tr>
                    <tr>
                        <td>Material Code:</td>
                        <td>'.$row['material_code'].'</td>
                        <td>Material Grade:</td>
                        <td>'.$row['grade'].'</td>
                    </tr>
                    <tr>
                    <td style="width:21%">PO Qty:</td>
                     <td style="width:79%">'.$row['qty'].' kg</td>
                    </tr>
                </table>
                <div></div>
                 <h3 style="text-align:center;">Labeling Details:</h3>
                <table cellpadding="4" style="text-align:center;">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td style="width:10%">Sr</td>
                         <td style="width:10%">Medicap lot no</td>
                        <td style="width:10%;">Batch No	</td>
                        <td style="width:10%;">Qty	</td>
                        <td style="width:20%">No Of Containers	</td>
                        <td style="width:20%">Mfg Date</td>
                        <td style="width:20%">Exp Date</td>
                        
                    </tr>';
                
    $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('QuotationLog.pdf', 'I');            
            
            }else if($_GET["plant_id"] == 27){//Novo
                 
                      
    $sql = "SELECT * FROM challan WHERE id='".$_GET["challan_id"]."' limit 1";
   
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    

        $_GET['filename'] = 'Purchase Order'; $_GET['pdftype'] = 'noheader'; include("../pdfimp2.php");
      
        $html.='
       <table border="1">
 <tr>
 <td style="width: 100px;"rowspan="2"> logo</td>
 <td style="width: 270px;text-align:center;" rowspan="2">'.$rowplant['plant_full_name'].'</td>
 <td style="width: 90px;"> Issued By/On:</td>
 <td style="width: 80px;"> </td>
 </tr>
 <tr>

 <td style="width: 90px;"> No. of Copies:</td>
 <td style="width: 80px;"></td>
 </tr>

 <tr>
 <td style="width: 100px;"> Format Title :</td>
 <td style="width: 440px;"></td>
 </tr>

 <tr>
 <td style="width: 100px;"> Format No.:</td>
 <td style="width: 170px;"></td>
 <td style="width: 100px;"> Page No.:</td>
 <td style="width: 170px;"></td>

 </tr>
 <tr>
 <td style="width: 100px;"> Ref. SOP No.:</td>
 <td style="width: 440px;"></td>
 </tr>
</table><div></div>

<table >

<tr>
    <td style="width: 270px;">AHU ID No.:</td>
</tr>

</table>
<div></div>
<table border="1">

<tr>
    <td style="width: 40px;text-align:center;">Sr.No.</td>
    <td style="width: 300px;text-align:center;">Check Point</td>
    <td style="width: 100px;text-align:center;">Activity</td>
    <td style="width: 100px;text-align:center;">Done by</td>
 </tr>
<tr>
    <td style="width: 40px;text-align:center;">1</td>
    <td style="width: 300px;"> Check power supply of AHU</td>
    <td style="width: 100px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"></td>
 </tr>
<tr>
    <td style="width: 40px;text-align:center;">2</td>
    <td style="width: 300px; "> Check all nut bolts of filter housing available</td>
    <td style="width: 100px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"></td>
 </tr>
<tr>
    <td style="width: 40px;text-align:center;">3</td>
    <td style="width: 300px; "> Remove filter from AHU</td>
    <td style="width: 100px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"></td>
 </tr>
<tr>
    <td style="width: 40px;text-align:center;">4</td>
    <td style="width: 300px; "> After washing with water check for trace of any particles</td>
    <td style="width: 100px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"></td>
 </tr>
<tr>
    <td style="width: 40px;text-align:center;">5</td>
    <td style="width: 300px; "> Dry filter with air</td>
    <td style="width: 100px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"></td>
 </tr>
<tr>
    <td style="width: 40px;text-align:center;">6</td>
    <td style="width: 300px; "> AHU filter cabinet Clean with Cloth</td>
    <td style="width: 100px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"></td>
 </tr>
<tr>
    <td style="width: 40px;text-align:center;">7</td>
    <td style="width: 300px; "> Before installing checked Filter Dried properly

    </td>
    <td style="width: 100px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"></td>
 </tr>
<tr>
    <td style="width: 40px;text-align:center;">8</td>
    <td style="width: 300px;"> Check all filters housing nut bolts tighten</td>
    <td style="width: 100px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"></td>
 </tr>
<tr>
    <td style="width: 40px;text-align:center;">9</td>
    <td style="width: 300px; "> Closed all AHU doors properly</td>
    <td style="width: 100px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"></td>
 </tr>
<tr>
    <td style="width: 40px; text-align:center;">10</td>
    <td style="width: 300px;text-align:center;"> Start AHU & checked differential pressure across filter & record it in AHU logbook</td>
    <td style="width: 100px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"></td>
 </tr>


</table>
<div></div>

<table >

<tr>
    <td style="width: 540px;">checked by (Sign/date) :</td>
</tr>

</table>
<div></div>



<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY</td>
    <td style="text-align:center;width: 146.6px;">REVIEWED BY</td>
    <td style="text-align:center;width: 146.6px;">APPROVED BY</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Sign/Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Designation</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Department</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
</table> ';
                
    $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('QuotationLog.pdf', 'I');            
            }
        
    }else {
    echo "{\"status\":\"invalid\"}";
    }

}
else {
    echo "{\"status\":\"invalid token\"}";
}
$conn->close();
?>