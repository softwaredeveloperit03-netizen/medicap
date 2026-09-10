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

    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


    if($_GET["type"]=="getManpowerAllocation") {
        
         // Get manpower allocation for a specific work order
        $work_order_no = $_GET['work_order_no'] ?? '';
        
        if (empty($work_order_no)) {
            echo json_encode(['status' => 'error', 'message' => 'Work order number is required']);
            exit;
        }
        
        $sql = "SELECT 
                    ma.id,
                    ma.work_order_no,
                    ma.emp_id,
                    COALESCE(e.emp_name, CONCAT(e.firstname, ' ', e.lastname)) as emp_name,
                    ma.role,
                    ma.remarks,
                    ma.allocated_date,
                    ma.allocated_by,
                    COALESCE(allocator.emp_name, CONCAT(allocator.firstname, ' ', allocator.lastname)) as allocated_by_name
                FROM mfg_manpower_allocation ma
                LEFT JOIN employee e ON ma.emp_id = e.emp_id AND ma.plant_id = e.plant_id
                LEFT JOIN employee allocator ON ma.allocated_by = allocator.emp_id AND ma.plant_id = allocator.plant_id
                WHERE ma.work_order_no = ? 
                    AND ma.plant_id = ?
                    AND ma.status = 'Active'
                ORDER BY ma.allocated_date DESC";
        
        $stmt = $conn->prepare($sql);
        $plant_id = $_GET['plant_id'] ?? '181'; // Default plant_id, should come from session
        $stmt->bind_param("ss", $work_order_no, $plant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $output = [];
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        
        echo json_encode($output);
    } 
    if($_GET["type"]=="addManpowerAllocation") {
        
           // Add new manpower allocation
        $input = json_decode(file_get_contents('php://input'), true);
        
        $work_order_no = $input['work_order_no'] ?? '';
        $emp_id = $input['emp_id'] ?? '';
        $emp_name = $input['emp_name'] ?? '';
        $role = $input['role'] ?? '';
        $remarks = $input['remarks'] ?? '';
        $allocated_by = $_GET['emp_id'] ?? ''; // Should come from session
        $plant_id = $_GET['plant_id'] ?? '181'; // Should come from session
        
        if (empty($work_order_no) || empty($emp_id) || empty($role)) {
            echo json_encode(['status' => 'error', 'message' => 'Work order number, employee ID, and role are required']);
            exit;
        }
        
        // Check if employee is already allocated to this work order
        $check_sql = "SELECT id FROM mfg_manpower_allocation 
                      WHERE work_order_no = ? 
                      AND emp_id = ? 
                      AND plant_id = ? 
                      AND status = 'Active'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("sss", $work_order_no, $emp_id, $plant_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Employee is already allocated to this work order']);
            exit;
        }
        
        // Insert new allocation
        $insert_sql = "INSERT INTO mfg_manpower_allocation 
                      (work_order_no, emp_id, emp_name, role, remarks, allocated_by, allocated_date, plant_id, status) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, 'Active')";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssssss", $work_order_no, $emp_id, $emp_name, $role, $remarks, $allocated_by, $plant_id);
        
        if ($insert_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Manpower allocated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to allocate manpower: ' . $conn->error]);
        }
    } 
    if($_GET["type"]=="removeManpowerAllocation") {
        
            // Remove (deactivate) manpower allocation
        $id = $_GET['id'] ?? '';
        $plant_id = $_GET['plant_id'] ?? '181'; // Should come from session
        
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'Allocation ID is required']);
            exit;
        }
        
        // Update status to 'Inactive' instead of deleting
        $update_sql = "UPDATE mfg_manpower_allocation 
                      SET status = 'Inactive', 
                          removed_date = NOW(),
                          removed_by = ?
                      WHERE id = ? AND plant_id = ?";
        
        $removed_by = $_GET['emp_id'] ?? ''; // Should come from session
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sss", $removed_by, $id, $plant_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Manpower allocation removed successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove allocation: ' . $conn->error]);
        }
    }
    
}

$conn->close();
?>