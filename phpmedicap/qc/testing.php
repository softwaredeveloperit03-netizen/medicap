<?php



// ini_set('display_errors', 1);
// error_reporting(E_ALL);
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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

    if ($_GET["type"] == "getPendingTestings") {
        $output = Array();
        $sql = "SELECT * FROM sampling WHERE status='approve' AND testing='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getSpecificationNo") {
        $output = Array();
        $sql = "SELECT specification_no FROM specification WHERE spec_type LIKE 'Raw Material%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getTestingLog") {
        $output = Array();
        $sql = "SELECT * FROM testing WHERE status='approve' AND specification_no LIKE '%".$_GET["specification_no"]."%' AND material_code LIKE '%".$_GET["material_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["material_name"] = $row2["material_name"];
                        $row["material_type"] = $row2["material_type"];
                        $row["material_grade"] = $row2["grade"];
                        break;
                    }
                }
                        
                $sql1 = "SELECT * FROM testing_tests WHERE testing_no='".$row["testing_no"]."'";
                $result1 = $conn->query($sql1);
                $output1 = Array();
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[]= $row1;
                    }
                }
                if (count($output1) > 0) {
                    $row["tests"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "TestingLogPDF") {
         $_GET['filename'] = 'Analytical Report'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
         $html.='
         <h2 style="text-align:center">Analytical Report</h2>
            <table cellpadding="3">
                <tr>
                    <td style="width:7%">Sr No</td>
                    <td style="width:13%">Sampling No	</td>
                    <td style="width:20%">Specification No</td>
                    <td style="width:25%">Material Name</td>
                    <td style="width:20%">Material Code</td>
                    <td style="width:15%">Status</td>
                </tr>';
        $output = Array();
        $sql = "SELECT * FROM testing WHERE status='approve' AND specification_no LIKE '%".$_GET["specification_no"]."%' AND material_code LIKE '%".$_GET["material_code"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $counter = 1;
            while ($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["material_name"] = $row2["material_name"];
                        $row["material_type"] = $row2["material_type"];
                        $row["material_grade"] = $row2["grade"];
                        break;
                    }
                }
                $html.='
                    <tr>
                        <td>'.$counter++.'</td>
                        <td>'.$row['sampling_no'].'</td>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['material_name'].'</td>
                        <td>'.$row['material_code'].'</td>
                        <td>'.$row['status'].'</td>
                    </tr>
                ';
            }
        }
        $html.='</table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
    }
    else if ($_GET["type"] == "downloadtesting") {
         $_GET['filename'] = 'Analytical Report'; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
         $html.='
         <h2 style="text-align:center">Analytical Report</h2>
            <table cellpadding="3">
                <tr>
                    <td style="width:7%">Sr No</td>
                    <td style="width:13%">Sampling No	</td>
                    <td style="width:20%">Specification No</td>
                    <td style="width:25%">Material Name</td>
                    <td style="width:20%">Material Code</td>
                    <td style="width:15%">Status</td>
                </tr>';
        $output = Array();
        // $sql = "SELECT DISTINCT * FROM sampling WHERE sample_status = 'complete' AND plant_id = '" . $plantId . "' ORDER BY id DESC";
        $sql = "SELECT DISTINCT * FROM sampling WHERE sample_status = 'complete'  ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $counter = 1;
            while ($row = $result->fetch_assoc()) {
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["material_name"] = $row2["material_name"];
                        $row["material_type"] = $row2["material_type"];
                        $row["material_grade"] = $row2["grade"];
                        break;
                    }
                }
                $html.='
                    <tr>
                        <td>'.$counter++.'</td>
                        <td>'.$row['sampling_no'].'</td>
                        <td>'.$row['specification_no'].'</td>
                        <td>'.$row['material_name'].'</td>
                        <td>'.$row['material_code'].'</td>
                        <td>'.$row['status'].'</td>
                    </tr>
                ';
            }
        }
        $html.='</table>';
         $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
    }
    else if ($_GET['type'] == 'TestingPDF'){
        $sql = "SELECT * FROM testing WHERE testing_no='".$_GET["testing_no"]."'";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $_GET['filename'] = 'CERTIFICATE OF ANALYSIS'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
                $sql2 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                $row2 = $result2->fetch_assoc();
                $html.= '
                <h2 style="text-align:center">CERTIFICATE OF ANALYSIS</h2>
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
}

$conn->close();
?>