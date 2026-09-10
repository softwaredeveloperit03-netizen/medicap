<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
$output = Array();
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
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"] == "saveBlister") {
        
        $sql = "INSERT INTO blister (machine_name,item_name,change_part,specification,remark,entry_by,entry_date,prodSpecific,product_code,plant_id) VALUES 
        ('".$input["machine_name"]."','".$input["item_name"]."', '".$input["change_part"]."', '".$input["specification"]."','".$input["remark"]."', 
        '".$_GET["emp_id"]."', '".$entry_date."', '".$input["prodSpecific"]."', '".$input["product_code"]."','".$_GET["plant_id"]."')";
        
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
    } else if ($_GET["type"] == "getBlister") {
        $output = array();
        $sql = "SELECT b.*,e.equipment_name as equipmentName,p.product_name FROM blister b left join equipment e ON e.equipment_code = b.machine_name
        left join product p ON p.product_code = b.product_code where b.plant_id = '".$_GET['plant_id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getChangeParts") {
        $output = array();
        $sql = "SELECT distinct machine_name from blister order by machine_name ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output1 = Array();
                $sql1 = "SELECT distinct change_part FROM blister WHERE machine_name='".$row["machine_name"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $output1[] = $row1;
                    }
                }
                $row['change_parts'] = $output1;
                
                
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
     else if ($_GET["type"] == "downloadBlisters") {
        $_GET['filename'] = 'Change Part Tools'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Change Part Tools</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ">Sr No</td>
                    <td style="width: 20%; ">Machine Name</td>
                    <td style="width: 10%; ">Item Name</td>
                    <td style="width: 15%; ">Change Part No.</td>
                    <td style="width: 20%; ">Specification</td>
                    <td style="width: 10%; ">Remark</td>
                    <td style="width: 10%; ">Entry Date</td>
                    <td style="width: 10%; ">Entry By</td>

                </tr>
            </thead>';
            $i=1;
            $output = array();
            $sql = "SELECT * FROM blister ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                $html.='<tr nobr="true">
                            <td style="width: 5%; ">'.$i.'.</td>
                            <td style="width: 20%;">'.$row['machine_name'].'</td>
                            <td style="width: 10%;">'.$row['item_name'].'</td>
                            <td style="width: 15%; ">'.$row['change_part'].'</td>
                            <td style="width: 20%;">'.$row['specification'].'</td>
                            <td style="width: 10%; ">'.$row['remark'].'</td>
                            <td style="width: 10%; ">'.$row['entry_date'].'</td>
                            <td style="width: 10%; ">'.$row['entry_by'].'</td>

                            
                        </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('General Materials.pdf', 'I');
        }
        else if ($_GET["type"] == "downloadGeneralMaterials") {
        $_GET['filename'] = 'General Materials'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">General Materials</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; ">Sr No</td>
                    <td style="width: 15%; ">Material Type</td>
                    <td style="width: 15%; ">Material Code</td>
                    <td style="width: 15%; ">Material Name</td>
                    <td style="width: 15%; ">GST</td>
                    <td style="width: 15%; ">HSN</td>
                    <td style="width: 10%; ">Entry By</td>
                    <td style="width: 10%; ">Entry Date</td>
                </tr>
            </thead>';
            $output = array();
            $sql = "SELECT * FROM general_material WHERE user_no='".$_GET["user_no"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $i=1;
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                $html.='<tr nobr="true">
                            <td style="width: 5%; ">'.$i.'.</td>
                            <td style="width: 15%;">'.$row['material_type'].'</td>
                            <td style="width: 15%;">'.$row['material_code'].'</td>
                            <td style="width: 15%; ">'.$row['material_name'].'</td>
                            <td style="width: 15%;">'.$row['gst'].'</td>
                            <td style="width: 15%; ">'.$row['hsn'].'</td>
                            <td style="width: 10%; ">'.$row['entry_by'].'</td>
                            <td style="width: 10%; ">'.$row['entry_date'].'</td>
                        </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('General Materials.pdf', 'I');
    }

}

$conn->close();
?>