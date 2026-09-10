<?php
    require '../../db.php';
    require '../../token.php';
    require '../../tcpdf/tcpdf.php';
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

    if ($_GET["type"] == "get_test_data") {
        $output = Array();
        $sql = "SELECT *,e.manufacturer_code as vendor_code,b.grn_no as GRN_NO  FROM specification a left JOIN testing b on a.specification_no=b.specification_no and a.material_code=b.material_code LEFT JOIN testing_tests c on b.testing_no=c.testing_no LEFT 
                JOIN spec_tests d on a.specification_no=d.specification_no left JOIN mst_vendor_materials e on a.material_code=e.material_code left join vendor f on f.vendor_no=e.manufacturer_code   LEFT JOIN  material g on a.material_code=g.material_code
                WHERE a.material_code='".$_GET["material_code"]."' and c.test='assay' and d.test='assay' GROUP BY a.id,b.id,c.id,d.id,e.id,f.id,g.id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
}
$conn->close();
?>