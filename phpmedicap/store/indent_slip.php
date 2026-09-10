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
    

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
}

     if($_GET['type'] == 'indent_slip')
     require '../../tcpdf/tcpdf.php';
     {
        
     if($_GET["plant_id"] == 59){ // Amardeep
                
           $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='';
              
           
    	    
    	    $html.='';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
            else if($_GET["plant_id"] == 64){//Novo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
                
				
        $html.=' 
         
 <table border="1">
 <tr>
 <td style="width: 100px;"rowspan="2"> logo</td>
 <td style="width: 270px;" rowspan="2"> Comp. Name</td>
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



   
    <tr>
    <td style="width: 540px;text-align:center;line-height:15px;font-size:20px;">
   <b> NOVO EXCIPIENTS PVT. LTD.</b>
    </td>
    </tr>
    <tr>
    <td style="width: 540px;text-align:center;line-height:25px;font-size:15px;">
    <b>INDENT SLIP</b>
    </td>
    </tr>
    <table  >
    <tr>
    <td style="width: 270px;">  INDENT NO: </td>
    <td style="width: 270px;">  DEPARTMENT: </td>
    </tr>
    <tr>
    <td style="width: 540px;">  INDENT DATE: </td>
    </tr>
   
    
    </table>
    
    
  
    <table style="padding-ledt:40px;" border="1" >
    <tr>
    <td style="width: 20px;text-align:center;font-size:9px;  line-height:15px;" >SR NO</td>
    <td style="width: 134.28px;text-align:center;font-size:9px;line-height:15px;">DESCRIPTION OF GOODS</td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;">APPROVED VENDOR</td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;">QUANTITY</td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;">GRADE</td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;">REMARKS</td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;">REQUIRED DATE</td>
    </tr>
    <tr>
    <td style="width: 20px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 134.28px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;"></td>
    </tr>
   
    <tr>
    <td style="width: 540px;"> FORMAT NO- F/SOP/PD/001/01</td>
    </tr>
    </table> <div></div>
    <table style="padding-ledt:40px;" >
    <tr>
    <td style="width: 180px;">PREPARED BY <br> SIGN:   
    </td>
    <td style="width: 180px;">APPROVED BY<br> SIGN
    </td>
    <td style="width: 180px;">RECEIVED BY <br>  SIGN
    </td>
    </tr>
    
    
    
    
 
</table>
<div></div>



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
</table>

		';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
            else if($_GET["plant_id"] == 28){//demo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
                
				
        $html.=' 
         
 <table border="1">
 <tr>
 <td style="width: 100px;"rowspan="2"> logo</td>
 <td style="width: 270px;" rowspan="2"> Comp. Name</td>
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



   
    <tr>
    <td style="width: 540px;text-align:center;line-height:15px;font-size:20px;">
   <b> NOVO EXCIPIENTS PVT. LTD.</b>
    </td>
    </tr>
    <tr>
    <td style="width: 540px;text-align:center;line-height:25px;font-size:15px;">
    <b>INDENT SLIP</b>
    </td>
    </tr>
    <table  >
    <tr>
    <td style="width: 270px;">  INDENT NO: </td>
    <td style="width: 270px;">  DEPARTMENT: </td>
    </tr>
    <tr>
    <td style="width: 540px;">  INDENT DATE: </td>
    </tr>
   
    
    </table>
    
    
  
    <table style="padding-ledt:40px;" border="1" >
    <tr>
    <td style="width: 20px;text-align:center;font-size:9px;  line-height:15px;" >SR NO</td>
    <td style="width: 134.28px;text-align:center;font-size:9px;line-height:15px;">DESCRIPTION OF GOODS</td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;">APPROVED VENDOR</td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;">QUANTITY</td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;">GRADE</td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;">REMARKS</td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;">REQUIRED DATE</td>
    </tr>
    <tr>
    <td style="width: 20px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 134.28px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;"></td>
    <td style="width: 77.14px;text-align:center;font-size:9px;line-height:15px;"></td>
    </tr>
   
    <tr>
    <td style="width: 540px;"> FORMAT NO- F/SOP/PD/001/01</td>
    </tr>
    </table> <div></div>
    <table style="padding-ledt:40px;" >
    <tr>
    <td style="width: 180px;">PREPARED BY <br> SIGN:   
    </td>
    <td style="width: 180px;">APPROVED BY<br> SIGN
    </td>
    <td style="width: 180px;">RECEIVED BY <br>  SIGN
    </td>
    </tr>
    
    
    
    
 
</table>
<div></div>



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
</table>

		';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
}

$conn->close();
?>