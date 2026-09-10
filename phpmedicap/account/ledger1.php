<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    //require '../phpmailer/class/phpmailer.php';
    require '../PHPExcel/Classes/PHPExcel.php';
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
    if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
     $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);

    // $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    // $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
     if($_GET['type'] == 'downloadJournal'){
    $date = strtotime($_GET['month']);
        $month=date("m",$date);
        $year=date("Y",$date);
        
        $objPHPExcel = new PHPExcel();
        $style1 = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, )
        );
    	$style = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, ),
            'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '	ffbf00') )
        );
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'Hamax Pharmaceuticals' );
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'Journal Entries');
        
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'Sr.');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Date');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'Client');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', 'Particulars');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', 'Cr / Dr');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F3', 'Amount');
        $mon = explode("-", $_GET["month"]);
        $curdate=strtotime(date("d", $timestamp)."-".$mon[1]."-".$mon[0]);
        $days = cal_days_in_month(CAL_GREGORIAN,$mon[1],$mon[0]);
        $b = 'C'; 
        $counter = 1;
        for ($i =1; $i <= $days; $i++) {
            $datenum = $counter++;
            $today = date("$year-$month-$datenum", strtotime($_GET['month']));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($b++. + 3, $today);
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:'.$b. + 1);
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:'.$b. + 1)->applyFromArray($style1);
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A2:'.$b. + 2);
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A3:'.$b. + 3)->getFont()->setBold( true );
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A2:'.$b. + 2)->applyFromArray($style);
        }
        
        
        $objPHPExcel->getActiveSheet()->setTitle('Simple');
        $objPHPExcel->setActiveSheetIndex(0);
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save(__DIR__."/Journal.xls");
        
        $file = 'Journal.xls';
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        unlink('Journal.xls');
    }
//     if($_GET['type'] == 'downloadLedger') {
//         $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
//          $html= "";
          
//     		      $html.='
    		     
//         <table cellpadding="5" border="0.1">
//       <tr>
//          <td style="width:5%;text-align:center"><b>Sr No.</b></td>
//           <td style="width:20%;text-align:center"><b>Date</b></td>
//           <td style="width:20%;text-align:center"><b>Particular</b></td>
//             <td style="width:20%;text-align:center"><b>Balance	</b></td>
//              <td style="width:15%;text-align:center"><b>Entry</b></td>
//              <td style="width:20%;text-align:center"><b>Debit Amount</b></td>
//          </tr>';
         
//             $sql = "SELECT * FROM  ledger";
 
//         $result = $conn->query($sql);
//         if ($result->num_rows > 0) {
//              $i=1;
//             while ($row = $result->fetch_assoc()) {
//              $html.='  <tr>
//          <td style="width:5%;text-align:center">'.$i.'</td>
//           <td style="width:20%;text-align:center">'.$row['entry_date'].'</td>
//           <td style="width:20%;text-align:center">'.$row['particular'].'</td>
//             <td style="width:20%;text-align:center">'.$row['amount'].'</td>
//              <td style="width:15%;text-align:center">'.$row['entry_by'].'</td>
//              <td style="width:20%;text-align:center">'.$row['entry_for'].'</td>
//          </tr>';
//           $i++;
//             }
//         }
         
//          $html.='  </table>';
            
        
     
//      $pdf->writeHTML($html, true, false, false, false, '');
        
//         $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
//   }
if($_GET['type'] == 'downloadLedger') {
        
        $date = strtotime($_GET['month']);
        $month=date("m",$date);
        $year=date("Y",$date);
        
        $objPHPExcel = new PHPExcel();
        $style1 = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, )
        );
    	$style = array(
            'alignment' => array( 'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, ),
            'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '	ffbf00') )
        );
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'GMP Software Pvt Ltd' );
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'Ledger Format- '.$month.'-'.$year );
        
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'Employee Id');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Employee Name');
        $mon = explode("-", $_GET["month"]);
        $curdate=strtotime(date("d", $timestamp)."-".$mon[1]."-".$mon[0]);
        $days = cal_days_in_month(CAL_GREGORIAN,$mon[1],$mon[0]);
        $b = 'C'; 
        $counter = 1;
        for ($i =1; $i <= $days; $i++) {
            $datenum = $counter++;
            $today = date("$year-$month-$datenum", strtotime($_GET['month']));
            $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue($b++. + 3, $today);
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:'.$b. + 1);
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:'.$b. + 1)->applyFromArray($style1);
            $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A2:'.$b. + 2);
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A3:'.$b. + 3)->getFont()->setBold( true );
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A2:'.$b. + 2)->applyFromArray($style);
        }
        $ii = 4;
   $sql = "SELECT * FROM  ledger";
   $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$ii, $row["emp_id"]);
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('B'.$ii, $row["emp_name"]);
                $b1 = 'C';
                
                $temp = Array();
                $temp["emp_id"] = $row["emp_id"];
                for ($i = 1; $i <= $days; $i++) {
                    $today = $mon[0]."-".$mon[1]."-".$i;
                    $mydate=strtotime($i."-".$mon[1]."-".$mon[0]);
                    if($curdate > $mydate) {
   $sql1 = "SELECT * FROM  ledger";                        
   $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $temp[$today] = "P";
                            }
                        } else {
                            $temp[$today] = "A";
                        }
                    } else {
                        $temp[$today] = "";
                    }
                    $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue($b1++. + $ii, $temp[$today]);
                }
                $ii++;
            }
        }
                
        $objPHPExcel->getActiveSheet()->setTitle('Simple');
        $objPHPExcel->setActiveSheetIndex(0);
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save(__DIR__."/employeesalaryreport.xls");
        
        $file = 'employeesalaryreport.xls';
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        unlink('employeereport.xls');
    }
    
    
    
    }    
$conn->close();


?>