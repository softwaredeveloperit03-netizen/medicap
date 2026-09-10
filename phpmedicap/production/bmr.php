<?php
    require '../db.php';
    require '../token.php';
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

    $sql = "INSERT INTO log (process,token,action,actiontime,department,emp_id,method,REMOTE_ADDR) VALUES ('FRONTEND','".$token."','".$_GET["type"]."','".$entry_date."','".$_GET["department"]."','".$_GET["emp_id"]."','".$_SERVER['REQUEST_METHOD']."','".$_SERVER['REMOTE_ADDR']."')";
    $conn->query($sql);


if($_GET["type"]=="getStages") {
    $stages = '{
        "WET": [
            {"stage": "SHIFTING", "stage_for": "LOT", "steps": [{"step": "Line Clearance"},{"step": "Sieve Integrity Check"},{"step": "Procedure"},{"step": "Weight of Material"},{"step": "Yield"}], "equipments": []},
            {"stage": "DRY MIXING", "stage_for": "LOT", "equipments": []},
            {"stage": "BINDER PREPARATION", "stage_for": "LOT", "equipments": []},
            {"stage": "WET MIXING", "stage_for": "LOT", "equipments": []},
            {"stage": "DRYING", "stage_for": "LOT", "equipments": []},
            {"stage": "SIZING", "stage_for": "LOT", "equipments": []},
            {"stage": "BLENDING", "stage_for": "BATCH", "equipments": []},
            {"stage": "COMPRESSION", "stage_for": "BATCH", "equipments": []}
        ],
        "DIRECT": [
            {"stage": "SHIFTING", "stage_for": "LOT", "steps": [{"step": "Line Clearance"},{"step": "Sieve Integrity Check"},{"step": "Procedure"},{"step": "Weight of Material"},{"step": "Yield"}], "equipments": []},
            {"stage": "BLENDING", "stage_for": "BATCH", "equipments": []},
            {"stage": "COMPRESSION", "stage_for": "BATCH", "equipments": []}
        ],
        "COATED": [
            {"stage": "COATING SOLUTION PREPARATION", "stage_for": "PACKING", "equipments": []},
            {"stage": "COATING", "stage_for": "PACKING", "equipments": []},
            {"stage": "INSPECTION", "stage_for": "PACKING", "equipments": []}
        ],
        "UNCOATED": [
            {"stage": "INSPECTION", "stage_for": "PACKING", "equipments": []}
        ]
    }';
    
    if ($_GET["dosage_form"] == "TABLET") {
        $stages = '{
            "WET": [
                {"stage": "SHIFTING", "stage_for": "LOT", "steps": [{"step": "Line Clearance"},{"step": "Sieve Integrity Check"},{"step": "Procedure"},{"step": "Weight of Material"},{"step": "Yield"}], "equipments": []},
                {"stage": "DRY MIXING", "stage_for": "LOT", "equipments": []},
                {"stage": "BINDER PREPARATION", "stage_for": "LOT", "equipments": []},
                {"stage": "WET MIXING", "stage_for": "LOT", "equipments": []},
                {"stage": "DRYING", "stage_for": "LOT", "equipments": []},
                {"stage": "SIZING", "stage_for": "LOT", "equipments": []},
                {"stage": "BLENDING", "stage_for": "BATCH", "equipments": []},
                {"stage": "COMPRESSION", "stage_for": "BATCH", "equipments": []}
            ],
            "DIRECT": [
                {"stage": "SHIFTING", "stage_for": "LOT", "steps": [{"step": "Line Clearance"},{"step": "Sieve Integrity Check"},{"step": "Procedure"},{"step": "Weight of Material"},{"step": "Yield"}], "equipments": []},
                {"stage": "BLENDING", "stage_for": "BATCH", "equipments": []},
                {"stage": "COMPRESSION", "stage_for": "BATCH", "equipments": []}
            ],
            "COATED": [
                {"stage": "COATING SOLUTION PREPARATION", "stage_for": "PACKING", "equipments": []},
                {"stage": "COATING", "stage_for": "PACKING", "equipments": []},
                {"stage": "INSPECTION", "stage_for": "PACKING", "equipments": []}
            ],
            "UNCOATED": [
                {"stage": "INSPECTION", "stage_for": "PACKING", "equipments": []}
            ]
        }';
    } else if ($_GET["dosage_form"] == "CAPSULE" || $_GET["dosage_form"] == "POWDER") {
        $stages = '{
            "DIRECT": [
                {"stage": "SHIFTING", "stage_for": "LOT", "steps": [{"step": "Line Clearance"},{"step": "Sieve Integrity Check"},{"step": "Procedure"},{"step": "Weight of Material"},{"step": "Yield"}], "equipments": []},
                {"stage": "BLENDING", "stage_for": "BATCH", "equipments": []},
                {"stage": "COMPRESSION", "stage_for": "BATCH", "equipments": []},
                {"stage": "FILLING", "stage_for": "BATCH", "equipments": []}
            ],
            "COATED": [
                {"stage": "COATING SOLUTION PREPARATION", "stage_for": "PACKING", "equipments": []},
                {"stage": "COATING", "stage_for": "PACKING", "equipments": []},
                {"stage": "INSPECTION", "stage_for": "PACKING", "equipments": []}
            ],
            "UNCOATED": [
                {"stage": "INSPECTION", "stage_for": "PACKING", "equipments": []}
            ]
        }';
    }
    
    $stages = json_decode($stages);
    if ($_GET["granulation_type"] == "Wet Granulation") {
        echo json_encode($stages->WET);
    } else if ($_GET["granulation_type"] == "Direct Compression") {
        echo json_encode($stages->DIRECT);
    }
    
    if ($_GET["packing"] == "Coated") {
        echo json_encode($stages->COATED);
    } else if ($_GET["packing"] == "Uncoated") {
        echo json_encode($stages->UNCOATED);
    }
}


} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>