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

    if($_GET['type'] == 'rawdatasheet'){
        $sql = "SELECT * FROM testing WHERE testing_no='".$_GET["testing_no"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $_GET['filename'] = 'RAW DATA SHEET'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");

                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                $html.= '
                <table cellpadding="5">
                    <thead>
                        <tr>
                            <td style="background-color:#DDDAD9; width:100%; text-align:center;"><b></b></td>
                        </tr>
                        <tr>
                            <td rowspan="2" style="width:30%;"></td>
                            <td rowspan="2" style="width:30%;"></td>
                            <td style="width:40%;">Copy No :</td>
                        </tr>
                        <tr>
                            <td>Issued By :</td>
                        </tr>
                        <tr>
                            <td>Product Name:</td>
                            <td>A.R. NO.: '.$row['ar_no'].'</td>
                            <td>Batch No. :</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width:20%;"><b>A. R. No.</b></td>
                            <td style="width:30%">'.$row['ar_no'].'</td>
                            <td style="width:20%"><b>Material Code</b></td>
                            <td style="width:30%">'.$row['material_code'].'</td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Name of Material / Product</b></td>
                            <td rowspan="2">';
                            $sql4 = "SELECT * FROM material WHERE material_code='".$row['material_code']."'";
                            $result4 = $conn->query($sql4);
                            if($result4->num_rows > 0){
                                while ($row4 = $result4->fetch_assoc()) {
                                    $html.=''.$row4['material_name'].'';
                                }
                            }
                            $html.='</td>
                            <td><b>Receiving no</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Version No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Batch No /Lot No</b></td>
                            <td></td>
                            <td><b>Supersedes</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Batch Size</b></td>
                            <td rowspan="2"></td>
                            <td><b>Mfg Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Exp. Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Sample Quntity</b></td>
                            <td rowspan="2"></td>
                            <td><b>Specification Reference No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>SAP Reference No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Sample By /Date</b></td>
                            <td></td>
                            <td><b>Analysis Completion Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Reference</b></td>
                            <td></td>
                            <td><b>Effective Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; text-align:center;"><br><br><b>ANALYTICAL REPORT SUMMARY</b><br></td>
                        </tr>
                        <tr style="font-weight:bold">
                            <td style="width:7%;">Sr No.</td>
                            <td style="width:20%;">Test</td>
                            <td style="width:20%;">Subtest</td>
                            <td style="width:27%;">Specification</td>
                            <td style="width:26%;">Result</td>
                        </tr>';
                        $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows > 0){
                            $counter = 1;
                            while ($row1 = $result1->fetch_assoc()) {
                            $html.='<tr>
                                    <td>'.$counter++.'</td>
                                    <td><b>'.$row1['test'].'</b></td>
                                    <td>'.$row1['subtest'].'</td>
                                    <td>'.$row1['description'].'</td>
                                    <td>'.$row1['result'].'</td>
                                </tr>';
                            }
                        }
                        $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows > 0){
                            $counter = 1;
                            while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:3%; border:none;">'.$counter++.'</td>
                                <td style="width:97%; border:none;"><b>'.$row1['test'].'</b> : </td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="border:none;"><b>Observation</b> - '.$row1['observation'].'</td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="border:none;">Acceptance criteria:';
                                $sql3 = "SELECT * spec_tests WHERE 	specification_no='".$row['specification_no']."' ";
                                $result3 = $conn->query($sql3);
                                if($result3->num_rows > 0){
                                    while ($row3 = $result3->fetch_assoc()) {
                                        
                                    }
                                }
                                $html.='</td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="text-align:center;"><b>The Test complies/ Does not Comply</b></td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="width:48%;"><b>Analysed By / Date</b></td>
                                <td style="width:49%;"><b>Checked By / Date</b></td>
                            </tr>';
                        }
                    }
                    $html.='
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
    else if($_GET['type'] == 'ARReportlog'){
       $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html= '
        <style>td { border:solid 1px BCBBBA;}</style>
        <table cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;">
                    <td style="width:10%;">GRN No.</td>
                    <td style="width:15%;">Sampling No</td>
                    <td style="width:15%;">Analysis Start Date</td>
                    <td style="width:30%;">Analysis End Date</td>
                    <td style="width:15%;">Material Name</td>
                    <td style="width:15%;">Material Code</td>
                </tr>
            </thead>
            <tbody>';
        
        //   $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t LEFT JOIN material m ON t.material_code=m.material_code WHERE t.status='Approved' ";
        
          $sql = "SELECT t.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM testing t 
        LEFT JOIN material m ON t.material_code=m.material_code WHERE t.status='Approved' AND m.material_type='Packing Material' ";
        $output = Array();
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='
                    <tr>
                        <td style="width:10%;">'.$row['grn_no'].'</td>
                        <td style="width:15%">'.$row['sampling_no'].'</td>
                        <td style="width:15%">'.$row['alalysis_start_date'].'</td>
                        <td style="width:30%">'.$row['alalysis_end_date'].'</td>
                        <td style="width:15%">'.$row['material_name'].'</td>
                        <td style="width:15%">'.$row['material_code'].'</td>
                    </tr>';
                }
            }
            $html.='
            </tbody>
        </table>
        <div></div>';
    
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('coa.pdf', 'I');
    }
    else if($_GET['type'] == 'ARReport'){
        $sql = "SELECT * FROM testing WHERE ar_no='".$_GET["ar_no"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
                
                $sql2 = "SELECT * FROM material ";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                $html.= '
                <table cellpadding="5" border="1">
                <tr>
                    <td style="width:25%; text-align:centre;"><b>A. R. No.:</b></td>
                    <td style="width:25%; text-align:centre;">'.$row['ar_no'].'</td>
                    <td style="width:25%; text-align:centre;"><b>Specification No.:</b></td>
                    <td style="width:25%; text-align:centre;"></td>
                </tr>';
                $sql4 = "SELECT * FROM material WHERE material_code='".$row['material_code']."'";
                            $result4 = $conn->query($sql4);
                            if($result4->num_rows > 0){
                                while ($row4 = $result4->fetch_assoc()) {
                $html.='<tr>
                    <td style="width:25%; text-align:centre;"><b>Material Code:	</b></td>
                    <td style="width:25%; text-align:centre;">'.$row['material_code'].'</td>
                    <td style="width:25%; text-align:centre;"><b>Material Name:</b></td>
                    <td style="width:25%; text-align:centre;">'.$row4['material_name'].'</td>
                </tr>';
                                }
                            }
               $html.=' <tr>
                    <td style="width:25%; text-align:centre;"><b>Grade</b></td>
                    <td style="width:25%; text-align:centre;">'.$row['grade'].'</td>
                    <td style="width:25%; text-align:centre;"><b>Chemical Name:</b></td>
                    <td style="width:25%; text-align:centre;">'.$row4['material_name'].'</td>
                </tr>';
              $html.='</table>
              <h3>Tests:</h3>
              <table cellpadding="5" border="1">
                    <tr style="font-weight:bold">
                            <td style="width:7%;">Sr No.</td>
                            <td style="width:20%;">Test</td>
                            <td style="width:20%;">Subtest</td>
                            <td style="width:17%;">Description</td>
                            <td style="width:15%;">Observation</td>
                            <td style="width:21%;">Result</td>
                        </tr>';
                        $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows > 0){
                            $counter = 1;
                            while ($row1 = $result1->fetch_assoc()) {
                        $html.='<tr>
                            <td>'.$counter++.'</td>
                            <td>'.$row1['test'].'</td>
                            <td>'.$row1['subtest'].'</td>
                            <td>'.$row1['description'].'</td>
                            <td>'.$row1['observation'].'</td>
                            <td>'.$row1['result'].'</td>
                        </tr>';
                            }
                        }
            }
        }
              $html.='</table>';
                        
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('coa.pdf', 'I');
        }
    else if($_GET['type'] == 'ARReportdigital'){
        $sql = "SELECT * FROM testing WHERE ar_no='".$_GET["ar_no"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'headfootdigital';  include("../pdfimp.php");
                
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                $html.= '
                <style>td { border:solid 1px BCBBBA;}</style>
                <table cellpadding="5">
                    <thead>
                        <tr>
                            <td style="background-color:#DDDAD9; width:100%; text-align:center;"><b>A. R. Report</b></td>
                        </tr>
                        <tr>
                            <td rowspan="2" style="width:30%;"></td>
                            <td rowspan="2" style="width:30%;"></td>
                            <td style="width:40%;">Copy No :</td>
                        </tr>
                        <tr>
                            <td>Issued By :</td>
                        </tr>
                        <tr>
                            <td>Product Name:</td>
                            <td>A.R. NO.: '.$row['ar_no'].'</td>
                            <td>Batch No. :</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width:20%;"><b>A. R. No.</b></td>
                            <td style="width:30%">'.$row['ar_no'].'</td>
                            <td style="width:20%"><b>Material Code</b></td>
                            <td style="width:30%">'.$row['material_code'].'</td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Name of Material / Product</b></td>
                            <td rowspan="2">';
                            $sql4 = "SELECT * FROM material WHERE material_code='".$row['material_code']."'";
                            $result4 = $conn->query($sql4);
                            if($result4->num_rows > 0){
                                while ($row4 = $result4->fetch_assoc()) {
                                    $html.=''.$row4['material_name'].'';
                                }
                            }
                            $html.='</td>
                            <td><b>Receiving no</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Version No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Batch No /Lot No</b></td>
                            <td></td>
                            <td><b>Supersedes</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Batch Size</b></td>
                            <td rowspan="2"></td>
                            <td><b>Mfg Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Exp. Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td rowspan="2"><b>Sample Quntity</b></td>
                            <td rowspan="2"></td>
                            <td><b>Specification Reference No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>SAP Reference No.</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Sample By /Date</b></td>
                            <td></td>
                            <td><b>Analysis Completion Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><b>Reference</b></td>
                            <td></td>
                            <td><b>Effective Date</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; text-align:center;"><br><br><b>TESTS</b><br></td>
                        </tr>
                        <tr style="font-weight:bold">
                            <td style="width:7%;">Sr No.</td>
                            <td style="width:20%;">Test</td>
                            <td style="width:20%;">Subtest</td>
                            <td style="width:27%;">Specification</td>
                            <td style="width:26%;">Result</td>
                        </tr>';
                        $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows > 0){
                            $counter = 1;
                            while ($row1 = $result1->fetch_assoc()) {
                            $html.='<tr>
                                    <td>'.$counter++.'</td>
                                    <td><b>'.$row1['test'].'</b></td>
                                    <td>'.$row1['subtest'].'</td>
                                    <td>'.$row1['description'].'</td>
                                    <td>'.$row1['result'].'</td>
                                </tr>';
                            }
                        }
                        $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                        $result1 = $conn->query($sql1);
                        if($result1->num_rows > 0){
                            $counter = 1;
                            while ($row1 = $result1->fetch_assoc()) {
                            $html.='
                            <tr>
                                <td style="width:3%; border:none;">'.$counter++.'</td>
                                <td style="width:97%; border:none;"><b>'.$row1['test'].'</b> : </td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="border:none;"><b>Observation</b> - '.$row1['observation'].'</td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="border:none;">Acceptance criteria:';
                                $sql3 = "SELECT * spec_tests WHERE 	specification_no='".$row['specification_no']."' ";
                                $result3 = $conn->query($sql3);
                                if($result3->num_rows > 0){
                                    while ($row3 = $result3->fetch_assoc()) {
                                        
                                    }
                                }
                                $html.='</td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="text-align:center;"><b>The Test complies/ Does not Comply</b></td>
                            </tr>
                            <tr>
                                <td style="border:none;"></td>
                                <td style="width:48%;"><b>Analysed By / Date</b></td>
                                <td style="width:49%;"><b>Checked By / Date</b></td>
                            </tr>';
                        }
                    }
                    $html.='
            </table>
            <div></div>';
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('coa.pdf', 'I');
        }else{
            echo "Invalid Testing No.";
        }
    }else if($_GET['type'] == 'ARReportlogRaw'){
       $_GET['filename'] = 'A. R. Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp.php");
        $html= '
        <style>td { border:solid 1px BCBBBA;}</style>
        <table cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;">
                    <td style="width:10%;">A R No.</td>
                    <td style="width:15%;">Sampling No</td>
                    <td style="width:15%;">Specification No</td>
                    <td style="width:30%;">Material Name</td>
                    <td style="width:15%;">Material Code</td>
                    <td style="width:15%;">Material Grade</td>
                </tr>
            </thead>
            <tbody>';
           
                $html.='
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:30%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>';
            
            $html.='
            </tbody>
        </table>
        <div></div>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('coa.pdf', 'I');
    }
}
else{
    echo "Invalid Token";
}
?>