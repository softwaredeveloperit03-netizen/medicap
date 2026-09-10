 <?php
require '../../db.php';
require '../../tcpdf/tcpdf.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

date_default_timezone_set("Asia/Kolkata");

try {
    $timestamp = time();
    $entry_date = date("Y-m-d H:i:s", $timestamp);

    // Capture input data
    $input = json_decode(file_get_contents('php://input'), true);

    // Sanitize inputs
    $plantId = $_GET["plant_id"] ?? null;
    $userNo = $_GET["user_no"] ?? null;
    $type = $_GET["type"] ?? null;

    // Log request data
    $txt = json_encode([
        "process" => "FRONTEND",
        "action" => $type,
        "actiontime" => $entry_date,
        "method" => $_SERVER['REQUEST_METHOD'],
        "REMOTE_ADDR" => $_SERVER['REMOTE_ADDR']
    ]);
    file_put_contents('../../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    if ($type == "getAllPendingPO") {
        $output = [];

        $stmt = $conn->prepare("SELECT DISTINCT p.id, p.*, v.vendor_name, v.address, v.gst_no, v.city, v.state_code, v.state_name 
            FROM purchaseorder p 
            LEFT JOIN vendor v ON p.vendor_no=v.vendor_no
            LEFT JOIN state s ON v.state_code=s.state_code 
            WHERE p.plant_id=? AND p.user_no=? AND p.status='Pending' 
            AND p.po_type NOT IN ('Packing Material', 'Raw Material') 
            ORDER BY p.id DESC");
        $stmt->bind_param("ss", $plantId, $userNo);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $output1 = [];

            $stmt1 = $conn->prepare("SELECT DISTINCT p.id, p.*, m.grade as graName, m.material_subtype, m.material_name, m.material_type 
                FROM po_material p 
                LEFT JOIN my_view m ON p.material_code=m.material_code 
                LEFT JOIN general_material g ON p.material_code=g.material_code 
                WHERE p.po_no=? AND (p.po_indend='Approve' OR p.material_status='pending')");
            $stmt1->bind_param("i", $row["id"]);
            $stmt1->execute();
            $result1 = $stmt1->get_result();

            while ($row1 = $result1->fetch_assoc()) {
                $row1["material_category"] = $row1["material_subtype"];

                $stmt2 = $conn->prepare("SELECT GROUP_CONCAT(grade) AS gradeName FROM grade WHERE id IN (?)");
                $stmt2->bind_param("s", $row1['id']);
                $stmt2->execute();
                $resQ = $stmt2->get_result();
                $prodLatest = $resQ->fetch_assoc();

                $row1['gradeName'] = $prodLatest['gradeName'] ?? "";
                $output1[] = $row1;
            }

            $row["materials"] = $output1;
            $row["schedule_data"] = json_decode($row["schedule_data"]);
            $row["terms_conditions"] = json_decode($row["terms_conditions"]);
            $row["additional_term"] = json_decode($row["additional_term"]);

            $output[] = $row;
        }

        echo json_encode($output);
        
        
    } else if ($type == "rejectPO") {
        
    $id = $_GET['id'];
    $remark = $_GET['remark'];
    
    // Ensure ID is received
    if(empty($id)) {
        echo json_encode(["status" => "error", "message" => "Missing ID"]);
        exit;
    }

    $query = "UPDATE purchase_orders SET status = 'rejected', remark = '$remark' WHERE id = '$id'";
    
    if(mysqli_query($conn, $query)) {
        echo json_encode(["status" => "success", "message" => "PO Rejected"]);
    } else {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    }
} 



//   else if ($_GET["type"] == "approvePO"){
//         $final_total = floatval($_GET["shipping_handling"]) + floatval($_GET["other_charges"]);
//         $transport = $_GET["dispatch_through"];

//           $sql = "UPDATE purchaseorder SET status='" . $_GET["status"] . "',remark='" . $_GET["remark"] . "', 
//         transport='" . $_GET["dispatch_through"] . "',shipping_handling='" . $_GET["shipping_handling"] . "',other_charges='" . $_GET["other_charges"] . "', 
//         approve_by='" . $_GET["emp_id"] . "', approve_date='" . $entry_date . "' WHERE id='" . $_GET["id"] . "'";
      
//         $conn->query($sql);
        
//         //$sql="update purchaseorder set final_total = (gross_total-discount)+(rounding+gst_total+shipping_handling+other_charges) WHERE id='" . $_GET["id"] . "'";
//         $sql="update purchaseorder set final_total = final_total+(shipping_handling+other_charges) WHERE id='" . $_GET["id"] . "'";

        

//         if ($conn->query($sql))
//         {
//             echo "{\"status\":\"success\"}";
            
//         }
//         else
//         {
//             echo "{\"status\":\"" . $conn->error . "\"}";
//         }
//     }
    
      else if ($_GET["type"] == "approvePO") {
           $sql = "UPDATE purchaseorder SET status='".$_GET["status"]."', approve_by='".$_GET["emp_id"]."', approve_date='$entry_date' WHERE id='".$_GET["id"]."'";
           if ($conn->query($sql)) {
           echo "{\"status\":\"success\"}";
            } else {
           echo "{\"status\":\"".$conn->error."\"}";
           }
        }
       

else {
        echo json_encode(["status" => "invalid action"]);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(["status" => "error", "message" => "Internal server error"]);
}

$conn->close();
