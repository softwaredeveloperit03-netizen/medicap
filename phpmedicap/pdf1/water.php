<?php 
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';


// error_reporting(E_ALL);
// ini_set('display_errors', 1);



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

   
    
    }
     else if ($_GET["type"] == "specificationReport") {
        $_GET['filename'] = 'A.R.Report'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%;">A. R. No.</td>
                    <td style="width: 10%;"> Sampling No</td>
                    <td style="width: 20%;">Specification No</td>
                    <td style="width: 20%;">Material Name</td>
                    <td style="width: 10%;">Material Code</td>
                    <td style="width: 15%;">Material Grade</td>
                    <td style="width: 15%;">view</td>
                </tr>
            </thead>';
              $output = Array();
               echo $sql = "SELECT * FROM testing_tests ORDER BY name";
                $result = $conn->query($sql);
                $i=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 10%; ">'.$i.'.</td>
                        <td style="width: 10%; ">'.$row['sampling_no'].'</td>
                        <td style="width: 20%; ">'.$row['specification_no'].'</td>
                        <td style="width: 20%; ">'.$row['material_name'].'</td>
                        <td style="width: 10%; ">'.$row['material_code'].'</td>
                        <td style="width: 15%;">'.$row['material Grade'].'</td>
                        <td style="width: 15%;">'.$row['view'].'</td>
                        
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Glasswares Log.pdf', 'I');
}


    else if($_GET['type'] == 'specification'){
        $output = Array();
        $sql = "SELECT * FROM water_specification WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("../pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['headertype'] = 'header';
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
                        <td>Water Specification</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Water Type :</b> '.$row['water_type'].'</td>
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
                        <td><b>Sample Qty. :</b> '.$row['sample_qty'].'</td>
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
    
    else if($_GET['type'] == 'getspecificationReport'){
        $output = Array();
        $sql = "SELECT * FROM water_specification WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $_GET['type'] = 'empdetail';
                include("../pdfimp.php");
                class MYPDF extends TCPDF {
                    public function Header() {
                        $_GET['headertype'] = 'header';
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
                    <tr style="background-color:#DDDAD9; text-align:center;font-weight:bold;">
                        <td>Water Specification</td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td><b>Department :</b> Quality Control</td>
                        <td><b>Material Code :</b></td>
                    </tr>
                    <tr>
                        <td><b>Water Type :</b> '.$row['water_type'].'</td>
                        <td><b>Specification No. :</b></td>
                    </tr>
                    <tr>
                        <td><b>Chemical Name :</b> COLLOIDAL SILICON DIOXIDE</td>
                        <td><b>Version No.</b></td>
                    </tr>
                    <tr>
                        <td><b>Grade :</b> '.$row['grade'].'</td>
                        <td><b>Supersede No : '.$row['supersed_no'].'</b></td>
                    </tr>
                    <tr>
                        <td><b>Sample Qty. : '.$row['sample_qty'].'</b></td>
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

else{
    echo "Invalid Token";
}
?>