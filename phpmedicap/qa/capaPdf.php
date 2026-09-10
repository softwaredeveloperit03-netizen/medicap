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
    
	  if ($_GET["type"]=="downloadCAPAReport") 
	  {$_GET['pdftype']= 'onlyheader';  
include('../pdfimp2.php');
$html.=' 	 
<div>
   
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
     <tr>
<td colspan="4" style="text-align:center; font-size:160%; background-color:#f0f0f0;">CORRECTIVE ACTION AND PREVENTIVE ACTION</td>
        </tr>
        <tr>
            <td>CAPA Reference No.:</td>
            <td></td>

            <td>Target Date (TCD):</td>
            <td></td>
        </tr>
        <tr>
            <td rowspan="2">Ref. QMS Document No.:</td>
            <td rowspan="2"></td>
  
            <td>First ATCD Date:</td>
            <td></td>
       </tr>
        <tr>
            <td>Second ATCD Date:</td>
            <td></td>
        </tr>
   
        <tr>
            <td >Category :</td>
             <td colspan="3"></td>
        </tr>
  
    <tr>
            <td>CAPA Details:</td>
                <td colspan="3"></td>
        </tr>
        <tr>
            <td>Status of Corrective Action:</td>
               <td colspan="3"></td>
        </tr>
        <tr>
            <td>Status of Preventive Action:</td>
                 <td colspan="3"></td>
        </tr>
        <tr>
            <td>Root Cause Identified:</td>
           <td colspan="3"></td>
        </tr>
        <tr>
            <td>Responsible Person / Initiator (Sign / Date):</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td>Concern HOD/Designee Comments (Sign / Date):</td>
            <td colspan="3"></td>
        </tr>
    </table>

    <br>

    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td>Immediate Actions:</td>
            <td></td>
        </tr>
        <tr>
            <td>Operation suspended / Hold</td>
            <td></td>
        </tr>
        <tr>
            <td>Status labeled & segregated / Covered</td>
            <td></td>
        </tr>
        <tr>
            <td>Additional samples collected</td>
            <td></td>
        </tr>
        <tr>
            <td>Activity continued</td>
            <td></td>
        </tr>
        <tr>
            <td>Others</td>
            <td></td>
        </tr>
        <tr>
            <td>NA</td>
            <td></td>
        </tr>
        <tr>
            <td>Description of Immediate Actions:</td>
            <td></td>
        </tr>
    </table>

    <br>

    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td>Reason / Justification of First Alternate TCD:</td>
            <td></td>
        </tr>
        <tr>
            <td>Reason / Justification of Second Alternate TCD:</td>
            <td></td>
        </tr>
    </table>

    <br>

    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td>Review by QA (Sign / Date):</td>
            <td></td>
        </tr>
        <tr>
            <td>Approval of CAPA By QA/ QC Head:</td>
            <td></td>
        </tr>
    </table>

    <br>

    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td>Closure Comment on CAPA by Concerned HOD/Designee:</td>
            <td></td>
        </tr>
        <tr>
            <td>Closure Comments on CAPA by QA Head / Quality Head:</td>
            <td></td>
        </tr>
    </table>
</div>';

//$html.=' </table>';
$pdf->writeHTML($html, true, false, false, false, '');
$pdf->Output('','I');
}
	  
	      

 else if ($_GET["type"] == "FOReportlog")
 {$_GET['filename'] = 'PO';
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




    $conn->close();
    } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
} 
?>	