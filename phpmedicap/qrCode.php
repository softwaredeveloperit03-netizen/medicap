<?php




//  ini_set('display_errors', 1);
//  error_reporting(E_ALL);



require 'db.php';
require 'token.php';
include 'barcode/phpqrcode/qrlib.php'; 
require 'tcpdf/tcpdf.php';

header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
 
 
$input = json_decode(file_get_contents('php://input'),true);
 
        $randomNumber = rand(100, 999);
        $plant_id = $_GET['plant_id'];
        $text = "https://cpplgmp.com/php/phpdevlop/gmptotal/employeeRegistration.php?type=ABCD&plant_id=$plant_id";
        $file = "barcode/$randomNumber.png";
        
        $ecc = 'H';
        $pixel_size = 20;
        $frame_size = 5;
        
        QRcode::png($text, $file, $ecc, $pixel_size, $frame_size);



        class MYPDF extends TCPDF {
            // Page header
            public function Header() {
                // Leave empty for now or customize it further.
            }
            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
            }
        }

        // Create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(10, 5, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage('p');
            
$html = '       
<table cellpadding="8" cellspacing="0" style="border:1px solid #00796b; width: 540px; margin: auto; font-family: helvetica; background-color: #f2fdfc;">
    <tr>
        <td style="font-weight: bold; font-size: 16px; color: #004d40; text-align: center; padding: 12px;">
            Scan for Employee Registration
        </td>
    </tr>
    <tr>
        <td style="text-align: center; padding: 10px;">
            <img src="'.$file.'" width="200" style="border:2px solid #00796b; padding: 5px;" alt="QR Code" />
        </td>
    </tr>
    <tr>
        <td style="text-align: center; font-size: 10px; color: #555;">
            Powered by GMP Software
        </td>
    </tr>
</table>
';

             
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('invoice.pdf', 'I');
        
          
$conn->close();
?>