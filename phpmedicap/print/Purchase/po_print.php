<?php
try{
require '../../db.php';
require '../../token.php';
require '../../tcpdf/tcpdf.php'; 
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$input = json_decode(file_get_contents('php://input') , true);

$sql = "SELECT * FROM token WHERE token='" . $_GET["token"] . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if ($result->num_rows > 0)
{
    while ($row = $result->fetch_assoc())
    {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];
        $_GET["department"] = $string[1];
        break;
    }

    $txt = '{"process": "FRONTEND", "token": "' . $token . '", "action": "' . $_GET["type"] . '", "actiontime": "' . $entry_date . '", "department": "' . $_GET["department"] . '", "emp_id": "' . $_GET["emp_id"] . '", "method": "' . $_SERVER['REQUEST_METHOD'] . '", "REMOTE_ADDR": "' . $_SERVER['REMOTE_ADDR'] . '"}';
    $myfile = file_put_contents('../../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

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

    if ($_GET["type"] == "downloadPOReport")
    {
        $_GET['filename'] = 'Purchase Order';
        $_GET['pdftype'] = 'noheader';
        $_GET['pdftop'] = 8;
        $_GET['pdfy'] = 8;
        include ("../../pdfimp2.php");
        $html = "";

        $sql = "SELECT p.*, v.vendor_name, v.address, v.vendor_type, v.contact_person, v.contact_number, v.gst_no as vendor_gst,
          v.state_name, v.pincode, c.company_name, c.mobile_no, c.fax_no as bill_fax, c.area, c.gst_no, c1.gst_no as gst_no1,
          c.address as c_address, c1.company_name as company_name1, c1.mobile_no as mobile_no1, c1.fax_no as ship_fax, c1.address as address1,
          c1.area as area1, e.firstname, e1.firstname as approve_firstname, c.pin as bill_pin, c1.pin as ship_pin,
          c.present_state as b_state, c1.present_state as s_state,
          (SELECT MIN(pm.delivery_schedule_date) FROM po_material pm WHERE pm.po_no = p.id) as expected_date,
          (SELECT pm.quotation_no FROM po_material pm WHERE pm.po_no = p.id LIMIT 1) as quote_no
          FROM purchaseorder p
          LEFT JOIN vendor v ON p.vendor_no = v.vendor_no
          LEFT JOIN company c ON p.billcompany_code = c.company_code
          LEFT JOIN employee e ON p.entry_by = e.emp_id
          LEFT JOIN employee e1 ON p.approve_by = e1.emp_id
          LEFT JOIN company c1 ON p.shipcompany_code = c1.company_code
          WHERE p.id='" . $_GET["id"] . "' LIMIT 1";
 
        $material_type = '';
        $result = $conn->query($sql);
        if ($result->num_rows > 0)
        {
           
            while ($row = $result->fetch_assoc())
            {
                $currancy = isset($row['currancy']) ? $row['currancy'] : 'INR';
                $final_total = number_format((float)$row['final_total'], 2, '.', ',');
                $us_total = ($currancy == 'USD') ? $final_total : '';
                $cdn_total = ($currancy != 'USD') ? $final_total : '';
                $logo_url = 'https://aurenyxgmp.com/php/phpdevlop/gmptotal/logos/' . $logo;
                $order_date = (!empty($row['entry_date']) && $row['entry_date'] != '0000-00-00 00:00:00') ? date('m/d/Y', strtotime($row['entry_date'])) : '';
                $expected_date = (!empty($row['expected_date']) && $row['expected_date'] != '0000-00-00') ? date('m/d/Y', strtotime($row['expected_date'])) : '';
                $approve_date = (!empty($row['approve_date']) && $row['approve_date'] != '0000-00-00 00:00:00') ? date('m/d/Y', strtotime($row['approve_date'])) : '';
                $pr_no = !empty($row['indent_no']) ? $row['indent_no'] : (!empty($row['indend_no']) ? $row['indend_no'] : '');
                $quote_no = !empty($row['quote_no']) ? $row['quote_no'] : '';
                $ordered_by = trim(($row['firstname'] ? $row['firstname'] . ' ' : '') . $row['entry_by']);
                $approved_by = trim(($row['approve_firstname'] ? $row['approve_firstname'] . ' ' : '') . $row['approve_by']);
                $contact = !empty($row['contact_person']) ? $row['contact_person'] : $row['contact_number'];
                $vendor_phone = !empty($row['contact_number']) ? $row['contact_number'] : '';
                $ship_phone = !empty($row['mobile_no1']) ? $row['mobile_no1'] : $row['mobile_no'];
                $ship_fax = !empty($row['ship_fax']) ? $row['ship_fax'] : '';

                $html .= '
                <style>
                    table.po-table td { border: 1px solid #000000; font-family: helvetica, arial, sans-serif; font-size: 9px; vertical-align: top; }
                    table.po-table th { border: 1px solid #000000; font-family: helvetica, arial, sans-serif; font-size: 9px; font-weight: bold; text-align: center; }
                </style>
                <div style="text-align:center; font-family:helvetica,arial,sans-serif; font-size:11px; margin-bottom:4px;">Purchase Order (P.O.)</div>
                <div style="text-align:center; margin-bottom:4px;"><img src="' . $logo_url . '" alt="Logo" width="120" height="70" /></div>
                <div style="text-align:center; font-family:helvetica,arial,sans-serif; font-size:16px; font-weight:bold; color:#1f4e79; margin-bottom:8px;">PURCHASE ORDER</div>

                <table class="po-table" border="1" cellpadding="4" cellspacing="0">
                    <tr>
                        <td style="width:36%; height:95px;">
                            <b>Purchased From:</b><br><br>
                            ' . $row['vendor_name'] . '<br>
                            ' . nl2br($row['address']) . '<br><br>
                            Phone: ' . $vendor_phone . '<br>
                            Fax:
                        </td>
                        <td style="width:34%; height:95px;">
                            <b>Ship To:</b><br><br>
                            ' . $row['company_name1'] . '<br>
                            ' . nl2br($row['address1']) . '<br><br>
                            Phone: ' . $ship_phone . '<br>
                            Fax: ' . $ship_fax . '
                        </td>
                        <td style="width:30%; height:95px;">
                            <table border="0" cellpadding="2" cellspacing="0">
                                <tr><td style="width:38%; border:none;"><b>P.O. #:</b></td><td style="width:62%; border:none;">' . $row['po_no'] . '</td></tr>
                                <tr><td style="border:none;"><b>P.R. #:</b></td><td style="border:none;">' . $pr_no . '</td></tr>
                                <tr><td style="border:none;"><b>Quote #:</b></td><td style="border:none;">' . $quote_no . '</td></tr>
                                <tr><td style="border:none;"><b>Order Date:</b></td><td style="border:none;">' . $order_date . '</td></tr>
                                <tr><td style="border:none;"><b>Expected Date:</b></td><td style="border:none;">' . $expected_date . '</td></tr>
                                <tr><td style="border:none;"><b>Contact:</b></td><td style="border:none;">' . $contact . '</td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3"><b>Ordered By:</b> ' . $ordered_by . '</td>
                    </tr>
                </table>
                <br>
                <table class="po-table" border="1" cellpadding="4" cellspacing="0">
                    <tr>
                        <th style="width:8%;">Item #</th>
                        <th style="width:14%;">Catalog #</th>
                        <th style="width:38%;">Product</th>
                        <th style="width:10%;">Quantity</th>
                        <th style="width:15%;">Unit Price</th>
                        <th style="width:15%;">Total Price</th>
                    </tr>';

                $sql1 = "SELECT p.*, m.material_name, m.material_code as catalog_code
                         FROM po_material p
                         LEFT JOIN master_material m ON p.material_code = m.material_code
                         WHERE p.po_no='" . $row["id"] . "'";
                $result1 = $conn->query($sql1);
                $idx = 1;
                $item_rows = 0;
                if ($result1 && $result1->num_rows > 0)
                {
                    while ($row1 = $result1->fetch_assoc())
                    {
                        $catalog_no = !empty($row1['catalog_code']) ? $row1['catalog_code'] : $row1['material_code'];
                        $qty = $row1['qty'] . (!empty($row1['unit']) ? ' ' . $row1['unit'] : '');
                        $unit_price = number_format((float)$row1['quotation_amt'], 2, '.', ',');
                        $line_total = number_format((float)$row1['net_total'], 2, '.', ',');
                        $html .= '
                    <tr>
                        <td style="text-align:center;">' . $idx . '</td>
                        <td>' . $catalog_no . '</td>
                        <td>' . $row1['material_name'] . '</td>
                        <td style="text-align:center;">' . $qty . '</td>
                        <td style="text-align:right;">' . $unit_price . '</td>
                        <td style="text-align:right;">' . $line_total . '</td>
                    </tr>';
                        $idx++;
                        $item_rows++;
                    }
                }

                $min_rows = 18;
                while ($item_rows < $min_rows)
                {
                    $html .= '
                    <tr>
                        <td style="height:16px;">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>';
                    $item_rows++;
                }

                $html .= '
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td style="text-align:right; font-weight:bold;">Total Price</td>
                        <td style="text-align:center; font-weight:bold;">U.S.</td>
                        <td style="text-align:center; font-weight:bold;">CDN</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td style="text-align:right;">' . $us_total . '</td>
                        <td style="text-align:right;">' . $cdn_total . '</td>
                        <td style="text-align:right; font-weight:bold;">' . $final_total . '</td>
                    </tr>
                    <tr>
                        <td colspan="3"><b>Approved By:</b> ' . $approved_by . '</td>
                        <td colspan="3" style="text-align:right;"><b>Date:</b> ' . $approve_date . '</td>
                    </tr>
                </table>';

                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Output('Po_Report.pdf', 'I');
                
            }
        }
    }
    else if ($_GET["type"] == "approvePO")
    {
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
}
else
{
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
} 
?>
