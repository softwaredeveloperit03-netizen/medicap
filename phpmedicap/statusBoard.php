<?php
include 'statusDB.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Add proper headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$action = $_GET['action'] ?? '';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Plan Type Definitions
class PlanType {
    const BASIC = 'basic';
    const DEVELOPMENT = 'development';
    const TESTING = 'testing';
    const TRAINING = 'training';
    const IMPLEMENTATION = 'implementation';
    const TRIAL = 'trial';

}

// Plan Type Configuration
class PlanTypeConfig {
    public static function getFields($type) {
        $fieldConfig = [
            PlanType::BASIC => [
                'required' => ['client_id', 'phase', 'department', 'module', 'sub_module', 'form_name'],
                'optional' => ['created_by'],
                'table_fields' => ['client_id', 'phase', 'department', 'module', 'sub_module', 'form_name', 'created_by']
            ],
            PlanType::DEVELOPMENT => [
            'required' => ['client_id', 'phase', 'department', 'module', 'sub_module', 'form_name'],
            'optional' => [
                'development_timeline', 'development_status', 'development_remark', 
                'developer_name', 'completed_on', 'deviation_days', 
                'new_forecast_date', 'deviation_reason', 'created_by'
            ],
            'table_fields' => [
                'client_id', 'phase', 'department', 'module', 'sub_module', 'form_name', 
                'development_timeline', 'development_status', 'development_remark', 
                'developer_name', 'completed_on', 'deviation_days', 
                'new_forecast_date', 'deviation_reason', 'created_by'
            ]
        ],
            PlanType::TESTING => [
            'required' => ['client_id', 'phase', 'department', 'module', 'sub_module', 'form_name'],
            'optional' => ['development_timeline', 'development_status', 'development_remark', 'developer_name', 'completed_on', 'testing_timeline', 'testing_status', 'test_completed_on', 'testing_remark','tester_name', 'created_by'],
            'table_fields' => ['client_id', 'phase', 'department', 'module', 'sub_module', 'form_name', 'development_timeline', 'development_status', 'development_remark', 'developer_name', 'completed_on', 'testing_timeline', 'testing_status', 'test_completed_on', 'testing_remark', 'tester_name', 'created_by']
            ],
           PlanType::TRAINING => [
                'required' => ['client_id', 'phase', 'department', 'module', 'sub_module', 'form_name'],
                'optional' => [
                'development_timeline', 'development_status', 'development_remark', 'developer_name', 'completed_on', 'deviation_days', 'deviation_reason',
                'testing_timeline', 'testing_status', 'test_completed_on', 'testing_remark', 'testing_deviation_days', 'testing_deviation_reason',
                'training_timeline', 'current_training_projected_date', 'trainer_name', 'training_status', 'training_completed_on', 
                'training_remark', 'training_deviation_days', 'training_deviation_reason', 'created_by'
            ],
                'table_fields' => [
                'client_id', 'phase', 'department', 'module', 'sub_module', 'form_name',
                'development_timeline', 'development_status', 'development_remark', 'developer_name', 'completed_on', 'deviation_days', 'deviation_reason',
                'testing_timeline', 'testing_status', 'test_completed_on', 'testing_remark', 'testing_deviation_days', 'testing_deviation_reason',
                'training_timeline', 'current_training_projected_date', 'trainer_name', 'training_status', 'training_completed_on', 
                'training_remark', 'training_deviation_days', 'training_deviation_reason', 'created_by'
                ]
            ],
            PlanType::TRIAL => [
                'required' => ['client_id', 'phase', 'department', 'module', 'sub_module', 'form_name'],
                'optional' => [
                    'development_timeline', 'development_status', 'development_remark', 'developer_name', 'completed_on', 'deviation_days', 'deviation_reason',
                    'testing_timeline', 'testing_status', 'test_completed_on', 'testing_remark', 'testing_deviation_days', 'testing_deviation_reason',
                    'training_timeline', 'training_status', 'training_completed_on', 'training_remark', 'training_deviation_days', 'training_deviation_reason',
                    'trial_timeline', 'current_trial_projected_date', 'trialer_name', 'trial_status', 'trial_completed_on', 
                    'trial_remark', 'trial_deviation_days', 'trial_deviation_reason', 'created_by'
                ],
                'table_fields' => [
                    'client_id', 'phase', 'department', 'module', 'sub_module', 'form_name',
                    'development_timeline', 'development_status', 'development_remark', 'developer_name', 'completed_on', 'deviation_days', 'deviation_reason',
                    'testing_timeline', 'testing_status', 'test_completed_on', 'testing_remark', 'testing_deviation_days', 'testing_deviation_reason',
                    'training_timeline', 'training_status', 'training_completed_on', 'training_remark', 'training_deviation_days', 'training_deviation_reason',
                    'trial_timeline', 'current_trial_projected_date', 'trialer_name', 'trial_status', 'trial_completed_on', 
                    'trial_remark', 'trial_deviation_days', 'trial_deviation_reason', 'created_by'
                ]
            ],
            PlanType::IMPLEMENTATION => [
                'required' => ['client_id', 'phase', 'department', 'module', 'sub_module', 'form_name'],
                'optional' => [
                    // Development fields
                    'development_timeline', 'development_status', 'development_remark', 'developer_name', 'completed_on', 
                    'deviation_days', 'deviation_reason',
                    
                    // Testing fields  
                    'testing_timeline', 'testing_status', 'testing_remark', 'tester_name', 'test_completed_on',
                    'testing_deviation_days', 'testing_deviation_reason',
                    
                    // Training fields
                    'training_timeline', 'training_status', 'training_remark', 'trainer_name', 'training_completed_on',
                    'training_deviation_days', 'training_deviation_reason',
                    
                    // Implementation fields
                    'implementation_timeline', 'current_projected_date', 'implementer_name', 'implementation_status', 
                    'implementation_completed_on', 'implementation_deviation_days', 'implementation_deviation_reason', 
                    'implementation_remark', 'created_by'
                ],
                'table_fields' => [
                    'client_id', 'phase', 'department', 'module', 'sub_module', 'form_name',
                    
                    // Development fields
                    'development_timeline', 'development_status', 'development_remark', 'developer_name', 'completed_on',
                    'deviation_days', 'deviation_reason',
                    
                    // Testing fields
                    'testing_timeline', 'testing_status', 'testing_remark', 'tester_name', 'test_completed_on',
                    'testing_deviation_days', 'testing_deviation_reason',
                    
                    // Training fields
                    'training_timeline', 'training_status', 'training_remark', 'trainer_name', 'training_completed_on',
                    'training_deviation_days', 'training_deviation_reason',
                    
                    // Implementation fields
                    'implementation_timeline', 'current_projected_date', 'implementer_name', 'implementation_status',
                    'implementation_completed_on', 'implementation_deviation_days', 'implementation_deviation_reason',
                    'implementation_remark', 'created_by'
                ]
            ]
        ];
        
        return $fieldConfig[$type] ?? $fieldConfig[PlanType::BASIC];
    }
    
    public static function getDefaultValues($type) {
        $defaults = [
            PlanType::BASIC => [
                'created_by' => 1
            ],
            PlanType::DEVELOPMENT => [
                'development_status' => 'not-started',
                'created_by' => 1
            ],
            PlanType::TESTING => [
                'development_status' => 'not-started',
                'testing_status' => 'not-started',
                'created_by' => 1
            ],
            PlanType::TRAINING => [
                'development_status' => 'not-started',
                'testing_status' => 'not-started',
                'training_status' => 'not-started',
                'created_by' => 1
            ],
            PlanType::IMPLEMENTATION => [
                'development_status' => 'not-started',
                'testing_status' => 'not-started',
                'training_status' => 'not-started',
                'implementation_status' => 'not-started',
                'created_by' => 1
            ],
            PlanType::TRIAL => [
                'development_status' => 'not-started',
                'testing_status' => 'not-started',
                'training_status' => 'not-started',
                'trial_status' => 'not-started',
                'created_by' => 1
            ],

        ];
        
        return $defaults[$type] ?? $defaults[PlanType::BASIC];
    }
    
    public static function detectType($data) {
    if (isset($data['implementation_timeline']) || isset($data['implementation_status']) || isset($data['implementation_remark'])) {
        return PlanType::IMPLEMENTATION;
    }
    if (isset($data['training_timeline']) || isset($data['training_status']) || isset($data['training_remark'])) {
        return PlanType::TRAINING;
    }
    if (isset($data['trial_timeline']) || isset($data['trial_status']) || isset($data['trial_remark'])) {
    return PlanType::TRIAL;
    }
    if (isset($data['testing_timeline']) || isset($data['testing_status']) || isset($data['testing_remark'])) {
        return PlanType::TESTING;
    }
    if (isset($data['development_timeline']) || isset($data['development_status']) || isset($data['development_remark'])) {
        return PlanType::DEVELOPMENT;
    }
    return PlanType::BASIC;
}
}

switch($action) {
    case 'getPlans':
        getPlans($conn);
        break;
        
    case 'getPlan':
        if(isset($_GET['id'])) {
            getPlan($conn, $_GET['id']);
        }
        break;
        
    case 'getPlansByClient':
        if(isset($_GET['client_id'])) {
            getPlansByClient($conn, $_GET['client_id']);
        }
        break;
        
    case 'getTestingPlans':
        getTestingPlans($conn);
        break;
        
    case 'getTestingPlansByClient':
        if(isset($_GET['client_id'])) {
            getTestingPlansByClient($conn, $_GET['client_id']);
        }
        break;
        
    case 'getTrainingPlans':
        getTrainingPlans($conn);
        break;
        
    case 'getTrainingPlansByClient':
        if(isset($_GET['client_id'])) {
            getTrainingPlansByClient($conn, $_GET['client_id']);
        }
        break;
        
    case 'createPlan':
        createPlan($conn);
        break;
        
    case 'updatePlan':
        if(isset($_GET['id'])) {
            updatePlan($conn, $_GET['id']);
        }
        break;
        
    case 'updateTestingStatus':
        if(isset($_GET['id'])) {
            updateTestingStatus($conn, $_GET['id']);
        }
        break;
        
    case 'updateTrainingStatus':
        if(isset($_GET['id'])) {
            updateTrainingStatus($conn, $_GET['id']);
        }
        break;
        
    case 'deletePlan':
        if(isset($_GET['id'])) {
            deletePlan($conn, $_GET['id']);
        }
        break;
        
    case 'getClients':
        getClients($conn);
        break;
        
    case 'getPlanTypes':
        getPlanTypes();
        break;
        
    case 'getTestingStatusOptions':
        getTestingStatusOptions();
        break;
        
    case 'getTrainingStatusOptions':
        getTrainingStatusOptions();
        break;
        
    case 'getDevelopmentStatusOptions':
        getDevelopmentStatusOptions();
        break;
        
    case 'getDateHistory':
    if(isset($_GET['plan_id'])) {
        getDateHistory($conn, $_GET['plan_id']);
    }
    break;

    case 'updateProjectedDate':
        if(isset($_GET['id'])) {
            updateProjectedDate($conn, $_GET['id']);
        }
        break;
        
    case 'getTestingDateHistory':
        if(isset($_GET['plan_id'])) {
            getTestingDateHistory($conn, $_GET['plan_id']);
        }
        break;
        
    case 'getTrainingDateHistory':
    if(isset($_GET['plan_id'])) {
        getTrainingDateHistory($conn, $_GET['plan_id']);
    }
    break;
    
    case 'updateTrainingProjectedDate':
    if(isset($_GET['id'])) {
        updateTrainingProjectedDate($conn, $_GET['id']);
    }
    break;
    
    case 'updateTestingProjectedDate':
        if(isset($_GET['id'])) {
            updateTestingProjectedDate($conn, $_GET['id']);
        }
        break;
    case 'getTrialPlans':
    getTrialPlans($conn);
    break;
    
    case 'getTrialPlansByClient':
        if(isset($_GET['client_id'])) {
            getTrialPlansByClient($conn, $_GET['client_id']);
        }
        break;
        
    case 'updateTrialStatus':
        if(isset($_GET['id'])) {
            updateTrialStatus($conn, $_GET['id']);
        }
        break;
        
    case 'getTrialDateHistory':
        if(isset($_GET['plan_id'])) {
            getTrialDateHistory($conn, $_GET['plan_id']);
        }
        break;
    
    case 'updateTrialProjectedDate':
        if(isset($_GET['id'])) {
            updateTrialProjectedDate($conn, $_GET['id']);
        }
        break;
    case 'getImplementationDateHistory':
        if(isset($_GET['plan_id'])) {
            getImplementationDateHistory($conn, $_GET['plan_id']);
        }
    break;
    
    case 'updateImplementationProjectedDate':
        if(isset($_GET['id'])) {
            updateImplementationProjectedDate($conn, $_GET['id']);
        }
    break;

    case 'getDevelopers':
    getDevelopers($conn);
    break;
    
    case 'getTesters':
    getTesters($conn);
    break;
    
    case 'getTrainers':
    getTrainers($conn);
    break;
    
    case 'getTrialers':
    getTrialers($conn);
    break;
    
    case 'getImplementers':
    getImplementers($conn);
    break;
    
    default:
        echo json_encode(array("success" => false, "message" => "Invalid action."));
        break;
    
}

function getDevelopers($conn) {
    $query = "SELECT emp_id, name FROM statusBoardUser WHERE userType = 'Developer' AND status = 'active' ORDER BY name";
    
    $result = $conn->query($query);
    
    $developers = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $developers[] = array(
                'emp_id' => $row['emp_id'],
                'name' => $row['name'],
                'display_text' => $row['name'] . ' (' . $row['emp_id'] . ')'
            );
        }
    }
    
    echo json_encode(array("success" => true, "data" => $developers));
}

function getTesters ($conn) {
    $query = "SELECT emp_id, name FROM statusBoardUser WHERE userType LIKE '%Tester%' AND status = 'active' ORDER BY name";
    
    $result = $conn->query($query);
    
    $testers = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $testers[] = array(
                'emp_id' => $row['emp_id'],
                'name' => $row['name'],
                'display_text' => $row['name'] . ' (' . $row['emp_id'] . ')'
            );
        }
    }
    
    echo json_encode(array("success" => true, "data" => $testers));
}


function getTrainers($conn) {
    $query = "SELECT emp_id, name FROM statusBoardUser WHERE userType LIKE '%Trainer%' AND status = 'active' ORDER BY name";
    
    $result = $conn->query($query);
    
    $trainers = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $trainers[] = array(
                'emp_id' => $row['emp_id'],
                'name' => $row['name'],
                'display_text' => $row['name'] . ' (' . $row['emp_id'] . ')'
            );
        }
    }
    
    echo json_encode(array("success" => true, "data" => $trainers));
}

function getTrialers($conn) {
    $query = "SELECT emp_id, name FROM statusBoardUser WHERE userType LIKE '%Trialer%' AND status = 'active' ORDER BY name";
    
    $result = $conn->query($query);
    
    $trialers = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $trialers[] = array(
                'emp_id' => $row['emp_id'],
                'name' => $row['name'],
                'display_text' => $row['name'] . ' (' . $row['emp_id'] . ')'
            );
        }
    }
    
    echo json_encode(array("success" => true, "data" => $trialers));
}

function getImplementers($conn) {
    $query = "SELECT emp_id, name FROM statusBoardUser WHERE userType LIKE '%Implementer%' AND status = 'active' ORDER BY name";
    
    $result = $conn->query($query);
    
    $implementers = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $implementers[] = array(
                'emp_id' => $row['emp_id'],
                'name' => $row['name'],
                'display_text' => $row['name'] . ' (' . $row['emp_id'] . ')'
            );
        }
    }
    
    echo json_encode(array("success" => true, "data" => $implementers));
}

function getPlans($conn) {
    // Force cyclone Pharmaceuticals (client_id=159)
    $client_id = 159;
    
    $query = "SELECT apl.*, cm.client_name 
              FROM add_plan_list apl 
              LEFT JOIN client_master cm ON apl.client_id = cm.id 
              WHERE apl.client_id = ? 
              ORDER BY apl.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $plans = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
    }
    
    $stmt->close();
    
    echo json_encode(array("success" => true, "data" => $plans));
}

function getTestingPlans($conn) {
    $query = "SELECT apl.*, cm.client_name 
              FROM add_plan_list apl 
              LEFT JOIN client_master cm ON apl.client_id = cm.id 
              WHERE apl.testing_status IS NOT NULL 
              ORDER BY apl.created_at DESC";
    
    $result = $conn->query($query);
    
    $plans = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $plans));
}

function getTrainingPlans($conn) {
    $query = "SELECT apl.*, cm.client_name 
              FROM add_plan_list apl 
              LEFT JOIN client_master cm ON apl.client_id = cm.id 
              WHERE apl.training_status IS NOT NULL 
              ORDER BY apl.created_at DESC";
    
    $result = $conn->query($query);
    
    $plans = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $plans));
}

function getPlan($conn, $id) {
    $query = "SELECT apl.*, cm.client_name 
              FROM add_plan_list apl 
              LEFT JOIN client_master cm ON apl.client_id = cm.id 
              WHERE apl.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $plan = $result->fetch_assoc();
        echo json_encode(array("success" => true, "data" => $plan));
    } else {
        echo json_encode(array("success" => false, "message" => "Plan not found."));
    }
    
    $stmt->close();
}

function getPlansByClient($conn, $client_id) {
    $query = "SELECT apl.*, cm.client_name 
              FROM add_plan_list apl 
              LEFT JOIN client_master cm ON apl.client_id = cm.id 
              WHERE apl.client_id = ? 
              ORDER BY apl.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $plans = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $plans));
    $stmt->close();
}

function getTestingPlansByClient($conn, $client_id) {
    $query = "SELECT apl.*, cm.client_name 
              FROM add_plan_list apl 
              LEFT JOIN client_master cm ON apl.client_id = cm.id 
              WHERE apl.client_id = ? AND apl.testing_status IS NOT NULL
              ORDER BY apl.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $plans = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $plans));
    $stmt->close();
}

function getTrainingPlansByClient($conn, $client_id) {
    $query = "SELECT apl.*, cm.client_name 
              FROM add_plan_list apl 
              LEFT JOIN client_master cm ON apl.client_id = cm.id 
              WHERE apl.client_id = ? AND apl.training_status IS NOT NULL
              ORDER BY apl.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $plans = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $plans));
    $stmt->close();
}

function getClients($conn) {
    $query = "SELECT id, client_name FROM client_master ORDER BY client_name";
    
    $result = $conn->query($query);
    
    $clients = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $clients));
}

function getPlanTypes() {
    $types = [
        [
            'id' => PlanType::BASIC,
            'name' => 'Basic Plan',
            'description' => 'Standard plan with basic fields',
            'fields' => PlanTypeConfig::getFields(PlanType::BASIC)
        ],
        [
            'id' => PlanType::DEVELOPMENT,
            'name' => 'Development Plan',
            'description' => 'Plan with development tracking fields',
            'fields' => PlanTypeConfig::getFields(PlanType::DEVELOPMENT)
        ],
        [
            'id' => PlanType::TESTING,
            'name' => 'Testing Plan',
            'description' => 'Plan with development and testing tracking fields',
            'fields' => PlanTypeConfig::getFields(PlanType::TESTING)
        ],
        [
            'id' => PlanType::TRAINING,
            'name' => 'Training Plan',
            'description' => 'Plan with development, testing and training tracking fields',
            'fields' => PlanTypeConfig::getFields(PlanType::TRAINING)
        ],
        [
            'id' => PlanType::IMPLEMENTATION,
            'name' => 'Implementation Plan',
            'description' => 'Plan with development, testing, training and implementation tracking fields',
            'fields' => PlanTypeConfig::getFields(PlanType::IMPLEMENTATION)
        ]
    ];
    
    echo json_encode(array("success" => true, "data" => $types));
}

function getTestingStatusOptions() {
    $statuses = [
        ['value' => 'not-started', 'label' => 'Not Started'],
        ['value' => 'in-progress', 'label' => 'In Progress'],
        ['value' => 'passed', 'label' => 'Passed'],
        ['value' => 'failed', 'label' => 'Failed'],
        ['value' => 'under-review', 'label' => 'Under Review'],
        ['value' => 'on-hold', 'label' => 'On Hold']
    ];
    
    echo json_encode(array("success" => true, "data" => $statuses));
}

function getTrialStatusOptions() {
    $statuses = [
        ['value' => 'not-started', 'label' => 'Not Started'],
        ['value' => 'scheduled', 'label' => 'Scheduled'],
        ['value' => 'in-progress', 'label' => 'In Progress'],
        ['value' => 'completed', 'label' => 'Completed'],
        ['value' => 'cancelled', 'label' => 'Cancelled'],
        ['value' => 'on-hold', 'label' => 'On Hold'],
        ['value' => 'successful', 'label' => 'Successful'],
        ['value' => 'failed', 'label' => 'Failed']
    ];
    
    echo json_encode(array("success" => true, "data" => $statuses));
}

function getDevelopmentStatusOptions() {
    $statuses = [
        ['value' => 'not-started', 'label' => 'Not Started'],
        ['value' => 'started', 'label' => 'Started'],
        ['value' => 'in-progress', 'label' => 'In Progress'],
        ['value' => 'not-completed', 'label' => 'Not Completed'],
        ['value' => 'completed', 'label' => 'Completed']
    ];
    
    echo json_encode(array("success" => true, "data" => $statuses));
}

function createPlan($conn) {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    error_log("Raw input received: " . $input);
    
    $data = json_decode($input, true);
    
    if(!$data) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data received."));
        return;
    }
    
    // Detect plan type based on provided fields
    $planType = PlanTypeConfig::detectType($data);
    $config = PlanTypeConfig::getFields($planType);
    $defaults = PlanTypeConfig::getDefaultValues($planType);
    
    // Validate required fields
    $missingFields = [];
    foreach ($config['required'] as $field) {
        if (empty($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        echo json_encode(array(
            "success" => false, 
            "message" => "Missing required fields: " . implode(', ', $missingFields),
            "type" => $planType
        ));
        return;
    }
    
    // Prepare data with defaults
    $preparedData = [];
    $fieldNames = [];
    $placeholders = [];
    $paramTypes = '';
    $paramValues = [];
    
    foreach ($config['table_fields'] as $field) {
        $fieldNames[] = $field;
        $placeholders[] = '?';
        
        // Get value from data or use default
        if (isset($data[$field]) && !empty($data[$field])) {
            $value = $data[$field];
        } elseif (isset($defaults[$field])) {
            $value = $defaults[$field];
        } else {
            $value = null;
        }
        
        $preparedData[$field] = $value;
        
        // Handle empty date fields - convert to NULL but store as variable
        if (($field === 'completed_on' || $field === 'development_timeline') && empty($value)) {
            $paramTypes .= 's';
            $nullValue = null;
            $paramValues[] = &$nullValue;
        } else {
            // Determine parameter type
            if (is_int($value)) {
                $paramTypes .= 'i';
            } elseif (is_float($value)) {
                $paramTypes .= 'd';
            } else {
                $paramTypes .= 's';
            }
            $paramValues[] = &$preparedData[$field];
        }
    }
    
    // Build and execute query
    $query = "INSERT INTO add_plan_list (" . implode(', ', $fieldNames) . ") 
              VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $conn->prepare($query);
    
    if(!$stmt) {
        echo json_encode(array("success" => false, "message" => "Prepare failed: " . $conn->error));
        return;
    }
    
    // Bind parameters dynamically
    $bindParams = array_merge([$paramTypes], $paramValues);
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    
    if($stmt->execute()) {
        $last_id = $conn->insert_id;
        echo json_encode(array(
            "success" => true, 
            "message" => "Plan created successfully.", 
            "id" => $last_id,
            "type" => $planType
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Execute failed: " . $stmt->error));
    }
    
    $stmt->close();
}

function updatePlan($conn, $id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if(!$data) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data received."));
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Detect plan type based on provided fields
        $planType = PlanTypeConfig::detectType($data);
        $config = PlanTypeConfig::getFields($planType);
        
        // Build dynamic update query
        $updates = [];
        $paramTypes = '';
        $paramValues = [];
        
        // Check if DEVELOPMENT projected date has changed and needs to be archived
        $devDateChanged = false;
        $newDevProjectedDate = null;
        
        if (isset($data['current_projected_date']) && !empty($data['current_projected_date'])) {
            // Get current development projected date from database to compare
            $currentDateQuery = "SELECT current_projected_date FROM add_plan_list WHERE id = ?";
            $stmtCurrent = $conn->prepare($currentDateQuery);
            $stmtCurrent->bind_param("i", $id);
            $stmtCurrent->execute();
            $stmtCurrent->bind_result($currentProjectedDate);
            $stmtCurrent->fetch();
            $stmtCurrent->close();
            
            // Check if date actually changed
            if ($currentProjectedDate !== $data['current_projected_date']) {
                $devDateChanged = true;
                $newDevProjectedDate = $data['current_projected_date'];
                $dateChangeReason = $data['date_change_reason'] ?? 'Date updated';
                
                // Archive old development date if it exists
                if (!empty($currentProjectedDate)) {
                    $updatePrevious = "UPDATE development_date_history SET is_current = FALSE WHERE plan_id = ? AND is_current = TRUE";
                    $stmtPrev = $conn->prepare($updatePrevious);
                    $stmtPrev->bind_param("i", $id);
                    $stmtPrev->execute();
                    $stmtPrev->close();
                }
                
                // Insert new development date record
                $insertDate = "INSERT INTO development_date_history (plan_id, projected_date, changed_by, reason, is_current) 
                               VALUES (?, ?, ?, ?, TRUE)";
                $stmtIns = $conn->prepare($insertDate);
                $changed_by = $data['changed_by'] ?? 'System';
                $stmtIns->bind_param("isss", $id, $newDevProjectedDate, $changed_by, $dateChangeReason);
                $stmtIns->execute();
                $stmtIns->close();
                
                // Update the main table with the new development projected date
                $updates[] = "current_projected_date = ?";
                $paramTypes .= 's';
                $paramValues[] = &$newDevProjectedDate;
            }
        }
        
        // Check if TESTING projected date has changed and needs to be archived
        $testingDateChanged = false;
        $newTestingProjectedDate = null;
        
        if (isset($data['current_testing_projected_date']) && !empty($data['current_testing_projected_date'])) {
            // Get current testing projected date from database to compare
            $currentTestingDateQuery = "SELECT current_testing_projected_date FROM add_plan_list WHERE id = ?";
            $stmtCurrentTesting = $conn->prepare($currentTestingDateQuery);
            $stmtCurrentTesting->bind_param("i", $id);
            $stmtCurrentTesting->execute();
            $stmtCurrentTesting->bind_result($currentTestingProjectedDate);
            $stmtCurrentTesting->fetch();
            $stmtCurrentTesting->close();
            
            // Check if testing date actually changed
            if ($currentTestingProjectedDate !== $data['current_testing_projected_date']) {
                $testingDateChanged = true;
                $newTestingProjectedDate = $data['current_testing_projected_date'];
                $testingDateChangeReason = $data['date_change_reason'] ?? 'Testing date updated';
                
                // Archive old testing date if it exists
                if (!empty($currentTestingProjectedDate)) {
                    $updatePreviousTesting = "UPDATE testing_date_history SET is_current = FALSE WHERE plan_id = ? AND is_current = TRUE";
                    $stmtPrevTesting = $conn->prepare($updatePreviousTesting);
                    $stmtPrevTesting->bind_param("i", $id);
                    $stmtPrevTesting->execute();
                    $stmtPrevTesting->close();
                }
                
                // Insert new testing date record
                $insertTestingDate = "INSERT INTO testing_date_history (plan_id, projected_date, changed_by, reason, is_current) 
                                      VALUES (?, ?, ?, ?, TRUE)";
                $stmtInsTesting = $conn->prepare($insertTestingDate);
                $changed_by = $data['changed_by'] ?? 'System';
                $stmtInsTesting->bind_param("isss", $id, $newTestingProjectedDate, $changed_by, $testingDateChangeReason);
                $stmtInsTesting->execute();
                $stmtInsTesting->close();
                
                // Update the main table with the new testing projected date
                $updates[] = "current_testing_projected_date = ?";
                $paramTypes .= 's';
                $paramValues[] = &$newTestingProjectedDate;
            }
        }
        
        $trainingDateChanged = false;
        $newTrainingProjectedDate = null;
        
        if (isset($data['current_training_projected_date']) && !empty($data['current_training_projected_date'])) {
            // Get current training projected date from database to compare
            $currentTrainingDateQuery = "SELECT current_training_projected_date FROM add_plan_list WHERE id = ?";
            $stmtCurrentTraining = $conn->prepare($currentTrainingDateQuery);
            $stmtCurrentTraining->bind_param("i", $id);
            $stmtCurrentTraining->execute();
            $stmtCurrentTraining->bind_result($currentTrainingProjectedDate);
            $stmtCurrentTraining->fetch();
            $stmtCurrentTraining->close();
            
            // Check if training date actually changed
            if ($currentTrainingProjectedDate !== $data['current_training_projected_date']) {
                $trainingDateChanged = true;
                $newTrainingProjectedDate = $data['current_training_projected_date'];
                $trainingDateChangeReason = $data['date_change_reason'] ?? 'Training date updated';
                
                // Archive old training date if it exists
                if (!empty($currentTrainingProjectedDate)) {
                    $updatePreviousTraining = "UPDATE training_date_history SET is_current = FALSE WHERE plan_id = ? AND is_current = TRUE";
                    $stmtPrevTraining = $conn->prepare($updatePreviousTraining);
                    $stmtPrevTraining->bind_param("i", $id);
                    $stmtPrevTraining->execute();
                    $stmtPrevTraining->close();
                }
                
                // Insert new training date record
                $insertTrainingDate = "INSERT INTO training_date_history (plan_id, projected_date, changed_by, reason, is_current) 
                                      VALUES (?, ?, ?, ?, TRUE)";
                $stmtInsTraining = $conn->prepare($insertTrainingDate);
                $changed_by = $data['changed_by'] ?? 'System';
                $stmtInsTraining->bind_param("isss", $id, $newTrainingProjectedDate, $changed_by, $trainingDateChangeReason);
                $stmtInsTraining->execute();
                $stmtInsTraining->close();
                
                // Update the main table with the new training projected date
                $updates[] = "current_training_projected_date = ?";
                $paramTypes .= 's';
                $paramValues[] = &$newTrainingProjectedDate;
            }
        }
        
            $trialDateChanged = false;
            $newTrialProjectedDate = null;
            
            if (isset($data['current_trial_projected_date']) && !empty($data['current_trial_projected_date'])) {
                // Get current trial projected date from database to compare
                $currentTrialDateQuery = "SELECT current_trial_projected_date FROM add_plan_list WHERE id = ?";
                $stmtCurrentTrial = $conn->prepare($currentTrialDateQuery);
                $stmtCurrentTrial->bind_param("i", $id);
                $stmtCurrentTrial->execute();
                $stmtCurrentTrial->bind_result($currentTrialProjectedDate);
                $stmtCurrentTrial->fetch();
                $stmtCurrentTrial->close();
                
                // Check if trial date actually changed
                if ($currentTrialProjectedDate !== $data['current_trial_projected_date']) {
                    $trialDateChanged = true;
                    $newTrialProjectedDate = $data['current_trial_projected_date'];
                    $trialDateChangeReason = $data['date_change_reason'] ?? 'Trial date updated';
                    
                    // Archive old trial date if it exists
                    if (!empty($currentTrialProjectedDate)) {
                        $updatePreviousTrial = "UPDATE trial_date_history SET is_current = FALSE WHERE plan_id = ? AND is_current = TRUE";
                        $stmtPrevTrial = $conn->prepare($updatePreviousTrial);
                        $stmtPrevTrial->bind_param("i", $id);
                        $stmtPrevTrial->execute();
                        $stmtPrevTrial->close();
                    }
                    
                    // Insert new trial date record
                    $insertTrialDate = "INSERT INTO trial_date_history (plan_id, projected_date, changed_by, reason, is_current) 
                                       VALUES (?, ?, ?, ?, TRUE)";
                    $stmtInsTrial = $conn->prepare($insertTrialDate);
                    $changed_by = $data['changed_by'] ?? 'System';
                    $stmtInsTrial->bind_param("isss", $id, $newTrialProjectedDate, $changed_by, $trialDateChangeReason);
                    $stmtInsTrial->execute();
                    $stmtInsTrial->close();
                    
                    // Update the main table with the new trial projected date
                    $updates[] = "current_trial_projected_date = ?";
                    $paramTypes .= 's';
                    $paramValues[] = &$newTrialProjectedDate;
                }
            }
            

        $implementationDateChanged = false;
        $newImplementationProjectedDate = null;
        
        // CORRECTED: Check for the implementation-specific field
        if (isset($data['current_implementation_projected_date']) && !empty($data['current_implementation_projected_date'])) {
            // Get current implementation projected date from database to compare
            $currentImplementationDateQuery = "SELECT current_implementation_projected_date FROM add_plan_list WHERE id = ?";
            $stmtCurrentImplementation = $conn->prepare($currentImplementationDateQuery);
            $stmtCurrentImplementation->bind_param("i", $id);
            $stmtCurrentImplementation->execute();
            $stmtCurrentImplementation->bind_result($currentImplementationProjectedDate);
            $stmtCurrentImplementation->fetch();
            $stmtCurrentImplementation->close();
            
            // Check if implementation date actually changed
            if ($currentImplementationProjectedDate !== $data['current_implementation_projected_date']) {
                $implementationDateChanged = true;
                $newImplementationProjectedDate = $data['current_implementation_projected_date'];
                $implementationDateChangeReason = $data['date_change_reason'] ?? 'Implementation date updated';
                
                // Archive old implementation date if it exists
                if (!empty($currentImplementationProjectedDate)) {
                    $updatePreviousImplementation = "UPDATE implementation_date_history SET is_current = FALSE WHERE plan_id = ? AND is_current = TRUE";
                    $stmtPrevImplementation = $conn->prepare($updatePreviousImplementation);
                    $stmtPrevImplementation->bind_param("i", $id);
                    $stmtPrevImplementation->execute();
                    $stmtPrevImplementation->close();
                }
                
                // Insert new implementation date record
                $insertImplementationDate = "INSERT INTO implementation_date_history (plan_id, projected_date, changed_by, reason, is_current) 
                                           VALUES (?, ?, ?, ?, TRUE)";
                $stmtInsImplementation = $conn->prepare($insertImplementationDate);
                $changed_by = $data['changed_by'] ?? 'System';
                $stmtInsImplementation->bind_param("isss", $id, $newImplementationProjectedDate, $changed_by, $implementationDateChangeReason);
                $stmtInsImplementation->execute();
                $stmtInsImplementation->close();
                
                // Update the main table with the new implementation projected date
                $updates[] = "current_implementation_projected_date = ?";
                $paramTypes .= 's';
                $paramValues[] = &$newImplementationProjectedDate;
            }
        }
        
        // Add all other fields from config that exist in data
        foreach ($config['table_fields'] as $field) {
            if (isset($data[$field]) && $field !== 'created_by' && $field !== 'current_projected_date' && $field !== 'current_testing_projected_date') {
                $updates[] = "$field = ?";
                
                // Handle empty date fields - convert to NULL with proper reference
                if (($field === 'completed_on' || $field === 'development_timeline' || $field === 'test_completed_on') && empty($data[$field])) {
                    $paramTypes .= 's';
                    $nullValue = null;
                    $paramValues[] = &$nullValue;
                } else {
                    if (is_int($data[$field])) {
                        $paramTypes .= 'i';
                    } elseif (is_float($data[$field])) {
                        $paramTypes .= 'd';
                    } else {
                        $paramTypes .= 's';
                    }
                    $paramValues[] = &$data[$field];
                }
            }
        }
        
        // Add updated_at timestamp
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        
        if (empty($updates)) {
            echo json_encode(array("success" => false, "message" => "No fields to update."));
            return;
        }
        
        // Add ID to parameters
        $paramTypes .= 'i';
        $paramValues[] = &$id;
        
        $query = "UPDATE add_plan_list SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        
        if(!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Bind parameters dynamically
        $bindParams = array_merge([$paramTypes], $paramValues);
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        
        if(!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        
        $conn->commit();
        
    echo json_encode(array(
    "success" => true, 
    "message" => "Plan updated successfully.",
    "type" => $planType,
    "dev_date_changed" => $devDateChanged,
    "new_dev_projected_date" => $newDevProjectedDate,
    "testing_date_changed" => $testingDateChanged,
    "new_testing_projected_date" => $newTestingProjectedDate,
    "training_date_changed" => $trainingDateChanged,
    "new_training_projected_date" => $newTrainingProjectedDate,
    "trial_date_changed" => $trialDateChanged,
    "new_trial_projected_date" => $newTrialProjectedDate,
    "implementation_date_changed" => $implementationDateChanged,
    "new_implementation_projected_date" => $newImplementationProjectedDate
    ));
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array("success" => false, "message" => "Error updating plan: " . $e->getMessage()));
    }
}

function updateTestingStatus($conn, $id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if(!$data) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data received."));
        return;
    }
    
    // Validate required testing fields
    $requiredFields = ['testing_status'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        echo json_encode(array(
            "success" => false, 
            "message" => "Missing required testing fields: " . implode(', ', $missingFields)
        ));
        return;
    }
    
    // Build update query for testing fields only
    $updates = [];
    $paramTypes = '';
    $paramValues = [];
    
    $testingFields = ['testing_timeline', 'testing_status', 'testing_remark'];
    
    foreach ($testingFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            
            if (is_int($data[$field])) {
                $paramTypes .= 'i';
            } elseif (is_float($data[$field])) {
                $paramTypes .= 'd';
            } else {
                $paramTypes .= 's';
            }
            
            $paramValues[] = &$data[$field];
        }
    }
    
    // Add updated_at timestamp
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
    
    if (empty($updates)) {
        echo json_encode(array("success" => false, "message" => "No testing fields to update."));
        return;
    }
    
    // Add ID to parameters
    $paramTypes .= 'i';
    $paramValues[] = &$id;
    
    $query = "UPDATE add_plan_list SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    
    if(!$stmt) {
        echo json_encode(array("success" => false, "message" => "Prepare failed: " . $conn->error));
        return;
    }
    
    // Bind parameters dynamically
    $bindParams = array_merge([$paramTypes], $paramValues);
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true, 
            "message" => "Testing status updated successfully."
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Execute failed: " . $stmt->error));
    }
    
    $stmt->close();
}

function updateTrainingStatus($conn, $id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if(!$data) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data received."));
        return;
    }
    
    // Validate required training fields
    $requiredFields = ['training_status'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        echo json_encode(array(
            "success" => false, 
            "message" => "Missing required training fields: " . implode(', ', $missingFields)
        ));
        return;
    }
    
    // Build update query for training fields only
    $updates = [];
    $paramTypes = '';
    $paramValues = [];
    
    $trainingFields = ['training_timeline', 'training_status', 'training_remark'];
    
    foreach ($trainingFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            
            if (is_int($data[$field])) {
                $paramTypes .= 'i';
            } elseif (is_float($data[$field])) {
                $paramTypes .= 'd';
            } else {
                $paramTypes .= 's';
            }
            
            $paramValues[] = &$data[$field];
        }
    }
    
    // Add updated_at timestamp
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
    
    if (empty($updates)) {
        echo json_encode(array("success" => false, "message" => "No training fields to update."));
        return;
    }
    
    // Add ID to parameters
    $paramTypes .= 'i';
    $paramValues[] = &$id;
    
    $query = "UPDATE add_plan_list SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    
    if(!$stmt) {
        echo json_encode(array("success" => false, "message" => "Prepare failed: " . $conn->error));
        return;
    }
    
    // Bind parameters dynamically
    $bindParams = array_merge([$paramTypes], $paramValues);
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true, 
            "message" => "Training status updated successfully."
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Execute failed: " . $stmt->error));
    }
    
    $stmt->close();
}
function getDateHistory($conn, $plan_id) {
    $query = "SELECT * FROM development_date_history 
              WHERE plan_id = ? 
              ORDER BY changed_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $history));
    $stmt->close();
}

function updateProjectedDate($conn, $id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if(!$data) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data received."));
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, update all previous dates to not current
        $updatePrevious = "UPDATE development_date_history SET is_current = FALSE WHERE plan_id = ?";
        $stmt1 = $conn->prepare($updatePrevious);
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Insert new date record
        $insertDate = "INSERT INTO development_date_history (plan_id, projected_date, changed_by, reason, is_current) 
                       VALUES (?, ?, ?, ?, TRUE)";
        $stmt2 = $conn->prepare($insertDate);
        $changed_by = $data['changed_by'] ?? 'System';
        $reason = $data['reason'] ?? '';
        $stmt2->bind_param("isss", $id, $data['projected_date'], $changed_by, $reason);
        $stmt2->execute();
        $stmt2->close();
        
        // Update main table
        $updateMain = "UPDATE add_plan_list SET current_projected_date = ? WHERE id = ?";
        $stmt3 = $conn->prepare($updateMain);
        $stmt3->bind_param("si", $data['projected_date'], $id);
        $stmt3->execute();
        $stmt3->close();
        
        $conn->commit();
        echo json_encode(array("success" => true, "message" => "Projected date updated successfully."));
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array("success" => false, "message" => "Error updating projected date: " . $e->getMessage()));
    }
}
function getTestingDateHistory($conn, $plan_id) {
    $query = "SELECT * FROM testing_date_history 
              WHERE plan_id = ? 
              ORDER BY changed_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $history));
    $stmt->close();
}

function updateTestingProjectedDate($conn, $id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if(!$data) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data received."));
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, update all previous dates to not current
        $updatePrevious = "UPDATE testing_date_history SET is_current = FALSE WHERE plan_id = ?";
        $stmt1 = $conn->prepare($updatePrevious);
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Insert new date record
        $insertDate = "INSERT INTO testing_date_history (plan_id, projected_date, changed_by, reason, is_current) 
                       VALUES (?, ?, ?, ?, TRUE)";
        $stmt2 = $conn->prepare($insertDate);
        $changed_by = $data['changed_by'] ?? 'System';
        $reason = $data['reason'] ?? '';
        $stmt2->bind_param("isss", $id, $data['projected_date'], $changed_by, $reason);
        $stmt2->execute();
        $stmt2->close();
        
        // Update main table
        $updateMain = "UPDATE add_plan_list SET current_testing_projected_date = ? WHERE id = ?";
        $stmt3 = $conn->prepare($updateMain);
        $stmt3->bind_param("si", $data['projected_date'], $id);
        $stmt3->execute();
        $stmt3->close();
        
        $conn->commit();
        echo json_encode(array("success" => true, "message" => "Testing projected date updated successfully."));
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array("success" => false, "message" => "Error updating testing projected date: " . $e->getMessage()));
    }
}

function getTrainingDateHistory($conn, $plan_id) {
    $query = "SELECT * FROM training_date_history 
              WHERE plan_id = ? 
              ORDER BY changed_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $history));
    $stmt->close();
}

function updateTrainingProjectedDate($conn, $id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if(!$data) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data received."));
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, update all previous dates to not current
        $updatePrevious = "UPDATE training_date_history SET is_current = FALSE WHERE plan_id = ?";
        $stmt1 = $conn->prepare($updatePrevious);
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Insert new date record
        $insertDate = "INSERT INTO training_date_history (plan_id, projected_date, changed_by, reason, is_current) 
                       VALUES (?, ?, ?, ?, TRUE)";
        $stmt2 = $conn->prepare($insertDate);
        $changed_by = $data['changed_by'] ?? 'System';
        $reason = $data['reason'] ?? '';
        $stmt2->bind_param("isss", $id, $data['projected_date'], $changed_by, $reason);
        $stmt2->execute();
        $stmt2->close();
        
        // Update main table
        $updateMain = "UPDATE add_plan_list SET current_training_projected_date = ? WHERE id = ?";
        $stmt3 = $conn->prepare($updateMain);
        $stmt3->bind_param("si", $data['projected_date'], $id);
        $stmt3->execute();
        $stmt3->close();
        
        $conn->commit();
        echo json_encode(array("success" => true, "message" => "Training projected date updated successfully."));
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array("success" => false, "message" => "Error updating training projected date: " . $e->getMessage()));
    }
}

function getTrialPlans($conn) {
    $query = "SELECT apl.*, cm.client_name 
              FROM add_plan_list apl 
              LEFT JOIN client_master cm ON apl.client_id = cm.id 
              WHERE apl.trial_status IS NOT NULL 
              ORDER BY apl.created_at DESC";
    
    $result = $conn->query($query);
    
    $plans = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $plans));
}

function getTrialPlansByClient($conn, $client_id) {
    $query = "SELECT apl.*, cm.client_name 
              FROM add_plan_list apl 
              LEFT JOIN client_master cm ON apl.client_id = cm.id 
              WHERE apl.client_id = ? AND apl.trial_status IS NOT NULL
              ORDER BY apl.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $plans = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $plans));
    $stmt->close();
}

function getTrialDateHistory($conn, $plan_id) {
    $query = "SELECT * FROM trial_date_history 
              WHERE plan_id = ? 
              ORDER BY changed_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $history));
    $stmt->close();
}

function updateTrialProjectedDate($conn, $id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if(!$data) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data received."));
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, update all previous dates to not current
        $updatePrevious = "UPDATE trial_date_history SET is_current = FALSE WHERE plan_id = ?";
        $stmt1 = $conn->prepare($updatePrevious);
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Insert new date record
        $insertDate = "INSERT INTO trial_date_history (plan_id, projected_date, changed_by, reason, is_current) 
                       VALUES (?, ?, ?, ?, TRUE)";
        $stmt2 = $conn->prepare($insertDate);
        $changed_by = $data['changed_by'] ?? 'System';
        $reason = $data['reason'] ?? '';
        $stmt2->bind_param("isss", $id, $data['projected_date'], $changed_by, $reason);
        $stmt2->execute();
        $stmt2->close();
        
        // Update main table
        $updateMain = "UPDATE add_plan_list SET current_trial_projected_date = ? WHERE id = ?";
        $stmt3 = $conn->prepare($updateMain);
        $stmt3->bind_param("si", $data['projected_date'], $id);
        $stmt3->execute();
        $stmt3->close();
        
        $conn->commit();
        echo json_encode(array("success" => true, "message" => "Trial projected date updated successfully."));
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array("success" => false, "message" => "Error updating trial projected date: " . $e->getMessage()));
    }
}

function updateTrialStatus($conn, $id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if(!$data) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data received."));
        return;
    }
    
    // Validate required trial fields
    $requiredFields = ['trial_status'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        echo json_encode(array(
            "success" => false, 
            "message" => "Missing required trial fields: " . implode(', ', $missingFields)
        ));
        return;
    }
    
    // Build update query for trial fields only
    $updates = [];
    $paramTypes = '';
    $paramValues = [];
    
    $trialFields = ['trial_timeline', 'trial_status', 'trial_remark'];
    
    foreach ($trialFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            
            if (is_int($data[$field])) {
                $paramTypes .= 'i';
            } elseif (is_float($data[$field])) {
                $paramTypes .= 'd';
            } else {
                $paramTypes .= 's';
            }
            
            $paramValues[] = &$data[$field];
        }
    }
    
    // Add updated_at timestamp
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
    
    if (empty($updates)) {
        echo json_encode(array("success" => false, "message" => "No trial fields to update."));
        return;
    }
    
    // Add ID to parameters
    $paramTypes .= 'i';
    $paramValues[] = &$id;
    
    $query = "UPDATE add_plan_list SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    
    if(!$stmt) {
        echo json_encode(array("success" => false, "message" => "Prepare failed: " . $conn->error));
        return;
    }
    
    // Bind parameters dynamically
    $bindParams = array_merge([$paramTypes], $paramValues);
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true, 
            "message" => "Trial status updated successfully."
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Execute failed: " . $stmt->error));
    }
    
    $stmt->close();
}
function getImplementationDateHistory($conn, $plan_id) {
    $query = "SELECT * FROM implementation_date_history 
              WHERE plan_id = ? 
              ORDER BY changed_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    
    echo json_encode(array("success" => true, "data" => $history));
    $stmt->close();
}

function updateImplementationProjectedDate($conn, $id) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if(!$data) {
        echo json_encode(array("success" => false, "message" => "Invalid JSON data received."));
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, update all previous dates to not current
        $updatePrevious = "UPDATE implementation_date_history SET is_current = FALSE WHERE plan_id = ?";
        $stmt1 = $conn->prepare($updatePrevious);
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Insert new date record
        $insertDate = "INSERT INTO implementation_date_history (plan_id, projected_date, changed_by, reason, is_current) 
                       VALUES (?, ?, ?, ?, TRUE)";
        $stmt2 = $conn->prepare($insertDate);
        $changed_by = $data['changed_by'] ?? 'System';
        $reason = $data['reason'] ?? '';
        $stmt2->bind_param("isss", $id, $data['projected_date'], $changed_by, $reason);
        $stmt2->execute();
        $stmt2->close();
        
        // Update main table
        $updateMain = "UPDATE add_plan_list SET current_projected_date = ? WHERE id = ?";
        $stmt3 = $conn->prepare($updateMain);
        $stmt3->bind_param("si", $data['projected_date'], $id);
        $stmt3->execute();
        $stmt3->close();
        
        $conn->commit();
        echo json_encode(array("success" => true, "message" => "Implementation projected date updated successfully."));
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(array("success" => false, "message" => "Error updating implementation projected date: " . $e->getMessage()));
    }
}

function deletePlan($conn, $id) {
    $query = "DELETE FROM add_plan_list WHERE id = ?";
    $stmt = $conn->prepare($query); 
    
    if(!$stmt) {
        echo json_encode(array("success" => false, "message" => "Prepare failed: " . $conn->error));
        return;
    }
    
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()) {
        echo json_encode(array("success" => true, "message" => "Plan deleted successfully."));
    } else {
        echo json_encode(array("success" => false, "message" => "Execute failed: " . $stmt->error));
    }
    
    $stmt->close();
}

$conn->close();
?>