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
    

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }
}

     if($_GET['type'] == 'rejection_note'){
        
     if($_GET["plant_id"] == 59){ // Amardeep
                
           $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='';
              
           
    	    
    	    $html.='';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }else if($_GET["plant_id"] == 64){//Novo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.=' <table border="1">
   

    <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;"> Raw Material & Packing Material Rejection Note</td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;"> F/SOP/WR/007/01-00</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;">  1 of 1</td>
   
    </tr>
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;"> SOP/WR/007</td>
    </tr>
</table><div></div><table border="1">
        <tr style="text-align: center; background-color:#DDDAD9;">
            <td style="width:20px;text-align:center;"> Sr. No.</td>
            <td style="width:74px;text-align:center;"> Material Description</td>
            <td style="width:54px;text-align:center;">Medicap Lot No</td>
            <td style="width:54px;text-align:center;">Mfg. Batch No.</td>
            <td style="width:54px;text-align:center;">Mfg. / Supplier Name</td>
            <td style="width:54px;text-align:center;">Reason for Rejection</td>
            <td style="width:54px;text-align:center;">UOM</td>
            <td style="width:54px;text-align:center;">Rejected Quantity</td>
            <td style="width:54px;text-align:center;">Value</td>
            <td style="width:54px;text-align:center;">Remark</td>
        </tr>
        </table>
        ';
              
           $sql = "SELECT m.*,m.description,r.arno,m.material_code,v.vendor_name,r.rejection_reason,m.uom,r.rejected_quantity,r.remark,r.material_code,v.material_code,c1.material_code ,c1.batches FROM rejection_rawpacking r LEFT JOIN material m ON m.material_code=r.material_code LEFT JOIN vendor v ON m.material_code=v.material_code LEFT JOIN challan_materials c1 ON m.material_code=c1.material_code";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    // $inword_details = json_decode($row["inword_details"]);
                    $html.='
                        <table>
        <tr>
            <td style="width:20px;text-align:center;">'.$counter++.'</td>
            <td style="width:74px;text-align:center;"> '.$row['description'].' </td>
            <td style="width:54px;text-align:center;"> '.$row["arno"].'</td>
            <td style="width:54px;text-align:center;"> '.$row["batches"].'</td>
            <td style="width:54px;text-align:center;"> '.$row["vendor_name"].'</td>
            <td style="width:54px;text-align:center;"> '.$row["rejection_reason"].' </td>
            <td style="width:54px;text-align:center;"> '.$row["uom"].'</td>
            <td style="width:54px;text-align:center;"> '.$row["rejected_quantity"].'</td>
            <td style="width:54px;text-align:center;"></td>
            <td style="width:54px;text-align:center;"> '.$row["remark"].'</td>
        </tr>
    </table>
       
                    ';
    		    }
    	        
    	    }
    		    $html.=' <div></div>
        
        


<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY : <br>(Warehouse)</td>
    <td style="text-align:center;width: 146.6px;">Approved By:  <br>(Quality Assurance Head)</td>
    <td style="text-align:center;width: 146.6px;">Authorized By: <br>(Plant Head)</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Signature</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>

</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
            else if($_GET["plant_id"] == 28){//demo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.=' <table border="1">
   

    <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;"> Raw Material & Packing Material Rejection Note</td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;"> F/SOP/WR/007/01-00</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;">  1 of 1</td>
   
    </tr>
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;"> SOP/WR/007</td>
    </tr>
</table><div></div><table border="1">
        <tr style="text-align: center; background-color:#DDDAD9;">
            <td style="width:20px;text-align:center;"> Sr. No.</td>
            <td style="width:74px;text-align:center;"> Material Description</td>
            <td style="width:54px;text-align:center;">Medicap Lot No</td>
            <td style="width:54px;text-align:center;">Mfg. Batch No.</td>
            <td style="width:54px;text-align:center;">Mfg. / Supplier Name</td>
            <td style="width:54px;text-align:center;">Reason for Rejection</td>
            <td style="width:54px;text-align:center;">UOM</td>
            <td style="width:54px;text-align:center;">Rejected Quantity</td>
            <td style="width:54px;text-align:center;">Value</td>
            <td style="width:54px;text-align:center;">Remark</td>
        </tr>
        </table>
        ';
              
           $sql = "SELECT m.*,m.description,r.arno,m.material_code,v.vendor_name,r.rejection_reason,m.uom,r.rejected_quantity,r.remark,r.material_code,v.material_code,c1.material_code ,c1.batches FROM rejection_rawpacking r LEFT JOIN material m ON m.material_code=r.material_code LEFT JOIN vendor v ON m.material_code=v.material_code LEFT JOIN challan_materials c1 ON m.material_code=c1.material_code";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    // $inword_details = json_decode($row["inword_details"]);
                    $html.='
                        <table>
        <tr>
            <td style="width:20px;text-align:center;">'.$counter++.'</td>
            <td style="width:74px;text-align:center;"> '.$row['description'].' </td>
            <td style="width:54px;text-align:center;"> '.$row["arno"].'</td>
            <td style="width:54px;text-align:center;"> '.$row["batches"].'</td>
            <td style="width:54px;text-align:center;"> '.$row["vendor_name"].'</td>
            <td style="width:54px;text-align:center;"> '.$row["rejection_reason"].' </td>
            <td style="width:54px;text-align:center;"> '.$row["uom"].'</td>
            <td style="width:54px;text-align:center;"> '.$row["rejected_quantity"].'</td>
            <td style="width:54px;text-align:center;"></td>
            <td style="width:54px;text-align:center;"> '.$row["remark"].'</td>
        </tr>
    </table>
       
                    ';
    		    }
    	        
    	    }
    		    $html.=' <div></div>
        
        


<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY : <br>(Warehouse)</td>
    <td style="text-align:center;width: 146.6px;">Approved By:  <br>(Quality Assurance Head)</td>
    <td style="text-align:center;width: 146.6px;">Authorized By: <br>(Plant Head)</td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Name</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Signature</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>
    <tr>
    <td style="text-align:center;width: 100px;">Date</td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    <td style="text-align:center;width: 146.6px;"></td>
    </tr>

</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }
}

$conn->close();
?>