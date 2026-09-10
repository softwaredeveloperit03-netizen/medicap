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

     if($_GET['type'] == 'packing_material_dispensing'){
        
     if($_GET["plant_id"] == 59){ // Amardeep
                
           $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='';
              
           
    	    
    	    $html.='';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            }else if($_GET["plant_id"] == 64){//Novo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='<table border="1">
   

    <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;"> Packing material sampling & dispensing both record </td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;"> F/SOP/WR/005/01-00</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;"></td>
   
    </tr>
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;">SOP/WR/005</td>
    </tr>
</table><div></div>

   <table>
   <tr>
    <td style="width: 300px;"></td>
    <td style="width: 240px;"> Booth  ID No.</td>
   </tr>
   </table>
    <table border="1">
        <tr>
        <td style="width: 20px;px; text-align:center;font-size:8px" rowspan="2">Sr. No </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Date </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">RLAF Start time </td>
        <td style="width:72px; text-align:center;font-size:8px" rowspan="2">Magnehelic Gauges Reading for RLAF       Limit  6 to 16 mm </td>
        <td style="width:46px; text-align:center;font-size:8px" rowspan="2">Product Name </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2"> Batch No
         </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Activity </td>
        <td style="width:62px; text-align:center;font-size:8px" colspan="2">Sampling/ Dispensing Time</td>

        <td style="width:31px; text-align:center;font-size:8px" rowspan="2">Done By </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Checked By </td>
        <td style="width:57px; text-align:center;font-size:8px" colspan="2">Cleaning Time </td>

        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Done By </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Checked By </td>
        </tr>
        <tr>
        <td style="width: 31px; text-align:center;font-size:8px">From </td>
        <td style="width:31px; text-align:center;font-size:8px">To </td>
        <td style="width: 31px; text-align:center;font-size:8px">From </td>
        <td style="width: 26px; text-align:center;font-size:8px">To </td>
        </tr>
        </table>
        ';
              
           $sql = "SELECT d.*, e.firstname,DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.dispensing_for='PACKING' AND d.status='Active' AND DATE(d.request_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    // $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <table border="1">
                        <tr>
        <td style="width: 20px;"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        <td style="width: 72px;"> </td>
        <td style="width:46px"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        <td style="width:31px"> </td>
        <td style="width:31px"> </td>
        <td style="width:31px"> </td>
        <td style="width:36px"> </td>
        <td style="width:31px"> </td>
        <td style="width:26px"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        </tr>
    </table>
       
                    ';
    		    }
    	        
    	    }
    		    $html.=' <div></div>
        
        


<table border="1">
    <tr>
    <td style="text-align:center;width: 100px;"></td>
    <td style="text-align:center;width: 146.6px;">PREPARED BY : </td>
    <td style="text-align:center;width: 146.6px;">Approved By:  </td>
    <td style="text-align:center;width: 146.6px;">Authorized By: </td>
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
            else if($_GET["plant_id"] == 28){//DEMO
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='<table border="1">
   

     <tr>
    <td style="width: 100px;"> Format Title :</td>
    <td style="width: 440px;"> Packing material sampling & dispensing both record </td>
    </tr>
   
    <tr>
    <td style="width: 100px;"> Format No.:</td>
    <td style="width: 170px;"> F/SOP/WR/005/01-00</td>
    <td style="width: 100px;"> Page No.:</td>
    <td style="width: 170px;"></td>
   
    </tr>
    <tr>
    <td style="width: 100px;"> Ref. SOP No.:</td>
    <td style="width: 440px;">SOP/WR/005</td>
    </tr>
</table><div></div>

   <table>
   <tr>
    <td style="width: 300px;"></td>
    <td style="width: 240px;"> Booth  ID No.</td>
   </tr>
   </table>
    <table border="1">
        <tr>
        <td style="width: 20px;px; text-align:center;font-size:8px" rowspan="2">Sr. No </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Date </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">RLAF Start time </td>
        <td style="width:72px; text-align:center;font-size:8px" rowspan="2">Magnehelic Gauges Reading for RLAF       Limit  6 to 16 mm </td>
        <td style="width:46px; text-align:center;font-size:8px" rowspan="2">Product Name </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2"> Batch No
         </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Activity </td>
        <td style="width:62px; text-align:center;font-size:8px" colspan="2">Sampling/ Dispensing Time</td>

        <td style="width:31px; text-align:center;font-size:8px" rowspan="2">Done By </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Checked By </td>
        <td style="width:57px; text-align:center;font-size:8px" colspan="2">Cleaning Time </td>

        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Done By </td>
        <td style="width:36px; text-align:center;font-size:8px" rowspan="2">Checked By </td>
        </tr>
        <tr>
        <td style="width: 31px; text-align:center;font-size:8px">From </td>
        <td style="width:31px; text-align:center;font-size:8px">To </td>
        <td style="width: 31px; text-align:center;font-size:8px">From </td>
        <td style="width: 26px; text-align:center;font-size:8px">To </td>
        </tr>
        </table>
        ';
              
           $sql = "SELECT d.*, e.firstname,DATE(d.request_date) as request_date, p.product_type, p.product_name, p.grade FROM dispensing d LEFT JOIN product p ON d.product_code=p.product_code LEFT JOIN employee e ON d.request_by=e.emp_id WHERE d.dispensing_for='PACKING' AND d.status='Active' AND DATE(d.request_date) BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."' ORDER BY id DESC";
            $result = $conn->query($sql);
    	    if($result->num_rows > 0){
                $counter = 1;
    		    while ($row = $result->fetch_assoc()) {
                    // $inword_details = json_decode($row["inword_details"]);
                    $html.='
                    <table border="1">
                        <tr>
        <td style="width: 20px;"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        <td style="width: 72px;"> </td>
        <td style="width:46px"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
        <td style="width:31px"> </td>
        <td style="width:31px"> </td>
        <td style="width:31px"> </td>
        <td style="width:36px"> </td>
        <td style="width:31px"> </td>
        <td style="width:26px"> </td>
        <td style="width:36px"> </td>
        <td style="width:36px"> </td>
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