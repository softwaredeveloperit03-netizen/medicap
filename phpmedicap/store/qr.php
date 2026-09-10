<?php
   require '../db.php';
    // require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
  


    
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);        


    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    if ($_GET["type"]=="receivingMaterialLogPDF") {
        //demo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='<table>
         <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;">Raw Material Inward Register</td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;">F/SOP/WR/002/03-01</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;">1 of 1

</td>
   
    </tr>
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;">SOP/WR/002</td>
    </tr>
    </table>
        <h2 style="text-align:center">Receiving of Material Log</h2>
        <table cellpadding="3">
                <tr style="text-align: center; background-color:#DDDAD9;">
                      <td style="width: 28.42px;text-align:center;font-size:6px;">Sr. No.</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Received Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">G.R. N. No & Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Material Code</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Material Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Suppli-er Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Mfg.  Name</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Inv. No. & Date</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Received Qty</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Pack Size</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Rate</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Net Amount</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">GST</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Freight</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Total Amount</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Batch No.</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Mfg. Dt</td>
            <td style="width: 28.42px;text-align:center;font-size:6px;">Exp. Dt</td>
            <td style="width: 28.42px;text-align:center;font-size:5px;">P.O. NO. & Date</td>
                </tr><tbody>';
              
           $sql = "SELECT c.*,c.net_total as total,c1.entry_by,c1.approve_by,c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,m.material_name, m.grade,c1.tax_invoice,c1.net_total,c.batches,v.vendor_name FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON c.material_code=m.material_code LEFT JOIN vendor v ON c1.vendor_no=v.vendor_no WHERE m.material_type ='Raw Material'AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND DATE(c.receiving_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY c.document_no DESC ";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <tr nobr="true">
                <td style="width: 28.42px;font-size:6px;">'.$counter++.'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d-m-y',strtotime($row['received_date'])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row['grn_no'].'<br>'.date('d/m/Y',strtotime($row["grn_date"])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["material_code"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["material_name"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["vendor_name"].'</td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;">'.$row["tax_invoice"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["received_qty"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["pack_size"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["rate"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["total"].'</td>
                <td style="width: 28.42px;font-size:6px;">'.$row["gst"].'</td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;"></td>
                <td style="width: 28.42px;font-size:6px;">'.$row['batches'].'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('M-Y',strtotime($row1["mfg_date"])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d-m-Y',strtotime($row1['exp_date'])).'</td>
                <td style="width: 28.42px;font-size:6px;">'.date('d/m/Y',strtotime($row['po_date'])).'<br>'.$row['po_no'].'</td>
                    </tr>';
    		    }
    	    }
    	    
    	    $html.='</table>';
    	    $html.='<br><table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY</td>
    <td style="text-align:center;width: 146.6px;">REVIEWED BY</td>
    <td style="text-align:center;width: 146.6px;">APPROVED BY</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Sign/Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Designation</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Department</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            
        
    }

// else{
//     echo 'no record';
// }
$conn->close();
?>