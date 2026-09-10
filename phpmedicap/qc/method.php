<?php
    require '../db.php';
    require '../token.php';
    require '../tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);
    
    
    
    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);



function getGrdeValue($grade , $conn){
    
            if ($grade == 'NA') {
                $grd = [0]; // Default value as an array containing 0
            } else {
                $grd = $grade;
            }
            
            // Ensure $grd is properly formatted as a comma-separated list
            if (!is_array($grd)) {
                $grd = explode(',', $grd); // Convert to an array if it is a string
            }
            
            // Validate $grd to contain only integers
            $grd = array_filter($grd, function($value) {
                return is_numeric($value) && intval($value) > 0; // Allow only positive integers
            });
            
            // Convert back to a comma-separated string for SQL
            $grdList = implode(',', $grd);
            
            if (!empty($grdList)) {
                // Only execute the query if $grdList is not empty
                $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade WHERE id IN ($grdList)";
               // echo $q; // Debugging: Display the query
                
                $resQ = $conn->query($q);
                if ($resQ) {
                    $prodLatest = $resQ->fetch_assoc();
                    $gradeName = $prodLatest['gradeName'];
                } else {
                    // Handle SQL query errors
                    echo "SQL Error: " . $conn->error;
                }
            } else {
                // Handle case where $grdList is empty
                $gradeName = "NA"; // Set a default value or handle it appropriately
              //  echo "No valid grades to fetch.";
            }     
            
            
            return $gradeName;
            
            
}

function ensure_test_methods_purpose_column($conn) {
    $check = $conn->query("SHOW COLUMNS FROM test_methods LIKE 'purpose'");
    if (!$check || $check->num_rows === 0) {
        $conn->query("ALTER TABLE test_methods ADD COLUMN purpose LONGTEXT NULL");
    }
}

/** Ensure MOA editor columns exist on test_methods (create from PHP when missing). */
function ensure_test_methods_moa_columns($conn) {
    $needed = array(
        'spec_test_id' => "ALTER TABLE test_methods ADD COLUMN spec_test_id TEXT NULL",
        'chemical_reagents' => "ALTER TABLE test_methods ADD COLUMN chemical_reagents LONGTEXT NULL",
        'balance' => "ALTER TABLE test_methods ADD COLUMN balance LONGTEXT NULL",
        'equipment_instruments' => "ALTER TABLE test_methods ADD COLUMN equipment_instruments LONGTEXT NULL",
        'glasswares' => "ALTER TABLE test_methods ADD COLUMN glasswares LONGTEXT NULL",
        'dilutions' => "ALTER TABLE test_methods ADD COLUMN dilutions LONGTEXT NULL",
        'calculations' => "ALTER TABLE test_methods ADD COLUMN calculations LONGTEXT NULL",
        'standards' => "ALTER TABLE test_methods ADD COLUMN standards LONGTEXT NULL",
        'testinginstruction' => "ALTER TABLE test_methods ADD COLUMN testinginstruction LONGTEXT NULL",
        'gc' => "ALTER TABLE test_methods ADD COLUMN gc LONGTEXT NULL",
        'uw' => "ALTER TABLE test_methods ADD COLUMN uw LONGTEXT NULL",
        'volumetric_solutions' => "ALTER TABLE test_methods ADD COLUMN volumetric_solutions LONGTEXT NULL",
        'hplc' => "ALTER TABLE test_methods ADD COLUMN hplc LONGTEXT NULL",
        'solution_preparation' => "ALTER TABLE test_methods ADD COLUMN solution_preparation LONGTEXT NULL",
        'Documentations' => "ALTER TABLE test_methods ADD COLUMN Documentations LONGTEXT NULL",
        'Trendings' => "ALTER TABLE test_methods ADD COLUMN Trendings LONGTEXT NULL",
        'Genral_Instruction' => "ALTER TABLE test_methods ADD COLUMN Genral_Instruction LONGTEXT NULL",
        'Safety' => "ALTER TABLE test_methods ADD COLUMN Safety LONGTEXT NULL",
        'purpose' => "ALTER TABLE test_methods ADD COLUMN purpose LONGTEXT NULL",
        'Scope' => "ALTER TABLE test_methods ADD COLUMN Scope LONGTEXT NULL",
        'Associative_Document' => "ALTER TABLE test_methods ADD COLUMN Associative_Document LONGTEXT NULL",
        'Refrenced_Document' => "ALTER TABLE test_methods ADD COLUMN Refrenced_Document LONGTEXT NULL",
        'defination' => "ALTER TABLE test_methods ADD COLUMN defination LONGTEXT NULL",
        'revision_history' => "ALTER TABLE test_methods ADD COLUMN revision_history LONGTEXT NULL",
        'phases' => "ALTER TABLE test_methods ADD COLUMN phases LONGTEXT NULL",
        'Test_Solutions' => "ALTER TABLE test_methods ADD COLUMN Test_Solutions LONGTEXT NULL",
        'standard_Solutions' => "ALTER TABLE test_methods ADD COLUMN standard_Solutions LONGTEXT NULL",
        'chromatographic_conditions' => "ALTER TABLE test_methods ADD COLUMN chromatographic_conditions LONGTEXT NULL",
        'test_id' => "ALTER TABLE test_methods ADD COLUMN test_id INT NULL",
    );
    $existing = array();
    $res = @$conn->query("SHOW COLUMNS FROM test_methods");
    if ($res) {
        while ($col = $res->fetch_assoc()) {
            $existing[$col['Field']] = true;
        }
    }
    foreach ($needed as $name => $alterSql) {
        if (!isset($existing[$name])) {
            // Skip lowercase procedure when capital-P Procedure already exists (live schema).
            if ($name === 'procedure' && isset($existing['Procedure'])) {
                continue;
            }
            @$conn->query($alterSql);
        }
    }
    // Refresh after alters is not required for callers that re-SHOW COLUMNS.
}

/** Insert a blank test_methods row using only columns that exist. */
function create_blank_test_method_row($conn, $testId, $specTestId = 0) {
    ensure_test_methods_moa_columns($conn);
    $existing = array();
    $res = @$conn->query("SHOW COLUMNS FROM test_methods");
    if ($res) {
        while ($col = $res->fetch_assoc()) {
            $existing[$col['Field']] = true;
        }
    }
    $map = array(
        'test_id' => "'".intval($testId)."'",
        'spec_test_id' => "'".intval($specTestId)."'",
        'chemical_reagents' => "'[]'",
        'balance' => "'[]'",
        'equipment_instruments' => "'[]'",
        'glasswares' => "'[]'",
        'dilutions' => "'[]'",
        'calculations' => "'NA'",
        'standards' => "'NA'",
        'testinginstruction' => "'[]'",
        'gc' => "'NA'",
        'uw' => "'NA'",
        'volumetric_solutions' => "'[]'",
        'hplc' => "'[]'",
        'solution_preparation' => "'NA'",
        'Documentations' => "'[]'",
        'Trendings' => "'[]'",
        'Genral_Instruction' => "'[]'",
        'Safety' => "'NA'",
        'purpose' => "'NA'",
        'Scope' => "'NA'",
        'Associative_Document' => "'[]'",
        'Refrenced_Document' => "'[]'",
        'defination' => "'[]'",
        'revision_history' => "'[]'",
        'phases' => "'[]'",
        'Test_Solutions' => "'NA'",
        'standard_Solutions' => "'NA'",
        'chromatographic_conditions' => "'NA'",
    );
    // Live DB may use Procedure (capital P) instead of procedure.
    if (isset($existing['Procedure'])) {
        $map['Procedure'] = "'NA'";
    } elseif (isset($existing['procedure'])) {
        $map['procedure'] = "'NA'";
    }
    $fields = array();
    $values = array();
    foreach ($map as $f => $v) {
        if (isset($existing[$f])) {
            $fields[] = '`'.$f.'`';
            $values[] = $v;
        }
    }
    if (count($fields) === 0) {
        return false;
    }
    try {
        return (bool)$conn->query("INSERT INTO test_methods (".implode(',', $fields).") VALUES (".implode(',', $values).")");
    } catch (Throwable $e) {
        return false;
    }
}

/** Normalize a test_methods row for the Angular MOA editor. */
function normalize_test_method_row_for_moa($mm) {
    if (!is_array($mm)) {
        return array();
    }
    // Prefer real content from Procedure (live schema) or procedure — never keep a
    // placeholder 'NA'/empty over the other column's saved HTML.
    $procRaw = '';
    foreach (array('Procedure', 'procedure') as $procKey) {
        if (!isset($mm[$procKey])) {
            continue;
        }
        $candidate = trim((string)$mm[$procKey]);
        if ($candidate !== '' && strtoupper($candidate) !== 'NA') {
            $procRaw = $mm[$procKey];
            break;
        }
    }
    $mm['procedure'] = $procRaw !== '' ? htmlspecialchars_decode($procRaw, ENT_QUOTES) : '';

    foreach (array('Genral_Instruction','Associative_Document','Refrenced_Document','defination','testinginstruction','equipment_instruments','chemical_reagents','glasswares','balance','dilutions','volumetric_solutions','hplc','phases','revision_history') as $f) {
        if (!isset($mm[$f]) || $mm[$f] === null || $mm[$f] === '') {
            $mm[$f] = array();
        } elseif (is_array($mm[$f])) {
            // already decoded
        } else {
            $decoded = json_decode($mm[$f], true);
            $mm[$f] = is_array($decoded) ? $decoded : array();
        }
        if (!is_array($mm[$f])) {
            $mm[$f] = array();
        }
    }
    foreach (array('chromatographic_conditions','Test_Solutions','standard_Solutions','purpose','Scope','Safety') as $f) {
        $mm[$f] = (isset($mm[$f]) && $mm[$f] !== null && $mm[$f] !== '' && $mm[$f] !== 'NA')
            ? htmlspecialchars_decode($mm[$f], ENT_QUOTES) : '';
    }
    return $mm;
}

function moa_method_row_content_score($row) {
    if (!is_array($row)) {
        return 0;
    }
    $score = 0;
    foreach (array('Safety', 'procedure', 'Procedure', 'chromatographic_conditions') as $f) {
        if (isset($row[$f]) && trim((string)$row[$f]) !== '' && strtoupper(trim((string)$row[$f])) !== 'NA') {
            $score += 10;
        }
    }
    foreach (array('testinginstruction', 'equipment_instruments', 'chemical_reagents', 'glasswares', 'balance', 'volumetric_solutions', 'phases') as $f) {
        if (!isset($row[$f])) {
            continue;
        }
        if (is_array($row[$f]) && count($row[$f]) > 0) {
            $score += 5;
            continue;
        }
        $raw = trim((string)$row[$f]);
        if ($raw !== '' && $raw !== '[]' && strtoupper($raw) !== 'NA') {
            $score += 5;
        }
    }
    return $score;
}

/** Build UPDATE fragments for full method body fields from a save payload. */
function moa_method_body_set_parts($conn, $input) {
    $existing = array();
    $res = @$conn->query("SHOW COLUMNS FROM test_methods");
    if ($res) {
        while ($col = $res->fetch_assoc()) {
            $existing[$col['Field']] = true;
        }
    }
    $procedureCol = isset($existing['Procedure']) ? 'Procedure' : (isset($existing['procedure']) ? 'procedure' : null);
    $esc = function ($v) use ($conn) {
        return $conn->real_escape_string((string)$v);
    };
    $escJson = function ($v) use ($conn) {
        $json = json_encode($v);
        if ($json === false) {
            $json = '[]';
        }
        return $conn->real_escape_string($json);
    };
    $setParts = array();
    $textMap = array(
        'Safety' => 'Safety',
        'purpose' => 'purpose',
        'Scope' => 'Scope',
        'Test_Solutions' => 'Test_Solutions',
        'standard_Solutions' => 'standard_Solutions',
        'chromatographic_conditions' => 'chromatographic_conditions',
    );
    foreach ($textMap as $inKey => $col) {
        if (!array_key_exists($inKey, $input) || !isset($existing[$col])) {
            continue;
        }
        $val = $input[$inKey];
        if ($val === null) {
            continue;
        }
        $setParts[] = "`".$col."`='".$esc($val)."'";
    }
    if (array_key_exists('procedure', $input) && $procedureCol) {
        $setParts[] = "`".$procedureCol."`='".$esc($input['procedure'] ?? '')."'";
    }
    $jsonMap = array(
        'Genral_Instruction' => 'Genral_Instruction',
        'Associative_Document' => 'Associative_Document',
        'Refrenced_Document' => 'Refrenced_Document',
        'defination' => 'defination',
        'testinginstruction' => 'testinginstruction',
        'equipment_instruments' => 'equipment_instruments',
        'chemical_reagents' => 'chemical_reagents',
        'glasswares' => 'glasswares',
        'balance' => 'balance',
        'volumetric_solutions' => 'volumetric_solutions',
        'phases' => 'phases',
        'hplc' => 'hplc',
        'revision_history' => 'revision_history',
    );
    foreach ($jsonMap as $inKey => $col) {
        if (!array_key_exists($inKey, $input) || !isset($existing[$col])) {
            continue;
        }
        $val = $input[$inKey];
        if ($val === null) {
            continue;
        }
        $setParts[] = "`".$col."`='".$escJson($val)."'";
    }
    return $setParts;
}

/** Resolve / create the test_methods row for a spec test + master, return id or 0. */
function moa_resolve_test_method_row_id($conn, $spectTestId, $testMasterId, $moaMode = '') {
    $spectTestId = intval($spectTestId);
    $testMasterId = intval($testMasterId);
    $moaMode = strtolower(trim((string)$moaMode));
    if ($testMasterId <= 0) {
        return 0;
    }
    if ($spectTestId > 0 && $moaMode !== 'test') {
        $where = "spec_test_id='".$spectTestId."' AND test_id='".$testMasterId."'";
    } else {
        $where = "test_id='".$testMasterId."' AND (spec_test_id IS NULL OR spec_test_id=0 OR spec_test_id='')";
    }
    $res = $conn->query("SELECT id FROM test_methods WHERE ".$where." ORDER BY id DESC LIMIT 1");
    if ((!$res || $res->num_rows === 0) && $spectTestId > 0 && $testMasterId > 0) {
        $res = $conn->query("SELECT id FROM test_methods WHERE test_id='".$testMasterId."' ORDER BY id DESC LIMIT 1");
    }
    if (!$res || $res->num_rows === 0) {
        create_blank_test_method_row($conn, $testMasterId, ($spectTestId > 0 && $moaMode !== 'test') ? $spectTestId : 0);
        $res = $conn->query("SELECT id FROM test_methods WHERE ".$where." ORDER BY id DESC LIMIT 1");
    }
    if ((!$res || $res->num_rows === 0) && $spectTestId > 0 && $testMasterId > 0) {
        $res = $conn->query("SELECT id FROM test_methods WHERE test_id='".$testMasterId."' ORDER BY id DESC LIMIT 1");
    }
    if (!$res || $res->num_rows === 0) {
        return 0;
    }
    return intval($res->fetch_assoc()['id']);
}

/** Resolve the best test_methods row for a specification test line. */
function moa_fetch_method_data_for_spec_test($conn, $specTestId, $testMasterId = 0) {
    ensure_test_methods_moa_columns($conn);
    $specTestId = intval($specTestId);
    $testMasterId = intval($testMasterId);
    if ($specTestId <= 0 && $testMasterId <= 0) {
        return array();
    }

    if ($testMasterId <= 0 && $specTestId > 0) {
        $tmRes = @$conn->query("SELECT test_master_id FROM spec_tests WHERE id='".$specTestId."' LIMIT 1");
        if ($tmRes && ($tmRow = $tmRes->fetch_assoc())) {
            $testMasterId = intval($tmRow['test_master_id']);
        }
    }

    $seen = array();
    $candidates = array();
    $queries = array();
    if ($specTestId > 0 && $testMasterId > 0) {
        $queries[] = "SELECT * FROM test_methods WHERE spec_test_id='".$specTestId."' AND test_id='".$testMasterId."' ORDER BY id DESC";
    }
    if ($specTestId > 0) {
        $queries[] = "SELECT * FROM test_methods WHERE spec_test_id='".$specTestId."' ORDER BY id DESC";
        // Legacy saves used spectTestId as test_id (both columns set to the same id).
        $queries[] = "SELECT * FROM test_methods WHERE test_id='".$specTestId."' ORDER BY id DESC";
    }
    if ($testMasterId > 0) {
        $queries[] = "SELECT * FROM test_methods WHERE test_id='".$testMasterId."' ORDER BY id DESC";
    }

    foreach ($queries as $sql) {
        $res = @$conn->query($sql);
        if (!$res) {
            continue;
        }
        while ($row = $res->fetch_assoc()) {
            $rid = intval($row['id'] ?? 0);
            if ($rid > 0 && isset($seen[$rid])) {
                continue;
            }
            if ($rid > 0) {
                $seen[$rid] = true;
            }
            $candidates[] = $row;
        }
    }

    if (count($candidates) === 0) {
        return array();
    }

    $best = $candidates[0];
    $bestScore = moa_method_row_content_score($best);
    foreach ($candidates as $row) {
        $score = moa_method_row_content_score($row);
        if ($score > $bestScore) {
            $best = $row;
            $bestScore = $score;
        }
    }
    return normalize_test_method_row_for_moa($best);
}

function ensure_test_methods_status_columns($conn) {
    $required = array(
        "status" => "ALTER TABLE test_methods ADD COLUMN status VARCHAR(20) NULL",
        "entry_on" => "ALTER TABLE test_methods ADD COLUMN entry_on DATETIME NULL",
        "approve_by" => "ALTER TABLE test_methods ADD COLUMN approve_by VARCHAR(100) NULL",
        "approve_on" => "ALTER TABLE test_methods ADD COLUMN approve_on DATETIME NULL"
    );
    foreach ($required as $columnName => $alterSql) {
        $check = $conn->query("SHOW COLUMNS FROM test_methods LIKE '".$columnName."'");
        if (!$check || $check->num_rows === 0) {
            $conn->query($alterSql);
        }
    }
}

function ensure_test_method_documents_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS test_method_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(50) NULL,
        test_master_id INT NOT NULL,
        test_method_id INT NULL,
        document_type VARCHAR(30) NOT NULL,
        document_name VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NULL,
        status VARCHAR(20) DEFAULT 'Active',
        entry_by VARCHAR(100) NULL,
        entry_date DATETIME NULL
    )";
    $conn->query($sql);
}

function ensure_test_method_equipment_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS test_method_equipment_map (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(50) NULL,
        test_master_id INT NOT NULL,
        test_method_id INT NULL,
        resource_type VARCHAR(30) NOT NULL,
        equipment_code VARCHAR(100) NOT NULL,
        equipment_name VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'Active',
        entry_by VARCHAR(100) NULL,
        entry_date DATETIME NULL
    )";
    $conn->query($sql);
}

function ensure_test_method_qc_material_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS test_method_qc_material_map (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plant_id VARCHAR(50) NULL,
        test_master_id INT NOT NULL,
        test_method_id INT NULL,
        resource_type VARCHAR(30) NOT NULL,
        material_code VARCHAR(100) NOT NULL,
        material_name VARCHAR(255) NOT NULL,
        material_subtype VARCHAR(255) NULL,
        status VARCHAR(20) DEFAULT 'Active',
        entry_by VARCHAR(100) NULL,
        entry_date DATETIME NULL
    )";
    $conn->query($sql);
}

function get_latest_test_method_id($conn, $testMasterId) {
    $methodId = 0;
    $res = $conn->query("SELECT id FROM test_methods WHERE test_id='".$testMasterId."' ORDER BY id DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $methodId = intval($row["id"]);
    }
    return $methodId;
}

function fetch_latest_test_method_row($conn, $testMasterId) {
    $row = null;
    $res = $conn->query("SELECT * FROM test_methods WHERE test_id='".$testMasterId."' ORDER BY id DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
    }
    return $row;
}
 
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
    
    // Ensure MOA workflow columns exist on spec_tests. These are referenced by
    // getMoaSpecTests / getPendingSpecForMoa; a missing column makes the whole
    // query fail (SQL error 1054) so the Tests & Methods table comes back empty.
    if (!function_exists('moa_ensure_spec_test_columns')) {
        function moa_ensure_spec_test_columns($conn) {
            $cols = array(
                "method_wf_status VARCHAR(50) DEFAULT NULL",
                "method_log_status VARCHAR(50) DEFAULT NULL",
                "method_details TEXT",
            );
            foreach ($cols as $def) {
                $name = trim(explode(' ', $def)[0]);
                $chk = $conn->query("SHOW COLUMNS FROM spec_tests LIKE '".$conn->real_escape_string($name)."'");
                if ($chk && $chk->num_rows === 0) {
                    @$conn->query("ALTER TABLE spec_tests ADD COLUMN $def");
                }
            }
        }
    }
    moa_ensure_spec_test_columns($conn);

if ($_GET["type"] == "getMaterialDetails") {
         $sql = "SELECT * FROM spec_tests WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT a.*,b.grade as m_grade FROM specification a left JOIN material b on a.material_code=b.material_code WHERE a.specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_code"] = $row1["material_code"];
                        $row["product_code"] = $row1["product_code"];
                        $row["spec_type"] = $row1["spec_type"];
                        
                 $q= 'SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('.$row1['m_grade'].')';
            $resQ = $conn->query($q);
           $prodLatest = $resQ->fetch_assoc();
           $row['gradeName'] = $prodLatest['gradeName'];
                        
                    }
                }
                
                if ($row["spec_type"] == "Finish Product" || $row["spec_type"] == "Inprocess Specification" || $row["spec_type"] == "Stability Specification") {
                    $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["product_name"] = $row1["product_name"];
                            $row["grade"] = $row1["grade"];
                            $row["generic_name"] = $row1["generic_name"];
                        }
                    }
                } else {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_type"] = $row1["material_type"];
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                }
                
                echo json_encode($row);
                break;
            }
        } else {
            echo "{}";
        }
    } 
   else if ($_GET["type"] == "getMaterialDetailsWater") {
        $sql = "SELECT * FROM spec_tests WHERE id='".$_GET["id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sql1 = "SELECT a.* FROM specification a  WHERE a.specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                       // $row["material_code"] = $row1["material_code"];
                       // $row["product_code"] = $row1["product_code"];
                        $row["spec_type"] = $row1["spec_type"];
                        $row["water_type"] = $row1["water_type"];
                        
        //          $q= 'SELECT GROUP_CONCAT(grade)  as gradeName FROM    grade where id in ('.$row1['m_grade'].')';
        //     $resQ = $conn->query($q);
        //   $prodLatest = $resQ->fetch_assoc();
        //   $row['gradeName'] = $prodLatest['gradeName'];
                        
                    }
                }
                
                // if ($row["spec_type"] == "Finish Product" || $row["spec_type"] == "Inprocess Specification" || $row["spec_type"] == "Stability Specification") {
                //     $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                //     $result1 = $conn->query($sql1);
                //     if ($result1->num_rows > 0) {
                //         while ($row1 = $result1->fetch_assoc()) {
                //             $row["product_name"] = $row1["product_name"];
                //             $row["grade"] = $row1["grade"];
                //             $row["generic_name"] = $row1["generic_name"];
                //         }
                //     }
                // } else {
                //     $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                //     $result1 = $conn->query($sql1);
                //     if ($result1->num_rows > 0) {
                //         while ($row1 = $result1->fetch_assoc()) {
                //             $row["material_type"] = $row1["material_type"];
                //             $row["material_subtype"] = $row1["material_subtype"];
                //             $row["material_name"] = $row1["material_name"];
                //             $row["grade"] = $row1["grade"];
                //         }
                //     }
                // }
                
                echo json_encode($row);
                break;
            }
        } else {
            echo "{}";
        }
    } 
    else if ($_GET["type"] == "getChemicals") {
        $output = array();
        $sql = "SELECT * FROM chemical_master";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
 
    else if ($_GET["type"] == "getChemicals") {
        $output = array();
        $sql = "SELECT * FROM chemical_master";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if($_GET['type'] == 'save_cromatograms'){
        
        $input = $_POST;
        
        $pid = $_GET["plant_id"];
        $mid = $_GET["test_method_no"];
        $randomNumber = mt_rand(10000, 99999);
        
        if (isset($_FILES["cromatograms"])) {
            $file_tmp = $_FILES['cromatograms']['tmp_name'];
            $file_name_parts = explode('.', $_FILES['cromatograms']['name']);
            $file_ext = strtolower(end($file_name_parts));
            $cromatograms = $pid.$mid.$randomNumber.".".$file_ext;
            move_uploaded_file($file_tmp, "../../../upload/cromatograms/" . $cromatograms);
        }
         
        $sql = "insert into test_cromatograms(test_method_no,cromatograms,plant_id) 
        values('".$_GET["test_method_no"]."','$cromatograms','".$_GET["plant_id"]."')";
    	if($conn->query($sql)){
    	   
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
    }
     else if($_GET['type'] == 'save_calculation'){
                   $input = $_POST;
        
        	if(isset($_FILES["label"])) {
            $file_tmp =$_FILES['label']['tmp_name'];
            $file_ext=strtolower(end(explode('.',$_FILES['label']['name'])));
            $file_name = $entry_date.basename($_FILES["label"]["name"]);
            $label = $file_name;
            move_uploaded_file($file_tmp,"../../../upload/calculation/".$file_name);
        }

           $sql = "insert into  test_calculations(test_method_no,test_id,file_name,plant_id) values('".$_GET["test_method_no"]."','".$_GET["id"]."','$label','".$_GET["plant_id"]."')";
    	if($conn->query($sql)){
    	   
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
 }
     
        else  if ($_GET["type"] == "get_calculations") {
        $output = array();
             $sql = "SELECT * FROM test_calculations WHERE test_id='".$_GET["test_id"]."'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
  else  if ($_GET["type"] == "get_cromatograms") {
        $output = array();
             $sql = "SELECT * FROM test_cromatograms WHERE test_method_no='".$_GET["test_method_no"]."' AND plant_id='".$_GET["plant_id"]."'"; 
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    
        else  if ($_GET["type"] == "del_calculations") {
       
           $sql = "delete from  test_calculations   where id='".$_GET["id"]."'";
    	if($conn->query($sql)){
    	   
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
        
     } 
    else  if ($_GET["type"] == "delcromatograms") {
        $sql = "delete from  test_cromatograms   where id='".$_GET["id"]."'";
    	if($conn->query($sql)){
    		echo "{\"status\":\"success\"}";
    	} else {
    		echo "{\"status\":\"".$conn->error."\"}";
    	}
    } 
    else if ($_GET["type"] == "getEquipments") {
        $output = Array();
    	$sql = "SELECT * FROM equipment WHERE status='approve'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    else if ($_GET["type"] == "getQCEquipments") { 
        
        $output = Array();
    	$sql ="SELECT * FROM equipment WHERE status ='Active' and  plant_id='".$_GET["plant_id"]."' AND ( department ='Quality Control' OR department ='Microbiology' or department = 'Quality Assurance' )";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    else if ($_GET["type"] == "getChemicals1") {
        $output = Array();
    	$sql = "SELECT chemical_name,grade,molecular_wt FROM chemical  WHERE plant_id='".$_GET["plant_id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    else if ($_GET["type"] == "getMedia") {
    //     $output = Array();
    // 	$sql = "SELECT * FROM media";
    // 	$result = $conn->query($sql);
    // 	if($result->num_rows > 0){
    // 		while($row = $result->fetch_assoc()){
    // 			$output[] = $row;
    // 		}
    // 	}
    // 	echo json_encode($output);
    	$sql = "SELECT * FROM others_material where material_subtype = 'Media' AND plant_id='".$_GET["plant_id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    else if ($_GET["type"] == "getweighingbalance"){
               $output = Array();
    	$sql = "SELECT * FROM equipment where equipment_type='Balance(Weighing)' AND status='Active' and plant_id='".$_GET["plant_id"]."'";
    	$result = $conn->query($sql);
    	if($result->num_rows > 0){
    		while($row = $result->fetch_assoc()){
    			$output[] = $row;
    		}
    	}
    	echo json_encode($output);
    }
    else if ($_GET["type"] == "getGlasswares") {
     $output = Array();
        $sql = "SELECT * FROM others_material  where material_subtype = 'Glassware' AND plant_id='".$_GET["plant_id"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
      else  if ($_GET["type"] == "GET_vOLUMENTRIC_sOLUNTIONS") {
                $output = array();
                     $sql = "SELECT * FROM volumetric_solution WHERE plant_id='".$_GET["plant_id"]."'"; 
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
                echo json_encode($output);
            } 
    else if ($_GET["type"] == "getHPLCs") {
         $output = Array();
        $sql = "SELECT * FROM hplc WHERE status='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "saveTestMethodMaster1") {

          $sql= "Insert into test_methods( `procedure`,test_id,balance,equipment_instruments,glasswares,dilutions,calculations,standards,gc,uw,
        volumetric_solutions,solution_preparation,entry_by,chemical_reagents,spec_test_id,hplc,media,phases)
        values('".$input["procedure"]."','".$input["id"]."','".json_encode($input["balanceData"])."','".json_encode($input["eqdata"])."','".json_encode($input["glasswareData"])."',
        '".json_encode($input["dilutiontList"])."',
        '".$input["Calculation"]."','".$input["standards"]."','".$input["gc"]."','".$input["uw"]."',
        '".json_encode($input["Volumetric_SolutionsList"])."','".$input["solution_preparation"]."','".$_GET["emp_id"]."',
        '".json_encode($input["chemData"])."','".$input["id"]."','".json_encode($input["HPLC"])."','".json_encode($input["mediaData"])."',
        '".json_encode($input["phases"])."')";
        $conn->query($sql);
        $sql = "UPDATE spec_tests SET method='active', procedures='".$input["procedures"]."' ,  method_details='".json_encode($input['description'])."' WHERE id='".$input["id"]."'";
      
        
            foreach($input["data"] as $index=>$catData){
                if(is_array($catData) && count(($catData))){
                    foreach($catData as $cdata){

                     $q = "insert into moa_procdeatil(moa_id,category,data) value (".$input["id"].",'".$index."','".json_encode($cdata)."')";
                     $conn->query($q);
                     
                    }   
                }


            }
       
      
        if ($conn->query($sql)) {
            echo "{\"status\":\"success\"}";
        } else {
            echo "{\"status\":\"failed\"}";
        }

        
    }
    
    else if ($_GET["type"] == "saveTestMethodMaster111"){ 
        ensure_test_methods_moa_columns($conn);
        ensure_test_methods_status_columns($conn);
        $spectTestId = intval($input["spectTestId"] ?? 0);
        $testMasterId = intval($input["test_master_id"] ?? 0);
        $moaMode = strtolower(trim((string)($input["moa_mode"] ?? '')));

        if ($testMasterId <= 0 && $spectTestId > 0) {
            $tmRes = $conn->query("SELECT test_master_id FROM spec_tests WHERE id='".$spectTestId."' LIMIT 1");
            if ($tmRes && ($tmRow = $tmRes->fetch_assoc())) {
                $testMasterId = intval($tmRow['test_master_id']);
            }
        }

        // Test Specific: save against test master (no specification line)
        if ($spectTestId <= 0 || $moaMode === 'test') {
            if ($testMasterId <= 0) {
                echo "{\"status\":\"failed\",\"message\":\"test_master_id required\"}";
            } else {
                $methodRowId = moa_resolve_test_method_row_id($conn, 0, $testMasterId, 'test');
                $setParts = moa_method_body_set_parts($conn, $input);
                $rev = $conn->real_escape_string(json_encode($input["revision_history"] ?? []));
                $setParts[] = "status='Pending'";
                $setParts[] = "revision_history='".$rev."'";
                $setParts[] = "entry_by='".$conn->real_escape_string($_GET["emp_id"] ?? '')."'";
                $setParts[] = "entry_on='".$entry_date."'";
                $ok = false;
                if ($methodRowId > 0) {
                    $ok = $conn->query("UPDATE test_methods SET ".implode(', ', $setParts)." WHERE id='".$methodRowId."'");
                }
                $sql1 = "UPDATE test SET method = 'For Checking' WHERE id ='".$testMasterId."'";
                $ok2 = $conn->query($sql1);
                echo ($ok && $ok2) ? "{\"status\":\"success\",\"moa_mode\":\"test\"}" : "{\"status\":\"failed\",\"message\":\"".($conn->error ?: "save failed")."\"}";
            }
        } else {
            $methodRowId = moa_resolve_test_method_row_id($conn, $spectTestId, $testMasterId, $moaMode);
            $setParts = moa_method_body_set_parts($conn, $input);
            $setParts[] = "status='Pending'";
            $setParts[] = "revision_history='".$conn->real_escape_string(json_encode($input["revision_history"] ?? []))."'";
            $setParts[] = "entry_by='".$conn->real_escape_string($_GET["emp_id"] ?? '')."'";
            $setParts[] = "entry_on='".$entry_date."'";
            $setParts[] = "spec_test_id='".$spectTestId."'";
            $okBody = false;
            if ($methodRowId > 0) {
                $okBody = $conn->query("UPDATE test_methods SET ".implode(', ', $setParts)." WHERE id='".$methodRowId."'");
            }
            // Refuse to mark Active when the method body is still empty (unless client forced).
            $scoreRow = $methodRowId > 0 ? $conn->query("SELECT * FROM test_methods WHERE id='".$methodRowId."' LIMIT 1") : null;
            $score = 0;
            if ($scoreRow && ($sr = $scoreRow->fetch_assoc())) {
                $score = moa_method_row_content_score($sr);
            }
            $force = !empty($input['force_empty']);
            if ($score <= 0 && !$force) {
                echo json_encode(array(
                    'status' => 'failed',
                    'message' => 'No method content to save. Fill and Save at least one section (Procedure, Safety, Equipment, etc.) before Save method.',
                    'content_score' => $score,
                ));
            } else {
                $sql1 = "UPDATE spec_tests  SET method = 'Active' , ismethod = 'YES'  WHERE id ='".$spectTestId."'";
                if ($okBody && $conn->query($sql1)) {
                    echo json_encode(array('status' => 'success', 'method_row_id' => $methodRowId, 'content_score' => $score));
                } else {
                    echo json_encode(array('status' => 'failed', 'message' => $conn->error ?: 'Could not mark method active'));
                }
            }
        }
    }
    else if ($_GET["type"] == "saveTestMethodMasterDraft") {
        ensure_test_methods_moa_columns($conn);
        ensure_test_methods_status_columns($conn);

        $spectTestId = intval($input['spectTestId'] ?? 0);
        $testMasterId = intval($input['test_master_id'] ?? $_GET['id'] ?? 0);
        $moaMode = strtolower(trim((string)($input['moa_mode'] ?? '')));
        $check = trim((string)($input['check'] ?? ''));

        if ($testMasterId <= 0 && $spectTestId > 0) {
            $tmRes = $conn->query("SELECT test_master_id FROM spec_tests WHERE id='".$spectTestId."' LIMIT 1");
            if ($tmRes && ($tmRow = $tmRes->fetch_assoc())) {
                $testMasterId = intval($tmRow['test_master_id']);
            }
        }

        if ($testMasterId <= 0) {
            echo json_encode(array('status' => 'failed', 'message' => 'test_master_id required'));
        } else if ($check === '') {
            echo json_encode(array('status' => 'failed', 'message' => 'section check required'));
        } else {
            if ($spectTestId > 0 && $moaMode !== 'test') {
                $where = "spec_test_id='".$spectTestId."' AND test_id='".$testMasterId."'";
            } else {
                $where = "test_id='".$testMasterId."' AND (spec_test_id IS NULL OR spec_test_id=0 OR spec_test_id='')";
            }

            $res = $conn->query("SELECT id FROM test_methods WHERE ".$where." ORDER BY id DESC LIMIT 1");
            if ((!$res || $res->num_rows === 0) && $spectTestId > 0 && $testMasterId > 0) {
                $res = $conn->query("SELECT id FROM test_methods WHERE test_id='".$testMasterId."' ORDER BY id DESC LIMIT 1");
            }
            if (!$res || $res->num_rows === 0) {
                create_blank_test_method_row($conn, $testMasterId, ($spectTestId > 0 && $moaMode !== 'test') ? $spectTestId : 0);
                $res = $conn->query("SELECT id FROM test_methods WHERE ".$where." ORDER BY id DESC LIMIT 1");
            }
            if ((!$res || $res->num_rows === 0) && $spectTestId > 0 && $testMasterId > 0) {
                $res = $conn->query("SELECT id FROM test_methods WHERE test_id='".$testMasterId."' ORDER BY id DESC LIMIT 1");
            }

            if (!$res || $res->num_rows === 0) {
                echo json_encode(array('status' => 'failed', 'message' => 'Could not create test_methods row'));
            } else {
                $methodRowId = intval($res->fetch_assoc()['id']);
                $procedureCol = 'procedure';
                $procCheck = $conn->query("SHOW COLUMNS FROM test_methods LIKE 'Procedure'");
                if ($procCheck && $procCheck->num_rows > 0) {
                    $procedureCol = 'Procedure';
                }

                $setParts = array();
                $esc = function ($v) use ($conn) {
                    return $conn->real_escape_string($v);
                };
                $escJson = function ($v) use ($conn) {
                    $json = json_encode($v);
                    if ($json === false) {
                        $json = '[]';
                    }
                    return $conn->real_escape_string($json);
                };

                if ($check === 'General Instructions') {
                    $setParts[] = "Genral_Instruction='".$escJson($input['Genral_Instruction'] ?? [])."'";
                } else if ($check === 'Purpose') {
                    $setParts[] = "purpose='".$esc($input['purpose'] ?? '')."'";
                } else if ($check === 'Test_Solutions') {
                    $setParts[] = "Test_Solutions='".$esc($input['Test_Solutions'] ?? '')."'";
                } else if ($check === 'chromatographic_conditions') {
                    $setParts[] = "chromatographic_conditions='".$esc($input['chromatographic_conditions'] ?? '')."'";
                } else if ($check === 'standard_Solutions') {
                    $setParts[] = "standard_Solutions='".$esc($input['standard_Solutions'] ?? '')."'";
                } else if ($check === 'Scope') {
                    $setParts[] = "Scope='".$esc($input['Scope'] ?? '')."'";
                } else if ($check === 'Associate Documents') {
                    $setParts[] = "Associative_Document='".$escJson($input['Associative_Document'] ?? [])."'";
                } else if ($check === 'Reference Documents') {
                    $setParts[] = "Refrenced_Document='".$escJson($input['Refrenced_Document'] ?? [])."'";
                } else if ($check === 'Definition') {
                    $setParts[] = "defination='".$escJson($input['defination'] ?? [])."'";
                } else if ($check === 'Safety') {
                    $setParts[] = "Safety='".$esc($input['Safety'] ?? '')."'";
                } else if ($check === 'Testing Instruction') {
                    $setParts[] = "testinginstruction='".$escJson($input['testinginstruction'] ?? [])."'";
                } else if ($check === 'Procedure') {
                    $setParts[] = "`".$procedureCol."`='".$esc($input['procedure'] ?? '')."'";
                } else if ($check === 'Equipment Instruments') {
                    $setParts[] = "equipment_instruments='".$escJson($input['equipment_instruments'] ?? [])."'";
                } else if ($check === 'Chemical Reagents') {
                    $setParts[] = "chemical_reagents='".$escJson($input['chemical_reagents'] ?? [])."'";
                } else if ($check === 'Glasswares') {
                    $setParts[] = "glasswares='".$escJson($input['glasswares'] ?? [])."'";
                } else if ($check === 'Weighing Balance') {
                    $setParts[] = "balance='".$escJson($input['balance'] ?? [])."'";
                } else if ($check === 'Volumetric Solutions') {
                    $setParts[] = "volumetric_solutions='".$escJson($input['volumetric_solutions'] ?? [])."'";
                } else if ($check === 'HPLCData') {
                    $setParts[] = "phases='".$escJson($input['phases'] ?? [])."'";
                    $setParts[] = "hplc='".$escJson($input['hplc'] ?? [])."'";
                }

                if (count($setParts) === 0) {
                    echo json_encode(array('status' => 'failed', 'message' => 'unknown section'));
                } else {
                    if ($spectTestId > 0 && $moaMode !== 'test') {
                        $setParts[] = "spec_test_id='".$spectTestId."'";
                    }
                    $emp = $esc($_GET['emp_id'] ?? '');
                    $sql = "UPDATE test_methods SET ".implode(', ', $setParts).",
                            entry_by='".$emp."', entry_on='".$entry_date."'
                            WHERE id='".$methodRowId."'";
                    echo json_encode($conn->query($sql)
                        ? array('status' => 'success')
                        : array('status' => 'failed', 'message' => $conn->error));
                }
            }
        }
    }
    else if ($_GET["type"] == "getMethodDraftByTestId") {
        $testMasterId = isset($_GET["test_id"]) ? intval($_GET["test_id"]) : 0;
        if ($testMasterId <= 0) {
            echo "{}";
        } else {
            $row = fetch_latest_test_method_row($conn, $testMasterId);
            if ($row) {
                echo json_encode($row);
            } else {
                echo "{}";
            }
        }
    }
    
    else if ($_GET["type"] == "get_test_data") {
            
            $output = array();
            $reqId = intval($_GET["id"] ?? 0);
            $moaModeReq = strtolower(trim((string)($_GET["moa_mode"] ?? $_GET["moaMode"] ?? '')));

            // Test Specific: id is always test-master id (table `test`).
            if ($moaModeReq === 'test' && $reqId > 0) {
                try {
                    ensure_test_methods_moa_columns($conn);
                    $tRes = $conn->query("SELECT * FROM test WHERE id='".$reqId."' LIMIT 1");
                    if ($tRes && ($t = $tRes->fetch_assoc())) {
                        $row1 = array(
                            'spectTestId' => 0,
                            'test_master_id' => (int)$t['id'],
                            'test_id' => (int)$t['id'],
                            'test' => $t['test'] ?? '',
                            'subtest' => '',
                            'test_type' => $t['test_type'] ?? '',
                            'specification_no' => '',
                            'plant_id' => $t['plant_id'] ?? '',
                            'material_code' => '',
                            'material_name' => '',
                            'method_id' => $t['test_method_no'] ?? ('TM-' . $t['id']),
                            'moa_mode' => 'test',
                        );
                        $methods = array(
                            'Genral_Instruction' => array(),
                            'Associative_Document' => array(),
                            'Refrenced_Document' => array(),
                            'defination' => array(),
                            'testinginstruction' => array(),
                            'equipment_instruments' => array(),
                            'chemical_reagents' => array(),
                            'glasswares' => array(),
                            'balance' => array(),
                            'dilutions' => array(),
                            'volumetric_solutions' => array(),
                            'hplc' => array(),
                            'phases' => array(),
                            'revision_history' => array(),
                            'chromatographic_conditions' => '',
                            'Test_Solutions' => '',
                            'standard_Solutions' => '',
                            'purpose' => '',
                            'Scope' => '',
                            'procedure' => '',
                            'Safety' => '',
                        );
                        $mRes = @$conn->query("SELECT * FROM test_methods WHERE test_id='".$reqId."' AND (spec_test_id IS NULL OR spec_test_id=0 OR spec_test_id='') ORDER BY id DESC LIMIT 1");
                        if ((!$mRes || $mRes->num_rows === 0)) {
                            $mRes = @$conn->query("SELECT * FROM test_methods WHERE test_id='".$reqId."' ORDER BY id DESC LIMIT 1");
                        }
                        if ($mRes && ($mm = $mRes->fetch_assoc())) {
                            $methods = normalize_test_method_row_for_moa($mm);
                        } else {
                            create_blank_test_method_row($conn, $reqId, 0);
                            $mRes2 = @$conn->query("SELECT * FROM test_methods WHERE test_id='".$reqId."' ORDER BY id DESC LIMIT 1");
                            if ($mRes2 && ($mm = $mRes2->fetch_assoc())) {
                                $methods = normalize_test_method_row_for_moa($mm);
                            }
                        }
                        $row1['methods'] = $methods;
                        echo json_encode($row1, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    echo json_encode(array('status' => 'failed', 'message' => 'Test master not found', 'moa_mode' => 'test'));
                    exit;
                } catch (Throwable $e) {
                    echo json_encode(array(
                        'status' => 'failed',
                        'message' => 'Unable to load test method: '.$e->getMessage(),
                        'moa_mode' => 'test',
                        'spectTestId' => 0,
                        'test_master_id' => $reqId,
                    ));
                    exit;
                }
            }

            // Fetch by primary key only. spec_tests.id is unique, so filtering by
            // plant_id here is unnecessary and actively breaks the form when the
            // caller's active plant context (localStorage) differs from the row's
            // plant_id: the query would return no row, the frontend would still
            // render the form (isView=true) with an undefined spectTestId, and every
            // save would then POST an empty id and silently persist nothing.
            $sql1 = "SELECT id as spectTestId, test_master_id, subtest, test, specification_no, test_type, plant_id  
                     FROM spec_tests 
                     WHERE id = '" . $conn->real_escape_string($_GET["id"]) . "' 
                     LIMIT 1";
            
            $result1 = $conn->query($sql1);
            if ($result1 && $result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {

                    // Header enrichment: material details + generated Method ID.
                    // Method ID format: MOA/nnn/Tnnn where nnn = specification serial
                    // (zero-padded id) and Tnnn = the test's ordinal within that
                    // specification (ordered by spec_tests.id). Deterministic, so it
                    // stays stable across reloads without needing a stored column.
                    $specNoEsc = $conn->real_escape_string($row1["specification_no"]);
                    $plantEsc  = $conn->real_escape_string($row1["plant_id"] ?? '');
                    $stIdEsc   = $conn->real_escape_string($row1["spectTestId"]);

                    $specId = "";
                    $materialCode = "";
                    $materialName = "";
                    $specRes = $conn->query("SELECT sp.id AS spec_id, sp.material_code, m.material_name
                                             FROM specification sp
                                             LEFT JOIN material m ON m.material_code = sp.material_code
                                             WHERE sp.specification_no = '$specNoEsc'
                                             ORDER BY sp.id DESC LIMIT 1");
                    if ($specRes && ($specRow = $specRes->fetch_assoc())) {
                        $specId = $specRow["spec_id"];
                        $materialCode = $specRow["material_code"];
                        $materialName = $specRow["material_name"];
                    }

                    $ordinal = 1;
                    $ordPlantFilter = ($plantEsc !== '') ? " AND plant_id = '$plantEsc'" : "";
                    $ordRes = $conn->query("SELECT COUNT(*) AS c FROM spec_tests
                                            WHERE specification_no = '$specNoEsc'"
                                            . $ordPlantFilter . "
                                            AND id <= '$stIdEsc'");
                    if ($ordRes && ($ordRow = $ordRes->fetch_assoc())) {
                        $ordinal = (int)$ordRow["c"];
                        if ($ordinal < 1) { $ordinal = 1; }
                    }

                    $nnn = str_pad((string)$specId, 3, "0", STR_PAD_LEFT);
                    $tno = str_pad((string)$ordinal, 3, "0", STR_PAD_LEFT);
                    $row1["material_code"] = $materialCode;
                    $row1["material_name"] = $materialName;
                    $row1["test_id"] = $row1["spectTestId"];
                    $row1["method_id"] = "MOA/" . $nnn . "/T" . $tno;

                    $spectId = intval($row1["spectTestId"]);
                    $tmId = intval($row1["test_master_id"]);
                    $output1 = moa_fetch_method_data_for_spec_test($conn, $spectId, $tmId);
                    $linkRes = @$conn->query(
                        "SELECT id FROM test_methods WHERE spec_test_id='".$spectId."' AND test_id='".$tmId."' LIMIT 1"
                    );
                    if ((!$linkRes || $linkRes->num_rows === 0) && moa_method_row_content_score($output1) === 0) {
                        create_blank_test_method_row($conn, $tmId, $spectId);
                        $output1 = moa_fetch_method_data_for_spec_test($conn, $spectId, $tmId);
                    }
                    $row1["methods"] = $output1;
                    $output = $row1;
                }
            }

            // Test Specific (GTP): id is test-master id when no spec_tests row exists.
            if (empty($output) || (is_array($output) && count($output) === 0)) {
                $tid = intval($_GET["id"] ?? 0);
                if ($tid > 0) {
                    $tRes = $conn->query("SELECT * FROM test WHERE id='".$tid."' LIMIT 1");
                    if ($tRes && ($t = $tRes->fetch_assoc())) {
                        $row1 = array(
                            'spectTestId' => 0,
                            'test_master_id' => (int)$t['id'],
                            'test_id' => (int)$t['id'],
                            'test' => $t['test'] ?? '',
                            'subtest' => '',
                            'test_type' => $t['test_type'] ?? '',
                            'specification_no' => '',
                            'plant_id' => $t['plant_id'] ?? '',
                            'material_code' => '',
                            'material_name' => '',
                            'method_id' => $t['test_method_no'] ?? ('TM-' . $t['id']),
                            'moa_mode' => 'test',
                        );
                        $methods = new stdClass();
                        $mRes = $conn->query("SELECT * FROM test_methods WHERE test_id='".$tid."' AND (spec_test_id IS NULL OR spec_test_id=0 OR spec_test_id='') ORDER BY id DESC LIMIT 1");
                        if ((!$mRes || $mRes->num_rows === 0)) {
                            $mRes = $conn->query("SELECT * FROM test_methods WHERE test_id='".$tid."' ORDER BY id DESC LIMIT 1");
                        }
                        if ($mRes && ($mm = $mRes->fetch_assoc())) {
                            $methods = normalize_test_method_row_for_moa($mm);
                        } else {
                            create_blank_test_method_row($conn, $tid, 0);
                            $mRes2 = $conn->query("SELECT * FROM test_methods WHERE test_id='".$tid."' ORDER BY id DESC LIMIT 1");
                            if ($mRes2 && ($mm = $mRes2->fetch_assoc())) {
                                $methods = normalize_test_method_row_for_moa($mm);
                            }
                        }
                        $row1['methods'] = $methods;
                        $output = $row1;
                    }
                }
            }
            
            echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        
    }
    else if ($_GET["type"] == "updateTestMasterMethodWorkflow") {
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        $action = trim((string)($input['action'] ?? $_GET['action'] ?? ''));
        $remark = $conn->real_escape_string(trim((string)($input['remark'] ?? '')));
        if ($id <= 0 || $action === '') {
            echo json_encode(['status' => 'failed', 'message' => 'id and action required']);
        } else {
            $status = '';
            if ($action === 'submit' || $action === 'resend') {
                $status = 'For Checking';
            } else if ($action === 'forward') {
                $status = 'For Approval';
            } else if ($action === 'approve') {
                $status = 'approve';
            } else if ($action === 'reject') {
                $status = 'Correction';
            }
            if ($status === '') {
                echo json_encode(['status' => 'failed', 'message' => 'unknown action']);
            } else {
                $sql = "UPDATE test SET method='".$conn->real_escape_string($status)."' WHERE id='".$id."'";
                echo json_encode($conn->query($sql) ? ['status' => 'success', 'method' => $status] : ['status' => 'failed', 'message' => $conn->error]);
            }
        }
    }
    // ---- REUSABLE METHOD DETECTION ------------------------------------------
    // When opening the Add Method form, detect if the SAME master test (test_id)
    // already has a method prepared for another material/product in any other
    // specification. Returns candidate methods so the user can copy one into the
    // new form, review and save.
    else if ($_GET["type"] == "findReusableMethods") {
        $tmid = $conn->real_escape_string($_GET["test_master_id"] ?? '');
        $cur  = $conn->real_escape_string($_GET["spec_test_id"] ?? '');
        $output = array();
        if ($tmid !== '') {
            $sql = "SELECT st.id AS spec_test_id, st.specification_no, st.test, st.subtest, st.method, st.method_wf_status,
                        (SELECT sp.material_code FROM specification sp WHERE sp.specification_no = st.specification_no ORDER BY sp.id DESC LIMIT 1) AS material_code,
                        (SELECT sp.spec_type FROM specification sp WHERE sp.specification_no = st.specification_no ORDER BY sp.id DESC LIMIT 1) AS spec_type,
                        (SELECT COALESCE(m.material_name, pr.product_name)
                            FROM specification sp
                            LEFT JOIN material m ON m.material_code = sp.material_code
                            LEFT JOIN product pr ON pr.product_code = sp.product_code
                            WHERE sp.specification_no = st.specification_no ORDER BY sp.id DESC LIMIT 1) AS material_name,
                        (SELECT tm.entry_on FROM test_methods tm WHERE tm.spec_test_id = st.id ORDER BY tm.id DESC LIMIT 1) AS prepared_on,
                        (SELECT COALESCE(NULLIF(TRIM(CONCAT(COALESCE(e.firstname,''),' ',COALESCE(e.lastname,''))), ''), tm2.entry_by)
                            FROM test_methods tm2 LEFT JOIN employee e ON e.emp_id = tm2.entry_by
                            WHERE tm2.spec_test_id = st.id ORDER BY tm2.id DESC LIMIT 1) AS prepared_by
                    FROM spec_tests st
                    WHERE st.test_master_id = '$tmid'
                      AND st.id <> '$cur'
                      AND st.ismethod = 'YES'
                      AND EXISTS (SELECT 1 FROM test_methods tmx WHERE tmx.spec_test_id = st.id)
                      AND st.id = (SELECT MAX(st2.id) FROM spec_tests st2
                                     WHERE st2.specification_no = st.specification_no
                                       AND st2.test_master_id = '$tmid'
                                       AND COALESCE(st2.subtest,'') = COALESCE(st.subtest,'')
                                       AND st2.ismethod = 'YES')
                    ORDER BY (LOWER(COALESCE(st.method,'')) IN ('approve','approved')) DESC, st.id DESC
                    LIMIT 25";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                while ($r = $res->fetch_assoc()) {
                    $output[] = $r;
                }
            }
        }
        echo json_encode($output, JSON_UNESCAPED_UNICODE);
    }
    
    else if ($_GET["type"] == "getPendingSpecForMoa") {
            $output = Array();
            $matType = isset($_GET["matType"]) ? $_GET["matType"] : '';
            $matTypeMap = Array(
                'Raw Material' => 'Raw Material Specification',
                'Packing Material' => 'Packing Material Specification',
                'Finish Product' => 'Finish Product',
                'Inprocess Material' => 'Inprocess Specification',
                'Inprocess Specification' => 'Inprocess Specification',
                'Stability' => 'Stability Specification',
                'Retest' => 'Retest Specification',
                'Water' => 'Water Specification'
            );
            $specType = isset($matTypeMap[$matType]) ? $matTypeMap[$matType] : $matType;
            $listOnly = isset($_GET["listOnly"]) && $_GET["listOnly"] === '1';
            // Specifications are saved with the SHORT spec_type (e.g. 'Raw Material',
            // 'Packing Material') by saveSpecification, but this list historically only
            // matched the LONG form ('Raw Material Specification'). That mismatch hid
            // every newly created raw/packing spec from the MOA queue. Accept both the
            // mapped long form and the base material type so approved specs surface here.
            $specTypeEsc = $conn->real_escape_string($specType);
            $matTypeEsc = $conn->real_escape_string($matType);
            $plantEsc = $conn->real_escape_string($_GET["plant_id"] ?? '');
            // Master MOA: matType All / empty → all approved specs for the plant
            if ($matType === '' || strcasecmp($matType, 'All') === 0 || strcasecmp($matType, 'all') === 0) {
                $specTypeFilter = "1=1";
            } else {
                $specTypeFilter = "(a.spec_type = '".$specTypeEsc."' OR a.spec_type = '".$matTypeEsc."')";
            }
            
           $includeCompleted = isset($_GET["includeCompleted"]) && $_GET["includeCompleted"] === '1';
           // Treat NULL/empty ismoa as pending — many approved specs never set ismoa.
           $ismoaFilter = $includeCompleted
               ? "(a.ismoa IS NULL OR a.ismoa = '' OR LOWER(a.ismoa) IN ('pending','approve','approved'))"
               : "(a.ismoa IS NULL OR a.ismoa = '' OR LOWER(a.ismoa) = 'pending')";
           $statusFilter = "(a.status IS NULL OR a.status = '' OR LOWER(a.status) IN ('approve','approved','checked','active'))";
           $plantFilter = ($plantEsc !== '')
               ? "(a.plant_id = '".$plantEsc."' OR a.plant_id IS NULL OR a.plant_id = '')"
               : "1=1";
           $sql = "SELECT a.id, a.specification_no, a.version_no, a.material_code, a.spec_type, a.supersede_no, a.storage, a.storage_condition, a.ismoa, a.status, a.plant_id,
            b.material_subtype, b.material_name, b.grade,
            (SELECT GROUP_CONCAT(g.grade) FROM grade g WHERE FIND_IN_SET(g.id, b.grade)) AS gradeName,
            (SELECT COUNT(*) FROM spec_tests st WHERE st.specification_no = a.specification_no) AS test_count,
            (SELECT COUNT(*) FROM spec_tests st WHERE st.specification_no = a.specification_no AND (LOWER(st.method)='approve' OR LOWER(st.method)='approved')) AS completed_method_count
            FROM specification a 
            left JOIN material b on a.material_code = b.material_code WHERE ".$ismoaFilter." AND ".$statusFilter." AND 
            ".$specTypeFilter." AND ".$plantFilter." AND LOWER(COALESCE(a.status,'')) NOT IN ('reject','rejected')
            order by a.id desc";
            
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    if (!$listOnly) {
                        $output1 = Array();
                        $sql1 = "SELECT id, test_type, test, subtest, description, method, reference_type, specification_no, ismethod, method_wf_status, method_log_status FROM spec_tests WHERE specification_no='".$row["specification_no"]."' ";
                        $result1 = $conn->query($sql1);
                        if ($result1 && $result1->num_rows > 0) {
                            while ($row1 = $result1->fetch_assoc()) {
                                if (!empty($row1['reference_type'])) {
                                    $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade where id in ('".str_replace(",", "','", $row1['reference_type'])."')";
                                    $resQ = $conn->query($q);
                                    if ($resQ && $resQ->num_rows > 0) {
                                        $prodLatest = $resQ->fetch_assoc();
                                        $row1['gradeName'] = $prodLatest['gradeName'];
                                    }
                                }
                                $output1[] = $row1;
                            }
                        }
                        $row["tests"] = $output1;
                        if (empty($row['gradeName'])) {
                            $row['gradeName'] = getGrdeValue($row['grade'], $conn);
                        }
                    }
                    if ($row['ismoa'] === null || $row['ismoa'] === '') {
                        $row['ismoa'] = 'pending';
                    }
                    $output[] = $row;
                }
            }
            // Last resort: plant has specs but filters excluded them all
            if (count($output) === 0 && $plantEsc !== '') {
                $sqlFb = "SELECT a.id, a.specification_no, a.version_no, a.material_code, a.spec_type, a.supersede_no, a.storage, a.storage_condition,
                    COALESCE(NULLIF(a.ismoa,''),'pending') AS ismoa, a.status, a.plant_id,
                    b.material_subtype, b.material_name, b.grade,
                    (SELECT GROUP_CONCAT(g.grade) FROM grade g WHERE FIND_IN_SET(g.id, b.grade)) AS gradeName,
                    (SELECT COUNT(*) FROM spec_tests st WHERE st.specification_no = a.specification_no) AS test_count,
                    (SELECT COUNT(*) FROM spec_tests st WHERE st.specification_no = a.specification_no AND (LOWER(st.method)='approve' OR LOWER(st.method)='approved')) AS completed_method_count
                    FROM specification a
                    LEFT JOIN material b ON a.material_code = b.material_code
                    WHERE a.plant_id = '".$plantEsc."'
                      AND LOWER(COALESCE(a.status,'')) NOT IN ('reject','rejected')
                    ORDER BY a.id DESC LIMIT 300";
                $fb = $conn->query($sqlFb);
                if ($fb && $fb->num_rows > 0) {
                    while ($row = $fb->fetch_assoc()) {
                        $output[] = $row;
                    }
                }
            }
            echo json_encode($output);
    }
    else if ($_GET["type"] == "getSpecsForMoa") {
        // Master MOA — Specification Specific: broad plant list for the Proceed tab
        // Do NOT join specification.product_code — that column does not exist on Medicap.
        header('Content-Type: application/json; charset=utf-8');
        $output = array();
        $plantEsc = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $moaCountsSql = ",
                       (SELECT COUNT(*) FROM spec_tests st WHERE st.specification_no = a.specification_no) AS test_count,
                       (SELECT COUNT(*) FROM spec_tests st WHERE st.specification_no = a.specification_no AND UPPER(COALESCE(st.ismethod,''))='YES') AS prepared_method_count,
                       (SELECT COUNT(*) FROM spec_tests st WHERE st.specification_no = a.specification_no
                            AND (LOWER(COALESCE(st.method,'')) IN ('approve','approved') OR LOWER(COALESCE(st.method_wf_status,''))='approved')) AS completed_method_count";
        $sql = "SELECT a.id, a.specification_no, a.version_no, a.material_code, a.spec_type, a.supersede_no,
                       a.storage, a.storage_condition, COALESCE(NULLIF(a.ismoa,''),'pending') AS ismoa, a.status, a.plant_id,
                       COALESCE(a.moa_wf_status,'') AS moa_wf_status,
                       COALESCE(b.material_subtype, '') AS material_subtype,
                       COALESCE(b.material_name, p.product_name, '') AS material_name,
                       b.grade,
                       (SELECT GROUP_CONCAT(g.grade) FROM grade g WHERE FIND_IN_SET(g.id, b.grade)) AS gradeName
                       ".$moaCountsSql."
                FROM specification a
                LEFT JOIN material b ON a.material_code = b.material_code
                LEFT JOIN product p ON a.material_code = p.product_code
                WHERE (a.plant_id = '".$plantEsc."' OR '".$plantEsc."' = '')
                  AND LOWER(COALESCE(a.status,'')) NOT IN ('reject','rejected')
                ORDER BY a.id DESC
                LIMIT 500";
        $result = @$conn->query($sql);
        if (!$result) {
            $sql = "SELECT a.id, a.specification_no, a.version_no, a.material_code, a.spec_type, a.supersede_no,
                       a.storage, a.storage_condition, COALESCE(NULLIF(a.ismoa,''),'pending') AS ismoa, a.status, a.plant_id,
                       COALESCE(b.material_subtype, '') AS material_subtype,
                       COALESCE(b.material_name, '') AS material_name,
                       b.grade,
                       (SELECT GROUP_CONCAT(g.grade) FROM grade g WHERE FIND_IN_SET(g.id, b.grade)) AS gradeName
                       ".$moaCountsSql."
                FROM specification a
                LEFT JOIN material b ON a.material_code = b.material_code
                WHERE (a.plant_id = '".$plantEsc."' OR '".$plantEsc."' = '')
                  AND LOWER(COALESCE(a.status,'')) NOT IN ('reject','rejected')
                ORDER BY a.id DESC
                LIMIT 500";
            $result = @$conn->query($sql);
        }
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Repair placeholder Spec Nos that were saved when auto-fetch failed.
                $specNoRaw = trim((string)($row['specification_no'] ?? ''));
                if ($specNoRaw === '' || stripos($specNoRaw, 'Auto Generated') !== false) {
                    $specType = trim((string)($row['spec_type'] ?? ''));
                    $matType = 'FG';
                    if ($specType === 'Raw Material' || stripos($specType, 'Raw') === 0) {
                        $matType = 'RMS';
                    } else if ($specType === 'Packing Material' || stripos($specType, 'Pack') === 0) {
                        $matType = 'PMS';
                    }
                    $fixedNo = 'SC/QCD/' . $matType . '/' . intval($row['id']);
                    $oldEsc = $conn->real_escape_string($specNoRaw);
                    $newEsc = $conn->real_escape_string($fixedNo);
                    $idEsc = intval($row['id']);
                    $clash = @$conn->query("SELECT id FROM specification WHERE specification_no='$newEsc' AND id<>$idEsc LIMIT 1");
                    if (!$clash || $clash->num_rows === 0) {
                        @$conn->query("UPDATE specification SET specification_no='$newEsc' WHERE id=$idEsc");
                        if ($oldEsc !== '') {
                            @$conn->query("UPDATE spec_tests SET specification_no='$newEsc' WHERE specification_no='$oldEsc'");
                            @$conn->query("UPDATE spec_revision SET specification_no='$newEsc' WHERE specification_no='$oldEsc'");
                        }
                        $row['specification_no'] = $fixedNo;
                    }
                }
                $prepared = (int)($row['prepared_method_count'] ?? 0);
                $done = (int)($row['completed_method_count'] ?? 0);
                $total = (int)($row['test_count'] ?? 0);
                $moaWf = strtolower(trim((string)($row['moa_wf_status'] ?? '')));
                $ismoaVal = strtolower(trim((string)($row['ismoa'] ?? '')));
                $target = $prepared > 0 ? $prepared : $total;
                $isComplete = ($moaWf === 'approved')
                    || ($ismoaVal === 'approve' || $ismoaVal === 'approved')
                    || ($target > 0 && $done >= $target);
                if ($isComplete) {
                    $row['ismoa'] = 'approve';
                    if ($moaWf !== 'approved') {
                        $specEsc = $conn->real_escape_string($row['specification_no']);
                        @$conn->query("UPDATE specification SET ismoa='approve', moa_wf_status='approved'
                            WHERE specification_no='$specEsc'
                            AND (ismoa IS NULL OR ismoa='' OR LOWER(ismoa)='pending')");
                        $row['moa_wf_status'] = 'approved';
                    }
                }
                $output[] = $row;
            }
        }
        if (count($output) === 0) {
            $result2 = @$conn->query("SELECT a.id, a.specification_no, a.version_no, a.material_code, a.spec_type,
                       COALESCE(NULLIF(a.ismoa,''),'pending') AS ismoa, a.status, a.plant_id,
                       COALESCE(b.material_name, '') AS material_name, b.material_subtype, b.grade
                       ".$moaCountsSql."
                FROM specification a
                LEFT JOIN material b ON a.material_code = b.material_code
                WHERE LOWER(COALESCE(a.status,'')) NOT IN ('reject','rejected')
                ORDER BY a.id DESC LIMIT 500");
            if ($result2 && $result2->num_rows > 0) {
                while ($row = $result2->fetch_assoc()) {
                    $prepared = (int)($row['prepared_method_count'] ?? 0);
                    $done = (int)($row['completed_method_count'] ?? 0);
                    $total = (int)($row['test_count'] ?? 0);
                    $target = $prepared > 0 ? $prepared : $total;
                    if ($target > 0 && $done >= $target) {
                        $row['ismoa'] = 'approve';
                        $specEsc = $conn->real_escape_string($row['specification_no']);
                        @$conn->query("UPDATE specification SET ismoa='approve', moa_wf_status='approved'
                            WHERE specification_no='$specEsc'
                            AND (ismoa IS NULL OR ismoa='' OR LOWER(ismoa)='pending')");
                        $row['moa_wf_status'] = 'approved';
                    }
                    $output[] = $row;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getMoaSpecTests") {
        header('Content-Type: application/json; charset=utf-8');
        ensure_test_methods_status_columns($conn);
        ensure_test_methods_moa_columns($conn);
        $output = Array();
        $specNo = trim($_GET["specification_no"] ?? '');
        $includeDetails = isset($_GET["includeDetails"]) && $_GET["includeDetails"] === '1';
        if ($specNo !== '') {
            $specEsc = $conn->real_escape_string($specNo);
            $plantEsc = $conn->real_escape_string($_GET["plant_id"] ?? '');
            $specMatch = "(TRIM(specification_no)='".$specEsc."' OR specification_no='".$specEsc."')";
            $wherePlant = $specMatch;
            if ($plantEsc !== '') {
                $wherePlant .= " AND (plant_id='".$plantEsc."' OR plant_id IS NULL OR plant_id='')";
            }

            $includeMethodData = isset($_GET['include_method_data']) && $_GET['include_method_data'] === '1';
            $testsWhere = $includeMethodData ? $specMatch : $wherePlant;

            $fullSql = "SELECT spec_tests.id, spec_tests.test_type, spec_tests.test, spec_tests.subtest, spec_tests.description,
                        spec_tests.method, spec_tests.reference_type, spec_tests.specification_no, spec_tests.ismethod,
                        spec_tests.test_master_id,
                        spec_tests.method_wf_status, spec_tests.method_log_status, spec_tests.method_details,
                        (SELECT tm.entry_on FROM test_methods tm WHERE tm.spec_test_id = spec_tests.id
                            ORDER BY tm.id DESC LIMIT 1) AS prepared_on,
                        (SELECT COALESCE(NULLIF(TRIM(CONCAT(COALESCE(e.firstname,''), ' ', COALESCE(e.lastname,''))), ''), tm2.entry_by)
                            FROM test_methods tm2 LEFT JOIN employee e ON e.emp_id = tm2.entry_by
                            WHERE tm2.spec_test_id = spec_tests.id ORDER BY tm2.id DESC LIMIT 1) AS prepared_by
                     FROM spec_tests WHERE ".$testsWhere." ORDER BY id ASC";
            $simpleSql = "SELECT id, test_type, test, subtest, description, method, reference_type, specification_no, ismethod, test_master_id
                         FROM spec_tests WHERE ".$testsWhere." ORDER BY id ASC";
            $anyPlantSql = "SELECT id, test_type, test, subtest, description, method, reference_type, specification_no, ismethod, test_master_id
                         FROM spec_tests WHERE ".$specMatch." ORDER BY id ASC";

            $result1 = @$conn->query($fullSql);
            if (!$result1 || $result1->num_rows === 0) {
                $result1 = @$conn->query($simpleSql);
            }
            if ((!$result1 || $result1->num_rows === 0) && !$includeMethodData && $plantEsc !== '') {
                $result1 = @$conn->query($anyPlantSql);
            }
            if ((!$result1 || $result1->num_rows === 0) && $includeMethodData) {
                $result1 = @$conn->query($anyPlantSql);
            }
            if ($result1 && $result1->num_rows > 0) {
                while ($row1 = $result1->fetch_assoc()) {
                    if ($includeDetails && !empty($row1['method_details'])) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                    } else {
                        unset($row1["method_details"]);
                    }
                    if (!empty($row1['reference_type'])) {
                        $refIds = preg_replace('/[^0-9,]/', '', $row1['reference_type']);
                        if ($refIds !== '') {
                            $q = "SELECT GROUP_CONCAT(grade) as gradeName FROM grade where id in ('".str_replace(",", "','", $refIds)."')";
                            $resQ = $conn->query($q);
                            if ($resQ && $resQ->num_rows > 0) {
                                $prodLatest = $resQ->fetch_assoc();
                                $row1['gradeName'] = $prodLatest['gradeName'];
                            }
                        }
                    }
                    if (isset($_GET['include_method_data']) && $_GET['include_method_data'] === '1') {
                        $stId = intval($row1['id'] ?? 0);
                        $tmId = intval($row1['test_master_id'] ?? 0);
                        $row1['method_data'] = moa_fetch_method_data_for_spec_test($conn, $stId, $tmId);
                    }
                    $output[] = $row1;
                }
            }
        }
        echo json_encode($output);
    }
    else if ($_GET["type"] == "getMoaMethodForSpecTest") {
        header('Content-Type: application/json; charset=utf-8');
        ensure_test_methods_moa_columns($conn);
        $output = array();
        $specTestId = intval($_GET["spec_test_id"] ?? 0);
        if ($specTestId <= 0) {
            echo json_encode($output);
        } else {
            $res = $conn->query("SELECT * FROM spec_tests WHERE id='".$specTestId."' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                if (!empty($row['method_details'])) {
                    $decoded = json_decode($row['method_details'], true);
                    $row['method_details'] = is_array($decoded) ? $decoded : array();
                } else {
                    $row['method_details'] = array();
                }
                $tmId = intval($row['test_master_id'] ?? 0);
                $row['method_data'] = moa_fetch_method_data_for_spec_test($conn, $specTestId, $tmId);
                $output = $row;
            }
            echo json_encode($output);
        }
    }
    // ---- MOA COMMON (document-level) SECTIONS -------------------------------
    // General Instructions, Purpose, Standard/Test Solutions, Scope, Associate /
    // Reference Documents and Definitions are common to the whole Method of
    // Analysis document (one specification), not per test. They are stored here
    // keyed by specification_no + plant_id and edited from the MOA proceed page.
    else if ($_GET["type"] == "getMoaCommon" || $_GET["type"] == "saveMoaCommon") {
        if (!function_exists('moa_ensure_common_table')) {
            function moa_ensure_common_table($conn) {
                $conn->query("CREATE TABLE IF NOT EXISTS moa_common_sections (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    specification_no VARCHAR(150) NOT NULL,
                    plant_id VARCHAR(50) NOT NULL DEFAULT '',
                    Genral_Instruction LONGTEXT,
                    purpose LONGTEXT,
                    standard_Solutions LONGTEXT,
                    Test_Solutions LONGTEXT,
                    Scope LONGTEXT,
                    Associative_Document LONGTEXT,
                    Refrenced_Document LONGTEXT,
                    defination LONGTEXT,
                    entry_by VARCHAR(100) DEFAULT NULL,
                    entry_on DATETIME DEFAULT NULL,
                    updated_on DATETIME DEFAULT NULL,
                    UNIQUE KEY uniq_spec_plant (specification_no, plant_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        }
        moa_ensure_common_table($conn);

        $specNo = $conn->real_escape_string(($_GET["specification_no"] ?? ($input["specification_no"] ?? '')));
        $plant  = $conn->real_escape_string($_GET["plant_id"] ?? '');
        $emp    = $conn->real_escape_string($_GET["emp_id"] ?? '');

        if ($specNo === '') {
            echo json_encode(["status" => "failed", "message" => "specification_no required"]);
        } else {
            // Ensure a row exists for this specification/plant.
            $chk = $conn->query("SELECT id FROM moa_common_sections WHERE specification_no='$specNo' AND plant_id='$plant' LIMIT 1");
            if (!$chk || $chk->num_rows === 0) {
                $conn->query("INSERT INTO moa_common_sections
                    (specification_no, plant_id, Genral_Instruction, purpose, standard_Solutions, Test_Solutions, Scope, Associative_Document, Refrenced_Document, defination, entry_by, entry_on)
                    VALUES ('$specNo','$plant','[]','NA','NA','NA','NA','[]','[]','[]','$emp','$entry_date')");
            }

            if ($_GET["type"] == "saveMoaCommon") {
                $check = $input["check"] ?? '';
                $set = '';
                if ($check == 'General Instructions') {
                    $set = "Genral_Instruction='".$conn->real_escape_string(json_encode($input["Genral_Instruction"]))."'";
                } else if ($check == 'Purpose') {
                    $set = "purpose='".$conn->real_escape_string($input["purpose"])."'";
                } else if ($check == 'standard_Solutions') {
                    $set = "standard_Solutions='".$conn->real_escape_string($input["standard_Solutions"])."'";
                } else if ($check == 'Test_Solutions') {
                    $set = "Test_Solutions='".$conn->real_escape_string($input["Test_Solutions"])."'";
                } else if ($check == 'Scope') {
                    $set = "Scope='".$conn->real_escape_string($input["Scope"])."'";
                } else if ($check == 'Associate Documents') {
                    $set = "Associative_Document='".$conn->real_escape_string(json_encode($input["Associative_Document"]))."'";
                } else if ($check == 'Reference Documents') {
                    $set = "Refrenced_Document='".$conn->real_escape_string(json_encode($input["Refrenced_Document"]))."'";
                } else if ($check == 'Definition') {
                    $set = "defination='".$conn->real_escape_string(json_encode($input["defination"]))."'";
                }
                if ($set === '') {
                    echo json_encode(["status" => "failed", "message" => "unknown section"]);
                } else {
                    $sql = "UPDATE moa_common_sections SET $set, updated_on='$entry_date'
                            WHERE specification_no='$specNo' AND plant_id='$plant'";
                    echo json_encode($conn->query($sql) ? ["status" => "success"] : ["status" => $conn->error]);
                }
            } else {
                // getMoaCommon — return the decoded row.
                $res = $conn->query("SELECT * FROM moa_common_sections WHERE specification_no='$specNo' AND plant_id='$plant' LIMIT 1");
                $row = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : array();
                if ($row) {
                    $row["Genral_Instruction"]   = ($row["Genral_Instruction"]   !== null && $row["Genral_Instruction"]   !== '') ? json_decode($row["Genral_Instruction"], true)   : [];
                    $row["Associative_Document"] = ($row["Associative_Document"] !== null && $row["Associative_Document"] !== '') ? json_decode($row["Associative_Document"], true) : [];
                    $row["Refrenced_Document"]   = ($row["Refrenced_Document"]   !== null && $row["Refrenced_Document"]   !== '') ? json_decode($row["Refrenced_Document"], true)   : [];
                    $row["defination"]           = ($row["defination"]           !== null && $row["defination"]           !== '') ? json_decode($row["defination"], true)           : [];
                    $row["purpose"]            = ($row["purpose"]            !== null) ? htmlspecialchars_decode($row["purpose"], ENT_QUOTES)            : "NA";
                    $row["standard_Solutions"] = ($row["standard_Solutions"] !== null) ? htmlspecialchars_decode($row["standard_Solutions"], ENT_QUOTES) : "NA";
                    $row["Test_Solutions"]     = ($row["Test_Solutions"]     !== null) ? htmlspecialchars_decode($row["Test_Solutions"], ENT_QUOTES)     : "NA";
                    $row["Scope"]              = ($row["Scope"]              !== null) ? htmlspecialchars_decode($row["Scope"], ENT_QUOTES)              : "NA";
                }
                echo json_encode($row ?: new stdClass());
            }
        }
    }
    // ---- COMPLETE METHOD OF ANALYSIS DOCUMENT --------------------------------
    // Assembles the whole MOA document for one specification: specification
    // header + material details, the document-level common sections, and every
    // test with its full decoded method (test_methods row). Used by the
    // "View Complete Method" page and by checkers/approvers to review the entire
    // document at once.
    else if ($_GET["type"] == "getCompleteMethod") {
        if (!function_exists('moa_decode_test_method')) {
            function moa_decode_test_method($row2) {
                if ((!isset($row2['procedure']) || $row2['procedure'] === null || $row2['procedure'] === '') && isset($row2['Procedure'])) {
                    $row2['procedure'] = $row2['Procedure'];
                }
                $jsonFields = ['Genral_Instruction','Associative_Document','Refrenced_Document','defination',
                    'testinginstruction','equipment_instruments','chemical_reagents','glasswares','balance',
                    'dilutions','volumetric_solutions','hplc','phases','revision_history'];
                foreach ($jsonFields as $f) {
                    $row2[$f] = (isset($row2[$f]) && $row2[$f] !== null && $row2[$f] !== '') ? @json_decode($row2[$f], true) : [];
                    if (!is_array($row2[$f])) { $row2[$f] = []; }
                }
                $textFields = ['chromatographic_conditions','Test_Solutions','standard_Solutions','purpose','Scope','procedure','Safety'];
                foreach ($textFields as $f) {
                    $row2[$f] = (isset($row2[$f]) && $row2[$f] !== null && $row2[$f] !== '' && $row2[$f] !== 'NA')
                        ? htmlspecialchars_decode($row2[$f], ENT_QUOTES) : '';
                }
                return $row2;
            }
        }
        if (!function_exists('moa_ensure_common_table')) {
            function moa_ensure_common_table($conn) {
                $conn->query("CREATE TABLE IF NOT EXISTS moa_common_sections (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    specification_no VARCHAR(150) NOT NULL,
                    plant_id VARCHAR(50) NOT NULL DEFAULT '',
                    Genral_Instruction LONGTEXT, purpose LONGTEXT, standard_Solutions LONGTEXT,
                    Test_Solutions LONGTEXT, Scope LONGTEXT, Associative_Document LONGTEXT,
                    Refrenced_Document LONGTEXT, defination LONGTEXT,
                    entry_by VARCHAR(100) DEFAULT NULL, entry_on DATETIME DEFAULT NULL, updated_on DATETIME DEFAULT NULL,
                    UNIQUE KEY uniq_spec_plant (specification_no, plant_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        }

        $specNo = $conn->real_escape_string($_GET["specification_no"] ?? '');
        $out = ["specification" => new stdClass(), "common" => new stdClass(), "tests" => []];

        if ($specNo !== '') {
            // Specification header + material details.
            $spRes = $conn->query("SELECT * FROM specification WHERE specification_no='$specNo' ORDER BY id DESC LIMIT 1");
            if ($spRes && ($sp = $spRes->fetch_assoc())) {
                $mRes = $conn->query("SELECT material_name, material_type, material_subtype, grade FROM material WHERE material_code='".$conn->real_escape_string($sp['material_code'])."' LIMIT 1");
                if ($mRes && ($m = $mRes->fetch_assoc())) {
                    $sp['material_name'] = $m['material_name'];
                    $sp['material_type'] = $m['material_type'];
                    $sp['material_subtype'] = $m['material_subtype'];
                    $sp['material_grade'] = $m['grade'];
                }
                $out["specification"] = $sp;
            }

            // Common document-level sections.
            moa_ensure_common_table($conn);
            $plant = $conn->real_escape_string($_GET["plant_id"] ?? '');
            $cRes = $conn->query("SELECT * FROM moa_common_sections WHERE specification_no='$specNo' AND plant_id='$plant' LIMIT 1");
            if (!$cRes || $cRes->num_rows === 0) {
                // Fall back to any plant row for this spec (plant context may differ).
                $cRes = $conn->query("SELECT * FROM moa_common_sections WHERE specification_no='$specNo' ORDER BY id DESC LIMIT 1");
            }
            if ($cRes && ($c = $cRes->fetch_assoc())) {
                foreach (['Genral_Instruction','Associative_Document','Refrenced_Document','defination'] as $f) {
                    $c[$f] = ($c[$f] !== null && $c[$f] !== '') ? json_decode($c[$f], true) : [];
                }
                foreach (['purpose','standard_Solutions','Test_Solutions','Scope'] as $f) {
                    $c[$f] = ($c[$f] !== null) ? htmlspecialchars_decode($c[$f], ENT_QUOTES) : '';
                }
                $out["common"] = $c;
            }

            // Detect which optional columns exist on spec_tests to build a safe SELECT.
            $stCols = [];
            $stColRes = @$conn->query("SHOW COLUMNS FROM spec_tests");
            if ($stColRes) { while ($sc = $stColRes->fetch_assoc()) { $stCols[$sc['Field']] = true; } }
            $optCols = ['method_wf_status','method_log_status','test_master_id','test_method_no',
                        'method_line_check','method_line_approve','method_wf_remark'];
            $selectExtra = '';
            foreach ($optCols as $oc) {
                $selectExtra .= isset($stCols[$oc]) ? ", spec_tests.$oc" : ", NULL AS $oc";
            }
            $hasEntryOn = isset($stCols['entry_on']);
            $hasIsMethod = isset($stCols['ismethod']);
            $hasMethodCol = isset($stCols['method']);
            $ismethodSql = $hasIsMethod ? ", spec_tests.ismethod" : ", NULL AS ismethod";
            $methodSql = $hasMethodCol ? ", spec_tests.method" : ", NULL AS method";
            $preparedOnSql = $hasEntryOn
                ? "(SELECT tm.entry_on FROM test_methods tm WHERE tm.spec_test_id = spec_tests.id ORDER BY tm.id DESC LIMIT 1) AS prepared_on"
                : "NULL AS prepared_on";
            $preparedBySql = "(SELECT COALESCE(NULLIF(TRIM(CONCAT(COALESCE(e.firstname,''),' ',COALESCE(e.lastname,''))), ''), tm2.entry_by)
                            FROM test_methods tm2 LEFT JOIN employee e ON e.emp_id = tm2.entry_by
                            WHERE tm2.spec_test_id = spec_tests.id ORDER BY tm2.id DESC LIMIT 1) AS prepared_by";

            $specMatch = "(TRIM(specification_no)='".$specNo."' OR specification_no='".$specNo."')";
            // Every test + its full method.
            $tRes = @$conn->query("SELECT id, test_type, test, subtest, description $methodSql, reference_type,
                        specification_no $ismethodSql $selectExtra,
                        $preparedOnSql, $preparedBySql
                     FROM spec_tests WHERE $specMatch ORDER BY id ASC");
            if (!$tRes || $tRes->num_rows === 0) {
                $tRes = @$conn->query("SELECT * FROM spec_tests WHERE $specMatch ORDER BY id ASC");
            }
            if ($tRes && $tRes->num_rows > 0) {
                while ($t = $tRes->fetch_assoc()) {
                    if (!empty($t['reference_type'])) {
                        $refIds = preg_replace('/[^0-9,]/', '', $t['reference_type']);
                        if ($refIds !== '') {
                            $rq = @$conn->query("SELECT GROUP_CONCAT(grade) as gradeName FROM grade where id in ('".str_replace(",", "','", $refIds)."')");
                            if ($rq && ($gr = $rq->fetch_assoc())) { $t['gradeName'] = $gr['gradeName']; }
                        }
                    }
                    $t['method_data'] = moa_fetch_method_data_for_spec_test(
                        $conn,
                        (int)$t['id'],
                        intval($t['test_master_id'] ?? 0)
                    );
                    $out["tests"][] = $t;
                }
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        // Word-pasted method HTML can contain invalid UTF-8; substitute rather than
        // failing the whole document (which left checkers with empty method bodies).
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($out, $flags);
        if ($json === false) {
            $json = json_encode($out);
        }
        echo ($json !== false) ? $json : '{"specification":{},"common":{},"tests":[]}';
    }
    else if ($_GET["type"] == "getAddedMethod") {
        $output = [] ;
     echo   $sql = "SELECT * FROM moa_procdeatil WHERE moa_id=38";
        // }
         $result = $conn->query($sql);
         if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
             //   $row["data"]  =  json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$row["data"]));
                $jsonD = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$row["data"]));

                print_r($jsonD);
      
                $output[] = $row;
                
            }
        }


    echo json_encode($output);
    }
    else if ($_GET["type"] == "getRawMOALog") {
       // if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
           // $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve' 
           // AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' 
            //AND '".$_GET["todate"]."'";
       // } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
           // $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve' 
           // AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
       // } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
          //  $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve' 
           // AND material_code='".$_GET['material_code']."'";
        //}else{
            $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve'";
       // }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRawMOAMaterial") {
        // $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve'";        
        $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve'";

        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingPackingMOA") {
        
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Packing Material' AND ismoa='pending' AND  status = 'approve' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPendingWaterMOA") {
        
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Water Specification' AND ismoa='pending' AND  status = 'approve' ORDER BY id DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } 
    else if ($_GET["type"] == "getPackingMOALog") {
        $output = Array();
       // if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
           // $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' 
           // AND ismoa='approve' AND material_code='".$_GET['material_code']."' 
          //  AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
       //} else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
          // $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND ismoa='approve' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
     // } else if($_GET['fromdate'] == '' && $_GET['material_code'] != ''){
         // $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' 
           // AND ismoa='approve' AND material_code='".$_GET['material_code']."'";
    //   }else{
           $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' 
           AND ismoa='approve'";
       //}
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPackingMOAMaterial") {
       
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Packing Material Specification' AND ismoa='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }

                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingFinishMOA") {
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND ismoa='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getFinishMOALog") {
        $output = Array();
        if($_GET['fromdate'] != '' && $_GET['product_code'] != ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND ismoa='approve' 
            AND product_code='".$_GET['product_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' 
            AND '".$_GET["todate"]."'";
        } else if($_GET['fromdate'] != '' && $_GET['product_code'] == ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND ismoa='approve' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
        } else if($_GET['fromdate'] == '' && $_GET['product_code'] != ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND ismoa='approve' AND product_code='".$_GET['product_code']."'";
        }else{
            $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND ismoa='approve'";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getFinishMOAMaterial") {
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Finish Product' AND ismoa='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "GetRevisonData") {
        $output = Array();
        $sql = "SELECT * FROM stp_rivision WHERE specification_no='".$_GET["specification_no"]."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }
       else if ($_GET["type"] == "addrevision_history_STP") {
        
                $sql = "INSERT INTO stp_rivision  (doe, desc_rational, version, stp_no,specification_no)
                   VALUES ('".$input["doe"]."','".$input["desc_rational"]."','".$input["version"]."','".$input["stp"]."','".$input["specification_no"]."' )";
                    if ($conn->query($sql)) {
                         echo "{\"status\":\"success\"}";
                    } else {
                        echo "{\"status\":\"".$conn->error."\"}";
                    }
                    
                
    }
    else if ($_GET["type"] == "getPendingInprocessMOA") {
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getInprocessMOALog") {
        $output = Array();
        if($_GET['fromdate'] != '' && $_GET['product_code'] != ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve' AND product_code='".$_GET['product_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
        } else if($_GET['fromdate'] != '' && $_GET['product_code'] == ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
        } else if($_GET['fromdate'] == '' && $_GET['product_code'] != ''){
            $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve' AND product_code='".$_GET['product_code']."'";
        }else{
            $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve'";
        }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getInprocessMOAMaterial") {
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Inprocess Specification' AND ismoa='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingRetestMOA") {
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND ismoa='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRetestMOALog") {
        $output = Array();
       // if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
          //  $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND ismoa='approve' AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
       // } else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
           // $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND ismoa='approve' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
       // } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
            //$sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND ismoa='approve' AND material_code='".$_GET['material_code']."'";
       // }else{
            $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND ismoa='approve'";
       // }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getRetestMOAMaterial") {
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Retest Specification' AND ismoa='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_subtype"] = $row1["material_subtype"];
                        $row["material_name"] = $row1["material_name"];
                        $row["grade"] = $row1["grade"];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getPendingStabilityMOA") {
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND ismoa='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getStabilityMOALog") {
        $output = Array();
       // if($_GET['fromdate'] != '' && $_GET['material_code'] != ''){
           // $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND ismoa='approve' AND material_code='".$_GET['material_code']."' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
        //} else if($_GET['fromdate'] != '' && $_GET['material_code'] == ''){
           // $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND ismoa='approve' AND DATE(entry_date) BETWEEN '".$_GET["fromdate"]."' AND '".$_GET["todate"]."'";
       // } else if($_GET['fromdate'] == '' && $_GET['material_code'] !== ''){
           // $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND ismoa='approve' AND material_code='".$_GET['material_code']."'";
       // }else{
            $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND ismoa='approve'";
       // }
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                
                $output1 = Array();
                $sql1 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row1["method_details"] = json_decode($row1["method_details"]);
                        $output1[] = $row1;
                    }
                }
                $row["tests"] = $output1;
                $output[] = $row;
            }
        }
        echo json_encode($output);
    } else if ($_GET["type"] == "getStabilityMOAMaterial") {
        $output = Array();
        $sql = "SELECT * FROM specification WHERE spec_type='Stability Specification' AND ismoa='approve'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                
                $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["product_name"] = $row1["product_name"];
                        $row["grade"] = $row1["grade"];
                        $row["generic_name"] = $row1["generic_name"];
                    }
                }
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }else if ($_GET["type"]=="TestMethodsLog") {
        $_GET['filename'] = 'Test Method  Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Test Method  Log</h2>
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;">
                <td style="width:10%;">Sr</td>
                <td style="width:15%;">Specification No</td>
                <td style="width:15%;">Specification Type</td>
                <td style="width:15%;">Material/Product Code</td>
                <td style="width:15%;">Test</td>
                <td style="width:15%;">Subtest</td>
                <td style="width:15%;">Status</td>
            </tr>';
       $output = Array();
       $i=1;
        $sql = "SELECT * FROM spec_tests WHERE method !='pending'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["method_details"] = json_decode($row["method_details"]);
                
                $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_code"] = $row1["material_code"];
                        $row["spec_type"] = $row1["spec_type"];
                        $row["product_code"] = $row1["product_code"];
                    }
                }
                
                if ($row["spec_type"] == "Finish Product" || $row["spec_type"] == "Inprocess Specification" || $row["spec_type"] == "Stability Specification") {
                    $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["product_name"] = $row1["product_name"];
                            $row["grade"] = $row1["grade"];
                            $row["generic_name"] = $row1["generic_name"];
                        }
                    }
                } else {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_type"] = $row1["material_type"];
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                }
                $output[] = $row;
                $html.='
                <tr nobr="true">
                    <td>'.$i.'</td>
                    <td>'.$row['specification_no'].'</td>
                    <td>'.$row['spec_type'].'</td>
                     <td>'.$row['material_code'].'</td>
                    <td>'.$row["test"].'</td>
                    <td>'.$row['subtest'].'</td>
                    <td>'.$row["status"].'</td>
                </tr>';
                $i++;
            }
        }
        $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('testmethodslog.pdf', 'I');
    } else if ($_GET["type"]=="TestMethod") {
        $_GET['filename'] = 'Test Method  Log'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Test Method  Log</h2>
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;">
                <td style="width:20%;">Specification No</td>
                <td style="width:20%;">Specification Type</td>
                <td style="width:20%;">Material/Product Code</td>
                <td style="width:20%;">Test</td>
                <td style="width:20%;">Subtest</td>
            </tr>';
       $output = Array();
       $i=1;
        $sql = "SELECT * FROM spec_tests WHERE method !='pending' AND id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["method_details"] = json_decode($row["method_details"]);
                
                $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_code"] = $row1["material_code"];
                        $row["spec_type"] = $row1["spec_type"];
                        $row["product_code"] = $row1["product_code"];
                    }
                }
                
                if ($row["spec_type"] == "Finish Product" || $row["spec_type"] == "Inprocess Specification" || $row["spec_type"] == "Stability Specification") {
                    $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["product_name"] = $row1["product_name"];
                            $row["grade"] = $row1["grade"];
                            $row["generic_name"] = $row1["generic_name"];
                        }
                    }
                } else {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_type"] = $row1["material_type"];
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                }
                $output[] = $row;
                $html.='
                <tr nobr="true">
                    <td>'.$row['specification_no'].'</td>
                    <td>'.$row['spec_type'].'</td>
                    <td>'.$row['material_code'].'</td>
                    <td>'.$row["test"].'</td>
                    <td>'.$row['subtest'].'</td>
                </tr>';
            
            }
        }
        $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('TestMethods.pdf', 'I');
    }
     else if ($_GET["type"]=="TestMethoddigital") {
        $_GET['filename'] = 'Test Method  Digital'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h2 style="text-align:center">Test Method  Digital</h2>
        <table cellpadding="5">
            <tr style="background-color:#DDDAD9;">
                <td style="width:20%;">Specification No</td>
                <td style="width:20%;">Specification Type</td>
                <td style="width:20%;">Material/Product Code</td>
                <td style="width:20%;">Test</td>
                <td style="width:20%;">Subtest</td>
            </tr>';
       $output = Array();
       $i=1;
        $sql = "SELECT * FROM spec_tests WHERE method !='pending' AND id='".$_GET['id']."'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row["method_details"] = json_decode($row["method_details"]);
                
                $sql1 = "SELECT * FROM specification WHERE specification_no='".$row["specification_no"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        $row["material_code"] = $row1["material_code"];
                        $row["spec_type"] = $row1["spec_type"];
                        $row["product_code"] = $row1["product_code"];
                    }
                }
                
                if ($row["spec_type"] == "Finish Product" || $row["spec_type"] == "Inprocess Specification" || $row["spec_type"] == "Stability Specification") {
                    $sql1 = "SELECT * FROM product WHERE product_code='".$row["product_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["product_name"] = $row1["product_name"];
                            $row["grade"] = $row1["grade"];
                            $row["generic_name"] = $row1["generic_name"];
                        }
                    }
                } else {
                    $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                    $result1 = $conn->query($sql1);
                    if ($result1->num_rows > 0) {
                        while ($row1 = $result1->fetch_assoc()) {
                            $row["material_type"] = $row1["material_type"];
                            $row["material_subtype"] = $row1["material_subtype"];
                            $row["material_name"] = $row1["material_name"];
                            $row["grade"] = $row1["grade"];
                        }
                    }
                }
                $output[] = $row;
                $html.='
                <tr nobr="true">
                    <td>'.$row['specification_no'].'</td>
                    <td>'.$row['spec_type'].'</td>
                    <td>'.$row['material_code'].'</td>
                    <td>'.$row["test"].'</td>
                    <td>'.$row['subtest'].'</td>
                </tr>';
            
            }
        }
        $html.='
        </table>';
        EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Test Methods digital.pdf', 'I');
        
     }else if($_GET['type'] == 'downloadRawMOARecord'){
       $_GET['filename'] = 'Raw Material MOA Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        
         $html.='
        <h3>Raw Material MOA Report</h3>
        <table cellpadding="5" border="0.1">';
      
         $html.=' ';
           $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification'
           AND ismoa='approve'";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $sql2 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {  
                        
                        
            $html.=' <tr >
         
         
       <td style="width:20%;background-color:#DDDAD9;"><b>Specification No</b></td>
       <td style="width:30%;">'.$row['specification_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Specification Type</b></td>
          <td style="width:30%;">'.$row['spec_type'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Material Type</b></td>
       <td style="width:30%;">'.$row1['material_type'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Material Name</b></td>
           <td style="width:30%;">'.$row1['material_name'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Material Code</b></td>
        <td style="width:30%;">'.$row1['material_code'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Material Grade</b></td>
           <td style="width:30%;">'.$row['material_grade'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Version No</b></td>
          <td style="width:30%;">'.$row['version_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Supersede No</b></td>
           <td style="width:30%;">'.$row['supersede_no'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Sample Qty</b></td>
         <td style="width:30%;">'.$row['sample_qty'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Shelf Life</b></td>
          <td style="width:30%;">'.$row['shelf_life'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>SAP No</b></td>
         <td style="width:30%;">'.$row['sap_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Storage</b></td>
           <td style="width:30%;">'.$row['storage'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Chemical Name</b></td>
           <td style="width:80%;">'.$row['chemical_name'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Safety Precaution</b></td>
         <td style="width:80%;">'.$row['safety_precaution'].'</td>
           </tr>';
                   
         
           $html.=' </table>
           <div></div>
           <h3>Tests:</h3>
           <table cellpadding="5" border="0.1">
            <tr style="text-align:center;background-color:#DDDAD9;">
           <td style="width:20%;text-align:center"><b>Test</b></td>
            <td style="width:20%;text-align:center"><b>Sub Test</b></td>
             <td style="width:20%;text-align:center"><b>Description</b></td>
              <td style="width:20%;text-align:center"><b>Refernce Type</b></td>
               <td style="width:20%;text-align:center"><b>Sample Qty</b></td>
           </tr>';
                    
                
         $html.=' <tr>
           <td style="width:20%">'.$row2['test'].'</td>
            <td style="width:20%">'.$row2['subtest'].'</td>
             <td style="width:20%">'.$row2['description'].'</td>
              <td style="width:20%">'.$row2['reference_type'].'</td>
               <td style="width:20%">'.$row2['sample_qty'].'</td>
           </tr>';
                    
                    }
                }
     }}}
    }   
          $html.=' </table>
           
          <div></div> <div></div> <div></div> <div></div> <div></div>
        
           <table cellpadding="5" border="0.1">
           <tr style="text-align:center;background-color:#DDDAD9;">
             <td style="width:15%"> </td>
                    <td style="width:30%;text-align:center"><b>Prepared by</b></td>
                    <td style="width:25%;text-align:center"><b>Checked By</b></td>
                    <td style="width:30%;text-align:center"><b>Approved By</b></td>
                </tr>
           <tr>
           <td style="width:15%;text-align:center"><b>Name</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
              <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Date</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
               <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Sign</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
             <td style="width:30%"></td>
              </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Raw MOA.pdf', 'I');
       
       
     }else if($_GET['type'] == 'downloadRawMOARecordDigital'){
       $_GET['filename'] = 'Raw Material MOA Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html.='
        <h3>Raw Material MOA Report</h3>
        <table cellpadding="5" border="0.1">';
      
         $html.=' ';
           $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' AND ismoa='approve'";
         $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                      $sql2 = "SELECT * FROM spec_tests WHERE specification_no='".$row["specification_no"]."'";
                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {  
                        
                        
            $html.=' <tr >
         
         
       <td style="width:20%;background-color:#DDDAD9;"><b>Specification No</b></td>
       <td style="width:30%;">'.$row['specification_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Specification Type</b></td>
          <td style="width:30%;">'.$row['spec_type'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Material Type</b></td>
       <td style="width:30%;">'.$row1['material_type'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Material Name</b></td>
           <td style="width:30%;">'.$row1['material_name'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Material Code</b></td>
        <td style="width:30%;">'.$row1['material_code'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Material Grade</b></td>
           <td style="width:30%;">'.$row['material_grade'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Version No</b></td>
          <td style="width:30%;">'.$row['version_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Supersede No</b></td>
           <td style="width:30%;">'.$row['supersede_no'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Sample Qty</b></td>
         <td style="width:30%;">'.$row['sample_qty'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Shelf Life</b></td>
          <td style="width:30%;">'.$row['shelf_life'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>SAP No</b></td>
         <td style="width:30%;">'.$row['sap_no'].'</td>
          <td style="width:20%;background-color:#DDDAD9;"><b>Storage</b></td>
           <td style="width:30%;">'.$row['storage'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Chemical Name</b></td>
           <td style="width:80%;">'.$row['chemical_name'].'</td>
        </tr>
         <tr>
        <td style="width:20%;background-color:#DDDAD9;"><b>Safety Precaution</b></td>
         <td style="width:80%;">'.$row['safety_precaution'].'</td>
           </tr>';
                    
                
         
           $html.=' </table>
           <div></div>
           <h3>Tests:</h3>
           <table cellpadding="5" border="0.1">
            <tr style="text-align:center;background-color:#DDDAD9;">
           <td style="width:20%;text-align:center"><b>Test</b></td>
            <td style="width:20%;text-align:center"><b>Sub Test</b></td>
             <td style="width:20%;text-align:center"><b>Description</b></td>
              <td style="width:20%;text-align:center"><b>Refernce Type</b></td>
               <td style="width:20%;text-align:center"><b>Sample Qty</b></td>
           </tr>';
            
         $html.=' <tr>
           <td style="width:20%">'.$row2['test'].'</td>
            <td style="width:20%">'.$row2['subtest'].'</td>
             <td style="width:20%">'.$row2['description'].'</td>
              <td style="width:20%">'.$row2['reference_type'].'</td>
               <td style="width:20%">'.$row2['sample_qty'].'</td>
           </tr>';
                    
                    }
                }
     }}}
    }   
          $html.=' </table>
           
          <div></div> <div></div> <div></div> <div></div> <div></div>
        
           <table cellpadding="5" border="0.1">
           <tr style="text-align:center;background-color:#DDDAD9;">
             <td style="width:15%"> </td>
                    <td style="width:30%;text-align:center"><b>Prepared by</b></td>
                    <td style="width:25%;text-align:center"><b>Checked By</b></td>
                    <td style="width:30%;text-align:center"><b>Approved By</b></td>
                </tr>
           <tr>
           <td style="width:15%;text-align:center"><b>Name</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
              <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Date</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
               <td style="width:30%"></td>
              </tr>
              <tr>
           <td style="width:15%;text-align:center"><b>Sign</b></td>
            <td style="width:30%"></td>
             <td style="width:25%"></td>
             <td style="width:30%"></td>
              </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('Raw MOA.pdf', 'I');
        
        
           }else if($_GET['type'] == 'downloadRawMOALog'){
       $_GET['filename'] = 'Raw Material MOA Report'; $_GET['pdftype'] = 'onlyheader';  include("../pdfimp2.php");
        $html= '
        <h2 style="text-align:center">Raw Material MOA Report</h2>
        <table cellpadding="5" border="1">
      <tr>
                    <td style="width:5%;"><b>Sr.</b></td>
                    <td style="width:15%;"><b>Specification No</b></td>
                    <td style="width:10%;"><b>Material Code</b></td>
                    <td style="width:20%;"><b>Material Name</b></td>
                    <td style="width:10%;"><b>Grade</b></td>
                    <td style="width:15%;"><b>Prepared By</b></td>
                    <td style="width:10%;"><b>Checked By</b></td>
                    <td style="width:15%;"><b>Approved By</b></td>
                </tr>';
                $i=1;
                $sql = "SELECT * FROM specification WHERE spec_type='Raw Material Specification' 
                AND ismoa='approve'";
     $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $sql1 = "SELECT * FROM material WHERE material_code='".$row["material_code"]."'";
                $result1 = $conn->query($sql1);
                if ($result1->num_rows > 0) {
                    while ($row1 = $result1->fetch_assoc()) {
                        
                $html.='<tr>
                        <td style="width:5%;">'.$i.'</td>
                        <td style="width:15%;">'.$row['specification_no'].'</td>
                        <td style="width:10%;">'.$row1['material_code'].'</td>
                        <td style="width:20%;">'.$row1['material_name'].'</td>
                        <td style="width:10%;">'.$row1['grade'].'</td>
                        <td style="width:15%;">'.$row1['prepared_by'].'</td>
                        <td style="width:10%;">'.$row1['checked_by'].'</td>
                        <td style="width:15%;">'.$row1['approved_by'].'</td>
                    </tr>';
                    $i++;
                    }
                }
            }
        }
            $html.='</table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('coa.pdf', 'I');
    } 

}

$conn->close();
?>

