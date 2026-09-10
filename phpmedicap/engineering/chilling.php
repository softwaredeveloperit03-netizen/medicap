<?php
require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);
$entry_time = date("H:i:s", $timestamp);
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

$sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
$conn->query($sql);

    if ($_GET["type"] == "saveKirloskerOperation") {
        $sql = "INSERT INTO kilosker_chilling (run_hr, comp_amp, gas_pr_lp, gas_pr_hp, comp_oil, cooling_in, cooling_out, chilled_in, chilled_out, oil_level, plant_supply, tank_level_hot, tank_level_cold, kwh, entry_by, entry_date, entry_time) VALUES ('".$input["run_hr"]."', '".$input["comp_amp"]."', '".$input["gas_pr_lp"]."', '".$input["gas_pr_hp"]."', '".$input["comp_oil"]."', '".$input["cooling_in"]."', '".$input["cooling_out"]."', '".$input["chilled_in"]."', '".$input["chilled_out"]."', '".$input["oil_level"]."', '".$input["plant_supply"]."', '".$input["tank_level_hot"]."', '".$input["tank_level_cold"]."', '".$input["kwh"]."','".$_GET["emp_id"]."','$entry_date','$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getKirloskerOperations") {
        $output = array();
        $sql = "SELECT * FROM kilosker_chilling WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveDaikinOperation") {
        $sql = "INSERT INTO daikin_chilling (load_per, EWT_CHW, LWT_CHW, eva_gas_pr, EWT_COW, LWT_COW, cond_gas_per, comp1_run_hr, comp1_kwh, comp2_run_hr, comp2_kwh, energy_reading, supply_pressure, tank_level_hot, tank_level_cold, entry_by, entry_date, entry_time) VALUES ('".$input["load_per"]."', '".$input["EWT_CHW"]."', '".$input["LWT_CHW"]."', '".$input["eva_gas_pr"]."', '".$input["EWT_COW"]."', '".$input["LWT_COW"]."', '".$input["cond_gas_per"]."', '".$input["comp1_run_hr"]."', '".$input["comp1_kwh"]."', '".$input["comp2_run_hr"]."', '".$input["comp2_kwh"]."', '".$input["energy_reading"]."', '".$input["supply_pressure"]."', '".$input["tank_level_hot"]."', '".$input["tank_level_cold"]."','".$_GET["emp_id"]."','$entry_date','$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getDaikinOperations") {
        $output = array();
        $sql = "SELECT * FROM daikin_chilling WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "saveBrineOperation") {
        $sql = "INSERT INTO brine_plant (run_hr, load_per, amp, gas_temp_dis, gas_temp_suc, gas_temp_liq, gas_temp_oil, gas_pressure_dis, gas_pressure_suc, gas_pressure_oil, brine_inlet, brine_outlet, cooling_inlet, cooling_outlet, supply_pressure, tank_level_mini1, tank_level_mini2, KWH, entry_by, entry_date, entry_time) VALUES ('".$input["run_hr"]."','".$input["load_per"]."','".$input["amp"]."','".$input["gas_temp_dis"]."','".$input["gas_temp_suc"]."','".$input["gas_temp_liq"]."','".$input["gas_temp_oil"]."','".$input["gas_pressure_dis"]."','".$input["gas_pressure_suc"]."','".$input["gas_pressure_oil"]."','".$input["brine_inlet"]."','".$input["brine_outlet"]."','".$input["cooling_inlet"]."','".$input["cooling_outlet"]."','".$input["supply_pressure"]."','".$input["tank_level_mini1"]."','".$input["tank_level_mini2"]."','".$input["KWH"]."','".$_GET["emp_id"]."','$entry_date','$entry_time')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "getBrineOperations") {
        $output = array();
        $sql = "SELECT * FROM brine_plant WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"] == "downloadBrineOperations") {
        $_GET['filename'] = 'J & E Hall Plant'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width: 4%;">Date</td>
                    <td rowspan="2" style="width: 4%;">Time</td>
                    <td rowspan="2" style="width: 4%;">Run Hr</td>
                    <td rowspan="2" style="width: 4%;">% Load</td>
                    <td rowspan="2" style="width: 4%;">Amp</td>
                    <td style="width: 20%;">Gas Temperature in c</td>
                    <td style="width: 15%;">Gas Pressure in bar </td>
                    <td style="width: 10%;">Brine Water Temperature</td>
                    <td style="width: 10%;">Cooling Water Temperature</td>
                    <td rowspan="2" style="width: 5%;">Plant Supply Pressure</td>
                    <td style="width: 10%;">Tank Level</td>
                    <td rowspan="3" style="width: 5%;">KWH</td>
                    <td rowspan="3" style="width: 5%;">Reading Taken By</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">Dis</td>
                    <td style="width:5%;">Suc</td>
                    <td style="width:5%;">Liq</td>
                    <td style="width:5%;">oil</td>
                    <td style="width:5%;">Dis(HP)</td>
                    <td style="width:5%;">suc(LP)</td>
                    <td style="width:5%;">Oil</td>
                    <td style="width:5%;">Inlet</td>
                    <td style="width:5%;">Outlet</td>
                    <td style="width:5%;">Inlet</td>
                    <td style="width:5%;">Outlet</td>
                    <td style="width:5%;">Mini</td>
                    <td style="width:5%;">Mini</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td colspan="4" style="width:16%;">LIMIT-></td>
                    <td style="width:4%;">NMT135</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">NMT 16 bar</td>
                    <td style="width:5%;">NLT 0.5 bar</td>
                    <td style="width:5%;">Diff NMT4 KG/Cm2 of HP</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">NMT40 c</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">NLT2.0 Kg/Cm2</td>
                    <td style="width:5%;">50 Cm</td>
                    <td style="width:5%;">50 Cm</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM brine_plant WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 4%;">'.$row['entry_date'].'.</td>
                        <td style="width: 4%;">'.$row['entry_time'].'</td>
                        <td style="width: 4%;">'.$row['run_hr'].'</td>
                        <td style="width: 4%;">'.$row['load_per'].'</td>
                        <td style="width: 4%;">'.$row['amp'].'</td>
                        <td style="width: 5%;">'.$row['gas_temp_dis'].'</td>
                        <td style="width: 5%;">'.$row['gas_temp_suc'].'</td>
                        <td style="width: 5%;">'.$row['gas_temp_liq'].'</td>
                        <td style="width: 5%;">'.$row['gas_temp_oil'].'</td>
                        <td style="width: 5%;">'.$row['gas_pressure_dis'].'</td>
                        <td style="width: 5%;">'.$row['gas_pressure_suc'].'</td>
                        <td style="width: 5%;">'.$row['gas_pressure_oil'].'</td>
                        <td style="width: 5%;">'.$row['brine_inlet'].'</td>
                        <td style="width: 5%;">'.$row['brine_outlet'].'</td>
                        <td style="width: 5%;">'.$row['cooling_inlet'].'</td>
                        <td style="width: 5%;">'.$row['cooling_outlet'].'</td>
                        <td style="width: 5%;">'.$row['supply_pressure'].'</td>
                        <td style="width: 5%;">'.$row['tank_level_mini1'].'</td>
                        <td style="width: 5%;">'.$row['tank_level_mini2'].'</td>
                        <td style="width: 5%;">'.$row['KWH'].'</td>
                        <td style="width: 5%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('J & E Hall Plant.pdf', 'I');
    }else if ($_GET["type"] == "downloadKirloskerOperations") {
        $_GET['filename'] = 'Kirlosker Chilling Plant'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width:10%;">Date</td>
                    <td rowspan="2" style="width:5%;">Time</td>
                    <td rowspan="2" style="width:5%;">Run Hr</td>
                    <td rowspan="2" style="width:5%;">Comp AMP</td>
                    <td style="width:10%;">GAS PR.(Kg/Cm2)</td>
                    <td rowspan="2" style="width:10%;">Comp Oil Pr.(Kg/Cm2)</td>
                    <td style="width:10%;">Cooling Water Temp(c)</td>
                    <td style="width:10%;">Chilled Water Temp(c)</td>
                    <td rowspan="2" style="width:5%;">Oil Level</td>
                    <td rowspan="2" style="width:10%;">Plant Supply Pr(Kg/Cm2)</td>
                    <td style="width:10%;">Tank Level</td>
                    <td rowspan="2" style="width:5%;">KWH</td>
                    <td rowspan="2" style="width:5%;">Reading Taken By</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">LP</td>
                    <td style="width:5%;">HP</td>
                    <td style="width:5%;">IN</td>
                    <td style="width:5%;">OUT</td>
                    <td style="width:5%;">IN</td>
                    <td style="width:5%;">OUT</td>
                    <td style="width:5%;">Hot Well</td>
                    <td style="width:5%;">Cold Well</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;">limit-></td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">Maxi140</td>
                    <td style="width:5%;">Mini0.5</td>
                    <td style="width:5%;">Maxi18.0</td>
                    <td style="width:10%;">Mini1.0</td>
                    <td style="width:5%;">NMT35(oc)</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:10%;">Mini2.0</td>
                    <td style="width:5%;">Mini30CM</td>
                    <td style="width:5%;">Mini30CM</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM kilosker_chilling WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$row['entry_date'].'.</td>
                        <td style="width: 5%;">'.$row['entry_time'].'</td>
                        <td style="width: 5%;">'.$row['run_hr'].'</td>
                        <td style="width: 5%;">'.$row['comp_amp'].'</td>
                        <td style="width: 5%;">'.$row['gas_pr_lp'].'</td>
                        <td style="width: 5%;">'.$row['gas_pr_hp'].'</td>
                        <td style="width: 10%;">'.$row['comp_oil'].'</td>
                        <td style="width: 5%;">'.$row['cooling_in'].'</td>
                        <td style="width: 5%;">'.$row['cooling_out'].'</td>
                        <td style="width: 5%;">'.$row['chilled_in'].'</td>
                        <td style="width: 5%;">'.$row['chilled_out'].'</td>
                        <td style="width: 5%;">'.$row['oil_level'].'</td>
                        <td style="width: 10%;">'.$row['plant_supply'].'</td>
                        <td style="width: 5%;">'.$row['tank_level_hot'].'</td>
                        <td style="width: 5%;">'.$row['tank_level_cold'].'</td>
                        <td style="width: 5%;">'.$row['kwh'].'</td>
                        <td style="width: 5%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Kirlosker Chilling Plant.pdf', 'I');
    }
    else if ($_GET["type"] == "downloadDaikinOperations") {
        $_GET['filename'] = 'Daikin Chilling Plant'; $_GET['pdftype'] = 'landscape'; include("../pdfimp.php");
        $html= "";

        $html.='<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td rowspan="2" style="width:10%;">Date</td>
                    <td rowspan="2" style="width:5%;">Time</td>
                    <td rowspan="2" style="width:5%;">% Load</td>
                    <td style="width:15%;text-align:center;">Evaporator</td>
                    <td style="width:15%;text-align:center;">Condensor</td>
                    <td style="width:10%;text-align:center;">Comp1</td>
                    <td style="width:10%;text-align:center;">Comp2</td>
                    <td rowspan="2" style="width:5%;">Chilled Water Temp(c)</td>
                    <td rowspan="2" style="width:5%;">Oil Level</td>
                    <td style="width:10%;text-align:center;">tank Level</td>
                    <td rowspan="2" style="width:10%;">Reading Taken By</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:5%;">EWT(CHW inlet)</td>
                    <td style="width:5%;">LWT(CHW outlet)</td>
                    <td style="width:5%;">GasPressure in KPa</td>
                    <td style="width:5%;">EWT(COW inlet)</td>
                    <td style="width:5%;">LWT(COW outlet)</td>
                    <td style="width:5%;">GasPressure in KPa</td>
                    <td style="width:5%;">Run Hr</td>
                    <td style="width:5%;">KWH</td>
                    <td style="width:5%;">Run HR</td>
                    <td style="width:5%;">KWH</td>
                    <td style="width:5%;">Hot Well</td>
                    <td style="width:5%;">Cold Well</td>
                </tr>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width:10%;"></td>
                    <td colspan="2" style="width:10%;">limit-></td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">NLT180</td>
                    <td style="width:5%;">NMT40c</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">NMT1000c</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">---</td>
                    <td style="width:5%;">Mini30CM</td>
                    <td style="width:5%;">NLT2.5kg/Cm2</td>
                    <td style="width:5%;">Mini 50cm</td>
                    <td style="width:10%;">---</td>
                </tr>
            </thead>';
        $sql = "SELECT * FROM daikin_chilling WHERE entry_date BETWEEN '".$_GET["from_date"]."' AND '".$_GET["to_date"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $html.='<tr nobr="true">
                        <td style="width: 10%;">'.$row['entry_date'].'.</td>
                        <td style="width: 5%;">'.$row['entry_time'].'</td>
                        <td style="width: 5%;">'.$row['load_per'].'</td>
                        <td style="width: 5%;">'.$row['EWT_CHW'].'</td>
                        <td style="width: 5%;">'.$row['LWT_CHW'].'</td>
                        <td style="width: 5%;">'.$row['eva_gas_pr'].'</td>
                        <td style="width: 5%;">'.$row['EWT_COW'].'</td>
                        <td style="width: 5%;">'.$row['LWT_COW'].'</td>
                        <td style="width: 5%;">'.$row['cond_gas_per'].'</td>
                        <td style="width: 5%;">'.$row['comp1_run_hr'].'</td>
                        <td style="width: 5%;">'.$row['comp1_kwh'].'</td>
                        <td style="width: 5%;">'.$row['comp2_run_hr'].'</td>
                        <td style="width: 5%;">'.$row['comp2_kwh'].'</td>
                        <td style="width: 5%;">'.$row['energy_reading'].'</td>
                        <td style="width: 5%;">'.$row['supply_pressure'].'</td>
                        <td style="width: 5%;">'.$row['tank_level_hot'].'</td>
                        <td style="width: 5%;">'.$row['tank_level_cold'].'</td>
                        <td style="width: 10%;">'.$row['entry_by'].'</td>
                    </tr>';
                $i++;
            }
        }
        $html.="</table>";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Daikin Chilling Plant.pdf', 'I');
    }


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>