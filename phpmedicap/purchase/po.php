<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

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
    
    
    function getIndianCurrency(float $number, string $words_val)
{
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(0 => '', 1 => 'One', 2 => 'Two',
        3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
        7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
        13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
        19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
        70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety');
    $digits = array('', 'Hundred','Thousand','Lakh', 'Crore');
    while( $i < $digits_length ) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
        } else $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? $Rupees . ''.$words_val : '') . $paise .'Only';
    //return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise .'Only';
}
    
     if ($_GET["type"] == "downloadPOReport") {
        $_GET['filename'] = 'Purchase Order'; $_GET['pdftype'] = 'onlyheader'; include("../../pdfimp2.php");
        $html= "";
        
      //  $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no WHERE p.id='".$_GET["id"]."'";
          $sql = "SELECT p.*, v.vendor_name, v.address,v.vendor_type, v.address_factory, v.gst_no, v.location, v.state_code,s.state_name, 
          v.pincode, c.company_name, c.mobile_no, c.area, c1.company_name as company_name1 , c1.mobile_no as mobile_no1, c1.address as address1,
          c1.area as area1,e.department,e1.department as dept,e.firstname,e1.firstname as firstname1,c.pin as bill_pin,c1.pin as ship_pin,tm.transport_company
          FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN state s ON v.state_code= s.state_code 
          LEFT JOIN company c ON p.billcompany_code=c.company_code
          LEFT JOIN employee e ON p.entry_by=e.emp_id 
          LEFT JOIN employee e1 ON p.entry_by=e1.emp_id 
          LEFT JOIN company c1 ON p.shipcompany_code=c1.company_code
          LEFT JOIN transport_master tm on p.transport = tm.id 
          WHERE p.id='".$_GET["id"]."' ";
            
        $material_type='';
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $float_value = floatval( $row['final_total'] );
                 
                 $po_type =  $row['po_type'] ;
                  $currancy= $row['currancy'];
                  $cur_value="Rupees ";
                    $symbol ="INR";
                    if($currancy == "USD"){
                        $cur_value="Dollars ";
                         $symbol ="$";
                    }else  if($currancy == "EUR"){
                        $cur_value="Euros ";
                         $symbol ="€";
                    }
                $value_in_words= getIndianCurrency($float_value,$cur_value);
                
                $transport =$row['transport'];
                if($transport == null || $transport==''){
                    $transport = "N/A";
                }
                
                $html.='
                <h2 style="text-align:center">Purchase Order</h2>
                          <table border="1" cellpadding="2">
                                 <tr style="background-color:black; color:white;">
                                    <td style="width:60%;"><b>Supplier Details</b></td> 
                                    <td style="width:20%;"><b>Vendor Type</b></td> 
                                    <td style="width:20%;"><b>'.$row['vendor_type'].'</b></td> 
                                    
                                </tr>
                                <tr>
                                    <td style="width:20%;"><b>Vendor Name:</b></td>
                                    <td style="width:80%;">'.$row['vendor_name'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;"><b>Address:</b></td>
                                    <td style="width:40%;">'.$row['address'].'</td>
                                    <td style="width:20%;" text-align:right;><b>GSTIN</b></td>
                                    <td style="width:20%;">'.$row['gst_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;"><b>State:</b></td>
                                     <td style="width:80%;">'.$row['state_name'].'</td>
                                </tr>
                                
                            </table>  <div></div>
                
                            <table border="1" cellpadding="2">
                    <tr style="background-color:black; color:white;">
                        <td style="width:33%;text-align:center;"><b>Bill To</b></td>
                        <td style="width:33%;text-align:center;"><b>SHIP TO</b></td>
                        <td style="width:34%;text-align:center;"><b>P.O NUMBER</b></td>
                    </tr>
                    <tr>
                        <td style="width:33%;">
                            <table>
                                <tr>
                                    <td style="width:100%;"><b>Name Of Company:</b></td>
                                    
                                </tr>
                                 <tr>
                                    <td style="width:100%;">'.$row['company_name'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Address:</b></td>
                                    <td style="width:60%;">'.$row['address1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Email:</b></td>
                                    <td style="width:60%;">'.$row['website'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Mobile No:</b></td>
                                    <td style="width:60%;">'.$row['mobile_no1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>GST No</b></td>
                                    <td style="width:60%;">'.$row['gst_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Location:</b></td>
                                     <td style="width:60%;">'.$row['area'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>State:</b></td>
                                    <td style="width:60%;">'.$row['state_name'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Pin:</b></td>
                                    <td style="width:60%;">'.$row['bill_pin'].'</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:34%;">
                            <table>
                                <tr>
                                    <td style="width:100%;"><b>Name Of Company:</b></td>
                                </tr>
                                 <tr>
                                    <td style="width:100%;">'.$row['company_name1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Address:</b></td>
                                    <td style="width:60%;">'.$row['address1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Email:</b></td>
                                    <td style="width:60%;">'.$row['website'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Mobile No:</b></td>
                                    <td style="width:60%;">'.$row['mobile_no1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>GST No:</b></td>
                                    <td style="width:60%;">'.$row['gst_no'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Location:</b></td>
                                    <td style="width:60%;">'.$row['area'].'</td>
                                </tr>
                                 <tr>
                                    <td style="width:40%;"><b>State:</b></td>
                                    <td style="width:60%;">'.$row['state_name'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:40%;"><b>Pin:</b></td>
                                    <td style="width:60%;">'.$row['ship_pin'].'</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:33%;">
                            <table>
                               <br> <tr>
                                    <td style="width:30%;font-weight:bold;">PO No:</td>
                                    <td style="width:70%;">'.$row['po_no'].'</td>
                                </tr><br>
                                <tr>
                                    <td style="width:30%;font-weight:bold;">PO Type:</td>
                                    <td style="width:70%;">'.$row['po_type'].'</td>
                                </tr><br>
                                <tr>
                                    <td style="width:30%;font-weight:bold;">PO Date:</td>
                                    <td style="width:70%;">'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                                </tr><br>
                                <tr>
                                    <td style="width:100%;font-weight:bold;">Transport:</td>
                                </tr>
                                 <tr>
                                    
                                    <td style="width:100%;">'.$transport.'</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <div></div>  '; 
                 $html.='<table border="1" cellpadding="2">
                                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                                    <td style="width:10%;">Sr. No.</td>';
                                    if($po_type == 'General Material'){
                                     $html.='<td style="width:28%; colspan=2 ">Material Name</td>
                                             <td style="width:8%; text-align: center;">Qty</td>
                                             <td style="width:8%; text-align: center">Unit</td>
                                             <td style="width:8%; text-align: center">Rate ('.$symbol.') </td>
                                             <td style="width:8%; text-align: center">GST(%)</td>
                                             <td style="width:10%; text-align: center">Taxable Amt </td>
                                             <td style="width:10%; text-align: center">Tax Amt </td>
                                             <td style="width:10%; text-align: center">Total</td>';
                                    }else{
                                         $html.='<td style="width:20%;">Material Name</td>
                                            <td style="width:10%; text-align: center;">Qty</td>
                                            <td style="width:10%; text-align: center">Unit</td>
                                            <td style="width:10%; text-align: center">Rate ('.$symbol.') </td>
                                            <td style="width:10%; text-align: center">GST(%)</td>
                                            <td style="width:10%; text-align: center">Taxable Amt </td>
                                            <td style="width:10%; text-align: center">Tax Amt </td>
                                            <td style="width:10%; text-align: center">Total</td>';
                                    }
                                    $html.='
                                </tr>';
                           
                switch($po_type){
                    case "General Material":
                    $sql1 = "SELECT p.*, m.material_name,m.material_subtype,m.part_size FROM po_material p LEFT JOIN general_material m ON p.material_code=m.material_code WHERE p.po_no='".$row["id"]."'";
                    break;
                    case "Raw Material":
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                    break;
                    case "Packing Material":
                        
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                    break;
                    case "Chemical":
                    $sql1 = "SELECT p.*, m.chemical_name as material_name FROM po_material p LEFT JOIN chemical m ON p.chemical_no=m.chemical_no WHERE p.po_no='".$row["id"]."'AND  p.po_type='Chemical' AND (p.po_indend='Approve' OR p.material_status='pending')  ";
                    break;
                    case "Glassware":
                    $sql1 = "SELECT p.*, m.name as material_name,m.glassware_class as material_subtype FROM po_material p LEFT JOIN glassware m ON p.material_code=m.glassware_no WHERE p.po_no='".$row["id"]."'";
                    break;
                }        
                     
                 $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;text-align: center;">'.$idx.'</td>';
                                        if($po_type== 'General Material'){
                                         $html.='<td style="width:18%;" col-span=2 >'.$row1['material_name'].'</td>
                                                  <td style="width:10%;text-align: right;" >'.$row1['part_size'].'</td>
                                                  <td style="width:8%;text-align: right;" >'.$row1['qty'].'</td>
                                                  <td style="width:8%;text-align: center;">'.$row1['unit'].'</td>
                                                  <td style="width:8%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                                  <td style="width:8%;text-align: right;">'.$row1['gst'].'</td>
                                                  <td style="width:10%;text-align: right;">'.$row1['gross_total'].'</td>
                                                  <td style="width:10%;text-align: right;">'.$row1['tax_total'].'</td>
                                                  <td style="width:10%;text-align: right;">'.$row1['net_total'].'</td>';
                                        }else{
                                             $html.='<td style="width:20%;">'.$row1['material_name'].'</td>
                                                    <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                                    <td style="width:10%;text-align: center;">'.$row1['unit'].'</td>
                                                    <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                                    <td style="width:10%;text-align: right;">'.$row1['gst'].'</td>
                                                    <td style="width:10%;text-align: right;">'.$row1['gross_total'].'</td>
                                                    <td style="width:10%;text-align: right;">'.$row1['tax_total'].'</td>
                                                    <td style="width:10%;text-align: right;">'.$row1['net_total'].'</td>';
                                        }
                                        
                                        $html.='
                                    </tr>';
                                      
                                    
                                   if(strlen($row1['descriptions_list'])>0){
                                        $json_obj =$row1['descriptions_list']; 
                                        $array = json_decode($json_obj, true);
                                          
                                         $html.='<tr>
                                      <td style="width:10%;text-align: center;"> </td>
                                      <td style="width:90%;text-align: left;"> <ul>';
                                      
                                       foreach($array as $values) {
                                            $term_heading =$values['Description'];
                                            $html.='<li>'.$term_heading.'</li>';
                                        }
                                                                              
                                      
                                      $html.='</ul></td></tr>';
                                    }
                                    $idx+=1;
                        }            
                    }                
                                
                                
                /*switch($po_type){
                    case 'General Material':
                                        echo $sql1;
                    $sql1 = "SELECT p.*, m.material_type, m.material_name,m.hsn,m.entry_date,m.part_size FROM po_material p LEFT JOIN general_material m ON p.material_code=m.material_code WHERE p.po_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;">'.$idx.'</td>
                                        <td style="width:20%;">'.$row1['material_name'].'</td>
                                        <td style="width:10%;">'.$row1['part_size'].'</td>
                                        <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gross_total'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }
                    break;
                    case 'Raw Material':
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                                echo $sql1;
                    $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;">'.$idx.'</td>
                                        <td style="width:20%;">'.$row1['material_name'].'</td>
                                        <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['gross_total'].'</td>
                                           <td style="width:10%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }
                    break;
                    case 'Packing Material':
                    $sql1 = "SELECT p.*, m.material_name, m.grade, m.material_subtype FROM po_material p LEFT JOIN material m ON p.material_code=m.material_code WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."' GROUP BY p.id";
                                   echo $sql1;
                    $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;">'.$idx.'</td>
                                        <td style="width:30%;">'.$row1['material_name'].'</td>
                                        <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gross_total'].'</td>
                                           <td style="width:15%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }
                    break;
                    case 'GlassWare':
                    $sql1 = "SELECT p.*, m.name,m.glassware_class FROM po_material p LEFT JOIN glassware m ON p.material_code=m.glassware_no  WHERE p.user_no='".$_GET["user_no"]."' AND p.po_no='".$row["id"]."'";
                    $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;">'.$idx.'</td>
                                        <td style="width:30%;">'.$row1['name'].'</td>
                                        <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gross_total'].'</td>
                                           <td style="width:15%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }
                    break;
                    case 'Chemical':
                    $sql1 = "SELECT p.*, m.chemical_name FROM po_material p LEFT JOIN chemical m ON p.chemical_no=m.chemical_no WHERE p.po_no='".$row["id"]."'AND  p.po_type='Chemical' AND (p.po_indend='Approve' OR p.material_status='pending')  ";
                                    echo $sql1;
                    $result1 = $conn->query($sql1);
                    $idx=1;
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
           
                        
                                     $html.='<tr>
                                      <td style="width:10%;">'.$idx.'</td>
                                        <td style="width:30%;">'.$row1['chemical_name'].'</td>
                                        <td style="width:10%;text-align: right;" >'.$row1['qty'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['unit'].'</td>
                                        <td style="width:10%;text-align: right;">'.$row1['quotation_amt'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gst'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['gross_total'].'</td>
                                           <td style="width:15%;text-align: right;">'.$row1['tax_total'].'</td>
                                        <td style="width:15%;text-align: right;">'.$row1['net_total'].'</td>
                                    </tr>';
                                    $idx+=1;
                        }            
                    }
                    break;
                }      */       

                                             
                $html.='  <tr>
                                    <td style="width:85%;text-align:right;font-weight:bold;">SUB Total</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">'.$row['gross_total'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:85%;text-align:right;font-weight:bold;">Discount</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">'.$row['discount'].'</td>
                                </tr>
                                   <tr><td style="width:70%;">';
            $sql2="SELECT p.gst,sum(p.tax_total) as tax_total from po_material p WHERE p.po_no='".$row["id"]."' GROUP by p.gst"  ; 
             $result2 = $conn->query($sql2);
                                if ($result2->num_rows > 0) {
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $html.='<table><tr>
                                               <td style="width:15%;font-weight:bold;border-left: 1px solid black;">'.$row2['gst'].'%</td>
                                               <td style="width:15%;font-weight:bold;border-left: 1px solid black;text-align:right">'.$row2['tax_total'].'</td>
                                               </tr></table>';
                                        
                                    }} 
                                     $html.='</td>  <td style="width:15%;text-align:right;font-weight:bold;">GST Total</td>
                                    <td style="width:15%;text-align: right;font-weight:bold;">'.$row['gst_total'].'</td>
                                </tr>
                                <tr>
                                  
                                    <td style="width:85%;text-align:right;font-weight:bold;">Total</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">'.$row['net_total'].'</td>
                                </tr>  
                                <tr>
                        <td style="width:85%;text-align:right;font-weight:bold;">Shipping & Handling</td>
                        <td style="width:15%;text-align:right;font-weight:bold;">'.$row['shipping_handling'].'</td>
                    </tr>
                    <tr>
                         <td style="width:85%;text-align:right;font-weight:bold;">Other</td>
                        <td style="width:15%;text-align:right;font-weight:bold;">'.$row['other_charges'].'</td>
                    </tr>
                                
                                <tr>
                                  <td style="width:70%;text-align:left;font-weight:bold;">Value in words</td>
                                    <td style="width:15%;text-align:right;font-weight:bold;">Round off</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">'.$row['rounding'].'</td>
                                </tr>
                                <tr>
                                      <td style="width:70%;text-align:left;font-weight:bold;">'.$value_in_words.'</td>
                                    <td style="width:15%;text-align:right;font-weight:bold;">Net Total</td>
                                    <td style="width:15%; text-align: right;font-weight:bold;">'.$row['final_total'].'</td>
                                </tr> 
                        </table>
                 <br pagebreak="true"/> ';              
                                 
                                
                               /* $html.='<br><br><table border="1" cellpadding="3">
                    <tr style="background-color:black; color:white;">
                        <td style="width:50%;text-align:center;"><b>Checked By & Digital Signed By</b></td>
                        <td style="width:50%;text-align:center;"><b>Approved By & Digital Signed By</b></td>
                    </tr>
                    <tr>
                        <td style="width:50%;" >
                            <table>
                                <tr>
                                    <td style="width:25%;">Name</td>
                                    <td style="width:25%;">:'.$row['entry_by'].'</td>
                                    <td rowspan="3" style="width:50%;text-align:right;"><img src="../../upload/pdf/sign.jpg" style="width:50px;height:50px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">ID</td>
                                    <td style="width:25%;">:'.$row['firstname'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Department</td>
                                    <td style="width:25%;">:Purchase</td>
                                </tr>
                                <tr>
                                <td style="width:100%;">For, AMARDEEP CHEMICAL INDUSTRIES PVT. LTD </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Date</td>
                                    <td style="width:35%;">:'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                                    <td style="width:20%;">Time</td>
                                    <td style="width:20%;">:'.date('H:i:s',strtotime($row['entry_date'])).'</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:50%;">
                            <table>
                                <tr>
                                    <td style="width:25%;">Name</td>
                                    <td style="width:25%;">:'.$row['approve_by'].'</td>
                                    <td rowspan="3" style="width:50%;text-align:right;"><img src="../../upload/pdf/sign.jpg" style="width:50px;height:50px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">ID</td>
                                    <td style="width:25%;">:'.$row['firstname1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Department</td>
                                    <td style="width:25%;">:Purchase</td>
                                </tr>
                                <tr>
                                <td style="width:100%;">For, AMARDEEP CHEMICAL INDUSTRIES PVT. LTD </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Date</td>
                                    <td style="width:35%;">:'.date('d-m-Y',strtotime($row['approve_date'])).'</td>
                                    <td style="width:20%;">Time</td>
                                    <td style="width:20%;">:'.date('H:i:s',strtotime($row['approve_date'])).'</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                                <td style="width:100%;text-align:center">Regd. Office :Plot No: A2/8, 1St Phase, G.I.D.C, Vapi, Dist. Valsad - 396195, CIN No.U99999GJ1971PTC109282</td>
                                </tr>
                </table>  <br pagebreak="true"/>'*/ ; 
                                
                                 $html.='
                
                 <table border="1" cellpadding="2">
                  <tr style="background-color:black; color:white;">
                            <td>    Terms & Conditions </td>
                        </tr>
                        <tr>
                            <td>
                                <ul>
                                    <li>Please send two copies of your invoice.</li>
                                    <li>Enter this order in accordance with the prices, terms, delivery method, and specifications listed above</li>
                                    <li>Please notify us immediately if you are unable to ship as specified.</li>
                                    <li>Send all correspondence to:</li>
                                </ul>
                            </td>
                            
                        </tr>
                        
                    <tr style="background-color:black; color:white;">
                        <td style="width:10%;text-align:center;"><b>Sr.No</b></td>
                        <td style="width:30%;text-align:left;"><b>Term Heading</b></td>
                        <td style="width:60%;text-align:left;"><b>Terms</b></td>
                    </tr> ';
                    
                  $term_heading ="";
                 $hdr_printed=false;
            $json_obj= $row['terms_conditions'];
            $array = json_decode($json_obj, true);
            foreach($array as $values) {
                $term = $values['term'];
                $term_heading =$values['term_heading'];
                     $html.=' <tr>
                        <td style="width:10%;">'.$i++.'</td>
                        <td style="width:30%;text-align:left;">'.$term_heading.'</td>
                        <td style="width:60%;text-align:left;">'.$term.'</td>
                    </tr>';
            }   
             $html.=' </table>';
                    $html.='<br><br><table border="1" cellpadding="3">
                    <tr style="background-color:black; color:white;">
                        <td style="width:50%;text-align:center;"><b>Checked By & Digital Signed By</b></td>
                        <td style="width:50%;text-align:center;"><b>Approved By & Digital Signed By</b></td>
                    </tr>
                    <tr>
                        <td style="width:50%;" >
                            <table>
                                <tr>
                                    <td style="width:25%;">Name</td>
                                    <td style="width:25%;">:'.$row['entry_by'].'</td>
                                    <td rowspan="3" style="width:50%;text-align:right;"><img src="../../upload/pdf/sign.jpg" style="width:50px;height:50px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">ID</td>
                                    <td style="width:25%;">:'.$row['firstname'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Department</td>
                                    <td style="width:25%;">:Purchase</td>
                                </tr>
                                <tr>
                                <td style="width:100%;">For, AMARDEEP CHEMICAL INDUSTRIES PVT. LTD </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Date</td>
                                    <td style="width:35%;">:'.date('d-m-Y',strtotime($row['entry_date'])).'</td>
                                    <td style="width:20%;">Time</td>
                                    <td style="width:20%;">:'.date('H:i:s',strtotime($row['entry_date'])).'</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:50%;">
                            <table>
                                <tr>
                                    <td style="width:25%;">Name</td>
                                    <td style="width:25%;">:'.$row['approve_by'].'</td>
                                    <td rowspan="3" style="width:50%;text-align:right;"><img src="../../upload/pdf/sign.jpg" style="width:50px;height:50px;"> </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">ID</td>
                                    <td style="width:25%;">:'.$row['firstname1'].'</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Department</td>
                                    <td style="width:25%;">:Purchase</td>
                                </tr>
                                <tr>
                                <td style="width:100%;">For, AMARDEEP CHEMICAL INDUSTRIES PVT. LTD </td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Date</td>
                                    <td style="width:35%;">:'.date('d-m-Y',strtotime($row['approve_date'])).'</td>
                                    <td style="width:20%;">Time</td>
                                    <td style="width:20%;">:'.date('H:i:s',strtotime($row['approve_date'])).'</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                                <td style="width:100%;text-align:center">Regd. Office :Plot No: A2/8, 1St Phase, G.I.D.C, Vapi, Dist. Valsad - 396195, CIN No.U99999GJ1971PTC109282</td>
                                </tr>
                </table>  <br pagebreak="true"/>' ; 
                                
           
           
                    //$html.="</table>";
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Po Report.pdf', 'I');
               // break;  
            }
        }
    }
        else if ($_GET["type"] == "getApprovedPurchaseOrders") {
          
        $output = array();
         echo  $sql="SELECT p.*, v.vendor_name, v.address, v.address_factory, v.gst_no, v.location,
         v.state_name, v.state_code, v.pincode FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
         LEFT JOIN state s ON v.state_code= s.state_code WHERE p.plant_id= '".$_GET["plant_id"]."' and 
         p.status in('approved') ORDER BY p.id DESC";
        
       /* $sql = "SELECT p.*, v.vendor_name, v.address_corporate, v.address_factory, v.gst_no, v.location, v.state_name, v.state_code, v.pincode, c.company_name,
        c.mobile_no, v.address, c1.company_name as company_name1 , c1.mobile_no as mobile_no1, c1.address as address1  FROM purchaseorder 
        p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no LEFT JOIN company c ON p.billcompany_code=c.company_code LEFT JOIN company 
        c1 ON p.shipcompany_code=c1.company_code WHERE   p.plant_id='".$_GET["plant_id"]."' and p.status ='approve' ORDER BY p.id DESC";
    echo $sql;*/
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

            //     $output1 = array();
            //      $sql1 = "SELECT distinct p.id , p.*, m.material_name, m.grade as gradeName, m.material_subtype,g.material_subtype as material_subtype1 FROM po_material p 
            //      LEFT JOIN master_material m ON p.material_code=m.material_code 
            //  LEFT JOIN general_material g ON p.material_code=g.material_code WHERE p.user_no='".$_GET["user_no"]."' 
            //  AND p.po_no='".$row["id"]."' ";
                 
            //     $result1 = $conn->query($sql1);
            //     if ($result1->num_rows > 0) {
            //         while ($row1 = $result1->fetch_assoc()) {
        //                  $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row1['gradeName']."')";
        //      $resQ = $conn->query($q);
        //       $prodLatest = $resQ->fetch_assoc(); 
         
        //   $row1['gradeName'] = $prodLatest['gradeName']; 
                        
                        // $output1[] = $row1;
            //         }
            //     }
            //   $row["materials"] = $output1;
            //      $row["terms_conditions"]=json_decode($row["terms_conditions"]);
            //      $row["additional_term"]=json_decode($row["additional_term"]);
        
                $output[] = $row;
                
            }
        }
        echo json_encode($output);
    }

     else if ($_GET["type"] == "approvePO") {
        $final_total = floatval($_GET["shipping_handling"])+floatval($_GET["other_charges"]);
        $transport =$_GET["dispatch_through"];
      
        $sql = "UPDATE purchaseorder SET status='".$_GET["status"]."',remark='".$_GET["remark"]."', 
        transport='".$_GET["dispatch_through"]."',shipping_handling='".$_GET["shipping_handling"]."',other_charges='".$_GET["other_charges"]."', 
        final_total=((net_total-disc_amt)+".$final_total.")+(rounding),approve_by='".$_GET["emp_id"]."', approve_date='".$entry_date."' WHERE id='".$_GET["id"]."'";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }
} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>