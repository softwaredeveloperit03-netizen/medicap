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
      $plant_name =  $rowplant['plant_name'];
      $status = $rowplant['    status'];
      $logo =  'https://'.$_SERVER['SERVER_NAME'].'public_html/php/gmptotal/logos'.$rowplant['logo_path'];
 $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "generate_maintenance_report") {
    
            if($_GET["plant_id"] == 26){ // Amardeep
                
                                   
    $sql = "SELECT * FROM challan WHERE id='".$_GET["challan_id"]."' limit 1";
   
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    

        $_GET['filename'] = 'Purchase Order'; $_GET['pdftype'] = 'noheader'; include("../pdfimp2.php");
      
        $html.='
        <table border="1">
 <tr>
 <td style="width: 100px;"rowspan="2"> logo</td>
 <td style="width: 270px;" rowspan="2"></td>
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

<table border="1">
<tr>
    <td style="width: 100px;text-align:center;">Sr. No. of Intimation</td>
    <td style="width: 170px;"></td>
    <td style="width: 100px;text-align:center;">Date/Time</td>
    <td style="width: 170px;"></td>
</tr>
<tr>
    <td style="width: 100px;text-align:center;">Department Name</td>
    <td style="width: 170px;"></td>
    <td style="width: 100px;text-align:center;">Name of Area/Utility/Equipment</td>
    <td style="width: 170px;"></td>
</tr>
<tr>
    <td style="width: 100px;text-align:center;">Initiated by Name</td>
    <td style="width: 170px;"></td>
    <td style="width: 100px;text-align:center;">Sign/Date</td>
    <td style="width: 170px;"></td>
</tr>

</table>
<table style="  border: 1px solid black;
border-collapse: collapse;
}">
<tr>
<td style="width: 540px;height:50px">Nature of Intimation :(If require, attach Annexure)	</td>
</tr>
<tr>
<td style="width: 540px;height:20px;text-align:right;">Department Head Sign/Date</td>
</tr>
</table>

<table border="1">
<tr>
    <td style="width: 170px;text-align:center;"rowspan="2"> Intimation Received by
    Sign/Date (Engg. Dept.)
    </td>
    <td style="width: 100px;"rowspan="2"></td>
    <td style="width: 100px;text-align:center;">Work Allotted to</td>
    <td style="width: 170px;"></td>
</tr>
<tr>

    <td style="width: 100px;text-align:center;">Sign/Date</td>
    <td style="width: 170px;"></td>
</tr>
<tr>
    <td style="width: 170px;">Internal Work / Outside Work
    (If any outside, Name of the Party)
    </td>
    <td style="width: 370px;"></td>
</tr>
<tr>
    <td style="width: 170px;">Part Replaced (if any)</td>
    <td style="width: 370px;"></td>
</tr>
<tr>
<td style="width: 100px;">Job Completed on</td>
    <td style="width: 170px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"rowspan="2">Maintenance HOD Sign/Date</td>
    <td style="width: 170px;"rowspan="2"></td>
</tr>
<tr>

    <td style="width: 100px;text-align:center;">Job Completed by Sign/Date </td>
    <td style="width: 170px;"></td>
</tr>
</table>
<table style="  border: 1px solid black;
border-collapse: collapse ; }">
<tr>
<td style="width: 540px;height:50px">Comment by after Completion of Work:</td>
</tr>
<tr>
<td style="width: 540px;height:20px;text-align:right;">Concern Department Head 
(Sign/Date)
</td>
</tr>
</table>
<table style="  border: 1px solid black;
border-collapse: collapse;}">
<tr>
<td style="width: 540px;height:50px">Final Comment by Maintenance Dept.:</td>
</tr>
<tr>
<td style="width: 540px;height:20px;text-align:right;">Maintenance Department Head
(Sign/Date)
</td>
</tr>
</table><div></div>



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
            }else if($_GET["plant_id"] == 27){//Novo
                 
                      
    // $sql = "SELECT * FROM challan WHERE id='".$_GET["challan_id"]."' limit 1";
   
    // $result = $conn->query($sql);
    // $row = $result->fetch_assoc();
    

        $_GET['filename'] = 'Purchase Order'; $_GET['pdftype'] = 'noheader'; include("../pdfimp2.php");
      
        $html.='
        <table border="1">
 <tr>
 <td style="width: 100px;"rowspan="2"> logo</td>
 <td style="width: 270px;" rowspan="2">' .$rowplant['plant_full_address'].'</td>
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

<table border="1">
<tr>
    <td style="width: 100px;text-align:center;">Sr. No. of Intimation</td>
    <td style="width: 170px;"></td>
    <td style="width: 100px;text-align:center;">Date/Time</td>
    <td style="width: 170px;"></td>
</tr>
<tr>
    <td style="width: 100px;text-align:center;">Department Name</td>
    <td style="width: 170px;"></td>
    <td style="width: 100px;text-align:center;">Name of Area/Utility/Equipment</td>
    <td style="width: 170px;"></td>
</tr>
<tr>
    <td style="width: 100px;text-align:center;">Initiated by Name</td>
    <td style="width: 170px;"></td>
    <td style="width: 100px;text-align:center;">Sign/Date</td>
    <td style="width: 170px;"></td>
</tr>

</table>
<table style="  border: 1px solid black;
border-collapse: collapse;
}">
<tr>
<td style="width: 540px;height:50px">Nature of Intimation :(If require, attach Annexure)	</td>
</tr>
<tr>
<td style="width: 540px;height:20px;text-align:right;">Department Head Sign/Date</td>
</tr>
</table>

<table border="1">
<tr>
    <td style="width: 170px;text-align:center;"rowspan="2"> Intimation Received by
    Sign/Date (Engg. Dept.)
    </td>
    <td style="width: 100px;"rowspan="2"></td>
    <td style="width: 100px;text-align:center;">Work Allotted to</td>
    <td style="width: 170px;"></td>
</tr>
<tr>

    <td style="width: 100px;text-align:center;">Sign/Date</td>
    <td style="width: 170px;"></td>
</tr>
<tr>
    <td style="width: 170px;">Internal Work / Outside Work
    (If any outside, Name of the Party)
    </td>
    <td style="width: 370px;"></td>
</tr>
<tr>
    <td style="width: 170px;">Part Replaced (if any)</td>
    <td style="width: 370px;"></td>
</tr>
<tr>
<td style="width: 100px;">Job Completed on</td>
    <td style="width: 170px;text-align:center;"></td>
    <td style="width: 100px;text-align:center;"rowspan="2">Maintenance HOD Sign/Date</td>
    <td style="width: 170px;"rowspan="2"></td>
</tr>
<tr>

    <td style="width: 100px;text-align:center;">Job Completed by Sign/Date </td>
    <td style="width: 170px;"></td>
</tr>
</table>
<table style="  border: 1px solid black;
border-collapse: collapse ; }">
<tr>
<td style="width: 540px;height:50px">Comment by after Completion of Work:</td>
</tr>
<tr>
<td style="width: 540px;height:20px;text-align:right;">Concern Department Head 
(Sign/Date)
</td>
</tr>
</table>
<table style="  border: 1px solid black;
border-collapse: collapse;}">
<tr>
<td style="width: 540px;height:50px">Final Comment by Maintenance Dept.:</td>
</tr>
<tr>
<td style="width: 540px;height:20px;text-align:right;">Maintenance Department Head
(Sign/Date)
</td>
</tr>
</table><div></div>



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