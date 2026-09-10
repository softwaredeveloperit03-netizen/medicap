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
    while($row = $result->fetch_assoc()){
    	$string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
    	$string = explode("$",$string);
    	$_GET["emp_id"] = $string[0];
    	$_GET["department"] = $string[1];
    	break;
    }
    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);

    if($_GET['type'] == 'coa'){
        $sql = "SELECT * FROM testing WHERE testing_no='".$_GET["testing_no"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $_GET['filename'] = 'CERTIFICATE OF ANALYSIS'; $_GET['pdftype']='headfoot'; include("../pdfimp.php");
                
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                $html.= '
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5" style="text-align:left;">
                    <tr>
                        <td rowspan="2" style="width:65%">Product: '.$row2['material_name'].'</td>
                        <td style="width:35%">Medicap lot no 2016-17/QCP/FGR/00377</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Completion Dt.</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">20/08/2016</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Analysis Date</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">20/08/2016</td>
                        <td style="width:15%">Mfg. Date</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">20/08/2016</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Batch</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">ATP003</td>
                        <td style="width:15%">Exp. Date</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">Jul,2018</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Batch Size</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">10</td>
                        <td style="width:15%">Sampled Date</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">20/08/2016</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Sample Type</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">Finished Product</td>
                        <td style="width:15%">Sample Qty.</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">300.000 Nos</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Mfg. Lic. No.</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">KD-2503-A</td>
                        <td style="width:15%">Sampled By</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">vipul</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Spec. No. :</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">FPS/FG092/01/12</td>
                        <td style="width:35%"><b>Sample Approved</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td style="width:10%;">Sr No.</td>
                        <td style="width:30%;">Test</td>
                        <td style="width:30%;">Specification</td>
                        <td style="width:30%;">Observation</td>
                    </tr>';
                    $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                    $result1 = $conn->query($sql1);
                    if($result1->num_rows > 0){
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='<tr>
                                <td>'.$counter++.'</td>
                                <td>'.$row1['test'].'</td>
                                <td>'.$row1['description'].'</td>
                                <td>'.$row1['result'].'</td>
                            </tr>';
                        }
                    }
            $html.='
            </table>
            <div></div>
            <table>
                <tr>
                    <td style="border:none; width:13%;">Conclusion :</td>
                    <td style="border:none; width:87%;">The conclusion of the undersigned about the above mentioned product Complies as per In House Specification Laid down specification and is of a standard quality mentioned there-in and released for Packing / Distribution.</td>
                </tr>
            </table>
            <div></div>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('coa.pdf', 'I');
        }else{
            echo "Invalid Testing No.";
        }
    }
    else if($_GET['type'] == 'coadigital'){
        $_GET['filename'] = 'CERTIFICATE OF ANALYSIS';
        $sql = "SELECT * FROM testing WHERE testing_no='".$_GET["testing_no"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
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
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                $html.= '
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5" style="text-align:left;">
                    <tr>
                        <td rowspan="2" style="width:65%">Product: '.$row2['material_name'].'</td>
                        <td style="width:35%">Medicap lot no 2016-17/QCP/FGR/00377</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Completion Dt.</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">20/08/2016</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Analysis Date</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">20/08/2016</td>
                        <td style="width:15%">Mfg. Date</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">20/08/2016</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Batch</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">ATP003</td>
                        <td style="width:15%">Exp. Date</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">Jul,2018</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Batch Size</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">10</td>
                        <td style="width:15%">Sampled Date</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">20/08/2016</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Sample Type</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">Finished Product</td>
                        <td style="width:15%">Sample Qty.</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">300.000 Nos</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Mfg. Lic. No.</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">KD-2503-A</td>
                        <td style="width:15%">Sampled By</td>
                        <td style="width:3%;">:</td>
                        <td style="width:17%">vipul</td>
                    </tr>
                    <tr>
                        <td style="width:15%">Spec. No. :</td>
                        <td style="width:3%;">:</td>
                        <td style="width:47%">FPS/FG092/01/12</td>
                        <td style="width:35%"><b>Sample Approved</b></td>
                    </tr>
                </table>
                <div></div>
                <table cellpadding="5">
                    <tr>
                        <td style="width:10%;">Sr No.</td>
                        <td style="width:30%;">Test</td>
                        <td style="width:30%;">Specification</td>
                        <td style="width:30%;">Observation</td>
                    </tr>';
                    $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                    $result1 = $conn->query($sql1);
                    if($result1->num_rows > 0){
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $html.='<tr>
                                <td>'.$counter++.'</td>
                                <td>'.$row1['test'].'</td>
                                <td>'.$row1['description'].'</td>
                                <td>'.$row1['result'].'</td>
                            </tr>';
                        }
                    }
            $html.='
            </table>
            <div></div>
            <table>
                <tr>
                    <td style="border:none; width:13%;">Conclusion :</td>
                    <td style="border:none; width:87%;">The conclusion of the undersigned about the above mentioned product Complies as per In House Specification Laid down specification and is of a standard quality mentioned there-in and released for Packing / Distribution.</td>
                </tr>
            </table>
            <div></div>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('coa.pdf', 'I');
        }else{
            echo "Invalid Testing No.";
        }
    }
    else if ($_GET["type"] == "capalog") {
        $_GET['filename'] = 'Volumetric Master '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    
                   
                    <td style="width: 25%; ">Solution Name</td>
                    <td style="width: 25%; ">Percentage</td>
                    <td style="width: 25%; ">Strength</td>
                    <td style="width: 25%;">Standard Type</td>
                </tr>
            </thead>';
             $output = Array();
            $sql = "SELECT * FROM volumetric_solution ORDER BY solution_name";
            $result = $conn->query($sql);
            $i=1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                $html.='<tr nobr="true">
                        
                       
                        <td style="width: 25%; ">'.$row['solution_name'].'</td>
                        <td style="width: 25%; ">'.$row['percentage'].'</td>
                        <td style="width: 25%; ">'.$row['strength'].'</td>
                        <td style="width: 25%; ">'.$row['standard_type'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Volumetric Master .pdf', 'I');
    }
    else if($_GET['type'] == 'coalog'){
        $_GET['pdfpage'] = 'L'; $_GET['filename'] = 'CERTIFICATE OF ANALYSIS LOG';
        $sql = "SELECT * FROM testing";
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
            $_GET['type'] = 'headerlandscape';
            include("../pdfimp.php");
            $html.='
            <style>td { border:solid 1px BCBBBA;}</style>
            <table cellpadding="5" style="text-align:left;">
                <tr>
                    <td style="width:15%">Testing No.</td>
                    <td style="width:15%">Sampling No.</td>
                    <td style="width:15%">Specification No</td>
                    <td style="width:25%">Material Name</td>
                    <td style="width:15%">Material Code</td>
                    <td style="width:15%">Status</td>
                </tr>';
                while($row = $result->fetch_assoc()){
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                $html.='
                <tr>
                    <td style="width:15%">'.$row['testing_no'].'</td>
                    <td style="width:15%">'.$row['sampling_no'].'</td>
                    <td style="width:15%">'.$row['specification_no'].'</td>
                    <td style="width:25%">'.$row2['material_name'].'</td>
                    <td style="width:15%">'.$row['material_code'].'</td>
                    <td style="width:15%">'.$row['status'].'</td>
                </tr>'; }
                $html.='
            </table>
            <div></div>
            <table>
                <tr>
                    <td style="border:none; width:13%;">Conclusion :</td>
                    <td style="border:none; width:87%;">The conclusion of the undersigned about the above mentioned product Complies as per In House Specification Laid down specification and is of a standard quality mentioned there-in and released for Packing / Distribution.</td>
                </tr>
            </table>
            <div></div>';
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('coa.pdf', 'I');
        }
    }
}
else{
    echo "Invalid Token";
}
?>