<?php
require 'db.php';
if(!isset($_GET['user_no'])){
    $_GET['user_no'] = 'GMP024';
}
$sqlcm = "SELECT * FROM user WHERE user_no='".$_GET["user_no"]."'";
$resultcm = $conn->query($sqlcm);
$rowcm = $resultcm->fetch_assoc();
// $_GET['logo'] = $rowcm['logo'];
// $_GET['company_name'] = $rowcm['company_name'];
// $_GET['address'] = $rowcm['address'];

if(isset($_GET['pdfpagebr'])){ $_GET['pdfpagebr'] = $_GET['pdfpagebr']; }else{ $_GET['pdfpagebr'] = 30; }
if(isset($_GET['pdfy'])){ $_GET['pdfy'] = $_GET['pdfy']; }else{ $_GET['pdfy'] = 30; }
if(isset($_GET['pdfleft'])){ $_GET['pdfleft'] = $_GET['pdfleft']; }else{ $_GET['pdfleft'] = 10; }
if(isset($_GET['pdftop'])){ $_GET['pdftop'] = $_GET['pdftop']; }else{ $_GET['pdftop'] = 30; }
if(isset($_GET['pdfright'])){ $_GET['pdfright'] = $_GET['pdfright']; }else{ $_GET['pdfright'] = 10; }
if(isset($_GET['pdfbottom'])){ $_GET['pdfbottom'] = $_GET['pdfbottom']; }else{ $_GET['pdfbottom'] = 10; }
if(isset($_GET['pdffont'])){ $_GET['pdffont'] = $_GET['pdffont']; }else{ $_GET['pdffont'] = 'Times'; }
if(isset($_GET['pdffonts'])){ $_GET['pdffonts'] = $_GET['pdffonts']; }else{ $_GET['pdffonts'] = 10; }
if($_GET['pdftype'] == 'landscape'){ $_GET['pdfpage'] = 'L';  $_GET['pdfpagebr'] = '15'; }else if(!isset($_GET['pdfpage'])){  $_GET['pdfpage'] = 'P'; }


 
// $sqlemp = "SELECT * FROM employee WHERE emp_id='".$row["entry_by"]."'";
//     $resultemp = $conn->query($sqlemp);
//     $rowemp = $resultemp->fetch_assoc();
//     $_GET['emp_by'] = $row['entry_by'];
//     if($row['entry_date'] != ''){ $_GET['emp_entry'] = date('d/m/Y', strtotime($row['entry_date'])); }
//     else{ $_GET['emp_entry'] = '';}
//     $_GET['emp_name'] = $rowemp['emp_name'];
//     $_GET['emp_department'] = $rowemp['department'];
    
//     $sqlemp1 = "SELECT * FROM employee WHERE emp_id='".$row["check_by"]."'";
//     $resultemp1 = $conn->query($sqlemp1);
//     $rowemp1 = $resultemp1->fetch_assoc();
//     $_GET['emp_by1'] = $row['check_by'];
//     if($row['check_date'] != ''){ $_GET['emp_entry1'] = date('d/m/Y', strtotime($row['check_date'])); }
//     else{ $_GET['emp_entry1'] = ''; }
//     $_GET['emp_name1'] = $rowemp1['emp_name'];
//     $_GET['emp_department1'] = $rowemp1['department'];
    
//     $sqlemp2 = "SELECT * FROM employee WHERE emp_id='".$row["approve_by"]."'";
//     $resultemp2 = $conn->query($sqlemp2);
//     $rowemp2 = $resultemp2->fetch_assoc();
//     $_GET['emp_by2'] = $row['approve_by'];
//     if($row['approve_date'] != ''){ $_GET['emp_entry2'] = date('d/m/Y', strtotime($row['approve_date'])); }
//     else{ $_GET['emp_entry2'] = ''; }
//     $_GET['emp_name2'] = $rowemp2['emp_name'];
//     $_GET['emp_department2'] = $rowemp2['department'];
    
    $sqlplant = "SELECT * FROM plant WHERE plant_id='".(isset($_GET["plant_id"]) ? $_GET["plant_id"] : '').  "'";
     $resultplant = $conn->query($sqlplant);
     $rowplant = ($resultplant && $resultplant->num_rows > 0) ? $resultplant->fetch_assoc() : array();
      $plant_full_name =  isset($rowplant['plant_full_name']) ? $rowplant['plant_full_name'] : '';
      $plant_full_address =  isset($rowplant['plant_full_address']) ? $rowplant['plant_full_address'] : '';
      $panNo =  isset($rowplant['panNo']) ? $rowplant['panNo'] : '';
      $stateCode =  isset($rowplant['dlNo']) ? $rowplant['dlNo'] : '';
      $gst_no =  isset($rowplant['gst_no']) ? $rowplant['gst_no'] : '';
      $email =  isset($rowplant['email']) ? $rowplant['email'] : '';
      $logo =  isset($rowplant['logo_path']) ? $rowplant['logo_path'] : '';
     // echo json_encode($rowplant);

if($_GET['pdftype'] == 'headfoot'){
    class MYPDF extends TCPDF {
        public function Header() {
            if($_GET['user_no'] == 'GMP024'){
                $_GET['crpage'] = $this->getAliasNumPage();
                $_GET['allpage'] = $this->getAliasNbPages();
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table>
                
                    <tr>
                       <td style="width:80%;text-align:center"> '.$rowplant['plant_full_name'].'
                        </td>
                        <td style="width:20%;">';
                            //$this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/logo.png'),169,8,25);
                            $table.='
                        </td>
                    </tr>
                    <tr>
                        <td align="center">'.$_GET['filename'].'</td>
                        <td align="center">Page No<br>'.$_GET['crpage'].' of '.$_GET['allpage'].'</td>
                    </tr>
                </table>';
                $this->SetY('5'); 
                $this->writeHTML($table, true, false, false, false, '');
            }
            else{
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table>
                    <tr>
                     <td style="width:20%;">';
                        //$this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/logo.png'),169,8,25);
                            $table.='
                        </td>
                         <td style="width:80%;text-align:center"> '.json_encode($rowplant).'
                        </td>
                       
                    </tr>
                </table>';
               $this->SetY('14'); $this->writeHTML($table, true, false, false, false, '');
                // $this->SetY(34); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                $this->SetY(4); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, $_GET['filename'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                // $this->SetY(4); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            }
        }
        public function Footer(){
            $table='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="text-align:center;background-color:#DDDAD9;">
                    <td style="width:33.33%"><b>Prepared by</b></td>
                    <td style="width:33.33%"><b>Checked By</b></td>
                    <td style="width:33.33%"><b>Approved By</b></td>
                </tr>
                <tr>
                    <td style="width:10.33%">Dept.</td>
                    <td style="width:23%">'.$_GET['emp_department'].'</td>
                    <td style="width:10.33%">Dept.</td>
                    <td style="width:23%">'.$_GET['emp_department1'].'</td>
                    <td style="width:10.33%">Dept.</td>
                    <td style="width:23%">'.$_GET['emp_department2'].'</td>
                </tr>
                <tr>
                    <td style="width:10.33%">Name.</td>
                    <td style="width:23%">'.$_GET['emp_name'].'</td>
                    <td style="width:10.33%">Name.</td>
                    <td style="width:23%">'.$_GET['emp_name1'].'</td>
                    <td style="width:10.33%">Name.</td>
                    <td style="width:23%">'.$_GET['emp_name2'].'</td>
                </tr>
                <tr>
                    <td style="width:10.33%">Sign.</td>
                    <td style="width:23%">'.$_GET['emp_by'].'</td>
                    <td style="width:10.33%">Sign.</td>
                    <td style="width:23%">'.$_GET['emp_by1'].'</td>
                    <td style="width:10.33%">Sign.</td>
                    <td style="width:23%">'.$_GET['emp_by2'].'</td>
                </tr>
                <tr>
                    <td style="width:10.33%">Date.</td>
                    <td style="width:23%">'.$_GET['emp_entry'].'</td>
                    <td style="width:10.33%">Date.</td>
                    <td style="width:23%">'.$_GET['emp_entry1'].'</td>
                    <td style="width:10.33%">Date.</td>
                    <td style="width:23%">'.$_GET['emp_entry2'].'</td>
                </tr>
            </table>';
            $this->SetY(-50);
            $this->SetFont('Times', '', 10);
            $this->writeHTML($table, true, false, false, false, '');
        }
    }
    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins($_GET['pdfleft'], $_GET['pdftop'], $_GET['pdfright'], $_GET['pdfbottom']);
    $pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
    $pdf->AddPage($_GET['pdfpage']);
    $pdf->SetY($_GET['pdfy']);
    $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
    $html='<style>td { border:solid 1px BCBBBA;}</style>';
}
else if($_GET['pdftype'] == 'onlyheader'){
    class MYPDF extends TCPDF {
         protected $company;
         protected $address;
         protected $logo;
         public function setCompany($var){
            $this->company = $var;
         }
         public function setAddress($var){
            $this->address = $var;
        
         }
         public function setLogo($var){
            // $this->logo = 'https://cpplgmp/public_html/php/gmptotal/logos/'.$var;
            $this->logo = 'https://aurenyxgmp.com/php/phpdevlop/gmptotal/logos/'.$var;
         }
        public function Header(){
                // $logo_path='https://'.$_SERVER['SERVER_NAME'].'/gmptotal/logo/'.this->logo;
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="2" border="0.1">
                    <tr>
                    <td style="width:20%;text-align: center;pading:50px">
                    <img src="'.$this->logo.'" alt="" width="80" height="50"  />';
                    // $this->Image('@'.file_get_contents( $this->logo),15,6,25,10);
                            //$this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/logo.png'),100,8,39);
                              //$this->Image('@'.file_get_contents('https://dns.gmpserver.online/gmptotal/upload/User/DNS.jpg'),10,6,25,18);
                                  
                                 //$this->Image('@'.file_get_contents('https://gmpsoftwareindia.com/public_html/php/gmptotal/upload/User/gmp.png'),10,6,25,18);
                            $table.='
                             </td>
                        <td style="width:80%;text-align:center;font-weight:bold;">
                       
                            <span style="font-family:times;font-size:17px;">'. $this->company.'</span><br>
                            <span style="font-size:9px;">'. $this->address.'</span>
                         </td>
                    </tr>
                 </table>';
                $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
               //$this->SetY(34); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
               //$this->SetY(25); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, $_GET['filename'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetY(34); $this->SetFont('helvetica', '', 10); 
                //$this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.
                //$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            
            
        }
              public function Footer() {
               if($_GET["plant_id"] == 72)
               {
                       $table='
             <hr>
        <tr>
            <span style="width:100%; font-size:9px;">This report is printed by / on :- ' . $_GET["emp_id"] . ' / ' . date("d-m-Y h:i:sa") . ' / IP : ' . $_SERVER["REMOTE_ADDR"] . '</span>
        </tr>
        <br>
        <tr>
            <span style="width:100%; font-size:9px;">This Work Order is Computer Generated.</span>
        </tr>';
               }
                 if($_GET["plant_id"] == 122)
               {
                       
             $table='
             
              <table cellpadding="3" border="1">
                <tr>
                  <td style="width: 10%; text-align: center;font-family:times;font-size:11px;"></td>
                  <td style="width: 30%; text-align: center;font-family:times;font-size:11px;">Prepared By</td>
                  <td style="width: 30%; text-align: center;font-family:times;font-size:11px;">Checked By</td>
                  <td style="width: 30%; text-align: center;font-family:times;font-size:11px;">Approved By</td>
                </tr>
                <tr>
                  <td style="height: 50px; text-align: center;font-family:times;font-size:11px;">Sign. / Date </td>
                  <td style="height: 50px; text-align: center;font-family:times;font-size:11px;">'.$_GET["emp_id"].'/ '.date("d-m-Y h:i:sa").'</td>
                  <td style="height: 50px; text-align: center;font-family:times;font-size:11px;"></td>
                  <td style="height: 50px; text-align: center;font-family:times;font-size:11px;"></td>
                </tr>
              </table> ';
               }
               else{
             $table='
             <hr>
                 <span style="width:100%; font-size:9px; ">This report is printed by / on :- '.$_GET["emp_id"].' / '.date("d-m-Y h:i:sa").' / IP : '.$_SERVER["REMOTE_ADDR"].'</span>

             ';
                   
               }
             
             
        // Position at 15 mm from bottom
        $this->SetY(-15);$this->writeHTML($table, true, false, false, false, '');
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
     }
    }
    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins($_GET['pdfleft'], $_GET['pdftop'], $_GET['pdfright'],$_GET['pdfbottom'] );
    $pdf->setCompany($plant_full_name);
    $pdf->setAddress($plant_full_address);
    $pdf->setLogo($logo);
    //$pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
    $pdf->AddPage($_GET['pdfpage']);
   
    $pdf->SetY($_GET['pdfy']);
    $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
    $html='<style>td { border:solid 1px BCBBBA;}</style>';
}

else if($_GET['pdftype'] == 'onlyheader1'){
    class MYPDF extends TCPDF {
         protected $company;
         protected $address;
        
         public function setCompany($var){
            $this->company = $var;
         }
         public function setAddress($var){
            $this->address = $var;
        
         }
         public function setLogo($var){
             $this->logo = 'https://cpplgmp/public_html/php/gmptotal/logos/'.$var;
         }
        public function Header(){
                // $logo_path='https://'.$_SERVER['SERVER_NAME'].'/gmptotal/logo/'.this->logo;
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="2" border="0.1">
                    <tr>
                    <td style="width:20%;text-align: center;pading:50px">
                    <img src="'.$this->logo.'" alt="" width="180" height="50"  />';
                    // $this->Image('@'.file_get_contents( $this->logo),15,6,25,10);
                            //$this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/logo.png'),100,8,39);
                              //$this->Image('@'.file_get_contents('https://dns.gmpserver.online/gmptotal/upload/User/DNS.jpg'),10,6,25,18);
                                  
                                 //$this->Image('@'.file_get_contents('https://gmpsoftwareindia.com/public_html/php/gmptotal/upload/User/gmp.png'),10,6,25,18);
                            $table.='
                             </td>
                        <td style="width:80%;text-align:center;font-weight:bold;">
                       
                            <span style="font-family:times;font-size:17px;">'. $this->company.'</span><br>
                            <span style="font-size:9px;">'. $this->address.'</span>
                         </td>
                    </tr>
                 </table>';
                $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
               //$this->SetY(34); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
               //$this->SetY(25); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, $_GET['filename'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetY(34); $this->SetFont('helvetica', '', 10); 
                //$this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.
                //$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            
            
        }
              public function Footer() {
               if($_GET["plant_id"] == 72)
               {
                       $table='
             <hr>
        <tr>
            <span style="width:100%; font-size:9px;">This report is printed by / on :- ' . $_GET["emp_id"] . ' / ' . date("d-m-Y h:i:sa") . ' / IP : ' . $_SERVER["REMOTE_ADDR"] . '</span>
        </tr>
        <br>
        <tr>
            <span style="width:100%; font-size:9px;">This Work Order is Computer Generated.</span>
        </tr>';
               }
               else{
             $table='
           
              <style>td { border:solid 1px BCBBBA;}</style>
                     <table>
            <tr style="text-align:center;">
                <td style="width:135px ; font-size:9px;">Prepared By</td>
                <td style="width:270px ; font-size:9px;">Checked By</td>
                <td style="width:135px  ; font-size:9px;">Approved by</td>
                
            </tr>
           
            <tr style="text-align:center;">
                <td style="text-align:center; width:135px ; font-size:9px;">Officer Production</td>
                <td style="text-align:center; width:135px ; font-size:9px;">Executive Production</td>
                <td style="text-align:center; width:135px ; font-size:9px;">Head Production</td>
                <td style="text-align:center; width:135px  ; font-size:9px;">Head QA</td>
            </tr>
        
              <tr style="text-align:center;">
                <td style="text-align:center; width:135px ; font-size:9px;"></td>
                <td style="text-align:center; width:135px ; font-size:9px;"></td>
                <td style="text-align:center; width:135px ; font-size:9px;"></td>
                <td style="text-align:center; width:135px ; font-size:9px;"></td>
            </tr></table>
            <hr>';
                           
               }
             
             
        // Position at 21 mm from bottom
        $this->SetY(-21);$this->writeHTML($table, true, false, false, false, '');
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
     }
    }
    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins($_GET['pdfleft'], $_GET['pdftop'], $_GET['pdfright'],$_GET['pdfbottom'] );
    $pdf->setCompany($plant_full_name);
    $pdf->setAddress($plant_full_address);
    $pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
    $pdf->setLogo($logo);
    //$pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
    $pdf->AddPage($_GET['pdfpage']);
   
    $pdf->SetY($_GET['pdfy']);
    $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
    $html='<style>td { border:solid 1px BCBBBA;}</style>';
}
else if($_GET['pdftype'] == 'noheader'){
    class MYPDF extends TCPDF {
         protected $company;
         protected $address;
         protected $logo;
         public function setCompany($var){
            $this->company = $var;
         }
         public function setAddress($var){
            $this->address = $var;
        
         }
         public function setLogo($var){
            $this->logo = 'https://'.$_SERVER['SERVER_NAME'].'/php/gmptotal/logos/'.$var;
            
        
         }
        public function Header(){
                // $logo_path='https://'.$_SERVER['SERVER_NAME'].'/gmptotal/logo/'.this->logo;
                $table='
                 ';
                $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
               //$this->SetY(34); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
               //$this->SetY(25); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, $_GET['filename'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetY(34); $this->SetFont('helvetica', '', 10); 
                //$this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.
                //$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            
            
        }
    }
    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins($_GET['pdfleft'], $_GET['pdftop'], $_GET['pdfright'],$_GET['pdfbottom'] );
    $pdf->setCompany($plant_full_name);
    $pdf->setAddress($plant_full_address);
    $pdf->setLogo($logo);
    //$pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
    $pdf->AddPage($_GET['pdfpage']);
   
    $pdf->SetY($_GET['pdfy']);
    $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
    $html='<style>td { border:solid 1px BCBBBA;}</style>';
}
else if($_GET['pdftype'] == 'headfootdigital'){
    class MYPDF extends TCPDF {
        public function Header(){
            if($_GET['user_no'] == 'GMP007'){
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table>
                    <tr>
                        <td style="width:80%;text-align:center"> '. $rowplant['plant_full_name'].' </td>
                        
                        <td style="width:20%;">';
                             // $this->Image('@'.file_get_contents('https://dns.gmpserver.online/gmptotal/upload/User/gmpdemo1.png'),13,8,30);
                            $table.='
                        </td>
                    </tr>
                </table>';
                $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
                $this->SetY(34); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                $this->SetY(37); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, $_GET['filename'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetY(35); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
                 $pdf->setListIndentWidth(4);
  
            }
            else{
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table>
                    <tr>
                        <td style="width:80%;text-align:center;font-weight:bold;">
                            <td style="width:80%;text-align:center"> '.$row['plant_full_name'].' </td>
                        <td style="width:20%;">';
                            $this->Image('@'.file_get_contents('https://dns.gmpserver.online/gmptotal/upload/User/gmpdemo1.png'),13,8,30);
                            $table.='
                        </td>
                    </tr>
                </table>';
                $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
                $this->SetY(34); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                $this->SetY(37); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, $_GET['filename'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetY(35); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            }
        }
        public function Footer(){
            $table='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="text-align:center;background-color:#DDDAD9;">
                    <td style="width:33.33%">Prepared by</td>
                    <td style="width:33.33%">Checked By</td>
                    <td style="width:33.33%">Approved By</td>
                </tr>
                <tr>
                    <td style="width:10.33%">Dept.</td>
                    <td style="width:23%">'.$_GET['emp_department'].'</td>
                    <td style="width:10.33%">Dept.</td>
                    <td style="width:23%">'.$_GET['emp_department1'].'</td>
                    <td style="width:10.33%">Dept.</td>
                    <td style="width:23%">'.$_GET['emp_department2'].'</td>
                </tr>
                <tr>
                    <td style="width:10.33%">Name.</td>
                    <td style="width:23%">'.$_GET['emp_name'].'</td>
                    <td style="width:10.33%">Name.</td>
                    <td style="width:23%">'.$_GET['emp_name1'].'</td>
                    <td style="width:10.33%">Name.</td>
                    <td style="width:23%">'.$_GET['emp_name2'].'</td>
                </tr>
                <tr>
                    <td style="width:10.33%">Sign.</td>
                    <td style="width:23%">';
                        if($_GET['emp_name'] != ''){
                            $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/pdf1/1.png'),35,273,4,4);
                            $table.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_GET['emp_by'].'';
                        }else{
                            $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/pdf1/2.png'),35,273,4,4);
                        }
                    $table.='</td>
                    <td style="width:10.33%">Sign.</td>
                    <td style="width:23%">';
                        if($_GET['emp_name1'] != ''){
                            $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/pdf1/1.png'),95,273,4,4);
                            $table.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_GET['emp_by1'].'';
                        }else{
                            $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/pdf1/2.png'),95,273,4,4);
                        }
                    $table.='</td>
                    <td style="width:10.33%">Sign.</td>
                    <td style="width:23%">';
                        if($_GET['emp_name2'] != ''){
                            $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/pdf1/1.png'),155,273,4,4);
                            $table.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_GET['emp_by2'].'';
                        }else{
                            $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/pdf1/2.png'),155,273,4,4);
                        }
                    $table.='</td>
                </tr>
                <tr>
                    <td style="width:10.33%">Date.</td>
                    <td style="width:23%">'.$_GET['emp_entry'].'</td>
                    <td style="width:10.33%">Date.</td>
                    <td style="width:23%">'.$_GET['emp_entry1'].'</td>
                    <td style="width:10.33%">Date.</td>
                    <td style="width:23%">'.$_GET['emp_entry2'].'</td>
                </tr>
            </table>';
            $this->SetY(-50);
            $this->SetFont('Times', '', 10);
            $this->writeHTML($table, true, false, false, false, '');  
        }
    }
    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins($_GET['pdfleft'], $_GET['pdftop'], $_GET['pdfright'], $_GET['pdfbottom']);
    $pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
    $pdf->AddPage($_GET['pdfpage']);
    $pdf->SetY($_GET['pdfy']);
    $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
    $html='<style>td { border:solid 1px BCBBBA;}</style>';
}
else if($_GET['pdftype'] == 'headfootlog'){
    if($_GET['user_no'] == 'GMP007'){
        class MYPDF extends TCPDF {
            public function Header() {
                    $_GET['crpage'] = $this->getAliasNumPage();
                    $_GET['allpage'] = $this->getAliasNbPages();
                    $table='
                    <style>td { border:solid 1px BCBBBA;}</style>
                    <table cellpadding="2">
                        <tr>
                            <td style="width:80%;text-align:center;font-weight:bold;">
                                <span style="font-family:times;font-size:17px;">Cyclone Pharmaceuticals Pvt. Ltd.</span><br>
                                <span style="font-size:9px;">Location:104 Garnet Bay, Near Shereton Hotel,Behind Chandhere Complex,Viman Nagar,Pune 411014<br><br></span>
                            </td>
                            <td style="width:20%;">';
                                $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/logo.png'),169,18,20);
                                $table.='
                            </td>
                        </tr>
                        <tr>
                            <td align="center">'.$_GET['filename'].'</td>
                            <td align="center">Page No<br>'.$_GET['crpage'].' of '.$_GET['allpage'].'</td>
                        </tr>
                    </table>';
                    $this->SetY('15'); 
                    $this->writeHTML($table, true, false, false, false, '');
            }
            public function Footer(){}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins($_GET['pdfleft'], $_GET['pdftop'], $_GET['pdfright'], $_GET['pdfbottom']);
        $pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
        $pdf->AddPage($_GET['pdfpage']);
        $pdf->SetY($_GET['pdfy']);
        $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
        $html='<style>td { border:solid 1px BCBBBA;}</style>';
    } else{
        class MYPDF extends TCPDF {
            public function Header() {
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table>
                    <tr>
                        <td style="width:80%;text-align:center;font-weight:bold;">
                             <td style="width:80%;text-align:center"> '.$row['plant_full_name'].' </td>
                        <td style="width:20%;">';
                            $this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/logo.png'),169,8,25);
                            $table.='
                        </td>
                    </tr>
                </table>';
                $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
                $this->SetY(34); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
                $this->SetY(37); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, $_GET['filename'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetY(35); $this->SetFont('helvetica', '', 10); $this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            }
            public function Footer(){}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins($_GET['pdfleft'], $_GET['pdftop'], $_GET['pdfright'], $_GET['pdfbottom']);
        $pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
        $pdf->AddPage($_GET['pdfpage']);
        $pdf->SetY($_GET['pdfy']);
        $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
        $html='<style>td { border:solid 1px BCBBBA;}</style>';
    }
}
else if($_GET['pdftype'] == 'landscape') {
 if($_GET["plant_id"] == 77)
               {
    class MYPDF extends TCPDF {
         protected $company;
         protected $address;
         protected $logo;
         public function setCompany($var){
            $this->company = $var;
         }
         public function setAddress($var){
            $this->address = $var;
         }
         public function setLogo($var){
            $this->logo = 'https://'.$_SERVER['SERVER_NAME'].'/php/gmptotal/logos/'.$var;
         }
        public function Header(){
                //$logo_path='https://'.$_SERVER['SERVER_NAME'].'/gmptotal/logo/'.this->logo;
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="2" border="0.1">
                    <tr>
                    <td style="width:20%;text-align: center;pading:50px">
                    <img src="'.$this->logo.'" alt="" width="80" height="50"  />';
                    // $this->Image('@'.file_get_contents( $this->logo),15,6,25,10);
                            //$this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/logo.png'),100,8,39);
                              //$this->Image('@'.file_get_contents('https://dns.gmpserver.online/gmptotal/upload/User/DNS.jpg'),10,6,25,18);
                                  
                                 //$this->Image('@'.file_get_contents('https://gmpsoftwareindia.com/public_html/php/gmptotal/upload/User/gmp.png'),10,6,25,18);
                            $table.='
                             </td>
                        <td style="width:60%;text-align:center;font-weight:bold;">
                       
                            <span style="font-family:times;font-size:17px;">ZUMA PHARMA LLC.</span><br>
                            <span style="font-size:9px;">Parkent, Tashkent, Uzbekistan</span>
                         </td>
                           <td style="width:20%;text-align: center;pading:50px"></td>
                    </tr>
                 <tr>
                 <td style="width:20%;text-align: center;pading:50px">Format No.</td>
                  <td style="width:10%;text-align: center;pading:50px"></td>
                 <td style="width:20%;text-align: center;pading:50px">Revision No.</td>
                  <td style="width:10%;text-align: center;pading:50px"></td>
                   <td style="width:20%;text-align: center;pading:50px">Effective Date</td>
                 <td style="width:20%;text-align: center;pading:50px"></td>
                 </tr>
                 </table>';
                $this->SetY('3'); $this->writeHTML($table, true, false, false, false, '');
               //$this->SetY(34); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
               //$this->SetY(25); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, $_GET['filename'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetY(34); $this->SetFont('helvetica', '', 10); 
                //$this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.
                //$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
            public function Footer() {
             $table='
             
              <table cellpadding="3" border="1">
                <tr>
                  <td style="width: 10%; text-align: center;"></td>
                  <td style="width: 30%; text-align: center;">Prepared By</td>
                  <td style="width: 30%; text-align: center;">Checked By</td>
                  <td style="width: 30%; text-align: center;">Approved By</td>
                </tr>
                <tr>
                  <td style="height: 50px; text-align: center;">Sign. / Date </td>
                  <td style="height: 50px; text-align: center;">'.$_GET["emp_id"].'/ '.date("d-m-Y h:i:sa").'</td>
                  <td style="height: 50px; text-align: center;"></td>
                  <td style="height: 50px; text-align: center;"></td>
                </tr>
              </table> ';
             $this->SetY(-17);$this->writeHTML($table, true, false, false, false, '');
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
     }
    }
    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins($_GET['pdfleft'], $_GET['pdftop'], $_GET['pdfright'], $_GET['pdfbottom']);
    $pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
     $pdf->setCompany($plant_full_name);
    $pdf->setAddress($plant_full_address);
    $pdf->setLogo($logo);
    $pdf->AddPage($_GET['pdfpage']);
    $pdf->SetY($_GET['pdfy']);
    $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
    $html='
    <style>
         td { border:solid 1px BCBBBA;}
        .tdall { border:solid 1px BCBBBA; }
        .tdb { border-bottom:solid 1px BCBBBA; }
        .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
    </style>';
}

else {
    class MYPDF extends TCPDF {
         protected $company;
         protected $address;
         protected $logo;
         public function setCompany($var){
            $this->company = $var;
         }
         public function setAddress($var){
            $this->address = $var;
         }
         public function setLogo($var){
            $this->logo = 'https://'.$_SERVER['SERVER_NAME'].'/php/gmptotal/logos/'.$var;
         }
        public function Header(){
                //$logo_path='https://'.$_SERVER['SERVER_NAME'].'/gmptotal/logo/'.this->logo;
                $table='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="2" border="0.1">
                    <tr>
                    <td style="width:20%;text-align: center;pading:50px">
                    <img src="'.$this->logo.'" alt="" width="80" height="50"  />';
                    // $this->Image('@'.file_get_contents( $this->logo),15,6,25,10);
                            //$this->Image('@'.file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/gmptotal/upload/User/logo.png'),100,8,39);
                              //$this->Image('@'.file_get_contents('https://dns.gmpserver.online/gmptotal/upload/User/DNS.jpg'),10,6,25,18);
                                  
                                 //$this->Image('@'.file_get_contents('https://gmpsoftwareindia.com/public_html/php/gmptotal/upload/User/gmp.png'),10,6,25,18);
                            $table.='
                             </td>
                        <td style="width:80%;text-align:center;font-weight:bold;">
                       
                            <span style="font-family:times;font-size:17px;">'. $this->company.'</span><br>
                            <span style="font-size:9px;">'. $this->address.'</span>
                         </td>
                    </tr>
                 </table>';
                $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
               //$this->SetY(34); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
               //$this->SetY(25); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, $_GET['filename'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetY(34); $this->SetFont('helvetica', '', 10); 
                //$this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.
                //$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
            public function Footer() {
             $table='
             <hr>
                 <span style="width:100%; font-size:9px; ">This report is printed by / on :- '.$_GET["emp_id"].' / '.date("d-m-Y h:i:sa").' / IP : '.$_SERVER["REMOTE_ADDR"].'</span> ';
             $this->SetY(-15);$this->writeHTML($table, true, false, false, false, '');
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
     }
    }
    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins($_GET['pdfleft'], $_GET['pdftop'], $_GET['pdfright'], $_GET['pdfbottom']);
    $pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
     $pdf->setCompany($plant_full_name);
    $pdf->setAddress($plant_full_address);
    $pdf->setLogo($logo);
    $pdf->AddPage($_GET['pdfpage']);
    $pdf->SetY($_GET['pdfy']);
    $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
    $html='
    <style>
         td { border:solid 1px BCBBBA;}
        .tdall { border:solid 1px BCBBBA; }
        .tdb { border-bottom:solid 1px BCBBBA; }
        .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
    </style>';
}
    
}
else if($_GET['pdftype'] == 'nootanheader') {
    class MYPDF extends TCPDF {
         protected $company;
         protected $address;
         protected $logo;
         public function setCompany($var){
            $this->company = $var;
         }
         public function setAddress($var){
            $this->address = $var;
         }
         public function setLogo($var){
            $this->logo = 'https://'.$_SERVER['SERVER_NAME'].'/php/gmptotal/logos/'.$var;
         }
       public function Header(){
                $table='
                  <style>td { border:solid 2px BCBBBA;color:blue;}</style>
                    <table cellpadding="2" >
                <tr>
                    <td rowspan="2" style="width:15%;  text-align: center;">
                    <img src="'.$this->logo.'" alt=""  width="80" height="50" />
                    </td>
<td style="width:85%; text-align: center; height:25px;  font-family: times; font-size: 17px;"><strong>NOOTAN PHARMACEUTICALS</strong></td>
                </tr>
<tr><td style="width:85%; text-align: center; height:25px;  font-family: times; font-size: 14px;"><strong>PURCHASE ORDER</strong></td>
</tr>
               
            </table>';
                 $this->SetY('5'); $this->writeHTML($table, true, false, false, false, '');
               //$this->SetY(34); $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, '', 'T', 100, 'L');
               //$this->SetY(25); $this->SetFont('helvetica', 'B', 14); $this->Cell(0, 0, $_GET['filename'], 0, false, 'L', 0, '', 0, false, 'M', 'M');
                $this->SetY(34); $this->SetFont('helvetica', '', 10); 
                //$this->Cell(0, 0, 'Page '.$this->getAliasNumPage().' of '.
                //$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
            public function Footer() {
             $table='
             <hr>
                 <span style="width:100%; font-size:9px; ">This report is printed by / on :- '.$_GET["emp_id"].' / '.date("d-m-Y h:i:sa").' / IP : '.$_SERVER["REMOTE_ADDR"].'</span>';
        $this->SetY(-15);$this->writeHTML($table, true, false, false, false, '');
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
     }
    }
    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins($_GET['pdfleft'], $_GET['pdftop'], $_GET['pdfright'], $_GET['pdfbottom']);
    $pdf->SetAutoPageBreak(TRUE, $_GET['pdfpagebr']);
    $pdf->setCompany($plant_full_name);
    $pdf->setAddress($plant_full_address);
    $pdf->setLogo($logo);
    $pdf->AddPage($_GET['pdfpage']);
    $pdf->SetY($_GET['pdfy']);
    $pdf->SetFont ($_GET['pdffont'], '', $_GET['pdffonts'] , '', 'default', true );
    $html='
    <style>
         td { border:solid 1px BCBBBA;}
        .tdall { border:solid 1px BCBBBA; }
        .tdb { border-bottom:solid 1px BCBBBA; }
        .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
    </style>';
}

?>