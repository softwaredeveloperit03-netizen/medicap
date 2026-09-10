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
    
    if ($_GET["type"] == "saveCompanyDetails") {
       $sql = "INSERT INTO company (plant_id,company_code,company_name,allise,mobile_no,mobile_no1,fax_no,website,note,country,present_state,present_city,area,pin,address,address1,gst_registered,gst_no,tin_no,pla_no,st_no,st_date,cst_no,cst_date,vat_no,pan_no,tds_no,tds_circle,commision_code,commision_name,lic_no,cin_no,fssai_no,person,p_email,contact_no,t_heading,bank_name,branch_name,address3,ac_no,f_date,l_date,from_date,to_date,holiday,ac_period,branches,entry_by,entry_date,scode) VALUES ('".$input["plant_id"]."','".$input["company_code"]."','".$input["company_name"]."','".$input["allise"]."','".$input["mobile_no"]."','".$input["mobile_no1"]."','".$input["fax_no"]."','".$input["website"]."','".$input["note"]."','".$input["country"]."','".$input["present_state"]."','".$input["present_city"]."','".$input["area"]."','".$input["pin"]."','".$input["address"]."','".$input["address1"]."','".$input["gst_registered"]."','".$input["gst_no"]."','".$input["tin_no"]."','".$input["pla_no"]."','".$input["st_no"]."','".$input["st_date"]."','".$input["cst_no"]."','".$input["cst_date"]."','".$input["vat_no"]."','".$input["pan_no"]."','".$input["tds_no"]."','".$input["tds_circle"]."','".$input["commision_code"]."','".$input["commision_name"]."','".$input["lic_no"]."','".$input["cin_no"]."','".$input["fssai_no"]."','".$input["person"]."','".$input["p_email"]."','".$input["contact_no"]."','".$input["t_heading"]."','".$input["bank_name"]."','".$input["branch_name"]."','".$input["address3"]."','".$input["ac_no"]."','".$input["f_date"]."','".$input["l_date"]."','".$input["from_date"]."','".$input["to_date"]."','".$input["holiday"]."','".$input["ac_period"]."','".json_encode($input["branches"])."','".$_GET["emp_id"]."','$entry_date','".$input["c_state_code"]."')";
    	
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } else if ($_GET["type"] == "getCompany") {
        $output = array();
        $plant=$_GET['plant_id'];
        $sql = "SELECT * FROM company ORDER BY id DESC";
        // if($plant==0){
        // $sql = "SELECT * FROM company";
        // }else{
        // $sql = "SELECT * FROM company WHERE plant_id='".$_GET["plant_id"]."'";    
        // }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["branches"] = json_decode($row["branches"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "downloadCompany") {
       $_GET['filename'] = 'Company Log'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";

        $html.='
        <h2 style="text-align:center">Company Log</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%; font-size: 9px;">Sr No</td>
                     <td style="width: 9%; font-size: 9px; ">Company Code</td>
                    <td style="width: 20%; font-size: 9px; ">Company Name</td>
                    <td style="width: 15%; font-size: 9px;  ">Contact Person</td>
                    <td style="width: 20%; font-size: 9px; ">Email Id</td>
                    <td style="width: 11%; font-size: 9px; ">Contacnmt No.</td>
                    <td style="width: 10%;  font-size: 9px;">State</td>
                     <td style="width: 10%; font-size: 9px;">Country</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM company";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $i=1;
            while ($row = $result->fetch_assoc()) {
                $html.='
                <tr nobr="true">
                    <td style="width: 5%; font-size: 8px; ">'.$i.'.</td>
                    <td style="width: 9%; font-size: 8px;">'.$row['company_code'].'</td>
                    <td style="width: 20%; font-size: 8px;">'.$row['company_name'].'</td>
                    <td style="width: 15%; font-size: 8px;">'.$row['person'].'</td>
                    <td style="width: 20%; font-size: 8px; ">'.$row['p_email'].'</td>
                    <td style="width: 11%; font-size: 8px;">'.$row['mobile_no'].'</td>
                    <td style="width: 10%; font-size: 8px;">'.$row['present_state'].'</td>
                    <td style="width: 10%; font-size: 8px;">'.$row['country'].'</td>

                </tr>';
                 $html.='
                    <tr>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Bottle
                      capacity :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                   
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Carton
                      board / bottle quality :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                   
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;">
                    <b>Composition :</b>
                  </td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                
                  </td>
                </tr>
                <tr>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Leaflet
                      required:</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Pack
                      style (KG):</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Pouch
                      required :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                </tr>
                
                <tr>
                  <td style="text-align: left; text-transform: capitalize;"><b>Quantity
                      (NOS):</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Pack Size
                      :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                    <td style=" text-align: left; text-transform: capitalize;"><b>Color of
                      capsules :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                </tr>
                <tr>
                
                  <td style=" text-align: left; text-transform: capitalize;"><b>Lid color
                      :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Capsule
                      printing :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;">
                    <b>Tagger:</b>
                  </td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                </tr>
                <tr>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Capsule
                      size :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;"><b>No. of
                      carton/bottle per shipper :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                   <td style=" text-align: left; text-transform: capitalize;"><b>Shelf
                      life :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                </tr>
                <tr>
                  <td style=" text-align: left; text-transform: capitalize;">
                    <b>Partition/nesting required :</b>
                  </td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                   </td>
                   <td style=" text-align: left; text-transform: capitalize;"><b>Aluminium
                      foil thickness :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Shipper
                      quality (7 ply / 5 ply) :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                </tr>
                <tr>
                  <td style= text-align: left; text-transform: capitalize;"><b>PVC or
                      PVDC (40 / 60 / 90gsm)</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Printed /
                      Unprinted / White :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                    <td style=" text-align: left; text-transform: capitalize;"><b>PVC /PVDC
                      (clear / opaque)</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                </tr>
                <tr>
                  <td style=" text-align: left; text-transform: capitalize;">
                    <b>Strapping/ BOPP required :</b>
                  </td>
                  <td style="text-align: left;margin: 0;padding: 0;">
               </td>
                <td style=" text-align: left; text-transform: capitalize;"><b>Shrink of
                      packs/ bottles</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Shipper
                      shrink required :</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                </td>
                </tr>
                <tr>
                  <td style=" text-align: left; text-transform: capitalize;">
                    <b>Promotional material</b>
                  </td>
                  <td style="text-align: left;margin: 0;padding: 0;">
                  </td>
                  <td style=" text-align: left; text-transform: capitalize;"><b>Scratch
                      card</b></td>
                  <td style="text-align: left;margin: 0;padding: 0;">
               </td>
                </tr>
                 ';
                $i++;
            }
        }
    
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('SubTests Log.pdf', 'I');
    

    }
    else if ($_GET["type"] == "getCompanyByCode") {
        $output = array();
        $plant=$_GET['plant_id'];
        if($plant==0){
        $sql = "SELECT * FROM company WHERE company_code LIKE '%".$_GET["company_code"]."'";
        }else{
        $sql = "SELECT * FROM company WHERE company_code LIKE '%".$_GET["company_code"]."'";    
        }
       // echo $sql;
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                 $row["branches"] = json_decode($row["branches"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

}

$conn->close();
?>