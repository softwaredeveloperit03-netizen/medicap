<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");

    if (!function_exists('bincard_utf8ize')) {
        function bincard_utf8ize($mixed) {
            if (is_array($mixed)) {
                foreach ($mixed as $key => $value) {
                    $mixed[$key] = bincard_utf8ize($value);
                }
            } else if (is_string($mixed)) {
                if (function_exists('mb_convert_encoding')) {
                    return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
                }
                return utf8_encode($mixed);
            }
            return $mixed;
        }
    }
    if (!function_exists('bincard_json_echo')) {
        function bincard_json_echo($data) {
            header('Content-Type: application/json; charset=UTF-8');
            $data = bincard_utf8ize($data);
            $flags = JSON_UNESCAPED_UNICODE;
            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
            }
            $json = json_encode($data, $flags);
            echo ($json === false) ? '[]' : $json;
        }
    }
    if (!function_exists('bincard_fetch_stock_data')) {
        function bincard_fetch_stock_data($conn, $plantId, $matCode) {
            $output11 = array();
            $matCodeEsc = $conn->real_escape_string($matCode);
            $plantIdEsc = $conn->real_escape_string($plantId);
            if ($matCodeEsc === '') {
                return $output11;
            }

            $sql11 = "SELECT * FROM stock_book WHERE material_code='".$matCodeEsc."'";
            if ($plantIdEsc !== '') {
                $sql11 .= " AND plant_id='".$plantIdEsc."'";
            }
            $sql11 .= " ORDER BY id DESC";
            $result11 = $conn->query($sql11);
            if ((!$result11 || $result11->num_rows === 0) && $plantIdEsc !== '') {
                $sql11 = "SELECT * FROM stock_book WHERE material_code='".$matCodeEsc."' ORDER BY id DESC";
                $result11 = $conn->query($sql11);
            }

            if ($result11 && $result11->num_rows > 0) {
                while ($row11 = $result11->fetch_assoc()) {
                    $row11["dispensing_qty"] = 0;
                    $row11["sampling_qty"] = 0;
                    $arNo = $conn->real_escape_string(isset($row11["ar_no"]) ? $row11["ar_no"] : "");
                    $grnNo = $conn->real_escape_string(isset($row11["grn_no"]) ? $row11["grn_no"] : "");

                    $sql10 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE material_code='".$matCodeEsc."' AND ar_no='".$arNo."'";
                    $result10 = $conn->query($sql10);
                    if ($result10 && $result10->num_rows > 0) {
                        $row10 = $result10->fetch_assoc();
                        $row11["dispensing_qty"] = floatval($row10["m_qty"]);
                    }

                    $sql12 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE material_code='".$matCodeEsc."'
                        AND ar_no='".$arNo."' AND issue_for='SAMPLING'";
                    $result12 = $conn->query($sql12);
                    if ($result12 && $result12->num_rows > 0) {
                        $row12 = $result12->fetch_assoc();
                        $row11["sampling_qty"] = floatval($row12["issue_qty"]);
                    }

                    $row11["bal_stock"] = floatval(isset($row11["qty"]) ? $row11["qty"] : 0)
                        - floatval($row11["dispensing_qty"]) - floatval($row11["sampling_qty"]);
                    if ($row11["bal_stock"] < 0) {
                        $row11["bal_stock"] = 0;
                    }

                    $medicapLot = trim(isset($row11["batch_no"]) ? $row11["batch_no"] : '');
                    $sbSql = "SELECT batch_no FROM sampling_batches WHERE material_code='".$matCodeEsc."'";
                    if ($grnNo !== '') {
                        $sbSql .= " AND grn_no='".$grnNo."'";
                    }
                    if ($arNo !== '') {
                        $sbSql .= " AND ar_no='".$arNo."'";
                    }
                    $sbSql .= " ORDER BY id DESC LIMIT 1";
                    $sbRes = $conn->query($sbSql);
                    if ($sbRes && $sbRes->num_rows > 0) {
                        $sbRow = $sbRes->fetch_assoc();
                        if (!empty(trim($sbRow['batch_no'] ?? ''))) {
                            $medicapLot = trim($sbRow['batch_no']);
                        }
                    }
                    $row11["medicap_lot_no"] = $medicapLot;

                    $output11[] = $row11;
                }
            }

            return $output11;
        }
    }

    $token = isset($_GET["token"]) ? $_GET["token"] : "";
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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
      $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
      $string = explode("$",$string);
      $_GET["emp_id"] = $string[0];
      $_GET["department"] = $string[1];
      break;
    }
}
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
   try{  
  if ($_GET["type"] == "getMaterials") {
        $output = array();
        $plantId = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : "");
        $materialType = trim(isset($_GET["material_type"]) ? $_GET["material_type"] : "Raw Material");
        if ($materialType === "") {
            $materialType = "Raw Material";
        }
        $materialTypeEsc = $conn->real_escape_string($materialType);

        // Same source as opening stock: material master by plant + type (no strict status filter)
        $sql = "SELECT id, material_type, material_subtype, material_name, material_code, unit, grade, status
            FROM material
            WHERE plant_id='".$plantId."'
            AND (material_type='".$materialTypeEsc."' OR material_subtype='".$materialTypeEsc."'
                 OR LOWER(IFNULL(material_type,'')) LIKE CONCAT(LOWER('".$materialTypeEsc."'), '%'))
            ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $matCode = $conn->real_escape_string(isset($row["material_code"]) ? $row["material_code"] : "");
                $row["qty1"] = 0;
                $row["m_qty"] = 0;
                $row["issue_qty1"] = 0;

                if ($matCode !== "") {
                    $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$matCode."' AND plant_id='".$plantId."'";
                    $result1 = $conn->query($sql1);
                    if ($result1 && $result1->num_rows > 0) {
                        $row1 = $result1->fetch_assoc();
                        $row["qty1"] = floatval($row1["qty"]);
                    }

                    $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE material_code='".$matCode."'
                        AND ar_no IN (SELECT ar_no FROM stock_book WHERE material_code='".$matCode."' AND plant_id='".$plantId."')";
                    $result1 = $conn->query($sql1);
                    if ($result1 && $result1->num_rows > 0) {
                        $row1 = $result1->fetch_assoc();
                        $row["m_qty"] = floatval($row1["m_qty"]);
                    }
                }

                $row["Issue"] = floatval($row["issue_qty1"]) + floatval($row["m_qty"]);
                $row["EOU_STOCK"] = floatval($row["qty1"]) - floatval($row["Issue"]);
                if ($row["EOU_STOCK"] < 0) {
                    $row["EOU_STOCK"] = 0;
                }

                $gradeRaw = isset($row['grade']) ? trim((string)$row['grade']) : '';
                $row['gradeName'] = $gradeRaw;
                if ($gradeRaw !== '') {
                    $idPart = preg_replace('/[^0-9,]/', '', $gradeRaw);
                    if ($idPart !== '' && preg_match('/^\d+(,\d+)*$/', $idPart)) {
                        $resQ = @$conn->query("SELECT GROUP_CONCAT(grade SEPARATOR ', ') AS gradeName FROM grade WHERE id IN (".$idPart.")");
                        if ($resQ && ($g = $resQ->fetch_assoc()) && !empty($g['gradeName'])) {
                            $row['gradeName'] = $g['gradeName'];
                        }
                    }
                }
                $row['grade'] = $row['gradeName'];

                $row['stock_data'] = bincard_fetch_stock_data($conn, $plantId, $matCode);
                $row['uom'] = isset($row['unit']) ? $row['unit'] : '';
                $output[] = $row;
            }
        }
        bincard_json_echo($output);
    }
  else if ($_GET["type"] == "getMaterialStockData") {
        $plantId = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : "");
        $matCode = trim(isset($_GET["material_code"]) ? $_GET["material_code"] : "");
        $output = bincard_fetch_stock_data($conn, $plantId, $matCode);
        bincard_json_echo($output);
    }
  else if ($_GET["type"] == "NewgetMaterials") {
        $output = Array();
 
       $sql = "SELECT * FROM material WHERE material_type='Raw Material' AND status='approve' and plant_id='".$_GET["plant_id"]."' order by id desc";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $received_qty = 0;
                $issue_qty = 0;
                $balance_qty = 0;
                
                 $sql1 = "SELECT IFNULL(SUM(qty), 0) as quarantine FROM stock_book WHERE material_code='".$row["material_code"]."' and status='quarantine'";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["quarantine"] = $row1["quarantine"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(a.qty), 0) as po_qty,IFNULL(SUM(a.gross_total), 0) as po_gross FROM po_material a LEFT JOIN purchaseorder b ON a.po_no = b.id 
                        WHERE a.material_code = '".$row["material_code"]."' AND b.po_no NOT IN (SELECT po_no FROM challan)";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["po_qty"] = $row1["po_qty"];
                         $row["po_gross"] = $row1["po_gross"];
                    }
                }
                 $sql1 = "SELECT SUM(value) AS total_value
                                FROM (
                                    SELECT 
                                        a.gross_total,
                                        a.rate,
                                        b.qty AS stock_qty,
                                        IFNULL(c.qty, 0) AS dis_qty,
                                        b.qty - IFNULL(c.qty, 0) AS bal_qty,
                                        (b.qty - IFNULL(c.qty, 0)) * a.rate AS value
                                    FROM 
                                        challan_materials a 
                                    LEFT JOIN 
                                        stock_book b ON a.grn_no = b.grn_no 
                                    LEFT JOIN 
                                        material_issue c ON b.ar_no = c.ar_no 
                                    WHERE 
                                        b.material_code = '".$row["material_code"]."'
                                ) AS subquery";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["total_value"] = $row1["total_value"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as UnderTest FROM stock_book WHERE material_code='".$row["material_code"]."' and status='Under Test'";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["UnderTest"] = $row1["UnderTest"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as Approved FROM stock_book WHERE material_code='".$row["material_code"]."' and status='Approved'";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["Approved"] = $row1["Approved"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["qty1"] = $row1["qty"];
                    }
                }
                
                $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$row["material_code"]."' ";
          
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["issue_qty1"] = $row1["issue_qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."')";
          
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["m_qty"] = $row1["m_qty"];
                    }
                }
                  $row["EOU_STOCK"] = $row["qty1"] - $row["issue_qty1"]- $row["m_qty"];
                  $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                  $row["Stock_price"] = $row["total_value"]+ $row["po_gross"];
                
                 
                   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".  $row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
          
          
          
           $output1 = array();
               // $sql1 = "SELECT * FROM sampling_batches WHERE challan_no = '".$row["challan_no"]."'";
                    $sql1 = "SELECT c.*, c1.challan_date, c1.po_no,c1.po_date, c1.vendor_no, v.vendor_name, m.material_type, m.material_subtype,
         m.material_name, m.grade , (select GROUP_CONCAT(grade.grade) from grade where FIND_IN_SET(grade.id , m.grade)) as gradeName 
         FROM challan_materials c LEFT JOIN challan c1 ON c.challan_no=c1.challan_no LEFT JOIN material m ON 
         c.material_code=m.material_code LEFT JOIN vendor v ON c.vendor_no=v.vendor_no WHERE c1.plant_id= '".$_GET["plant_id"]."' 
         AND m.material_type='Raw Material' and c.material_code='".$row["material_code"]."' ORDER BY c.id DESC";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                         $output1[] = $row1;
                    }
                }
          
                        $row["grn"] = $output1; 
                        
               
                       
                    $output[] = $row;
                    
                    
                
            }
            }
        echo json_encode($output);
   
    }
    else if ($_GET["type"] == "get_grn_stock_summary_by_material_code") {
        $output = Array();
  
        $grnNo = $conn->real_escape_string(isset($_GET["grn_no"]) ? $_GET["grn_no"] : "");
        $arNo = $conn->real_escape_string(isset($_GET["ar_no"]) ? $_GET["ar_no"] : "");
        $matCodeSummary = $conn->real_escape_string(isset($_GET["material_code"]) ? $_GET["material_code"] : "");
        $plantIdSummary = $conn->real_escape_string(isset($_GET["plant_id"]) ? $_GET["plant_id"] : "");
         $sql ="SELECT * from vw_stock_details a left join vw_stock_summary b on a.material_code=b.material_code 
        where a.plant_id='".$plantIdSummary."' and a.material_code='".$matCodeSummary."'   AND a.grn_no='".$grnNo."' AND a.ar_no='".$arNo."'";
    
        $result = $conn->query($sql);
             if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output1 = Array(); 
                  
                 $sql2="SELECT a.issue_for,a.entry_by,a.batch_no,a.qty,a.unit,b.product_name,a.unit,a.entry_date,c.work_order_no FROM material_issue a 
                JOIN product b on a.plant_id = b.plant_id and a.product_code = b.product_code JOIN mfg_work_order_hdr c on a.work_order_id = c.id
                where a.material_code = '".$row["material_code"]."'  order by a.id desc";
             
                
                $result1 = $conn->query($sql2);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $output1[]=$row1;
                    }
                     
                }else{
                     $output1=[];
                }
                $row['issued']= $output1;
                $output[] = $row;
            }
                 
             }
              echo json_encode($output);
    }
    else if ($_GET["type"] == "get_grn_stock_by_material_code") {
        
         $output = Array();
       
       
        
        
            //   $sql="SELECT a.material_code,a.ar_no,a.grn_no,a.grn_date,a.batch_no,a.qty,a.total_containers,v.vendor_name,b.qty as issue_qty ,m.uom,ch.challan_no,ch.po_no,po.quotation_amt 
            // from stock_book a LEFT JOIN challan_materials cm on a.material_code = cm.material_code LEFT JOIN challan ch on cm.challan_no = ch.challan_no 
            // left JOIN material m ON a.material_code = m.material_code LEFT JOIN po_material po on a.material_code = po.material_code LEFT JOIN mst_vendor_materials mv ON m.material_code=mv.material_code LEFT JOIN vendor v ON mv.manufacturer_code = v.vendor_no LEFT JOIN 
            // material_issue b on a.material_code= b.material_code and a.plant_id= b.plant_id and a.ar_no = b.ar_no and a.grn_no=b.grn_no 
            // where a.material_code='".$_GET["material_code"]."' group by a.material_code,a.ar_no,a.grn_no, a.grn_date,a.batch_no,v.vendor_name,b.qty,ch.challan_no,a.qty,a.total_containers,
            // ch.po_no,po.quotation_amt ORDER by a.grn_date";
            
        
                $sql="SELECT a.material_code,a.ar_no,a.grn_no,a.grn_date,a.batch_no,a.qty,a.total_containers,v.vendor_name,m.uom,ch.challan_no,ch.po_no,po.quotation_amt 
            from stock_book a LEFT JOIN challan_materials cm on a.material_code = cm.material_code LEFT JOIN challan ch on cm.challan_no = ch.challan_no 
            left JOIN material m ON a.material_code = m.material_code LEFT JOIN po_material po on a.material_code = po.material_code LEFT JOIN mst_vendor_materials mv ON m.material_code=mv.material_code LEFT JOIN vendor v ON mv.manufacturer_code = v.vendor_no
            where a.material_code='".$_GET["material_code"]."' group by a.material_code,a.ar_no,a.grn_no, a.grn_date,a.batch_no,v.vendor_name,ch.challan_no,a.qty,a.total_containers,
            ch.po_no,po.quotation_amt ORDER by a.grn_date";
            
            
            
            
              $result = $conn->query($sql);
             if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $output1 = Array();
                  
                 $sql2="SELECT a.issue_for,a.entry_by,a.batch_no,a.qty,a.unit,b.product_name,a.unit,a.entry_date,c.work_order_no FROM material_issue a 
                JOIN product b on a.plant_id = b.plant_id and a.product_code = b.product_code JOIN mfg_work_order_hdr c on a.work_order_id = c.id
                where a.material_code = '".$row["material_code"]."' order by a.id desc";
                
                $result1 = $conn->query($sql2);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                    $output1[]=$row1;
                    }
                     
                }else{
                     $output1=[];
                }
                $row['issued']= $output1;
                // ///////////////////////////////////////////////////////////////////////////
                 $received_qty = 0;
                $issue_qty = 0;
                $balance_qty = 0;
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$_GET["material_code"]."' ";
                //AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                         $row["qty1"] = $row1["qty"];
                    }
                }
                
                $sql1 = "SELECT IFNULL(SUM(undertest_qty), 0) as issue_qty FROM stock_book WHERE material_code='".$_GET["material_code"]."' ";
            // echo    $sql1 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no IN 
            //     (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' 
            //     AND challan_for='EOU')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["issue_qty1"] = $row1["issue_qty"];
                    }
                }
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE ar_no IN 
                 (SELECT ar_no FROM stock_book WHERE material_code='".$_GET["material_code"]."')";
            // echo    $sql1 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no IN 
            //     (SELECT ar_no FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' 
            //     AND challan_for='EOU')";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["m_qty"] = $row1["m_qty"];
                    }
                }
                  $row["EOU_STOCK"] = $row["qty"] - $row["issue_qty1"]- $row["m_qty"];
                  $row["bal_stock"] = $row["qty"] - $row["issue_qty1"]- $row["m_qty"];
                  if($row["bal_stock"]<0){
                      $row["bal_stock"]=0;
                  }
                  if($row["EOU_STOCK"]<0){
                      $row["EOU_STOCK"]=0;
                  }
                  $row["Issue"] = $row["issue_qty1"]+ $row["m_qty"];
                  
                
                $output[] = $row;
            }
                 
             }
             echo json_encode($output);
    }
    else if ($_GET["type"] == "getPackingMaterials") {
        $output = Array();
        $sql = "SELECT * FROM material WHERE material_type='Packing Material' AND status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $received_qty = 0;
                $issue_qty = 0;
                $balance_qty = 0;
                
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' AND challan_for='EOU'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["EOU_STOCK"] = $row1["qty"];
                    }
                }
                
                $sql1 = "SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved' AND challan_for='PART-A'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["PARTA_STOCK"] = $row1["qty"];
                    }
                }
                
                $output1 = array();
                $sql1 = "SELECT * FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row1["vendor_no"]."'";
                        $result2 = $corporate->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["vendor_name"] = $row2["vendor_name"];
                            }
                        }
                        
                        $issue_qty = 0;
                        $sql2 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no='".$row1["ar_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["issue_qty"] = +$row2["issue_qty"];
                            }
                        } else {
                            $row1["issue_qty"] = 0;
                        }
                        $row1["balance_qty"] = +$row1["qty"] - +$row1["issue_qty"];
                        
                        $received_qty += +$row1["qty"];
                        $issue_qty += +$row1["issue_qty"];
                        $unit = $row1["unit"];
                        
                        $output2 = array();
                        $sql2 = "SELECT m.*, p.product_name FROM material_issue m LEFT JOIN product p ON m.product_code=p.product_code WHERE m.ar_no='".$row1["ar_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["issued"] = $output2;
                        $output1[] = $row1;
                    }
                    $balance_qty = $received_qty - $issue_qty;
                    $balance_qty = round($balance_qty, 2);
                    $row["received_qty"] = $received_qty;
                    $row["issue_qty"] = $issue_qty;
                    $row["balance_qty"] = $balance_qty;
                    $row["unit"]=$unit;
                    $row["grns"] = $output1;
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "issueMaterial") {
        $sql = "INSERT INTO material_issue (grn_no, ar_no, entry_date, material_code, product_code, batch_no, issue_for, qty, unit, entry_by) VALUES ('".$input["grn_no"]."', '".$input["ar_no"]."', '".$input["issue_date"]."', '".$input["material_code"]."', '".$input["product_code"]."', '".$input["batch_no"]."', '".$input["issue_for"]."', '".$input["issued_qty"]."', '".$input["unit"]."', '".$_GET["emp_id"]."')";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
    } else if ($_GET["type"] == "disableStock") {
        $sql = "UPDATE stock_book SET status='DISABLED' WHERE id='".$_GET["id"]."'";
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"".$conn->error."\"}";
        }
   
    } else if($_GET['type'] == 'downloadMaterialLog'){
          if($_GET["plant_id"] == 96) {//Olive
        $_GET['filename'] = ''; $_GET['pdftype'] = 'landscape'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
<table style="width: 785px">
    <tr>
        <th style="width: 125px">Name of Packing Material(RM):-</th>
        <td style="width: 170px"></td>

        <th style="width: 100px">Item Code:-</th>
        <td style="width: 120px"></td>

        <th style="width: 120px">Manufacturing line:-</th>
        <td style="width: 150px"></td>


    </tr>
</table>
<table style="width: 785px" border="1">
    <br>
    <tr style="text-align:center; height: 30px; ">
        <td >Date</td>
        <td >G.R.N.No</td>
        <td>Qty. received</td>
        <td >Stock Qty.</td>
        <td>Op.Bal.</td>
        <td>Qty. Issued</td>
        <td>Returned Qty.</td>
        <td>Product Name</td>
        <td >Medicap Lot No</td>
        <td>Bal. In use Qty.</td>
        <td >Bal. Qty.</td>
        <td >Sign</td>
        <td >Remark</td>
    </tr>';
    
    $sql = "SELECT * from vw_stock_details a left join vw_stock_summary b on a.material_code=b.material_code 
        where a.plant_id='".$_GET["plant_id"]."'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                $html.='
    <tr style="text-align:center; line-height: 30px;">
        <td></td>
        <td>'.$row['grn_no'].'</td>
        <td>'.$row['received_qty'].'</td>
        <td>'.$row['ar_no'].'</td>
        <td>'.$row['issued_Qty'].'</td>
        <td></td>
        <td>'.$row['issue_by'].'</td>
        <td>'.$row['ar_no'].'</td>
        <td>'.$row['material_name'].'</td>
        <td>'.$row['batch_no'].'</td>
        <td></td>
        <td>'.$row['material_type'].'</td>
        <td>'.$row['balence_qty'].'</td>
        <td></td>
        <td></td>
     
    </tr>';}}

         $html.='  </table>';

     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
        
     if($_GET["plant_id"] == 59) {//amerdeep
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Material Type</b></td>
           <td style="width:20%;text-align:center"><b>Material Sub type</b></td>
            <td style="width:15%;text-align:center"><b>Material Code</b></td>
             <td style="width:15%;text-align:center"><b>Material Name</b></td>
             <td style="width:15%;text-align:center"><b>Grade</b></td>
             <td style="width:15%;text-align:center"><b>Av.Qty.Kg </b></td>
         </tr>';
         
         
       $sql = "SELECT * FROM material WHERE material_type='Raw Material' AND status='approve' and plant_id='".$_GET["plant_id"]."' order by id desc";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
                   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
 
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['material_type'].'</td>
           <td style="width:20%;text-align:center">'.$row['material_subtype'].'</td>
            <td style="width:15%;text-align:center">'.$row['material_code'].'</td>
             <td style="width:15%;text-align:center">'.$row['material_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['gradeName'].'</td>
             <td style="width:15%;text-align:center">'.$row['EOU_STOCK'].''.$row['uom'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   }
    else if($_GET["plant_id"] == 68) {//Novo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='
        ';
              
           $sql = "
           
           ";
          
        //   $sql = "SELECT m.*,s.status,s.entry_date,c.tax_invoice,s.batch_no FROM stock_book s LEFT JOIN material m ON s.user_no=m.user_no LEFT JOIN challan c on m.material_type=c.material_type WHERE m.material_type ='Raw Material' AND s.status='Approved' where material_code='".$row["material_code"]."' ORDER BY id DESC ";
    //          $result = $conn->query($sql);
    // $row = $result->fetch_assoc();{
                    
                    $html.='
                    
                    ';
    		    
    // }
    	    
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            
			}	    
    	   
            else if($_GET["plant_id"] == 142){//demo
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Material Type</b></td>
           <td style="width:20%;text-align:center"><b>Material Sub type</b></td>
            <td style="width:15%;text-align:center"><b>Material Code</b></td>
             <td style="width:15%;text-align:center"><b>Material Name</b></td>
             <td style="width:15%;text-align:center"><b>Grade</b></td>
             <td style="width:15%;text-align:center"><b>Av.Qty.Kg </b></td>
         </tr>';
         
         
       $sql = "SELECT * FROM material WHERE material_type='Raw Material' AND status='approve' and plant_id='".$_GET["plant_id"]."' order by id desc";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
                   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
 
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['material_type'].'</td>
           <td style="width:20%;text-align:center">'.$row['material_subtype'].'</td>
            <td style="width:15%;text-align:center">'.$row['material_code'].'</td>
             <td style="width:15%;text-align:center">'.$row['material_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['gradeName'].'</td>
             <td style="width:15%;text-align:center">'.$row['EOU_STOCK'].''.$row['uom'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   } 
            
            else if($_GET["plant_id"] == 28){//amerdeep
        $_GET['filename'] = ''; $_GET['pdftype'] = 'onlyheader'; include("../pdfimp2.php");
         $html= "";
          
    		      $html.='
    		     
        <table cellpadding="5" border="0.1">
      <tr>
         <td style="width:5%;text-align:center"><b>Sr No.</b></td>
          <td style="width:15%;text-align:center"><b>Material Type</b></td>
           <td style="width:20%;text-align:center"><b>Material Sub type</b></td>
            <td style="width:15%;text-align:center"><b>Material Code</b></td>
             <td style="width:15%;text-align:center"><b>Material Name</b></td>
             <td style="width:15%;text-align:center"><b>Grade</b></td>
             <td style="width:15%;text-align:center"><b>Av.Qty.Kg </b></td>
         </tr>';
         
         
       $sql = "SELECT * FROM material WHERE material_type='Raw Material' AND status='approve' and plant_id='".$_GET["plant_id"]."' order by id desc";
 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
             $i=1;
            while ($row = $result->fetch_assoc()) {
                   $q= "SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('".$row['grade']."')";
             $resQ = $conn->query($q);
              $prodLatest = $resQ->fetch_assoc(); 
         
          $row['gradeName'] = $prodLatest['gradeName']; 
 
              $html.='  <tr>
         <td style="width:5%;text-align:center">'.$i.'</td>
          <td style="width:15%;text-align:center">'.$row['material_type'].'</td>
           <td style="width:20%;text-align:center">'.$row['material_subtype'].'</td>
            <td style="width:15%;text-align:center">'.$row['material_code'].'</td>
             <td style="width:15%;text-align:center">'.$row['material_name'].'</td>
             <td style="width:15%;text-align:center">'.$row['gradeName'].'</td>
             <td style="width:15%;text-align:center">'.$row['EOU_STOCK'].''.$row['uom'].'</td>
         </tr>';
          $i++;
            }
        }
         
         $html.='  </table>';
            
        
     
     $pdf->writeHTML($html, true, false, false, false, '');
        
        $pdf->Output('downloadCandidatesList.pdf', 'I');
     
     
     
   } else {
        $esc = function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };
        $fmtQty = function ($v) {
            if ($v === null || $v === '') {
                return '0.00';
            }
            return number_format((float)$v, 2, '.', '');
        };
        $plantId = $conn->real_escape_string(isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
        $materialType = trim(isset($_GET['material_type']) ? $_GET['material_type'] : 'Raw Material');
        if ($materialType === '' || strtolower($materialType) === 'raw ma') {
            $materialType = 'Raw Material';
        }
        $materialTypeEsc = $conn->real_escape_string($materialType);

        $_GET['filename'] = 'Material Log';
        $_GET['pdftype'] = 'landscape';
        include('../pdfimp2.php');

        $html .= '<h3 style="text-align:center;">Material Log — '.$esc($materialType).'</h3>
            <table cellpadding="4" border="0.1">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold;">
                    <td style="width:6%;text-align:center;">Sr.</td>
                    <td style="width:14%;text-align:center;">Material Type</td>
                    <td style="width:14%;text-align:center;">Subtype</td>
                    <td style="width:22%;text-align:center;">Material Name</td>
                    <td style="width:12%;text-align:center;">Code</td>
                    <td style="width:12%;text-align:center;">Received Qty</td>
                    <td style="width:10%;text-align:center;">Issued Qty</td>
                    <td style="width:10%;text-align:center;">Balance Qty</td>
                </tr>
            </thead>';

        $sql = "SELECT id, material_type, material_subtype, material_name, material_code, unit
            FROM material
            WHERE plant_id='".$plantId."'
            AND (material_type='".$materialTypeEsc."' OR material_subtype='".$materialTypeEsc."'
                 OR LOWER(IFNULL(material_type,'')) LIKE CONCAT(LOWER('".$materialTypeEsc."'), '%'))
            ORDER BY id DESC";
        $result = $conn->query($sql);
        $i = 1;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $matCode = $conn->real_escape_string(isset($row['material_code']) ? $row['material_code'] : '');
                $receivedQty = 0;
                $issueQty = 0;
                $unit = isset($row['unit']) ? $row['unit'] : '';
                if ($matCode !== '') {
                    $resR = $conn->query("SELECT IFNULL(SUM(qty), 0) as qty FROM stock_book WHERE material_code='".$matCode."' AND plant_id='".$plantId."'");
                    if ($resR && ($r = $resR->fetch_assoc())) {
                        $receivedQty = floatval($r['qty']);
                    }
                    $resI = $conn->query("SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE material_code='".$matCode."'
                        AND ar_no IN (SELECT ar_no FROM stock_book WHERE material_code='".$matCode."' AND plant_id='".$plantId."')");
                    if ($resI && ($iss = $resI->fetch_assoc())) {
                        $issueQty = floatval($iss['m_qty']);
                    }
                }
                $balance = $receivedQty - $issueQty;
                if ($balance < 0) {
                    $balance = 0;
                }
                $html .= '<tr>
                    <td style="width:6%;text-align:center;">'.$i.'</td>
                    <td style="width:14%;text-align:center;">'.$esc($row['material_type']).'</td>
                    <td style="width:14%;text-align:center;">'.$esc($row['material_subtype']).'</td>
                    <td style="width:22%;text-align:center;">'.$esc($row['material_name']).'</td>
                    <td style="width:12%;text-align:center;">'.$esc($row['material_code']).'</td>
                    <td style="width:12%;text-align:center;">'.$fmtQty($receivedQty).' '.$esc($unit).'</td>
                    <td style="width:10%;text-align:center;">'.$fmtQty($issueQty).' '.$esc($unit).'</td>
                    <td style="width:10%;text-align:center;">'.$fmtQty($balance).' '.$esc($unit).'</td>
                </tr>';
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="8" style="text-align:center;">No records found.</td></tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('MaterialLog.pdf', 'I');
   }
			}else if ($_GET["type"] == "downloadMaterialBinCard") {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=".$_GET["ar_no"].".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        flush();

        // require_once '../../PHPExcel/Classes/PHPExcel/IOFactory.php';
        require_once '../PHPExcel/Classes/PHPExcel.php';
        
        $excel2 = PHPExcel_IOFactory::createReader('Excel2007');
        $excel2 = $excel2->load('documents/BINCARD.xlsx');
        $excel2->setActiveSheetIndex(0);
        
        $sql = "SELECT * FROM stock_book WHERE ar_no='".$_GET["ar_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql2 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row["vendor_no"]."'";
                $result2 = $corporate->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $row["vendor_name"] = $row2["vendor_name"];
                    }
                }
                
                $received_qty = +$row["qty"];
                
                $excel2->getActiveSheet()->setCellValue('A2', $row["inword_no"])
                        ->setCellValue('B2', $row["vendor_name"])
                        ->setCellValue('C2', $row["grn_no"])       
                        ->setCellValue('D2', $row["batch_no"])
                        ->setCellValue('E2', $row["mfg_date"])
                        ->setCellValue('F2', $row["exp_date"])
                        ->setCellValue('G2', $row["qty"])
                        ->setCellValue('I2', $row["ar_no"])
                        ->setCellValue('A3', $row["inword_date"]);
                
                $i = 3;   
                $sql1 = "SELECT m.*, p.product_name FROM material_issue m LEFT JOIN product p ON m.product_code=p.product_code WHERE m.ar_no='".$_GET["ar_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $balance_qty = $received_qty - +$row1["qty"];
                        $excel2->getActiveSheet()->setCellValue('G'.$i, $received_qty)
                        ->setCellValue('K'.$i, $row1["entry_date"])
                        ->setCellValue('L'.$i, $row1["product_name"])
                        ->setCellValue('M'.$i, $row1["batch_no"])       
                        ->setCellValue('N'.$i, $row1["ar_no"])
                        ->setCellValue('O'.$i, $row1["qty"])
                        ->setCellValue('P'.$i, $row1["qty"])
                        ->setCellValue('Q'.$i, $balance_qty);
                        $i++;
                        $received_qty -= +$row1["qty"];
                    }
                }
            }
        }
        $objWriter = PHPExcel_IOFactory::createWriter($excel2, 'Excel2007');
        $objWriter->save('php://output');
        
    }           else if ($_GET["type"] == "downloadGrn") { 
     if($_GET["plant_id"] == 59) {//amardeep 

        require '../tcpdf/tcpdf.php';
        $_GET['formatno'] = 'Format No:'; $_GET['pdftype'] = 'topheader-landscape'; include("../pdfimp.php");
       
        $html.='<h3 style="text-align:center;">MaterialLog List</h3>
            <table cellpadding="5">
            <thead>
                <tr style="background-color:#DDDAD9;font-weight:bold; border: solid 1px black">
                    <td style="width: 5%;">Sr.</td>
                    <td style="width: 12%;">Matrial type</td>
                    <td style="width: 12%;">Subtype</td>
                    <td style="width: 15%;">Matrial Name</td>
                    <td style="width: 8%;">Grade</td>
                    <td style="width: 11%;">Code</td>
                    <td style="width: 10%;">Received Qty</td>
                    <td style="width: 10%;">Issued Qty</td>
                    <td style="width: 10%;">Balance Qty</td>
                    <td style="width: 7%;">Unit</td>
                </tr>
            </thead>';
             
        $sql = "SELECT * FROM material WHERE status='approve'";
        $result = $conn->query($sql);
        $i=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $received_qty = 0;
                $issue_qty = 0;
                $balance_qty = 0;
                $output1 = array();
                $sql1 = "SELECT * FROM stock_book WHERE material_code='".$row["material_code"]."' AND status='Approved'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $sql2 = "SELECT vendor_name FROM vendor WHERE vendor_no='".$row1["vendor_no"]."'";
                        $result2 = $corporate->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["vendor_name"] = $row2["vendor_name"];
                            }
                        }
                        
                        $issue_qty = 0;
                        $sql2 = "SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE ar_no='".$row1["ar_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $row1["issue_qty"] = +$row2["issue_qty"];
                            }
                        } else {
                            $row1["issue_qty"] = 0;
                        }
                        $row1["balance_qty"] = +$row1["qty"] - +$row1["issue_qty"];
                        
                        $received_qty += +$row1["qty"];
                        $issue_qty += +$row1["issue_qty"];
                        
                        $output2 = array();
                        $sql2 = "SELECT m.*, p.product_name FROM material_issue m LEFT JOIN product p ON m.product_code=p.product_code WHERE m.ar_no='".$row1["ar_no"]."'";
                        $result2 = $conn->query($sql2);
                        if ($result2->num_rows > 0) {
                            while ($row2 = $result2->fetch_assoc()) {
                                $output2[] = $row2;
                            }
                        }
                        $row1["issued"] = $output2;
                        $output1[] = $row1;
                    }
                    $balance_qty = $received_qty - $issue_qty;
                    $balance_qty = round($balance_qty, 2);
                    $row["received_qty"] = $received_qty;
                    $row["issue_qty"] = $issue_qty;
                    $row["balance_qty"] = $balance_qty;
                    $row["unit"]= $unit;
                    $row["grns"] = $output1;
                    $output[] = $row;
                $html.='<tr>
                    <td style="width: 5%;">'.$i++.'</td>
                    <td style="width: 12%;">'.$row['material_type'].'</td>
                    <td style="width: 12%;">'.$row['material_subtype'].'</td>
                    <td style="width: 15%;">'.$row['material_name'].'</td>
                    <td style="width: 8%;">'.$row['grade'].'</td>
                    <td style="width: 11%;">'.$row['material_code'].'</td>
                    <td style="width: 10%;">'.$row['received_qty'].''.$row['unit'].'</td>
                    <td style="width: 10%;">'.$row['issue_qty'].''.$row['unit'].'</td>
                    <td style="width: 10%;">'.$row['balance_qty'].''.$row['unit'].'</td>
                     <td style="width: 7%;">'.$row['unit'].'</td>
                </tr>
                ';
                }
            }
        }
        $html.="</table>";
           
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Material.pdf', 'I');
   
    
}
  else if($_GET["plant_id"] == 28) {//demo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='
        ';
            //   $sql = "SELECT * FROM material WHERE id='".$_GET["id"]."'";
        //   $sql = "SELECT m.*,s.status,s.entry_date,c.tax_invoice,s.batch_no,s.mfg_date,s.exp_date,s.grn_no,s.ar_no,p.product_name,s.qty as balance_qty,mi.qty as issue_qty,v.vendor_name,s.vendor_no,v.vendor_no FROM stock_book s LEFT JOIN material m ON s.user_no=m.user_no LEFT JOIN material_issue mi ON s.product_code=mi.product_code LEFT JOIN vendor v on s.vendor_no=v.vendor_no LEFT JOIN product p ON s.product_code=p.product_code JOIN challan c on m.material_type=c.material_type WHERE m.material_type ='Raw Material' AND s.status='Approved'  and m.id='".$_GET["id"]."'";
          
        $sql = "SELECT s.*, m.material_type, m.material_subtype, m.grade, m.material_name, v.vendor_name FROM stock_book s LEFT JOIN material m ON s.material_code=m.material_code LEFT JOIN vendor v ON s.vendor_no=v.vendor_no WHERE s.user_no='".$_GET["user_no"]."' AND m.material_type='Packing Material' AND m.material_subtype LIKE '%".$_GET["material_subtype"]."%' AND s.vendor_no LIKE '%".$_GET["vendor_no"]."' AND s.status LIKE '%".$_GET["status"]."'";
        
        
    //          $result = $conn->query($sql);
    //           if ($result->num_rows > 0) {
    // while ($row = $result->fetch_assoc()){
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();{
                    
                    $html.='<table border="1">
    
                    
                        <tr>
                        <td style="width: 100px;"> Format Title :</td>
                        <td style="width: 440px;"> Raw material stock register</td>
                        </tr>
                       
                        <tr>
                        <td style="width: 100px;"> Format No.:</td>
                        <td style="width: 170px;"> F/SOP/WR/005/02-01</td>
                        <td style="width: 100px;"> Page No.:</td>
                        <td style="width: 170px;"> 1 of 1
                    
                    </td>
                       
                        </tr>
                        <tr>
                        <td style="width: 100px;"> Ref. SOP No.:</td>
                        <td style="width: 440px;"> SOP/WR/005</td>
                        </tr>
                    </table><div></div>
                     <table>
        <tr>
            <td style="width: 320px;">Raw Material Name:- '.$row["material_name"].'  </td>
            <td style="width: 220px;">  Material Code:- '.$row["material_code"].'</td>
        </tr>
    </table>
    <table border="1" >
          <tr style="text-align: center; background-color:#DDDAD9;">
        <td style="width: 31.7px;text-align:center;font-size:8px;">Date of Receipt </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Manufacturer </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Supplier </td>
        <td style="width: 31.7px;text-align:center;font-size:8px;">Inv. No & Date </td>
        <td style="width:26.7px;text-align:center;font-size:8px;">Medicap Lot No </td>
        <td style="width:21.7px;text-align:center;font-size:8px;">Mfg. Date </td>
        <td style="width:21.7px;text-align:center;font-size:8px;">Exp. Date </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Openi-ng Qty </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">GRN No. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Date of Issue </td>
        <td style="width:56.7px;text-align:center;font-size:8px;">Product name </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Product Batch No </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Issue Qty. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Balance Qty. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Total Quantity </td>

        <td style="width:31.7px;text-align:center;font-size:8px;">Checked by </td>
        </tr>
        <tr>
        <td style="width: 31.7px;text-align:center;font-size:8px;"> '.$row["entry_date"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["vendor_name"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width: 31.7px;text-align:center;font-size:8px;"> '.$row["tax_invoice"].' </td>
        <td style="width:26.7px;text-align:center;font-size:8px;"> '.$row["batch_no"].' </td>
        <td style="width:21.7px;text-align:center;font-size:8px;"> '.$row["mfg_date"].' </td>
        <td style="width:21.7px;text-align:center;font-size:8px;"> '.$row["exp_date"].'</td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["grn_no"].'</td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width:56.7px;text-align:center;font-size:8px;"> '.$row["product_name"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["batch_no"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["issue_qty"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["balance_qty"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">  </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">  </td>
        </tr>
        </table>
                    
<div></div>
<table border="1">
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
</table>
                    ';
    		    
    	        
    }  
                
            // }  
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
    }	    
    	   
    
            
            else if($_GET["plant_id"] == 64){//Novo
                $_GET['filename'] = 'Receiving of Material Log'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimp2.php');
        $html.='
        ';
            //   $sql = "SELECT * FROM material WHERE id='".$_GET["id"]."'";
          $sql = "SELECT m.*,s.status,s.entry_date,c.tax_invoice,s.batch_no,s.mfg_date,s.exp_date,s.grn_no,s.ar_no,p.product_name,s.qty as balance_qty,mi.qty as issue_qty,v.vendor_name,s.vendor_no,v.vendor_no FROM stock_book s LEFT JOIN material m ON s.user_no=m.user_no LEFT JOIN material_issue mi ON s.product_code=mi.product_code LEFT JOIN vendor v on s.vendor_no=v.vendor_no LEFT JOIN product p ON s.product_code=p.product_code JOIN challan c on m.material_type=c.material_type WHERE m.material_type ='Raw Material' AND s.status='Approved'  and m.id='".$_GET["id"]."'";
          
        
    //          $result = $conn->query($sql);
    //           if ($result->num_rows > 0) {
    // while ($row = $result->fetch_assoc()){
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();{
                    
                    $html.='<table border="1">
    
                    
                        <tr>
                        <td style="width: 100px;"> Format Title :</td>
                        <td style="width: 440px;"> Raw material stock register</td>
                        </tr>
                       
                        <tr>
                        <td style="width: 100px;"> Format No.:</td>
                        <td style="width: 170px;"> F/SOP/WR/005/02-01</td>
                        <td style="width: 100px;"> Page No.:</td>
                        <td style="width: 170px;"> 1 of 1
                    
                    </td>
                       
                        </tr>
                        <tr>
                        <td style="width: 100px;"> Ref. SOP No.:</td>
                        <td style="width: 440px;"> SOP/WR/005</td>
                        </tr>
                    </table><div></div>
                     <table>
        <tr>
            <td style="width: 320px;">Raw Material Name:- '.$row["material_name"].'  </td>
            <td style="width: 220px;">  Material Code:- '.$row["material_code"].'</td>
        </tr>
    </table>
    <table border="1" >
          <tr style="text-align: center; background-color:#DDDAD9;">
        <td style="width: 31.7px;text-align:center;font-size:8px;">Date of Receipt </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Manufacturer </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Supplier </td>
        <td style="width: 31.7px;text-align:center;font-size:8px;">Inv. No & Date </td>
        <td style="width:26.7px;text-align:center;font-size:8px;">Medicap Lot No </td>
        <td style="width:21.7px;text-align:center;font-size:8px;">Mfg. Date </td>
        <td style="width:21.7px;text-align:center;font-size:8px;">Exp. Date </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Openi-ng Qty </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">GRN No. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Date of Issue </td>
        <td style="width:56.7px;text-align:center;font-size:8px;">Product name </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Product Batch No </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Issue Qty. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Balance Qty. </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">Total Quantity </td>

        <td style="width:31.7px;text-align:center;font-size:8px;">Checked by </td>
        </tr>
        <tr>
        <td style="width: 31.7px;text-align:center;font-size:8px;"> '.$row["entry_date"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["vendor_name"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width: 31.7px;text-align:center;font-size:8px;"> '.$row["tax_invoice"].' </td>
        <td style="width:26.7px;text-align:center;font-size:8px;"> '.$row["batch_no"].' </td>
        <td style="width:21.7px;text-align:center;font-size:8px;"> '.$row["mfg_date"].' </td>
        <td style="width:21.7px;text-align:center;font-size:8px;"> '.$row["exp_date"].'</td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["grn_no"].'</td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> </td>
        <td style="width:56.7px;text-align:center;font-size:8px;"> '.$row["product_name"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["batch_no"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["issue_qty"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;"> '.$row["balance_qty"].' </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">  </td>
        <td style="width:31.7px;text-align:center;font-size:8px;">  </td>
        </tr>
        </table>
                    
<div></div>
<table border="1">
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
</table>
                    ';
    		    
    	        
    }  
                
            // }  
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
            
            } else {
        $esc = function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };
        $fmtDate = function ($v) {
            if ($v === null || $v === '' || $v === '0000-00-00' || $v === '0000-00-00 00:00:00') {
                return '';
            }
            $ts = strtotime($v);
            return $ts ? date('d-m-Y', $ts) : $esc($v);
        };
        $fmtQty = function ($v) {
            if ($v === null || $v === '') {
                return '0.00';
            }
            return number_format((float)$v, 2, '.', '');
        };

        $plantId = $conn->real_escape_string(isset($_GET['plant_id']) ? $_GET['plant_id'] : '');
        $matId = $conn->real_escape_string(isset($_GET['id']) ? $_GET['id'] : '');
        $mat = null;
        if ($matId !== '') {
            $sqlMat = "SELECT id, material_type, material_subtype, material_name, material_code, unit, grade
                FROM material WHERE id='".$matId."'";
            if ($plantId !== '') {
                $sqlMat .= " AND plant_id='".$plantId."'";
            }
            $sqlMat .= " LIMIT 1";
            $resMat = $conn->query($sqlMat);
            if ($resMat && $resMat->num_rows > 0) {
                $mat = $resMat->fetch_assoc();
            }
        }

        $_GET['filename'] = 'Material Receiving  ';
        $_GET['pdftype'] = 'landscape';
        include('../pdfimp2.php');

        if (!$mat) {
            $html .= '<h3 style="text-align:center;">Material Receiving Notes</h3><p>No material found for this record.</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Material Receiving Notes.pdf', 'I');
        } else {
            $matCode = $conn->real_escape_string(isset($mat['material_code']) ? $mat['material_code'] : '');
            $unit = isset($mat['unit']) ? $mat['unit'] : '';
            $html .= '<h3 style="text-align:center;">Material Receiving Notes</h3>
            <table cellpadding="4">
                <tr>
                    <td style="width:18%;"><b>Material Name</b></td>
                    <td style="width:82%;" colspan="3">'.$esc($mat['material_name']).'</td>
                </tr>
                <tr>
                    <td style="width:18%;"><b>Material Type</b></td>
                    <td style="width:32%;">'.$esc($mat['material_type']).'</td>
                    <td style="width:18%;"><b>Subtype</b></td>
                    <td style="width:32%;">'.$esc($mat['material_subtype']).'</td>
                </tr>
                <tr>
                    <td style="width:18%;"><b>Material Code</b></td>
                    <td style="width:32%;">'.$esc($mat['material_code']).'</td>
                    <td style="width:18%;"><b>Grade / Unit</b></td>
                    <td style="width:32%;">'.$esc($mat['grade']).' / '.$esc($unit).'</td>
                </tr>
            </table><br/>
            <table cellpadding="3">
                <tr style="background-color:#DDDAD9;font-weight:bold;">
                    <td style="width:5%;">Sr.</td>
                    <td style="width:14%;">Receiving no</td>
                    <td style="width:10%;">Date</td>
                    <td style="width:12%;">Medicap Lot No</td>
                    <td style="width:12%;">Received Qty</td>
                    <td style="width:12%;">Sampling Qty</td>
                    <td style="width:12%;">Dispensing Qty</td>
                    <td style="width:11%;">Balance Qty</td>
                </tr>';

            $sr = 1;
            $hasRows = false;
            if ($matCode !== '') {
                $sqlSb = "SELECT * FROM stock_book WHERE material_code='".$matCode."'";
                if ($plantId !== '') {
                    $sqlSb .= " AND plant_id='".$plantId."'";
                }
                $sqlSb .= " ORDER BY id DESC";
                $resSb = $conn->query($sqlSb);
                if ($resSb && $resSb->num_rows > 0) {
                    while ($sb = $resSb->fetch_assoc()) {
                        $hasRows = true;
                        $arNo = $conn->real_escape_string(isset($sb['ar_no']) ? $sb['ar_no'] : '');
                        $dispensingQty = 0;
                        $samplingQty = 0;
                        if ($arNo !== '') {
                            $resDis = $conn->query("SELECT IFNULL(SUM(qty), 0) as m_qty FROM material_issue WHERE material_code='".$matCode."' AND ar_no='".$arNo."'");
                            if ($resDis && ($d = $resDis->fetch_assoc())) {
                                $dispensingQty = floatval($d['m_qty']);
                            }
                            $resSam = $conn->query("SELECT IFNULL(SUM(qty), 0) as issue_qty FROM material_issue WHERE material_code='".$matCode."' AND ar_no='".$arNo."' AND issue_for='SAMPLING'");
                            if ($resSam && ($s = $resSam->fetch_assoc())) {
                                $samplingQty = floatval($s['issue_qty']);
                            }
                        }
                        $received = floatval(isset($sb['qty']) ? $sb['qty'] : 0);
                        $balance = $received - $dispensingQty - $samplingQty;
                        if ($balance < 0) {
                            $balance = 0;
                        }
                        $grnDate = isset($sb['grn_date']) && $sb['grn_date'] ? $sb['grn_date'] : (isset($sb['entry_date']) ? $sb['entry_date'] : '');
                        $html .= '<tr>
                            <td style="width:5%;">'.$sr++.'</td>
                            <td style="width:14%;">'.$esc(isset($sb['grn_no']) ? $sb['grn_no'] : '').'</td>
                            <td style="width:10%;">'.$fmtDate($grnDate).'</td>
                            <td style="width:12%;">'.$esc(isset($sb['batch_no']) && $sb['batch_no'] !== '' ? $sb['batch_no'] : (isset($sb['ar_no']) ? $sb['ar_no'] : '')).'</td>
                            <td style="width:12%;">'.$fmtQty($received).' '.$esc($unit).'</td>
                            <td style="width:12%;">'.$fmtQty($samplingQty).' '.$esc($unit).'</td>
                            <td style="width:12%;">'.$fmtQty($dispensingQty).' '.$esc($unit).'</td>
                            <td style="width:11%;">'.$fmtQty($balance).' '.$esc($unit).'</td>
                        </tr>';
                    }
                }
            }
            if (!$hasRows) {
                $html .= '<tr><td colspan="8" style="text-align:center;">No Material Receiving Notes records found.</td></tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Output('Material Receiving Notes.pdf', 'I');
        }
            }
        
    }
    
 }catch (\Throwable $e) {
               echo "{\"statuse\":\"".$e."\"}";
             //	echo "{\"status\":\"exception\"}";
            }


// else{
//     echo 'no record';
// }
$conn->close();
?>