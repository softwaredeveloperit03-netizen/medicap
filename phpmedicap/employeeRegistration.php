<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save'])) {
    $data = $_POST;
    $line = implode(", ", $data) . "\n";
    file_put_contents("employees.txt", $line, FILE_APPEND | LOCK_EX);
    echo "<p style='color: green; text-align:center;'>Employee registered successfully!</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            padding: 20px;
        }

        .form-container {
            background-color: #fff;
            max-width: 1200px;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        form {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 13px;
            margin-bottom: 4px;
        }

        input, select {
            padding: 6px;
            font-size: 13px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .submit-btn {
            grid-column: span 5;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            font-size: 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }

        /* Responsive for tablets */
        @media (max-width: 1024px) {
            form {
                grid-template-columns: repeat(3, 1fr);
            }

            .submit-btn {
                grid-column: span 3;
            }
        }

        /* Responsive for small screens */
        @media (max-width: 768px) {
            form {
                grid-template-columns: repeat(2, 1fr);
            }

            .submit-btn {
                grid-column: span 2;
            }
        }

        /* Responsive for mobile */
        @media (max-width: 480px) {
            form {
                grid-template-columns: 1fr;
            }

            .submit-btn {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Employee Registration</h2>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>First Name *</label>
            <input type="text" name="firstname" required>
        </div>
        <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="middlename">
        </div>
        <div class="form-group">
            <label>Last Name *</label>
            <input type="text" name="lastname" required>
        </div>
        <div class="form-group">
            <label>Contact No</label>
            <input type="text" name="contact_no" pattern="[0-9]{10}" maxlength="10">
        </div>
        <div class="form-group">
            <label>Email ID</label>
            <input type="email" name="emp_email">
        </div>
        <div class="form-group">
            <label>Department *</label>
            <select name="department" required>
                <option value="">Select</option>
                <option>HR</option>
                <option>Finance</option>
                <option>IT</option>
            </select>
        </div>
        <div class="form-group">
            <label>Designation *</label>
            <select name="designation" required>
                <option value="">Select</option>
                <option>Manager</option>
                <option>Assistant</option>
                <option>Intern</option>
            </select>
        </div>
        <div class="form-group">
            <label>Employee Category *</label>
            <select name="operator_category" required>
                <option>Worker / Operator</option>
                <option>Staff</option>
            </select>
        </div>
        <div class="form-group">
            <label>Joining Status</label>
            <select name="joining_status">
                <option>trainee</option>
                <option>probation</option>
                <option>confirmed</option>
            </select>
        </div>
        <div class="form-group">
            <label>Gender *</label>
            <select name="gender" required>
                <option>Male</option>
                <option>Female</option>
            </select>
        </div>
        <div class="form-group">
            <label>Date of Birth *</label>
            <input type="date" name="birthdate" required>
        </div>
        <div class="form-group">
            <label>Joining Date</label>
            <input type="date" name="joining_date">
        </div>
        <div class="form-group">
            <label>Aadhar No</label>
            <input type="number" name="adhar_no">
        </div>
        <div class="form-group">
            <label>Pan No</label>
            <input type="text" name="pan">
        </div>
        <div class="form-group">
            <label>Qualification</label>
            <select name="qualification">
                <option></option>
                <option>Graduate</option>
                <option>Post Graduate</option>
            </select>
        </div>
        <div class="form-group">
            <label>Experience (Yrs)</label>
            <input type="number" name="experience">
        </div>
        <div class="form-group">
            <label>Nationality</label>
            <input type="text" name="nationality">
        </div>
        <div class="form-group">
            <label>Marital Status</label>
            <select name="marital_status">
                <option></option>
                <option>Married</option>
                <option>UnMarried</option>
            </select>
        </div>
        <div class="form-group">
            <label>Blood Group</label>
            <input type="text" name="blood_group">
        </div>
        <div class="form-group">
            <label>Anniversary Date</label>
            <input type="date" name="anni_date">
        </div>
        <div class="form-group">
            <label>Induction Training</label>
            <select name="induction">
                <option>Yes</option>
                <option>No</option>
            </select>
        </div>
        <div class="form-group">
            <label>ESIC Applicable</label>
            <select name="esic_app">
                <option></option>
                <option>Yes</option>
                <option>No</option>
            </select>
        </div>
        <div class="form-group">
            <label>ESIC No</label>
            <input type="text" name="esic_no">
        </div>
        <div class="form-group">
            <label>EPF Applicable</label>
            <select name="epf_app">
                <option></option>
                <option>Yes</option>
                <option>No</option>
            </select>
        </div>
        <div class="form-group">
            <label>UAN No</label>
            <input type="text" name="pf_no">
        </div>
        <div class="form-group">
            <label>Upload Photo</label>
            <input type="file" name="photo">
        </div>
        <div class="form-group">
            <label>Upload Resume</label>
            <input type="file" name="resume">
        </div>

        <button type="submit" class="submit-btn" name="save">Save</button>
    </form>
</div>

</body>
</html>
