<?php
// Standalone Visitor Registration Form - No Login Required
// Access directly: https://yourdomain.com/php/visitor-form.php?plant_id=1

ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';

date_default_timezone_set("Asia/Kolkata");
$timestamp = time();
$entry_date = date("Y-m-d H:i:s", $timestamp);

// Get plant_id from URL parameter
$plant_id = isset($_GET['plant_id']) ? $conn->real_escape_string(trim($_GET['plant_id'])) : '1';

// Handle form submission
$success_message = '';
$error_message = '';
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;
    
    // Sanitize input
    $e = function($v) use ($conn) { return $conn->real_escape_string(isset($v) ? trim($v) : ''); };
    
    $visitDate = $e($_POST['visitDate'] ?? '');
    $phoneNumber = $e($_POST['phoneNumber'] ?? '');
    $visitorName = $e($_POST['visitorName'] ?? '');
    $email = $e($_POST['email'] ?? '');
    $category = $e($_POST['category'] ?? '');
    $company = $e($_POST['company'] ?? '');
    $department_name = ''; // Department will be assigned by security
    $purpose = $e($_POST['purpose'] ?? '');
    $country = $e($_POST['country'] ?? '');
    // State field - both inputs use name="state", but only one is visible/active at a time
    $state = $e($_POST['state'] ?? '');
    $city = $e($_POST['city'] ?? '');
    
    // Handle photo upload (base64)
    $photo = '';
    if (isset($_POST['photo']) && !empty($_POST['photo'])) {
        $photo = $conn->real_escape_string($_POST['photo']);
    }
    
    // Validation (department_name removed - will be assigned by security)
    $validation_errors = array();
    if (empty($visitDate)) $validation_errors[] = 'Visit Date';
    if (empty($phoneNumber)) $validation_errors[] = 'Contact Number';
    if (empty($visitorName)) $validation_errors[] = 'Full Name';
    if (empty($category)) $validation_errors[] = 'Category';
    if (empty($country)) $validation_errors[] = 'Country';
    if (empty($state)) $validation_errors[] = 'State';
    if (empty($city)) $validation_errors[] = 'City';
    
    if (!empty($validation_errors)) {
        $error_message = 'Please fill all required fields: ' . implode(', ', $validation_errors);
    } else {
        // Generate passNo
        $passNo = '';
        $maxIdSql = "SELECT MAX(id) as max_id FROM gatepass";
        $maxResult = $conn->query($maxIdSql);
        if ($maxResult && $maxResult->num_rows > 0) {
            $maxRow = $maxResult->fetch_assoc();
            $nextId = ($maxRow['max_id'] ?? 0) + 1;
            $passNo = 'GP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        } else {
            $passNo = 'GP-0001';
        }
        
        // Insert into database
        $sql = "INSERT INTO gatepass (plant_id, gatepassType, passNo, visitDate, phoneNumber, visitorName, email, category, company, department_name, purpose, country, state, city, entryOn, photo, status) VALUES "
            ."('".$plant_id."','ON_GATE','".$passNo."','".$visitDate."','".$phoneNumber."','".$visitorName."','".$email."','".$category."','".$company."','".$department_name."','".$purpose."','".$country."','".$state."','".$city."','".$entry_date."','".$photo."','PENDING')";
        
        if ($conn->query($sql)) {
            $success_message = 'Visitor form submitted successfully! Your Pass No: ' . $passNo . '. Security will assign your meeting person shortly.';
            // Clear form data
            $_POST = array();
        } else {
            $error_message = 'Error: ' . $conn->error;
        }
    }
}

// Department will be assigned by security, no need to load departments here

// Get visitor data by phone number (for auto-fill)
$visitorData = null;
if (isset($_GET['phone']) && !empty($_GET['phone'])) {
    $phone = $conn->real_escape_string(trim($_GET['phone']));
    $visitorSql = "SELECT * FROM gatepass WHERE phoneNumber = '".$phone."' ORDER BY id DESC LIMIT 1";
    $visitorResult = $conn->query($visitorSql);
    if ($visitorResult && $visitorResult->num_rows > 0) {
        $visitorData = $visitorResult->fetch_assoc();
    }
}

// Countries list
$countries = array(
    "Afghanistan", "Albania", "Algeria", "Argentina", "Australia", "Austria", "Bangladesh",
    "Belgium", "Brazil", "Canada", "China", "Colombia", "Denmark", "Egypt", "Finland",
    "France", "Germany", "Greece", "Hong Kong", "India", "Indonesia", "Iran", "Iraq",
    "Ireland", "Israel", "Italy", "Japan", "Kenya", "Malaysia", "Mexico", "Netherlands",
    "New Zealand", "Nigeria", "Norway", "Pakistan", "Philippines", "Poland", "Portugal",
    "Qatar", "Russia", "Saudi Arabia", "Singapore", "South Africa", "South Korea", "Spain",
    "Sri Lanka", "Sweden", "Switzerland", "Thailand", "Turkey", "United Arab Emirates",
    "United Kingdom", "United States of America", "Vietnam"
);

// Indian states
$indianStates = array(
    "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa",
    "Gujarat", "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala",
    "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya", "Mizoram", "Nagaland",
    "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura",
    "Uttar Pradesh", "Uttarakhand", "West Bengal", "Delhi", "Chandigarh", "Puducherry"
);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Registration Form</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #1a5276 0%, #2e86ab 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .form-container {
            padding: 30px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a5276;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #1a5276;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .form-group label .required {
            color: #e62700;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1a5276;
            box-shadow: 0 0 0 2px rgba(26, 82, 118, 0.1);
        }
        
        .form-group input.error,
        .form-group select.error {
            border-color: #e62700;
        }
        
        .photo-section {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        
        .photo-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .photo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #1a5276;
            color: white;
        }
        
        .btn-primary:hover {
            background: #154360;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            font-size: 16px;
            padding: 15px 40px;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .form-footer p {
            color: #666;
            font-size: 13px;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .form-container {
                padding: 20px;
            }
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Visitor Registration Form</h1>
            <p>Please fill in your details. Security will assign your meeting person.</p>
        </div>
        
        <div class="form-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="visitorForm" enctype="multipart/form-data">
                <input type="hidden" name="photo" id="photoBase64">
                
                <!-- Visit & Contact Information -->
                <div class="form-section">
                    <div class="section-title">Visit & Contact Information</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="visitDate">Visit Date <span class="required">*</span></label>
                            <input type="date" id="visitDate" name="visitDate" required 
                                   value="<?php echo isset($_POST['visitDate']) ? htmlspecialchars($_POST['visitDate']) : date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phoneNumber">Contact Number <span class="required">*</span></label>
                            <input type="tel" id="phoneNumber" name="phoneNumber" required 
                                   pattern="[0-9]{10,12}" minlength="10" maxlength="12"
                                   placeholder="10-12 digits"
                                   value="<?php echo isset($_POST['phoneNumber']) ? htmlspecialchars($_POST['phoneNumber']) : (isset($visitorData['phoneNumber']) ? htmlspecialchars($visitorData['phoneNumber']) : ''); ?>"
                                   oninput="this.value = this.value.replace(/\D/g, ''); checkVisitorData();">
                        </div>
                        
                        <div class="form-group">
                            <label for="visitorName">Full Name <span class="required">*</span></label>
                            <input type="text" id="visitorName" name="visitorName" required maxlength="100"
                                   value="<?php echo isset($_POST['visitorName']) ? htmlspecialchars($_POST['visitorName']) : (isset($visitorData['visitorName']) ? htmlspecialchars($visitorData['visitorName']) : ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($visitorData['email']) ? htmlspecialchars($visitorData['email']) : ''); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Visitor Details -->
                <div class="form-section">
                    <div class="section-title">Visitor Details</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category <span class="required">*</span></label>
                            <select id="category" name="category" required>
                                <option value="">Select category</option>
                                <option value="Client" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Client') || (isset($visitorData['category']) && $visitorData['category'] == 'Client') ? 'selected' : ''; ?>>Client</option>
                                <option value="Vendors" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Vendors') || (isset($visitorData['category']) && $visitorData['category'] == 'Vendors') ? 'selected' : ''; ?>>Vendors</option>
                                <option value="Visitors" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Visitors') || (isset($visitorData['category']) && $visitorData['category'] == 'Visitors') ? 'selected' : ''; ?>>Visitors</option>
                                <option value="Contractors" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Contractors') || (isset($visitorData['category']) && $visitorData['category'] == 'Contractors') ? 'selected' : ''; ?>>Contractors</option>
                                <option value="Auditor" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Auditor') || (isset($visitorData['category']) && $visitorData['category'] == 'Auditor') ? 'selected' : ''; ?>>Auditor</option>
                                <option value="FDA" <?php echo (isset($_POST['category']) && $_POST['category'] == 'FDA') || (isset($visitorData['category']) && $visitorData['category'] == 'FDA') ? 'selected' : ''; ?>>FDA</option>
                                <option value="Vendor Audit" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Vendor Audit') || (isset($visitorData['category']) && $visitorData['category'] == 'Vendor Audit') ? 'selected' : ''; ?>>Vendor Audit</option>
                                <option value="Client Visit" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Client Visit') || (isset($visitorData['category']) && $visitorData['category'] == 'Client Visit') ? 'selected' : ''; ?>>Client Visit</option>
                                <option value="Interview" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Interview') || (isset($visitorData['category']) && $visitorData['category'] == 'Interview') ? 'selected' : ''; ?>>Interview</option>
                                <option value="Contractor / Service Provider" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Contractor / Service Provider') || (isset($visitorData['category']) && $visitorData['category'] == 'Contractor / Service Provider') ? 'selected' : ''; ?>>Contractor / Service Provider</option>
                                <option value="Annual Maintenance" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Annual Maintenance') || (isset($visitorData['category']) && $visitorData['category'] == 'Annual Maintenance') ? 'selected' : ''; ?>>Annual Maintenance</option>
                                <option value="Equipment Repair" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Equipment Repair') || (isset($visitorData['category']) && $visitorData['category'] == 'Equipment Repair') ? 'selected' : ''; ?>>Equipment Repair</option>
                                <option value="Engineering Work" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Engineering Work') || (isset($visitorData['category']) && $visitorData['category'] == 'Engineering Work') ? 'selected' : ''; ?>>Engineering Work</option>
                                <option value="Civil Work" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Civil Work') || (isset($visitorData['category']) && $visitorData['category'] == 'Civil Work') ? 'selected' : ''; ?>>Civil Work</option>
                                <option value="Others" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Others') || (isset($visitorData['category']) && $visitorData['category'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="company">Company</label>
                            <input type="text" id="company" name="company" maxlength="150"
                                   value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : (isset($visitorData['company']) ? htmlspecialchars($visitorData['company']) : ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="purpose">Purpose of Visit</label>
                            <input type="text" id="purpose" name="purpose" maxlength="200" placeholder="Brief purpose"
                                   value="<?php echo isset($_POST['purpose']) ? htmlspecialchars($_POST['purpose']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Present Location -->
                <div class="form-section">
                    <div class="section-title">Present Location</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="country">Country <span class="required">*</span></label>
                            <select id="country" name="country" required onchange="toggleStateField()">
                                <option value="">Select country</option>
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c); ?>" 
                                        <?php echo (isset($_POST['country']) && $_POST['country'] == $c) || (isset($visitorData['country']) && $visitorData['country'] == $c) || (!isset($_POST['country']) && !isset($visitorData['country']) && $c == 'India') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="stateSelectGroup">
                            <label for="state">State <span class="required">*</span></label>
                            <select id="state" name="state" required>
                                <option value="">Select state</option>
                                <?php foreach ($indianStates as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s); ?>" 
                                        <?php echo (isset($_POST['state']) && $_POST['state'] == $s) || (isset($visitorData['state']) && $visitorData['state'] == $s) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group hidden" id="stateInputGroup">
                            <label for="stateOther">State <span class="required">*</span></label>
                            <input type="text" id="stateOther" name="state" maxlength="100" required
                                   value="<?php echo isset($_POST['state']) && isset($_POST['country']) && $_POST['country'] != 'India' ? htmlspecialchars($_POST['state']) : (isset($visitorData['state']) && isset($visitorData['country']) && $visitorData['country'] != 'India' ? htmlspecialchars($visitorData['state']) : ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="city">City <span class="required">*</span></label>
                            <input type="text" id="city" name="city" required maxlength="100"
                                   value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : (isset($visitorData['city']) ? htmlspecialchars($visitorData['city']) : ''); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Visitor Photo -->
                <div class="form-section">
                    <div class="section-title">Visitor Photo</div>
                    <div class="photo-section">
                        <div>
                            <input type="file" id="photoFile" accept="image/*" onchange="handlePhotoUpload(event)" style="display: none;">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('photoFile').click()">Upload Photo</button>
                            <button type="button" class="btn btn-secondary" onclick="capturePhoto()" style="margin-left: 10px;">Take Photo</button>
                            <button type="button" class="btn btn-secondary" onclick="clearPhoto()" id="clearPhotoBtn" style="margin-left: 10px; display: none;">Remove</button>
                        </div>
                        <div class="photo-preview" id="photoPreview">
                            <span style="color: #999;">No photo selected</span>
                        </div>
                    </div>
                    <video id="video" style="display: none; width: 100%; max-width: 400px; margin-top: 15px;"></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                </div>
                
                <div class="form-footer">
                    <button type="submit" class="btn btn-success">Submit Visitor Form</button>
                    <p><strong>Note:</strong> Security will assign your meeting person and notify you.</p>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Auto-fill form from visitor data
        <?php if ($visitorData): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Form is already pre-filled via PHP
        });
        <?php endif; ?>
        
        // Toggle state field based on country
        function toggleStateField() {
            const country = document.getElementById('country').value;
            const stateSelectGroup = document.getElementById('stateSelectGroup');
            const stateInputGroup = document.getElementById('stateInputGroup');
            const stateSelect = document.getElementById('state');
            const stateInput = document.getElementById('stateOther');
            
            if (country === 'India') {
                stateSelectGroup.classList.remove('hidden');
                stateInputGroup.classList.add('hidden');
                stateSelect.required = true;
                stateSelect.removeAttribute('disabled');
                stateSelect.removeAttribute('readonly');
                // Remove name from hidden input, add to visible select
                stateInput.removeAttribute('name');
                stateSelect.setAttribute('name', 'state');
                stateInput.required = false;
                stateInput.setAttribute('disabled', 'disabled');
                stateInput.value = '';
            } else if (country !== '') {
                stateSelectGroup.classList.add('hidden');
                stateInputGroup.classList.remove('hidden');
                stateSelect.required = false;
                stateSelect.setAttribute('disabled', 'disabled');
                // Remove name from hidden select, add to visible input
                stateSelect.removeAttribute('name');
                stateInput.setAttribute('name', 'state');
                stateInput.required = true;
                stateInput.removeAttribute('disabled');
                stateSelect.value = '';
            } else {
                stateSelectGroup.classList.remove('hidden');
                stateInputGroup.classList.add('hidden');
                stateSelect.required = false;
                stateInput.required = false;
            }
        }
        
        // Check visitor data by phone number
        function checkVisitorData() {
            const phone = document.getElementById('phoneNumber').value;
            if (phone.length >= 10) {
                window.location.href = '?plant_id=<?php echo $plant_id; ?>&phone=' + phone;
            }
        }
        
        // Handle photo upload
        function handlePhotoUpload(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoBase64').value = e.target.result;
                    document.getElementById('photoPreview').innerHTML = '<img src="' + e.target.result + '" alt="Visitor Photo">';
                    document.getElementById('clearPhotoBtn').style.display = 'inline-block';
                };
                reader.readAsDataURL(file);
            }
        }
        
        // Capture photo from webcam
        let stream = null;
        function capturePhoto() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const ctx = canvas.getContext('2d');
            
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(mediaStream) {
                    stream = mediaStream;
                    video.srcObject = stream;
                    video.style.display = 'block';
                    video.play();
                    
                    // Add capture button
                    const captureBtn = document.createElement('button');
                    captureBtn.type = 'button';
                    captureBtn.className = 'btn btn-success';
                    captureBtn.textContent = 'Capture';
                    captureBtn.style.marginTop = '10px';
                    captureBtn.onclick = function() {
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        ctx.drawImage(video, 0, 0);
                        const dataUrl = canvas.toDataURL('image/jpeg');
                        document.getElementById('photoBase64').value = dataUrl;
                        document.getElementById('photoPreview').innerHTML = '<img src="' + dataUrl + '" alt="Visitor Photo">';
                        document.getElementById('clearPhotoBtn').style.display = 'inline-block';
                        
                        // Stop webcam
                        stream.getTracks().forEach(track => track.stop());
                        video.style.display = 'none';
                        captureBtn.remove();
                    };
                    video.parentNode.appendChild(captureBtn);
                })
                .catch(function(err) {
                    alert('Error accessing webcam: ' + err.message);
                });
        }
        
        // Clear photo
        function clearPhoto() {
            document.getElementById('photoBase64').value = '';
            document.getElementById('photoPreview').innerHTML = '<span style="color: #999;">No photo selected</span>';
            document.getElementById('clearPhotoBtn').style.display = 'none';
            document.getElementById('photoFile').value = '';
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }
        
        // Form validation
        document.getElementById('visitorForm').addEventListener('submit', function(e) {
            // Check all required fields
            const visitDate = document.getElementById('visitDate').value;
            const phoneNumber = document.getElementById('phoneNumber').value;
            const visitorName = document.getElementById('visitorName').value;
            const category = document.getElementById('category').value;
            const country = document.getElementById('country').value;
            const city = document.getElementById('city').value;
            
            // State field depends on country - get value from the active field
            let state = '';
            const stateSelect = document.getElementById('state');
            const stateInput = document.getElementById('stateOther');
            if (country === 'India') {
                state = stateSelect && !stateSelect.disabled ? stateSelect.value : '';
            } else if (country !== '') {
                state = stateInput && !stateInput.disabled ? stateInput.value : '';
            }
            
            const missingFields = [];
            if (!visitDate) missingFields.push('Visit Date');
            if (!phoneNumber || phoneNumber.length < 10) missingFields.push('Contact Number (min 10 digits)');
            if (!visitorName) missingFields.push('Full Name');
            if (!category) missingFields.push('Category');
            if (!country) missingFields.push('Country');
            if (!state) missingFields.push('State');
            if (!city) missingFields.push('City');
            
            if (missingFields.length > 0) {
                e.preventDefault();
                alert('Please fill all required fields:\n' + missingFields.join('\n'));
                return false;
            }
        });
        
        // Initialize state field on page load
        toggleStateField();
    </script>
</body>
</html>
