<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    // print_r($_GET);exit;
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    $counter = 1;
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
	
	if($_GET['type'] == 'downloadQuotationLog') {
	      if($_GET["plant_id"] == 72) {
	
	       $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
		$html.='
		 <table border="1" >
 <tr style="background-color:#DDDAD9;">
     <td style="width:27px; text-align: center;"><b>Sr No.</b></td>
     <td style="width:81px; text-align: center;"><b>Quotation No</b></td>
     <td style="width:81px; text-align: center;"><b>Material Type</b></td>
     <td style="width:135px; text-align: center;"><b>Vendor Name</b></td>
     <td style="width:108px; text-align: center;"><b>Vendor Id</b></td>
     <td style="width:108px; text-align: center;"><b>Quotation Date</b></td>
 </tr>';
  $j=1;
   $sql= "SELECT q.*, q.vendor_id,v.id,v.vendor_name FROM quotation_hdr q LEFT JOIN vendor v on q.vendor_id = v.id ORDER BY q.id DESC";     
   
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
 $html.='<tr nobr="true">
     <td style="width:27px; text-align: center;"> '.$j++.'</td>
     <td style="width:81px; text-align: center;">  '.$row['quotation_no'].'</td>
     <td style="width:81px; text-align: center;"> '.$row['material_type'].'</td>
     <td style="width:135px; text-align: left;"> '.$row['vendor_name'].'</td>
     <td style="width:108px; text-align: center;"> '.$row['vendor_id'].'</td>
     <td style="width:108px; text-align: center;"> '.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
 </tr>';
                    }
        }

$html.='</table>
		';
			$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	}
	else if($_GET["plant_id"] == 29) {
	
	       $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
		$html.='
		 <table border="1" cellpadding="5" style="text-align:center;">
 <tr style="background-color:#DDDAD9;">
     <td style="width:27px; text-align: center;"><b>Sr No.</b></td>
     <td style="width:81px; text-align: center;"><b>Quotation No</b></td>
     <td style="width:81px; text-align: center;"><b>Material Type</b></td>
     <td style="width:135px; text-align: left;"><b>Vendor Name</b></td>
     <td style="width:108px; text-align: center;"><b>Vendor Id</b></td>
     <td style="width:108px; text-align: center;"><b>Quotation Date</b></td>
 </tr>';
  $j=1;
   $sql= "SELECT q.*, q.vendor_id,v.id,v.vendor_name FROM quotation_hdr q LEFT JOIN vendor v on q.vendor_id = v.id ORDER BY q.id DESC";     
   
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
 $html.='<tr nobr="true">
     <td style="width:27px; text-align: center;"> '.$j++.'</td>
     <td style="width:81px; text-align: center;"> '.$row['quotation_no'].'</td>
     <td style="width:81px; text-align: center;"> '.$row['material_type'].'</td>
     <td style="width:135px; text-align: left;"> '.$row['vendor_name'].'</td>
     <td style="width:108px; text-align: center;"> '.$row['vendor_id'].'</td>
     <td style="width:108px; text-align: center;"> '.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
 </tr>';
                    }
        }

$html.='</table>
		';
			$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	}	
    else if($_GET["plant_id"] == 59) { //amardeep
	
	       $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
		$html.='
		 <table border="1" cellpadding="5" style="text-align:center;">
 <tr style="background-color:#DDDAD9;">
     <td style="width:27px; text-align: center;"><b>Sr No.</b></td>
     <td style="width:81px; text-align: center;"><b>Quotation No</b></td>
     <td style="width:81px; text-align: center;"><b>Material Type</b></td>
     <td style="width:135px; text-align: left;"><b>Vendor Name</b></td>
     <td style="width:108px; text-align: center;"><b>Vendor Id</b></td>
     <td style="width:108px; text-align: center;"><b>Quotation Date</b></td>
 </tr>';

        
          $j=1;
           $sql= "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC";  
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array();
             $sql1="SELECT q.*, v.material_name,v.material_type,v.material_subtype FROM quotation_dtl q LEFT JOIN material v 
             ON q.material_id=v.id and q.material_code = v.material_code where q.quotation_hdr_id = '".$row['id']."' ";
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                       $html.='<tr nobr="true">
     <td style="width:27px; text-align: center;"> '.$j++.'</td>
     <td style="width:81px; text-align: center;"> '.$row['quotation_no'].'</td>
     <td style="width:81px; text-align: center;"> '.$row['material_type'].'</td>
     <td style="width:135px; text-align: left;"> '.$row['vendor_name'].'</td>
     <td style="width:108px; text-align: center;"> '.$row['vendor_id'].'</td>
     <td style="width:108px; text-align: center;"> '.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
 </tr>';
                    }
                    
                }
            
             
            }
        }

$html.='</table>
		';
			$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	} else if($_GET["plant_id"] == 67) { //saipro
	
	       $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
		$html.='
		 <table border="1" cellpadding="5" style="text-align:center;">
 <tr style="background-color:#DDDAD9;">
     <td style="width:27px; text-align: center;"><b>Sr No.</b></td>
     <td style="width:81px; text-align: center;"><b>Quotation No</b></td>
     <td style="width:81px; text-align: center;"><b>Material Type</b></td>
     <td style="width:135px; text-align: left;"><b>Vendor Name</b></td>
     <td style="width:108px; text-align: center;"><b>Vendor Id</b></td>
     <td style="width:108px; text-align: center;"><b>Quotation Date</b></td>
 </tr>';

        
          $j=1;
     $sql= "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC"; 
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            //  $output1 = Array();
            //           $sql1="SELECT distinct q.*, v.material_name,v.material_type,v.material_subtype FROM quotation_dtl q LEFT JOIN master_material v 
            //  ON q.material_id=v.id and q.material_code = v.material_code where q.quotation_hdr_id = '".$row['id']."' ";
            //      $result1 = $conn->query($sql1);
            //     if ($result1->num_rows > 0) {
            //         while ($row1 = $result1->fetch_assoc()) {
                    
                       $html.='<tr nobr="true">
     <td style="width:27px; text-align: center;" > '.$j++.'</td>
     <td style="width:81px; text-align: center;"> '.$row['quotation_no'].'</td>
     <td style="width:81px; text-align: center;"> '.$row['material_type'].'</td>
     <td style="width:135px; text-align: left;"> '.$row['vendor_name'].'</td>
     <td style="width:108px; text-align: center;"> '.$row['vendor_id'].'</td>
     <td style="width:108px; text-align: center;"> '.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
 </tr>';
                //     }
                    
                // }
            
             
            }
        }

$html.='</table>
		';
			$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	}
	    else if($_GET["plant_id"] == 58) { //amardeep
	
	       $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
		$html.='
		 <table border="1" cellpadding="5" style="text-align:center;">
 <tr style="background-color:#DDDAD9;">
     <td style="width:27px; text-align: center;"><b>Sr No.</b></td>
     <td style="width:81px; text-align: center;"><b>Quotation No</b></td>
     <td style="width:81px; text-align: center;"><b>Material Type</b></td>
     <td style="width:135px; text-align: left;"><b>Vendor Name</b></td>
     <td style="width:108px; text-align: center;"><b>Vendor Id</b></td>
     <td style="width:108px; text-align: center;"><b>Quotation Date</b></td>
 </tr>';

        
          $j=1;
           $sql= "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC";  
     
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
             $output1 = Array();
             $sql1="SELECT q.*, v.material_name,v.material_type,v.material_subtype FROM quotation_dtl q LEFT JOIN material v 
             ON q.material_id=v.id and q.material_code = v.material_code where q.quotation_hdr_id = '".$row['id']."' ";
                 $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    
                       $html.='<tr nobr="true">
     <td style="width:27px; text-align: center;"> '.$j++.'</td>
     <td style="width:81px; text-align: center;"> '.$row['quotation_no'].'</td>
     <td style="width:81px; text-align: center;"> '.$row['material_type'].'</td>
     <td style="width:135px; text-align: left;"> '.$row['vendor_name'].'</td>
     <td style="width:108px; text-align: center;"> '.$row['vendor_id'].'</td>
     <td style="width:108px; text-align: center;"> '.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
 </tr>';
                    }
                    
                }
            
             
            }
        }

$html.='</table>
		';
			$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	}

	else if($_GET["plant_id"] == 28) {
	
	       $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
		$html.='
		 <table border="1" cellpadding="5" style="text-align:center;">
 <tr style="background-color:#DDDAD9;">
     <td style="width:27px; text-align: center;"><b>Sr No.</b></td>
     <td style="width:81px; text-align: center;"><b>Quotation No</b></td>
     <td style="width:81px; text-align: center;"><b>Material Type</b></td>
     <td style="width:135px; text-align: left;"><b>Vendor Name</b></td>
     <td style="width:108px; text-align: center;"><b>Vendor Id</b></td>
     <td style="width:108px; text-align: center;"><b>Quotation Date</b></td>
 </tr>';
  $j=1;
  $sql= "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC";    
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
 $html.='<tr nobr="true">
     <td style="width:27px; text-align: center;"> '.$j++.'</td>
     <td style="width:81px; text-align: center;"> '.$row['quotation_no'].'</td>
     <td style="width:81px; text-align: center;"> '.$row['material_type'].'</td>
     <td style="width:135px; text-align: left;"> '.$row['vendor_name'].'</td>
     <td style="width:108px; text-align: center;"> '.$row['vendor_id'].'</td>
     <td style="width:108px; text-align: center;"> '.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
 </tr>';
                    }
        }

$html.='</table>
		';
			$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	}
	
	
	else if($_GET["plant_id"] == 70) {
	
	       $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
		$html.='
		 <table border="1" cellpadding="5" style="text-align:center;">
 <tr style="background-color:#DDDAD9;">
     <td style="width:27px; text-align: center;"><b>Sr No.</b></td>
     <td style="width:81px; text-align: center;"><b>Quotation No</b></td>
     <td style="width:81px; text-align: center;"><b>Material Type</b></td>
     <td style="width:135px; text-align: left;"><b>Vendor Name</b></td>
     <td style="width:108px; text-align: center;"><b>Vendor Id</b></td>
     <td style="width:108px; text-align: center;"><b>Quotation Date</b></td>
 </tr>';
  $j=1;
   $sql= "SELECT q.*, q.vendor_id,v.id,v.vendor_name FROM quotation_hdr q LEFT JOIN vendor v on q.vendor_id = v.id ORDER BY q.id DESC";     
   
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
 $html.='<tr nobr="true">
     <td style="width:27px; text-align: center;"> '.$j++.'</td>
     <td style="width:81px; text-align: center;"> '.$row['quotation_no'].'</td>
     <td style="width:81px; text-align: center;"> '.$row['material_type'].'</td>
     <td style="width:135px; text-align: left;"> '.$row['vendor_name'].'</td>
     <td style="width:108px; text-align: center;"> '.$row['vendor_id'].'</td>
     <td style="width:108px; text-align: center;"> '.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
 </tr>';
                    }
        }

$html.='</table>
		';
			$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	}
	
	else if($_GET["plant_id"] == 77) { // Zuma create 14/10/23 swapnil
	
	       $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
		$html.='
		 <table border="1" cellpadding="5" style="text-align:center;">
 <tr style="background-color:#DDDAD9;">
     <td style="width:27px; text-align: center"><b> Sr No.</b></td>
     <td style="width:81px; text-align: center"><b> Quotation No</b></td>
     <td style="width:81px; text-align: center"><b> Material Type</b></td>
     <td style="width:135px; text-align: left"><b> Vendor Name</b></td>
     <td style="width:108px; text-align: center"><b> Vendor Id</b></td>
     <td style="width:108px; text-align: center"><b> Quotation Date</b></td>
 </tr>';
  $j=1;
   $sql= "SELECT q.*, q.vendor_id,v.id,v.vendor_name FROM quotation_hdr q LEFT JOIN vendor v on q.vendor_id = v.id ORDER BY q.id DESC";     
   
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
 $html.='<tr nobr="true">
     <td style="width:27px; text-align: center;"> '.$j++.'</td>
     <td style="width:81px; text-align: center;"> '.$row['quotation_no'].'</td>
     <td style="width:81px; text-align: center;"> '.$row['material_type'].'</td>
     <td style="width:135px; text-align: left;"> '.$row['vendor_name'].'</td>
     <td style="width:108px; text-align: center;"> '.$row['vendor_id'].'</td>
     <td style="width:108px; text-align: center;"> '.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
 </tr>';
                    }
        }

$html.='</table>
		';
			$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	}
		else  {  
	
	       $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
		$html.='
		 <table border="1" cellpadding="5" style="text-align:center;">
 <tr style="background-color:#DDDAD9;">
     <td style="width:27px; text-align: center"><b>  Sr No.</b></td>
     <td style="width:81px; text-align: center"><b>  Quotation No</b></td>
     <td style="width:81px; text-align: center"><b>  Material Type</b></td>
     <td style="width:135px; text-align: left"><b>  Vendor Name</b></td>
     <td style="width:108px; text-align: center"><b>  Vendor Id</b></td>
     <td style="width:108px; text-align: center"><b>  Quotation Date</b></td>
 </tr>';
  $j=1;
   $sql= "SELECT q.*, v.vendor_name, v.email,v.city, v.gst_no FROM quotation_hdr q 
    LEFT JOIN vendor v ON q.vendor_id=v.id WHERE q.status='approve' and q.plant_id = '".$_GET["plant_id"]."' 
    ORDER BY q.id DESC";     
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
 $html.='<tr nobr="true">
     <td style="width:27px; text-align: center;">'.$j++.'</td>
     <td style="width:81px; text-align: center;">'.$row['quotation_no'].'</td>
     <td style="width:81px; text-align: center;">'.$row['material_type'].'</td>
     <td style="width:135px; text-align: left;">'.$row['vendor_name'].'</td>
     <td style="width:108px; text-align: center;">'.$row['vendor_id'].'</td>
     <td style="width:108px; text-align: center;">'.date('d-m-Y ', strtotime($row['entry_date'])).'</td>
 </tr>';
                    }
        }

$html.='</table>
		';
			$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	}
	
	
	}
	}
	$conn->close();
?>