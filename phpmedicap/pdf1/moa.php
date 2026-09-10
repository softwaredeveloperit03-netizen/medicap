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

    if($_GET["type"]=="testmethod"){
        $sql = "SELECT * FROM spec_tests WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $data = json_decode($row['descriptions']);
            $_GET['filename'] = 'Test Method Master'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
            $html.='
        	    <table cellpadding="5">
        	        <tr>
        	            <td><b>Classification:</b></td>
        	            <td><b>Dosage Form</b></td>
        	            <td><b>Test</b></td>
        	            <td><b>Sub Test</b></td>
        	        </tr>
        	        <tr>
        	            <td>'.$row['classification'].'</td>
        	            <td>'.$row['dosage_form'].'</td>
        	            <td>'.$row['subtest'].'</td>
        	            <td>'.$row['subtest'].'</td>
        	        </tr>
        	   </table>
        	   <div></div>';
        	    $j = 0;
                for ($i=1; $i <= count($data); $i++) {
                    $detail = $data[$j];
                    $list = $detail->list;
                    if($detail->name == 'Chemical / Reagents'){
                        $html.='
                	    <table cellpadding="5">
                	        <tr>
                	            <td style="width:100%;border:none;"><b>Chemical / Reagents</b></td>
                	        </tr>
                	        <tr style="background-color:#DDDAD9; text-align:center;">
                	            <td style="width:40%;">Chemical Name</td>
                	            <td style="width:30%;">Grade</td>
                	            <td style="width:30%;">Make</td>
                	        </tr>';
                	        $m=0;
                	        for ($n=1; $n <= count($list); $n++) {
                	            $listdata = $list[$m];
                	            $html.='
                    	        <tr>
                    	            <td>'.$listdata->chemical_name.'</td>
                    	            <td>'.$listdata->grade.'</td>
                    	            <td>'.$listdata->make.'</td>
                    	        </tr>';
                    	        $m++;
                	        }
                	        $html.='
                        </table>';
                    }else if($detail->name == 'Procedure / method Description'){
                        $html.='
                	    <table cellpadding="5">
                	        <tr>
                	            <td style="width:100%;border:none;"><b>Procedure / method Description</b></td>
                	        </tr>
                	        <tr style="background-color:#DDDAD9; text-align:center;">
                	            <td style="width:20%;">Sr No.</td>
                	            <td style="width:80%;">Procedure</td>
                	        </tr>';
                	        $a=0;
                	        for ($n=1; $n <= count($list); $n++) {
                	            $listdata = $list[$a];
                	            $html.='
                    	        <tr>
                    	            <td>'.$n.'</td>
                    	            <td>'.$listdata->test_description.'</td>
                    	        </tr>';
                    	        $a++;
                	        }
                	        $html.='
                        </table>';
                    }else if($detail->name == 'Equipments / Instruments'){
                        $html.='
                	    <table cellpadding="5">
                	        <tr>
                	            <td style="width:100%;border:none;"><b>Equipments / Instruments</b></td>
                	        </tr>
                	        <tr style="background-color:#DDDAD9; text-align:center;">
                	            <td style="width:20%;">Sr No.</td>
                	            <td style="width:20%;">Equipment Name</td>
                	            <td style="width:20%">Make</td>
                	            <td style="width:20%">Equiment Type</td>
                	            <td style="width:20%">Installation Date</td>
                	        </tr>';
                	        $a=0;
                	        for ($n=1; $n <= count($list); $n++) {
                	            $listdata = $list[$a];
                	            $html.='
                    	        <tr>
                    	            <td>'.$n.'</td>
                    	            <td>'.$listdata->equipment_name.'</td>
                    	            <td>'.$listdata->make.'</td>
                    	            <td>'.$listdata->equipment_type.'</td>
                    	            <td>'.$listdata->installation_date.'</td>
                    	        </tr>';
                    	        $a++;
                	        }
                	        $html.='
                        </table>';
                    }
                    $j++;
                }
                
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('BatchRelease.pdf', 'I');
        }
    }
    else if($_GET["type"]=="testmethoddigital"){
        $sql = "SELECT * FROM spec_tests WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $data = json_decode($row['descriptions']);
            $_GET['filename'] = 'Test Method Master'; $_GET['pdftype'] = 'headfootdigital';  include("../pdfimp.php");
            $html.='
        	    <table cellpadding="5">
        	        <tr>
        	            <td><b>Classification:</b></td>
        	            <td><b>Dosage Form</b></td>
        	            <td><b>Test</b></td>
        	            <td><b>Sub Test</b></td>
        	        </tr>
        	        <tr>
        	            <td>'.$row['classification'].'</td>
        	            <td>'.$row['dosage_form'].'</td>
        	            <td>'.$row['subtest'].'</td>
        	            <td>'.$row['subtest'].'</td>
        	        </tr>
        	   </table>
        	   <div></div>';
        	    $j = 0;
                for ($i=1; $i <= count($data); $i++) {
                    $detail = $data[$j];
                    $list = $detail->list;
                    if($detail->name == 'Chemical / Reagents'){
                        $html.='
                	    <table cellpadding="5">
                	        <tr>
                	            <td style="width:100%;border:none;"><b>Chemical / Reagents</b></td>
                	        </tr>
                	        <tr style="background-color:#DDDAD9; text-align:center;">
                	            <td style="width:40%;">Chemical Name</td>
                	            <td style="width:30%;">Grade</td>
                	            <td style="width:30%;">Make</td>
                	        </tr>';
                	        $m=0;
                	        for ($n=1; $n <= count($list); $n++) {
                	            $listdata = $list[$m];
                	            $html.='
                    	        <tr>
                    	            <td>'.$listdata->chemical_name.'</td>
                    	            <td>'.$listdata->grade.'</td>
                    	            <td>'.$listdata->make.'</td>
                    	        </tr>';
                    	        $m++;
                	        }
                	        $html.='
                        </table>';
                    }else if($detail->name == 'Procedure / method Description'){
                        $html.='
                	    <table cellpadding="5">
                	        <tr>
                	            <td style="width:100%;border:none;"><b>Procedure / method Description</b></td>
                	        </tr>
                	        <tr style="background-color:#DDDAD9; text-align:center;">
                	            <td style="width:20%;">Sr No.</td>
                	            <td style="width:80%;">Procedure</td>
                	        </tr>';
                	        $a=0;
                	        for ($n=1; $n <= count($list); $n++) {
                	            $listdata = $list[$a];
                	            $html.='
                    	        <tr>
                    	            <td>'.$n.'</td>
                    	            <td>'.$listdata->test_description.'</td>
                    	        </tr>';
                    	        $a++;
                	        }
                	        $html.='
                        </table>';
                    }else if($detail->name == 'Equipments / Instruments'){
                        $html.='
                	    <table cellpadding="5">
                	        <tr>
                	            <td style="width:100%;border:none;"><b>Equipments / Instruments</b></td>
                	        </tr>
                	        <tr style="background-color:#DDDAD9; text-align:center;">
                	            <td style="width:20%;">Sr No.</td>
                	            <td style="width:20%;">Equipment Name</td>
                	            <td style="width:20%">Make</td>
                	            <td style="width:20%">Equiment Type</td>
                	            <td style="width:20%">Installation Date</td>
                	        </tr>';
                	        $a=0;
                	        for ($n=1; $n <= count($list); $n++) {
                	            $listdata = $list[$a];
                	            $html.='
                    	        <tr>
                    	            <td>'.$n.'</td>
                    	            <td>'.$listdata->equipment_name.'</td>
                    	            <td>'.$listdata->make.'</td>
                    	            <td>'.$listdata->equipment_type.'</td>
                    	            <td>'.$listdata->installation_date.'</td>
                    	        </tr>';
                    	        $a++;
                	        }
                	        $html.='
                        </table>';
                    }
                    $j++;
                }
                
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('BatchRelease.pdf', 'I');
        }
    }
    else if ($_GET["type"]=="TestMethodsLog") {
        $_GET['filename'] = 'Test Method  Log'; $_GET['pdftype'] = 'landscape';  include("../pdfimp.php");
        $html.='
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;">
                <td style="width:10%;">Sr</td>
                <td style="width:15%;">Specification No</td>
                <td style="width:15%;">Specification Type</td>
                <td style="width:15%;">Material/Product Code</td>
                <td style="width:15%;">Test</td>
                <td style="width:15%;">Subtest</td>
                <td style="width:15%;">Status</td>
            </tr>';
        $sql = "SELECT * FROM spec_tests";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $counter = 1;
            while ($row = $result->fetch_assoc()) {
                $html.='
                <tr nobr="true">
                    <td>'.$counter++.'</td>
                    <td>'.$row['specification_no'].'</td>
                    <td>'.$row['spec_type'].'</td>
                     <td>'.$row['material_code'].'</td>
                    <td>'.$row["test"].'</td>
                    <td>'.$row['subtest'].'</td>
                    <td>'.$row["status"].'</td>
                </tr>';
            }
        }
        $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('testmethodslog.pdf', 'I');
    } 
    else if($_GET['type'] == 'moa'){
        class MYPDF extends TCPDF {
            public function Header() {
            }
            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            }
        }
        $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(15, 15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();
        $pdf->SetFont ('Times', '', '10' , '', 'default', true );
        $html ='<style>
            td {
                border:solid 1px BCBBBA;
            }
            </style>
            <table border="0" cellpadding="5" style="text-align:center; vertical-align:middle;margin-top:20px;">
                <tr>
                    <td colspan="3" style="width: 80%;">CYCLONE PHARMACEUTICALS PVT. LTD.</td>
                    <td style="width: 20%;" rowspan="2"><img src="../../assets/logo.png" style="height:50px;"></td>
                </tr>
                <tr><td colspan="3" style="width: 80%;">202,Sai Heritage, Lane No 06,Adarsh Nagar/Tingare Nagar,Vishrantwadi to Air Port, near Air Port, Pune, 411015</td></tr>
                <tr><td colpsan="4" style="width: 100%;">METHOD OF ANALYSIS</td></tr>
            </table>
            <br><br>';
        $sql = "SELECT * FROM testing WHERE testing_no='T-01'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html1 .= '<table cellpadding="5" style="text-align:left;">
                        <tr>
                            <td style="width:15%">Department</td>
                            <td style="width:3%;"> :</td>
                            <td style="width:47%">Quality Control Department</td>
                            <td style="width:15%">Material Code:</td>
                            <td style="width:3%;"> :</td>
                            <td style="width:17%">'.$row["material_code"].'</td>
                        </tr>';
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $html1.='<tr>
                            <td style="width:15%" rowspan="2">Version No.</td>
                            <td style="width:3%;vertical-align: middle;" rowspan="2"> :</td>
                            <td style="width:47%;vertical-align: middle;" rowspan="2">'.$row1["material_name"].'</td>
                            <td style="width:15%">Supersede No.</td>
                            <td style="width:3%;"> :</td>
                            <td style="width:17%">Jul,2018</td>
                        </tr>';
                    }
                }
                $html1.='
                <tr>
                    <td style="width:15%">Sample Qty:</td>
                    <td style="width:3%;"> :</td>
                    <td style="width:17%">Jul,2018</td>
                </tr>
                <tr>
                    <td style="width:15%" rowspan="2">Shelf Life</td>
                    <td style="width:3%;vertical-align: middle;" rowspan="2"> :</td>
                    <td style="width:47%;vertical-align: middle;" rowspan="2">ATP003</td>
                    <td style="width:15%">SAP No</td>
                    <td style="width:3%;"> :</td>
                    <td style="width:17%">Jul,2018</td>
                </tr>
                <tr>
                    <td style="width:15%">Storage</td>
                    <td style="width:3%;"> :</td>
                    <td style="width:17%">Jul,2018</td>
                </tr>
                <tr>
                    <td style="width:15%" rowspan="2">Safety Precaution</td>
                    <td style="width:3%;vertical-align: middle;" rowspan="2"> :</td>
                    <td style="width:47%;vertical-align: middle;" rowspan="2">ATP003</td>
                    <td style="width:15%">Chemical Name</td>
                    <td style="width:3%;"> :</td>
                    <td style="width:17%">Jul,2018</td>
                </tr>
            </table>
            <h2 style="text-align: center;">RAW MATERIAL SPECIFICATION</h2>
            <table cellpadding="5">
                <tr>
                    <td style="width:10%;">Sr No.</td>
                    <td style="width:30%;">Test</td>
                    <td style="width:30%;">Specification</td>
                    <td style="width:30%;">Result</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </table>';
            }
        }
        $sql = "SELECT * FROM testing_tests WHERE testing_no='T-01' GROUP BY test";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i = 1;
            $alphabet = range('A', 'Z');
            while ($row = $result->fetch_assoc()) {
                $html1 .= '<h3>'.$i.'. '.$row["test"].'</h3>';
                $sql1 = "SELECT * FROM testing_tests WHERE testing_no='T-01' WHERE test='".$row["test"]."' AND subtest !=''";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $html1.='<span><b>'.$alphabet[$i].'. Observation:</b> '.$row["result"].'</span><br>';
                        $html1.='<span><b>Acceptance criteria:</b><br>'.$row["description"].'</span><br>';
                        if ($row["status"] == "approve") {
                            $html1.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test complies</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                        } else {
                            $html1.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test Not Comply</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                        }
                    }
                } else {
                    $html1.='<span><b>Observation:</b> '.$row["result"].'</span><br>';
                    $html1.='<span><b>Acceptance criteria:</b><br>'.$row["description"].'</span><br>';
                    if ($row["status"] == "approve") {
                        $html1.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test complies</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                    } else {
                        $html1.='<table cellpadding="5"><tr><td colspan="2" style="text-align: center;">The Test Not Comply</td></tr><tr><td style="text-align: center;">Analysed By/Date: '.$row["person"].'</td><td style="text-align: center;">Checked By/Date:</td></tr></table>';
                    }
                }
                $i++;
            }
        }
        EOD;
        $pdf->writeHTML($html1, true, false, false, false, '');
        $pdf->Output('moa.pdf', 'I');
    }
    
    else if($_GET['type'] == 'RawMOA'){
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve' AND id='".$_GET['id']."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $_GET['filename'] = 'Raw Material MOA Report'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
                    
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
                    <table cellpadding="5" style="text-align:left;">
                        <tr>
                            <td style="width:20%">Department</td>
                            <td style="width:30%">Quality Control Department</td>
                            <td style="width:20%">Material Code:</td>
                            <td style="width:30%">'.$row["material_code"].'</td>
                        </tr>
                        <tr>
                            <td>Material Name.</td>
                            <td>'.$row["material_name"].'</td>
                            <td>Material Grade.</td>
                            <td>'.$row["grade"].'</td>
                        </tr>
                        <tr>
                            <td>Material Type</td>
                            <td>'.$row["material_type"].'</td>
                            <td>Supersede No</td>
                            <td>'.$row["supersede_no"].'</td>
                        </tr>
                        <tr>
                            <td>Sample Qty</td>	
                            <td>30 mg	</td>
                            <td>Shelf Life</td>	
                            <td>22</td>
                        </tr>
                        <tr>
                            <td>SAP No</td>
                            <td>4654156</td>	
                            <td>Storage</td>
                            <td>TEST</td>
                        </tr>
                    </table>
                    <h2 style="text-align: center;">Tests:</h2>';
                    $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        $counter = 1;
                        while ($row1 = $result1->fetch_assoc()) {
                            $method_details = json_decode($row1["method_details"]);
                            $j =0;
                            $html.='<br><br><b>'.$row1['test'].' : '.$row1['subtest'].'</b><br><br>';
                            for($i=1; $i<=count($method_details); $i++){
                                $data = $method_details[$j];
                                if($data->name == 'Procedure / method Description'){
                                    $list = $data->list;
                                    $html.='
                                    <table cellpadding="5" nobr="true">
                                        <tr>
                                            <td style="border:none;"><b>'.$data->name.'</b></td>
                                        </tr>
                                        <tr>
                                            <td style="width:5%;">Sr.</td>
                                            <td style="width:95%;">Procedure / method Description</td>
                                        </tr>';
                                        $k = 0;
                                        for($a=1; $a<=count($list); $a++){
                                        $listdata = $list[$k];
                                        $html.='
                                            <tr>
                                                <td></td>
                                                <td>'.$listdata->test_description.'</td>
                                            </tr>';
                                            $k++;
                                        }
                                    $html.='
                                    </table>';
                                    $j++;
                                }
                                if($data->name == 'Chemical / Reagents'){
                                    $list = $data->list;
                                    $html.='
                                    <table cellpadding="5" nobr="true">
                                        <tr>
                                            <td style="border:none;"><b>'.$data->name.'</b></td>
                                        </tr>
                                        <tr>
                                            <td style="width:5%;">Sr.</td>
                                            <td style="width:35%;">Chemical Name</td>
                                            <td style="width:30%;">Grade</td>
                                            <td style="width:30%;">Make</td>
                                        </tr>';
                                        $k = 0;
                                        for($a=1; $a<=count($list); $a++){
                                        $listdata = $list[$k];
                                        $html.='
                                            <tr>
                                                <td>'.$a.'</td>
                                                <td>'.$listdata->chemical_name.'</td>
                                                <td>'.$listdata->grade.'</td>
                                                <td>'.$listdata->make.'</td>
                                            </tr>';
                                            $k++;
                                        }
                                    $html.='
                                    </table>';
                                    $j++;
                                }
                                if($data->name == 'Equipments / Instruments'){
                                    $list = $data->list;
                                    $html.='
                                    <table cellpadding="5" nobr="true">
                                        <tr>
                                            <td style="border:none;"><b>'.$data->name.'</b></td>
                                        </tr>
                                        <tr>
                                            <td style="width:5%;">Sr.</td>
                                            <td style="width:25%;">Equipment Name</td>
                                            <td style="width:25%;">Category</td>
                                            <td style="width:25%;">Type</td>
                                            <td style="width:20%;">Make</td>
                                        </tr>';
                                        $k = 0;
                                        for($a=1; $a<=count($list); $a++){
                                        $listdata = $list[$k];
                                        $html.='
                                            <tr>
                                                <td>'.$a.'</td>
                                                <td>'.$listdata->equipment_name.'</td>
                                                <td>'.$listdata->category.'</td>
                                                <td>'.$listdata->equipment_type.'</td>
                                                <td>'.$listdata->make.'</td>
                                            </tr>';
                                            $k++;
                                        }
                                    $html.='
                                    </table>';
                                    $j++;
                                }
                                if($data->name == 'HPLC Column'){
                                    $list = $data->list;
                                    $html.='
                                    <table cellpadding="5" nobr="true">
                                        <tr>
                                            <td style="border:none;"><b>'.$data->name.'</b></td>
                                        </tr>
                                        <tr>
                                            <td style="width:5%;">Sr.</td>
                                            <td style="width:35%;">HPLC Name</td>
                                            <td style="width:30%;">Packing</td>
                                            <td style="width:30%;">Brand</td>
                                        </tr>';
                                        $k = 0;
                                        for($a=1; $a<=count($list); $a++){
                                        $listdata = $list[$k];
                                        $html.='
                                            <tr>
                                                <td>'.$a.'</td>
                                                <td>'.$listdata->name.'</td>
                                                <td>'.$listdata->packing.'</td>
                                                <td>'.$listdata->brand.'</td>
                                            </tr>';
                                            $k++;
                                        }
                                    $html.='
                                    </table>';
                                    $j++;
                                }
                                if($data->name == 'Glasswares'){
                                    $list = $data->list;
                                    $html.='
                                    <table cellpadding="5" nobr="true">
                                        <tr>
                                            <td style="border:none;"><b>'.$data->name.'</b></td>
                                        </tr>
                                        <tr>
                                            <td style="width:5%;">Sr.</td>
                                            <td style="width:35%;">Glassware Name</td>
                                            <td style="width:30%;">Capacity</td>
                                            <td style="width:30%;">Make</td>
                                        </tr>';
                                        $k = 0;
                                        for($a=1; $a<=count($list); $a++){
                                        $listdata = $list[$k];
                                        $html.='
                                            <tr>
                                                <td>'.$a.'</td>
                                                <td>'.$listdata->name.'</td>
                                                <td>'.$listdata->capacity.'</td>
                                                <td>'.$listdata->make.'</td>
                                            </tr>';
                                            $k++;
                                        }
                                    $html.='
                                    </table>';
                                    $j++;
                                }
                                if($data->name == 'Calculations'){
                                    $list = $data->list;
                                    $html.='
                                    <table cellpadding="5" nobr="true">
                                        <tr>
                                            <td style="border:none;"><b>'.$data->name.'</b></td>
                                        </tr>
                                        <tr>
                                            <td style="width:5%;">Sr.</td>
                                            <td style="width:35%;">Calculation Formula</td>
                                        </tr>';
                                        $k = 0;
                                        for($a=1; $a<=count($list); $a++){
                                        $listdata = $list[$k];
                                        $html.='
                                            <tr>
                                                <td>'.$a.'</td>
                                                <td>'.$listdata->name.'</td>
                                            </tr>';
                                            $k++;
                                        }
                                    $html.='
                                    </table>';
                                    $j++;
                                }
                                if($data->name == 'Dilutions'){
                                    $list = $data->list;
                                    $html.='
                                    <table cellpadding="5" nobr="true">
                                        <tr>
                                            <td style="border:none;"><b>'.$data->name.'</b></td>
                                        </tr>
                                        <tr>
                                            <td style="width:5%;">Sr.</td>
                                            <td style="width:35%;">Calculation Formula</td>
                                        </tr>';
                                        $k = 0;
                                        for($a=1; $a<=count($list); $a++){
                                        $listdata = $list[$k];
                                        $html.='
                                            <tr>
                                                <td>'.$a.'</td>
                                                <td>'.$listdata->name.'</td>
                                            </tr>';
                                            $k++;
                                        }
                                    $html.='
                                    </table>';
                                    $j++;
                                }
                                if($data->name == 'Volumetric Solutions'){
                                    $list = $data->list;
                                    $html.='
                                    <table cellpadding="5" nobr="true">
                                        <tr>
                                            <td style="border:none;"><b>'.$data->name.'</b></td>
                                        </tr>
                                        <tr>
                                            <td style="width:5%;">Sr.</td>
                                            <td style="width:35%;">Calculation Formula</td>
                                        </tr>';
                                        $k = 0;
                                        for($a=1; $a<=count($list); $a++){
                                        $listdata = $list[$k];
                                        $html.='
                                            <tr>
                                                <td>'.$a.'</td>
                                                <td>'.$listdata->name.'</td>
                                            </tr>';
                                            $k++;
                                        }
                                    $html.='
                                    </table>';
                                    $j++;
                                }
                            }
                        }
                    }
                }
            }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('moa.pdf', 'I');
    }
    else if($_GET['type'] == 'RawMOAdigital'){
        $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve' AND id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $_GET['filename'] = 'Raw Material MOA'; $_GET['pdftype'] = 'headfootdigital';  include("../pdfimp.php");
                
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
                <style>td {border:solid 1px BCBBBA;}</style>
                <h3 style="text-align:center;">Raw Material MOA Report</h3>
                <table cellpadding="5" style="text-align:left;">
                    <tr>
                        <td style="width:20%">Department</td>
                        <td style="width:30%">Quality Control Department</td>
                        <td style="width:20%">Material Code:</td>
                        <td style="width:30%">'.$row["material_code"].'</td>
                    </tr>
                    <tr>
                        <td>Material Name.</td>
                        <td>'.$row["material_name"].'</td>
                        <td>Material Grade.</td>
                        <td>'.$row["grade"].'</td>
                    </tr>
                    <tr>
                        <td>Material Type</td>
                        <td>'.$row["material_type"].'</td>
                        <td>Supersede No</td>
                        <td>'.$row["supersede_no"].'</td>
                    </tr>
                    <tr>
                        <td>Sample Qty</td>	
                        <td>30 mg	</td>
                        <td>Shelf Life</td>	
                        <td>22</td>
                    </tr>
                    <tr>
                        <td>SAP No</td>
                        <td>4654156</td>	
                        <td>Storage</td>
                        <td>TEST</td>
                    </tr>
                </table>
                <h2 style="text-align: center;">Tests:</h2>';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $counter = 1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $method_details = json_decode($row1["method_details"]);
                        $j =0;
                        $html.='<br><br><b>'.$row1['test'].' : '.$row1['subtest'].'</b><br><br>';
                        for($i=1; $i<=count($method_details); $i++){
                            $data = $method_details[$j];
                            if($data->name == 'Procedure / method Description'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Procedure / method Description</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td></td>
                                            <td>'.$listdata->test_description.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Chemical / Reagents'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Chemical Name</td>
                                        <td style="width:30%;">Grade</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->chemical_name.'</td>
                                            <td>'.$listdata->grade.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Equipments / Instruments'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:25%;">Equipment Name</td>
                                        <td style="width:25%;">Category</td>
                                        <td style="width:25%;">Type</td>
                                        <td style="width:20%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->equipment_name.'</td>
                                            <td>'.$listdata->category.'</td>
                                            <td>'.$listdata->equipment_type.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'HPLC Column'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">HPLC Name</td>
                                        <td style="width:30%;">Packing</td>
                                        <td style="width:30%;">Brand</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->packing.'</td>
                                            <td>'.$listdata->brand.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Glasswares'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Glassware Name</td>
                                        <td style="width:30%;">Capacity</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->capacity.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Calculations'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Dilutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Volumetric Solutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                        }
                    }
                }
            }
        }
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('moa.pdf', 'I');
    }
    else if($_GET['type'] == 'RawMOALog'){
        $_GET['filename'] = 'Raw Material MOA Report'; $_GET['pdftype'] = 'landscape';  include("../pdfimp.php");
        $html.='
        <style>td {border:solid 1px BCBBBA;}</style>
        <table cellpadding="5">
            <tr>
                <td colspan="6" style="border:none;text-align:center;"><b>RAW MATERIAL MOA REPORT LOG</b></td>
            </tr>
            <tr style="background-color:#DDDAD9;">
                <td style="width:5%;">Sr</td>
                <td style="width:10%;">Material Code</td>
                <td style="width:30%;">Material Name</td>
                <td style="width:15%;">Grade</td>
                <td style="width:15%;">Specification No</td>
                <td style="width:15%;">Status</td>
                <td style="width:10%;">Prepared By</td>
            </tr>';
            if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve' AND material_code='".$_GET['materil_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve' AND material_code='".$_GET['materil_code']."'";
            }else{
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve'";
            }
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $counter = 1;
                while ($row = $result->fetch_assoc()) {
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
                    <td>'.$row["material_code"].'</td>
                    <td>'.$row["material_name"].'</td>
                    <td>'.$row["grade"].'</td>
                    <td>'.$row["specification_no"].'</td>
                    <td>'.$row["status"].'</td>
                    <td>'.$row["entry_by"].'</td>
                </tr>';
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('moa.pdf', 'I');
    }
    
    else if($_GET['type'] == 'PackingMOA'){
        $_GET['filename'] = 'Raw Material MOA Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        
         $html.='
        <h3>Raw Material MOA Report</h3>
        <table cellpadding="5" border="0.1">';
      
         $html.=' ';
           $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification'
           AND ismoa='approve'";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $sql2 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {  
                        
                        
            $html.=' <tr >
         
         
       <td style="width:20%;background-color:#DDDAD9;"><b>Specification No</b></td>
       <td style="width:30%;">'.$row['specification_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Specification Type</b></td>
          <td style="width:30%;">'.$row['spec_type'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Material Type</b></td>
       <td style="width:30%;">'.$row1['material_type'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Material Name</b></td>
           <td style="width:30%;">'.$row1['material_name'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Material Code</b></td>
        <td style="width:30%;">'.$row1['material_code'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Material Grade</b></td>
           <td style="width:30%;">'.$row['material_grade'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Version No</b></td>
          <td style="width:30%;">'.$row['version_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Supersede No</b></td>
           <td style="width:30%;">'.$row['supersede_no'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Sample Qty</b></td>
         <td style="width:30%;">'.$row['sample_qty'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Shelf Life</b></td>
          <td style="width:30%;">'.$row['shelf_life'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>SAP No</b></td>
         <td style="width:30%;">'.$row['sap_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Storage</b></td>
           <td style="width:30%;">'.$row['storage'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Chemical Name</b></td>
           <td style="width:80%;">'.$row['chemical_name'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Safety Precaution</b></td>
         <td style="width:80%;">'.$row['safety_precaution'].'</td>
           </tr>';
                   
         
           $html.=' </table>
           <div></div>
           <h3>Tests:</h3>
           <table cellpadding="5" border="0.1">
            <tr style="text-align:center;background-color:#DDDAD9;">
           <td style="width:20%;text-align:center"><b>Test</b></td>
            <td style="width:20%;text-align:center"><b>Sub Test</b></td>
             <td style="width:20%;text-align:center"><b>Description</b></td>
              <td style="width:20%;text-align:center"><b>Refernce Type</b></td>
               <td style="width:20%;text-align:center"><b>Sample Qty</b></td>
           </tr>';
                    
                
         $html.=' <tr>
           <td style="width:20%">'.$row2['test'].'</td>
            <td style="width:20%">'.$row2['subtest'].'</td>
             <td style="width:20%">'.$row2['description'].'</td>
              <td style="width:20%">'.$row2['reference_type'].'</td>
               <td style="width:20%">'.$row2['sample_qty'].'</td>
           </tr>';
                    
                    }
                }
     }}}
    }   
          $html.=' </table>
           
          <div></div> <div></div> <div></div> <div></div> <div></div>
        
           <table cellpadding="5" border="0.1">
           <tr style="text-align:center;background-color:#DDDAD9;">
             <td style="width:15%"> </td>
                    <td style="width:30%;text-align:center"><b>Prepared by</b></td>
                    <td style="width:25%;text-align:center"><b>Checked By</b></td>
                    <td style="width:30%;text-align:center"><b>Approved By</b></td>
                </tr>
           <tr>
           <td style="width:15%;text-align:center"><b>Name</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
              <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Date</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
               <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Sign</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
             <td style="width:30%"></td>
              </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Raw MOA.pdf', 'I');
        
        
    }
    else if($_GET['type'] == 'PackingMOAdigital'){
        $_GET['filename'] = 'Raw Material MOA Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        
         $html.='
        <h3>Raw Material MOA Report</h3>
        <table cellpadding="5" border="0.1">';
      
         $html.=' ';
           $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification'
           AND ismoa='approve'";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $sql2 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {  
                        
                        
            $html.=' <tr >
         
         
       <td style="width:20%;background-color:#DDDAD9;"><b>Specification No</b></td>
       <td style="width:30%;">'.$row['specification_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Specification Type</b></td>
          <td style="width:30%;">'.$row['spec_type'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Material Type</b></td>
       <td style="width:30%;">'.$row1['material_type'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Material Name</b></td>
           <td style="width:30%;">'.$row1['material_name'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Material Code</b></td>
        <td style="width:30%;">'.$row1['material_code'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Material Grade</b></td>
           <td style="width:30%;">'.$row['material_grade'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Version No</b></td>
          <td style="width:30%;">'.$row['version_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Supersede No</b></td>
           <td style="width:30%;">'.$row['supersede_no'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Sample Qty</b></td>
         <td style="width:30%;">'.$row['sample_qty'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Shelf Life</b></td>
          <td style="width:30%;">'.$row['shelf_life'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>SAP No</b></td>
         <td style="width:30%;">'.$row['sap_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Storage</b></td>
           <td style="width:30%;">'.$row['storage'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Chemical Name</b></td>
           <td style="width:80%;">'.$row['chemical_name'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Safety Precaution</b></td>
         <td style="width:80%;">'.$row['safety_precaution'].'</td>
           </tr>';
                   
         
           $html.=' </table>
           <div></div>
           <h3>Tests:</h3>
           <table cellpadding="5" border="0.1">
            <tr style="text-align:center;background-color:#DDDAD9;">
           <td style="width:20%;text-align:center"><b>Test</b></td>
            <td style="width:20%;text-align:center"><b>Sub Test</b></td>
             <td style="width:20%;text-align:center"><b>Description</b></td>
              <td style="width:20%;text-align:center"><b>Refernce Type</b></td>
               <td style="width:20%;text-align:center"><b>Sample Qty</b></td>
           </tr>';
                    
                
         $html.=' <tr>
           <td style="width:20%">'.$row2['test'].'</td>
            <td style="width:20%">'.$row2['subtest'].'</td>
             <td style="width:20%">'.$row2['description'].'</td>
              <td style="width:20%">'.$row2['reference_type'].'</td>
               <td style="width:20%">'.$row2['sample_qty'].'</td>
           </tr>';
                    
                    }
                }
     }}}
    }   
          $html.=' </table>
           
          <div></div> <div></div> <div></div> <div></div> <div></div>
        
           <table cellpadding="5" border="0.1">
           <tr style="text-align:center;background-color:#DDDAD9;">
             <td style="width:15%"> </td>
                    <td style="width:30%;text-align:center"><b>Prepared by</b></td>
                    <td style="width:25%;text-align:center"><b>Checked By</b></td>
                    <td style="width:30%;text-align:center"><b>Approved By</b></td>
                </tr>
           <tr>
           <td style="width:15%;text-align:center"><b>Name</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
              <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Date</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
               <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Sign</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
             <td style="width:30%"></td>
              </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Raw MOA.pdf', 'I');
        
        
    
    }else if($_GET['type'] == 'PackingMOALog'){
        $_GET['filename'] = 'Packing Material MOA Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <style>td {border:solid 1px BCBBBA;}</style>
        <table cellpadding="5">
            <tr>
                <td colspan="6" style="border:none;text-align:center;"><b>PACKING MATERIAL MOA REPORT LOG</b></td>
            </tr>
            <tr style="background-color:#DDDAD9;">
                <td style="width:15%;">Material Code</td>
                <td style="width:25%;">Material Name</td>
                <td style="width:15%;">Grade</td>
                <td style="width:15%;">Specification No</td>
                <td style="width:15%;">Status</td>
                <td style="width:15%;">Prepared By</td>
            </tr>';
            //if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
               // $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND ismoa='approve' AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
           // } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
                //$sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND ismoa='approve' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            //} else if($_GET['fromdate'] == '' && $_GET['material_code'] != ''){
                //$sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND ismoa='approve' AND material_code='".$_GET['material_code']."'";
           // }else{
                $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND ismoa='approve'";
           // }
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
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
                    <td>'.$row["material_code"].'</td>
                    <td>'.$row["material_name"].'</td>
                    <td>'.$row["grade"].'</td>
                    <td>'.$row["specification_no"].'</td>
                    <td>'.$row["status"].'</td>
                    <td>'.$row["entry_by"].'</td>
                </tr>';
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('moa.pdf', 'I');
    }
    
    else if($_GET['type'] == 'FinishMOA'){
        $sql = "SELECT * FROM specification WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $_GET['filename'] = 'Finish Product MOA'; $_GET['pdftype'] = 'headfoot';  include("../pdfimp.php");
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                $html.='
                <style>td {border:solid 1px BCBBBA;}</style>
                <h3 style="text-align:center;">Finish Product MOA Report</h3>
                <table cellpadding="5" style="text-align:left;">
                    <tr>
                        <td style="width:20%">Department</td>
                        <td style="width:30%">Quality Control Department</td>
                        <td style="width:20%">Material Code:</td>
                        <td style="width:30%">'.$row["material_code"].'</td>
                    </tr>
                    <tr>
                        <td>Product Name.</td>
                        <td>'.$row["product_name"].'</td>
                        <td>Material Grade.</td>
                        <td>'.$row["grade"].'</td>
                    </tr>
                    <tr>
                        <td>Product Type</td>
                        <td>'.$row["material_subtype"].'</td>
                        <td>Supersede No</td>
                        <td>'.$row["supersede_no"].'</td>
                    </tr>
                    <tr>
                        <td>Sample Qty</td>	
                        <td>'.$row['sample_qty'].'</td>
                        <td>Shelf Life</td>	
                        <td>'.$row['shelf_life'].'</td>
                    </tr>
                    <tr>
                        <td>SAP No</td>
                        <td>'.$row['sap_no'].'</td>	
                        <td>Storage</td>
                        <td>'.$row['storage'].'</td>
                    </tr>
                </table>
                <h2 style="text-align: center;">Tests:</h2>';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $counter = 1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $method_details = json_decode($row1["method_details"]);
                        $j =0;
                        $html.='<br><br><b>'.$row1['test'].' : '.$row1['subtest'].'</b><br><br>';
                        for($i=1; $i<=count($method_details); $i++){
                            $data = $method_details[$j];
                            if($data->name == 'Procedure / method Description'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Procedure / method Description</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td></td>
                                            <td>'.$listdata->test_description.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Chemical / Reagents'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Chemical Name</td>
                                        <td style="width:30%;">Grade</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->chemical_name.'</td>
                                            <td>'.$listdata->grade.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Equipments / Instruments'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:25%;">Equipment Name</td>
                                        <td style="width:25%;">Category</td>
                                        <td style="width:25%;">Type</td>
                                        <td style="width:20%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->equipment_name.'</td>
                                            <td>'.$listdata->category.'</td>
                                            <td>'.$listdata->equipment_type.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'HPLC Column'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">HPLC Name</td>
                                        <td style="width:30%;">Packing</td>
                                        <td style="width:30%;">Brand</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->packing.'</td>
                                            <td>'.$listdata->brand.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Glasswares'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Glassware Name</td>
                                        <td style="width:30%;">Capacity</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->capacity.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Calculations'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Dilutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Volumetric Solutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                        }
                    }
                }
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('moa.pdf', 'I');
        }
        
    }
    else if($_GET['type'] == 'FinishMOAdigital'){
        $sql = "SELECT * FROM specification WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $_GET['filename'] = 'Finish Product MOA'; $_GET['pdftype'] = 'headfootdigital';  include("../pdfimp.php");
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                $html.='
                <style>td {border:solid 1px BCBBBA;}</style>
                <h3 style="text-align:center;">Finish Product MOA Report</h3>
                <table cellpadding="5" style="text-align:left;">
                    <tr>
                        <td style="width:20%">Department</td>
                        <td style="width:30%">Quality Control Department</td>
                        <td style="width:20%">Material Code:</td>
                        <td style="width:30%">'.$row["material_code"].'</td>
                    </tr>
                    <tr>
                        <td>Product Name.</td>
                        <td>'.$row["product_name"].'</td>
                        <td>Material Grade.</td>
                        <td>'.$row["grade"].'</td>
                    </tr>
                    <tr>
                        <td>Material Type</td>
                        <td>'.$row["material_type"].'</td>
                        <td>Supersede No</td>
                        <td>'.$row["supersede_no"].'</td>
                    </tr>
                    <tr>
                        <td>Sample Qty</td>	
                        <td>30 mg	</td>
                        <td>Shelf Life</td>	
                        <td>22</td>
                    </tr>
                    <tr>
                        <td>SAP No</td>
                        <td>4654156</td>	
                        <td>Storage</td>
                        <td>TEST</td>
                    </tr>
                </table>
                <h2 style="text-align: center;">Tests:</h2>';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $counter = 1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $method_details = json_decode($row1["method_details"]);
                        $j =0;
                        $html.='<br><br><b>'.$row1['test'].' : '.$row1['subtest'].'</b><br><br>';
                        for($i=1; $i<=count($method_details); $i++){
                            $data = $method_details[$j];
                            if($data->name == 'Procedure / method Description'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Procedure / method Description</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td></td>
                                            <td>'.$listdata->test_description.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Chemical / Reagents'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Chemical Name</td>
                                        <td style="width:30%;">Grade</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->chemical_name.'</td>
                                            <td>'.$listdata->grade.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Equipments / Instruments'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:25%;">Equipment Name</td>
                                        <td style="width:25%;">Category</td>
                                        <td style="width:25%;">Type</td>
                                        <td style="width:20%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->equipment_name.'</td>
                                            <td>'.$listdata->category.'</td>
                                            <td>'.$listdata->equipment_type.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'HPLC Column'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">HPLC Name</td>
                                        <td style="width:30%;">Packing</td>
                                        <td style="width:30%;">Brand</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->packing.'</td>
                                            <td>'.$listdata->brand.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Glasswares'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Glassware Name</td>
                                        <td style="width:30%;">Capacity</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->capacity.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Calculations'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Dilutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Volumetric Solutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                        }
                    }
                }
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('moa.pdf', 'I');
        }
        
    }
    else if($_GET['type'] == 'FinishMOALog'){
        $_GET['filename'] = 'FINISH PRODUCT MOA LOG'; $_GET['pdftype'] = 'landscape';  include("../pdfimp.php");
        $html.='
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;">
                <td style="width:5%;">Sr No.</td>
                <td style="width:10%;">Product Code</td>
                <td style="width:25%;">Product Name</td>
                <td style="width:15%;">Grade</td>
                <td style="width:15%;">Specification No</td>
                <td style="width:15%;">Status</td>
                <td style="width:15%;">Prepared By</td>
            </tr>';
            if($_GET['fromdate'] != '' && $_GET['product_code'] != ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND ismoa='approve' AND product_code='".$_GET['product_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] != '' && $_GET['product_code'] == ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND ismoa='approve' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] == '' && $_GET['product_code'] != ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND ismoa='approve' AND product_code='".$_GET['product_code']."'";
            }else{
                $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND ismoa='approve'";
            }
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $counter = 1;
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["product_name"] = $row1["product_name"];
                            $row["grade"] = $row1["grade"];
                            $row["generic_name"] = $row1["generic_name"];
                        }
                    }
                $html.='
                <tr>
                    <td>'.$counter++.'</td>
                    <td>'.$row["product_code"].'</td>
                    <td>'.$row["product_name"].'</td>
                    <td>'.$row["grade"].'</td>
                    <td>'.$row["specification_no"].'</td>
                    <td>'.$row["status"].'</td>
                    <td>'.$row["entry_by"].'</td>
                </tr>';
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('moa.pdf', 'I');
    }
    
    else if($_GET['type'] == 'InprocessMOA'){
         $_GET['filename'] = 'Raw Material MOA Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
       $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
         
          $html.='
          <h3>Inprocess Material MOA Report</h3>
          <table cellpadding="5" border="0.1">
         
          <tr>
          <td style="width:20%;"><b>Specification No</b></td>
          <td style="width:30%;">'.$row['specification_no'].'</td>
          <td style="width:20%;"><b>Specification Type</b></td>
          <td style="width:30%;">'.$row['spec_type'].'</td>
          </tr>
          <tr>
          <td style="width:20%;"><b>Version No</b></td>
          <td style="width:30%;">'.$row['version_no'].'</td>
          <td style="width:20%;"><b>Supersede No</b></td>
          <td style="width:30%;">'.$row['supersede_no'].'</td>
          </tr>
          <tr>
          <td style="width:20%;"><b>Sample Qty</b></td>
          <td style="width:80%;">'.$row['sample_qty'].'</td>
          
          </tr>
          <tr>
          <td style="width:20%;"><b>Product Name</b></td>
          <td style="width:30%;">'.$row1['product_name'].'</td>
          <td style="width:20%;"><b>Product Code</b></td>
          <td style="width:30%;">'.$row1['product_code'].'</td>
          </tr>
          <tr>
          <td style="width:20%;"><b>Generic Name</b></td>
          <td style="width:30%;">'.$row1['generic_name'].'</td>
          <td style="width:20%;"><b>Reference</b></td>
          <td style="width:30%;">'.$row1['grade'].'</td>
          </tr>';
                    }
                }
          $html.=' </table>
           <div></div>
            <h3>Tests:</h3>
             
              
         <table cellpadding="5" border="1">
                <tr style="text-align:center;background-color:#DDDAD9;">
                  
                    <td style="width:20%; text-align:centre;"><b>Test</b></td>
                    <td style="width:20%; text-align:centre;"><b>Sub Test</b></td>
                    <td style="width:20%; text-align:centre;"><b>Description</b></td>
                    <td style="width:20%; text-align:centre;"><b>Reference Type</b></td>
                     <td style="width:20%; text-align:centre;"><b>Sample Qty</b></td>
                     </tr>';
                  $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
             if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                 $html.='    <tr>
                   <td style="width:20%; text-align:centre;">'.$row1['test'].'</td>
                    <td style="width:20%; text-align:centre;">'.$row1['subtest'].'</td>
                    <td style="width:20%; text-align:centre;">'.$row1['description'].'</td>
                    <td style="width:20%; text-align:centre;">'.$row1['reference_type'].'</td>
                     <td style="width:20%; text-align:centre;">'.$row1['sample_qty'].'</td>
                    
                </tr>';
                }
                }
    }
}
            $html.=' </table>
           <div></div> <div></div> <div></div> <div></div> <div></div>
        
           <table cellpadding="5" border="0.1">
           <tr style="text-align:center;background-color:#DDDAD9;">
             <td style="width:25%"> </td>
                    <td style="width:25%"><b>Prepared by</b></td>
                    <td style="width:25%"><b>Checked By</b></td>
                    <td style="width:25%"><b>Approved By</b></td>
                </tr>
           <tr>
           <td style="width:25%"><b>Name</b></td>
            <td style="width:25%"></td>
             <td style="width:25%"></td>
              <td style="width:25%"></td>
              </tr>
              <tr>
           <td style="width:25%"><b>Date</b></td>
            <td style="width:25%"></td>
             <td style="width:25%"></td>
               <td style="width:25%"></td>
              </tr>
              <tr>
           <td style="width:25%"><b>Sign</b></td>
            <td style="width:25%"></td>
             <td style="width:25%"></td>
             <td style="width:25%"></td>
              </tr>
        </table>';
            
            
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('moa.pdf', 'I');
       
        
    
   } else if($_GET['type'] == 'InprocessMOAdigital'){
        $_GET['filename'] = 'Raw Material MOA Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
       $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
         
          $html.='
          <h3>Inprocess Material MOA Report</h3>
          <table cellpadding="5" border="0.1">
         
          <tr>
          <td style="width:20%;"><b>Specification No</b></td>
          <td style="width:30%;">'.$row['specification_no'].'</td>
          <td style="width:20%;"><b>Specification Type</b></td>
          <td style="width:30%;">'.$row['spec_type'].'</td>
          </tr>
          <tr>
          <td style="width:20%;"><b>Version No</b></td>
          <td style="width:30%;">'.$row['version_no'].'</td>
          <td style="width:20%;"><b>Supersede No</b></td>
          <td style="width:30%;">'.$row['supersede_no'].'</td>
          </tr>
          <tr>
          <td style="width:20%;"><b>Sample Qty</b></td>
          <td style="width:80%;">'.$row['sample_qty'].'</td>
          
          </tr>
          <tr>
          <td style="width:20%;"><b>Product Name</b></td>
          <td style="width:30%;">'.$row1['product_name'].'</td>
          <td style="width:20%;"><b>Product Code</b></td>
          <td style="width:30%;">'.$row1['product_code'].'</td>
          </tr>
          <tr>
          <td style="width:20%;"><b>Generic Name</b></td>
          <td style="width:30%;">'.$row1['generic_name'].'</td>
          <td style="width:20%;"><b>Reference</b></td>
          <td style="width:30%;">'.$row1['grade'].'</td>
          </tr>';
                    }
                }
          $html.=' </table>
           <div></div>
            <h3>Tests:</h3>
             
              
         <table cellpadding="5" border="1">
                <tr style="text-align:center;background-color:#DDDAD9;">
                  
                    <td style="width:20%; text-align:centre;"><b>Test</b></td>
                    <td style="width:20%; text-align:centre;"><b>Sub Test</b></td>
                    <td style="width:20%; text-align:centre;"><b>Description</b></td>
                    <td style="width:20%; text-align:centre;"><b>Reference Type</b></td>
                     <td style="width:20%; text-align:centre;"><b>Sample Qty</b></td>
                     </tr>';
                  $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
             if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                 $html.='    <tr>
                   <td style="width:20%; text-align:centre;">'.$row1['test'].'</td>
                    <td style="width:20%; text-align:centre;">'.$row1['subtest'].'</td>
                    <td style="width:20%; text-align:centre;">'.$row1['description'].'</td>
                    <td style="width:20%; text-align:centre;">'.$row1['reference_type'].'</td>
                     <td style="width:20%; text-align:centre;">'.$row1['sample_qty'].'</td>
                    
                </tr>';
                }
                }
    }
}
            $html.=' </table>
           <div></div> <div></div> <div></div> <div></div> <div></div>
        
           <table cellpadding="5" border="0.1">
           <tr style="text-align:center;background-color:#DDDAD9;">
             <td style="width:25%"> </td>
                    <td style="width:25%"><b>Prepared by</b></td>
                    <td style="width:25%"><b>Checked By</b></td>
                    <td style="width:25%"><b>Approved By</b></td>
                </tr>
           <tr>
           <td style="width:25%"><b>Name</b></td>
            <td style="width:25%"></td>
             <td style="width:25%"></td>
              <td style="width:25%"></td>
              </tr>
              <tr>
           <td style="width:25%"><b>Date</b></td>
            <td style="width:25%"></td>
             <td style="width:25%"></td>
               <td style="width:25%"></td>
              </tr>
              <tr>
           <td style="width:25%"><b>Sign</b></td>
            <td style="width:25%"></td>
             <td style="width:25%"></td>
             <td style="width:25%"></td>
              </tr>
        </table>';
            
            
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('moa.pdf', 'I');
    }
    else if($_GET['type'] == 'InprocessMOALog'){
        $_GET['filename'] = 'Inprocess Product MOA'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <style>td {border:solid 1px BCBBBA;}</style>
        <table cellpadding="5">
            <tr>
                <td colspan="6" style="border:none;text-align:center;"><b>INPROCESS PRODUCT MOA LOG</b></td>
            </tr>
            <tr style="background-color:#DDDAD9;">
                <td style="width:5%;">Sr No.</td>
                <td style="width:10%;">Product Code</td>
                <td style="width:25%;">Product Name</td>
                <td style="width:15%;">Grade</td>
                <td style="width:15%;">Specification No</td>
                <td style="width:15%;">Status</td>
                <td style="width:15%;">Prepared By</td>
            </tr>';
            if($_GET['fromdate'] != '' && $_GET['product_code'] != ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve' AND product_code='".$_GET['product_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] != '' && $_GET['product_code'] == ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
            } else if($_GET['fromdate'] == '' && $_GET['product_code'] != ''){
                $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve' AND product_code='".$_GET['product_code']."'";
            }else{
                $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve'";
            }
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $counter = 1;
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["product_name"] = $row1["product_name"];
                            $row["grade"] = $row1["grade"];
                            $row["generic_name"] = $row1["generic_name"];
                        }
                    }
                $html.='
                <tr>
                    <td>'.$counter++.'</td>
                    <td>'.$row["product_code"].'</td>
                    <td>'.$row["product_name"].'</td>
                    <td>'.$row["grade"].'</td>
                    <td>'.$row["specification_no"].'</td>
                    <td>'.$row["status"].'</td>
                    <td>'.$row["entry_by"].'</td>
                </tr>';
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('moa.pdf', 'I');
    }
    
    else if($_GET['type'] == 'RetestMOA'){
        $sql = "SELECT * FROM specification WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $_GET['filename'] = 'Retest Product MOA'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp.php");
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                $html.='
                <style>td {border:solid 1px BCBBBA;}</style>
                <h3 style="text-align:center;">Retest MOA Report</h3>
                <table cellpadding="5" style="text-align:left;">
                    <tr>
                        <td style="width:20%">Department</td>
                        <td style="width:30%">Quality Control Department</td>
                        <td style="width:20%">Material Code:</td>
                        <td style="width:30%">'.$row1["material_code"].'</td>
                    </tr>
                    <tr>
                        <td>Material Name.</td>
                        <td>'.$row1["material_name"].'</td>
                        <td>Material Grade.</td>
                        <td>'.$row1["grade"].'</td>
                    </tr>
                    <tr>
                        <td>Material Type</td>
                        <td>'.$row1["material_subtype"].'</td>
                        <td>Supersede No</td>
                        <td>'.$row["supersede_no"].'</td>
                    </tr>
                    <tr>
                        <td>Sample Qty</td>	
                        <td>'.$row['sample_qty'].'</td>
                        <td>Shelf Life</td>	
                        <td>'.$row['shelf_life'].'</td>
                    </tr>
                    <tr>
                        <td>SAP No</td>
                        <td>'.$row['sap_no'].'</td>	
                        <td>Storage</td>
                        <td>'.$row['storage'].'</td>
                    </tr>
                </table>
                <h2 style="text-align: center;">Tests:</h2>';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $counter = 1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $method_details = json_decode($row1["method_details"]);
                        $j =0;
                        $html.='<br><br><b>'.$row1['test'].' : '.$row1['subtest'].'</b><br><br>';
                        for($i=1; $i<=count($method_details); $i++){
                            $data = $method_details[$j];
                            if($data->name == 'Procedure / method Description'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Procedure / method Description</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td></td>
                                            <td>'.$listdata->test_description.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Chemical / Reagents'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Chemical Name</td>
                                        <td style="width:30%;">Grade</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->chemical_name.'</td>
                                            <td>'.$listdata->grade.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Equipments / Instruments'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:25%;">Equipment Name</td>
                                        <td style="width:25%;">Category</td>
                                        <td style="width:25%;">Type</td>
                                        <td style="width:20%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->equipment_name.'</td>
                                            <td>'.$listdata->category.'</td>
                                            <td>'.$listdata->equipment_type.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'HPLC Column'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">HPLC Name</td>
                                        <td style="width:30%;">Packing</td>
                                        <td style="width:30%;">Brand</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->packing.'</td>
                                            <td>'.$listdata->brand.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Glasswares'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Glassware Name</td>
                                        <td style="width:30%;">Capacity</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->capacity.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Calculations'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Dilutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Volumetric Solutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                        }
                    }
                }
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('moa.pdf', 'I');
        }
        
    }
    else if($_GET['type'] == 'RetestMOAdigital'){
        $sql = "SELECT * FROM specification WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $_GET['filename'] = 'Retest Product MOA'; $_GET['pdftype'] = 'headfootdigital';  include("../pdfimp.php");
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                $row1 = $result1->fetch_assoc();
                        
                $html.='
                <style>td {border:solid 1px BCBBBA;}</style>
                <h3 style="text-align:center;">Retest MOA Report</h3>
                <table cellpadding="5" style="text-align:left;">
                    <tr>
                        <td style="width:20%">Department</td>
                        <td style="width:30%">Quality Control Department</td>
                        <td style="width:20%">Material Code:</td>
                        <td style="width:30%">'.$row1["material_code"].'</td>
                    </tr>
                    <tr>
                        <td>Material Name.</td>
                        <td>'.$row1["material_name"].'</td>
                        <td>Material Grade.</td>
                        <td>'.$row1["grade"].'</td>
                    </tr>
                    <tr>
                        <td>Material Type</td>
                        <td>'.$row1["material_subtype"].'</td>
                        <td>Supersede No</td>
                        <td>'.$row["supersede_no"].'</td>
                    </tr>
                    <tr>
                        <td>Sample Qty</td>	
                        <td>30 mg	</td>
                        <td>Shelf Life</td>	
                        <td>22</td>
                    </tr>
                    <tr>
                        <td>SAP No</td>
                        <td>4654156</td>	
                        <td>Storage</td>
                        <td>TEST</td>
                    </tr>
                </table>
                <h2 style="text-align: center;">Tests:</h2>';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $counter = 1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $method_details = json_decode($row1["method_details"]);
                        $j =0;
                        $html.='<br><br><b>'.$row1['test'].' : '.$row1['subtest'].'</b><br><br>';
                        for($i=1; $i<=count($method_details); $i++){
                            $data = $method_details[$j];
                            if($data->name == 'Procedure / method Description'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Procedure / method Description</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td></td>
                                            <td>'.$listdata->test_description.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Chemical / Reagents'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Chemical Name</td>
                                        <td style="width:30%;">Grade</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->chemical_name.'</td>
                                            <td>'.$listdata->grade.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Equipments / Instruments'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:25%;">Equipment Name</td>
                                        <td style="width:25%;">Category</td>
                                        <td style="width:25%;">Type</td>
                                        <td style="width:20%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->equipment_name.'</td>
                                            <td>'.$listdata->category.'</td>
                                            <td>'.$listdata->equipment_type.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'HPLC Column'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">HPLC Name</td>
                                        <td style="width:30%;">Packing</td>
                                        <td style="width:30%;">Brand</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->packing.'</td>
                                            <td>'.$listdata->brand.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Glasswares'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Glassware Name</td>
                                        <td style="width:30%;">Capacity</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->capacity.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Calculations'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Dilutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Volumetric Solutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                        }
                    }
                }
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('moa.pdf', 'I');
        }
        
    }
    else if($_GET['type'] == 'RetestMOALog'){
        $_GET['filename'] = 'RETEST MOA LOG'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <div></div> <div></div> 
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;">
                <td style="width:5%;">Sr No.</td>
                <td style="width:10%;">Material Code</td>
                <td style="width:25%;">Material Name</td>
                <td style="width:15%;">Grade</td>
                <td style="width:15%;">Specification No</td>
                <td style="width:15%;">Status</td>
                <td style="width:15%;">Prepared By</td>
            </tr>';
          
                $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND ismoa='approve'";
          
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $counter = 1;
                while ($row = $result->fetch_assoc()) {
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
                    <td>'.$row["material_code"].'</td>
                    <td>'.$row["material_name"].'</td>
                    <td>'.$row["grade"].'</td>
                    <td>'.$row["specification_no"].'</td>
                    <td>'.$row["status"].'</td>
                    <td>'.$row["entry_by"].'</td>
                </tr>';
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('moa.pdf', 'I');
    }
    
    else if($_GET['type'] == 'StabilityMOA'){
       
        $_GET['filename'] = 'Raw Material MOA Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        
         $html.='
        <h3>Raw Material MOA Report</h3>
        <table cellpadding="5" border="0.1">';
      
         $html.=' ';
          $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND ismoa='approve'";
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                 $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                        
            $html.=' <tr >
         
         
       <td style="width:20%;background-color:#DDDAD9;"><b>Specification No</b></td>
       <td style="width:30%;">'.$row['specification_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Specification Type</b></td>
          <td style="width:30%;">'.$row['spec_type'].'</td>
        </tr>
         
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Version No</b></td>
          <td style="width:30%;">'.$row['version_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Supersede No</b></td>
           <td style="width:30%;">'.$row['supersede_no'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Product Name</b></td>
         <td style="width:30%;">'.$row['product_name'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Product Code</b></td>
          <td style="width:30%;">'.$row['product_code'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Generic Name</b></td>
         <td style="width:30%;">'.$row['generic_name'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Reference</b></td>
           <td style="width:30%;">'.$row['grade'].'</td>
        </tr>';
         
                   
         
           $html.=' </table>
           <div></div>
           <h3>Tests:</h3>
           <table cellpadding="5" border="0.1">
            <tr style="text-align:center;background-color:#DDDAD9;">
           <td style="width:20%;text-align:center"><b>Test</b></td>
            <td style="width:20%;text-align:center"><b>Sub Test</b></td>
             <td style="width:20%;text-align:center"><b>Description</b></td>
              <td style="width:20%;text-align:center"><b>Refernce Type</b></td>
               <td style="width:20%;text-align:center"><b>Sample Qty</b></td>
           </tr>';
                    
                
         $html.=' <tr>
           <td style="width:20%">'.$row2['test'].'</td>
            <td style="width:20%">'.$row2['subtest'].'</td>
             <td style="width:20%">'.$row2['description'].'</td>
              <td style="width:20%">'.$row2['reference_type'].'</td>
               <td style="width:20%">'.$row2['sample_qty'].'</td>
           </tr>';
                    
                    }
                }
     }}}
    }   
          $html.=' </table>
           
          <div></div> <div></div> <div></div> <div></div> <div></div>
        
           <table cellpadding="5" border="0.1">
           <tr style="text-align:center;background-color:#DDDAD9;">
             <td style="width:15%"> </td>
                    <td style="width:30%;text-align:center"><b>Prepared by</b></td>
                    <td style="width:25%;text-align:center"><b>Checked By</b></td>
                    <td style="width:30%;text-align:center"><b>Approved By</b></td>
                </tr>
           <tr>
           <td style="width:15%;text-align:center"><b>Name</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
              <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Date</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
               <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Sign</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
             <td style="width:30%"></td>
              </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Raw MOA.pdf', 'I');
    
 
   } else if($_GET['type'] == 'StabilityMOAdigital'){
        $sql = "SELECT * FROM specification WHERE id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $_GET['filename'] = 'Stability MOA Report'; $_GET['pdftype'] = 'headfootdigital';  include("../pdfimp.php");
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                $html.='
                <table cellpadding="5" style="text-align:left;">
                    <tr>
                        <td style="width:20%">Department</td>
                        <td style="width:30%">Quality Control Department</td>
                        <td style="width:20%">Material Code:</td>
                        <td style="width:30%">'.$row["material_code"].'</td>
                    </tr>
                    <tr>
                        <td>Product Name.</td>
                        <td>'.$row["product_name"].'</td>
                        <td>Material Grade.</td>
                        <td>'.$row["grade"].'</td>
                    </tr>
                    <tr>
                        <td>Material Type</td>
                        <td>'.$row["material_type"].'</td>
                        <td>Supersede No</td>
                        <td>'.$row["supersede_no"].'</td>
                    </tr>
                    <tr>
                        <td>Sample Qty</td>	
                        <td>30 mg	</td>
                        <td>Shelf Life</td>	
                        <td>22</td>
                    </tr>
                    <tr>
                        <td>SAP No</td>
                        <td>4654156</td>	
                        <td>Storage</td>
                        <td>TEST</td>
                    </tr>
                </table>
                <h2 style="text-align: center;">Tests:</h2>';
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    $counter = 1;
                    while ($row1 = $result1->fetch_assoc()) {
                        $method_details = json_decode($row1["method_details"]);
                        $j =0;
                        $html.='<br><br><b>'.$row1['test'].' : '.$row1['subtest'].'</b><br><br>';
                        for($i=1; $i<=count($method_details); $i++){
                            $data = $method_details[$j];
                            if($data->name == 'Procedure / method Description'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Procedure / method Description</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td></td>
                                            <td>'.$listdata->test_description.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Chemical / Reagents'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Chemical Name</td>
                                        <td style="width:30%;">Grade</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->chemical_name.'</td>
                                            <td>'.$listdata->grade.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Equipments / Instruments'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:25%;">Equipment Name</td>
                                        <td style="width:25%;">Category</td>
                                        <td style="width:25%;">Type</td>
                                        <td style="width:20%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->equipment_name.'</td>
                                            <td>'.$listdata->category.'</td>
                                            <td>'.$listdata->equipment_type.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'HPLC Column'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">HPLC Name</td>
                                        <td style="width:30%;">Packing</td>
                                        <td style="width:30%;">Brand</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->packing.'</td>
                                            <td>'.$listdata->brand.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Glasswares'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Glassware Name</td>
                                        <td style="width:30%;">Capacity</td>
                                        <td style="width:30%;">Make</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                            <td>'.$listdata->capacity.'</td>
                                            <td>'.$listdata->make.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Calculations'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:95%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Dilutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                            if($data->name == 'Volumetric Solutions'){
                                $list = $data->list;
                                $html.='
                                <table cellpadding="5" nobr="true">
                                    <tr>
                                        <td style="border:none;"><b>'.$data->name.'</b></td>
                                    </tr>
                                    <tr>
                                        <td style="width:5%;">Sr.</td>
                                        <td style="width:35%;">Calculation Formula</td>
                                    </tr>';
                                    $k = 0;
                                    for($a=1; $a<=count($list); $a++){
                                    $listdata = $list[$k];
                                    $html.='
                                        <tr>
                                            <td>'.$a.'</td>
                                            <td>'.$listdata->name.'</td>
                                        </tr>';
                                        $k++;
                                    }
                                $html.='
                                </table>';
                                $j++;
                            }
                        }
                    }
                }
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('moa.pdf', 'I');
        }
    }
    else if($_GET['type'] == 'StabilityMOALog'){
        $_GET['filename'] = 'STABILITY MOA LOG'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;">
                <td style="width:5%;">Sr No.</td>
                <td style="width:10%;">Product Code</td>
                <td style="width:25%;">Product Name</td>
                <td style="width:15%;">Grade</td>
                <td style="width:15%;">Specification No</td>
                <td style="width:15%;">Status</td>
                <td style="width:15%;">Prepared By</td>
            </tr>';
            
                $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND ismoa='approve'";
            
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $counter = 1;
                while ($row = $result->fetch_assoc()) {
                    $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["product_name"] = $row1["product_name"];
                            $row["grade"] = $row1["grade"];
                            $row["generic_name"] = $row1["generic_name"];
                        }
                    }
                $html.='
                <tr>
                    <td>'.$counter++.'</td>
                    <td>'.$row["product_code"].'</td>
                    <td>'.$row["product_name"].'</td>
                    <td>'.$row["grade"].'</td>
                    <td>'.$row["specification_no"].'</td>
                    <td>'.$row["status"].'</td>
                    <td>'.$row["entry_by"].'</td>
                </tr>';
                }
            }
            $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('moa.pdf', 'I');
    }
}
?>