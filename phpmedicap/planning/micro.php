<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Origin: *"); // Allow all domains (or replace * with specific domain)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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
     if ($_GET["type"] == "getPlansForMicro") {
         
         
           $output=Array();
           
          $sql = "SELECT 
                  a.product_code,
                  a.product_name,
                  COALESCE(SUM(CASE WHEN a.month = 'January' THEN a.oder_qty ELSE 0 END), 0) AS JanuaryQty,
                  COALESCE(SUM(CASE WHEN a.month = 'February' THEN a.oder_qty ELSE 0 END), 0) AS FebruaryQty,
                  COALESCE(SUM(CASE WHEN a.month = 'March' THEN a.oder_qty ELSE 0 END), 0) AS MarchQty,
                  COALESCE(SUM(CASE WHEN a.month = 'April' THEN a.oder_qty ELSE 0 END), 0) AS AprilQty,
                  COALESCE(SUM(CASE WHEN a.month = 'May' THEN a.oder_qty ELSE 0 END), 0) AS MayQty,
                  COALESCE(SUM(CASE WHEN a.month = 'June' THEN a.oder_qty ELSE 0 END), 0) AS JuneQty,
                  COALESCE(SUM(CASE WHEN a.month = 'July' THEN a.oder_qty ELSE 0 END), 0) AS JulyQty,
                  COALESCE(SUM(CASE WHEN a.month = 'August' THEN a.oder_qty ELSE 0 END), 0) AS AugustQty,
                  COALESCE(SUM(CASE WHEN a.month = 'September' THEN a.oder_qty ELSE 0 END), 0) AS SeptemberQty,
                  COALESCE(SUM(CASE WHEN a.month = 'October' THEN a.oder_qty ELSE 0 END), 0) AS OctoberQty,
                  COALESCE(SUM(CASE WHEN a.month = 'November' THEN a.oder_qty ELSE 0 END), 0) AS NovemberQty,
                  COALESCE(SUM(CASE WHEN a.month = 'December' THEN a.oder_qty ELSE 0 END), 0) AS DecemberQty
                FROM split_planning_qty a where year='".$_GET['year']."'
                GROUP BY a.product_code, a.product_name
                ORDER BY a.product_code;";
        //   $sql = "select  * from split_planning_qty where month='".$_GET['month']."' and year='".$_GET['year']."'";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $row["raw_materials"] = json_decode($row["raw_materials"]);
                $output[] = $row;
            }
        }
        echo json_encode($output);
     
     }
     if ($_GET["type"] == "getPlansForMicroMonthQty") {
         
         
           $output=Array();
           
        
          $sql = "select  * from split_planning_qty where month='".$_GET['month']."' and year='".$_GET['year']."' and product_code='".$_GET['product_code']."'";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
//                  SELECT a.*, ir.indend_no,ir.approve_date as indend_date,p.po_no,p.approve_date as po_date,p.approve_date as actual_del_date

// FROM mrp_raised_indnd_qty  a left join indend_raw ir on a.indend_id=ir.id and a.material_code=ir.material_code

// left join purchaseorder p on ir.indend_no=p.indent_no

// left join challan c on c.po_no=p.po_no

// GROUP BY a.material_code,a.id,indend_no,indend_date,p.approve_date  ,p.po_no,actual_del_date
// and a.month='".$_GET['month']."' and a.year='".$_GET['year']."'
                        $sql1 = "SELECT b.*,a.ordered_qty,p.purchase_type,p.approve_date as po_approve_date,c.approve_date as recevingDate, ir.indend_no,ir.approve_date as indend_date,p.po_no,p.approve_date as po_date,p.approve_date as actual_del_date,(select material_name from material where material_code=b.material_code limit 1) as material_name,
                        a.appxDelivery,a.appxPurchase,a.apprxIndend,a.responsiblePerson
                        FROM batch_planning bp left join batch_planning_materials b  on bp.id=b.batch_plan_id left join
                        mrp_raised_indnd_qty a on b.material_code=a.material_code and   a.month='".$_GET['month']."' and a.year='".$_GET['year']."'  left join indend_raw ir on a.indend_id=ir.id and a.material_code=ir.material_code
                        left join purchaseorder p on ir.indend_no=p.indent_no
                        left join challan c on c.po_no=p.po_no
                        WHERE bp.id='".$row["batch_plan_id"]."' and b.material_type='Raw Material'  ";
                        
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                $output1[] = $row1;
                            }
                        }
                        $row["RawMaterials"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
     
     }
     if ($_GET["type"] == "ReceivingForcastQty") {
         
         
           $output=Array();
            
        
          $sql = "SELECT  a.ordered_qty,p.purchase_type,p.approve_date as po_approve_date,c.approve_date as recevingDate, ir.indend_no,ir.approve_date as indend_date,
          p.po_no,p.approve_date as po_date,p.approve_date as actual_del_date,(select material_name from material where material_code=a.material_code limit 1) as material_name,
                        a.appxDelivery,a.appxPurchase,a.apprxIndend,a.responsiblePerson,m.Purchase_prepare_date,m.PurchaseDeliveryTime,m.material_type,m.material_code,
                        (select workorder_no from WO_deductions where id=a.WO_deductions_id limit 1) as workorder_no
                        FROM 
                        mrp_raised_indnd_qty a    left join indend_raw ir on a.indend_id=ir.id and a.material_code=ir.material_code
                        left join purchaseorder p on ir.indend_no=p.indent_no
                        left join challan c on c.po_no=p.po_no left join material m on a.material_code=m.material_code";
        
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                 $output1 = Array();
                            
                            
                            $date = new DateTime($row['apprxIndend']);
                            // Add days
                            $date->modify("+{$row['Purchase_prepare_date']} days");
                            // Store back in array as formatted date
                            $row['forcastPurchase'] = $date->format("Y-m-d");
                            
                            
                            
                            $date1 = new DateTime($row['forcastPurchase']);
                            // Add days
                            $date1->modify("+{$row['PurchaseDeliveryTime']} days");
                            // Store back in array as formatted date
                            $row['DeliveyDate'] = $date1->format("Y-m-d");
                          
                          
                          
                            $date2 = new DateTime($row['DeliveyDate']);
                            // Add days
                            $date2->modify("+{$row['Sampling_prepare_date']} days");
                            // Store back in array as formatted date
                            $row['samplingDate'] = $date2->format("Y-m-d");
                          
                          
                          
                            $date3 = new DateTime($row['releaseDate']);
                            // Add days
                            $date3->modify("+{$row['release_prepare_date']} days");
                            // Store back in array as formatted date
                            $row['releaseDate'] = $date3->format("Y-m-d");
                            
                            
                $output[] = $row;
            }
        }
        //  header('Content-Type: application/json');
        echo json_encode($output);
     
     }
    
    }

$conn->close();
?>