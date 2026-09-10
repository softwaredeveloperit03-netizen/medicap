<?php 
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// // also log errors to a file
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/mrp-error.log');



require '../db.php';
require '../token.php';
// require '../tcpdf/tcpdf.php';
 
 
 
 function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } else if (is_string($mixed)) {
        return utf8_encode($mixed);
    }
    return $mixed;
}

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
 
 
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
    
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
    
    
          if ($_GET["type"] == "getproductsYearly") {
      
      $output = [];
 

$sql = "
SELECT 
    b.product_name,
    b.product_code,
    b.generic_name,
   
    SUM(a.planQty) AS order_qty
FROM 
    order_materials a 
LEFT JOIN 
    po_entry p 
ON 
    a.order_no = p.order_no 
LEFT JOIN 
    product b 
ON 
    a.product_code = b.product_code  and p.status='Approved'
WHERE 
    b.product_type = 'Finish Product'
GROUP BY 
    b.product_name, 
    b.product_code, 
    b.generic_name
   
";

// $sql = "select * from product where product_type='Branded'";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

     $row['genericProductCode']= $row['product_code'];


       
            $output[] = $row;
       
}
}

 

// echo json_encode($output);
$output = utf8ize($output);
echo json_encode($output);

  }
      if ($_GET["type"] == "getPOsLogForSplits") {
      
      $output = [];
$prepares = [];
$pending = []; 

   $sql = "SELECT
    a.id AS pid,a.unit,a.order_no,b.file,c.TrdNm,b.po_no,b.po_type,b.valid_till,b.po_date,
    a.product_code,a.order_qty,a.pack_size,d.product_name,d.grade AS product_grade,d.generic_name,d.genericProductCode,
    e.raw_materials,e.id AS unit_formula_id,a.order_qty AS qty_to_prepare,a.deliveryDate,
    d.generic_name,
    -- Subqueries for batch info
    (SELECT MAX(CAST(batch_formula_weight AS UNSIGNED)) FROM batch_formula_info 
    WHERE
        product_code = a.product_code
) AS bfr_batch_size,(
    SELECT
        REGEXP_SUBSTR(
            unit_formula_batch_weight,
            '[0-9]+'
        )
    FROM
        batch_formula_info
    WHERE
        product_code = a.product_code AND batch_formula_weight =(
        SELECT
            MAX(batch_formula_weight)
        FROM
            batch_formula_info
        WHERE
            product_code = a.product_code
    )
LIMIT 1
) AS batch_weight
FROM
    order_materials a
LEFT JOIN po_entry b ON
    a.order_no = b.order_no
LEFT JOIN client c ON
    b.client_code = c.client_code
LEFT JOIN product d ON
    a.product_code = d.product_code
LEFT JOIN(
    SELECT product_code,
        MAX(id) AS max_id
    FROM
        unitformula
    GROUP BY
        product_code
) latest_unitformula
ON
    a.product_code = latest_unitformula.product_code
LEFT JOIN unitformula e ON
    latest_unitformula.max_id = e.id
WHERE
    a.reqStatus = 'pending' and b.status='Approved'
        AND a.plant_id = '".$_GET["plant_id"]."' 
        ORDER BY a.id DESC";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // packing configuration
        $output1 = [];
        $sql1 = "SELECT a.*, b.id,b.unit_formula_id,b.market_type,b.country_specific,
                        b.country_name,b.packing_type,b.pack_size,b.batch_size,b.unit
                 FROM unitformula_packing_materials a 
                 LEFT JOIN unitformula_pm_dtl b 
                 ON a.unit_formula_dtl_id = b.id 
                 WHERE b.unit_formula_id ='".$row["unit_formula_id"]."'";

        $result1 = $conn->query($sql1);
        if ($result1->num_rows > 0) {
            while ($row1 = $result1->fetch_assoc()) {
                $output1[] = $row1;
            }
        }

        // split qty
        $output2 = [];
        $sql2 = "SELECT *,a.oder_qty as Qty,a.balance_qty as bal_qty 
                 FROM split_planning_qty a 
                 WHERE a.order_no='".$row["order_no"]."' 
                 AND a.product_code='".$row["product_code"]."'";

        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $output2[] = $row2;
            }
        }
        $row['splits'] = $output2;

        // available stock
        // Calculate booked quantity from both mrp_bookedStock and InQty from split_planning_qty
        // InQty represents inhouse manufacturing quantity that should be booked from available stock
        $sql3 = "SELECT 
                    SUM(a.qty) AS stock_qty, 
                    IFNULL(b.issued_qty, 0) AS issued_qty, 
                    IFNULL(c.booked_qty, 0) AS booked_from_table,
                    IFNULL(d.booked_from_splits, 0) AS booked_from_splits
                FROM fg_stock_book a 
                LEFT JOIN ( 
                    SELECT material_code, SUM(qty) AS issued_qty 
                    FROM material_issue 
                    GROUP BY material_code 
                ) b ON a.material_code = b.material_code 
                LEFT JOIN ( 
                    SELECT material_code, SUM(qty) AS booked_qty 
                    FROM mrp_bookedStock 
                    GROUP BY material_code 
                ) c ON a.material_code = c.material_code 
                LEFT JOIN (
                    SELECT product_code, SUM(CAST(COALESCE(InQty, 0) AS DECIMAL(15,4))) AS booked_from_splits
                    FROM split_planning_qty
                    WHERE (status IS NULL OR status = 'Pending')
                    GROUP BY product_code
                ) d ON a.material_code = d.product_code
                WHERE a.material_code = '".$conn->real_escape_string($row["product_code"])."'";

        $result3 = $conn->query($sql3);
        if ($result3->num_rows > 0) {
            while ($row3 = $result3->fetch_assoc()) {
                // Total booked = booked from mrp_bookedStock + InQty from splits
                $total_booked = floatval($row3['booked_from_table']) + floatval($row3['booked_from_splits']);
                $row['avblStock'] = floatval($row3['stock_qty']) - floatval($row3['issued_qty']) - $total_booked;
                // Ensure available stock is not negative
                $row['avblStock'] = max(0, $row['avblStock']);
            }
        } else {
            $row['avblStock'] = 0;
        }

        // decode JSON fields
        $row["pack_size"] = json_decode($row["pack_size"]);
        $row['packing_configuration'] = $output1;
        // $row["raw_materials"] = json_decode($row["raw_materials"], true);
        
        
        
        $row["raw_materials"] = json_decode($row["raw_materials"], true);

if (is_array($row["raw_materials"])) {
    foreach ($row["raw_materials"] as $key => $material) {
        $materialCode = $material['material_code'];

        // ✅ Get total stock
        $sqlStock = "SELECT COALESCE(SUM(a.qty), 0) AS total_stock ,a.unit as stock_unit FROM stock_book a
                     WHERE a.material_code = ?";
        $stmt = $conn->prepare($sqlStock);
        $stmt->bind_param("s", $materialCode);
        $stmt->execute();
        $resStock = $stmt->get_result()->fetch_assoc();
        $totalStock = $resStock['total_stock'] ?? 0;
        $totalStock_unit = $resStock['stock_unit'];

        // ✅ Get total issued
        $sqlIssue = "SELECT COALESCE(SUM(qty), 0) AS total_issue 
                     FROM material_issue 
                     WHERE material_code = ?";
        $stmt = $conn->prepare($sqlIssue);
        $stmt->bind_param("s", $materialCode);
        $stmt->execute();
        $resIssue = $stmt->get_result()->fetch_assoc();
        $totalIssue = $resIssue['total_issue'] ?? 0;

        // ✅ Available stock = stock - issue
        $availableStock = $totalStock - $totalIssue;

        // Save back into raw_materials
        $row["raw_materials"][$key]['avbl_stock'] = $availableStock;
        $row["raw_materials"][$key]['avbl_stock_unit'] = $totalStock_unit;
    }
}


        // classify into prepares or pending
        if (!empty($row['splits'])) {
            $prepares[] = $row;
        } else {
            $pending[] = $row;
        }
    }
}

// Final response in 2 arrays
$response = [
    "prepares" => $prepares,
    "pending"  => $pending
];

echo json_encode($response);

  }
  if ($_GET["type"] == "getPOsLogForReqAnalysis") {
      
        $output = Array();
        
      $sql = "SELECT a.id as pid,a.unit,a.order_no,b.file,c.TrdNm,b.po_no,b.po_type,b.valid_till,b.po_date,a.product_code,a.order_qty,a.pack_size,d.product_name,d.grade as product_grade, e.raw_materials,e.id as unit_formula_id ,a.order_qty as qty_to_prepare FROM 
      order_materials a LEFT JOIN po_entry b ON a.order_no = b.order_no 
      LEFT JOIN client c ON b.client_code=c.client_code
        LEFT JOIN product d ON a.product_code = d.product_code 
      LEFT JOIN 
            (
                SELECT 
                    product_code,
                    MAX(id) as max_id
                FROM 
                    unitformula
                GROUP BY 
                    product_code
            ) latest_unitformula ON a.product_code = latest_unitformula.product_code
        LEFT JOIN 
            unitformula e ON latest_unitformula.max_id = e.id
      WHERE a.reqStatus = 'Pending'   AND a.plant_id = '".$_GET["plant_id"]."' ORDER BY a.id DESC ";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                 $output1 = Array();
                 
                 $sql1 = "SELECT a.*, b.id,b.unit_formula_id,b.market_type,b.country_specific,b.country_name,b.packing_type,
                b.pack_size,b.batch_size,b.unit  from unitformula_packing_materials a left join unitformula_pm_dtl b ON 
                a.unit_formula_dtl_id = b.id where b.unit_formula_id ='".$row["unit_formula_id"]."'  ";
               
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {

                        $output1[] = $row1;
                    }
                    
                }
                
                                 $output2 = Array();
                 
                 $sql2 = "select *,a.oder_qty as Qty,a.balance_qty as bal_qty from split_planning_qty a where a.order_no='".$row["order_no"]."' AND a.product_code='".$row["product_code"]."' ";
               
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {

                        $output2[] = $row2;
                    }
                    
                }
                 $row['splits'] =$output2;
               $output3 = Array();
                 
                $sql3 = "
                        SELECT 
                            a.qty AS stock_qty,
                            IFNULL(b.qty, 0) AS issued_qty,
                            IFNULL(c.qty, 0) AS Booked_qty
                        FROM fg_stock_book a
                        LEFT JOIN material_issue b 
                            ON a.material_code = b.material_code
                        left join mrp_bookedStock c 
                            on a.material_code=c.material_code
                        
                        WHERE a.material_code = '".$conn->real_escape_string($row["product_code"])."'
                    ";

               
                $result3 = $conn->query($sql3);
               if ($result3->num_rows > 0) {
                    while ($row3 = $result3->fetch_assoc()) {
                        $row['avblStock'] = $row3['stock_qty'] - $row3['issued_qty']  - $row3['Booked_qty'];
                    }
                } else {
                    $row['avblStock'] = 0; // no stock if no record
                }



 
                
                
                
                
                                          $row["pack_size"] = json_decode($row["pack_size"]);

             
                    $row['packing_configuration'] =$output1;
                    $rawMaterials = json_decode($row["raw_materials"], true);
                    $row["raw_materials"] = $rawMaterials;
                    $output[] = $row;
                    
            
            }
        }
        echo json_encode($output);
    }
  if ($_GET["type"] == "Get_Qty_For_YearlyForcast") {
      
        $output = Array();
        
       $sql = "SELECT * FROM yearly_forecast_qty_dtl WHERE yfq_common_id = (SELECT MAX(yfq_common_id) FROM yearly_forecast_qty_dtl );";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                
                   $output[] = $row;
            }
        }
        echo json_encode($output);
    }
  if ($_GET["type"] == "Get_Annual_forcast_Data") {
      
        $output = Array();
        
      $sql = "SELECT 
    p.product_code,
    pr.product_name,   -- get product name from product master

    -- Batch planning qty grouped by month (using planned_qty)
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 1  THEN p.planned_qty ELSE 0 END),0) AS jan_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 2  THEN p.planned_qty ELSE 0 END),0) AS feb_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 3  THEN p.planned_qty ELSE 0 END),0) AS mar_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 4  THEN p.planned_qty ELSE 0 END),0) AS apr_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 5  THEN p.planned_qty ELSE 0 END),0) AS may_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 6  THEN p.planned_qty ELSE 0 END),0) AS jun_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 7  THEN p.planned_qty ELSE 0 END),0) AS jul_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 8  THEN p.planned_qty ELSE 0 END),0) AS aug_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 9  THEN p.planned_qty ELSE 0 END),0) AS sep_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 10 THEN p.planned_qty ELSE 0 END),0) AS oct_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 11 THEN p.planned_qty ELSE 0 END),0) AS nov_batch,
    IFNULL(SUM(CASE WHEN MONTH(p.entry_date) = 12 THEN p.planned_qty ELSE 0 END),0) AS dec_batch,

    -- Forecast qty directly from table b (latest only)
    IFNULL(f.jan,0) AS jan,
    IFNULL(f.feb,0) AS feb,
    IFNULL(f.mar,0) AS mar,
    IFNULL(f.apr,0) AS apr,
    IFNULL(f.may,0) AS may,
    IFNULL(f.jun,0) AS jun,
    IFNULL(f.jul,0) AS jul,
    IFNULL(f.aug,0) AS aug,
    IFNULL(f.sep,0) AS sep,
    IFNULL(f.oct,0) AS oct,
    IFNULL(f.nov,0) AS nov,
    IFNULL(f.dec,0) AS `dec`

FROM batch_planning p

LEFT JOIN (
    SELECT y.*
    FROM yearly_forecast_qty_dtl y
    INNER JOIN (
        SELECT product_code, MAX(yfq_common_id) AS max_common_id
        FROM yearly_forecast_qty_dtl
        WHERE year = YEAR(CURDATE())
        GROUP BY product_code
    ) t ON y.product_code = t.product_code 
        AND y.yfq_common_id = t.max_common_id
) f ON p.product_code = f.product_code

LEFT JOIN product pr ON p.product_code = pr.product_code

WHERE YEAR(p.entry_date) = YEAR(CURDATE())

GROUP BY p.product_code, pr.product_name,
         f.jan, f.feb, f.mar, f.apr, f.may, f.jun,
         f.jul, f.aug, f.sep, f.oct, f.nov, f.dec;";

        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                
                   $output[] = $row;
            }
        }
        echo json_encode($output);
    }
  if ($_GET["type"] == "getmrp_raised_indnd_qty") {
      
        $output = Array();
        
       $sql = "select * from mrp_raised_indnd_qty";
        
    
      
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $output1 = Array();

            while ($row = $result->fetch_assoc()) {
                
                   $output[] = $row;
            }
        }
        echo json_encode($output);
    }
      if ($_GET["type"] == "getPOsLogForSplitsLog") {
      
      $output = [];
$prepares = [];
$pending = []; 

   $sql = "SELECT
    a.id AS pid,a.unit,a.order_no,b.file,c.TrdNm,b.po_no,b.po_type,b.valid_till,b.po_date,
    a.product_code,a.order_qty,a.pack_size,d.product_name,d.grade AS product_grade,d.generic_name,d.genericProductCode,
    e.raw_materials,e.id AS unit_formula_id,a.order_qty AS qty_to_prepare,a.deliveryDate,
    d.generic_name,
  
    (SELECT MAX(CAST(batch_formula_weight AS UNSIGNED)) FROM batch_formula_info 
    WHERE
        product_code = a.product_code
) AS bfr_batch_size,(
    SELECT
        REGEXP_SUBSTR(
            unit_formula_batch_weight,
            '[0-9]+'
        )
    FROM
        batch_formula_info
    WHERE
        product_code = a.product_code AND batch_formula_weight =(
        SELECT
            MAX(batch_formula_weight)
        FROM
            batch_formula_info
        WHERE
            product_code = a.product_code
    )
LIMIT 1
) AS batch_weight
FROM
    order_materials a
LEFT JOIN po_entry b ON
    a.order_no = b.order_no
LEFT JOIN client c ON
    b.client_code = c.client_code
LEFT JOIN product d ON
    a.product_code = d.product_code
LEFT JOIN(
    SELECT product_code,
        MAX(id) AS max_id
    FROM
        unitformula
    GROUP BY
        product_code
) latest_unitformula
ON
    a.product_code = latest_unitformula.product_code
LEFT JOIN unitformula e ON
    latest_unitformula.max_id = e.id ";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
 

        // split qty
        $output2 = [];
        $sql2 = "SELECT *,a.oder_qty as Qty,a.balance_qty as bal_qty 
                 FROM split_planning_qty a 
                 WHERE a.order_no='".$row["order_no"]."' 
                 AND a.product_code='".$row["product_code"]."'";

        $result2 = $conn->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $output2[] = $row2;
            }
        }
         $row["pack_size"] = json_decode($row["pack_size"]);
        $row['splits'] = $output2;

   $output[] = $row;

 
    }
}

  echo json_encode($output);

  }
     if ($_GET["type"] == "sendFor_ConcolidatePlan") {
       $sql = "UPDATE order_materials 
            SET reqStatus='Inprocess' 
            WHERE id='" . $_GET['id'] . "'";
    
    if ($conn->query($sql)) {
      
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => $conn->error]);
    }
      
  }
     else if($_GET["type"]=="saveWOEntryData") {
         
          
       
  $sql = "INSERT INTO split_planning_qty (order_no,product_name, product_code,date, month, year, 
        oder_qty,balance_qty,plant_id,entry_by,InQty,outQty,avbl_stock)
       VALUES ('".$input["order_no"]."','".$input["product_name"]."','".$input["product_code"]."','".$input["date"]."','".$input["month"]."',
       '".$input["year"]."','".$input["Qty"]."','".$input["bal_qty"]."','".$_GET["plant_id"]."',
       '".$_GET["emp_id"]."','".$input["InQty"]."','".$input["outQty"]."','".$input["AvblStock"]."')";
        if ($conn->query($sql)) {
              $sql1 = "INSERT INTO mrp_bookedStock (plant_id,order_no,qty,material_code)
       VALUES ('".$_GET["plant_id"]."','".$input["order_no"]."','".$input["BookedQty"]."','".$input["product_code"]."')";
        $conn->query($sql1);
       
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
        
     }
else if ($_GET["type"] == "save_yearly_forecast_qty") {

    // Decode posted JSON from Angular
    $array = json_decode(file_get_contents("php://input"), true);

    $status1 = false;
    $entry_date = date('Y-m-d H:i:s');

    // ✅ Find the last common_id used (max value)
    $result = $conn->query("SELECT MAX(common_id) AS last_id FROM yearly_forecast_qty");
    $row = $result->fetch_assoc();
    $last_id = isset($row['last_id']) ? (int)$row['last_id'] : 0;

    // ✅ Next serial common_id
    $common_id = $last_id + 1;

    foreach ($array as $values) {
        $plant_id       = $conn->real_escape_string($_GET["plant_id"]);
        $generic_name   = $conn->real_escape_string($values["generic_name"]);
        $generic_code   = $conn->real_escape_string($values["genericProductCode"]);
        $total_qty      = $conn->real_escape_string($values["total_qty"]);
        $emp_id         = $conn->real_escape_string($_GET["emp_id"]);

        // Insert into parent table
        $sql = "INSERT INTO yearly_forecast_qty 
                (common_id, plant_id, generic_name, generic_code, total_qty, productDetails, entry_by, entey_date)
                VALUES (
                    '$common_id',
                    '$plant_id',
                    '$generic_name',
                    '$generic_code',
                    '$total_qty',
                    '".$conn->real_escape_string(json_encode($values["productDetails"]))."',
                    '$emp_id',
                    '$entry_date'
                )";

        if ($conn->query($sql)) {
            $status1 = true;

            // ✅ Get inserted parent ID
            $yfq_id = $conn->insert_id;

            // ✅ Loop through productDetails and insert into yearly_forecast_qty_dtl
            if (!empty($values["productDetails"]) && is_array($values["productDetails"])) {
                foreach ($values["productDetails"] as $prod) {
                    $product_name = $conn->real_escape_string($prod["product_name"]);
                    $product_code = $conn->real_escape_string($prod["product_code"]);
                    $order_qty    = $conn->real_escape_string($prod["order_qty"]);

                    $sqlDtl = "INSERT INTO yearly_forecast_qty_dtl 
                               (yfq_id, yfq_common_id, product_name, product_code, order_qty)
                               VALUES (
                                   '$yfq_id',
                                   '$common_id',
                                   '$product_name',
                                   '$product_code',
                                   '$order_qty'
                               )";

                    if (!$conn->query($sqlDtl)) {
                        $status1 = false;
                        break; // stop detail loop if insert fails
                    }
                }
            }

        } else {
            $status1 = false;
            break; // stop parent loop if insert fails
        }
    }

    if ($status1) {
        echo "{\"status\":\"success\",\"common_id\":\"$common_id\"}";
    } else {
        echo "{\"status\":\"".$conn->error."\"}";
    }
}

else if($_GET['type'] == 'update_monthly_forecast') {
    
   
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        "status" => "error",
        "error" => "Invalid JSON",
        "raw" => file_get_contents('php://input'),
        "json_error" => json_last_error_msg()
    ]);
    exit;
}

$id = $conn->real_escape_string($input['id']);
$month = strtolower($conn->real_escape_string($input['month'])); // jan, feb, mar ...
$value = $conn->real_escape_string($input['value']);
$remainingQty = $conn->real_escape_string($input['remainingQty']);

// ✅ Define allowed months to avoid SQL injection
$allowedMonths = ["jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec"];
if (!in_array($month, $allowedMonths)) {
    echo json_encode(["status" => "error", "error" => "Invalid month", "raw" => $month]);
    exit;
}

// Fetch current row including existing history
$rowResult = $conn->query("SELECT *, IFNULL(history, '[]') as history FROM yearly_forecast_qty_dtl WHERE id = '$id'");
$row = $rowResult->fetch_assoc();

$history = json_decode($row['history'], true);
if (!is_array($history)) {
    $history = [];
}

// Add new snapshot to history
$snapshot = [
    'timestamp' => date('Y-m-d H:i:s'),
    'month' => $month,
    'month_value' => $value,
    'remainingQty' => $remainingQty,
    'row_data' => $row  // careful: row may contain binary/unencodable data
];

$history[] = $snapshot;

// ✅ Use JSON_THROW_ON_ERROR to catch bad encoding
try {
    $historyJson = json_encode($history, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "error" => "History JSON encode failed",
        "raw" => $e->getMessage()
    ]);
    exit;
}

$historyJson = $conn->real_escape_string($historyJson);

// ✅ Build update query safely
$sql = "UPDATE yearly_forecast_qty_dtl
        SET `$month` = '$value',
            remainingQty = '$remainingQty',
            history = '$historyJson'
        WHERE id = '$id'";

if ($conn->query($sql)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'error' => $conn->error]);
}

}




}

$conn->close();
?>