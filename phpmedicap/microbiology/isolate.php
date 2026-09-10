<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];

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
    
    if ($_GET["type"] == "saveEnvInvestigation") {
        $sql = "INSERT INTO env_isolate (source, isolate_date, enrichment_obs_date, enrichment_observation, enrichment_positive,
        enrichment_negative, subcluture_obs_date, subcluture_observation, subcluture_positive, subcluture_negative,
        morphological_size, morphological_shape, morphological_color, morphological_elevation, morphological_margin,
        morphological_opacity, morphological_texture, gram_observation, identified_microorganism,plant_id, entry_by, entry_date) 
        VALUES ('".$input["source"]."','".$input["isolate_date"]."','".$input["enrichment_obs_date"]."',
        '".$input["enrichment_observation"]."','".$input["enrichment_positive"]."','".$input["enrichment_negative"]."',
        '".$input["subcluture_obs_date"]."','".$input["subcluture_observation"]."','".$input["subcluture_positive"]."',
        '".$input["subcluture_negative"]."','".$input["morphological_size"]."','".$input["morphological_shape"]."',
        '".$input["morphological_color"]."','".$input["morphological_elevation"]."','".$input["morphological_margin"]."',
        '".$input["morphological_opacity"]."','".$input["morphological_texture"]."','".$input["gram_observation"]."',
        '".$input["identified_microorganism"]."','".$_GET["plant_id"]."','".$_GET["emp_id"]."','$entry_date')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getEnvInvestigations") {
        $output = array();
        $sql = "SELECT * FROM env_isolate";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadEnvInvestigations") {
        $_GET['filename'] = 'EnvInvestigations'; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
        $html= "";
        $sql = "SELECT * FROM env_isolate WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        $html.='
        <h2 style="text-align:center">EnvInvestigations</h2>
        <table border="1" cellpadding="4">
                  <tr>
                    <td style="width:100%; font-weight:bold;">1.	Enrichment Test</td>
                  </tr>
                  <tr>
                    <td style="width:11%;font-weight:bold;">Particulars</td>
                    <td style="width:11%;font-weight:bold;">Media Reference Lot No.</td>
                    <td style="width:11%;font-weight:bold;">Incubation Conditions</td>
                    <td style="width:11%;font-weight:bold;">Eqp. Used</td>
                    <td style="width:13%;font-weight:bold;">Date of Observations</td>
                    <td style="width:11%;font-weight:bold;">observation</td>
                    <td style="width:11%;font-weight:bold;">Positive</td>
                    <td style="width:11%;font-weight:bold;">Negative</td>
                    <td style="width:10%;font-weight:bold;">Sign</td>
                  </tr>
                  <tr>
                    <td style="width:100%;"><b>A. Enrichment:</b>Aseptically take one colony in 100 ml Soyabean Casein Digest Medium.   </td>
                  </tr>
                  <tr>
                    <td style="width:11%;">Observed Microorganism</td>
                    <td style="width:11%;">SCDM/</td>
                    <td style="width:11%;">30-35o C 18-24 Hours</td>
                    <td style="width:11%;">M-03</td>
                    <td style="width:13%;">'.$row['enrichment_obs_date'].'</td>
                    <td style="width:11%;">'.$row['enrichment_observation'].'</td>
                    <td style="width:11%;">'.$row['enrichment_positive'].'</td>
                    <td style="width:11%;">'.$row['enrichment_negative'].'</td>
                    <td style="width:10%;"></td>
                  </tr>
                  <tr>
                    <td style="width:100%;">
                        P->Growth Observed <br>
                        N->No Growth Observed 
                    </td>
                  </tr>
                  <tr>
                    <td style="width:100%;">Subculture on Soyabean Casein Digest Agar.</td>
                  </tr>
                  <tr>
                    <td style="width:11%;">Enrichment</td>
                    <td style="width:11%;">SCDA/</td>
                    <td style="width:11%;">30-35o C 24-48 Hours</td>
                    <td style="width:11%;">M-03</td>
                    <td style="width:13%;">'.$row['subcluture_obs_date'].'</td>
                    <td style="width:11%;">'.$row['subcluture_observation'].'</td>
                    <td style="width:11%;">'.$row['subcluture_positive'].'</td>
                    <td style="width:11%;">'.$row['subcluture_negative'].'</td>
                    <td style="width:10%;"></td>
                  </tr>
                  <tr>
                    <td style="width:100%;">
                        P->Growth Observed<br>
                        N->No Growth Observed 
                    </td>
                  </tr>
                </table><br><br>';
        $html.='<table border="1" cellpadding="4">
                    <tr>
                        <td style="width:100%; font-weight:bold;">2.	Morphological Characteristics</td>
                    </tr>
                    <tr>
                        <td style="width:100%;font-weight:bold;">Observation</td>
                    </tr>
                    <tr>
                        <td style="width:10%;font-weight:bold;">Size</td>
                        <td style="width:15%;font-weight:bold;">Shape</td>
                        <td style="width:15%;font-weight:bold;">Colour</td>
                        <td style="width:15%;font-weight:bold;">Elevation</td>
                        <td style="width:15%;font-weight:bold;">Margin</td>
                        <td style="width:15%;font-weight:bold;">Opacity</td>
                        <td style="width:15%;font-weight:bold;">Texture</td>
                    </tr>
                     <tr>
                        <td style="width:10%;">'.$row['morphological_size'].'</td>
                        <td style="width:15%;">'.$row['morphological_shape'].'</td>
                        <td style="width:15%;">'.$row['morphological_Color'].'</td>
                        <td style="width:15%;">'.$row['morphological_elevation'].'</td>
                        <td style="width:15%;">'.$row['morphological_margin'].'</td>
                        <td style="width:15%;">'.$row['morphological_opacity'].'</td>
                        <td style="width:15%;">'.$row['morphological_texture'].'</td>
                    </tr>
                </table><br><br>';
        $html.='<table border="1" cellpadding="4">
                 <tr>
                    <td style="width:100%;font-weight:bold;">3.	Grams Staining Characteristics</td>
                 </tr>
                 <tr>
                    <td style="width:100%;font-weight:bold;">Observation</td>
                 </tr>
                 <tr>
                    <td style="width:100%;">'.$row['gram_observation'].'</td>
                 </tr>
                </table><br><br>';
                
        $html.='<table border="1" cellpadding="4">
                 <tr>
                    <td style="width:100%;font-weight:bold;">4.	Results of outside Laboratory Biochemical Test / Microbial identification system </td>
                 </tr>
                 <tr>
                    <td style="width:100%;font-weight:bold;">Observation</td>
                 </tr>
                 <tr>
                    <td style="width:100%;"><b>Name of Identified Microorganism:</b>'.$row['identified_microorganism'].'</td>
                 </tr>
                </table><br><br>';
                
        $html.='<table border="1" cellpadding="4">
                 <tr>
                    <td style="width:50%;"><b>Performed by:</b>'.$row['entry_by'].'</td>
                    <td style="width:50%;"><b>Checked by:</b>'.$row['check_by'].'</td>
                 </tr>
                 <tr>
                    <td style="width:50%;"><b>Date:</b>'.$row['entry_date'].'</td>
                    <td style="width:50%;"><b>Date:</b>'.$row['check_date'].'</td>
                 </tr>
                </table>';
            }
        }
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('EnvInvestigations.pdf', 'I');
    }
    

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
