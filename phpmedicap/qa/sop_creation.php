<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

require '../db.php';
require '../token.php';
require '../tcpdf/tcpdf.php';

// Load OpenAI configuration (if file exists)
if (file_exists(__DIR__ . '/config_openai.php')) {
    require_once __DIR__ . '/config_openai.php';
}

header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");
$token = $_GET["token"];
$timestamp = time();
$entry_date = date("Y-m-d h:i:s", $timestamp);

// Handle input - can be from POST body (JSON) or POST form data
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $input = json_decode($raw_input, true);
        if ($input === null && isset($_POST['data'])) {
            // Handle form POST with JSON string in 'data' field
            $input = json_decode($_POST['data'], true);
        }
    }
}

// Set JSON header only if not generating PDF (PDF needs to output binary)
if (!isset($_GET['type']) || $_GET['type'] !== 'generatePDF') {
    header('Content-Type: application/json');
}

$sql = "SELECT * FROM token WHERE token='" . $_GET["token"] . "'";
$result = $conn->query($sql);
$_GET["emp_id"] = "";
$_GET["department"] = "";
if ($result->num_rows > 0) {
    
    while ($row = $result->fetch_assoc()) {
        $string = decrypt('decrypt', $_GET["token"], $row["key1"], $row["key2"]);
        $string = explode("$", $string);
        $_GET["emp_id"] = $string[0];   //you can get here login emp id 
        $_GET["department"] = $string[1];
        break;
    }

    $txt = '{"process": "FRONTEND", "token": "' . $token . '", "action": "' . $_GET["type"] . '", "actiontime": "' . $entry_date . '", "department": "' . $_GET["department"] . '", "emp_id": "' . $_GET["emp_id"] . '", "method": "' . $_SERVER['REQUEST_METHOD'] . '", "REMOTE_ADDR": "' . $_SERVER['REMOTE_ADDR'] . '"}';
    $myfile = file_put_contents('../logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

    // Get OpenAI API key from config or environment
    // Priority: 1. Environment variable, 2. Config file constant, 3. Empty string
    $openai_api_key = getenv('OPENAI_API_KEY') ?: (defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '');
    $openai_api_url = defined('OPENAI_API_URL') ? OPENAI_API_URL : 'https://api.openai.com/v1/chat/completions';
    $openai_model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-3.5-turbo';
    $openai_max_tokens = defined('OPENAI_MAX_TOKENS') ? OPENAI_MAX_TOKENS : 1000;
    $openai_temperature = defined('OPENAI_TEMPERATURE') ? OPENAI_TEMPERATURE : 0.7;

    // Convert markdown to editor-friendly HTML (for AI responses that return markdown)
    function sop_markdown_to_html($text) {
        if (trim($text) === '') return '';
        $html = $text;
        // Already looks like HTML (contains common block tags)? Return as-is, trimmed.
        if (preg_match('/<(p|ul|ol|li|h[2-6]|strong|em|br)\b/i', $html)) {
            return trim($html);
        }
        $lines = preg_split('/\r\n|\r|\n/', $html);
        $out = [];
        $inList = false;
        $listTag = null;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($inList) { $out[] = $listTag === 'ul' ? '</ul>' : '</ol>'; $inList = false; $listTag = null; }
                continue;
            }
            // Numbered list: 1. or 1)
            if (preg_match('/^(\d+)[.)]\s+(.+)$/', $trimmed, $m)) {
                if (!$inList || $listTag !== 'ol') {
                    if ($inList) $out[] = $listTag === 'ul' ? '</ul>' : '</ol>';
                    $out[] = '<ol>';
                    $inList = true;
                    $listTag = 'ol';
                }
                $out[] = '<li>' . htmlspecialchars(trim($m[2]), ENT_HTML5, 'UTF-8') . '</li>';
                continue;
            }
            // Unordered list: - or * or •
            if (preg_match('/^[-*•]\s+(.+)$/', $trimmed, $m) || preg_match('/^\*\s+(.+)$/', $trimmed, $m)) {
                $content = isset($m[1]) ? $m[1] : substr($trimmed, 2);
                if (!$inList || $listTag !== 'ul') {
                    if ($inList) $out[] = $listTag === 'ul' ? '</ul>' : '</ol>';
                    $out[] = '<ul>';
                    $inList = true;
                    $listTag = 'ul';
                }
                $out[] = '<li>' . htmlspecialchars(trim($content), ENT_HTML5, 'UTF-8') . '</li>';
                continue;
            }
            if ($inList) { $out[] = $listTag === 'ul' ? '</ul>' : '</ol>'; $inList = false; $listTag = null; }
            // Heading ## or ###
            if (preg_match('/^###\s+(.+)$/', $trimmed, $m)) {
                $out[] = '<h3>' . htmlspecialchars($m[1], ENT_HTML5, 'UTF-8') . '</h3>';
            } elseif (preg_match('/^##\s+(.+)$/', $trimmed, $m)) {
                $out[] = '<h2>' . htmlspecialchars($m[1], ENT_HTML5, 'UTF-8') . '</h2>';
            } else {
                $out[] = '<p>' . htmlspecialchars($trimmed, ENT_HTML5, 'UTF-8') . '</p>';
            }
        }
        if ($inList) $out[] = $listTag === 'ul' ? '</ul>' : '</ol>';
        return implode("\n", $out);
    }

    // ============================================
    // API ENDPOINTS
    // ============================================

    if ($_GET["type"] == "getDepartments") {
        $output = Array();
        $sql = "SELECT * FROM department ORDER BY department_name ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        echo json_encode($output);
    }

    // Get SOP Templates from database
    else if ($_GET["type"] == "getTemplates") {
        $output = Array();
        $sql = "SELECT template_id, template_name, description, default_sections, is_active 
                FROM sop_creation_templates 
                WHERE is_active = 1 
                ORDER BY template_id ASC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Decode JSON default_sections
                $row['default_sections'] = json_decode($row['default_sections'], true);
                $output[] = $row;
            }
        } else {
            // Fallback: Return empty array if no templates found
            // Frontend will use hardcoded templates as fallback
            $output = [];
        }
        echo json_encode($output);
    }

    // Rewrite section content using OpenAI
    else if ($_GET["type"] == "rewriteWithAI") {
        $sectionName = isset($input['sectionName']) ? $input['sectionName'] : '';
        $text = isset($input['text']) ? $input['text'] : '';
        
        if (empty($text)) {
            echo json_encode(['status' => 'error', 'message' => 'Text is required']);
            exit;
        }
        
        if (empty($openai_api_key)) {
            echo json_encode(['status' => 'error', 'message' => 'OpenAI API key not configured']);
            exit;
        }
        
        // Prepare prompt: output must be editor-friendly HTML (same as generateWithAI)
        $prompt = "You are an experienced pharmaceutical SOP writer. Rewrite the following text so it is grammatically correct, clear, and in formal SOP language. Keep the same meaning and structure; use the same list/paragraph structure. Return ONLY valid HTML: use <p>, <ul>, <ol>, <li>, <strong>, <em>, <br>. No markdown (no ##, -, 1.).\n\nSection: {$sectionName}\n\nText to rewrite:\n{$text}";
        
        // Call OpenAI API
        $ch = curl_init($openai_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openai_api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => $openai_model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $openai_max_tokens,
            'temperature' => $openai_temperature
        ]));
        
        // Set timeout if defined
        if (defined('OPENAI_TIMEOUT')) {
            curl_setopt($ch, CURLOPT_TIMEOUT, OPENAI_TIMEOUT);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            $data = json_decode($response, true);
            $raw = isset($data['choices'][0]['message']['content']) ? trim($data['choices'][0]['message']['content']) : '';
            $rewrittenText = sop_markdown_to_html($raw);
            echo json_encode([
                'status' => 'success',
                'rewrittenText' => $rewrittenText
            ]);
        } else {
            $error_data = json_decode($response, true);
            echo json_encode([
                'status' => 'error',
                'message' => 'OpenAI API error: ' . (isset($error_data['error']['message']) ? $error_data['error']['message'] : $http_code)
            ]);
        }
    }

    // Generate section content from user prompt using OpenAI (Write with AI)
    else if ($_GET["type"] == "generateWithAI") {
        $sectionName = isset($input['sectionName']) ? trim($input['sectionName']) : '';
        $prompt = isset($input['prompt']) ? trim($input['prompt']) : '';

        if (empty($openai_api_key)) {
            echo json_encode(['status' => 'error', 'message' => 'OpenAI API key not configured']);
            exit;
        }

        $user_instructions = $prompt !== '' ? $prompt : "Write appropriate professional content for this section.";
        $system_prompt = "You are an experienced pharmaceutical SOP writer. Generate professional, formal SOP content for the given section. Use clear language.\n\nIMPORTANT - Output format: You MUST return ONLY valid HTML that will be pasted into a rich text editor. Use these tags only:\n- <p>...</p> for paragraphs\n- <ul><li>...</li></ul> for bullet lists\n- <ol><li>...</li></ol> for numbered steps\n- <strong>...</strong> for bold, <em>...</em> for italic\n- <br> for line break inside a paragraph\nDo NOT use markdown (no ##, no -, no 1.). Do NOT add a section title or heading. Return only the HTML body content.";
        $user_message = "Section name: {$sectionName}\n\nUser instructions or prompt:\n{$user_instructions}\n\nGenerate the section content as HTML only:";

        $ch = curl_init($openai_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openai_api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => $openai_model,
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $user_message]
            ],
            'max_tokens' => isset($openai_max_tokens) ? (int) $openai_max_tokens : 1500,
            'temperature' => isset($openai_temperature) ? (float) $openai_temperature : 0.7
        ]));

        if (defined('OPENAI_TIMEOUT')) {
            curl_setopt($ch, CURLOPT_TIMEOUT, OPENAI_TIMEOUT);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $data = json_decode($response, true);
            $raw = isset($data['choices'][0]['message']['content']) ? trim($data['choices'][0]['message']['content']) : '';
            $generatedText = sop_markdown_to_html($raw);
            echo json_encode([
                'status' => 'success',
                'generatedText' => $generatedText
            ]);
        } else {
            $error_data = json_decode($response, true);
            echo json_encode([
                'status' => 'error',
                'message' => 'OpenAI API error: ' . (isset($error_data['error']['message']) ? $error_data['error']['message'] : (string) $http_code)
            ]);
        }
    }

    // Save draft SOP
    else if ($_GET["type"] == "saveDraft") {
        $draft_id = isset($input['draft_id']) ? $input['draft_id'] : '';
        $template_id = isset($input['template_id']) ? $input['template_id'] : '';
        $template_name = isset($input['template_name']) ? $input['template_name'] : '';
        $header_template_id = isset($input['header_template_id']) ? $input['header_template_id'] : '';
        $section_order = isset($input['section_order']) ? json_encode($input['section_order']) : '[]';
        $content = isset($input['content']) ? json_encode($input['content']) : '{}';
        $section_names = isset($input['section_names']) ? json_encode($input['section_names']) : '{}';
        $document_info = isset($input['document_info']) ? json_encode($input['document_info']) : '{}';
        $header_config = isset($input['header_config']) ? json_encode($input['header_config']) : '{}';
        $footer_config = isset($input['footer_config']) ? json_encode($input['footer_config']) : '{}';
        $approval_info = isset($input['approval_info']) ? json_encode($input['approval_info']) : '{}';
        $status = isset($input['status']) ? $input['status'] : 'DRAFT';
        $created_by = $_GET["emp_id"];
        $plant_id = isset($_GET["plant_id"]) ? $_GET["plant_id"] : '';
        
        // Escape strings for SQL
        $draft_id = mysqli_real_escape_string($conn, $draft_id);
        $template_id = mysqli_real_escape_string($conn, $template_id);
        $template_name = mysqli_real_escape_string($conn, $template_name);
        $header_template_id = mysqli_real_escape_string($conn, $header_template_id);
        $section_order = mysqli_real_escape_string($conn, $section_order);
        $content = mysqli_real_escape_string($conn, $content);
        $section_names = mysqli_real_escape_string($conn, $section_names);
        $document_info = mysqli_real_escape_string($conn, $document_info);
        $header_config = mysqli_real_escape_string($conn, $header_config);
        $footer_config = mysqli_real_escape_string($conn, $footer_config);
        $approval_info = mysqli_real_escape_string($conn, $approval_info);
        $status = mysqli_real_escape_string($conn, $status);
        $created_by = mysqli_real_escape_string($conn, $created_by);
        $plant_id = mysqli_real_escape_string($conn, $plant_id);
        
        // Check if draft exists
        $check_sql = "SELECT id FROM sop_creation_drafts WHERE draft_id = '" . $draft_id . "'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            // Update existing draft
            $sql = "UPDATE sop_creation_drafts SET 
                    template_id = '" . $template_id . "', 
                    template_name = '" . $template_name . "', 
                    header_template_id = '" . $header_template_id . "',
                    section_order = '" . $section_order . "', 
                    content = '" . $content . "', 
                    section_names = '" . $section_names . "',
                    document_info = '" . $document_info . "',
                    header_config = '" . $header_config . "',
                    footer_config = '" . $footer_config . "',
                    approval_info = '" . $approval_info . "',
                    status = '" . $status . "', 
                    updated_at = NOW()
                    WHERE draft_id = '" . $draft_id . "'";
        } else {
            // Insert new draft
            $sql = "INSERT INTO sop_creation_drafts 
                    (draft_id, template_id, template_name, header_template_id, section_order, content, section_names, document_info, header_config, footer_config, approval_info, status, created_by, plant_id, created_at, updated_at)
                    VALUES ('" . $draft_id . "', '" . $template_id . "', '" . $template_name . "', '" . $header_template_id . "', '" . $section_order . "', '" . $content . "', '" . $section_names . "', '" . $document_info . "', '" . $header_config . "', '" . $footer_config . "', '" . $approval_info . "', '" . $status . "', '" . $created_by . "', '" . $plant_id . "', NOW(), NOW())";
        }
        
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Draft saved successfully', 'draft_id' => $draft_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save draft: ' . $conn->error]);
        }
    }

    // Get user's drafts
    else if ($_GET["type"] == "getDrafts") {
        $created_by = $_GET["emp_id"];
        $plant_id = isset($_GET["plant_id"]) ? $_GET["plant_id"] : '';
        
        $created_by = mysqli_real_escape_string($conn, $created_by);
        $plant_id = mysqli_real_escape_string($conn, $plant_id);
        
        $sql = "SELECT * FROM sop_creation_drafts 
                WHERE created_by = '" . $created_by . "' AND plant_id = '" . $plant_id . "' 
                ORDER BY updated_at DESC";
        $result = $conn->query($sql);
        
        $drafts = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['section_order'] = json_decode($row['section_order'], true);
                $row['content'] = json_decode($row['content'], true);
                $row['section_names'] = json_decode($row['section_names'], true);
                $row['document_info'] = json_decode($row['document_info'], true);
                $row['header_config'] = json_decode($row['header_config'], true);
                $row['footer_config'] = json_decode($row['footer_config'], true);
                $row['approval_info'] = json_decode($row['approval_info'], true);
                $drafts[] = $row;
            }
        }
        
        echo json_encode($drafts);
    }

    // Get single draft by ID
    else if ($_GET["type"] == "getDraft") {
        $draft_id = isset($_GET["draft_id"]) ? $_GET["draft_id"] : '';
        $draft_id = mysqli_real_escape_string($conn, $draft_id);
        
        $sql = "SELECT * FROM sop_creation_drafts WHERE draft_id = '" . $draft_id . "'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row['section_order'] = json_decode($row['section_order'], true);
            $row['content'] = json_decode($row['content'], true);
            $row['section_names'] = json_decode($row['section_names'], true);
            $row['document_info'] = json_decode($row['document_info'], true);
            $row['header_config'] = json_decode($row['header_config'], true);
            $row['footer_config'] = json_decode($row['footer_config'], true);
            $row['approval_info'] = json_decode($row['approval_info'], true);
            
            // Get change request comments if draft status is CHANGES_REQUESTED
            if ($row['status'] == 'CHANGES_REQUESTED') {
                $comment_sql = "SELECT approval_comments FROM sop_creation_approvals 
                               WHERE draft_id = '" . $draft_id . "' 
                               AND approval_status = 'changes_requested' 
                               ORDER BY approval_date DESC LIMIT 1";
                $comment_result = $conn->query($comment_sql);
                if ($comment_result->num_rows > 0) {
                    $comment_row = $comment_result->fetch_assoc();
                    $row['change_request_comments'] = $comment_row['approval_comments'];
                }
            }
            
            echo json_encode($row);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Draft not found']);
        }
    }

    // Submit SOP for approval
    else if ($_GET["type"] == "submitForApproval") {
        $draft_id = isset($input['draft_id']) ? $input['draft_id'] : '';
        $created_by = $_GET["emp_id"];
        
        $draft_id = mysqli_real_escape_string($conn, $draft_id);
        $created_by = mysqli_real_escape_string($conn, $created_by);
        
        // Check current status
        $check_sql = "SELECT status FROM sop_creation_drafts WHERE draft_id = '" . $draft_id . "'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            $current_status = $row['status'];
            
            // If resubmitting after changes requested, delete old approval records
            if ($current_status == 'CHANGES_REQUESTED') {
                $delete_sql = "DELETE FROM sop_creation_approvals WHERE draft_id = '" . $draft_id . "'";
                $conn->query($delete_sql);
            }
        }
        
        // Update draft status
        $sql = "UPDATE sop_creation_drafts SET status = 'SUBMITTED' WHERE draft_id = '" . $draft_id . "'";
        $conn->query($sql);
        
        // Create Level 1 (Checker) approval entry
        $approval_sql = "INSERT INTO sop_creation_approvals 
                         (draft_id, approval_level, approver_role, approval_status, created_at)
                         VALUES ('" . $draft_id . "', 1, 'checker', 'pending', NOW())";
        
        if ($conn->query($approval_sql)) {
            echo json_encode(['status' => 'success', 'message' => 'SOP submitted for approval']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to submit: ' . $conn->error]);
        }
    }

    // Get pending approvals for user's role
    else if ($_GET["type"] == "getPendingApprovals") {
        $emp_id = $_GET["emp_id"];
        $role = isset($_GET["role"]) ? $_GET["role"] : ''; // checker, approver, qms_approver, dept_head
        
        // Map role to approval level
        $level_map = [
            'checker' => 1,
            'approver' => 2,
            'qms_approver' => 3,
            'dept_head' => 4
        ];
        $level = isset($level_map[$role]) ? $level_map[$role] : 0;
        
        if ($level == 0) {
            echo json_encode([]);
            exit;
        }
        
        // Get drafts pending at this level
        $sql = "SELECT d.*, a.id as approval_id, a.approval_level
                FROM sop_creation_drafts d
                INNER JOIN sop_creation_approvals a ON d.draft_id = a.draft_id
                WHERE a.approval_level = " . $level . " 
                AND a.approval_status = 'pending'
                AND d.status = 'SUBMITTED'
                ORDER BY d.created_at DESC";
        $result = $conn->query($sql);
        
        $approvals = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['section_order'] = json_decode($row['section_order'], true);
                $row['content'] = json_decode($row['content'], true);
                $row['section_names'] = json_decode($row['section_names'], true);
                $row['document_info'] = json_decode($row['document_info'], true);
                $row['header_config'] = json_decode($row['header_config'], true);
                $row['footer_config'] = json_decode($row['footer_config'], true);
                $row['approval_info'] = json_decode($row['approval_info'], true);
                $approvals[] = $row;
            }
        }
        
        echo json_encode($approvals);
    }

    // Approve SOP at current level
    else if ($_GET["type"] == "approveSOP") {
        $draft_id = isset($input['draft_id']) ? $input['draft_id'] : '';
        $approval_level = isset($input['approval_level']) ? intval($input['approval_level']) : 0;
        $approver_emp_id = $_GET["emp_id"];
        $comments = isset($input['comments']) ? $input['comments'] : '';
        
        $draft_id = mysqli_real_escape_string($conn, $draft_id);
        $approver_emp_id = mysqli_real_escape_string($conn, $approver_emp_id);
        $comments = mysqli_real_escape_string($conn, $comments);
        
        // Update approval record
        $sql = "UPDATE sop_creation_approvals 
                SET approval_status = 'approved', 
                    approver_emp_id = '" . $approver_emp_id . "', 
                    approval_comments = '" . $comments . "',
                    approval_date = NOW()
                WHERE draft_id = '" . $draft_id . "' AND approval_level = " . $approval_level;
        $conn->query($sql);
        
        // If not final level (4), create next level approval
        if ($approval_level < 4) {
            $next_level = $approval_level + 1;
            $role_map = [1 => 'checker', 2 => 'approver', 3 => 'qms_approver', 4 => 'dept_head'];
            $next_role = isset($role_map[$next_level]) ? $role_map[$next_level] : '';
            
            $next_sql = "INSERT INTO sop_creation_approvals 
                         (draft_id, approval_level, approver_role, approval_status, created_at)
                         VALUES ('" . $draft_id . "', " . $next_level . ", '" . $next_role . "', 'pending', NOW())";
            $conn->query($next_sql);
        } else {
            // Final approval - move to final table
            $draft_sql = "SELECT * FROM sop_creation_drafts WHERE draft_id = '" . $draft_id . "'";
            $draft_result = $conn->query($draft_sql);
            
            if ($draft_result->num_rows > 0) {
                $draft = $draft_result->fetch_assoc();
                $doc_info = json_decode($draft['document_info'], true);
                
                $sop_number = isset($doc_info['sop_number']) ? mysqli_real_escape_string($conn, $doc_info['sop_number']) : '';
                $version = isset($doc_info['version']) ? mysqli_real_escape_string($conn, $doc_info['version']) : '1.0';
                $title = isset($doc_info['title']) ? mysqli_real_escape_string($conn, $doc_info['title']) : '';
                $effective_date = isset($doc_info['effective_date']) ? mysqli_real_escape_string($conn, $doc_info['effective_date']) : null;
                
                $header_template_id = isset($draft['header_template_id']) ? mysqli_real_escape_string($conn, $draft['header_template_id']) : '';
                
                $final_sql = "INSERT INTO sop_creation_final 
                             (sop_number, version, title, template_id, template_name, header_template_id, section_order, content, document_info, header_config, footer_config, status, created_by, approved_by, effective_date, created_at)
                             VALUES ('" . $sop_number . "', '" . $version . "', '" . $title . "', '" . mysqli_real_escape_string($conn, $draft['template_id']) . "', '" . mysqli_real_escape_string($conn, $draft['template_name']) . "', '" . $header_template_id . "', '" . mysqli_real_escape_string($conn, $draft['section_order']) . "', '" . mysqli_real_escape_string($conn, $draft['content']) . "', '" . mysqli_real_escape_string($conn, $draft['document_info']) . "', '" . mysqli_real_escape_string($conn, $draft['header_config']) . "', '" . mysqli_real_escape_string($conn, $draft['footer_config']) . "', 'APPROVED', '" . mysqli_real_escape_string($conn, $draft['created_by']) . "', '" . $approver_emp_id . "', " . ($effective_date ? "'" . $effective_date . "'" : "NULL") . ", NOW())";
                $conn->query($final_sql);
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => 'SOP approved successfully']);
    }

    // Reject SOP
    else if ($_GET["type"] == "rejectSOP") {
        $draft_id = isset($input['draft_id']) ? $input['draft_id'] : '';
        $approval_level = isset($input['approval_level']) ? intval($input['approval_level']) : 0;
        $rejection_reason = isset($input['rejection_reason']) ? $input['rejection_reason'] : '';
        
        $draft_id = mysqli_real_escape_string($conn, $draft_id);
        $rejection_reason = mysqli_real_escape_string($conn, $rejection_reason);
        
        // Update approval record
        $sql = "UPDATE sop_creation_approvals 
                SET approval_status = 'rejected', 
                    rejection_reason = '" . $rejection_reason . "',
                    approval_date = NOW()
                WHERE draft_id = '" . $draft_id . "' AND approval_level = " . $approval_level;
        $conn->query($sql);
        
        // Update draft status
        $draft_sql = "UPDATE sop_creation_drafts SET status = 'REJECTED' WHERE draft_id = '" . $draft_id . "'";
        $conn->query($draft_sql);
        
        echo json_encode(['status' => 'success', 'message' => 'SOP rejected']);
    }

    // Request changes
    else if ($_GET["type"] == "requestChanges") {
        $draft_id = isset($input['draft_id']) ? $input['draft_id'] : '';
        $approval_level = isset($input['approval_level']) ? intval($input['approval_level']) : 0;
        $comments = isset($input['comments']) ? $input['comments'] : '';
        
        $draft_id = mysqli_real_escape_string($conn, $draft_id);
        $comments = mysqli_real_escape_string($conn, $comments);
        
        // Update approval record
        $sql = "UPDATE sop_creation_approvals 
                SET approval_status = 'changes_requested', 
                    approval_comments = '" . $comments . "',
                    approval_date = NOW()
                WHERE draft_id = '" . $draft_id . "' AND approval_level = " . $approval_level;
        $conn->query($sql);
        
        // Update draft status to CHANGES_REQUESTED so creator can edit it
        $draft_sql = "UPDATE sop_creation_drafts SET status = 'CHANGES_REQUESTED' WHERE draft_id = '" . $draft_id . "'";
        $conn->query($draft_sql);
        
        echo json_encode(['status' => 'success', 'message' => 'Changes requested']);
    }

    // Get drafts needing changes for current user
    else if ($_GET["type"] == "getDraftsNeedingChanges") {
        $emp_id = $_GET["emp_id"];
        $emp_id = mysqli_real_escape_string($conn, $emp_id);
        
        $sql = "SELECT d.*, 
                (SELECT approval_comments FROM sop_creation_approvals 
                 WHERE draft_id = d.draft_id AND approval_status = 'changes_requested' 
                 ORDER BY approval_date DESC LIMIT 1) as change_request_comments
                FROM sop_creation_drafts d
                WHERE d.created_by = '" . $emp_id . "' 
                AND d.status = 'CHANGES_REQUESTED'
                ORDER BY d.updated_at DESC";
        $result = $conn->query($sql);
        
        $drafts = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['section_order'] = json_decode($row['section_order'], true);
                $row['content'] = json_decode($row['content'], true);
                $row['section_names'] = json_decode($row['section_names'], true);
                $row['document_info'] = json_decode($row['document_info'], true);
                $row['header_config'] = json_decode($row['header_config'], true);
                $row['footer_config'] = json_decode($row['footer_config'], true);
                $row['approval_info'] = json_decode($row['approval_info'], true);
                $drafts[] = $row;
            }
        }
        
        echo json_encode($drafts);
    }

    // Continue editing a draft (load it for editing)
    else if ($_GET["type"] == "continueEditing") {
        // Support both GET and POST parameters
        $draft_id = isset($_GET["draft_id"]) ? $_GET["draft_id"] : (isset($input['draft_id']) ? $input['draft_id'] : '');
        // Use emp_id from token authentication (set earlier in the file)
        $emp_id = $_GET["emp_id"];
        
        $draft_id = mysqli_real_escape_string($conn, $draft_id);
        $emp_id = mysqli_real_escape_string($conn, $emp_id);
        
        if (empty($draft_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing draft_id']);
            exit;
        }
        
        if (empty($emp_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Authentication failed. Please login again.']);
            exit;
        }
        
        // Get draft and verify user is creator
        $sql = "SELECT * FROM sop_creation_drafts 
                WHERE draft_id = '" . $draft_id . "' 
                AND created_by = '" . $emp_id . "' 
                AND status IN ('DRAFT', 'CHANGES_REQUESTED')";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row['section_order'] = json_decode($row['section_order'], true);
            $row['content'] = json_decode($row['content'], true);
            $row['section_names'] = json_decode($row['section_names'], true);
            $row['document_info'] = json_decode($row['document_info'], true);
            $row['header_config'] = json_decode($row['header_config'], true);
            $row['footer_config'] = json_decode($row['footer_config'], true);
            $row['approval_info'] = json_decode($row['approval_info'], true);
            
            // Get change request comments if any
            $comment_sql = "SELECT approval_comments, approval_level FROM sop_creation_approvals 
                           WHERE draft_id = '" . $draft_id . "' 
                           AND approval_status = 'changes_requested' 
                           ORDER BY approval_date DESC LIMIT 1";
            $comment_result = $conn->query($comment_sql);
            if ($comment_result->num_rows > 0) {
                $comment_row = $comment_result->fetch_assoc();
                $row['change_request_comments'] = $comment_row['approval_comments'];
                $row['change_request_level'] = $comment_row['approval_level'];
            }
            
            echo json_encode(['status' => 'success', 'draft' => $row]);
        } else {
            // Check if draft exists and provide helpful error message
            $check_sql = "SELECT draft_id, created_by, status FROM sop_creation_drafts WHERE draft_id = '" . $draft_id . "'";
            $check_result = $conn->query($check_sql);
            if ($check_result->num_rows > 0) {
                $check_row = $check_result->fetch_assoc();
                // Draft exists but either user is not creator or status is not editable
                if ($check_row['created_by'] != $emp_id) {
                    echo json_encode(['status' => 'error', 'message' => 'You do not have permission to edit this draft. It was created by a different user.']);
                } else if (!in_array($check_row['status'], ['DRAFT', 'CHANGES_REQUESTED'])) {
                    echo json_encode(['status' => 'error', 'message' => 'This draft cannot be edited. Current status: ' . $check_row['status']]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Draft found but cannot be loaded for editing']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Draft not found']);
            }
        }
    }

    // Delete draft
    else if ($_GET["type"] == "deleteDraft") {
        $draft_id = isset($_GET["draft_id"]) ? $_GET["draft_id"] : '';
        $created_by = $_GET["emp_id"];
        
        $draft_id = mysqli_real_escape_string($conn, $draft_id);
        $created_by = mysqli_real_escape_string($conn, $created_by);
        
        // Only allow deletion if user is the creator and draft is not submitted
        $check_sql = "SELECT status FROM sop_creation_drafts WHERE draft_id = '" . $draft_id . "' AND created_by = '" . $created_by . "'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            if ($row['status'] == 'DRAFT') {
                // Delete approval records first
                $delete_approvals = "DELETE FROM sop_creation_approvals WHERE draft_id = '" . $draft_id . "'";
                $conn->query($delete_approvals);
                
                // Delete draft
                $sql = "DELETE FROM sop_creation_drafts WHERE draft_id = '" . $draft_id . "'";
                if ($conn->query($sql)) {
                    echo json_encode(['status' => 'success', 'message' => 'Draft deleted successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to delete: ' . $conn->error]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete submitted draft']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Draft not found or unauthorized']);
        }
    }

    // Get SOP Log (Approved SOPs)
    else if ($_GET["type"] == "getSOPLog") {
        $sql = "SELECT * FROM sop_creation_final 
                ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        $sops = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Decode JSON fields
                $row['section_order'] = json_decode($row['section_order'], true);
                $row['content'] = json_decode($row['content'], true);
                $row['section_names'] = json_decode($row['section_names'], true);
                $row['document_info'] = json_decode($row['document_info'], true);
                $row['header_config'] = json_decode($row['header_config'], true);
                $row['footer_config'] = json_decode($row['footer_config'], true);
                
                // Ensure sop_number, title, and effective_date are accessible at root level
                if (!isset($row['sop_number']) && isset($row['document_info']['sop_number'])) {
                    $row['sop_number'] = $row['document_info']['sop_number'];
                }
                if (!isset($row['title']) && isset($row['document_info']['title'])) {
                    $row['title'] = $row['document_info']['title'];
                }
                if (!isset($row['effective_date']) && isset($row['document_info']['effective_date'])) {
                    $row['effective_date'] = $row['document_info']['effective_date'];
                }
                
                $sops[] = $row;
            }
        }
        
        echo json_encode($sops);
    }

    // Get SOP by Number and Version
    else if ($_GET["type"] == "getSOPByNumber") {
        $sop_number = isset($_GET["sop_number"]) ? $_GET["sop_number"] : '';
        $version = isset($_GET["version"]) ? $_GET["version"] : '';
        
        $sop_number = mysqli_real_escape_string($conn, $sop_number);
        $version = mysqli_real_escape_string($conn, $version);
        
        $sql = "SELECT * FROM sop_creation_final 
                WHERE sop_number = '" . $sop_number . "'";
        
        if ($version) {
            $sql .= " AND version = '" . $version . "'";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 1";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row['section_order'] = json_decode($row['section_order'], true);
            $row['content'] = json_decode($row['content'], true);
            $row['section_names'] = json_decode($row['section_names'], true);
            $row['document_info'] = json_decode($row['document_info'], true);
            $row['header_config'] = json_decode($row['header_config'], true);
            $row['footer_config'] = json_decode($row['footer_config'], true);
            
            // Ensure sop_number, title, and effective_date are accessible at root level
            if (!isset($row['sop_number']) && isset($row['document_info']['sop_number'])) {
                $row['sop_number'] = $row['document_info']['sop_number'];
            }
            if (!isset($row['title']) && isset($row['document_info']['title'])) {
                $row['title'] = $row['document_info']['title'];
            }
            if (!isset($row['effective_date']) && isset($row['document_info']['effective_date'])) {
                $row['effective_date'] = $row['document_info']['effective_date'];
            }
            // Ensure final_id is included for PDF generation
            if (isset($row['final_id'])) {
                $row['final_id'] = $row['final_id'];
            }
            
            echo json_encode($row);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'SOP not found']);
        }
    }

    // Generate PDF with TCPDF (header and footer on every page)
    // Supports GET (with draft_id or final_id) and POST (with full data)
else if ($_GET["type"] == "generatePDF") {

    $draft_id = $_GET["draft_id"] ?? '';
    $final_id = $_GET["final_id"] ?? '';

    if ($draft_id) {
        $draft_id = mysqli_real_escape_string($conn, $draft_id);
        $res = $conn->query("SELECT * FROM sop_creation_drafts WHERE draft_id='$draft_id'");
        if (!$res->num_rows) die("Draft not found");
        $row = $res->fetch_assoc();
    } 
    else if ($final_id) {
        $final_id = mysqli_real_escape_string($conn, $final_id);
        $res = $conn->query("SELECT * FROM sop_creation_final WHERE final_id='$final_id'");
        if (!$res->num_rows) die("Final SOP not found");
        $row = $res->fetch_assoc();
    } 
    else {
        $row = [
            'document_info' => json_encode($input['document_info'] ?? []),
            'header_config' => json_encode($input['header_config'] ?? []),
            'footer_config' => json_encode($input['footer_config'] ?? []),
            'section_order' => json_encode($input['section_order'] ?? []),
            'content' => json_encode($input['content'] ?? []),
            'section_names' => json_encode($input['section_names'] ?? [])
        ];
    }

    $doc = json_decode($row['document_info'], true);
    $hdr = json_decode($row['header_config'], true);
    $ftr = json_decode($row['footer_config'], true);
    $section_order = json_decode($row['section_order'], true);
    $content = json_decode($row['content'], true);
    $section_names = json_decode($row['section_names'], true);

    // Get header_template_id and map to layout_type
    $header_template_id = $row['header_template_id'] ?? '';
    $layout_type_map = [
        'H1' => 'full_branding',
        'H2' => 'simple',
        'H3' => 'minimal',
        'H4' => 'professional',
        'H5' => 'corporate',
        'H6' => 'centered_branding',
        'H7' => 'two_column',
        'H8' => 'modern_integrated'
    ];
    $layout_type = $layout_type_map[$header_template_id] ?? 'full_branding'; // Default to full_branding if not found

    $sop_number = $doc['sop_number'] ?? 'DRAFT';
    $version = $doc['version'] ?? '1.0';
    $title = $doc['title'] ?? '';
    $effective_date = !empty($doc['effective_date']) ? date('d-M-Y', strtotime($doc['effective_date'])) : '';

    $company = $hdr['company_name'] ?? '';
    $dept = $hdr['department'] ?? '';
    $copy_no = $hdr['copy_no'] ?? '';
    $company_slogan = $hdr['company_slogan'] ?? '';
    $show_logo = !empty($hdr['show_logo']);
    $logo_path = $hdr['logo_path'] ?? '';
    
    // Extract all header config flags
    $show_master_copy = !empty($hdr['show_master_copy']);
    $show_controlled_copy = !empty($hdr['show_controlled_copy']);
    $show_main_title = !empty($hdr['show_main_title']);
    $show_department = !empty($hdr['show_department']);
    $show_copy_no = !empty($hdr['show_copy_no']);
    $show_title = !empty($hdr['show_title']);
    $show_sop_no = !empty($hdr['show_sop_no']);
    $show_version_no = !empty($hdr['show_version_no']);
    $show_effective_date = !empty($hdr['show_effective_date']);
    $show_supersede_no = !empty($hdr['show_supersede_no']);
    $supersede_no = $hdr['supersede_no'] ?? '';
    $show_supersede_version = !empty($hdr['show_supersede_version']);
    $supersede_version = $hdr['supersede_version'] ?? '';
    $show_next_review_date = !empty($hdr['show_next_review_date']);
    $next_review_date = !empty($hdr['next_review_date']) ? date('d-M-Y', strtotime($hdr['next_review_date'])) : '';

    // Handle logo path - prepare for TCPDF
    $logo_src = '';
    if ($show_logo && !empty($logo_path)) {
        // For base64 data URLs, pass as-is (TCPDF writeHTML handles it)
        if (strpos($logo_path, 'data:image') === 0) {
            $logo_src = $logo_path;
        }
        // For URLs, pass as-is
        elseif (strpos($logo_path, 'http') === 0) {
            $logo_src = $logo_path;
        }
        // For file paths, try to resolve to absolute path
        else {
            // Try absolute path first
            $abs_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($logo_path, '/');
            if (file_exists($abs_path)) {
                $logo_src = $abs_path;
            } 
            // Try as web-accessible path
            else {
                $logo_src = '/' . ltrim($logo_path, '/');
            }
        }
    }

    $footer_left = trim(($ftr['confidentiality_notice'] ?? '') . ' ' . ($ftr['footer_text'] ?? ''));
    $footer_html = '
        <table width="100%" style="font-size:8pt; font-family:times;">
            <tr>
                <td align="left">'.$footer_left.'</td>
                <td align="right">Page {PAGENO} of {nb}</td>
            </tr>
        </table>';

    class SOP_PDF extends TCPDF {

        public $headerData;
        public $footerHTML;

        public function Header() {
            // Set Times New Roman font for header
            $this->SetFont('times', '', 9);
            
            $d = $this->headerData;
            $layout_type = $d['layout_type'] ?? 'full_branding';
            
            // Render header based on layout_type
            switch ($layout_type) {
                case 'full_branding':
                    $this->renderFullBrandingHeader($d);
                    break;
                case 'simple':
                    $this->renderSimpleHeader($d);
                    break;
                case 'minimal':
                    $this->renderMinimalHeader($d);
                    break;
                case 'professional':
                    $this->renderProfessionalHeader($d);
                    break;
                case 'corporate':
                    $this->renderCorporateHeader($d);
                    break;
                case 'centered_branding':
                    $this->renderCenteredBrandingHeader($d);
                    break;
                case 'two_column':
                    $this->renderTwoColumnHeader($d);
                    break;
                case 'modern_integrated':
                    $this->renderModernIntegratedHeader($d);
                    break;
                default:
                    $this->renderFullBrandingHeader($d);
            }
        }
        
        private function renderLogo($logo_path, $y_pos, $width_percent = 0.2) {
            $logo_rendered = false;
            $page_width = $this->getPageWidth() - $this->lMargin - $this->rMargin;
            $logo_x = $this->lMargin + ($page_width * $width_percent);
            
            if (!empty($logo_path)) {
                try {
                    // Handle base64 data URL
                    if (strpos($logo_path, 'data:image') === 0) {
                        $image_data = explode(',', $logo_path);
                        if (count($image_data) > 1) {
                            $image_data = base64_decode($image_data[1]);
                            $tmp_file = tempnam(sys_get_temp_dir(), 'logo_');
                            file_put_contents($tmp_file, $image_data);
                            $this->Image($tmp_file, $logo_x, $y_pos, 0, 15, '', '', '', false, 300, 'C');
                            @unlink($tmp_file);
                            $logo_rendered = true;
                        }
                    }
                    // Handle file path
                    elseif (file_exists($logo_path)) {
                        $this->Image($logo_path, $logo_x, $y_pos, 0, 15, '', '', '', false, 300, 'C');
                        $logo_rendered = true;
                    }
                } catch (Exception $e) {
                    // If Image() fails, will use HTML img tag
                }
            }
            return $logo_rendered;
        }
        
        private function renderLogoImage($logo_path, $x, $y, $width_mm, $height_mm) {
            $logo_rendered = false;
            
            if (!empty($logo_path)) {
                try {
                    // Handle base64 data URL
                    if (strpos($logo_path, 'data:image') === 0) {
                        $image_data = explode(',', $logo_path);
                        if (count($image_data) > 1) {
                            $image_data = base64_decode($image_data[1]);
                            $tmp_file = tempnam(sys_get_temp_dir(), 'logo_');
                            file_put_contents($tmp_file, $image_data);
                            $this->Image($tmp_file, $x, $y, $width_mm, $height_mm, '', '', '', false, 300, '', false, false, 0);
                            @unlink($tmp_file);
                            $logo_rendered = true;
                        }
                    }
                    // Handle file path
                    elseif (file_exists($logo_path)) {
                        $this->Image($logo_path, $x, $y, $width_mm, $height_mm, '', '', '', false, 300, '', false, false, 0);
                        $logo_rendered = true;
                    }
                } catch (Exception $e) {
                    // If Image() fails, will use HTML img tag
                }
            }
            return $logo_rendered;
        }
        
        private function renderFullBrandingHeader($d) {
            $start_y = $this->GetY();
            $page_width = $this->getPageWidth() - $this->lMargin - $this->rMargin;
            $center_x = $this->lMargin + ($page_width / 2);
            $logo_width = 30; // mm (120px ≈ 30mm)
            $logo_height = 15; // mm (60px ≈ 15mm)
            
            // Render logo using TCPDF Image() method if available
            $logo_y = $start_y;
            $logo_rendered = false;
            if (!empty($d['logo'])) {
                $logo_rendered = $this->renderLogoImage($d['logo'], $center_x - ($logo_width/2), $logo_y, $logo_width, $logo_height);
                if ($logo_rendered) {
                    $logo_y += $logo_height + 2;
                }
            }
            
            // Build HTML table for watermarks and text
            $html = '<table width="100%" cellpadding="2" border="0" style="font-family:times;">
                        <tr>';
            
            // Left: Master Copy watermark (1fr)
            if ($d['show_master_copy']) {
                $html .= '<td width="20%" align="center" valign="middle" style="color:#999; font-size:7pt; font-style:italic; opacity:0.5;">Master Copy</td>';
            } else {
                $html .= '<td width="20%"></td>';
            }
            
            // Center: Company, Slogan (2fr) - CENTERED
            $html .= '<td width="60%" align="center" valign="middle" style="text-align:center;">';
            
            // Company name centered below logo
            if (!empty($d['company'])) {
                $html .= '<div style="font-size:11pt; font-weight:bold; color:#333; margin-top:'.($logo_rendered ? '2mm' : '0').';">'.htmlspecialchars($d['company']).'</div>';
            }
            
            // Slogan centered below company name
            if (!empty($d['slogan'])) {
                $html .= '<div style="font-size:9pt; color:#666;">'.htmlspecialchars($d['slogan']).'</div>';
            }
            
            $html .= '</td>';
            
            // Right: Controlled Copy watermark (1fr)
            if ($d['show_controlled_copy']) {
                $html .= '<td width="20%" align="center" valign="middle" style="color:#999; font-size:7pt; font-style:italic; opacity:0.5;">Controlled Copy Stamp</td>';
            } else {
                $html .= '<td width="20%"></td>';
            }
            
            $html .= '</tr></table>';
            
            // Set Y position after logo if rendered
            if ($logo_rendered) {
                $this->SetY($logo_y);
            }
            $this->writeHTML($html);
            
            // SOP Info Table
            $this->renderSOPInfoTable($d, 3);
        }
        
        private function renderSimpleHeader($d) {
            $start_y = $this->GetY();
            $page_width = $this->getPageWidth() - $this->lMargin - $this->rMargin;
            $center_x = $this->lMargin + ($page_width / 2);
            $logo_width = 25; // mm (100px ≈ 25mm)
            $logo_height = 12.5; // mm (50px ≈ 12.5mm)
            
            // Render logo using TCPDF Image() method
            $logo_rendered = false;
            $logo_x = $center_x - ($logo_width / 2) - 15; // Position logo to the left of center
            if (!empty($d['logo'])) {
                $logo_rendered = $this->renderLogoImage($d['logo'], $logo_x, $start_y, $logo_width, $logo_height);
            }
            
            // Build HTML for company name
            $html = '<table width="100%" cellpadding="2" border="0" style="font-family:times;">
                        <tr>
                            <td align="center" style="padding:3mm;">';
            
            if (!empty($d['company'])) {
                $html .= '<span style="font-size:12pt; font-weight:600; color:#333;">'.htmlspecialchars($d['company']).'</span>';
            }
            
            $html .= '</td></tr></table>';
            $this->writeHTML($html);
            
            // SOP Info Table
            $this->renderSOPInfoTable($d, 3);
        }
        
        private function renderMinimalHeader($d) {
            // Minimal header: Just SOP Info Table (no branding)
            $this->renderSOPInfoTable($d, 2);
        }
        
        private function renderProfessionalHeader($d) {
            $start_y = $this->GetY();
            $logo_width = 25; // mm
            $logo_height = 12.5; // mm
            $logo_x = $this->lMargin;
            
            // Render logo using TCPDF Image() method on left
            $logo_rendered = false;
            if (!empty($d['logo'])) {
                $logo_rendered = $this->renderLogoImage($d['logo'], $logo_x, $start_y, $logo_width, $logo_height);
            }
            
            // Build HTML for company info on right
            $html = '<table width="100%" cellpadding="2" border="0" style="font-family:times;">
                        <tr>';
            
            if ($logo_rendered) {
                $html .= '<td width="'.($logo_width + 5).'mm"></td>'; // Space for logo
            } else {
                $html .= '<td width="20%"></td>';
            }
            
            // Company info on right
            $html .= '<td width="'.($logo_rendered ? 'auto' : '80%').'" align="left" valign="middle">';
            if (!empty($d['company'])) {
                $html .= '<div style="font-weight:600; font-size:11pt; color:#333;">'.htmlspecialchars($d['company']).'</div>';
            }
            if (!empty($d['slogan'])) {
                $html .= '<div style="font-size:9pt; color:#666; margin-top:1mm;">'.htmlspecialchars($d['slogan']).'</div>';
            }
            $html .= '</td></tr></table>';
            $this->writeHTML($html);
            
            // SOP Info Table
            $this->renderSOPInfoTable($d, 3);
        }
        
        private function renderCorporateHeader($d) {
            // Corporate header: Logo left, Company info right (text-align: right, space-between)
            $html = '<table width="100%" cellpadding="2" border="0" style="font-family:times; border-bottom:2px solid #336600;">
                        <tr>';
            
            // Logo on left
            if (!empty($d['logo'])) {
                $logo_src = htmlspecialchars($d['logo'], ENT_QUOTES, 'UTF-8');
                $html .= '<td width="20%" align="left" valign="top" style="flex-shrink:0;">';
                $html .= '<img src="'.$logo_src.'" height="50" style="max-height:50px; max-width:100px; object-fit:contain;">';
                $html .= '</td>';
            } else {
                $html .= '<td width="20%"></td>';
            }
            
            // Company info on right (text-align: right)
            $html .= '<td width="80%" align="right" valign="top" style="flex:1; text-align:right;">';
            if (!empty($d['company'])) {
                $html .= '<div style="font-weight:600; font-size:0.95rem; color:#333; margin-bottom:0.25rem;">'.htmlspecialchars($d['company']).'</div>';
            }
            if (!empty($d['slogan'])) {
                $html .= '<div style="font-size:0.8rem; color:#666;">'.htmlspecialchars($d['slogan']).'</div>';
            }
            $html .= '</td></tr></table>';
            $this->writeHTML($html);
            
            // SOP Info Table
            $this->renderSOPInfoTable($d, 3);
        }
        
        private function renderCenteredBrandingHeader($d) {
            $start_y = $this->GetY();
            $page_width = $this->getPageWidth() - $this->lMargin - $this->rMargin;
            $center_x = $this->lMargin + ($page_width / 2);
            $logo_width = 25; // mm
            $logo_height = 12.5; // mm
            
            // Render logo using TCPDF Image() method centered
            $logo_rendered = false;
            $logo_y = $start_y;
            if (!empty($d['logo'])) {
                $logo_rendered = $this->renderLogoImage($d['logo'], $center_x - ($logo_width/2), $logo_y, $logo_width, $logo_height);
                if ($logo_rendered) {
                    $logo_y += $logo_height + 2;
                }
            }
            
            // Build HTML for company name and slogan
            $html = '<table width="100%" cellpadding="2" border="0" style="font-family:times; background-color:#f8f9fa; border:1px solid #ddd;">
                        <tr>
                            <td align="center" style="padding:4mm;">';
            
            // Company name centered below logo
            if (!empty($d['company'])) {
                $html .= '<div style="font-size:12pt; font-weight:600; color:#333; margin-top:'.($logo_rendered ? '2mm' : '0').';">'.htmlspecialchars($d['company']).'</div>';
            }
            
            // Slogan centered below company name
            if (!empty($d['slogan'])) {
                $html .= '<div style="font-size:10pt; color:#666; font-style:italic; margin-top:1mm;">'.htmlspecialchars($d['slogan']).'</div>';
            }
            
            $html .= '</td></tr></table>';
            
            // Set Y position after logo if rendered
            if ($logo_rendered) {
                $this->SetY($logo_y);
            }
            $this->writeHTML($html);
            
            // SOP Info Table
            $this->renderSOPInfoTable($d, 3);
        }
        
        private function renderTwoColumnHeader($d) {
            // Two-column header: Logo/Company/Slogan left column, SOP info right column
            $html = '<table width="100%" cellpadding="2" border="0" style="font-family:times; border-left:4px solid #336600;">
                        <tr>
                            <td width="50%" style="border-right:1px solid #e0e0e0; padding-right:1rem; text-align:left;">';
            
            // Logo in left column
            if (!empty($d['logo'])) {
                $logo_src = htmlspecialchars($d['logo'], ENT_QUOTES, 'UTF-8');
                $html .= '<div style="margin-bottom:0.5rem;">';
                $html .= '<img src="'.$logo_src.'" height="50" style="max-height:50px; max-width:100px; object-fit:contain;">';
                $html .= '</div>';
            }
            
            // Company name in left column
            if (!empty($d['company'])) {
                $html .= '<div style="font-size:0.95rem; font-weight:600; color:#333; margin-bottom:0.25rem;">'.htmlspecialchars($d['company']).'</div>';
            }
            
            // Slogan in left column
            if (!empty($d['slogan'])) {
                $html .= '<div style="font-size:0.8rem; color:#666; font-style:italic;">'.htmlspecialchars($d['slogan']).'</div>';
            }
            
            $html .= '</td><td width="50%" align="right" style="padding-left:1rem; text-align:right;">';
            
            // SOP info in right column
            if ($d['show_department']) {
                $html .= '<div style="font-size:0.8rem; color:#555; margin-bottom:0.3rem; line-height:1.4;"><strong>Department:</strong> '.htmlspecialchars($d['dept']).'</div>';
            }
            if ($d['show_copy_no']) {
                $html .= '<div style="font-size:0.8rem; color:#555; margin-bottom:0.3rem; line-height:1.4;"><strong>Copy No:</strong> '.htmlspecialchars($d['copy']).'</div>';
            }
            if ($d['show_sop_no']) {
                $html .= '<div style="font-size:0.8rem; color:#555; margin-bottom:0.3rem; line-height:1.4;"><strong>SOP No:</strong> '.htmlspecialchars($d['sop']).'</div>';
            }
            if ($d['show_version_no']) {
                $html .= '<div style="font-size:0.8rem; color:#555; margin-bottom:0.3rem; line-height:1.4;"><strong>Version:</strong> '.htmlspecialchars($d['ver']).'</div>';
            }
            
            $html .= '</td></tr></table>';
            $this->writeHTML($html);
            
            // SOP Info Table (simplified)
            $this->renderSOPInfoTable($d, 3, true);
        }
        
        private function renderModernIntegratedHeader($d) {
            $start_y = $this->GetY();
            $page_width = $this->getPageWidth() - $this->lMargin - $this->rMargin;
            $logo_width = 20; // mm (smaller for integrated header)
            $logo_height = 10; // mm
            $logo_x = $this->lMargin;
            
            // Render logo using TCPDF Image() method on left
            $logo_rendered = false;
            if (!empty($d['logo'])) {
                $logo_rendered = $this->renderLogoImage($d['logo'], $logo_x, $start_y, $logo_width, $logo_height);
            }
            
            // Build HTML for horizontal bar
            $html = '<table width="100%" cellpadding="2" border="0" style="font-family:times; background-color:#336600; color:white;">
                        <tr>';
            
            if ($logo_rendered) {
                $html .= '<td width="'.($logo_width + 3).'mm"></td>'; // Space for logo
            } else {
                $html .= '<td width="15%"></td>';
            }
            
            // Company name CENTER
            $html .= '<td width="'.($logo_rendered ? 'auto' : '60%').'" align="center" valign="middle" style="text-align:center; font-weight:600; font-size:12pt;">';
            if (!empty($d['company'])) {
                $html .= htmlspecialchars($d['company']);
            }
            $html .= '</td>';
            
            // SOP info RIGHT
            $html .= '<td width="25%" align="right" valign="middle" style="text-align:right; font-size:9pt;">';
            $sop_info_parts = [];
            if ($d['show_sop_no']) {
                $sop_info_parts[] = '<strong>SOP No:</strong> '.htmlspecialchars($d['sop']);
            }
            if ($d['show_version_no']) {
                $sop_info_parts[] = '<strong>Version:</strong> '.htmlspecialchars($d['ver']);
            }
            if ($d['show_copy_no']) {
                $sop_info_parts[] = '<strong>Copy:</strong> '.htmlspecialchars($d['copy']);
            }
            $html .= implode(' ', $sop_info_parts);
            $html .= '</td></tr></table>';
            $this->writeHTML($html);
            
            // SOP Info Table
            $this->renderSOPInfoTable($d, 3);
        }
        
        private function renderSOPInfoTable($d, $colspan = 3, $simplified = false) {
            $html = '<table width="100%" border="1" cellpadding="3" style="font-size:9pt; font-family:times;">';
            
            // Main title row
            if ($d['show_main_title']) {
                $html .= '<tr bgcolor="#e0e0e0">
                            <td colspan="'.$colspan.'" align="center"><b>STANDARD OPERATING PROCEDURE</b></td>
                          </tr>';
            }
            
            // Department and Copy No row
            if ($d['show_department'] || $d['show_copy_no']) {
                $html .= '<tr>';
                if ($d['show_department']) {
                    $html .= '<td><strong>Department:</strong> '.htmlspecialchars($d['dept']).'</td>';
                }
                if (!$d['show_department'] && $d['show_copy_no']) {
                    $html .= '<td colspan="'.($colspan-1).'"></td>';
                }
                if ($d['show_copy_no']) {
                    $colspan_copy = ($d['show_department'] ? 1 : $colspan);
                    $html .= '<td colspan="'.$colspan_copy.'"><strong>Copy No:</strong> '.htmlspecialchars($d['copy']).'</td>';
                }
                $html .= '</tr>';
            }
            
            // Title row
            if ($d['show_title']) {
                $html .= '<tr bgcolor="#f5f5f5">
                            <td colspan="'.$colspan.'"><strong>Title:</strong> '.htmlspecialchars($d['title']).'</td>
                          </tr>';
            }
            
            // SOP No, Version, Effective Date row
            if ($d['show_sop_no'] || $d['show_version_no'] || $d['show_effective_date']) {
                $html .= '<tr>';
                if ($d['show_sop_no']) {
                    $html .= '<td><strong>SOP No:</strong> '.htmlspecialchars($d['sop']).'</td>';
                }
                if ($d['show_version_no']) {
                    $html .= '<td><strong>Version:</strong> '.htmlspecialchars($d['ver']).'</td>';
                }
                if ($d['show_effective_date']) {
                    $html .= '<td><strong>Effective:</strong> '.htmlspecialchars($d['eff']).'</td>';
                }
                $html .= '</tr>';
            }
            
            // Supersede and Next Review row (only for certain templates)
            if (!$simplified && ($d['show_supersede_no'] || $d['show_supersede_version'] || $d['show_next_review_date'])) {
                $html .= '<tr>';
                if ($d['show_supersede_no']) {
                    $html .= '<td><strong>Supersede No:</strong> '.htmlspecialchars($d['supersede_no']).'</td>';
                }
                if ($d['show_supersede_version']) {
                    $html .= '<td><strong>Supersede Version:</strong> '.htmlspecialchars($d['supersede_version']).'</td>';
                }
                if ($d['show_next_review_date']) {
                    $colspan_review = ($d['show_supersede_no'] || $d['show_supersede_version'] ? 1 : $colspan);
                    $html .= '<td colspan="'.$colspan_review.'"><strong>Next Review date:</strong> '.htmlspecialchars($d['next_review_date']).'</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</table><br>';
            $this->writeHTML($html);
        }

        public function Footer() {
            // Set Times New Roman font for footer
            $this->SetFont('times', '', 8);
            $this->SetY(-12);
            $footer = str_replace(
                ['{PAGENO}','{nb}'],
                [$this->getAliasNumPage(),$this->getAliasNbPages()],
                $this->footerHTML
            );
            $this->writeHTML($footer);
        }
    }

    $pdf = new SOP_PDF('P','mm','A4',true,'UTF-8',false);

    $pdf->SetMargins(12,70,12);     // <-- fixed safe header space
    $pdf->SetAutoPageBreak(true,15);
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);
    
    // Set Times New Roman font for all PDF content
    $pdf->SetFont('times', '', 10);

    $pdf->headerData = [
        'layout_type' => $layout_type,
        'company' => $company,
        'dept' => $dept,
        'copy' => $copy_no,
        'title' => $title,
        'sop' => $sop_number,
        'ver' => $version,
        'eff' => $effective_date,
        'slogan' => $company_slogan,
        'logo' => $logo_src,
        'show_master_copy' => $show_master_copy,
        'show_controlled_copy' => $show_controlled_copy,
        'show_main_title' => $show_main_title,
        'show_department' => $show_department,
        'show_copy_no' => $show_copy_no,
        'show_title' => $show_title,
        'show_sop_no' => $show_sop_no,
        'show_version_no' => $show_version_no,
        'show_effective_date' => $show_effective_date,
        'show_supersede_no' => $show_supersede_no,
        'supersede_no' => $supersede_no,
        'show_supersede_version' => $show_supersede_version,
        'supersede_version' => $supersede_version,
        'show_next_review_date' => $show_next_review_date,
        'next_review_date' => $next_review_date
    ];

    $pdf->footerHTML = $footer_html;

    $pdf->AddPage();

    foreach ($section_order as $sid) {
        // Get section display name from section_names array, fallback to formatted key
        $name = '';
        if (!empty($section_names[$sid])) {
            // Use custom display name if set
            $name = $section_names[$sid];
        } else {
            // Fallback: format the key (replace underscores with spaces, capitalize)
            $name = ucwords(str_replace('_', ' ', $sid));
        }
        
        $text = $content[$sid] ?? '';

        // Use Times New Roman font in HTML
        $pdf->writeHTML("
            <h3 style='font-family:times; font-size:12pt; font-weight:bold;'>".htmlspecialchars($name)."</h3>
            <div style='font-family:times; line-height:1.6; font-size:10pt;'>$text</div>
            <br>
        ");
    }

    $filename = 'SOP_'.$sop_number.'_v'.$version.'.pdf';
    $pdf->Output($filename,'I');
    exit;
}
    // Invalid request type
    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request type']);
    }

} else {
    echo "{\"status\":\"invalid\"}";
}

$conn->close();
?>
