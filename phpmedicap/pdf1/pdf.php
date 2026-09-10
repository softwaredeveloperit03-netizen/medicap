<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

$token = $_GET["token"];
$sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()) {
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "downloadpdf") {
    $_GET['filename'] = ''; $_GET['annexure']='Annexure1';$_GET['pdftype'] = 'headfoot'; include("../pdfimp.php");
    $html= "";
    $pdf->Bookmark('Chapter 1', 0, 0, '', 'B', array(0,64,128));
        function numberOfDecimals($value)
        {
            if ((int)$value == $value)
            {
                return 0;
            }
            else if (! is_numeric($value))
            {
                // throw new Exception('numberOfDecimals: ' . $value . ' is not a number!');
                return false;
            }
        
            return strlen($value) - strrpos($value, '.') - 1;
        }
        $value="1.2.1";
        numberOfDecimals($str);
        // print a line using Cell()
        $pdf->Cell(0, 10, 'Chapter 1', 0, 1, 'L');
        
        $pdf->AddPage();
        $pdf->Bookmark('Paragraph 1.1', 1, 0, '', '', array(128,0,0));
        $pdf->Cell(0, 10, 'Paragraph 1.1', 0, 1, 'L');
        
        // $pdf->AddPage();
        // $pdf->Bookmark('Paragraph 1.2', 1, 0, '', '', array(128,0,0));
        // $pdf->Cell(0, 10, 'Paragraph 1.2', 0, 1, 'L');
        
        // $pdf->AddPage();
        // $pdf->Bookmark('Sub-Paragraph 1.2.1', 2, 0, '', 'I', array(0,128,0));
        // $pdf->Cell(0, 10, 'Sub-Paragraph 1.2.1', 0, 1, 'L');
        //$str = "1.2.1";
        //strlen(substr(strrchr($str, "."), 1));
        if(test($value)==2){
           $pdf->AddPage();
            $pdf->Bookmark('Sub-Paragraph 1.2.1', 2, 0, '', 'I', array(0,128,0));
            $pdf->Cell(0, 10, 'Sub-Paragraph 1.2.1', 0, 1, 'L'); 
        }
        // $pdf->AddPage();
        // $pdf->Bookmark('Paragraph 1.3', 1, 0, '', '', array(128,0,0));
        // $pdf->Cell(0, 10, 'Paragraph 1.3', 0, 1, 'L');
        
        // add some pages and bookmarks
        for ($i = 2; $i < 12; $i++) {
            $pdf->AddPage();
            $pdf->Bookmark('Chapter '.$i, 0, 0, '', 'B', array(0,64,128));
            $pdf->Cell(0, 10, 'Chapter '.$i, 0, 1, 'L');
        }
        $pdf->addTOCPage();

        // write the TOC title and/or other elements on the TOC page
        $pdf->SetFont('times', 'B', 16);
        $pdf->MultiCell(0, 0, 'Table Of Content', 0, 'C', 0, 1, '', '', true, 0);
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 10);
        
        // define styles for various bookmark levels
                $bookmark_templates = array();
        
        /*
         * The key of the $bookmark_templates array represent the bookmark level (from 0 to n).
         * The following templates will be replaced with proper content:
         *     #TOC_DESCRIPTION#    this will be replaced with the bookmark description;
         *     #TOC_PAGE_NUMBER#    this will be replaced with page number.
         *
         * NOTES:
         *     If you want to align the page number on the right you have to use a monospaced font like courier, otherwise you can left align using any font type.
         *     The following is just an example, you can get various styles by combining various HTML elements.
         */
        
        // A monospaced font for the page number is mandatory to get the right alignment
        $bookmark_templates[0] = '<table border="0" cellpadding="0" cellspacing="0" style="background-color:#EEFAFF"><tr><td width="155mm"><span style="font-family:times;font-weight:bold;font-size:12pt;color:black;">#TOC_DESCRIPTION#</span></td><td width="25mm"><span style="font-family:courier;font-weight:bold;font-size:12pt;color:black;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
        $bookmark_templates[1] = '<table border="0" cellpadding="0" cellspacing="0"><tr><td width="5mm">&nbsp;</td><td width="150mm"><span style="font-family:times;font-size:11pt;color:green;">#TOC_DESCRIPTION#</span></td><td width="25mm"><span style="font-family:courier;font-weight:bold;font-size:11pt;color:green;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
        $bookmark_templates[2] = '<table border="0" cellpadding="0" cellspacing="0"><tr><td width="10mm">&nbsp;</td><td width="145mm"><span style="font-family:times;font-size:10pt;color:#666666;"><i>#TOC_DESCRIPTION#</i></span></td><td width="25mm"><span style="font-family:courier;font-weight:bold;font-size:10pt;color:#666666;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
        // add other bookmark level templates here ...
        
        // add table of content at page 1
        // (check the example n. 45 for a text-only TOC
        $pdf->addHTMLTOC(1, 'INDEX', $bookmark_templates, true, 'B', array(128,0,0));
        
        // end of TOC page
        $pdf->endTOCPage();
        
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Output('MarketComplaint.pdf', 'I');
    }
    
} else {
    echo "[]";
}
?>