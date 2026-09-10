<?php
try {
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    
    // ini_set('display_errors', 1);
    // error_reporting(E_ALL); 

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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    function getIndianCurrency(float $number, string $words_val)
    {
        $decimal = round($number - ($no = floor($number)) , 2) * 100;
        $hundred = null;
        $digits_length = strlen($no);
        $i = 0;
        $str = array();
        $words = array(
            0 => '',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety'
        );
        $digits = array(
            '',
            'Hundred',
            'Thousand',
            'Lakh',
            'Crore'
        );
        while ($i < $digits_length)
        {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number)
            {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            }
            else $str[] = null;
        }
        $Rupees = implode('', array_reverse($str));
        $paise = ($decimal > 0) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
        return ($Rupees ? $Rupees . '' . $words_val : '') . $paise . 'Only';
        //return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise .'Only';
        
    }
    
    
    function getGrdeValue($grade , $conn){
    
            if ($grade == 'NA') {
                $grd = [0]; // Default value as an array containing 0
            } else {
                $grd = $grade;
            }
            
            // Ensure $grd is properly formatted as a comma-separated list
            if (!is_array($grd)) {
                $grd = explode(',', $grd); // Convert to an array if it is a string
            }
            
            // Validate $grd to contain only integers
            $grd = array_filter($grd, function($value) {
                return is_numeric($value) && intval($value) > 0; // Allow only positive integers
            });
            
            // Convert back to a comma-separated string for SQL
            $grdList = implode(',', $grd);
            
            if (!empty($grdList)) {
                // Only execute the query if $grdList is not empty
                $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade WHERE id IN ($grdList)";
               // echo $q; // Debugging: Display the query
                
                $resQ = $conn->query($q);
                if ($resQ) {
                    $prodLatest = $resQ->fetch_assoc();
                    $gradeName = $prodLatest['gradeName'];
                } else {
                    // Handle SQL query errors
                    echo "SQL Error: " . $conn->error;
                }
            } else {
                // Handle case where $grdList is empty
                $gradeName = "NA"; // Set a default value or handle it appropriately
              //  echo "No valid grades to fetch.";
            }     
            
            
            return $gradeName;
            
            
}
    
 if ($_GET["type"]=="downloadPOReport") {
             if($_GET['plant_id']=='182'){
                 
                 
                
	    $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'noheader';  include('../pdfimp2.php');
		
  $sql = "SELECT p.*, v.vendor_name, v.address,v.vendor_type, v.gst_no as vendor_gst, v.scode,v.permanent_state, v.panNo,v.contact_person,v.contact_email,v.contact_number,
          v.pincode, c.company_name, c.mobile_no, c.area, c.gst_no,c1.gst_no as gst_no1,c.address as c_address, c.p_email as email,c1.p_email as email1, c1.company_name as company_name1 , c1.mobile_no as mobile_no1, c1.address as address1,
          c1.area as area1,e.department,e1.department as dept,e.firstname,e1.firstname as firstname1,c.pin as bill_pin,
          c1.pin as ship_pin,tm.transport_company,c.present_state as b_state, c1.present_state as s_state,
         pid.plant_full_address,pid.plant_name FROM purchaseorder p LEFT JOIN vendor v ON p.vendor_no=v.vendor_no  
          LEFT JOIN company c ON p.billcompany_code=c.company_code
          LEFT JOIN employee e ON p.entry_by=e.emp_id 
          LEFT JOIN employee e1 ON p.entry_by=e1.emp_id 
          LEFT JOIN company c1 ON p.shipcompany_code=c1.company_code
          LEFT JOIN transport_master tm on p.transport = tm.id 
          LEFT JOIN plant pid ON p.plant_id=pid.plant_id
          WHERE p.id='". $_GET["id"] ."' limit 1";
            $material_type = '';
        $result = $conn->query($sql);
    $row = $result->fetch_assoc();{
        
       $selectedShipJson = $row['selectedShip']; // JSON string from DB
$selectedShip = json_decode($selectedShipJson, true); // decode as associative array
$fullAddress = $selectedShip['address'] . ', ' 
             . $selectedShip['present_city'] . ', ' 
             . $selectedShip['present_state'] . ' - ' 
             . $selectedShip['pin'];
  
        $float_value = $row['final_total'];
        $cur_value = $row['currency'].' ';
        
        $value_in_words = getIndianCurrency($float_value, $cur_value);
           
            
			$html= "";
	$html .= '
<table border="0" cellpadding="0" cellspacing="0" width="540"  style="border:1px solid #5eaba8;">
    <tr>
        <!-- LEFT CELL -->
        <td style="
            width:80px; font-weight:bold; border-bottom:0.5px solid #5eaba8;">
            LOGO
        </td>

        <!-- RIGHT CELL -->
        <td style="
            width:460px;text-align:center; border-bottom:0.5px solid #5eaba8;" >
            ONE ASIA NETWORK INDIA PVT LTD<br>
            C-9 PART (MIDC) SUTALA BK KHAMGAON Maharashtra-444303.
        </td>
    </tr>

    <tr>
        <td style="width:80px;"> Telephone No</td>
        <td style="border-right:0.5px solid #5eaba8;width:200px;text-align:left">: 8857881297</td>
        <td style="width:80px;"> STATE CODE</td>
        <td style="width:180px;text-align:left">: 27 - MAHARASHTRA</td>
    </tr>
    <tr>
        <td style="width:80px; "> Email</td>
        <td style="border-right:0.5px solid #5eaba8;width:200px;text-align:left">: commercial@oanindia.com</td>
        <td style="width:80px;"> GSTIN No.</td>
        <td style="width:180px;text-align:left">: 27AADCO0514H1ZS</td>
    </tr>
    <tr>
        <td style="width:80px; "> Website</td>
        <td style="border-right:0.5px solid #5eaba8;width:200px;text-align:left">: www.oneasia-network.com/</td>
        <td style="width:80px;"> PAN No</td>
        <td style="width:180px;text-align:left">: AADCO0514H</td>
    </tr>
</table>

<table cellpadding="4" cellspacing="0">
    <tr>
        <td style=" border-left:0.5px solid #5eaba8;width:80px;">Supplier</td>
        <td style="border-right:0.5px solid #5eaba8; width:200px;text-align:left">: ' . $row['vendor_name'] . ' </td>
        <td style="border-left:0.5px solid #5eaba8;width:80px;">P.O. No</td>
        <td style="border-right:0.5px solid #5eaba8;width:180px;text-align:left">: ' . $row['po_no'] . '</td>
    </tr>

    <tr>
        <td style=" border-left:0.5px solid #5eaba8;width:80px;" rowspan="2">Address</td>
        <td style="border-right:0.5px solid #5eaba8; width:200px;text-align:left" rowspan="2">: ' . $row['address'] . ' </td>
        <td style="border-left:0.5px solid #5eaba8;width:80px;">P.O. Date.</td>
        <td style="border-right:0.5px solid #5eaba8;width:180px;text-align:left">:  ' . $row['entry_date'] . '</td>
    </tr>

    <tr>
        <td style=" border-left:0.5px solid #5eaba8;width:80px;">Purchase For</td>
        <td style="border-right:0.5px solid #5eaba8; width:180px;text-align:left">: </td>
    </tr>
 

    <tr>
        <td style=" border-left:0.5px solid #5eaba8;width:80px;">State Code</td>
        <td style="border-right:0.5px solid #5eaba8; width:200px;text-align:left">:  ' . $row['scode'] . '</td>
        <td style="border-top:0.5px solid #5eaba8;border-left:0.5px solid #5eaba8;width:80px;">QTN. NO.</td>
        <td style="border-right:0.5px solid #5eaba8;border-top:0.5px solid #5eaba8;width:180px;text-align:left">: -</td>
    </tr>

    <tr>
        <td style="border-left:0.5px solid #5eaba8;width:80px;">GSTIN No</td>
        <td style="border-right:0.5px solid #5eaba8;width:200px;text-align:left">:  ' . $row['vendor_gst'] . ' </td>
        <td style="border-left:0.5px solid #5eaba8;width:80px;">QTN. Date.</td>
        <td style="border-right:0.5px solid #5eaba8;width:180px;text-align:left">:</td>
    </tr>

    <tr>
        <td style="border-left:0.5px solid #5eaba8;width:80px;">PAN No.</td>
        <td style="border-right:0.5px solid #5eaba8;width:200px;text-align:left">:  ' . $row['panNo'] . ' </td>
        <td style="border-right:0.5px solid #5eaba8;border-left:0.5px solid #5eaba8;width:260px;"><u>Delivery Address        :</u> </td>
     </tr>

    <tr>
        <td style="border-left:0.5px solid #5eaba8;width:80px;">Contact.</td>
        <td style="border-right:0.5px solid #5eaba8;width:200px;text-align:left">:  ' . $row['contact_person'] . ' </td>
        <td style="border-right:0.5px solid #5eaba8;border-left:0.5px solid #5eaba8;width:260px;" rowspan="3">  ' .$fullAddress . ' </td>
    </tr>

    <tr>
        <td style="border-left:0.5px solid #5eaba8;width:80px;">Email.</td>
        <td style="border-right:0.5px solid #5eaba8;width:200px;text-align:left">:  ' . $row['contact_email'] . ' </td>
    </tr>

    <tr>
        <td style="border-left:0.5px solid #5eaba8;width:80px;">Mobile No.</td>
        <td style="border-right:0.5px solid #5eaba8width:200px;text-align:left">:  ' . $row['contact_number'] . ' </td>
    </tr>
</table>
<br>

<table cellpadding="4" cellspacing="0">
    <tr>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">Sr</td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">HSN</td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">Item Code</td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;width:9%"> Description of Goods </td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">Order Qty</td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">Rate</td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">Dispatch Date</td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">Delivery Date</td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">SGST %</td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">CGST %</td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">IGST %</td>
        <td style="border:0.5px solid white;background-color:#228280;font-size:8px;">Amount</td>
         
         
    </tr>';
      $sql1 = "SELECT p.*, m.material_name,m.hsn FROM po_material p LEFT JOIN
            my_view m ON p.material_code = m.material_code  WHERE p.plant_id='" . $_GET["plant_id"] . "' AND p.po_no='" . $row["id"] . "' " ;
            
                $i=1;
                $hsn1= isset($row1['hsn']) && !empty($row1['hsn']) ? $row1['hsn'] : 'NA';
                 $descriptions_list= isset($row1['descriptions_list']) && !empty($row1['descriptions_list']) ? $row1['descriptions_list'] : 'NA';
        $result = $conn->query($sql1);
        if ($result->num_rows > 0) {
            while ($row1 = $result->fetch_assoc()) {
                $html.='
    <tr>
        <td style="font-size:8px;border:0.5px solid #5eaba8;"> ' . $i . '</td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;">' . $row1['hsn'] . '</td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;">' . $row1['material_code'] . '</td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;">' . $row1['material_name'] . '</td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;">' . $row1['qty'] . ' ' . $row1['unit'] . '</td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;">' . $row1['quotation_amt'] . '</td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;"></td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;"></td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;">' . $row1['sgstPer'] . '</td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;">' . $row1['cgstPer'] . '</td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;">' . $row1['igstPer'] . '</td>
        <td style="font-size:8px;border:0.5px solid #5eaba8;">' . $row1['gross_total'] . '</td>
         
         
    </tr>';
     $i++;
            }
        }
  $html.=' </table>';

// Calculate totals - use database values if available, otherwise calculate
$total_amount = isset($row['gross_total']) ? $row['gross_total'] : 0;
$discount = isset($row['discTotal']) ? $row['discTotal'] : 0;
$taxable_amount = isset($row['taxable_total']) ? $row['taxable_total'] : ($total_amount - $discount);

// Calculate GST amounts
$sgst_amount = 0;
$cgst_amount = 0;
$igst_amount = 0;

// Try to get GST from database first
if (isset($row['gst_total']) && $row['gst_total'] > 0) {
    // If we have gstSplitData, use it
    if (isset($row['gstSplitData']) && !empty($row['gstSplitData'])) {
        $gst_split = json_decode($row['gstSplitData'], true);
        if (is_array($gst_split)) {
            foreach ($gst_split as $gst_item) {
                $sgst_amount += isset($gst_item['sgst']) ? $gst_item['sgst'] : 0;
                $cgst_amount += isset($gst_item['cgst']) ? $gst_item['cgst'] : 0;
                $igst_amount += isset($gst_item['igst']) ? $gst_item['igst'] : 0;
            }
        }
    } else {
        // Calculate from items
        $sql1_gst = "SELECT sgstPer, cgstPer, igstPer, gross_total, disc_amt FROM po_material 
                     WHERE plant_id='" . $_GET["plant_id"] . "' AND po_no='" . $row["id"] . "'";
        $result_gst = $conn->query($sql1_gst);
        if ($result_gst->num_rows > 0) {
            while ($row_gst = $result_gst->fetch_assoc()) {
                $item_total = $row_gst['gross_total'] - (isset($row_gst['disc_amt']) ? $row_gst['disc_amt'] : 0);
                $sgst_amount += ($item_total * (isset($row_gst['sgstPer']) ? $row_gst['sgstPer'] : 0)) / 100;
                $cgst_amount += ($item_total * (isset($row_gst['cgstPer']) ? $row_gst['cgstPer'] : 0)) / 100;
                $igst_amount += ($item_total * (isset($row_gst['igstPer']) ? $row_gst['igstPer'] : 0)) / 100;
            }
        }
    }
} else {
    // Calculate from items if no GST total in database
    $sql1_gst = "SELECT sgstPer, cgstPer, igstPer, gross_total, disc_amt FROM po_material 
                 WHERE plant_id='" . $_GET["plant_id"] . "' AND po_no='" . $row["id"] . "'";
    $result_gst = $conn->query($sql1_gst);
    if ($result_gst->num_rows > 0) {
        while ($row_gst = $result_gst->fetch_assoc()) {
            $item_total = $row_gst['gross_total'] - (isset($row_gst['disc_amt']) ? $row_gst['disc_amt'] : 0);
            $sgst_amount += ($item_total * (isset($row_gst['sgstPer']) ? $row_gst['sgstPer'] : 0)) / 100;
            $cgst_amount += ($item_total * (isset($row_gst['cgstPer']) ? $row_gst['cgstPer'] : 0)) / 100;
            $igst_amount += ($item_total * (isset($row_gst['igstPer']) ? $row_gst['igstPer'] : 0)) / 100;
        }
    }
}

$grand_total = isset($row['final_total']) ? $row['final_total'] : ($taxable_amount + $sgst_amount + $cgst_amount + $igst_amount);

// Create two-column layout: Left side (Additional Info) and Right side (Summary Totals)
$html .= '
<table cellpadding="0" cellspacing="0"
       style="border:0.5px solid #5eaba8;width:540px;">
    <tr>
        <!-- LEFT COLUMN -->
       <td style="width:260px;vertical-align:top;font-size:8px;">

    <table cellpadding="2" cellspacing="4" style="width:100%;border-bottom:0.5px solid #5eaba8;">
        <tr>
            <td style="width:25%;"><b>Remarks :</b></td>
            <td style="width:75%;">OKAY</td>
        </tr>
    </table>

    <table cellpadding="" cellspacing="4"
           style="width:100%;font-size:8px;border-bottom:0.5px solid #5eaba8;margin-top:4px;">
        <tr>
            <td style="width:30%;">Payment Terms</td>
            <td style="width:20%;">:</td>
            <td style="width:25%;">Validity</td>
            <td style="width:25%;">:</td>
        </tr>

        <tr>
            <td>Delivery Period</td>
            <td>:</td>
            <td>Delivery Place</td>
            <td>: KHAMGAON</td>
        </tr>

        <tr>
            <td>Mode of Despatch</td>
            <td>:</td>
            <td>Freight</td>
            <td>: FOR</td>
        </tr>
    </table>

    <table cellpadding="2" cellspacing="4"
           style="width:100%;font-size:8px;border-bottom:0.5px solid #5eaba8;margin-top:4px;">
        <tr>
            <td style="width:30%;">Transporter Name</td>
            <td style="width:70%;">:</td>
        </tr>
    </table>

    <table cellpadding="" cellspacing="4" style="width:100%;font-size:8px;margin-top:4px;">
        <tr>
            <td>
                <b>Amount In Words :</b><br>
                Two Lakh Seventy One Thousand Four Hundred Only.
            </td>
        </tr>
    </table>

</td>



        
        <td style="width:278px;vertical-align:top;padding-left:5px;">
            <table cellpadding="3" cellspacing="0" style="border:0.5px solid #5eaba8;width:100%;table-layout:fixed;padding-top:25px;">
                <tr>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:left;font-weight:bold;width:60%;">Total</td>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:right;width:40%;">Rs ' . number_format($total_amount, 2) . '</td>
                </tr>
                <tr>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:left;font-weight:bold;">Less : Discount</td>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:right;">' . ($discount > 0 ? 'Rs ' . number_format($discount, 2) : '') . '</td>
                </tr>
                <tr>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:left;font-weight:bold;">Taxable Amount</td>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:right;">Rs ' . number_format($taxable_amount, 2) . '</td>
                </tr>
                <tr>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:left;font-weight:bold;">SGST AMOUNT</td>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:right;">' . ($sgst_amount > 0 ? 'Rs ' . number_format($sgst_amount, 2) : '') . '</td>
                </tr>
                <tr>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:left;font-weight:bold;">CGST AMOUNT</td>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:right;">' . ($cgst_amount > 0 ? 'Rs ' . number_format($cgst_amount, 2) : '') . '</td>
                </tr>
                <tr>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:left;font-weight:bold;">IGST AMOUNT</td>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:right;">' . ($igst_amount > 0 ? 'Rs ' . number_format($igst_amount, 2) : '') . '</td>
                </tr>
                <tr>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:left;font-weight:bold;"></td>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:right;"></td>
                </tr>
                <tr>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:left;font-weight:bold;"></td>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:right;"></td>
                </tr>
                <tr>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:left;font-weight:bold;background-color:#228280;color:white;">GRAND TOTAL</td>
                    <td style="border:0.5px solid #5eaba8;font-size:8px;text-align:right;font-weight:bold;background-color:#228280;color:white;">Rs ' . number_format($grand_total, 2) . '</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>

<table cellpadding="4" cellspacing="0" style="border:0.5px solid #5eaba8;width:540px;">
    <tr>
        <td style="font-size:9px;font-weight:bold;color:black;text-decoration: underline double;">Terms & Conditions</td>
        
    </tr>
    <tr>
        <td style="font-size:8px;padding-left:10px;">
            1. PO No is mandatory to mention in Invoice and excess material will not be accepted over the PO quantity.
        </td>
    </tr>

    <tr>
        <td style="font-size:8px;padding-left:10px;">
            2. All documents must be attached along with original invoice – GR Copy (Bill-T), Certificate of Analysis, E-Way Bill, etc.
        </td>
    </tr>

    <!-- Term 3 -->
    <tr>
        <td style="font-size:8px;padding-left:10px;">
            3. Invoiced material must have minimum 80% shelf life at the time of delivery. Debit note with penalty will be raised in case of rejection.
        </td>
    </tr>';

// Add Terms & Conditions
if (isset($row['terms_conditions']) && !empty($row['terms_conditions'])) {
    $json_obj = $row['terms_conditions'];
    $array = json_decode($json_obj, true);
    if (is_array($array)) {
        foreach ($array as $values) {
            $html .= '<tr>
                <td style="border:0.5px solid #5eaba8;font-size:8px;padding-left:10px;">
                    ' . (isset($values['term']) ? htmlspecialchars($values['term']) : (is_string($values) ? htmlspecialchars($values) : '')) . '
                </td>
            </tr>';
        }
    } else {
        $html .= '<tr>
            <td style="border:0.5px solid #5eaba8;font-size:8px;padding-left:10px;">
                ' . htmlspecialchars($row['terms_conditions']) . '
            </td>
        </tr>';
    }
}

$html .= '</table>

<!-- Signature Section -->
<table cellpadding="4" cellspacing="0" style="border:0.5px solid #5eaba8;width:540px;">
    <tr>
        <td style="width:180px;font-size:9px;">Prepared By</td>
        <td style="width:180px;font-size:9px;">Checked By</td>
        <td style="font-size:9px;text-align:center;padding-top:40px;color:#228280;">For: ONE ASIA NETWORK INDIA PVT LTD.</td>
    </tr>
    <tr>
        <td style="width:180px;font-size:9px;text-align:left;">' . (isset($row['firstname']) && !empty($row['firstname']) ? $row['firstname'] : 'NAKUL BELURKAR') . '</td>
        <td style="font-size:9px;padding-top:40px;"></td>
        <td style="font-size:9px;padding-top:40px; text-align:right;">Authorised Signatory</td>
    </tr>
</table>';

           
            
     
    
		$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	   
 
        }
	  
              
          
	  
              
          
             } else {
                 include(__DIR__ . '/po_print_medicap.php');
             }
      }
	  
	  
 
	      
else if($_GET["type"] == "trainingLogPdf") {

      $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
         $html= "";
         
         
         
    
    		
    $sql11 = "SELECT t.*,e.firstname, e.middlename, e.lastname, e.joining_date ,e.department,e.designation from training_induction t
          LEFT JOIN employee e ON t.emp_id=e.emp_id 
          WHERE t.id='". $_GET["id"] ."' limit 1";
            
           $result11 = $conn->query($sql11);
                     if($result11->num_rows > 0) {
                        while($row = $result11->fetch_assoc()) {
         
         

         $html.='
          <table style="width: 785px" border:none;>
 <tr>
      <td style="width: 785px;text-align:center; border:none;"><h2><b>Induction Training Schedule</b></h2></td>
  </tr>
  <br><br>
   <tr>
      <td style="width: 200px;text-align:left; border:none;height:20px;"><b>Name of New Employee:</b></td>
      <td style="width: 195px;text-align:left; border:none; height:20px;">' . $row['firstname'] . ' ' . $row['lastname'] . '</td>
      <td style="width: 195px;text-align:left; border:none; height:20px;"><b>Department:</b></td>
      <td style="width: 195px;text-align:left; border:none; height:20px;">' . $row['department'] . '</td>
  </tr>
  <tr>
      <td style="width: 200px;text-align:left; border:none; height:20px;"><b>Date of Joining:</b></td>
      <td style="width: 195px;text-align:left; border:none; height:20px;">' . $row['joining_date'] . '</td>
      <td style="width: 195px;text-align:left; border:none; height:20px;"><b>Designation:</b></td>
      <td style="width: 195px;text-align:left; border:none; height:20px;"> ' . $row['designation'] . '</td>
  </tr>
  </table>
  
  <div></div>
  
  <table style="width: 785px" border="1">
 
   <tr>
      <td style="width: 785px;text-align:left;height:20px;font-weight:bold;"><b> Day 1:</b></td>
 
  </tr>
 <tr>
    <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">Time</td>
    <td style="width: 120px;text-align:center; height:20px; font-weight:bold;">Department</td>
    <td style="width: 375px;text-align:center; height:20px; font-weight:bold;">Subject to be Covered</td>
    <td style="width: 100px;text-align:center; height:20px; font-weight:bold;">Responsibility</td>
    <td style="width: 100px;text-align:center; height:20px; font-weight:bold;">Date</td>
    </tr>';
    
$json_obj1 = $row['adminChecklist'];
$adminChecklist = json_decode($json_obj1, true);

if (json_last_error() === JSON_ERROR_NONE && isset($adminChecklist['checklist'])) {
    $json_obj = $adminChecklist['checklist'];
    $cheklist = $json_obj['cheklist'];
 
        $html .= '
            <tr>
                <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '</td>
                <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                    <ul>';
        
         foreach ($cheklist as $check) {
            $html .= '<li>' . $check['subject_covered'] . '</li>';
        }
        
        $html .= '</ul>
                </td>
                <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td>
             </tr>';
            
} 

$json_obj1 = $row['qaChecklist'];
$qaChecklist = json_decode($json_obj1, true);

if (json_last_error() === JSON_ERROR_NONE && isset($qaChecklist['checklist'])) {
    $json_obj = $qaChecklist['checklist'];
    $cheklist = $json_obj['cheklist'];
 
        $html .= '
            <tr>
                <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '</td>
                <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                    <ul>';
        
         foreach ($cheklist as $check) {
            $html .= '<li>' . $check['subject_covered'] . '</li>';
        }
        
        $html .= '</ul>
                </td>
                <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td>
             </tr>';
            
} 

    $html .= '
    <tr>
      <td style="width: 785px;text-align:center;height:20px;"><b>Lunch Break</b></td>
  </tr>';

$json_obj1 = $row['qcChecklist'];
$qcChecklist = json_decode($json_obj1, true);

if (json_last_error() === JSON_ERROR_NONE && isset($qcChecklist['checklist'])) {
    $json_obj = $qcChecklist['checklist'];
    $cheklist = $json_obj['cheklist'];
 
        $html .= '
            <tr>
                <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '</td>
                <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                    <ul>';
        
         foreach ($cheklist as $check) {
            $html .= '<li>' . $check['subject_covered'] . '</li>';
        }
        
        $html .= '</ul>
                </td>
                <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td>
             </tr>';
            
} 
    
      
  $html.='  </table>
  <div style="page-break-before: always;"></div>
  ';
     
          
  $html.='  
     
    <table style="width: 785px" border="1">
 
    <tr>
      <td style="width: 785px;text-align:left;height:20px;font-weight:bold;"><b> Day 2:</b></td>
 
    </tr>
    <tr>
        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">Time</td>
        <td style="width: 120px;text-align:center; height:20px; font-weight:bold;">Department</td>
        <td style="width: 375px;text-align:center; height:20px; font-weight:bold;">Subject to be Covered</td>
        <td style="width: 100px;text-align:center; height:20px; font-weight:bold;">Responsibility</td>
        <td style="width: 100px;text-align:center; height:20px; font-weight:bold;">Date</td>
    </tr>';
    
        $json_obj1 = $row['productionChecklist'];
        $prodChecklist = json_decode($json_obj1, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($prodChecklist['checklist'])) {
            $json_obj = $prodChecklist['checklist'];
            $cheklist = $json_obj['cheklist'];
         
                $html .= '
                    <tr>
                        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '</td>
                        <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                        <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                            <ul>';
                
                 foreach ($cheklist as $check) {
                    $html .= '<li>' . $check['subject_covered'] . '</li>';
                }
                
                $html .= '</ul>
                        </td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td>
                     </tr>';
                    
        } 

    
       
        $json_obj1 = $row['storeChecklist'];
        $storeChecklist = json_decode($json_obj1, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($storeChecklist['checklist'])) {
            $json_obj = $storeChecklist['checklist'];
            $cheklist = $json_obj['cheklist'];
         
                $html .= '
                    <tr>
                        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '</td>
                        <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                        <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                            <ul>';
                
                 foreach ($cheklist as $check) {
                    $html .= '<li>' . $check['subject_covered'] . '</li>';
                }
                
                $html .= '</ul>
                        </td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td>
                     </tr>';
                    
        } 
        
     $html .= '
            <tr>
              <td style="width: 785px;text-align:center;height:20px;"><b>Lunch Break</b></td>
          </tr>';
    
           
        $json_obj1 = $row['engineeringChecklist'];
        $enggChecklist = json_decode($json_obj1, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($enggChecklist['checklist'])) {
            $json_obj = $enggChecklist['checklist'];
            $cheklist = $json_obj['cheklist'];
         
                $html .= '
                    <tr>
                        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '</td>
                        <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                        <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                            <ul>';
                
                 foreach ($cheklist as $check) {
                    $html .= '<li>' . $check['subject_covered'] . '</li>';
                }
                
                $html .= '</ul>
                        </td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td>
                     </tr>';
                    
        } 
           
        $json_obj1 = $row['itChecklist'];
        $itChecklist = json_decode($json_obj1, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($itChecklist['checklist'])) {
            $json_obj = $itChecklist['checklist'];
            $cheklist = $json_obj['cheklist'];
         
                $html .= '
                    <tr>
                        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '</td>
                        <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                        <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                            <ul>';
                
                 foreach ($cheklist as $check) {
                    $html .= '<li>' . $check['subject_covered'] . '</li>';
                }
                
                $html .= '</ul>
                        </td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td>
                     </tr>';
                    
        } 
        $json_obj1 = $row['quality_headChecklist'];
        $qheadChecklist = json_decode($json_obj1, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($qheadChecklist['checklist'])) {
            $json_obj = $qheadChecklist['checklist'];
            $cheklist = $json_obj['cheklist'];
         
                $html .= '
                    <tr>
                        <td style="width: 90px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['fromTime'] . ' - ' . $json_obj['toTime'] . '</td>
                        <td style="width: 120px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['department_name'] . '</td>
                        <td style="width: 375px;text-align:center; height:20px;font-weight:bold;">
                            <ul>';
                
                 foreach ($cheklist as $check) {
                    $html .= '<li>' . $check['subject_covered'] . '</li>';
                }
                
                $html .= '</ul>
                        </td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . $json_obj['responsibility'] . '</td>
                        <td style="width: 100px;text-align:center; height:20px;font-weight:bold;">' . date('d-m-Y', strtotime($json_obj['trDate'])) . '</td>
                     </tr>';
                    
        } 
    
      $html.='  </table>
  <div style="page-break-before: always;"></div>
  ';
     
  $html.='  
  
  
 <table style="width: 785px" border="1">
 
   <tr>
      <td style="width: 655px;text-align:left;height:20px;font-weight:bold;"><b >Day 3:</b></td>
        <td style="width: 130px;text-align:left; height:20px; font-weight:bold;">Date</td>
  </tr>
  
    <tr>
      <td style="width: 655px;text-align:left;height:20px;font-weight:bold;"><b>Handing over to the Department HOD by Admin and Personal.</b> </td>
      <td style="width: 130px;text-align:left; height:20px; font-weight:bold;">Sign/Date</td>
    </tr>
    
    
    <tr>
        <td style="width: 655px;text-align:left; height:20px; ;"> <b>Report Writing on Induction : </b><br> 
       <b> (To be submitted by the candidate) </b><br><br> ' . $row['emp_report'] . '<br>
        </td>
        <td style="width: 130px;text-align:left; height:20px;  "> <br><br><br>' . $row['empRepBy'] . ' /  ' .date('d-m-Y', strtotime($row['empRepOn']))  . ' </td>
    
    </tr>
    <tr>
        <td style="width: 655px;text-align:left; height:20px; "><b>Evaluation of induction Report by Dept. Head : </b>
        <br><br> ' . $row['eval_dept_head'] . '<br></td>
        <td style="width: 130px;text-align:left; height:20px; "><br><br><br>' . $row['eval_dept_head_by'] . ' /  ' .date('d-m-Y', strtotime($row['eval_dept_head_on']))  . ' </td>
  
    </tr>
    <tr>
        <td style="width: 655px;text-align:left; height:20px; "><b> Evaluation of induction Report by Admin. And Personnel : </b>
        <br><br> ' . $row['eval_hr'] . '<br></td>
        <td style="width: 130px;text-align:left; height:20px; "><br><br><br>' . $row['eval_hr_by'] . ' /  ' .date('d-m-Y', strtotime($row['eval_hr_on']))  . ' </td>
    </tr>


</table>


<div></div>
<div></div>
 


     
        
<table style="width: 785px" border="1" cellpadding="3">
      <tr style="background-color:black; color:white;">
          <td style="width: 370px;text-align:center;"><b>Prepared By A & P Dept.</b></td>
          <td style="width: 45px;text-align:center;"><img src="../upload/pdf/sign.jpg" style="width:20px;height:20px;"></td>
          <td style="width: 370px;text-align:center;"><b>Approved By Manager QA</b></td>
      </tr>
  <tr>
      <td style="width: 392px;" >
        <table>
            <tr>
                  <td style="width:100px;font-weight:bold;">Sign</td>
                  <td style="width:292px; ">:' . $row['entry_by'] . '</td>
            </tr>
            <tr>
                <td style="width:100px;font-weight:bold;">Date</td>
                <td style="width:96px; ">:' . date('d-m-Y', strtotime($row['entry_on'])) . '</td>
                <td style="width: 100px;font-weight:bold;">Time</td>
                <td style="width: 96px; ">:' . date('H:i:s', strtotime($row['entry_on'])) . '</td>
            </tr>
        </table>
      </td>
      <td style="width: 392px;">
        <table >
            <tr>
                <td style="width:100px;font-weight:bold;"> Sign :</td>
                <td style="width:292px;">' . $row['qaManagerBy'] . '</td>
            </tr>
            <tr>
                <td style="width:100px;font-weight:bold;"> Date :</td>
                <td style="width:96px;">' . date('d-m-Y', strtotime($row['qaManagerOn'])) . '</td>
                <td style="width: 100px;font-weight:bold;">Time</td>
                <td style="width: 96px;">' . date('H:i:s', strtotime($row['qaManagerOn'])) . '</td>
            </tr>
        
        </table>
      </td>
  </tr>
 
</table>
          





  ';
        }
         
     }
  
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
}
	  
	else if ($_GET["type"] == "FOReport"){
	      
	      	   	   
	   
	    $_GET['filename'] = ' PO';
		$_GET['pdftype']= 'onlyheader';  include('../pdfimp2.php');
		
  $sql = "SELECT p.*, c.TrdNm, c.address, c.state, c.city, c.pincode, c.country,c.phone,c.email ,c1.phone as phone1,c1.email as email1, 
  c1.TrdNm as TrdNm1, c1.address as address1, c1.state as state1, c1.city as city1, c1.pincode as pincode1, c1.country as country1 
  FROM po_entry p 
  LEFT JOIN client c ON p.client_code=c.client_code
  LEFT JOIN client c1 ON p.conisgnee=c1.client_code
  WHERE  p.status !='pending' AND p.id = '".$_GET['id']."'";
          
        $result = $conn->query($sql);
    $row = $result->fetch_assoc();{
    $products = json_decode($row["products"]);
			$html= "";
			$html.='<table>
    <tr>
        <td style="width: 540px;text-align:center;font-size:25px;">Purchase Order  </td>
        </td>
    </tr>
 </table>
  <table  border="1" cellpadding="2" style="border:solid rgb(14, 67, 112) 1px ;">
            <tr style="background-color: black; color: white;">
              <td colspan="4" style="text-align: left; padding: 10px;">Client Details</td>
            </tr>
            <tr>
              <td style="text-align: left;width: 20%;">Client Name:</td>
              <td style="text-align: left;width: 80%;"> '  . $row['TrdNm'] . '  </td>
            </tr>
            <tr>
              <td style="text-align: left;width: 20%;">PO No.:</td>
              <td style="text-align: left;width: 80%;">'  . $row['po_no'] . ' </td>
            </tr>
            <tr>
              <td style="text-align: left;width: 20%;">PO Date:</td>
              <td style="text-align: left;width: 80%;">'  . $row['po_date'] . ' </td>
            </tr>
            <tr>
              <td style="text-align: left;width: 20%;">PO Type:</td>
              <td style="text-align: left;width: 80%;">'  . $row['po_type'] . ' </td>
            </tr>
          
          </table>  <div></div>

           <table border="1" cellpadding="2">
        <tr style="background-color:black; color: white;color:white;">
        <td style="width:269.7px;text-align:center;"><b>BILL TO</b></td>
        <td style="width:269.7px;text-align:center;"><b>SHIP TO</b></td>
        </tr>
        <tr>
        <td style="width:269.7px;">
      <table>
       <tr>
           <td style="width:40%;"><b>Name Of Company:</b></td>
           <td style="width:60%;">' . $row['TrdNm'] . '</td>
       </tr>
       <tr style="margin-top: 100px">
           <td style="width:40%;"><b>Address:</b></td>
           <td style="width:60%;">' . $row['address'] . '</td>
       </tr>
       <tr style="margin-top: 10px">
           <td style="width:40%;"><b>Email:</b></td>
           <td style="width:60%;">' . $row['email'] . '</td>
       </tr>
       <tr>
           <td style="width:40%;"><b>Mobile No:</b></td>
           <td style="width:60%;">' . $row['phone'] . '</td>
       </tr>
       <tr>
           <td style="width:40%;"><b>Location:</b></td>
            <td style="width:60%;">' . $row['city'] . '</td>
       </tr>
       <tr>
           <td style="width:40%;"><b>State:</b></td>
           <td style="width:60%;">' . $row['state'] . '</td>
       </tr>
       <tr>
           <td style="width:40%;"><b>Pin:</b></td>
           <td style="width:60%;">' . $row['pincode'] . '</td>
       </tr>
   </table>
</td>
<td style="width:269.7px;">
   <table>
       <tr>
           <td style="width:40%;"><b>Name Of Company:</b></td>

           <td style="width:60%;">' . $row['TrdNm1'] . '</td>
       </tr>
       <tr style="margin-top: 100px">
           <td style="width:40%;"><b>Address:</b></td>
           <td style="width:60%;">' . $row['address1'] . '</td>
       </tr>
       <tr style="margin-top: 10px">
           <td style="width:40%;"><b>Email:</b></td>
           <td style="width:60%;">' . $row['email1'] . '</td>
       </tr>
       <tr>
           <td style="width:40%;"><b>Mobile No:</b></td>
           <td style="width:60%;">' . $row['phone1'] . '</td>
       </tr>
       <tr>
           <td style="width:40%;"><b>Location:</b></td>
            <td style="width:60%;">' . $row['city1'] . '</td>
       </tr>
       <tr>
           <td style="width:40%;"><b>State:</b></td>
           <td style="width:60%;">' . $row['state1'] . '</td>
       </tr>
       <tr>
           <td style="width:40%;"><b>Pin:</b></td>
           <td style="width:60%;">' . $row['pincode1'] . '</td>
       </tr>
   </table>
</td>

</tr>
</table>
<div></div> 
 <table border="1" cellpadding="2">
            <tr style="background-color:black; color:white;">
                  <td style="width: 540px;"> Product Details  </td>
              </tr>';
               $json_obj = $row['products'];
                $array = json_decode($json_obj, true);
             
                foreach ($array as $values)
                {
                   $html.=' 
<tr>
    <td style="text-align: left; width: 20%;"><b>Quantity ( in Individual Count )  </b></td>
    <td style="text-align: left; width: 30%;">'.$values['quantity'].'</td>
    <td style="text-align: left; width: 20%;"><b>Quantity In Packs :  <span style="color: red;">*</span></b></td>
    <td style="text-align: left; width: 30%;">'.$values['quantity_inpacks'].'</td>
  </tr>
  <tr>
    <td style="text-align: left; width: 20%;"><b>Pack style :</b><span style="color: red;">*</span></td>
    <td style="text-align: left; width: 30%;">'.$values['pack_style'].'</td>
    <td style="text-align: left; width: 20%;"><b>Pack Size :  <span style="color: red;">*</span></b></td>
    <td style="text-align: left; width: 30%;">'.$values['pack_size'].'</td>
  </tr>
  <tr>
    <td style="text-align: left; width: 20%;"><b>Pouch specification:</b></td>
    <td style="text-align: left; width: 30%;">'.$values['pouch_required'].'</td>
    <td style="text-align: left; width: 20%;"><b>Hologram :</b></td>
    <td style="text-align: left; width: 30%;">'.$values['haologram'].'</td>
  </tr>
  <tr>
    <td style="text-align: left; width: 20%;"><b>Drum Color :</b></td>
    <td style="text-align: left; width: 30%;">'.$values['drumColor'].'</td>
    <td style="text-align: left; width: 20%;"><b>Shelf life :</b></td>
    <td style="text-align: left; width: 30%;">'.$values['shelf_life'].'</td>
  </tr>
  <tr>
    <td style="text-align: left; width: 20%;"><b>Min. Exp. Shelf life :</b></td>
    <td style="text-align: left; width: 30%;">'.$values['minShelf_life'].'</td>
    <td style="text-align: left; width: 20%;"><b>Lable Artwork:</b></td>
    <td style="text-align: left; width: 30%;">'.$values['shipper_quality'].'</td>
  </tr>
  <tr>
    <td style="text-align: left; width: 20%;"><b>Data Logger</b></td>
    <td style="text-align: left; width: 30%;">'.$values['data_logger'].'</td>
    <td style="text-align: left; width: 20%;"><b>Data Logger Provided By</b></td>
    <td style="text-align: left; width: 30%;">'.$values['data_logger_provided'].'</td>
  </tr>
  <tr>
    <td style="text-align: left; width: 20%;"><b>Data Logger Type</b></td>
    <td style="text-align: left; width: 30%;">'.$values['data_logger_type'].'</td>
    <td style="text-align: left; width: 20%;"><b>Palletisation</b></td>
    <td style="text-align: left; width: 30%;">'.$values['palletisatio'].'</td>
  </tr>
  <tr>
    <td style="text-align: left; width: 20%;"><b>Palletisation type</b></td>
    <td style="text-align: left; width: 30%;">'.$values['palletisation_type'].'</td>
    <td style="text-align: left; width: 20%;"><b>Shipping Label Instructions</b></td>
    <td style="text-align: left; width: 30%;">'.$values['shipper_label_instruction'].'</td>
  </tr>
  <tr>
    <td style="text-align: left; width: 20%;"><b>Mode of Shipment</b></td>
    <td style="text-align: left; width: 30%;">'.$values['mode_of_shipnment'].'</td>
    <td style="text-align: left; width: 20%;"><b>Container Stuffing Point</b></td>
    <td style="text-align: left; width: 30%;">'.$values['container_suffereing_point'].'</td>
  </tr>
  <tr>
    <td style="text-align: left; width: 20%;"><b>Container Type
    </b></td>
    <td style="text-align: left; width: 30%;">'.$values['container_type'].'</td>
    <td style="text-align: left; width: 20%;"><b>Temp. Required</b></td>
    <td style="text-align: left; width: 30%;">'.$values['tempRequired'].'</td>
  </tr>
  <tr>
    <td style="text-align: left; width: 20%;"><b>Eo / MOH / Custom Sample Requirement</b></td>
    <td style="text-align: left; width: 30%;">'.$values['pre_shipment_samples'].'</td>
  </tr>
           
';
                }
                $html.='
              
             </table>

  <div></div>
            <table border="1" cellpadding="2">
            <tr style="background-color:black; color:white;">
                  <td style="width: 540px;">    Terms & Conditions </td>
              </tr>';
               $json_obj = $row['terms'];
                $array = json_decode($json_obj, true);
             
                foreach ($array as $values)
                {
                  
                    $add_term = $values['additional_term'];
             $html.=' <tr>
                  <td style="width: 540px;">
                      <ul>
                          <li>' . $add_term . '</li>
                        
                      </ul>
                  </td>
                  
              </tr> ';
                }
                $html.='
              
             </table>
            
              <table border="1">';
        //   $term_heading = "";
                // $hdr_printed = false;
                $json_obj = $row['terms_conditions'];
                $array = json_decode($json_obj, true);
                $k=1;
                foreach ($array as $values)
                {
                   $term = $values['term'];
                    $term_heading = $values['term_heading'];
          $html.='

          <tr>
          <td style="width: 54px; text-align:center"> ' . $k++ . '</td>
          <td style="width: 162px;text-align:left;"> ' . $term_heading . '</td>
          <td style="width: 324px;text-align:left;"> ' . $term . '</td>
      </tr>';
                }
     $html.=' </table>';
      $html.='
     <div></div>
        ';
		$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
	   
	   //}
        }
	   
	  }
	  else if ($_GET["type"] == "FOReportlog"){$_GET['filename'] = 'PO';
$_GET['pdftype'] = 'onlyheader';  
include('../pdfimp2.php');

// SQL Query to fetch PO entries along with client details
$sql = "SELECT q.*, c.TrdNm, c.address, c.state, c.city, c.pincode, c.country 
        FROM po_entry q 
        LEFT JOIN client c ON q.client_code = c.client_code 
        WHERE q.user_no = '" . $_GET["user_no"] . "' 
          AND q.status != 'pending' 
        ORDER BY q.id DESC";

// Execute the query
$result = $conn->query($sql);

// Check if any results are returned
if ($result->num_rows > 0) {
    $html = "";
    
    
    // Add the header for the PDF
    $html .= '<table>
        <tr>
            <td style="width: 540px; text-align: center; font-size: 25px;">Received FO Log</td>
        </tr>
    </table>';

    // Start the table for displaying PO details
    $html .= '<table border="1" style="margin: 0 auto; text-align: center;">
        <thead>
            <tr style="background-color: gray; color: white;">
                <th>Sr</th>
                <th>Order No</th>
                <th>Client Name</th>
                <th>PO No</th>
                <th>PO Date</th>
                <th>Valid Till</th>
            </tr>
        </thead>
        <tbody>';

    // Initialize counter for Sr (serial number)
    $i = 1;

    // Loop through each row and populate the table
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
            <td>' . $i++ . '</td>
            <td>' . $row["order_no"] . '</td>
            <td>' . $row["TrdNm"] . '</td>
            <td>' . $row["po_no"] . '</td>
            <td>' . $row["po_date"] . '</td>
            <td>' . $row["valid_till"] . '</td>
        </tr>';
    }

    // Close the table tags
    $html .= '</tbody></table>';

    // Optionally add any additional content like a footer
    $html .= '<div></div>'; // Empty div for space if needed

    // Output the generated HTML content into the PDF
    $pdf->writeHTML($html, true, false, false, false, '');

    // Output the PDF to the browser
    $pdf->Output('', 'I');
}}

	  
	  
	  else if ($_GET["type"] == "download_personal_hygiene") {
        $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
        $html= "";
        
        $html.='
 <table border="1" cellpadding="2">
 
                    <tr>
                        <td style="  width: 420px; font-size: 8;  text-align: left; "> DOCUMENT NAME :- PERSONAL HYGIENE REPORT</td>
                        <td style="  width: 320px; font-size: 8;  "> DOCUMENT NO :- SIPL/SOP/AD/01</td>

                    </tr>

                   <tr>
                        <td style="width: 15px; font-size: 7; text-align: center; ">sr</td>
                        <td style="width: 125px; font-size: 7; text-align: center; ">NAME</td>
                        <td style="width: 33px; font-size: 7; text-align: center; ">Clothing</td>
                        <td style="width: 40px; font-size: 7; text-align: center; ">Hairs</td>
                        <td style="width: 45px; font-size: 7; text-align: center; ">Nails</td>
                        <td style="width: 35px; font-size: 7; text-align: center; ">Jewelary</td>
                        <td style="width: 35px; font-size: 7; text-align: center; ">Cuts & woden</td>
                        <td style="width: 44px; font-size: 7; text-align: center; ">Beard</td>
                        <td style="width: 34px; font-size: 7; text-align: center; ">Body Clean</td>
                        <td style="width: 29px; font-size: 7; text-align: center; ">Teeth</td>
                        <td style="width: 39px; font-size: 7; text-align: center; ">Footware</td>
                        <td style="width: 45px; font-size: 7; text-align: center; ">Mask</td>
                        <td style="width: 45px; font-size: 7; text-align: center; ">Cap</td>
                        <td style="width: 60px; font-size: 7; text-align: center; ">Gloves</td>
                        <td style="width: 45px; font-size: 7; text-align: center; ">Appron</td>
                        <td style="width: 70px; font-size: 7; text-align: center; ">Overall Remark</td>
                   </tr>

';
         
            $sql = "SELECT p.*,e.firstname,e.middlename,e.lastname FROM personal_hygiene p 
        LEFT JOIN employee e ON p.emp_id = e.emp_id where p.plant_id = '".$_GET['plant_id']."'";
           $i=1;
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row = $result1->fetch_assoc()) {

                    $html.='<tr>
                         <td style="width: 15px; font-size: 7; ">'.$i.'</td>
                        <td style="width: 125px;font-size: 7; ">'.$row["firstname"].'</td>
                        <td style="width: 33px; font-size: 7; ">'.$row["clothing"].'</td>
                        <td style="width: 40px; font-size: 7; ">'.$row["hairs"].'</td>
                        <td style="width: 45px; font-size: 7; ">'.$row["nails"].'</td>
                        <td style="width: 35px; font-size: 7; ">'.$row["jewelry"].'</td>
                        <td style="width: 35px; font-size: 7; ">'.$row["cuts_wound"].'</td>
                        <td style="width: 44px; font-size: 7; ">'.$row["beard"].'</td>
                        <td style="width: 34px; font-size: 7; ">'.$row["body_clean"].'</td>
                        <td style="width: 29px; font-size: 7; ">'.$row["teeth"].'</td>
                        <td style="width: 39px; font-size: 7; ">'.$row["footware"].'</td>
                        <td style="width: 45px; font-size: 7; ">'.$row["mask"].'</td>
                        <td style="width: 45px; font-size: 7; ">'.$row["cap"].'</td>
                        <td style="width: 60px; font-size: 7; ">'.$row["gloves"].'</td>
                        <td style="width: 45px; font-size: 7; ">'.$row["appron"].'</td>
                        <td style="width: 70px; font-size: 7; ">'.$row["overall_remark"].'</td>

                   </tr>';
                    $i++;
                }
            } 
        
        $html.='</table>
                           <div></div>
      <table border="1" cellpadding="4">

                <tr>
                    <td style="width: 246px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 246px; font-size: 9;text-align: center;"> </td>
                    <td style="width: 246px; font-size: 9;text-align: center;"> </td>

                </tr>
                <tr>
                    <td style="width: 246px; font-size: 9;text-align: center;"> HYGIENE SUPERVISOR</td>
                    <td style="width: 246px; font-size: 9;text-align: center;">QUALITY MANAGER</td>
                    <td style="width: 246px; font-size: 9;text-align: center;"> PLANT MANAGER</td>

                </tr>
         </table> ';
         
         
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Attaendence.pdf', 'I');
    }
     
	  
	  
    else if ($_GET["type"] == "approvePO"){
        $final_total = floatval($_GET["shipping_handling"]) + floatval($_GET["other_charges"]);
        $transport = $_GET["dispatch_through"];

        $sql = "UPDATE purchaseorder SET status='" . $_GET["status"] . "',remark='" . $_GET["remark"] . "', 
        transport='" . $_GET["dispatch_through"] . "',shipping_handling='" . $_GET["shipping_handling"] . "',other_charges='" . $_GET["other_charges"] . "', 
        approve_by='" . $_GET["emp_id"] . "', approve_date='" . $entry_date . "' WHERE id='" . $_GET["id"] . "'";
      
        $conn->query($sql);
        
        //$sql="update purchaseorder set final_total = (gross_total-discount)+(rounding+gst_total+shipping_handling+other_charges) WHERE id='" . $_GET["id"] . "'";
        $sql="update purchaseorder set final_total = final_total+(shipping_handling+other_charges) WHERE id='" . $_GET["id"] . "'";
        

        if ($conn->query($sql))
        {
            echo "{\"status\":\"success\"}";
        }
        else
        {
            echo "{\"status\":\"" . $conn->error . "\"}";
        }
        
        
        
        
    }
     else if ($_GET["type"] == "sendEmail")
    {
        echo "weelcome";
        $to = 'deva.ratnala1982@gmail.com';
        $subject = 'Business Proposal';
        $message = 'Hi Jane, will you marry me?'; 
        $from = 'peterparker@email.com';
         echo $to;
        // Sending email
        if(mail($to, $subject, $message)){
            echo 'Your mail has been sent successfully.';
        } else{
            echo 'Unable to send email. Please try again.';
        }
        
        
        
        
    }



    $conn->close();
    } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
} 
?>	