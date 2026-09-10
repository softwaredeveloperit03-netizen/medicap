<?php
set_time_limit(60);
require '../db.php';
require '../tcpdf/tcpdf.php';
require '../token.php';

$token = $_GET["token"];
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
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);
    
    if($_GET['type'] == 'specification'){
        $_GET['filename'] = 'RAW MATERIAL SPECIFICATION';
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET["specification_no"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            $_GET['type'] = 'empdetail';
            include("../pdfimp.php");
            class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'headerlandscape';
                    include("../pdfimp.php");
                }
                public function Footer() {}
            }
            $_GET['type'] = 'pdfdata';
            include("../pdfimp.php");
            while($row = $result->fetch_assoc()){
            $html.='
            <table cellpadding="5" style="text-align:left;">
                <tr>
                    <td style="width:20%"><b>Department</b></td>
                    <td style="width:30%"><b>Quality Control Department</b></td>
                    <td style="width:20%"><b>Material Code</b></td>
                    <td style="width:30%">'.$row['material_code'].'</td>
                </tr>
                <tr>
                    <td><b>Name of Material</b></td>
                    <td>'.$row['material_name'].'</td>
                    <td><b>Specification No.</b></td>
                    <td>'.$row['specification_no'].'</td>
                </tr>
                <tr>
                    <td><b>Chemical Name</b></td>
                    <td>'.$row['chemical_name'].'</td>
                    <td><b>Version No.</b></td>
                    <td>'.$row['version_no'].'</td>
                </tr>
                <tr>
                    <td><b>Reference</b></td>
                    <td>'.$row['reference'].'</td>
                    <td><b>Supersede No</b></td>
                    <td>'.$row['supersede_no'].'</td>
                </tr>
                <tr>
                    <td><b>Sample Qty.</b></td>
                    <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                    <td><b>Shelf Life</b></td>
                    <td>'.$row['shelf_life'].'</td>
                </tr>
                <tr>
                    <td><b>Effective Date</b></td>
                    <td>'.$row['entry_date'].'</td>
                    <td><b>Specification Type</b></td>
                    <td>'.$row['spec_type'].'</td>
                </tr>
                <tr>
                    <td><b>Review Date</b></td>
                    <td></td>
                    <td><b>Storage</b></td>
                    <td>'.$row['storage'].'</td>
                </tr>
                <tr>
                    <td><b>Safety Precaution</b></td>
                    <td style="width:80%;">'.$row['safety_precaution'].'</td>
                </tr>
            </table>
            <div></div>
            <p style="text-align:center;"><b>Raw Material Specification</b></p>
            <div></div>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;font-weight:bold;">
                    <td style="width:10%;">Sr No</td>
                    <td style="width:30%;">Test</td>
                    <td style="width:60%;">Specification</td>
                </tr>';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$_GET["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if($result1->num_rows > 0){
                    $counter = 1;
                    while($row1 = $result1->fetch_assoc()){
                        $html.='
                        <tr>
                            <td>'.$counter++.'</td>
                            <td>'.$row1['test'].'</td>
                            <td>'.$row1['description'].'</td>
                        </tr>';
                    }
                }
                $html.='
            </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('specification.pdf', 'I');
        } else {
            echo "{\"status\":\"invalid\"}";
        }
    }
    
    else if($_GET['type'] == 'rawSpecificationReport') {
            $_GET['type'] = 'empdetail';
            include("../pdfimp.php");
            class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'headerlandscape';
                    include("../pdfimp.php");
                }
                public function Footer() {}
            }
            $_GET['type'] = 'pdfdata';
            include("../pdfimp.php");
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;text-align:center;">
                    <td><b>Raw Material Specification Report</b></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold;">
                        <td style="width:10%;">Spec No.</td>
                        <td style="width:15%;">Material Type</td>
                        <td style="width:20%;">Material Name</td>
                        <td style="width:10%;">Grade</td>
                        <td style="width:15%;">Sample Qty</td>
                        <td style="width:15%;">Version No</td>
                        <td style="width:15%;">Supersed No.</td>
                    </tr>
                </thead>
                <tbody>';
                $output = Array();
                if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
                    $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
                } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
                    $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
                } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
                    $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."'";
                }else{
                    $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate'";
                }
                $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                                <tr nobr="true">
                                    <td style="width:10%;">'.$row['specification_no'].'</td>
                                    <td style="width:15%;">'.$row1["material_subtype"].'</td>
                                    <td style="width:20%;">'.$row1["material_name"].'</td>
                                    <td style="width:10%;">'.$row1['grade'].'</td>
                                    <td style="width:15%;">'.$row['sample_qty'].' '.$row['unit'].'</td>
                                    <td style="width:15%;">'.$row['version_no'].'</td>
                                    <td style="width:15%;">'.$row['supersede_no'].'</td>
                                </tr>
                            ';
                        }
                    }
                }
            }
            $conn->close();
            $html.='</tbody></table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        
    }
    else if($_GET['type'] == 'rawSpecification'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("../pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("../pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("../pdfimp.php");
                    }
                }
                $_GET['type'] = 'pdfdata';
                include("../pdfimp.php");
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td>Raw Material Specification</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'rawSpecificationdigital'){
        $_GET['type'] = 'empdetail';
        include("../pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'header';
                include("../pdfimp.php");
            }
            public function Footer() {
                $_GET['type'] = 'footerdigital';
                include("../pdfimp.php");
            }
        }
        $_GET['type'] = 'pdfdata';
        include("../pdfimp.php");
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td>Raw Material Specification</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Type :</b> '.$row["material_subtype"].'</td>
                    </tr>
                    <tr>
                        <td><b>Material Name :</b> '.$row["material_name"].'</td>
                        <td><b>Specification No. :</b> '.$_GET['specification_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Grade :</b> '.$row['grade'].'</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <dv></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
    
    else if($_GET['type'] == 'rawHistoryReport'){
        $_GET['type'] = 'empdetail';
        include("../pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("../pdfimp.php");
            }
            public function Footer() {}
        }
        $_GET['type'] = 'pdfdata';
        include("../pdfimp.php");
        $output = Array();
        if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
        } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
        } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."'";
        }else{
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status !='obsolate'";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9; text-align:center;"><td><b>Raw Material Revision History</b></td></tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td style="width:5%;">Sr No</td>
                    <td style="width:15%;">Spec No.</td>
                    <td style="width:15%;">Material Type</td>
                    <td style="width:25%;">Material Name</td>
                    <td style="width:10%;">Grade</td>
                    <td style="width:10%;">Sample Qty</td>
                    <td style="width:10%;">Version No</td>
                    <td style="width:10%;">Supersed No.</td>
                </tr>';
                $counter = 1;
            while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                    <tr nobr="true">
                        <td>'.$counter++.'</td>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row["material_subtype"].'</td>
                        <td>'.$row["material_name"].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'rawHistory'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("../pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("../pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("../pdfimp.php");
                    }
                }
                $_GET['type'] = 'pdfdata';
                include("../pdfimp.php");
                        
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;"><td>Raw Material Revision History</td></tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'rawHistorydigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("../pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("../pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("../pdfimp.php");
                    }
                }
                $_GET['type'] = 'pdfdata';
                include("../pdfimp.php");
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;"><td>Raw Material Revision History</td></tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'rawObsolateReport'){
        $_GET['type'] = 'empdetail';
        include("../pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("../pdfimp.php");
            }
            public function Footer() {}
        }
        $_GET['type'] = 'pdfdata';
        include("../pdfimp.php");
                
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;text-align:center;font-weight:bold;">
                    <td>Raw Material Obsolate Specification</td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td style="width:5%;">Sr No</td>
                    <td style="width:15%;">Spec No.</td>
                    <td style="width:15%;">Material Type</td>
                    <td style="width:25%;">Material Name</td>
                    <td style="width:10%;">Grade</td>
                    <td style="width:10%;">Sample Qty</td>
                    <td style="width:10%;">Version No</td>
                    <td style="width:10%;">Supersed No.</td>
                </tr>';
                $counter = 1;
                while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$counter++.'</td>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['material_subtype'].'</td>
                        <td>'.$row['material_name'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'rawObsolate'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("../pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("../pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("../pdfimp.php");
                    }
                }
                $_GET['type'] = 'pdfdata';
                include("../pdfimp.php");
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;text-align:center;font-weight:bold;">
                        <td>Raw Material Obsolate Specification</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Grade :</b> '.$row['grade'].'</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>Raw Material Specification</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'rawObsolatedigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("../pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("../pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("../pdfimp.php");
                    }
                }
                $_GET['type'] = 'pdfdata';
                include("../pdfimp.php");
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;text-align:center;font-weight:bold;">
                        <td>Raw Material Obsolate Specification</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Grade :</b> '.$row['grade'].'</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>Raw Material Specification</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'packingSpecificationReport'){
        $_GET['type'] = 'empdetail';
        include("../pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("../pdfimp.php");
            }
            public function Footer() {}
        }
        $_GET['type'] = 'pdfdata';
        include("../pdfimp.php");
        
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND status!='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;text-align:center;">
                    <td><b>Packing Material Specification Report</b></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold">
                        <td>Spec No.</td>
                        <td>Material Type</td>
                        <td>Material Name</td>
                        <td>Reference</td>
                        <td>Sample Qty</td>
                        <td>Version No</td>
                        <td>Supersed No.</td>
                    </tr>
                </thead>
                <tbody>';
            while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                    <tr nobr="true">
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['material_subtype'].'</td>
                        <td>'.$row['material_name'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</tbody></table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'packingSpecification'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("../pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("../pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("../pdfimp.php");
                    }
                }
                $_GET['type'] = 'pdfdata';
                include("../pdfimp.php");
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;text-align:center;">
                        <td><b>Packing Material Specification</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Type :</b> '.$row["material_subtype"].'</td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> '.$row["material_name"].'</td>
                        <td><b>Specification No. :</b> '.$_GET['specification_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>Finish Product Specification</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'packingSpecificationdigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;text-align:center;">
                        <td><b>Packing Material Specification</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>material Type :</b>'.$row['material_type'].'</td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> '.$row['material_name'].'</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>Finish Product Specification</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'packingHistoryReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND status!='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;text-align:center;font-weight:bold;"><td>Packing Material Specification Report</td></tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Material Type</td>
                    <td>Material Name</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['material_subtype'].'</td>
                        <td>'.$row['material_name'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'packingHistory'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;text-align:center;">
                        <td><b>Packing Material Specification</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Type :</b> '.$row["material_subtype"].'</td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> '.$row["material_name"].'</td>
                        <td><b>Specification No. :</b> '.$_GET['specification_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'packingHistorydigital'){
                $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;text-align:center;">
                        <td><b>Packing Material Specification</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Type :</b> '.$row["material_subtype"].'</td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> '.$row["material_name"].'</td>
                        <td><b>Specification No. :</b> '.$_GET['specification_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'packingObsolateReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;text-align:center;"><td>Packing Material Obsolate Specification</td></tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Material type</td>
                    <td>Material Name</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['material_type'].'</td>
                        <td>'.$row['material_name'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
    else if($_GET['type'] == 'packingObsolate'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;text-align:center;">
                        <td><b>Packing Material Obsolate Specification</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Type :</b> '.$row["material_subtype"].'</td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> '.$row["material_name"].'</td>
                        <td><b>Specification No. :</b> '.$_GET['specification_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'packingObsolatedigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;text-align:center;">
                        <td><b>Packing Material Obsolate Specification</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Type :</b> '.$row["material_subtype"].'</td>
                    </tr>
                    <tr>
                        <td><b>Name of Material :</b> '.$row["material_name"].'</td>
                        <td><b>Specification No. :</b> '.$_GET['specification_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'finishSpecificationReport'){
        $_GET['type'] = 'empdetail';
        include("../pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['pagetype'] = 'headerlandscape';
                include("../pdfimp.php");
            }
            public function Footer() {}
        }
        $_GET['pagetype'] = 'pdfdata';
        include("../pdfimp.php");

        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status!='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;text-align:center;">
                    <td><b>Finish Product Specification Report</b></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold">
                        <td>Spec No.</td>
                        <td>Product Name</td>
                        <td>Product Code</td>
                        <td>Reference</td>
                        <td>Sample Qty</td>
                        <td>Version No</td>
                        <td>Supersed No.</td>
                    </tr>
                </thead>
                <tbody>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr nobr="true">
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</tbody></table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'finishSpecification'){
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;"><td>Finish Product Specification</td></tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'finishSpecificationdigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;"><td>Finish Product Specification</td></tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'finishHistoryReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'finishHistory'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;"><td>Finish Product Specification</td></tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'finishHistorydigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;"><td>Finish Product Specification</td></tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'finishObsolatereport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'finishObsolate'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;"><td>Finish Product Specification</td></tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'finishObsolatedigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;"><td>Finish Product Specification</td></tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
////  Inprocess product
    else if($_GET['type'] == 'inprocessSpecificationReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND status!='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;text-align:center;">
                    <td><b>Inprocess Specification Report</b></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold">
                        <td>Spec No.</td>
                        <td>Product Name</td>
                        <td>Product Code</td>
                        <td>Reference</td>
                        <td>Sample Qty</td>
                        <td>Version No</td>
                        <td>Supersed No.</td>
                    </tr>
                </thead>
                <tbody>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr nobr="true">
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</tbody></table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'inprocessSpecification'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;"><td>Inprocess Specification</td></tr>
                </table>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'inprocessSpecificationdigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 55);
                $pdf->AddPage('P');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;"><td>Inprocess Specification</td></tr>
                </table>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $j =1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                            $result2 = $conn->query($sql2);
                            $output2 = Array();
                            if ($result2->num_rows > 0) {
                                while ($row2 = $result2->fetch_assoc()) {
                                
                                }
                            }
                            $html.='
                            <tr>
                                <td>'.$j++.'.</td>
                                <td>'.$row1["test"].'</td>
                                <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'inprocessHistoryReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;taxt-align:center;font-weight:bold;">
                    <td>Inprocess Revision History</td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'inprocessHistory'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;"><td>Inprocess Specification</td></tr>
                </table>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'inprocessHistorydigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;"><td>Inprocess Specification</td></tr>
                </table>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'inprocessObsolateReport'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $_GET['type'] = 'empdetail';
            include("pdfimp.php");
            class MYPDF extends TCPDF {
                public function Header() {
                    $_GET['type'] = 'headerlandscape';
                    include("pdfimp.php");
                }
                public function Footer() {
                    
                }
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(15, 45, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }
            $pdf->AddPage('L', 'A4');
            $pdf->SetY(45);
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
    else if($_GET['type'] == 'inprocessObsolate'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'inprocessObsolatedigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;"><td>Inprocess Specification</td></tr>
                </table>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
////  Retest product
    else if($_GET['type'] == 'retestSpecificationReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage('L');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;text-align:center;">
                    <td><b>Retest Specification Report</b></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;">
                        <td style="width:5%;">Sr No.</td>
                        <td style="width:15%;">Spec No.</td>
                        <td style="width:15%;">Material Type</td>
                        <td style="width:25%;">Material Name</td>
                        <td style="width:10%;">Grade</td>
                        <td style="width:10%;">Sample Qty</td>
                        <td style="width:10%;">Version No</td>
                        <td style="width:10%;">Supersed No.</td>
                    </tr>
                </thead>
                <tbody>';
        $output = Array();
        if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
        } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status!='obsolate' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
        } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status!='obsolate' AND material_code='".$_GET['material_code']."'";
        }else{
            $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status !='obsolate'";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $counter =1;
            while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                    <tr nobr="true">
                        <td style="width:5%;">'.$counter++.'</td>
                        <td style="width:15%;">'.$row['specification_no'].'</td>
                        <td style="width:15%;">'.$row['material_subtype'].'</td>
                        <td style="width:25%;">'.$row['material_name'].'</td>
                        <td style="width:10%;">'.$row['grade'].'</td>
                        <td style="width:10%;">'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td style="width:10%;">'.$row['version_no'].'</td>
                        <td style="width:10%;">'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
        }
        $html.='</tbody></table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Specification.pdf', 'I');
    }
    else if($_GET['type'] == 'retestSpecification'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;text-align:center;">
                        <td><b>Retest Specification Report</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Type :</b> '.$row["material_subtype"].'</td>
                    </tr>
                    <tr>
                        <td><b>Material Name :</b> '.$row["material_name"].'</td>
                        <td><b>Specification No. :</b>'.$_GET['specification_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>Finish Product Specification</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'retestSpecificationdigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footerdigital';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 55);
                $pdf->AddPage('P');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9;text-align:center;">
                        <td><b>Retest Specification Report</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Type :</b>'.$row["material_subtype"].'</td>
                    </tr>
                    <tr>
                        <td><b>Material Name :</b> '.$row["material_name"].'</td>
                        <td><b>Specification No. :</b>'.$_GET['specification_no'].'</td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>Finish Product Specification</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'retestHistoryReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'retestHistory'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'retestHistorydigital'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'header';
                include("pdfimp.php");
            }
            public function Footer() {
                $_GET['type'] = 'footerdigital';
                include("pdfimp.php");
            }
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage('L');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Material type</td>
                    <td>Material Name</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['material_subtype'].'</td>
                        <td>'.$row['material_name'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'retestObsolateReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                    <td>Retest Obsolate Specification Report</td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Material Type</td>
                    <td>Merial Name</td>
                    <td>Grade</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'retestObsolate'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5"> 
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td>Retest Obsolate Specification</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>Finish Product Specification</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'retestObsolatedigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>Finish Product Specification</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    
//// Stability product
    else if($_GET['type'] == 'stabilitySpecificationReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND status!='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;text-align:center;">
                    <td><b>Stability Specification Report</b></td>
                </tr>
            </table>
            <div></div>
            <table cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold">
                        <td>Spec No.</td>
                        <td>Product Name</td>
                        <td>Product Code</td>
                        <td>Reference</td>
                        <td>Sample Qty</td>
                        <td>Version No</td>
                        <td>Supersed No.</td>
                    </tr>
                </thead>
                <tbody>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr nobr="true">
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</tbody></table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'stabilitySpecification'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5"cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td>Stability Specification</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'stabilitySpecificationdigital'){
        $output = Array();
        $sql = "SELECT * FROM specification WHERE specification_no='".$_GET['specification_no']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['type'] = 'header';
                        include("pdfimp.php");
                    }
                    public function Footer() {
                        $_GET['type'] = 'footer';
                        include("pdfimp.php");
                    }
                }
                $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 45, 15, 15);
                $pdf->SetAutoPageBreak(TRUE, 52);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                    require_once(dirname(__FILE__).'/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage('P', 'A4');
                $pdf->SetY(45);
                $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5"cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td>Stability Specification</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Product Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Name of Product :</b> AEROSIL</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Reference :</b> BP</td>
                        <td><b>Supersede No :</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. :</b></td>
                        <td><b>Shelf Life :</b></td>
                    </tr>
                    <tr>
                        <td><b>Effective Date :</b></td>
                        <td><b>Specification Type :</b></td>
                    </tr>
                    <tr>
                        <td><b>Review Date :</b></td>
                        <td><b>Storage:</b> Store in a tightly closed container between 28ºC to 32ºC.</td>
                    </tr>
                    <tr>
                        <td><b>Safety Precaution :</b></td>
                        <td>Do not ingest. Do not breathe dust. If ingested, seek medical advice immediately and show the container or the label.</td>
                    </tr>
                </table>
                <p style="text-align:center;"><b>Finish Product Specification</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td style="width:10%;">Sr. No</td>
                        <td style="width:40%;">Test</td>
                        <td style="width:50%;">Specification</td>
                    </tr>
                    ';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $j =1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT * FROM spec_tests WHERE test='".$row1["test"]."' AND subtest!='' AND specification_no='".$row1["specification_no"]."'";
                        $result2 = $conn->query($sql2);
                        $output2 = Array();
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                            
                            }
                        }
                        $html.='
                        <tr>
                            <td>'.$j++.'.</td>
                            <td>'.$row1["test"].'</td>
                            <td>White or almost white, light, fine, amorphous powder, with a particle size of about 15 nm.</td>
                        </tr>';
                    }
                }
                $html.='
                </table>
                <p style="text-align:center;"><b>REVISION HISTORY</b></p>
                <table cellpadding="5">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td>Specification No.</td>
                        <td>Version No.</td>
                        <td>Change Made</td>
                        <td>Reasons for change</td>
                    </tr>';
                    $sql1 = "SELECT * FROM spec_revision WHERE spec_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    $output1 = Array();
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr nobr="true">
                                <td>'.$row["specification_no"].'</td>
                                <td>'.$row1["version_no"].'</td>
                                <td>'.$row1["change_mode"].'</td>
                                <td>'.$row["specification_no"].'</td>
                            </tr>';
                        }
                    }
                    $html.='
                </table>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'stabilityHistoryReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'stabilityHistory'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'stabilityHistorydigital'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
    else if($_GET['type'] == 'stabilityObsolateReport'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'stabilityObsolate'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'stabilityObsolatedigital'){
        $_GET['type'] = 'empdetail';
        include("pdfimp.php");
        class MYPDF extends TCPDF {
            public function Header() {
                $_GET['type'] = 'headerlandscape';
                include("pdfimp.php");
            }
            public function Footer() {}
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 45, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 52);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage('L', 'A4');
        $pdf->SetY(45);
        $pdf->SetFont ('Times', '', '11' , '', 'default', true );
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND status='obsolate'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5">
                <tr style="background-color:#DDDAD9;">
                    <td>Spec No.</td>
                    <td>Product Name</td>
                    <td>Product Code</td>
                    <td>Reference</td>
                    <td>Sample Qty</td>
                    <td>Version No</td>
                    <td>Supersed No.</td>
                </tr>';
            while($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["product_name"] = $row2["product_name"];
                        $row["generic_name"] = $row2["generic_name"];
                        $row["grade"] = $row2["grade"];
                    }
                }
                $html.='
                    <tr>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['product_name'].'</td>
                        <td>'.$row['product_code'].'</td>
                        <td>'.$row['grade'].'</td>
                        <td>'.$row['sample_qty'].' '.$row['unit'].'</td>
                        <td>'.$row['version_no'].'</td>
                        <td>'.$row['supersede_no'].'</td>
                    </tr>
                ';
            }
            $html.='</table>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Specification.pdf', 'I');
        }
    }
    
}else{
    echo "Invalid Token";
}
?>