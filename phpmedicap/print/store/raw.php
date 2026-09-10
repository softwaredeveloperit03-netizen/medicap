<?php
    require '/db.php';
    require '/token.php';
    require '/tcpdf/tcpdf.php';
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
    

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
}

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('/logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"]=="getStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%'AND m.grade LIKE '%".$_GET["grade"]."%' ORDER BY s.grn_no";
    	//echo $sql;
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }else if ($_GET["type"]=="getAllStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }if ($_GET["type"]=="getApprovedStock") {
        if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.status='Approved'ORDER BY id DESC";
    	$result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                
                $output1 = array();
                $sql1 = "SELECT material_code, grade FROM material WHERE material_name='".$row["material_name"]."' AND material_code!='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row["grades"] = $output1;
                $output[] = $row;
            }
        }
    	echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadStock") {
        $_GET['filename'] = "Change Grade"; $_GET['pdftype'] = 'onlyheader'; include("/pdfimp2.php");
        $html= "";
       
        $html.='
        <h2 style="text-align:center">Change Grade</h2>
        <table border="1" cellpadding="5">
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 5%;">Sr.</td>
                        <td style="width: 10%;">GRN No</td>	
                        <td style="width: 15%;">Material Type</td>
                        <td style="width: 15%;">Material code</td>
                        <td style="width: 15%;">Material Name</td>
                        <td style="width: 10%;">Grade</td>
                        <td style="width: 15%;">Batch No</td>
                        <td style="width: 15%;">Available Qty</td>
                    </tr>';
        $i=1;
       $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.status='Approved'ORDER BY id DESC";
       //$sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%'AND m.grade LIKE '%".$_GET["grade"]."%' ORDER BY s.grn_no";
       //$sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                    <td style="width: 5%;">'.$i.'</td>
                    <td style="width: 10%;">'.$row['grn_no'].'</td>
                    <td style="width: 15%;">'.$row['material_type'].'</td>
                    <td style="width: 15%;">'.$row['material_code'].'</td>
                    <td style="width: 15%;">'.$row['material_name'].'</td>
                    <td style="width: 10%;">'.$row['grade'].'</td>
                    <td style="width: 15%;">'.$row['batch_no'].'</td>
                    <td style="width: 15%;">'.$row['qty'].'<td>'.$row['unit'].'</td></td>
                </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('stock.pdf', 'I');
    }   else if ($_GET["type"] == "downloadRawQuarantine") {
        $_GET['filename'] = "Raw Material Quarantine Stock Book"; $_GET['pdftype'] = 'onlyheader'; include("/pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Raw Material Quarantine Stock Book</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 4%;">Sr.</td>
                        <td style="width: 10%;">Material For</td>
                        <td style="width: 9%;">GRN No</td>	
                        <td style="width: 10%;">Vendor Name</td>
                        <td style="width: 9%;">Material Type</td>
                        <td style="width: 8%;">Material code</td>
                        <td style="width: 10%;">Material Name</td>
                        <td style="width: 8%;">Grade</td>
                        <td style="width: 8%;">Batch No</td>
                        <td style="width: 6%;">Received Qty</td>
                        <td style="width: 8%;">Damage Containers</td>
                        <td style="width: 8%;">Received Date</td>  
                    </tr>
                </thead>';
        $i=1;
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='gmpdemo1' AND m.material_type='Raw Material' AND s.material_code LIKE '%%' AND s.vendor_no LIKE '%' AND s.status LIKE '%quarantine' AND m.material_name LIKE '%".$_GET['material_name']."%'AND m.material_subtype LIKE '%%'AND m.grade LIKE '%%' ORDER BY s.grn_no";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                    <td style="width: 4%;">'.$i.'</td>
                    <td style="width: 10%;">'.$row['material_for'].'</td>
                    <td style="width: 9%;">'.$row['grn_no'].'</td>
                    <td style="width: 10%;">'.$row['vendor_name'].'</td>
                    <td style="width: 9%;">'.$row['material_type'].'</td>
                    <td style="width: 8%;">'.$row['material_code'].'</td>
                    <td style="width: 10%;">'.$row['material_name'].'</td>
                    <td style="width: 8%;">'.$row['grade'].'</td>
                    <td style="width: 8%;">'.$row['batch_no'].'</td>
                    <td style="width: 6%;">'.$row['qty'].'</td>
                    <td style="width: 8%;">'.$row['containers'].'</td>
                    <td style="width: 8%;">'.date('d-m-y',strtotime($row['received_date'])).'</td>
                </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('quarantine stock pdf', 'I');
    }
     else if ($_GET["type"] == "downloadRawtest") {
        $_GET['filename'] = "Raw Material Under Test Stock Book"; $_GET['pdftype'] = 'onlyheader'; include("/pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Raw Material Under Test Stock Book</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 4%;">Sr.</td>
                        <td style="width: 9%;">GRN No</td>
                        <td style="width: 10%;">AR NO</td>
                        <td style="width: 10%;">Vendor Name</td>
                        <td style="width: 9%;">Material Type</td>
                        <td style="width: 8%;">Material code</td>
                        <td style="width: 10%;">Material Name</td>
                        <td style="width: 8%;">Grade</td>
                        <td style="width: 8%;">Batch No</td>
                        <td style="width: 6%;">Received Qty</td>
                        <td style="width: 8%;">Sampling Date</td>
                        <td style="width: 8%;">Testing Status</td>  
                    </tr>
                </thead>';
        $i=1;
                $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%'AND m.grade LIKE '%".$_GET["grade"]."%' ORDER BY s.grn_no";

        //$sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='gmpdemo1' AND m.material_type='Raw Material' AND s.material_code LIKE '%%' AND s.vendor_no LIKE '%' AND s.status LIKE '%quarantine' AND m.material_name LIKE '%".$_GET['material_name']."%'AND m.material_subtype LIKE '%%'AND m.grade LIKE '%%' ORDER BY s.grn_no";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                    <td style="width: 4%;">'.$i.'</td>
                    <td style="width: 9%;">'.$row['grn_no'].'</td>
                    <td style="width: 10%;">'.$row['ar_no'].'</td>
                    <td style="width: 10%;">'.$row['vendor_name'].'</td>
                    <td style="width: 9%;">'.$row['material_type'].'</td>
                    <td style="width: 8%;">'.$row['material_code'].'</td>
                    <td style="width: 10%;">'.$row['material_name'].'</td>
                    <td style="width: 8%;">'.$row['grade'].'</td>
                    <td style="width: 8%;">'.$row['batch_no'].'</td>
                    <td style="width: 6%;">'.$row['qty'].'<td>'.$row['unit'].'</td></td>
                    <td style="width: 8%;">'.$row['sample_date'].'</td>
                    <td style="width: 8%;">'.$row['statuse'].'</td>
                </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Rawtest stock pdf', 'I');
    }
    else if ($_GET["type"] == "downloadRawapproved") {
        $_GET['filename'] = "Raw Material Approved Stock Book"; $_GET['pdftype'] = 'onlyheader'; include("/pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Raw Material Approved Stock Book</h2>
        <table border="1" cellpadding="5">
                <thead>
                    <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                        <td style="width: 4%;">Sr.</td>
                        <td style="width: 8%;">GRN No</td>
                        <td style="width: 8%;">AR NO</td>
                        <td style="width: 10%;">Vendor Name</td>
                        <td style="width: 9%;">Material Type</td>
                        <td style="width: 7%;">Material code</td>
                        <td style="width: 10%;">Material Name</td>
                        <td style="width: 8%;">Grade</td>
                        <td style="width: 6%;">Batch No</td>
                        <td style="width: 5%;">Sampling Date</td>
                        <td style="width: 5%;">Release Date</td>
                        <td style="width: 5%;">LOD %</td>
                        <td style="width: 5%;">Received Qty</td>
                        <td style="width: 5%;">Dry Qty</td>   
                        <td style="width: 5%;">Balance Qty</td> 
                    </tr>
                </thead>';
        $i=1;
                $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%'AND m.grade LIKE '%".$_GET["grade"]."%' ORDER BY s.grn_no";

        //$sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='gmpdemo1' AND m.material_type='Raw Material' AND s.material_code LIKE '%%' AND s.vendor_no LIKE '%' AND s.status LIKE '%quarantine' AND m.material_name LIKE '%".$_GET['material_name']."%'AND m.material_subtype LIKE '%%'AND m.grade LIKE '%%' ORDER BY s.grn_no";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                    <td style="width: 4%;">'.$i.'</td>
                    <td style="width: 8%;">'.$row['grn_no'].'</td>
                    <td style="width: 8%;">'.$row['ar_no'].'</td>
                    <td style="width: 10%;">'.$row['vendor_name'].'</td>
                    <td style="width: 9%;">'.$row['material_type'].'</td>
                    <td style="width: 7%;">'.$row['material_code'].'</td>
                    <td style="width: 10%;">'.$row['material_name'].'</td>
                    <td style="width: 8%;">'.$row['grade'].'</td>
                    <td style="width: 6%;">'.$row['batch_no'].'</td>
                    <td style="width: 5%;">'.$row['sample_date'].'</td>
                    <td style="width: 5%;">'.$row['release_date'].'</td>
                    <td style="width: 5%;">'.$row['lod'].'</td>
                    <td style="width: 5%;">'.$row['qty'].'<td>'.$row['unit'].'</td></td>
                    <td style="width: 5%;">'.$row['dry_qty'].'<td>'.$row['unit'].'</td></td>
                    <td style="width: 5%;">'.$row['qty'].'</td>
                </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Rawapproved stock pdf', 'I');
    }
    else if ($_GET["type"] == "getAvailableMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material WHERE material_type='Raw Material' AND material_subtype='".$_GET["material_type"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
    		    $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' HAVING SUM(qty) > 0";
    		    $result1 = $conn->query($sql1);
    		    while ($row1 = $result1->fetch_assoc()) {
		            $output1 = Array();
		            $sql2 = "SELECT * FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved'";
		            $result2 = $conn->query($sql2);
		            if ($result2->num_rows > 0) {
		                while ($row2 = $result2->fetch_assoc()) {
		                    $output1[] = $row2;
		                }
		            }
		            $row["batches"] = $output1;
		            $output[] = $row;
		        }
    		}
    	}
    	echo json_encode($output);
    } else if ($_GET["type"] == "getPendingReceivings") {
        $output = Array();
        $sql = "SELECT c.*,c1.type,c1.challan_date,c1.tax_invoice,c1.transport_company,c1.transport_frieght, c1.po_no,c1.po_date,
        v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_subtype,m.material_name,c1.is_tanker
        FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m 
        ON c.material_code=m.material_code LEFT JOIN vendor v ON c.vendor_no=v.vendor_no 
        LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c.status='pending' 
        AND m.material_type='Raw Material' AND c.receiving='pending' AND 
        c.material_subtype LIKE '%".$_GET["material_subtype"]."%' 
        AND c1.po_no LIKE '%".$_GET["po_number"]."%'   
        AND DATE(c.Inward_Date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' 
        ORDER BY c1.id DESC";
       
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  else if ($_GET["type"] == "getPendingWeighingBridge") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c.status='inprocess' AND m.material_type='Raw Material' AND c.receiving='approve' AND c.weighing='pending' AND c.weighing_bridgestatus='pending' AND c1.weighing_procedure='Weigh Bridge' ORDER BY c.id DESC";
        // $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c.status='Sent To Weigh Bridge' AND m.material_type='Raw Material' AND c.receiving='approve' AND c.weighing='pending' AND c.weighing_bridgestatus='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["batches"] = json_decode($row["batches"]);
                if (($row["damage"] == 'approve' || $row["damage"] == "no")) {
                    $damage = 0;
                    if ($row["damage"] == "approve") {
                        $row["damage_details"] = json_decode($row["damage_details"]);

                        $damage_details = $row["damage_details"];
                        $damange_containers = $damage_details->containers;
                        $damage = +$damage_details->total_damage;
                        for ($i = 0; $i < count($damange_containers); $i++) {
                            $container = $damange_containers[$i];
                            $container->gross_wt = 0;
                            $container->tare_wt = 0;
                            $container->net_wt = 0;
                            $container->weight_by = "";
                            $container->check_by = "";
                            $damange_containers[$i]  = $container;
                        }
                        $row["damage_containers"] = $damange_containers;
                    }else{
                        $row["damage_containers"] = [];
                    }
                    $output1 = Array();
                    $containers = +$row["containers"];
                    // echo $containers;
                    for ($i = 1; $i <= $containers; $i++) {
                        $temp = Array();
                        $temp["container_no"] = $i;
                        $temp["gross_wt"] = 0;
                        $temp["tare_wt"] = 0;
                        $temp["net_wt"] = 0;
                        $temp['weight_by'] = "";
                        $temp['check_by'] = "";
                        $output1[] = $temp;
                    }
                    
                    $row["weight_containers"] = $output1;
                    
                    
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;

                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getAllPendingReceivings") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.status='pending' AND m.material_type='Raw Material' AND c.receiving='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"]=="receiveMaterial") {
        $input = $_POST;
        $flag = 0;
        $file = "";
        $dev_no = "";

        $dedusting = array();
        if ($_POST["dedustingmaterial"] == "Yes") {
            $dedusting = $_POST["dedusting"];
        }

        if ($_POST["coa_received"] == 'Yes') {
            if (isset($_FILES["coa"])) {
                $rand_no = date("YmdHis", $timestamp);
                $file = "../upload/coa/".$rand_no.basename($_FILES["coa"]["name"]).".pdf";
                move_uploaded_file($_FILES["coa"]["tmp_name"], $file);
                $file = $rand_no.basename($_FILES["coa"]["name"]).".pdf";
            } else {
                $flag = 1;
            }
        } else {
            $deviation = json_decode($input["deviation"], true);
            $sql = "INSERT INTO deviation (document_no, related_to, category, type, deviation_date, justification, cause, quality_impact, entry_by, entry_date) VALUES ('".$input["document_no"]."','".$deviation['related_to']."', '".$deviation['deviation_category']."', '".$deviation['type']."', '$entry_date', '".$deviation['justification']."', '".$deviation['cause']."', '".$deviation['quality_impact']."', '".$_GET["emp_id"]."', '$entry_date')";
            if ($conn->query($sql)) {
                $last_id = $conn->insert_id;
                
                $dev_no = "";
                $sql1 = "SELECT * FROM deviation WHERE id='$last_id'";
                $result = $conn->query($sql1);
                while ($row = $result->fetch_assoc()) {
                    $dev_no = $row["dev_no"];
                }
                if ($deviation["Stores"]) {
                    $sql = "INSERT INTO deviation_comments (dev_no, department) VALUES ('".$dev_no."', 'Store')";
                    $conn->query($sql);
                }
                if ($deviation["Production"]) {
                    $sql = "INSERT INTO deviation_comments (dev_no, department) VALUES ('".$dev_no."', 'Production')";
                    $conn->query($sql);
                }
                if ($deviation["Quality Control"]) {
                    $sql = "INSERT INTO deviation_comments (dev_no, department) VALUES ('".$dev_no."', 'Quality Control')";
                    $conn->query($sql);
                }
                if ($deviation["Packing"]) {
                    $sql = "INSERT INTO deviation_comments (dev_no, department) VALUES ('".$dev_no."', 'Packing')";
                    $conn->query($sql);
                }
                if ($deviation["Marketing"]) {
                    $sql = "INSERT INTO deviation_comments (dev_no, department) VALUES ('".$dev_no."', 'Marketing')";
                    $conn->query($sql);
                }
                if ($deviation["Client"]) {
                    $sql = "INSERT INTO deviation_comments (dev_no, department) VALUES ('".$dev_no."', 'Client')";
                    $conn->query($sql);
                }
                if ($deviation["Regulatory"]) {
                    $sql = "INSERT INTO deviation_comments (dev_no, department) VALUES ('".$dev_no."', 'Regulatory')";
                    $conn->query($sql);
                }
            }
        }
    
        $damange = "pending";
        $temp = Array();
        $temp["pack_size"] = $_POST["pack_size"];
        if ($_POST["isdamagecontainer"] == "Yes") {
            $temp["outer_damage"] = $_POST["outer_damage"];
            $temp["inner_damage"] = $_POST["inner_damage"];
            $temp["damage_status"] = "pending";
        } else {
            $damange = "no";
        }
        $temp["isdamagecontainer"] = $_POST["isdamagecontainer"];
        $temp["damange_remark"] = "";
        $temp["packing_condition"] = $_POST["packing_condition"];
        $temp["outer_packing"] = $_POST["outer_packing"];
        $temp["container_type"] = $_POST["container_type"];
        $temp["container_subtype"] = $_POST["container_subtype"];
        $temp["vehicle_condition"] = $_POST["vehicle_condition"];
        $temp["coa_received"] = $_POST["coa_received"];
        if ($_POST["coa_received"] == 'Yes') {
            $temp["coa_file"] = $file;
        }
        $temp["received_by"] = $_GET["emp_id"];
        $temp["received_date"] = $entry_date;
        
        // $sql = "UPDATE challan_materials SET received_by='".$_GET["emp_id"]."',receiving='inprocess', receiving_date='$entry_date',batches='".$_POST["batches"]."', challan_qty='".$input["challan_qty"]."', cleaning_type='".$_POST["cleaning_type"]."', received_qty='".$_POST["received_qty"]."', containers='".$_POST["containerTotal"]."', receiving_details='".json_encode($temp)."', coa_received='".$_POST["coa_received"]."', status='inprocess',  damage='".$damange."', receiving='inprocess', container_condition='".$input["container_condition"]."', container_seal='".$input["container_seal"]."', document_status='".$input["document_status"]."', storage_condition='".$input["storage_condition"]."', vehicle_cleanliness='".$_POST["vehicle_cleanliness"]."', container_type='".$input["container_type"]."' WHERE id='".$_GET["id"]."'";
        // $sql = "UPDATE challan_materials SET received_by='".$_GET["emp_id"]."',receiving='inprocess', receiving_date='$entry_date' , challan_qty='".$input["challan_qty"]."', cleaning_type='".$_POST["cleaning_type"]."', received_qty='".$_POST["received_qty"]."', containers='".$_POST["containerTotal"]."', receiving_details='".json_encode($temp)."', coa_received='".$_POST["coa_received"]."',dedusting_applicable='".$_POST["dedusting_applicable"]."', status='inprocess',  damage='".$damange."', receiving='inprocess', container_condition='".$input["container_condition"]."', container_seal='".$input["container_seal"]."', document_status='".$input["document_status"]."', storage_condition='".$input["storage_condition"]."', vehicle_cleanliness='".$_POST["vehicle_cleanliness"]."', container_type='".$input["container_type"]."' WHERE id='".$_GET["id"]."'";
        $sql = "UPDATE challan_materials SET qty_status = '".$input["qty_status"]."', received_by='".$_GET["emp_id"]."',
                receiving='inprocess', receiving_date='$entry_date' , challan_qty='".$input["challan_qty"]."',
                cleaning_type='".$_POST["cleaning_type"]."', received_qty='".$_POST["received_qty"]."',
                containers='".$_POST["containerTotal"]."', receiving_details='".json_encode($temp)."',
                coa_received='".$_POST["coa_received"]."',dedusting_applicable='".$_POST["dedusting_applicable"]."', 
                status='inprocess',  damage='".$damange."', receiving='inprocess', 
                container_condition='".$input["container_condition"]."', container_seal='".$input["container_seal"]."', 
                document_status='".$input["document_status"]."', storage_condition='".$input["storage_condition"]."',
                vehicle_cleanliness='".$_POST["vehicle_cleanliness"]."', 
                entry_time_start='".$input["entry_time_start"]."',
                entry_time_end='".$input["entry_time_end"]."',
                container_type='".$input["container_type"]."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            $batches =json_decode($input["batches"],true);
             for ($i = 0; $i < count($batches); $i++) {
                $bat = $batches[$i];
               
                $sql1 = "INSERT INTO sampling_batches (batch_no ,mfg_date ,pack_size ,total_containers ,qty_received , exp_date ,unit,challan_no) VALUES('".$bat["batch_no"]."', '".$bat["mfg_date"]."','".$bat["pack_size"]."','".$bat["total_containers"]."', '".$bat["qty_received"]."' ,'".$bat["exp_date"]."' ,'".$bat["unit"]."' ,'".$input["challan_no"]."')";
                $conn->query($sql1);
                  $sql2 = "INSERT INTO label (label_for, document_no,batch_no, label_type, material_code, label_count, entry_by, entry_date,challan_no,mfg_date,exp_date,recv_qty) VALUES ('Receving', '".$input["document_no"]."','".$bat["batch_no"]."', 'Receving Label', '".$input["material_code"]."', '".$bat["total_containers"]."', '".$_GET["emp_id"]."', '$entry_date','".$input["challan_no"]."','".$bat["mfg_date"]."','".$bat["exp_date"]."','".$bat["qty_received"]."')";
                  
                  $conn->query($sql2);
             }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\",\"error\":\"".$conn->error."\"}";
        }
    } 
    else if ($_GET["type"] == "getInprocessReceivings") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_subtype,m.material_name, 
        m.grade,c1.challan_file FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code
        LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c.status='inprocess'
        AND c.receiving='inprocess' AND m.material_type='Raw Material' AND c.material_code LIKE '%".$_GET["material_code"]."%' ORDER BY c1.id DESC";
    
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["coa_received"] == "No") {
                    $sql1 = "SELECT * FROM deviation WHERE document_no='".$row["document_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["deviation_no"] = $row1["dev_no"];
                        }
                    } else {
                        $row["deviation_no"] = "";
                    }
                }
                
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["batches"] = json_decode($row["batches"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }  else if ($_GET["type"] == "getAllInprocessReceivings") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.status='inprocess' AND c.receiving='inprocess' AND m.material_type='Raw Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["coa_received"] == "No") {
                    $sql1 = "SELECT * FROM deviation WHERE document_no='".$row["document_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["deviation_no"] = $row1["dev_no"];
                        }
                    } else {
                        $row["deviation_no"] = "";
                    }
                }
                
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["batches"] = json_decode($row["batches"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRejectedReceving") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE  c.receiving='reject' AND m.material_type='Raw Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row["coa_received"] == "No") {
                    $sql1 = "SELECT * FROM deviation WHERE document_no='".$row["document_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["deviation_no"] = $row1["dev_no"];
                        }
                    } else {
                        $row["deviation_no"] = "";
                    }
                }
                
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["batches"] = json_decode($row["batches"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "checkReceivedMaterial") {
        $sql = "Select count(*)+1 as count from challan_materials where (document_no != null or document_no!='')";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['count'];
        }
        
        if($last_id==0){
            $last_id=1;
        }else{
            $last_id = $last_id + 1;
        }
        
        $length = 4;
        $number = 'RM-'.substr(str_repeat(0, $length).$last_id, - $length);
        
        $sql = "UPDATE challan_materials SET receiving='".$_GET["status"]."', receiving_date= '".$entry_date."', document_no='".$number."' WHERE id='".$_GET["id"]."'";
       
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
        
        
    }
    else if ($_GET["type"] == "getaterialLog") {
        $sql="SELECT c.id, c.material_type ,c.po_no,c.po_date,c.status,p.status as po_status,cm.grn_no, s.sampling_no, s.sample_status, v.vendor_name,cm.material_subtype,c.challan_no,c.challan_date,cm.status as mat_recd_status FROM challan c left join challan_materials cm on c.challan_no = cm.challan_no left join vendor v on cm.vendor_no = v.vendor_no left join sampling s on cm.grn_no = s.grn_no left join purchaseorder p on c.po_no = p.po_no ORDER BY p.id DESC";
 $result = $conn->query($sql);
 if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
    }
    echo json_encode($output);

    }
    else if ($_GET["type"] == "getReceivingLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND DATE(c.receiving_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY c.document_no DESC";
        // $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  GROUP BY c.id ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["batches"] = json_decode($row["batches"]);
                
                if ($row["coa_received"] == "No") {
                    $sql1 = "SELECT * FROM deviation WHERE document_no='".$row["document_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["deviation_no"] = $row1["dev_no"];
                        }
                    } else {
                        $row["deviation_no"] = "";
                    }
                }
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getAllReceivingLogFinish") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' GROUP BY c.id DESC";
        //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  GROUP BY c.id ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["batches"] = json_decode($row["batches"]);
                
                if ($row["coa_received"] == "No") {
                    $sql1 = "SELECT * FROM deviation WHERE document_no='".$row["document_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["deviation_no"] = $row1["dev_no"];
                        }
                    } else {
                        $row["deviation_no"] = "";
                    }
                }
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getAllReceivingLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' GROUP BY c.id DESC";
        //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  GROUP BY c.id ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["batches"] = json_decode($row["batches"]);
                
                if ($row["coa_received"] == "No") {
                    $sql1 = "SELECT * FROM deviation WHERE document_no='".$row["document_no"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["deviation_no"] = $row1["dev_no"];
                        }
                    } else {
                        $row["deviation_no"] = "";
                    }
                }
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingDedustingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.receiving='approve' AND c.dedusting='pending' AND c.dedusting_applicable='Yes'  AND m.material_type='Raw Material' ORDER BY c.id DESC ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                // $row["batches"] = json_decode($row["batches"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveMaterialDedusting") {
        $sql = "UPDATE challan_materials SET dedusting='approve', dedusting_details='".json_encode($input)."', dedusting_by='".$_GET["emp_id"]."', dedusting_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getDedustingMaterials") {
        $output = Array();
        
       $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.dedusting !='pending' AND c.dedusting_applicable ='Yes' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%' AND DATE(c.receiving_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
       // $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.id='".$_GET['id']."' ";
        $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                //$row["batches"] = json_decode($row["batches"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                
                 $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getAllDedustingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.dedusting !='pending' AND m.material_type ='Raw Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                //$row["batches"] = json_decode($row["batches"]);
                $row["dedusting_details"] = json_decode($row["dedusting_details"]);
                
                 $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingDamages") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.receiving='approve' AND c.dedusting ='approve' AND c.damage='pending' AND m.material_type='Raw Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                 $row["batches"] = json_decode($row["batches"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDamageInspection") {
        $input["entry_by"] = $_GET["emp_id"];
        $input["entry_date"] = $entry_date;
        $sql = "UPDATE challan_materials SET damage='inprocess', damage_details='".json_encode($input)."' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getInprocessDamages") {
       $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,
        m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN 
        material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
        WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.receiving='approve'";
        //AND c.dedusting ='approve' AND c.damage NOT IN ('no') AND m.material_type='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' 
        //AND v.vendor_no LIKE '%".$_GET["vendor_no"]."%' AND c.status LIKE '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["damage_details"] = json_decode($row["damage_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateDamageInspection") {
        $input["approve_by"] = $_GET["emp_id"];
        $input["approve_date"] = $entry_date;
        $sql = "UPDATE challan_materials SET damage='".$_GET["status"]."', damage_details='".json_encode($input)."' WHERE id='".$_GET["id"]."'";
       // echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getDamageLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,
        m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN 
        material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no 
        WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.receiving='approve'";
        //AND c.dedusting ='approve' AND c.damage NOT IN ('no') AND m.material_type='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' 
        //AND v.vendor_no LIKE '%".$_GET["vendor_no"]."%' AND c.status LIKE '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["damage_details"] = json_decode($row["damage_details"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingWeighingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, v.vendor_name,v2.vendor_name as manufacturer_name,
        m.material_type, m.material_subtype,m.material_name, m.grade,c1.weighing_procedure FROM challan_materials c
        LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c.status='inprocess' AND m.material_type='Raw Material' AND c.receiving='approve' AND c.weighing='pending' ORDER BY c.id DESC"; //AND c1.weighing_procedure='Regular' 
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["batches"] = json_decode($row["batches"]);
                if (($row["damage"] == 'approve' || $row["damage"] == "no")) {
                    $damage = 0;
                    if ($row["damage"] == "approve") {
                        $row["damage_details"] = json_decode($row["damage_details"]);

                        $damage_details = $row["damage_details"];
                        $damange_containers = $damage_details->containers;
                        $damage = +$damage_details->total_damage;
                        for ($i = 0; $i < count($damange_containers); $i++) {
                            $container = $damange_containers[$i];
                            $container->gross_wt = 0;
                            $container->tare_wt = 0;
                            $container->net_wt = 0;
                            $container->weight_by = "";
                            $container->check_by = "";
                            $damange_containers[$i]  = $container;
                        }
                        $row["damage_containers"] = $damange_containers;
                    }else{
                        $row["damage_containers"] = [];
                    }
                    $output1 = Array();
                    $containers = +$row["containers"];
                    // echo $containers;
                    for ($i = 1; $i <= $containers; $i++) {
                        $temp = Array();
                        $temp["container_no"] = $i;
                        $temp["gross_wt"] = 0;
                        $temp["tare_wt"] = 0;
                        $temp["net_wt"] = 0;
                        $temp['weight_by'] = "";
                        $temp['check_by'] = "";
                        $output1[] = $temp;
                    }
                    
                    $row["weight_containers"] = $output1;
                    
                    
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;

                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "updateWeighBridgeStatus") {
        $input["approve_by"] = $_GET["emp_id"];
        $input["approve_date"] = $entry_date;
        $sql = "UPDATE challan_materials SET status='".$_GET["status"]."' WHERE id='".$_GET["id"]."'";
       // echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }
    else if ($_GET["type"] == "saveWeighingsList") {
        $sql = "UPDATE sampling_batches SET weight='".json_encode($input["weight"])."', status = 'approve' , root_container='".$input["root_container"]."'  WHERE batch_no = '".$input["batch_no"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }else if ($_GET["type"] == "saveWeighingMaterials") {
        $sql = "UPDATE challan_materials SET weighing='inprocess',weight='".json_encode($input["weight"])."',balance = '".$input["balance"]."' ,weighing_details='".json_encode($input)."', weighing_by='".$_GET["emp_id"]."', weighing_date='$entry_date' WHERE id='".$input["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    }else if ($_GET["type"] == "saveWeighingBridge") {
        $sql = "UPDATE challan_materials SET weighing_bridge='".json_encode($input)."',weighing_bridgestatus='approve' WHERE id='".$_GET["id"]."'";
        // echo $sql;
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getCheckingWeighingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, v.vendor_name,v2.vendor_name as manufacturer_name,
        m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan 
        c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor
        v ON c.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no
        WHERE c.status='inprocess' AND weighing='inprocess'
        AND m.material_type='Raw Material' ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getCorrectionWeighingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, 
        m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code
        LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='reject' AND weighing='approve' AND m.material_type='Raw Material'";
       //echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "updateWeighing") {
        $input["check_by"] = $_GET["emp_id"];
        $input["check_date"] = $entry_date;
        
         $sql = "Select count(*) as count from challan_materials where (weighing_no != null or weighing_no!='')";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
              $last_id = $row['count'];
        }
        if($last_id==0){
            $last_id=1;
        }else{
            $last_id=$last_id+1;
        }
        $length = 4;
        $number = 'WTRM-'.substr(str_repeat(0, $length).$last_id, - $length);
        
        
        // $sql = "UPDATE challan_materials SET weighing='approve', weighing_details='".json_encode($input)."' WHERE id='".$input["id"]."'";
        $sql = "UPDATE challan_materials SET  status='approve', weighing='".$_GET["status"]."', weighing_no= '".$number."', weighing_date= '".$entry_date."', weighing_details='".json_encode($input)."' WHERE id='".$_GET["id"]."'";
         
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }
    } else if ($_GET["type"] == "getWeighingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, v.vendor_name,v2.vendor_name as manufacturer_name,
        m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON
        c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code 
        LEFT JOIN vendor v ON c.vendor_no=v.vendor_no
        LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no 
        WHERE c.user_no='".$_GET["user_no"]."' AND weighing !='pending' AND m.material_type='Raw Material' AND DATE(c.inward_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY c.weighing_date DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getAllWeighingMaterials") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND weighing !='pending' AND m.material_type='Raw Material'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingGRN") {
        $output = Array();
        // $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date,v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess'  AND grn='pending' AND m.material_type='Raw Material' AND (weighing='approve' OR weighing_bridgestatus='approve')  ORDER BY c.id DESC";
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date,v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type,
        m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material
        m ON c.material_code=m.material_code LEFT JOIN vendor v ON c.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no
        WHERE c.user_no='".$_GET["user_no"]."' AND c.status='approve'  AND grn='pending'
        AND m.material_type='Raw Material' AND (weighing='approve' OR weighing_bridgestatus='approve') 
        ORDER BY c.id DESC";
     
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["batches"] = json_decode($row["batches"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $weighings = $row["weighing_details"];
                
                $accept_qty = 0;
                $reject_qty = 0;
                $containers = $weighings->containers;
                $damages = $weighings->damage_containers;

                for ($i = 0; $i < count($containers); $i++) {
                    $container = $containers[$i];
                    $accept_qty += +$container->net_wt;
                }

                for ($i = 0; $i < count($damages); $i++) {
                    $container = $damages[$i];
                    if ($container->status == "approve") {
                        $accept_qty += +$container->net_wt;
                    } else {
                        $reject_qty += +$container->net_wt;
                    }
                }
                $short_qty = number_format(+$row["order_qty"] - ($accept_qty + $reject_qty), 2);
                $row["accept_qty"] = $accept_qty;
                $row["reject_qty"] = $reject_qty;
                $row["short_qty"] = $short_qty;
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveGRN") {
        $input["entry_by"] = $_GET["emp_id"];
        $input["entry_date"] = $entry_date;
        $grn_no = "GRN/22/R/0".$input["id"];
       
        $sql = "UPDATE challan_materials SET grn='inprocess',accept_qty='".$input["accept_qty"]."',
        grn_details='".json_encode($input)."', grn_no='$grn_no' , grn_date='$entry_date', 
       
        urgency='".$input["urgency"]."',grn_grade= '".json_encode($input["grn_grade"])."' WHERE id='".$input["id"]."'";
    
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
                    echo "{\"status\":\"".$conn->error."\"}";
        }
    }
    else if ($_GET["type"] == "getPendingCheckingGRN") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type,
        m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material
        m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no LEFT JOIN vendor v2 
        ON c.manufacturer_no=v2.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='approve'
        AND grn='inprocess' AND m.material_type='Raw Material'  ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                // $row["batches"] =json_decode($row["batches"]);
                $weighings = $row["weighing_details"];
    
                $output1 = Array();
                $containers = $weighings->containers;
                for ($i = 0; $i < count($containers); $i++) {
                    $output1[] = $containers[$i];
                }
    
                $damages = $weighings->damage_containers;
                for ($i = 0; $i < count($damages); $i++) {
                    $damage = $damages[$i];
                    if ($damage->status == 'approve') {
                        $output1[] = $damages[$i];
                    }
                }
                $row["container_details"] = $output1;
    
                $row["grn_details"] = json_decode($row["grn_details"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    // else if ($_GET["type"] == "updateGRN") {
    //     $input["entry_by"] = $_GET["emp_id"];
    //     $input["entry_date"] = $entry_date;
    //     $sql = "";
    //     if ($input["status"] == "approve") {
    //         $sql = "UPDATE challan_materials SET grn='".$input["status"]."', status='approve' WHERE id='".$input["id"]."'";
    //     } else {
    //         $sql = "UPDATE challan_materials SET grn='".$input["status"]."' WHERE id='".$input["id"]."'";
    //     }
        
    //     if ($conn->query($sql)) {
    //         $grn_no = "GRN-".$input["id"];
    //         $ar_no = "AR-".$input["id"];
    //         $sql1 = "INSERT INTO stock_book (user_no, grn_no,ar_no,vendor_no,material_code, batch_no,qty,unit,mfg_date,exp_date,entry_by,entry_date, containers, receiving_no) VALUES ('".$_GET["user_no"]."','$grn_no', '$ar_no','".$input["vendor_no"]."', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["received_qty"]."', '".$input["unit"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$_GET["emp_id"]."', '$entry_date', '".json_encode($input["containers"])."', '".$input["id"]."')";
    //         $conn->query($sql1);
            
    //         $sql1 = "UPDATE sampling_batches SET grn_no ='".$grn_no."' ,ar_no='".$ar_no."' WHERE  challan_no ='".$input["challan_no"]."' ";
    //         $conn->query($sql1);
                
    //         // $sql1 = "INSERT INTO sampling (user_no, material_code, batch_no, containers, grn_no, grn_date, mfg_date, exp_date, urgency) VALUES ('".$_GET["user_no"]."','".$input["material_code"]."', '".$input["batch_no"]."', '".count($input["containers"])."','$grn_no', '$entry_date', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["urgency"]."')";
    //         // $conn->query($sql1);
            
            
            
    //         echo "{\"status\":\"success\"}";
    //     } else {
    //         echo "{\"status\":\"".$conn->error."\"}";
    //     }
    // } 
    
       
       else if ($_GET["type"] == "updateGRN") {
        $input["entry_by"] = $_GET["emp_id"];
        $input["entry_date"] = $entry_date;
        $sql = "";
        if ($input["status"] == "approve") {
            //$sql = "UPDATE challan_materials SET grn='".$input["status"]."', status='Under_Sampling' WHERE id='".$input["id"]."'";
            $sql = "UPDATE challan_materials SET grn='approve', status='approve' WHERE id='".$input["id"]."'";
        } else {
            $sql = "UPDATE challan_materials SET grn='".$input["status"]."' WHERE id='".$input["id"]."'";
        }
    
        if ($conn->query($sql)) {
            $grn_no = "GRN/22/R/0".$input["id"];
             $sql1 = "SELECT IFNULL(COUNT(id), 0) as id FROM stock_book ORDER BY id DESC LIMIT 1";
              $result1 = $conn->query($sql1);
             $row1 = $result1->fetch_assoc();
             
            $batches = $input["batches"];
           // echo $batches;
            for ($i = 0; $i < count($batches); $i++) {
                $batch = $batches[$i];
                $last_id=$row1["id"]+1;
                $ar_no="AR-0".$last_id;
                
                $sql2 = "UPDATE sampling_batches SET grn_no ='".$grn_no."' ,ar_no='$ar_no' WHERE  challan_no ='".$input["challan_no"]."' and id='".$batch["id"]."' ";
                $conn->query($sql2);
                 
                $sql1 = "INSERT INTO stock_book (user_no, grn_no,ar_no,inword_no,vendor_no,material_code, batch_no,qty,unit,
                mfg_date,exp_date,entry_by,entry_date, total_containers,pack_size) VALUES ('".$_GET["user_no"]."','$grn_no', '$ar_no',
                '".$input["inward_no"]."','".$input["vendor_no"]."', '".$input["material_code"]."', '".$batch["batch_no"]."', 
                '".$batch["qty_received"]."', '".$batch["unit"]."', '".$batch["mfg_date"]."', '".$batch["exp_date"]."', 
                '".$_GET["emp_id"]."', '$entry_date', '".$batch["total_containers"]."', '".$batch["pack_size"]."')";
                $conn->query($sql1);
                $last_id=$row1["id"]++;
               
            }
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } 
    
    else if ($_GET["type"] == "changeGrade") {
        $input["entry_by"] = $_GET["emp_id"];
        $input["entry_date"] = $entry_date;
        $sql = "";
        if ($input["status"] == "approve") {
            $sql = "UPDATE challan_materials SET grn='".$input["status"]."', status='approve' WHERE id = '".$input["id"]."'";
        } else {
            $sql = "UPDATE challan_materials SET grn='".$input["status"]."' WHERE id = '".$input["id"]."'";
        }
        
        if ($conn->query($sql)) {
            $grn_no = "GRN-".$input["id"];
            $ar_no = "AR-".$input["id"];
            $sql1 = "INSERT INTO stock_book (user_no, grn_no,ar_no,vendor_no,material_code, batch_no,qty,unit,mfg_date,exp_date,entry_by,entry_date, containers, receiving_no) VALUES ('".$_GET["user_no"]."','$grn_no', '$ar_no','".$input["vendor_no"]."', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["accept_qty"]."', '".$input["unit"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$_GET["emp_id"]."', '$entry_date', '".json_encode($input["containers"])."', '".$input["id"]."')";
            $conn->query($sql1);
            
            $sql1 = "INSERT INTO sampling (change_grade,change_qty,change_unit,user_no, material_code, batch_no, containers, grn_no, grn_date, mfg_date, exp_date, urgency) VALUES ('".$input["change_grade"]."','".$input["change_qty"]."','".["change_unit"]."','".$_GET["user_no"]."','".$input["material_code"]."', '".$input["batch_no"]."', '".count($input["containers"])."','$grn_no', '$entry_date', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$input["urgency"]."')";
            $conn->query($sql1);
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    }else if ($_GET["type"] == "getGRNLog") {
        $output = Array();
       // $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type='Raw Material' ORDER BY id DESC";
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type,
        m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no 
        LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c.vendor_no=v.vendor_no
        WHERE   m.material_type='Raw Material' ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getAllGRNLog") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type='Raw Material' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getHoldGrn") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type='Raw Material' AND c.grn='On Hold' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getRejectedGrn") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type='Raw Material' AND c.grn='reject' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getRejectedGrn") {
        $output = Array();
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type='Raw Material' AND c.status='reject' GROUP BY c.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // $row["batches"] = json_decode($row["batches"]);
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $row["weighing_details"] = json_decode($row["weighing_details"]);
                $row["grn_details"] = json_decode($row["grn_details"]);
                $output1 = array();
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["weight"] = json_decode($row1["weight"]);
                         $output1[] = $row1;
                    }
                }
        
                $row["batches"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "getPendingGRNLabels") {
        $output = Array();
        $sql = "SELECT l.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM label l LEFT JOIN material m ON l.material_code=m.material_code WHERE l.label_for='GRN' AND m.material_type='Raw Material' AND l.status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "grnLabelsPDF1"){
        $sql = "UPDATE label SET status='print' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            require '../tcpdf/tcpdf.php';
            class MYPDF extends TCPDF {
                public function Header() {}
                public function Footer() {}
            }
            $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetMargins(10, 10, 10, 10);
            $pdf->SetAutoPageBreak(TRUE, 10);
            $pdf->AddPage('P', 'A4');
            $pdf->SetFont ('Times', '', '11' , '', 'default', true );
            for ($i=1; $i <= +$_GET['label_count']; $i++) {
                if($i == +$_GET['label_count'] && $i % 2 !== 0){
                    $html.='&nbsp;<br>
                    <table cellpadding="-5" style="width:100%;">
                        <tr>
                            <td style="width:49%;">
                                <table cellpadding="5" nobr="true" style="background-color:#C4A484">
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Quarantine</b></td>
                                        <td style="border:solid 1px BCBBBA; width:74%;">
                                            <table>
                                                <tr>
                                                    <td colspan="2"><b>'.$_GET['material_name'].'</b></td>
                                                </tr>
                                                <tr>
                                                    <td><b>Grade</b></td>
                                                    <td>'.$_GET['grade'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Batch No</b></td>
                                                    <td>'.$_GET['batch_no'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Container No</b></td>
                                                    <td>'.$i.' /'.$_GET['label_count'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width:2%;"></td>
                            <td style="width:49%;"></td>
                        </tr>
                    </table>';
                }else{
                $html.='&nbsp;<br>
                    <table cellpadding="-5" style="width:100%;">
                        <tr>
                            <td style="width:49%;">
                                <table cellpadding="5" nobr="true" style="background-color:#C4A484">
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Quarantine</b></td>
                                        <td style="border:solid 1px BCBBBA; width:74%;">
                                            <table>
                                                <tr>
                                                    <td colspan="2"><b>'.$_GET['material_name'].'</b></td>
                                                </tr>
                                                <tr>
                                                    <td><b>Grade</b></td>
                                                    <td>'.$_GET['grade'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Batch No</b></td>
                                                    <td>'.$_GET['batch_no'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Container No</b></td>
                                                    <td>'.$i.' /'.$_GET['label_count'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width:2%;"></td>
                            <td style="width:49%;">
                                <table cellpadding="5" nobr="true" style="background-color:#C4A484;">
                                    <tr>
                                        <td style="border:solid 1px BCBBBA;width:26%;">&nbsp;<br><b>&nbsp;Quarantine</b></td>
                                        <td style="border:solid 1px BCBBBA; width:74%;">
                                            <table>
                                                <tr>
                                                    <td colspan="2"><b>'.$_GET['material_name'].'</b></td>
                                                </tr>
                                                <tr>
                                                    <td><b>Grade</b></td>
                                                    <td>'.$_GET['grade'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Batch No</b></td>
                                                    <td>'.$_GET['batch_no'].'</td>
                                                </tr>
                                                <tr>
                                                    <td><b>Container No</b></td>
                                                    <td>'.($i+1).' /'.$_GET['label_count'].'</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>';
                }
                $i++;
            }
            EOD;
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
        }
    } else if ($_GET["type"] == "getRetestCalendar") {
        $output = Array();
        $sql = "SELECT s.vendor_no, s.material_code, m.material_subtype, m.material_name, m.grade, s.batch_no, s.qty, s.unit, s.mfg_date, s.exp_date, s.grn_no, s.ar_no, DATE(s.approve_date) as approve_date FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code WHERE s.status='Approved' AND s.material_code LIKE 'R%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT retest_period FROM specification WHERE material_code='".$row["material_code"]."' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["retest_period"] = $row1["retest_period"];
                    }
                }
                $row["retest_date"] = date('Y-m-d', strtotime("+".$row["retest_period"]." months", strtotime($row["approve_date"])));
                
                $todays = date("Y-m-d", $timestamp);
                if (($todays <= $row["retest_date"])) {
                    $diff = abs(strtotime($row["retest_date"]) - strtotime($todays));
                    $dtF = new \DateTime('@0');
                    $dtT = new \DateTime("@$diff");
                    $row["due_days"] = $dtF->diff($dtT)->format('%a');
                } else {
                    $row["due_days"] = 0;
                }
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingRetests") {
        $output = Array();
        $sql = "SELECT s.vendor_no, s.material_code, m.material_type, m.material_subtype, m.material_name, m.grade, s.batch_no, s.qty, s.unit, s.mfg_date, s.exp_date, s.grn_no, s.ar_no, s.containers, DATE(s.approve_date) as approve_date, v.vendor_name, s.grn_date FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.status='Approved' AND s.material_code LIKE 'R%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT retest_period FROM specification WHERE material_code='".$row["material_code"]."' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["retest_period"] = $row1["retest_period"];
                    }
                }
                $row["retest_date"] = date('Y-m-d', strtotime("+".$row["retest_period"]." months", strtotime($row["approve_date"])));
                
                $sql1 = "SELECT * FROM retest WHERE user_no='".$_GET["user_no"]."' AND material_code='".$row["material_code"]."' AND batch_no='".$row["batch_no"]."' AND retest_date='".$row["retest_date"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows == 0) {
                    $retest_date = date('Y-m-d', strtotime('-7 day', strtotime($row["retest_date"])));
                
                    $todays = date("Y-m-d", $timestamp);
                        
                    if (($todays >= $retest_date) && ($todays <= $row["retest_date"])) {
                        
                        $todays = date("Y-m-d", $timestamp);
                        if (($todays <= $row["retest_date"])) {
                            $diff = abs(strtotime($row["retest_date"]) - strtotime($todays));
                            $years = floor($diff / (365*60*60*24));
                            $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
                            $row["due_days"] = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
                        } else {
                            $row["due_days"] = 0;
                        }
                        
                        $output[] = $row;
                    }
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveRetest") {
        $sql = "INSERT INTO retest (user_no, retest_date, grn_no, grn_date, material_code, batch_no, qty, unit, vendor_no, ar_no, release_date, mfg_date, exp_date, entry_by, entry_date) VALUES ('".$_GET["user_no"]."', '".$input["retest_date"]."', '".$input["grn_no"]."', '".$input["grn_date"]."', '".$input["material_code"]."', '".$input["batch_no"]."', '".$input["qty"]."', '".$input["unit"]."', '".$input["vendor_no"]."', '".$input["ar_no"]."', '".$input["approve_date"]."', '".$input["mfg_date"]."', '".$input["exp_date"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getRetestsLog") {
        $output = array();
        $sql = "SELECT r.*, m.material_type, m.material_subtype, m.material_name, m.grade, v.vendor_name FROM retest r LEFT JOIN material m ON r.material_code=m.material_code LEFT JOIN vendor v ON r.vendor_no=v.vendor_no WHERE r.user_no='".$_GET["user_no"]."' AND r.vendor_no LIKE '%".$_GET["vendor_no"]."' AND DATE(r.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if($_GET["type"] == "GRNPDF"){
        $_GET['filename'] = 'Goods Receipt Notes Log'; $_GET['pdftype']= 'onlyheader';  include('/pdfimp2.php');
          $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name,v.address,m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.id='".$_GET['id']."' ";
        $result = $conn->query($sql);
       if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
               //$sql = "SELECT c.*,c1.entry_by,c1.approve_by, c1.challan_date, c1.inword_no, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type,m.material_name, m.grade, m.material_subtype as material_subtype FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type='Raw Material' AND c.id='".$_GET["id"]."'";
    //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.grn !='pending' AND m.material_type='Raw Material' AND c.id='".$_GET["id"]."' GROUP BY c.id";
     
                
                // $row["receiving_details"] = json_decode($row["receiving_details"]);
                // $row["weighing_details"] = json_decode($row["weighing_details"]);
                // $row["grn_details"] = json_decode($row["grn_details"]);
                $html.='
                <h3 style="text-align:center;">Good Receipt Note</h3>
                <table cellpadding="3" style="text-align:left;">
                    <tr>
                        <td style="width:21%">Material Name:	</td>
                        <td style="width:79%">'.$row['material_name'].'</td>
                        
                    </tr>
                    <tr>
                        <td style="width:21%">Vendor Name:</td>
                       <td style="width:79%">'.$row["vendor_name"].'</td>
                        
                    </tr>
                    <tr>
                        <td style="width:21%">Vendor Location:</td>
                        <td style="width:32%">'.$row['address'].'</td>
                        <td style="width:15%">Manufacturer:</td>
                        <td style="width:32%">'.$row['manufacturer'].'</td>
                    </tr>
                    <tr>
                        <td>Challan No.:</td>
                        <td>'.$row['challan_no'].'</td>
                        <td>Challan Date:</td>
                        <td>'.date('d/m/Y',strtotime($row['challan_date'])).'</td>
                    </tr>
                    <tr>
                        <td>PO No.:</td>
                        <td>'.$row['po_no'].'</td>
                        <td>PO Date:</td>
                        <td>'.date('d/m/Y',strtotime($row['po_date'])).'</td>
                    </tr>
                    <tr>
                        <td>Material Type:	</td>
                        <td>'.$row['material_type'].'</td>
                        <td>Material Subtype:</td>
                        <td>'.$row['material_subtype'].'</td>
                    </tr>
                    <tr>
                        <td>Material Code:</td>
                        <td>'.$row['material_code'].'</td>
                        <td>Material Grade:</td>
                        <td>'.$row['grade'].'</td>
                    </tr>
                    <tr>
                    <td style="width:21%">PO Qty:</td>
                     <td style="width:79%">'.$row['qty'].' kg</td>
                    </tr>
                </table>
                <div></div>
                 <h3 style="text-align:center;">Labeling Details:</h3>
                <table cellpadding="4" style="text-align:center;">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td style="width:10%">Sr</td>
                         <td style="width:10%">Medicap lot no</td>
                        <td style="width:10%;">Batch No	</td>
                        <td style="width:10%;">Qty	</td>
                        <td style="width:20%">No Of Containers	</td>
                        <td style="width:20%">Mfg Date</td>
                        <td style="width:20%">Exp Date</td>
                        
                    </tr>';
                
                          $i=1;
            $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                $row1["weight"] = json_decode($row1["weight"]);
                $html.='
                <tr>
                    <td style="width:10%;">'.$i++.'</td>
                    <td style="width:10%;">'.$row1["ar_no"].'</td>
                    <td style="width:10%;">'.$row1["batch_no"].'</td>
                    <td style="width:10%;">'.$row1["qty_received"].'</td>
                    <td style="width:20%;">'.$row["containers"].'</td>
                    <td style="width:20%;">'.date('M-Y',strtotime($row1["mfg_date"])).'</td>
                    <td style="width:20%;">'.date('M-Y',strtotime($row1["exp_date"])).'</td>
                </tr>';
                }
            }
           
                            
                $html.='</table>
                
                <h3 style="text-align:center;">Batch Details:</h3>
                <table cellpadding="3" style="text-align:center;">
                    <tr style="background-color:#DDDAD9; text-align:center;">
                        <td style="width:10%">sr</td>
                        <td style="width:20%;">Batch No	</td>
                        <td style="width:10%;">Received Qty		</td>
                        <td style="width:20%">Containers	</td>
                        <td style="width:20%">MFG Date</td>
                        <td style="width:20%">Exp Date</td>
                        </tr>';
                  $i=1;
                $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $row1["weight"] = json_decode($row1["weight"]);
                    $html.='
                    <tr>
                       <td >'.$i.'.</td>
                       <td>'.$row1['batch_no'].'</td>
                       <td>'.$row1['qty_received'].'</td>
                        <td>'.$row1['total_containers'].'</td>
                        <td>'.date('d-m-Y',strtotime($row1['mfg_date'])).'</td>
                        <td>'.date('d-m-Y',strtotime($row1['exp_date'])).'</td>
                    </tr>';
                    $i++;
                    }
                }
                $html.='
                </table>';
                $html.='
                <div></div>
                <h3>Remark:</h3>
                <table cellpadding="3">
                    <tr>
                        <td >'.$row['grn_remark'].'</td>
                       </tr>
                       
                    </table>';
             
                   $pdf->writeHTML($html, true, false, false, false, '');
                 $pdf->Output('','I');
             
            }
       }
            
           
      
    
     }else if($_GET["type"] == 'GRNLogPDF') {
        $_GET['filename'] = 'Goods Receipt Notes Log'; $_GET['pdftype'] = 'onlyheader';  include('/pdfimp2.php');
        $html.='
        <h2 style="text-align:center">Goods Receipt Notes Log</h2>
        <table cellpadding="3">
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 9%;">GRN No.</td>
                    <td style="width: 9%;">GRN Date	</td>
                    <td style="width: 9%;">Receiving Date</td>
                    <td style="width: 14%;">Material Type</td>
                    <td style="width: 9%;">Material Code</td>
                    <td style="width: 9%;">Material Name</td>
                    <td style="width: 9%;">Grade</td>
                    <td style="width: 9%;">Vendor Name</td>
                    <td style="width: 9%;">Qty</td>
                    <td style="width: 9%;">Status</td>
                </tr>';
             $count=1;
            $sql = "SELECT c.*,c.grn_no, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.grn !='pending' AND m.material_type='Raw Material' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td style="width: 5%;">'.$count++.'</td>
                        <td style="width: 9%;" >'.$row["grn_no"].'</td>
                        <td style="width: 9%;">'.date('d/m/Y',strtotime($row["grn_date"])).'</td>
                        <td style="width: 9%;">'.date('d/m/Y',strtotime($row["receiving_date"])).'</td>
                        <td style="width: 14%;">'.$row["material_type"].'</td>
                        <td style="width: 9%;">'.$row["material_code"].'</td>
                        <td style="width: 9%;">'.$row["material_name"].'</td>
                        <td style="width: 9%;">'.$row["grade"].'</td>
                        <td style="width: 9%;">'.$row["vendor_name"].'</td>
                        <td style="width: 9%;">'.$row["challan_qty"].'<td>'.$row["unit"].'</td></td>
                        <td style="width: 9%;">'.$row["status"].'</td>
                    </tr>';
                      $count++;
            }
        }
                  
       $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
            
        
        
        
    }else if($_GET["type"] == 'downloadDedustingMaterials') {
        $_GET['filename'] = 'DEDUSTING MATERIAL LOG '; $_GET['pdftype'] = 'onlyheader';  include('/pdfimp2.php');
        $html.='
        <h2 style="text-align:center">DEDUSTING MATERIAL LOG</h2>
        <table cellpadding="3">
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 9%;">Receiving Date</td>
                    <td style="width: 9%;">Receiving No</td>
                    <td style="width: 9%;">Vendor Name</td>
                    <td style="width: 14%;">Material Name</td>
                    <td style="width: 9%;">Material Type</td>
                    <td style="width: 9%;">Material Code</td>
                    <td style="width: 9%;">Grade</td>
                    <td style="width: 9%;">Containers</td>
                    <td style="width: 9%;">Dedusting By</td>
                    <td style="width: 9%;">Dedusting Date</td>
                </tr>';
          $i=1;
           $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.dedusting !='pending' AND m.material_type ='Raw Material' AND m.material_type LIKE '%".$_GET["material_subtype"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%' AND DATE(c.receiving_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $html.='
                    <tr nobr="true">
                        <td style="width: 5%;">'.$i.'</td>
                        <td style="width: 9%;" align="left">'.date('d-m-Y',strtotime($row["receiving_date"])).'</td>
                        <td style="width: 9%;">'.$row["document_no"].'</td>
                        <td style="width: 9%;">'.$row["vendor_name"].'</td>
                        <td style="width: 14%;">'.$row["material_name"].'</td>
                        <td style="width: 9%;">'.$row["material_type"].'</td>
                        <td style="width: 9%;">'.$row["material_code"].'</td>
                        <td style="width: 9%;">'.$row["grade"].'</td>
                        <td style="width: 9%;">'.$row["containers"].'</td>
                        <td style="width: 9%;">'.$row["dedusting_by"].'</td>
                        <td style="width: 9%;">'.date('d-m-Y',strtotime($row["dedusting_date"])).'</td>
                    </tr>';
                  $i++;
    		    }
    	    }
    	    $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }
    else if($_GET["type"] == 'dedustingMaterialPDF') {
    $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.id='".$_GET['id']."' ";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $dedusting_details = $row['dedusting_details'];
        $row['entry_by'] = $dedusting_details->check_by;
        $_GET['filename'] = 'DEDUSTING MATERIAL DETAILS'; $_GET['pdftype'] = 'onlyheader'; include("/pdfimp2.php");
        $html.='
        <h2 style="text-align:center">DEDUSTING MATERIAL DETAILS</h2>
        <table cellpadding="3">
            <tr>
                <td style="width:15%;"><b>Material Name:</b></td>
                <td style="width:85%;"><b>'.$row['material_name'].'</b></td>
            </tr>
            <tr>
                <td><b>Vendor Name:</b></td>
                <td>'.$row['vendor_name'].'</td>
            </tr>
             <tr>
                <td><b>Vendor Unit.:</b></td>
                <td style="width:40%;">'.$row['unit'].'</td>
                <td style="width:20%;"><b>	Manufacturer:</b></td>
                <td style="width:25%;">'.$row['vendor_name'].'</td>
            </tr>
            <tr>
                <td><b>Challan No.:</b></td>
                <td style="width:40%;">'.$row['challan_no'].'</td>
                <td style="width:20%;"><b>Challan Date:</b></td>
                <td style="width:25%;">'.date('d-m-Y',strtotime($row["challan_date"])).'</td>
            </tr>
            <tr>
                <td><b>PO No.:</b></td>
                <td>'.$row['po_no'].'</td>
                <td><b>PO Date:</b></td>
                <td>'.date('d-m-Y',strtotime($row["po_date"])).'</td>
            </tr>
            <tr>
                <td><b>Material Type:</b></td>
                <td>'.$row['material_type'].'</td>
                <td><b>Material Subtype:</b></td>
                <td>'.$row["material_subtype"].'</td>
            </tr>
            <tr>
                <td><b>Material Code:</b></td>
                <td>'.$row['material_code'].'</td>
                <td><b>Material Grade:</b></td>
                <td>'.$row["grade"].'</td>
            </tr>
            <tr>
                <td><b>PO Qty:</b></td>
                <td>'.$row['qty'].'</td>
                <td></td>
                <td></td>
            </tr>
        </table>
        <h3>Labeling Details:</h3>
        <table cellpadding="3">
            <tr style="text-align: center; background-color:#DDDAD9;">
                <td style="font-weight:bold;">Sr</td>
                <td style="font-weight:bold;">Batch No</td>
                <td style="font-weight:bold;">Pack Size</td>
                <td style="font-weight:bold;">Qty</td>
                <td style="font-weight:bold;">No of Containers</td>
                <td style="font-weight:bold;">Mfg Date</td>
                <td style="font-weight:bold;">Exp Date</td>
            </tr>';
            $j=1;
             $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                    
                $html.='<tr nobr="true">
                    <td>'.$j++.'</td>
                    <td>'.$row1['batch_no'].'</td>
                    <td>'.$row1['pack_size'].'</td>
                    <td>'.$row1['qty_received'].'</td>
                    <td>'.$row1['root_container'].'</td>
                    <td>'.date('d-m-Y',strtotime($row1['mfg_date'])).'</td>
                    <td>'.date('d-m-Y',strtotime($row1['exp_date'])).'</td>
                   
                </tr>';
                }
            }
            
    $html.='</table>
        <h3>Dedusting Details:</h3>
        <table cellpadding="2">
            <tr style="text-align: center; background-color:#DDDAD9;">
                <td style="font-weight:bold;">Dedusting</td>
                <td style="font-weight:bold;">Equipment Code</td>
                <td style="font-weight:bold;">Dedusting By</td>
                <td style="font-weight:bold;">Dedusting Date</td>
                <td style="font-weight:bold;">Start Time</td>
                <td style="font-weight:bold;">End Time</td>
                <td style="font-weight:bold;">Done By</td>
            </tr>';
        $row["dedusting_details"] = json_decode($row["dedusting_details"]);
        $dedusting_details=$row["dedusting_details"];
        // for($i=1;$i<count($dedusting_details);$i++){
        //     $dedusting=$dedusting_details[$i];
            $html.='
            <tr nobr="true">
                <td>Vacuum</td>
                <td>'.$dedusting_details->equipment_code.'</td>
                <td>'.$row['dedusting_by'].'</td>
                <td>'.date('d-m-Y',strtotime($row['dedusting_date'])).'</td>
                <td>'.$dedusting_details->from_time.'</td>
                <td>'.$dedusting_details->to_time.'</td>
                <td>'.$dedusting_details->area_cleaned_by.'</td>
                <td>'.$dedusting_details->equip_cleaned_to.'</td>
            </tr>
            <tr nobr="true">
                <td>Equipment Cleaning</td>
                <td>'.$dedusting_details->equipment_code.'</td>
                <td>'.$row['dedusting_by'].'</td>
                <td>'.date('d-m-Y',strtotime($row['dedusting_date'])).'</td>
                <td>'.$dedusting_details->from_time.'</td>
                <td>'.$dedusting_details->to_time.'</td>
                <td>'.$dedusting_details->area_cleaned_by.'</td>
                <td>'.$dedusting_details->equip_cleaned_to.'</td>
            </tr>
            <tr nobr="true">
                <td>Area Cleaning</td>
                <td>'.$dedusting_details->equipment_code.'</td>
                <td>'.$row['dedusting_by'].'</td>
                <td>'.date('d-m-Y',strtotime($row['dedusting_date'])).'</td>
                <td>'.$dedusting_details->from_time.'</td>
                <td>'.$dedusting_details->to_time.'</td>
                <td>'.$dedusting_details->area_cleaned_by.'</td>
                <td>'.$dedusting_details->equip_cleaned_to.'</td>
            </tr>';
      
    $html.='</table>
        <div></div>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false,'');
        $pdf->Output('', 'I');
    
  } else if($_GET['type'] == 'weighingMaterialLogPDF'){
        $_GET['filename'] = 'Weighing  of Material Log'; $_GET['pdftype'] = 'onlyheader';  include('/pdfimp2.php');
        $html.='
        <h2 style=text-align:center>Weighing  of Material Log</h2>
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 15%;">Receiving Date</td>
                    <td style="width: 12%;">Receiving No</td>
                    <td style="width: 10%;">Challan No</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 10%;">Material Code</td>
                    <td style="width: 18%;">Material Name</td>
                    <td style="width: 10%;">Accepted Qty</td>
                    <td style="width: 10%;">Containers</td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND weighing !='pending' AND m.material_type='Raw Material' AND DATE(c.inward_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY c.id DESC";
            //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND weighing !='pending' AND m.material_type='Raw Material'";
            //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.status='inprocess' AND weighing !='pending' AND c.material_type IN ('API', 'Excipient', 'Liquid')";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr>
                        <td style="width: 5%;">'.$counter++.'</td>
                        <td style="width: 15%;">'.date('d-m-Y',strtotime($row["receiving_date"])).'</td>
                        <td style="width: 12%;">'.$row["document_no"].'</td>
                        <td style="width: 10%;">'.$row["challan_no"].'</td>
                        <td style="width: 10%;">'.$row["material_type"].'</td>
                        <td style="width: 10%;">'.$row["material_code"].'</td>
                        <td style="width: 18%;">'.$row["material_name"].'</td>
                        <td style="width: 10%;">'.$row["challan_qty"].'</td>
                        <td style="width: 10%;">'.$row['containers'].'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    }
    else if($_GET["type"] == 'weighingMaterialPDF') {
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.id='".$_GET['id']."'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
      
        $_GET['filename'] = "Weighing of Material"; $_GET['pdftype'] = 'onlyheader'; include("/pdfimp2.php");
        $html.='
        <h2 style=text-align:center>Weighing of Material</h2>
        <table cellpadding="2">
            <tr>
                <td style="width:15%;"><b>Material Name:</b></td>
                <td style="width:85%;"><b>'.$row['material_name'].'</b></td>
            </tr>
            <tr>
                <td><b>Vendor Name:</b></td>
                <td>'.$row["vendor_name"].'</td>
            </tr>
            <tr>
                <td><b>Challan No.:</b></td>
                <td style="width:40%;">'.$row['challan_no'].'</td>
                <td style="width:20%;"><b>Challan Date:</b></td>
                <td style="width:25%;">'.date('d-m-Y',strtotime($row["challan_date"])).'</td>
            </tr>
            <tr>
                <td><b>PO No.:</b></td>
                <td>'.$row['po_no'].'</td>
                <td><b>PO Date:</b></td>
                <td>'.date('d-m-Y',strtotime($row['po_date'])).'</td>
            </tr>
            <tr>
                <td><b>Material Type:</b></td>
                <td>'.$row['material_type'].'</td>
                <td><b>Material Subtype:</b></td>
                <td>'.$row["material_subtype"].'</td>
            </tr>
            <tr>
                <td><b>Material Code:</b></td>
                <td>'.$row['material_code'].'</td>
                <td><b>Material Grade:</b></td>
                <td>'.$row["grade"].'</td>
            </tr>
            <tr>
                <td><b>PO Qty:</b></td>
                <td>'.$row['qty'].''.$row["unit"].'</td>
                <td></td>
                <td></td>
            </tr>
        </table>
        <div></div>';
        
        $html.='<h3>Labeling Details:</h3>
        <table cellpadding="3" border="1">
            <tr style="text-align: center; background-color:#DDDAD9;">
                <td style="width:10%;">Sr</td>
                <td style="width:20%;">Batch No</td>
                <td style="width:10%;">Qty</td>
                <td style="width:20%;">No of Containers</td>
                <td style="width:20%;">Mfg Date</td>
                <td style="width:20%;">Exp Date</td>
            </tr>';
            $i=1;
            $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
            $result1 = $conn->query($sql1);
            if ($result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                $row1["weight"] = json_decode($row1["weight"]);
                $html.='
                <tr>
                    <td style="width:10%;">'.$i++.'</td>
                    <td style="width:20%;">'.$row1["batch_no"].'</td>
                    <td style="width:10%;">'.$row["received_qty"].'</td>
                    <td style="width:20%;">'.$row["containers"].'</td>
                    <td style="width:20%;">'.date('M-Y',strtotime($row1["mfg_date"])).'</td>
                    <td style="width:20%;">'.date('M-Y',strtotime($row1["exp_date"])).'</td>
                </tr>';
                }
            }
            $html.='
            </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('raw.pdf', 'I');
    }
   else if($_GET['type'] == 'receivingMaterialLogPDF'){
        $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('/pdfimp2.php');
        $html.='
        <<h2 style="text-align:center">Receiving of Material Log</h2>
        <table cellpadding="3">
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 4%;">Sr.</td>
                    <td style="width: 10%;">Receiving Date</td>
                    <td style="width: 10%;">Receiving No</td>
                    <td style="width: 10%;">Challan No</td>
                    <td style="width: 10%;">Material Type</td>
                    <td style="width: 8%;">Grade</td>
                    <td style="width: 8%;">Material Code</td>
                    <td style="width: 8%;">Material Name</td>
                    <td style="width: 10%;">No Of Containers</td>
                    <td style="width: 10%;">Short / Extra Qty</td>
                    <td style="width: 12%;">Qty Received</td>
                </tr><tbody>';
              
            $sql = "SELECT c.*,c1.entry_by,c1.approve_by,c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_type LIKE '%".$_GET["material_type"]."%' AND c.status LIKE '%".$_GET["status"]."%'AND DATE(c.receiving_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' GROUP BY c.id ORDER BY c.id DESC";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr nobr="true">
                        <td style="width: 4%;">'.$counter++.'</td>
                        <td style="width: 10%;">'.date('d-m-Y',strtotime($row['receiving_date'])).'</td>
                        <td style="width: 10%;">'.$row['document_no'].'</td>
                        <td style="width: 10%;">'.$row['challan_no'].'</td>
                        <td style="width: 10%;">'.$row['material_type'].'</td>
                        <td style="width: 8%;">'.$row['grade'].'</td>
                        <td style="width: 8%;">'.$row['material_code'].'</td>
                        <td style="width: 8%;">'.$row['material_name'].'</td>
                        <td style="width: 10%;">'.$row['containers'].'</td>
                        <td style="width: 10%;">'.$row['qty_status'].'</td>
                        <td style="width: 12%;">'.$row['received_qty'].'<td>'.$row['unit'].'</td></td>
                    </tr>';
    		    }
    	    }
    	    $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
    }else if($_GET["type"] == 'receivingMaterialPDF') {
        if($_GET['pdfsign'] == 'manual'){$_GET['pdftype'] = 'onlyheader';}else{$_GET['pdftype'] = 'onlyheader';}
        $_GET['filename'] = "Receiving of Material Log"; include("/pdfimp2.php");
        $html="";
          //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND c.id = '".$_GET["id"]."'";
        //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  GROUP BY c.id ORDER BY c.id DESC";
            $sql="SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, v.vendor_name,v2.vendor_name as manufacturer_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c.vendor_no=v.vendor_no LEFT JOIN vendor v2 ON c.manufacturer_no=v2.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND c.id = '".$_GET["id"]."'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
            $html.='<h3 style="text-align:centre;">Receiving of Material Log</h3>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:25%;"><b>Material Name:</b></td>
                <td style="width:75%;">'.$row['material_name'].'</td>
            </tr>
              
            <tr>
                <td style="width:25%;"><b>Vendor Name:</b></td>
                <td style="width:75%;">'.$row['manufacturer_name'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>Vendor Unit:</b></td>
                <td style="width:25%;">'.$row['unit'].'</td>
                <td style="width:25%;"><b>Manufacturer:</b></td>
                <td style="width:25%;">'.$row['manufacturer_name'].'</td>
            </tr>
           <tr>
                <td style="width:25%;"><b>Challan No.:</b></td>
                <td style="width:25%;">'.$row['challan_no'].'</td>
                <td style="width:25%;"><b>Challan Date:</b></td>
                <td style="width:25%;">'.date('d-m-Y',strtotime($row['challan_date'])).'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>PO No.:</b></td>
                <td style="width:25%;">'.$row['po_no'].'</td>
                <td style="width:25%;"><b>PO Date:</b></td>
                <td style="width:25%;">'.$row['po_date'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>Material Type:</b></td>
                <td style="width:25%;">'.$row['material_type'].'</td>
                <td style="width:25%;"><b>Material Subtype:</b></td>
                <td style="width:25%;">'.$row['material_subtype'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>Material Code:</b></td>
                <td style="width:25%;">'.$row['material_code'].'</td>
                <td style="width:25%;"><b>Material Grade:</b></td>
                <td style="width:25%;">'.$row['grade'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>PO Qty:</b></td>
                <td style="width:75%;">'.$row['qty'].'</td>
            </tr>';
            }
        }
     $html.='</table>';
       
        $html.=' <h3>Labeling Details:</h3>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:10%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:15%; text-align:centre;"><b>Batch No</b></td>
                <td style="width:15%; text-align:centre;"><b>Pack Size</b></td>
                <td style="width:15%; text-align:centre;"><b>Qty</b></td>
                <td style="width:15%; text-align:centre;"><b>No Of Containers</b></td>
                <td style="width:15%; text-align:centre;"><b>Mfg Date.</b></td>
                <td style="width:15%; text-align:centre;"><b>Exp Date.</b></td>
            </tr>';
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND c.id = '".$_GET["id"]."'";
        //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  GROUP BY c.id ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
            $html.='<tr>
                <td style="width:10%;">'.$i.'</td>
                <td style="width:15%;">'.$row['batch_no'].'</td>
                <td style="width:15%;">'.$row['pack_size'].'</td>
                <td style="width:15%;">'.$row['received_qty'].'</td>
                <td style="width:15%;">'.$row['containers'].'</td>
                <td style="width:15%;">'.date('d-m-Y',strtotime($row['mfg_date'])).'</td>
                <td style="width:15%;">'.date('d-m-Y',strtotime($row['exp_date'])).'</td>
            </tr>';
            $i++;
            }
        }
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND c.id = '".$_GET["id"]."'";
        //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  GROUP BY c.id ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
        $html.='</table>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:25%;"><b>Condition / Cleanliness of Containers:</b></td>
                <td style="width:25%;">'.$row['container_condition'].'</td>
                <td style="width:25%;"><b>Integrity of Containers Seal:</b></td>
                <td style="width:25%;">'.$row['container_seal'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>Status of Document Received:</b></td>
                <td style="width:25%;">'.$row['document_status'].'</td>
                <td style="width:25%;"><b>Specific storage condition:</b></td>
                <td style="width:25%;">'.$row['storage_condition'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>Cleanliness of Vehicle:</b></td>
                <td style="width:25%;">'.$row['vehicle_cleanliness'].'</td>
                <td style="width:25%;"><b>Tanker cleaning certificate:</b></td>
                <td style="width:25%;">NA</td>
            </tr>';
            }
        }
         $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND c.id = '".$_GET["id"]."'";
        //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  GROUP BY c.id ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
        $html.='</table>
        <h3>Received Quantity Details:</h3>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:25%;"><b>Challan Qty: gm</b></td>
                <td style="width:25%;">'.$row['challan_qty'].'</td>
                <td style="width:25%;"><b>Received Qty: gm</b></td>
                <td style="width:25%;">'.$row['received_qty'].'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>No. of Containers:</b></td>
                <td style="width:25%;">'.$row['containers'].'</td>
                <td style="width:25%;"><b>Damage Container Observed:</b></td>
                <td style="width:25%;">'.$row['damage'].'</td>
            </tr>';
            }
        }
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND c.id = '".$_GET["id"]."'";
        //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%' AND DATE(c.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'  GROUP BY c.id ORDER BY c.id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
             $row["receiving_details"]= json_decode($row["receiving_details"]);
             $receive=$row["receiving_details"];
        $html.='</table>
        <h3>Packing Details:</h3>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:25%;"><b>Packing Intactness / Condition:</b></td>
                <td style="width:25%;">'.$receive->packing_condition.'</td>
                <td style="width:25%;"><b>Outer Packing:</b></td>
                <td style="width:25%;">'.$receive->outer_packing.'</td>
            </tr>
            <tr>
                <td style="width:25%;"><b>Container type:</b></td>
                <td style="width:25%;">'.$receive->container_type.'</td>
                <td style="width:25%;"><b>Container Subtype:</b></td>
                <td style="width:25%;">'.$receive->container_subtype.'</td>
            </tr>';
            }
        }
        $html.='</table>
        <h3>Transporter’s Details:</h3>
        <table cellpadding="5" border="1">
            <tr>
                <td style="width:50%; text-align:centre;"><b>Vehicle Condition</b></td>
                <td style="width:50%; text-align:centre;"><b>COA Received</b></td>
            </tr>
            <tr>
                <td style="width:50%;">'.$receive->vehicle_condition.'</td>
                <td style="width:50%;">'.$receive->coa_received.'</td>
            </tr>
        </table><div></div>';
         $html.='<table cellpadding="5" border="1">
         <tr>
         <th style="width:25%"><b></b></th>
          <th style="width:25%"><b>PREPARED BY</b></th>
           <th style="width:25%"><b>REVIEWED BY</b></th>
            <th style="width:25%"><b>APPROVED BY</b></th>
         </tr>
         <tr>
         <td style="width:25%"><b>Sign/Date</b></td>
          <td style="width:25%"></td>
           <td style="width:25%"></td>
            <td style="width:25%"></td>
         </tr>
         </table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('', 'I');
    }else if ($_GET["type"] == "grnLabelsPDF"){
        // $sql = "SELECT * FROM challan_materials WHERE id='".$_GET['id']."'";
        // $result = $conn->query($sql);
        // if ($result->num_rows > 0) {
        //     while ($row = $result->fetch_assoc()) {
        //         $sql1 = "SELECT * FROM material WHERE material_code = '".$row['material_code']."'";
        //         $result1 = $conn->query($sql1);
        //          $row1 = $result1->fetch_assoc();
            $sql = "SELECT c.*, m.material_type, m.material_subtype, m.grade, m.material_name, c1.inward_no, DATE(c.receiving_date) as receiving_date FROM challan_materials c LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN challan c1 ON c.inward_no=c1.inward_no WHERE m.material_type='Raw Material' AND c.id='".$_GET['id']."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $batches = json_decode($row["batches"]);
                    for ($i = 0; $i < count($batches); $i++) {
                        $batch = $batches[$i];
                        $batch->id = $row["id"];
                        $batch->id = $row["id"];
                            $batch->inward_no = $row["inward_no"];
                            $batch->material_name = $row["material_name"];
                            $batch->material_code = $row["material_code"];
                            $batch->grade = $row["grade"];
                            $batch->manufacturer = $row["manufacturer"];
                            $batch->vendor_no = $row["vendor_no"];
                            $batch->receiving_date = $row["receiving_date"];
                            $mfg_date=$batch->mfg_date;
                            $exp_date=$batch->exp_date;
                            $batch_no=$batch->batch_no;
                            $total_containers=$batch->total_containers;
                            $qty_received=$batch->qty_received;
                            $pack_size=$batch->pack_size;
                        $sql1 = "SELECT * FROM label WHERE label_type='GRN' AND material_code='".$row["material_code"]."' AND inward_no='".$row["inward_no"]."' AND batch_no='".$batch->batch_no."'";
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows == 0) {
                             $output[] = $batch;
                        }
                    }
                    class MYPDF extends TCPDF {
                        public function Header() {}
                        public function Footer() {}
                    }
                    $pdf = new MYPDF (PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                    $pdf->SetMargins(10, 10, 10, 10);
                    $pdf->SetAutoPageBreak(TRUE, 10);
                    $pdf->AddPage('P', 'A4');
                    $pdf->SetFont ('Times', '', '11' , '', 'default', true );
                    $j=1;
                    for ($i=1; $i <= $total_containers; $i++) {
                        $html.='&nbsp;<br>
                        <table cellpadding="-5" style="width:100%;">
                            <tr>
                                <td style="width:100%;">
                                    <table border="1" cellpadding="2" nobr="true" style="background-color:#C4A484">
                                        <tr>
                                            <td style="width:22%;"><br><br><img src="../upload/User/logo.png" style="width: 250px; height: 100px;"></td>
                                            <td style="width:78%;">
                                                <table>
                                                    <tr>
                                                        <td style="width:100%; text-align:center;font-weight:bold;font-size:14px;">Material Status Lable</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width:100%;font-size:12px;font-weight:bold;text-align:center;">Cyclone Pharmaceuticals Pvt. Ltd.</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width:100%;text-align:center;"><b>Address:</b>104 Garnet Bay, Near Shereton Hotel,Behind Chandhere Complex,Viman Nagar,Pune 411014</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width:22%; font-weight:bold;text-align:center;"><br><br><br><br>Quarantine</td>
                                            <td style="width:78%;">
                                                <table>
                                                    <tr>
                                                        <td style="width:50%;"><b>GRN No:</b>'.$row['grn_no'].'</td>
                                                        <td style="width:50%;"><b>GRN Dt:</b>'.date('d-m-Y',strtotime($row['grn_date'])).'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width:100%;"><b>Material Name:</b>'.$row['material_name'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width:50%;"><b>M.Code:</b>'.$row['material_code'].'</td>
                                                        <td style="width:50%;"><b>Grade:</b>'.$row['grade'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width:50%;"><b>Mfg.Dt:</b>'.date("d-m-y",strtotime($mfg_date)).'</td>
                                                        <td style="width:50%;"><b>Exp.Dt:</b>'.date("d-m-y",strtotime($exp_date)).'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width:50%;"><b>Vendor:</b></td>
                                                        <td style="width:50%;"><b>Mfg.By:</b>'.$row["manufacturer"].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width:50%;"><b>Container No:</b>'.$j++.'</td>
                                                        <td style="width:50%;"><b>No.of.container:</b>'.$total_containers.'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width:50%;"><b>Received Dt:</b>'.date("d-m-y",strtotime($row['receiving_date'])).'</td>
                                                        <td style="width:50%;"><b>Received Qty:</b>'.$row['received_qty'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width:100%;"><b>Pack Size:</b>'.$pack_size.'</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="width:50%;"><b>GRN By:</b>'.$row['received_by'].'</td>
                                                        <td style="width:50%;"><b>Checked By:</b>'.$row['grncheck_by'].'</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width:60%;">
                                                <table>
                                                    <tr>
                                                        <td style="width:50%;"><b>Sop No:</b></td>
                                                        <td style="width:50%;"><b>Format No:</b></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td style="width:40%;text-align:center;"><b>Barcode</b><br><br></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>';
                    // $i++;
                    }
                }
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('labels.pdf', 'I');
        }
    }
    else if ($_GET['type'] == 'downloadRetestCalendar'){
        $_GET['filename'] = 'Retest Calender'; $_GET['pdftype'] = 'onlyheader';  include('/pdfimp2.php');
        $html.='
        <h2 style="text-align:center">Retest Calender</h2>
        <table cellpadding="5">
            <tr style="text-align: center; background-color:#DDDAD9;">
                <td style="width:5%; text-align:centre;"><b>Sr.</b></td>
                <td style="width:9%; text-align:centre;"><b>Challan For</b></td>
                <td style="width:9%; text-align:centre;"><b>Material Type</b></td>
                <td style="width:9%; text-align:centre;"><b>Material Code</b></td>
                <td style="width:9%; text-align:centre;"><b>Material Name</b></td>
                <td style="width:7%; text-align:centre;"><b>Grade</b></td>
                <td style="width:7%; text-align:centre;"><b>Batch No</b></td>
                <td style="width:7%; text-align:centre;"><b>Grn No.</b></td>
                <td style="width:5%; text-align:centre;"><b>Medicap lot no</b></td>
                <td style="width:7%; text-align:centre;"><b>Release Date</b></td>
                <td style="width:7%; text-align:centre;"><b>Exp Date</b></td>
                <td style="width:7%; text-align:centre;"><b>Retest Date</b></td>
                <td style="width:7%; text-align:centre;"><b>Due Days</b></td>
                <td style="width:7%; text-align:centre;"><b>status</b></td> 
            </tr>';
            $i=1;
            $sql = "SELECT s.vendor_no, s.material_code, m.material_subtype, m.material_name, m.grade, s.batch_no, s.qty, s.unit, s.mfg_date, s.exp_date, s.grn_no, s.ar_no, DATE(s.approve_date) as approve_date FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code WHERE s.status='Approved' AND s.material_code LIKE 'R%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT retest_period FROM specification WHERE material_code='".$row["material_code"]."' AND status='approve'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["retest_period"] = $row1["retest_period"];
                    }
                }
                $row["retest_date"] = date('Y-m-d', strtotime("+".$row["retest_period"]." months", strtotime($row["approve_date"])));
                
                $todays = date("Y-m-d", $timestamp);
                if (($todays <= $row["retest_date"])) {
                    $diff = abs(strtotime($row["retest_date"]) - strtotime($todays));
                    $dtF = new \DateTime('@0');
                    $dtT = new \DateTime("@$diff");
                    $row["due_days"] = $dtF->diff($dtT)->format('%a');
                } else {
                    $row["due_days"] = 0;
                }
                    $html.='
            <tr nobr="true">
                <td style="width:5%;">'.$i.'</td>
                <td style="width:9%;">'.$row[''].'</td>
                <td style="width:9%;">'.$row['material_subtype'].'</td>
                <td style="width:9%;">'.$row['material_code'].'</td>
                <td style="width:9%;">'.$row['material_name'].'</td>
                <td style="width:7%;">'.$row['grade'].'</td>
                <td style="width:7%;">'.$row['batch_no'].'</td>
                <td style="width:7%;">'.$row['grn_no'].'</td>
                <td style="width:5%;">'.$row['ar_no'].'</td>
                <td style="width:7%;">'.$row['release_date'].'</td>
                <td style="width:7%;">'.date('M-Y',strtotime($row['exp_date'])).'</td>
                <td style="width:7%;">'.date('d-m-Y',strtotime($row['retest_date'])).'</td>
                <td style="width:7%;">'.$row['due_days'].'</td>
                <td style="width:7%;"></td> 
            </tr>';
                    $i++;
    		    }
    	    }
    	    $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
        }
        else if ($_GET['type'] == 'DamageLogPDF'){
        $_GET['filename'] = 'Weighing of Material Log'; $_GET['pdftype'] = 'onlyheader';  include('/pdfimp2.php');
        $html.='
        <h2 style="text-align:center">Weighing of Material Log</h2>
        <table cellpadding="5">
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 10%;">Sr.</td>
                    <td style="width: 10%;">Vendor Name</td>
                    <td style="width: 20%;">Material Type</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 15%;">Material Name	</td>
                    <td style="width: 10%;">Grade</td>
                    <td style="width: 10%;">Containers</td>
                    <td style="width: 10%;">Status</td>
                     
                </tr>';
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.receiving='approve' AND c.dedusting ='approve' AND c.damage NOT IN ('no') AND m.material_type='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."%' AND c.status LIKE '%".$_GET["status"]."%'";
            //$sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.receiving='approve' AND c.dedusting ='approve' AND c.damage NOT IN ('pending', 'no') AND m.material_type='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."%' AND c.status LIKE '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr>
                        <td style="width: 10%;" align="center">'.$counter++.'</td>
                        <td style="width: 10%;">'.$row["vendor_name"].'</td>
                        <td style="width: 20%;">'.$row["material_type"].'</td>
                        <td style="width: 15%;">'.$row["material_code"].'</td>
                        <td style="width: 15%;">'.$row["material_name"].'</td>
                        <td style="width: 10%;">'.$row["grade"].'</td>
                        <td style="width: 10%;">'.$row["containers"].'</td>
                        <td style="width: 10%;">'.$row["status"].'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    } else if ($_GET['type'] == 'DamageInspectionLog'){
        $_GET['filename'] = 'Damage Inspection Log'; $_GET['pdftype'] = 'onlyheader';  include('/pdfimp2.php');
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.receiving='approve' AND c.dedusting ='approve' AND c.damage NOT IN ('pending', 'no') AND m.material_type='Raw Material' AND c.id = '".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
           $html.='
           <h2 style="text-align:center">Damage Inspection Log</h2>
           <table cellpadding="5" border="1">
            <tr> 
                <td style="width:25%;"><b>Material Name:</b></td>
                <td style="width:75%;">'.$row['material_name'].'</td>
            </tr>
            
            <tr> 
                <td style="width:25%;"><b>Vendor Name:</b></td>
                <td style="width:75%;">'.$row['vendor_name'].'</td>
            </tr>
            <tr> 
                <td style="width:25%;"><b>Challan No.:</b></td>
                <td style="width:25%;">'.$row['challan_no'].'</td>
                <td style="width:25%;"><b>Challan Date:</b></td>
                <td style="width:25%;">'.date('d-m-Y',strtotime($row['challan_date'])).'</td>
            </tr>
            <tr> 
                <td style="width:25%;"><b>PO No.:</b></td>
                <td style="width:25%;">'.$row['po_no'].'</td>
                <td style="width:25%;"><b>PO Date:</b></td>
                <td style="width:25%;">'.date('d-m-Y',strtotime($row['po_date'])).'</td>
            </tr>
            <tr> 
                <td style="width:25%;"><b>Material Type:</b></td>
                <td style="width:25%;">'.$row["material_type"].'</td>
                <td style="width:25%;"><b>Material Subtype:</b></td>
                <td style="width:25%;">'.$row["material_subtype"].'</td>
            </tr>
            <tr> 
                <td style="width:25%;"><b>Material Code:</b></td>
                <td style="width:25%;">'.$row["material_code"].'</td>
                <td style="width:25%;"><b>Material Grade:</b></td>
                <td style="width:25%;">'.$row["grade"].'</td>
            </tr>';
            }
        }
        
            $html.='</table>
           <h3>Labeling Details:</h3>
    	    <table cellpadding="5" border="1">
    	        <tr>
    	            <td style="width:35%;">Batch No:</td>
    	            <td style="width:35%;">Mfg. Date:</td>
    	            <td style="width:30%;">Expiry Date:</td>
    	        </tr>
    	         <tr>
    	            <td style="width:35%;"></td>
    	            <td style="width:35%;"></td>
    	            <td style="width:30%;"></td>
    	        </tr>
    	    </table>';
    	    $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.user_no='".$_GET["user_no"]."' AND c.status='inprocess' AND c.receiving='approve' AND c.dedusting ='approve' AND c.damage NOT IN ('pending', 'no') AND m.material_type='Raw Material' AND c.id = '".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["damage_details"]= json_decode($row["damage_details"]);
             $damage=$row["damage_details"];
    	    $html.='<h3>Damage Containers Details:</h3>
    	    <table cellpadding="5" border="1">
    	        <tr>
    	            <td style="width:35%;"><b>Container No.</b></td>
    	            <td style="width:35%;"><b>Condition</b></td>
    	            <td style="width:30%;"><b>Remark</b></td>
    	        </tr>
    	        <tr>
    	            <td style="width:35%;">'.$damage->container_no.'</td>
    	            <td style="width:35%;"></td>
    	            <td style="width:30%;"></td>
    	        </tr>';
            }
        }
    	    $html.='</table>
    	    <div></div>
    	    <table cellpadding="5">
    	    <tr>
    	        <td style="width:100%;">Remark: </td>
    	    </tr>
    	     <tr>
                        <td >'.$row['grn_remark'].'</td>
                       </tr>
    	   </table>';
            
            
       EOD;
            $pdf->writeHTML($html, true, false, false, false,'');
            $pdf->Output('log.pdf', 'I');
    } else if ($_GET['type'] == 'DamageInspectionDigitalLog'){
        $_GET['filename'] = 'Damage Container Inspection Log'; $_GET['pdftype'] = 'onlyheader';  include('/pdfimp2.php');
        $html.='
        <h2 style="text-align:center">Damage Container Inspection Log</h2>
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 20%;">Vendor Name</td>
                    <td style="width: 15%;">Containers</td>
                    <td style="width: 15%;">Material Code</td>
                    <td style="width: 15%;">Material Name</td>
                    <td style="width: 15%;">Material Type</td>
                    <td style="width: 15%;">Grade</td>
                </tr>
            </thead>
            <tbody>';
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.status='inprocess' AND c.receiving='approve' AND c.dedusting ='approve' AND c.damage NOT IN ('pending', 'no') AND m.material_type='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."%' AND c.status LIKE '%".$_GET["status"]."%'";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr>
                        <td style="width: 5%;" align="center">'.$counter++.'</td>
                        <td style="width: 20%;">'.$row["vendor_name"].'</td>
                        <td style="width: 15%;" align="center">'.$row["containers"].'</td>
                        <td style="width: 15%;" align="center">'.$row["material_code"].'</td>
                        <td style="width: 15%;" align="center">'.$row["material_name"].'</td>
                        <td style="width: 15%;" align="center">'.$row["material_type"].'</td>
                        <td style="width: 15%;" align="center">'.$row["grade"].'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
        
    } else if ($_GET['type'] == 'RawMaterialQuarantineStockBook'){
       $_GET['filename'] = "Raw Material Quarantine Stock Book"; $_GET['pdftype'] = 'onlyheader'; include("/pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Raw Material Quarantine Stock Book</h2>
        <table cellpadding="5">
            <thead>
                <tr style="text-align: center; background-color:#DDDAD9;">
                   <td style="width:5%; text-align:centre;"><b>Sr.</b></td>
            <td style="width:10%; text-align:centre;"><b>Material For</b></td>
            <td style="width:5%; text-align:centre;"><b>GRN No.</b></td>
            <td style="width:9%; text-align:centre;"><b>Vendor Name</b></td>
            <td style="width:9%; text-align:centre;"><b>Material Type</b></td>
            <td style="width:10%; text-align:centre;"><b>Material Code</b></td>
            <td style="width:10%; text-align:centre;"><b>Material Name</b></td>
            <td style="width:5%; text-align:centre;"><b>Grade</b></td>
            <td style="width:7%; text-align:centre;"><b>Batch No.</b></td>
            <td style="width:10%; text-align:centre;"><b>Received Qty</b></td>
            <td style="width:10%; text-align:centre;"><b>Damage Containers	</b></td>
            <td style="width:10%; text-align:centre;"><b>Received Date</b></td>
                </tr>
                  if (!isset($_GET["material_name"])) {
            $_GET["material_name"] = "";
        }
            </thead>
            <tbody>';
            
            $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Raw Material' AND s.material_code LIKE '%".$_GET["material_code"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."' AND m.material_name LIKE '%".$_GET["material_name"]."%'";
    	$result = $conn->query($sql);
    	$output = Array();
    	if($result->num_rows > 0){
    		while ($row = $result->fetch_assoc()) {
        $html.='<tr>
            <td style="width:5%;"></td>
            <td style="width:10%;"></td>
            <td style="width:5%;"></td>
            <td style="width:9%;"></td>
            <td style="width:9%;"></td>
            <td style="width:10%;"></td>
            <td style="width:10%;"></td>
            <td style="width:5%;"></td>
            <td style="width:7%;"></td>
            <td style="width:10%;"></td>
            <td style="width:10%;"></td>
            <td style="width: 10%;" align="center">'.$row["grade"].'</td>
                    </tr>';
    		    }
    	    }
    	    $html.='
    	    </tbody>
    	</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
    }else if ($_GET["type"] == "RawMaterialLog"){
       $_GET['filename'] = 'RAW MATERIAL  LOG';  $_GET['pdftype'] = 'onlyheader';  include('/pdfimp2.php');
            $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row["receiving_details"] = json_decode($row["receiving_details"]);
                    $output[] = $row;
                }
            }
                //$sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                //$result1 = $conn->query($sql1);
               // $row1 = $result1->fetch_assoc();
                //$sql2 = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
               // $result2 = $conn->query($sql2);
                
               // $sql21 = "SELECT * FROM grn WHERE grn_no='".$row["grn_no"]."'";
                //$result21 = $conn->query($sql21);
                //$row21 = $result21->fetch_assoc();
                
                //$sql3 = "SELECT * FROM material_received WHERE receiving_no='".$row2["receiving_no"]."'";
               // $result3 = $conn->query($sql3);
                //$row3 = $result3->fetch_assoc();
                
               // $sql5 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                //$result5 = $conn->query($sql5);
                //$row5 = $result5->fetch_assoc();
                
                //$sql9 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
                //$result9 = $conn->query($sql9);
               // $row9 = $result9->fetch_assoc();
                
               // $sql2=" select * from vendor where vendor_name='".$row2["vendor_name"].";";
               // $result2=$conn->query($sql2);
               // $row2 = $result2->fetch_assoc();
            $html.='
            <style>
            .tdall { border:solid 1px BCBBBA; }
            .tdb { border-bottom:solid 1px BCBBBA; }
            .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
            </style>
             <style>td { border:solid 1px BCBBBA; border-right:solid 1px BCBBBA;}</style>
                <table cellpadding="2">
                    <thead border-right:solid 1px BCBBBA; >
                    <tr style="background-color:#DDDAD9;font-weight:bold;" border-right:solid 1px BCBBBA;>
                        <td style="width:9%;">Material Name</td>
                        <td style="width:9%;">Vendor Name</td>
                        <td style="width:9%;">Challan No</td>
                        <td style="width:9%;">Challan date</td>
                        <td style="width:9%;">PO No</td>
                        <td style="width:9%;">PO Date</td>
                        <td style="width:9%;">Material Type</td>
                        <td style="width:9%;">Material Subtype</td>
                        <td style="width:9%;">Material Code</td>
                        <td style="width:9%;">Material grade</td>
                        <td style="width:9%;"border:solid 1px BCBBBA;>PO Qty</td>
                    </tr>
                    </thead>
                    </tbody>';
                //$sql2 = "SELECT m.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM material s LEFT JOIN vendor v ON v.vendor_no=m.vendor_no WHERE spec_type LIKE 'Raw Material%' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND m.grade LIKE '%".$_GET["grade"]."%' AND v.status LIKE '%".$_GET["status"]."%'";
                //$result2 = $conn->query($sql2);
                $sql2 = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE spec_type LIKE 'Raw Material%' AND s.status='checked'";
                $result2 = $conn->query($sql2);
                //$sql2 = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
                //$result2 = $conn->query($sql2);
                if($result2->num_rows > 0){
                while($row2 = $result2->fetch_assoc()) {
                    $html.='
                        <tr nobr="true">
                            <td style="width:9%;">'.$row2["material_name"].'</td>
                            <td style="width:9%;">'.$row2["vendor_name"].'</td>
                            <td style="width:9%;">'.$row2["challan_no"].'</td>
                            <td style="width:9%;">'.$row2["challan_date"].'</td>
                            <td style="width:9%;">'.$row2["po_no"].'</td>
                            <td style="width:9%;">'.$row2["po_date"].'</td>
                            <td style="width:9%;">'.$row2["material_type"].'</td>
                            <td style="width:9%;">'.$row2["material_subtype"].'</td>
                            <td style="width:9%;">'.$row2["material_code"].'</td>
                            <td style="width:9%;">'.$row2['grade'].'</td>
                            <td style="width:9%;">'.$row2['po_qty'].'</td>
                        </tr>';
                        
                }
                $html.='</tbody></table>';
            $html.='   
                <style>
                    .tdall { border:solid 1px BCBBBA; }
                    .tdb { border-bottom:solid 1px BCBBBA; }
                    .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
                </style>
                <p style="text-align:center;"><b>Labeling Details:</b></p>
            <table style="border:solid 1px BCBBBA;" cellpadding="2">
                <tr>
                    <td class="tdb"><b>Batch No:</b></td>
                    <td class="tdb"> '.$row["batch_no"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>Mfg. Date:</b></td>
                    <td class="tdb"> '.$row["mfg_date"].'</td>
                </tr>
                 <tr>
                    <td class="tdb"><b>Expiry Date:</b></td>
                    <td class="tdb"> '.$row["expiry_date"].'</td>
                </tr>
            </table>
            <div></div>';
            $html.='   
            <style>
            .tdall { border:solid 1px BCBBBA; }
            .tdb { border-bottom:solid 1px BCBBBA; }
            .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
            </style>
            <p style="text-align:center;"><b>Received Quantity Details:</b></p>
            <table style="border:solid 1px BCBBBA;" cellpadding="2">
                <tr>
                    <td class="tdb"><b>Pack Size:</b></td>
                    <td class="tdb"> '.$row["pack_size"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>Challan Qty: Kg:</b></td>
                    <td class="tdb"> '.$row["challan_qty"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>Received Qty: Kg:</b></td>
                    <td class="tdb"> '.$row["received_qty"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>No. of Containers: Kg:</b></td>
                    <td class="tdb"> '.$row["no_of_containers"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>Damage Container Observed:</b></td>
                    <td class="tdb"> '.$row["damage_container"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>Outer Damage Containers:</b></td>
                    <td class="tdb"> '.$row["outer_container"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>inner Damage Containers:</b></td>
                    <td class="tdb"> '.$row["inner_container"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>Remark (if Any):</b></td>
                    <td class="tdb"> '.$row["remark"].'</td>
                </tr>
            </table>
            <div></div>';
            $html.='
            <style>
                .tdall { border:solid 1px BCBBBA; }
                .tdb { border-bottom:solid 1px BCBBBA; }
                .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
            </style>
                <p style="text-align:center;"><b>Packing Details:</b></p>
            <table style="border:solid 1px BCBBBA;" cellpadding="2">
                <tr>
                    <td class="tdb"><b>Packing Intactness / Condition:</b></td>
                    <td class="tdb"> '.$row["packing"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>Outer Packing: Kg:</b></td>
                    <td class="tdb"> '.$row["outer_packing"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>Container type:: Kg:</b></td>
                    <td class="tdb"> '.$row["container_type"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>Container Subtype: Kg:</b></td>
                    <td class="tdb"> '.$row["container_subtype"].'</td>
                </tr>
            </table>
            <div></div>';
            $html.='
            <style>
            .tdall { border:solid 1px BCBBBA; }
            .tdb { border-bottom:solid 1px BCBBBA; }
            .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
            </style>
            <p style="text-align:center;"><b>Transporter’s Details::</b></p>
            <table style="border:solid 1px BCBBBA;" cellpadding="2">
                <tr>
                    <td class="tdb"><b>Vehicle Condition:</b></td>
                    <td class="tdb"> '.$row["vehical_condition"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>COA Received: Kg:</b></td>
                    <td class="tdb"> '.$row["coa_recived"].'</td>
                </tr>
                <tr>
                    <td class="tdb"><b>COA : Kg:</b></td>
                    <td class="tdb"> '.$row["coa"].'</td>
                </tr>
            </table>
            <div></div>';
            
            EOD;
            $pdf->writeHTML($html, true, false, false, false,'');
            $pdf->Output('raw.pdf', 'I');
        }
    }
    else if ($_GET["type"] == "RawMaterialDigitalLog"){
        $_GET['filename'] = 'RAW MATERIAL Digital LOG';  $_GET['pdftype'] = 'onlyheader';  include('/pdfimp2.php');
        $sql = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["receiving_details"] = json_decode($row["receiving_details"]);
                $output[] = $row;
            }
        }
        //$sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
           // $result1 = $conn->query($sql1);
            //$row1 = $result1->fetch_assoc();
            //$sql2 = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
           // $result2 = $conn->query($sql2);
            
            //$sql2 = "SELECT * FROM grn WHERE grn_no='".$row["grn_no"]."'";
            //$result2 = $conn->query($sql2);
            //$row2 = $result2->fetch_assoc();
            
            //$sql3 = "SELECT * FROM material_received WHERE receiving_no='".$row2["receiving_no"]."'";
            //$result3 = $conn->query($sql3);
           // $row3 = $result3->fetch_assoc();
            
            //$sql5 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
            //$result5 = $conn->query($sql5);
           // $row5 = $result5->fetch_assoc();
            
            //$sql9 = "SELECT * FROM grn_material WHERE grn_no='".$row["grn_no"]."'";
            //$result9 = $conn->query($sql9);
            //$row9 = $result9->fetch_assoc();
            
           // $sql2=" select * from vendor where vendor_name='".$row2["vendor_name"].";";
           // $result2=$conn->query($sql2);
           // $row2 = $result2->fetch_assoc();
        $html.='
        <h2 style="text-align:center">RAW MATERIAL Digital LOG</h2>
            <table cellpadding="2">
                <thead border-right:solid 1px BCBBBA; >
                <tr style="background-color:#DDDAD9;font-weight:bold;" border-right:solid 1px BCBBBA;>
                    <td style="width:9.09%;">Material Name</td>
                    <td style="width:9.09%;">Vendor Name</td>
                    <td style="width:9.09%;">Challan No</td>
                    <td style="width:9.09%;">Challan date</td>
                    <td style="width:9.09%;">PO No</td>
                    <td style="width:9.09%;">PO Date</td>
                    <td style="width:9.09%;">Material Type</td>
                    <td style="width:9.09%;">Material Subtype</td>
                    <td style="width:9.09%;">Material Code</td>
                    <td style="width:9.09%;">Material grade</td>
                    <td style="width:9.09%;"border:solid 1px BCBBBA;>PO Qty</td>
                </tr>
                </thead>
                </tbody>';
            //$sql2 = "SELECT m.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM material s LEFT JOIN vendor v ON v.vendor_no=m.vendor_no WHERE spec_type LIKE 'Raw Material%' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND m.grade LIKE '%".$_GET["grade"]."%' AND v.status LIKE '%".$_GET["status"]."%'";
            //$result2 = $conn->query($sql2);
            $sql2 = "SELECT s.*, m.material_type, m.material_subtype, m.material_name, m.grade FROM specification s LEFT JOIN material m ON s.material_code=m.material_code WHERE spec_type LIKE 'Raw Material%' AND s.status='checked'";
            $result2 = $conn->query($sql2);
            //$sql2 = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE c.receiving !='pending' AND m.material_type ='Raw Material' AND m.material_subtype LIKE '%".$_GET["material_type"]."%' AND v.vendor_no LIKE '%".$_GET["vendor_no"]."' AND c.status LIKE '%".$_GET["status"]."%'";
            //$result2 = $conn->query($sql2);
            if($result2->num_rows > 0){
            while($row2 = $result2->fetch_assoc()) {
                $html.='
                    <tr nobr="true">
                        <td style="width:9.09%;">'.$row2["material_name"].'</td>
                        <td style="width:9.09%;">'.$row2["vendor_name"].'</td>
                        <td style="width:9.09%;">'.$row2["challan_no"].'</td>
                        <td style="width:9.09%;">'.$row2["challan_date"].'</td>
                        <td style="width:9.09%;">'.$row2["po_no"].'</td>
                        <td style="width:9.09%;">'.$row2["po_date"].'</td>
                        <td style="width:9.09%;">'.$row2["material_type"].'</td>
                        <td style="width:9.09%;">'.$row2["material_subtype"].'</td>
                        <td style="width:9.09%;">'.$row2["material_code"].'</td>
                        <td style="width:9.09%;">'.$row2['grade'].'</td>
                        <td style="width:9.09%;">'.$row2['po_qty'].'</td>
                    </tr>';
                    
            }
            $html.='</tbody></table>';
        $html.='   
            <style>
                .tdall { border:solid 1px BCBBBA; }
                .tdb { border-bottom:solid 1px BCBBBA; }
                .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
            </style>
            <p style="text-align:center;"><b>Labeling Details:</b></p>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td class="tdb"><b>Batch No:</b></td>
                <td class="tdb"> '.$row["batch_no"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Mfg. Date:</b></td>
                <td class="tdb"> '.$row["mfg_date"].'</td>
            </tr>
             <tr>
                <td class="tdb"><b>Expiry Date:</b></td>
                <td class="tdb"> '.$row["expiry_date"].'</td>
            </tr>
        </table>
        <div></div>';
        $html.='   
        <style>
        .tdall { border:solid 1px BCBBBA; }
        .tdb { border-bottom:solid 1px BCBBBA; }
        .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
        </style>
        <p style="text-align:center;"><b>Received Quantity Details:</b></p>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td class="tdb"><b>Pack Size:</b></td>
                <td class="tdb"> '.$row["pack_size"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Challan Qty: Kg:</b></td>
                <td class="tdb"> '.$row["challan_qty"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Received Qty: Kg:</b></td>
                <td class="tdb"> '.$row["received_qty"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>No. of Containers: Kg:</b></td>
                <td class="tdb"> '.$row["no_of_containers"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Damage Container Observed:</b></td>
                <td class="tdb"> '.$row["damage_container"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Outer Damage Containers:</b></td>
                <td class="tdb"> '.$row["outer_container"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>inner Damage Containers:</b></td>
                <td class="tdb"> '.$row["inner_container"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Remark (if Any):</b></td>
                <td class="tdb"> '.$row["remark"].'</td>
            </tr>
        </table>
        <div></div>';
        $html.='
        <style>
            .tdall { border:solid 1px BCBBBA; }
            .tdb { border-bottom:solid 1px BCBBBA; }
            .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
        </style>
            <p style="text-align:center;"><b>Packing Details:</b></p>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td class="tdb"><b>Packing Intactness / Condition:</b></td>
                <td class="tdb"> '.$row["packing"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Outer Packing: Kg:</b></td>
                <td class="tdb"> '.$row["outer_packing"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Container type:: Kg:</b></td>
                <td class="tdb"> '.$row["container_type"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>Container Subtype: Kg:</b></td>
                <td class="tdb"> '.$row["container_subtype"].'</td>
            </tr>
        </table>
        <div></div>';
        $html.='
        <style>
        .tdall { border:solid 1px BCBBBA; }
        .tdb { border-bottom:solid 1px BCBBBA; }
        .tdbr { border-bottom:solid 1px BCBBBA; border-right:solid 1px BCBBBA; }
        </style>
        <p style="text-align:center;"><b>Transporter’s Details::</b></p>
        <table style="border:solid 1px BCBBBA;" cellpadding="2">
            <tr>
                <td class="tdb"><b>Vehicle Condition:</b></td>
                <td class="tdb"> '.$row["vehical_condition"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>COA Received: Kg:</b></td>
                <td class="tdb"> '.$row["coa_recived"].'</td>
            </tr>
            <tr>
                <td class="tdb"><b>COA : Kg:</b></td>
                <td class="tdb"> '.$row["coa"].'</td>
            </tr>
        </table>
        <div></div>';
        
        EOD;
        $pdf->writeHTML($html, true, false, false, false,'');
        $pdf->Output('raw.pdf', 'I');
    } 
}
// else{
//     echo 'no record';
// }
$conn->close();
?>