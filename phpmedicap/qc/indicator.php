<?php
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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

    if ($_GET["type"] == "saveIndicator") {
           $input = $_POST;  
  $data = json_decode($input["data"], true);
         $sql1 = "SELECT MAX(id) as id FROM glassware ";
          $result1 = $conn->query($sql1);
         $row1 = $result1->fetch_assoc();
         $last_id=$row1["id"]; 
         $target_dir = "../../../upload/product/";
           if(isset($_FILES["coa"]["name"])) {
            	$target_file = $target_dir.$last_id."_".basename($_FILES["coa"]["name"]);
            	$structure_file =$last_id."_".basename($_FILES["coa"]["name"]);
        	    move_uploaded_file($_FILES["coa"]["tmp_name"], $target_file);
        	   
           }
        $output = Array();
        $sql = "INSERT INTO indicator (coa,start_date,valid_up_to,open_date,plant_id,indicator, acid_color, base_color, ph_range, pka, entry_by, entry_date) VALUES
    ('".$structure_file."','".$input["start_date"]."','".$input["valid_up_to"]."','".$input["open_date"]."','".$_GET["plant_id"]."','".$input["indicator"]."', '".$input["acid_color"]."', '".$input["base_color"]."', '".$input["ph_range"]."', '".$input["pka"]."', '".$_GET["emp_id"]."', '$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getPendingIndicators") {
        $output = Array();
        $sql = "SELECT * FROM indicator WHERE status='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "updateIndicator") {
        $sql = "UPDATE indicator SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getIndicatorsLog") {
        $output = Array();
        $plant=$_GET['plant_id'];
        if($plant==0){
        $sql = "SELECT * FROM indicator ORDER BY `id` DESC";
        }else{
         $sql = "SELECT * FROM indicator WHERE plant_id='".$_GET["plant_id"]."' ORDER BY `id` DESC";    
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "receivingMaterialLogPDF") {
        $_GET['filename'] = 'Indicators Log '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Indicators Log</h2>
        <table border="1" cellpadding="5">
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 10%;"> Material Type</td>
                    <td style="width: 10%;">Reagents No.</td>
                    <td style="width: 10%;">Reagents Name</td>
                    <td style="width: 10%;">Ph Range</td>
                    <td style="width: 10%;">Acid Color</td>
                    <td style="width: 5%;">PO No</td>
                    <td style="width: 10%;">PO Date</td>
                    <td style="width: 10%;">Vendor Name</td>
                    <td style="width: 10%;">Challan No</td>
                    <td style="width: 10%;">Challan Date</td>
                </tr>';
                
            $html.='<tr>
                        <td style="width: 5%; "></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%; "></td>
                        <td style="width: 10%;"></td>
                        <td style="width: 10%; "></td>
                        <td style="width: 10%; "></td>
                        <td style="width: 5%; "></td>
                        <td style="width: 10%; "></td>
                        <td style="width: 10%; "></td>
                        <td style="width: 10%; "></td>
                        <td style="width: 10%; "></td>
                    </tr>';                
           
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Indicators Log .pdf', 'I');
    
}
else if ($_GET["type"] == "GRNLogPDF") {
        $_GET['filename'] = 'Indicators Log '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Indicators Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ">Sr.</td>
                    <td style="width: 10%; ">Date	</td>
                    <td style="width: 15%; ">Material Code	</td>
                    <td style="width: 15%; ">Reagents Name	</td>
                    <td style="width: 10%; ">PH Range	</td>
                    <td style="width: 10%; ">Vendor Name		</td>
                    <td style="width: 10%; ">Containers</td>
                     <td style="width: 10%; ">Receiving Date	</td>
                      <td style="width: 5%; ">Qty	</td>
                       <td style="width: 10%; ">Status	</td>
                </tr>
            </thead>';
                $output = Array();
                 $sql = "SELECT s.*, v.vendor_name, v.vendor_type, m.chemical_name,s.material_code as chemical_no FROM stock_book s LEFT JOIN chemical m ON s.material_code=m.chemical_no LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND s.material_type='Chemicals' AND m.grade LIKE '%".$_GET["grade"]."%'AND s.status LIKE '%".$_GET["status"]."%' AND DATE(s.entry_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
                    $j=1;
    	            $result = $conn->query($sql);
    	            $output = Array();
    	            if($result->num_rows > 0){
    		            while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 5%; ">'.$i.'.</td>
                        <td style="width: 10%;">'.$row[''].'</td>
                        <td style="width: 15%; ">'.$row[''].'</td>
                        <td style="width: 15%;">'.$row[''].'</td>
                        <td style="width: 10%; ">'.$row[''].'</td>
                        <td style="width: 10%; ">'.$row[''].'</td>
                        <td style="width: 10%; ">'.$row[''].'</td>
                        <td style="width: 10%; ">'.$row[''].'</td>
                        <td style="width: 5%; ">'.$row[''].'</td>
                        <td style="width: 10%; ">'.$row[''].'</td>
                    </tr>';
               
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Indicators Log .pdf', 'I');
}
 else if ($_GET["type"] == "WeighingMaterials") {
        $_GET['filename'] = 'Indicators Log '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Indicators Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 10%; ">Sr.</td>
                    <td style="width: 15%; ">Challan No.	</td>
                    <td style="width: 15%; ">Material Code	</td>
                    <td style="width: 15%; ">Reagents Name	</td>
                    <td style="width: 15%; ">PH Range	</td>
                    <td style="width: 15%; ">Accepted Qty	</td>
                    <td style="width: 15%; ">Containers</td>
                </tr>
            </thead>';
                $output = Array();
                $sql = "SELECT * FROM indicator ORDER BY indicator";
                $result = $conn->query($sql);
                $i=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width: 10%; ">'.$i.'.</td>
                        <td style="width: 15%;">'.$row['challan_no'].'</td>
                        <td style="width: 15%; ">'.$row['material_code'].'</td>
                        <td style="width: 15%;">'.$row['reagents_name'].'</td>
                        <td style="width: 15%; ">'.$row['ph_range'].'</td>
                        <td style="width: 15%; ">'.$row['accepted_qty'].'</td>
                        <td style="width: 15%; ">'.$row['containers'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Indicators Log .pdf', 'I');
 }
    else if ($_GET["type"] == "downloadIndicatorsLog") {
        $_GET['filename'] = 'Regents / Indicators Master '; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Regents / Indicators Master</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">Sr.</td>
                    <td style="width:15%;">Indicator No</td>
                    <td style="width:15%;">Indicator</td>
                    <td style="width:15%;">Acid Color</td>
                    <td style="width:15%;">Base Color</td>
                    <td style="width:15%;">Ph Range</td>
                    <td style="width:15%;">Pka</td>
                </tr>
            </thead>';
                $output = Array();
                $sql = "SELECT * FROM indicator WHERE indicator LIKE '%".$_GET["indicator"]."%' ORDER BY indicator";
                $result = $conn->query($sql);
                $i=1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                $html.='<tr nobr="true">
                        <td style="width:10%;">'.$i.'.</td>
                        <td style="width:15%;">'.$row['indicator_no'].'</td>
                        <td style="width:15%;">'.$row['indicator'].'</td>
                        <td style="width:15%;">'.$row['acid_color'].'</td>
                        <td style="width:15%;">'.$row['base_color'].'</td>
                        <td style="width:15%;">'.$row['ph_range'].'</td>
                        <td style="width:15%;">'.$row['pka'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Indicators Log .pdf', 'I');
    }

}

$conn->close();
?>