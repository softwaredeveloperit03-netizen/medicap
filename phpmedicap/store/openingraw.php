<?php
try{
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

         if ($_GET["type"] == "getCommonlog") {
        $output = array();
          $sql = "SELECT s.*,m.material_name from stock_book s left join material m on m.material_code = s.material_code 
          where s.plant_id = '".$_GET["plant_id"]."'  AND m.material_type = '".$_GET["Material_type"]."'  order by s.id desc";
                            
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) { 
                
                
                 $sql1 = "SELECT  IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no = '".$row["ar_no"]."' AND  material_code= '".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["pRASAD"] = $row1["m_qty"];
                      
                    }
                }
                
                
                 $output1 = array();
                 $sql2 = "SELECT  i.*,m.material_name,p.product_name FROM material_issue i left join material m on i.material_code=m.material_code
                left join product p on p.product_code = i.product_code  WHERE i.ar_no = '".$row["ar_no"]."' AND  i.material_code= '".$row["material_code"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $output1[] = $row2;
                    }
                }
                
                 $row["despensing_data"] = $output1;
                
                $row["Issue"] = $row["qty"]- $row["pRASAD"];
               
                $output[] = $row;
            }
        }
        echo json_encode($output);
        
    }

    
    
    
    
 
}

$conn->close();

function isWeekend($date) {
    $weekDay = date('w', strtotime($date));
    return ($weekDay == 0);
}
}
catch(Exception $e) {
  echo 'Message: ' .$e->getMessage();
}
?>