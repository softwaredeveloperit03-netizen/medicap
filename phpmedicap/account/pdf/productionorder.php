<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
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
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

   $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR,frontend_url) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."','".$currentUrl."')";
    $conn->query($sql);
    
    if ($_GET["type"] == "downloadPOReport") {
        $_GET['filename'] = 'Production order'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="3">
                    <tr>
                        <td style="width:50%;">Order No:</td>
                        <td style="width:50%;">Date:</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">Bayer Name:</td>
                        <td style="width:50%;">Exporter Name:</td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:15%;">Prod/Brand Name</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Generic Name</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Product Permission</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Specification</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Quantity</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:15%;">Self Life(MST)</td>
                                    <td style="width:35%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Total Quantity</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:15%;">Marking(MST)</td>
                                    <td style="width:35%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Product Master No</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:15%;">Specifica(MST)</td>
                                    <td style="width:35%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold; border: solid 1px black">Final Packing</td>
                    </tr>
                    <tr style="font-weight:bold; border: solid 1px black">
                        <td style="width:55%;">Item Name</td>
                        <td style="width:15%;">Quantity</td>
                        <td style="width:15%;">Case Pack</td>
                        <td style="width:15%;">M.R.P</td>
                    </tr>
                    <tr>
                        <td style="width:55%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:15%;">Inner Packing</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:15%;">Outer Packing</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:15%;">Shipping Mark</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">From</td>
                                    <td style="width:45%;">:WEST COAST PHARMACEUTICAL WORKS LTD</td>
                                    <td style="width:10%;">Mst.MRP</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:10%;">New MRP</td>
                                    <td style="width:10%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Samples</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:20%;">MRP of Last Batches</td>
                                    <td style="width:30%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Box/Label</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Inspection By</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:15%;">Label/Box Art Work Code No</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:15%;">Pre-Batch No Printing</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:15%;">Remarks </td>
                                    <td style="width:85%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:15%;">Ins. To Desp. Dept</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Ins. To R.M.Dept</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Ins. To  P.M. Dest</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Ins. To Prod. Dept</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:100%;">S.O.P</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">1. All fields must be filled with full details. If it is not Applicable, Please mention Not Applicable but do not keep it blank.</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">2. Export/Mkt Incharge can keep one copy for his record</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">Signature must be done after verification and they must be forwarded or to be taken with signature on it.</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">Any correction must be done on original copy only which is lying with the Production Incharge and it must be done in the presence of the production Incharge with Signature</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:30%;font-weight:bold;">Export/Sales/Cont.Dept</td>
                        <td style="width:30%;font-weight:bold;">CEO-Pharma Operation </td>
                        <td style="width:30%;font-weight:bold;">Production Incharge</td>
                    </tr>
                    <tr>
                        <td style="width:10%;"><b>Sign</b></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;"><b>Date</b></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                    </tr>
                </table>
                ';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('POReport.pdf', 'I');
    }else  if ($_GET["type"] == "RMStorePO") {
        $_GET['filename'] = 'RMStorePO'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="3">
                    <tr>
                        <td style="width:50%;">Order No:</td>
                        <td style="width:50%;">Date:</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">Bayer Name:</td>
                        <td style="width:50%;">Exporter Name:</td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:15%;">Prod/Brand Name</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Generic Name</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Product Permission</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Specification</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Quantity</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:15%;">Self Life(MST)</td>
                                    <td style="width:35%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Total Quantity</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:15%;">Marking(MST)</td>
                                    <td style="width:35%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Product Master No</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:15%;">Specifica(MST)</td>
                                    <td style="width:35%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold; border: solid 1px black">Final Packing</td>
                    </tr>
                    <tr style="font-weight:bold; border: solid 1px black">
                        <td style="width:55%;">Item Name</td>
                        <td style="width:15%;">Quantity</td>
                        <td style="width:15%;">Case Pack</td>
                        <td style="width:15%;">M.R.P</td>
                    </tr>
                    <tr>
                        <td style="width:55%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:25%;">Inner Packing</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Outer Packing</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Shipping Mark</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">From</td>
                                    <td style="width:75%;">:WEST COAST PHARMACEUTICAL WORKS LTD</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Samples</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Box/Label</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Inspection By</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Label/Box Art Work Code No</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Pre-Batch No Printing</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Ins. To R.M.Dept</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold;">Raw Material Requirement Statement</td>
                    </tr>
                    <tr style="width:100%;font-weight:bold; border: solid 1px black">
                        <td style="width:40%;">Material Name</td>
                        <td style="width:15%;">Qty Reqd</td>
                        <td style="width:15%;">Stock Qty.</td>
                        <td style="width:15%;">Qty To Pur</td>
                        <td style="width:15%;">Pending P. O.</td>
                    </tr>
                    <tr>
                        <td style="width:40%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:100%;">S.O.P</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">1. R.M Store Incharge should check R.M.Position and immediately send the active R.M. on the same day with signature of Production Incharge.</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">2. Requirement of excipients should place immediately without approval of Production Incharge.</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">3. R.M Store Incharge must discuss all this procedures with Production Incharge.</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">4. R.M.Store Incharge keep all this procedure in his individual file.</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:12%;"></td>
                        <td style="width:22%;font-weight:bold;">Export/Sales/Cont.Dept</td>
                        <td style="width:22%;font-weight:bold;">CEO-Pharma Operation </td>
                        <td style="width:22%;font-weight:bold;">Production Incharge</td>
                        <td style="width:22%;font-weight:bold;">R.M.Store I/C</td>
                    </tr>
                    <tr>
                        <td style="width:12%;"><b>Sign</b></td>
                        <td style="width:22%;"></td>
                        <td style="width:22%;"></td>
                        <td style="width:22%;"></td>
                        <td style="width:22%;"></td>
                    </tr>
                    <tr>
                        <td style="width:12%;"><b>Date</b></td>
                        <td style="width:22%;"></td>
                        <td style="width:22%;"></td>
                        <td style="width:22%;"></td>
                        <td style="width:22%;"></td>
                    </tr>
                </table>
                ';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('RMStorePO.pdf', 'I');
    }else  if ($_GET["type"] == "PMStorePO") {
        $_GET['filename'] = 'PMStorePO'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="3">
                    <tr>
                        <td style="width:50%;">Order No:</td>
                        <td style="width:50%;">Date:</td>
                    </tr>
                    <tr>
                        <td style="width:50%;">Bayer Name:</td>
                        <td style="width:50%;">Exporter Name:</td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:15%;">Prod/Brand Name</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Generic Name</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Product Permission</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Specification</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Quantity</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:15%;">Self Life(MST)</td>
                                    <td style="width:35%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Total Quantity</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:15%;">Marking(MST)</td>
                                    <td style="width:35%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Product Master No</td>
                                    <td style="width:35%;">:</td>
                                    <td style="width:15%;">Specifica(MST)</td>
                                    <td style="width:35%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold; border: solid 1px black">Final Packing</td>
                    </tr>
                    <tr style="font-weight:bold; border: solid 1px black">
                        <td style="width:55%;">Item Name</td>
                        <td style="width:15%;">Quantity</td>
                        <td style="width:15%;">Case Pack</td>
                        <td style="width:15%;">M.R.P</td>
                    </tr>
                    <tr>
                        <td style="width:55%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:25%;">Inner Packing</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Outer Packing</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Shipping Mark</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">From</td>
                                    <td style="width:75%;">:WEST COAST PHARMACEUTICAL WORKS LTD</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Samples</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Box/Label</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Inspection By</td>
                                    <td style="width:85%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Label/Box Art Work Code No</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Pre-Batch No Printing</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Ins. To P.M.Dept</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold;">Packing Material </td>
                    </tr>
                    <tr style="width:100%;font-weight:bold; border: solid 1px black">
                        <td style="width:40%;">Material Name</td>
                        <td style="width:15%;">Qty Reqd</td>
                        <td style="width:15%;">Stock Qty.</td>
                        <td style="width:15%;">Qty To Pur</td>
                        <td style="width:15%;">Pending P. O.</td>
                    </tr>
                    <tr>
                        <td style="width:40%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                        <td style="width:15%;"></td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:100%;">S.O.P</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">1. P.M Store Incharge must place order on the same day on receipt of this production order.</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">2. Production Order must be filled in seperate file.</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">3. Everyday discussion should be done with the Production Incharge.</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">4. Second copy of P.M.Store copy must be sent to Production Incharge with P.M.sample after complete Store available. </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:5%;"></td>
                        <td style="width:23%;font-weight:bold;">Export/Sales/Cont.Dept</td>
                        <td style="width:18%;font-weight:bold;">CEO-Pharma Operation </td>
                        <td style="width:18%;font-weight:bold;">Production Incharge</td>
                        <td style="width:18%;font-weight:bold;">Inv. I/C</td>
                        <td style="width:18%;font-weight:bold;">P.M. Incharge</td>
                    </tr>
                    <tr>
                        <td style="width:5%;"><b>Sign</b></td>
                        <td style="width:23%;"></td>
                        <td style="width:18%;"></td>
                        <td style="width:18%;"></td>
                        <td style="width:18%;"></td>
                        <td style="width:18%;"></td>
                    </tr>
                    <tr>
                        <td style="width:5%;"><b>Date</b></td>
                        <td style="width:23%;"></td>
                        <td style="width:18%;"></td>
                        <td style="width:18%;"></td>
                        <td style="width:18%;"></td>
                        <td style="width:18%;"></td>
                    </tr>
                </table>
                ';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('PMStorePO.pdf', 'I');
    }else  if ($_GET["type"] == "DispatchDepartPO") {
        $_GET['filename'] = 'DispatchDepartPO'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="3">
                    <tr>
                        <td style="width:50%;">Order No:</td>
                        <td style="width:50%;">Date:</td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:20%;">Prod/Brand Name</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Generic Name</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Product Permission</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Specification</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Size of Tab/Cap</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Product Master No</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Manufactured Under</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Buyer Name</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Manufactured By</td>
                                    <td style="width:80%;">:WEST COAST PHARMACEUTICAL WORKS LTD</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Special Remarks</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold; border: solid 1px black">Final Packing</td>
                    </tr>
                    <tr style="font-weight:bold; border: solid 1px black">
                        <td style="width:50%;">Item Name</td>
                        <td style="width:10%;">Quantity</td>
                        <td style="width:10%;">Bill Pr. Excl. Ex.</td>
                        <td style="width:10%;">Ex. Appl.</td>
                        <td style="width:10%;">Bill. Pr. Incl. Ex.</td>
                        <td style="width:10%;">C. Pack</td>
                    </tr>
                    <tr>
                        <td style="width:50%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold;">Ins. To Desp. Dept :</td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:25%;">Sales Tax Applicable (Yes/No)</td>
                                    <td style="width:25%;">:</td>
                                    <td style="width:25%;">Freight To Add (Yes/No)</td>
                                    <td style="width:25%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Suggested Transport</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                 <tr>
                                    <td style="width:25%;">Despatch At</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Document To</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Terms Of Payment</td>
                                    <td style="width:75%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:25%;">Contact Person</td>
                                    <td style="width:25%;">:</td>
                                    <td style="width:25%;">Phone No</td>
                                    <td style="width:25%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold;">C.C To : Account Department </td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:100%;">1. Seperate file to be maintained for this despatch instructions</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">2. These instructions are mainly for Loan Lic. job Work, P To P Sale and Contract Manufacturing products and Govt.Supply.</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">3. For any query in this format, Please contact the Contract Mfg. Department,C.E.O.Pharma Operation or Director.</td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">4. Without signature, Despatch Incharge will not take this letter.</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:30%;font-weight:bold;">Sales/Export/cont. I/C</td>
                        <td style="width:30%;font-weight:bold;">CEO-Pharma Operation </td>
                        <td style="width:30%;font-weight:bold;">Dispatch Incharge</td>
                    </tr>
                    <tr>
                        <td style="width:10%;"><b>Sign</b></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;"><b>Date</b></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                    </tr>
                </table>
                ';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('DispatchDepartPO.pdf', 'I');
    }else  if ($_GET["type"] == "QcDepartPO") {
        $_GET['filename'] = 'QcDepartPO'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="3">
                    <tr>
                        <td style="width:100%;">Intimation To Q.C. Dept. (Following Product is Going To Manufacture)</td>
                    </tr>
                    <tr>
                        <td style="width:100%;">
                            <table>
                                <tr>
                                    <td style="width:20%;">Prod/Brand Name</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Generic Name</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Product Permission</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Specification</td>
                                    <td style="width:80%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Size of Tab/Cap</td>
                                    <td style="width:30%;">:</td>
                                    <td style="width:20%;">Self Life (MST)</td>
                                    <td style="width:30%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Total Quantity</td>
                                    <td style="width:30%;">:</td>
                                    <td style="width:20%;">Marking</td>
                                    <td style="width:30%;">:</td>
                                </tr>
                                <tr>
                                    <td style="width:20%;">Product Master No</td>
                                    <td style="width:30%;">:</td>
                                    <td style="width:20%;">Specifica. (MST)</td>
                                    <td style="width:30%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold; border: solid 1px black">Final Packing</td>
                    </tr>
                    <tr style="font-weight:bold; border: solid 1px black">
                        <td style="width:50%;">Item Name</td>
                        <td style="width:10%;">Quantity</td>
                        <td style="width:10%;">Bill Pr. Excl. Ex.</td>
                        <td style="width:10%;">Ex. Appl.</td>
                        <td style="width:10%;">Bill. Pr. Incl. Ex.</td>
                        <td style="width:10%;">C. Pack</td>
                    </tr>
                    <tr>
                        <td style="width:50%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                        <td style="width:10%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:30%;font-weight:bold;">Sales/Export/cont. I/C</td>
                        <td style="width:30%;font-weight:bold;">CEO-Pharma Operation </td>
                        <td style="width:30%;font-weight:bold;">Dispatch Incharge</td>
                    </tr>
                    <tr>
                        <td style="width:10%;"><b>Sign</b></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;"><b>Date</b></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                    </tr>
                </table>';
        $html.='<table border="1" cellpadding="3">
                    <tr>
                        <td style="width:100%;">
                            <table  cellpadding="3">
                                <tr>
                                    <td style="width:15%;">Product Name</td>
                                    <td style="width:85%;"></td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Material Name</td>
                                    <td style="width:85%;"></td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Production Qty</td>
                                    <td style="width:85%;"></td>
                                </tr>
                                <tr>
                                    <td style="width:15%;">Qty Required</td>
                                    <td style="width:25%;">:</td>
                                    <td style="width:15%;">Physical Stock</td>
                                    <td style="width:15%;">:</td>
                                    <td style="width:15%;">Qty To Purc </td>
                                    <td style="width:15%;">:</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:10%;"></td>
                        <td style="width:30%;font-weight:bold;">P.M. Store Incharge</td>
                        <td style="width:30%;font-weight:bold;">Prod. Coordinator</td>
                        <td style="width:30%;font-weight:bold;">Prod. Incharge</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;">Sign</td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;">Name</td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                        <td style="width:30%;"></td>
                    </tr>
                </table>
                ';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('QcDepartPO.pdf', 'I');
    }
    
    
}
$conn->close();
?>