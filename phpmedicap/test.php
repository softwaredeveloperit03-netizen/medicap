<?php

require 'db.php';

if ($_GET["type"] == "excelUpload") {
    require_once "PHPExcel/Classes/PHPExcel.php";
    $tmpfname = "Raw_Material_specification_2.xlsx";
    $excelReader = PHPExcel_IOFactory::createReaderForFile($tmpfname);
    $excelObj = $excelReader->load($tmpfname);
    $worksheet = $excelObj->getSheet(0);//
    $lastRow = $worksheet->getHighestRow();
    $lastCol = $worksheet->getHighestColumn();
    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($lastCol);
    
    $data = [];
    for ($row = 2; $row <= $lastRow; $row++) {
        $output = Array();
        for ($col = 'A'; $col <= $lastCol; $col++) {
            $output[$worksheet->getCell($col.'1')->getValue()] = $worksheet->getCell($col.$row)->getValue();
        }
        $data[] = $output;
    }
    
    for ($i = 0; $i < count($data); $i++) {
        $temp = $data[$i];
        
        $sql = "INSERT INTO material (material_code, material_name, material_type, material_subtype, status) VALUES ('".trim($temp["PRODUCT CODE"])."','".trim($temp["PRODUCT"])."','Raw Material','Raw Material','approve')";
        echo $sql."<br>";
        $conn->query($sql);
    }
    echo "success";
} else if ($_GET["type"] == "jsonUpload") {
    $data = '[{"material_code": "RM004","inward_no": "RMA-0281","inward_date": "13/06/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0281","batch_no": "092104002","mfg_date": "04/04/2021","exp_date": "03/04/2022","qty": "5900","ar_no": "RMA/21/0844"},{"material_code": "RM004","inward_no": "RMA-0285","inward_date": "17/06/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0285","batch_no": "092104001","mfg_date": "02/04/2021","exp_date": "01/04/2022","qty": "20000","ar_no": "RMA/21/0849"},{"material_code": "RM004","inward_no": "RMA-0285","inward_date": "17/06/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0285","batch_no": "092104003","mfg_date": "06/04/2021","exp_date": "05/04/2022","qty": "20000","ar_no": "RMA/21/0850"},{"material_code": "RM004","inward_no": "RMA-0346","inward_date": "04/07/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0346","batch_no": "092104004","mfg_date": "08/04/2021","exp_date": "07/04/2022","qty": "20000","ar_no": "RMA/21/0955"},{"material_code": "RM004","inward_no": "RMA-0346","inward_date": "04/07/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0346","batch_no": "092104006","mfg_date": "12/04/2021","exp_date": "11/04/2022","qty": "20000","ar_no": "RMA/21/0956"},{"material_code": "RM004","inward_no": "RMA-0353","inward_date": "07/07/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0353","batch_no": "092104012","mfg_date": "24.04.2021","exp_date": "23/04/2022","qty": "20000","ar_no": "RMA/21/0965"},{"material_code": "RM004","inward_no": "RMA-0353","inward_date": "07/07/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0353","batch_no": "092104013","mfg_date": "26/04/2021","exp_date": "25/04/2022","qty": "20000","ar_no": "RMA/21/0966"},{"material_code": "RM004","inward_no": "RMA-0354","inward_date": "07/07/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0354","batch_no": "092104007","mfg_date": "14/04/2021","exp_date": "13/04/2022","qty": "20000","ar_no": "RMA/21/0967"},{"material_code": "RM004","inward_no": "RMA-0354","inward_date": "07/07/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0354","batch_no": "092104010","mfg_date": "20/04/2021","exp_date": "19/04/2022","qty": "20000","ar_no": "RMA/21/0968"},{"material_code": "RM004","inward_no": "RMA-0354","inward_date": "07/07/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0354","batch_no": "092104011","mfg_date": "22/04/2021","exp_date": "21/04/2022","qty": "20000","ar_no": "RMA/21/0969"},{"material_code": "RM004","inward_no": "RMA-0441","inward_date": "26/07/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0441","batch_no": "092105001","mfg_date": "02/05/2021","exp_date": "01/05/2022","qty": "20000","ar_no": "RMA/21/1192"},{"material_code": "RM004","inward_no": "RMA-0441","inward_date": "26/07/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0441","batch_no": "092105002","mfg_date": "04/05/2021","exp_date": "03/05/2022","qty": "20000","ar_no": "RMA/21/1193"},{"material_code": "RM004","inward_no": "RMA-0441","inward_date": "26/07/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0441","batch_no": "092105003","mfg_date": "06/05/2021","exp_date": "05/05/2022","qty": "20000","ar_no": "RMA/21/1194"},{"material_code": "RM004","inward_no": "RME-0506","inward_date": "12/08/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0506","batch_no": "092105004","mfg_date": "08/05/2021","exp_date": "07/05/2022","qty": "20000"},{"material_code": "RM004","inward_no": "RME-0506","inward_date": "12/08/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0506","batch_no": "092105005","mfg_date": "10/05/2021","exp_date": "09/05/2022","qty": "20000"},{"material_code": "RM004","inward_no": "RMA/0537","inward_date": "19/08/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0537","batch_no": "092105006","mfg_date": "12/05/2021","exp_date": "11/05/2022","qty": "20000"},{"material_code": "RM004","inward_no": "RMA/0537","inward_date": "19/08/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0537","batch_no": "092105007","mfg_date": "14/05/2021","exp_date": "13/05/2022","qty": "20000"},{"material_code": "RM004","inward_no": "RMA/0537","inward_date": "19/08/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0537","batch_no": "092105008","mfg_date": "16/05/2021","exp_date": "15/05/2022","qty": "20000"},{"material_code": "RM004","inward_no": "RMA/0538","inward_date": "19/08/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0538","batch_no": "092105009","mfg_date": "18/05/2021","exp_date": "17/05/2022","qty": "20000"},{"material_code": "RM004","inward_no": "RMA/0538","inward_date": "19/08/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0538","batch_no": "092105014","mfg_date": "28/05/2021","exp_date": "27/05/2022","qty": "20000"},{"material_code": "RM004","inward_no": "RMA/0565","inward_date": "25/08/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0565","batch_no": "092106002","mfg_date": "04/06/2021","exp_date": "03/06/2022","qty": "20000"},{"material_code": "RM004","inward_no": "RMA/0565","inward_date": "25/08/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0565","batch_no": "092106003","mfg_date": "06/06/2021","exp_date": "05/06/2022","qty": "20000"},{"material_code": "RM004","inward_no": "RMA/0565","inward_date": "25/08/2021","vendor_no": "V10060","manufacturer_no": "V10089","grn_no": "GRN/RMA/21-22/0565","batch_no": "092106010","mfg_date": "20/06/2021","exp_date": "19/06/2022","qty": "20000"}]';
    $data = json_decode($data, true);
    for ($i = 0; $i < count($data); $i++) {
        $temp = $data[$i];
        
        $inward_date = $temp["inward_date"];
        $inward_date = explode("/", $inward_date);
        $inward_date = $inward_date[2]."-".$inward_date[1]."-".$inward_date[0];
        
        $mfg_date = $temp["mfg_date"];
        $mfg_date = explode("/", $mfg_date);
        $mfg_date = $mfg_date[2]."-".$mfg_date[1]."-".$mfg_date[0];
        $temp["mfg_date"] = $mfg_date;
        
        $exp_date = $temp["exp_date"];
        $exp_date = explode("/", $exp_date);
        $exp_date = $exp_date[2]."-".$exp_date[1]."-".$exp_date[0];
        $temp["exp_date"] = $exp_date;
        
        // $sql = "INSERT INTO stock_book (challan_for, inword_no, inword_date, grn_no, ar_no, stock_type, vendor_no, manufacturer_no, material_code, batch_no, qty, unit, mfg_date, exp_date, grn_date, release_date) VALUES ('EOU', '".trim($temp["inward_no"])."','$inward_date', '".trim($temp["grn_no"])."', '".trim($temp["ar_no"])."','Opening', '".trim($temp["vendor_no"])."', '".trim($temp["manufacturer_no"])."', '".trim($temp["material_code"])."', '".trim($temp["batch_no"])."', '".trim($temp["received_qty"])."', 'Kg', '".trim($temp["mfg_date"])."', '".trim($temp["exp_date"])."', '$inward_date', '$inward_date')";
        $sql = "UPDATE stock_book SET qty='".trim($temp["qty"])."' WHERE material_code='".trim($temp["material_code"])."' AND batch_no='".trim($temp["batch_no"])."' AND ar_no='".trim($temp["ar_no"])."'";
        echo $sql."<br>";
        $conn->query($sql);
    }
} else if ($_GET["type"] == "employeeList") {
    $data = json_decode($data, true);
    for ($i = 0; $i < count($data); $i++) {
        $temp = $data[$i];
        
        $joinind_date = $temp["JOINING DATE"];
        $joinind_date = explode("/", $joinind_date);
        $joinind_date = $joinind_date[2]."-".$joinind_date[1]."-".$joinind_date[0];
        
        $birthdate = $temp["BIRTHDATE"];
        $birthdate = explode("/", $birthdate);
        $birthdate = $birthdate[2]."-".$birthdate[1]."-".$birthdate[0];
        
        $sql = "INSERT INTO employee (emp_id, employee_type, firstname, middlename, department, unit, designation, qualification, birthdate, contact_no, email, joining_date, gender) VALUES ('".trim($temp["EMP ID"])."', '".trim($temp["CATEGORY"])."', '".trim($temp["FIRST NAME"])."', '".trim($temp["MIDDLE NAME"])."', '".trim($temp["Department"])."','".trim($temp["DEPARTMENT Code"])."', '".trim($temp["DESIGNATION"])."', '".trim($temp["QUALIFICATION"])."', '$birthdate', '".trim($temp["CONTACT NO"])."', '".trim($temp["EMAIL"])."','$joinind_date', '".trim($temp["GENDER"])."')";
       
        $conn->query($sql);
    }
} else if ($_GET["type"] == "uploadFile") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        $joinind_date = $temp["JOINING DATE"];
        $joinind_date = explode("/", $joinind_date);
        $joinind_date = $joinind_date[2]."-".$joinind_date[1]."-".$joinind_date[0];
        
        $birthdate = $temp["BIRTHDATE"];
        $birthdate = explode("/", $birthdate);
        $birthdate = $birthdate[2]."-".$birthdate[1]."-".$birthdate[0];
         
        // $sql = "UPDATE employee SET employee_type='".trim($temp["CATEGORY"])."' WHERE firstname='".$temp["FIRST NAME"]."' AND middlename='".trim($temp["MIDDLE NAME"])."', birthdate='$birthdate', contact_no='".trim($temp["CONTACT NO"])."', email='".trim($temp["EMAIL"])."', joining_date='$joinind_date', gender='".trim($temp["GENDER"])."', designation='".trim($temp["DESIGNATION"])."', qualification='".trim($temp["QUALIFICATION"])."' WHERE emp_id='".trim($temp["EMP ID"])."'";
        $conn->query($sql);
    }
} else if ($_GET["type"] == "CommonDetails") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        //  $sql = "INSERT INTO product (product_type,product_code,product_name,grade,product_apperance,manufactured_under,storage_condition) VALUES ('".trim($temp["Type of Product"])."','".trim($temp["Product Code"])."', '".trim($temp["Product Name"])."', '".trim($temp["Grade"])."','".trim($temp["Product Appearance"])."','".trim($temp["Manufactured Under"])."','".trim($temp["Storage Condition"])."')";
         $sql = "INSERT INTO material (material_type,material_subtype,category, material_name, material_code,unit,grade,inventory,order_qty) VALUES ('Packing Material','".trim($temp["Sub type/Nameof material"])."', '".trim($temp["Category Printed/Plain"])."', '".trim($temp["Material Name"])."', '".trim($temp["Code"])."','".trim($temp["Unit"])."','".trim($temp["Grade"])."','".trim($temp["Min. Inventory Level"])."','".trim($temp["Min. Order Qty"])."')";
        //   $sql = "INSERT INTO material (material_type,material_subtype, material_nature, material_name, material_code,order_qty, inventory,unit,location) VALUES ('Raw Material','".trim($temp["Type"])."', '".trim($temp["Nature of Material"])."', '".trim($temp["Material Name"])."', '".trim($temp["Material Code"])."','".trim($temp["Min. Order Qty"])."', '".trim($temp["Min. Inventory"])."','".trim($temp["Unit"])."','".trim($temp["Storage Location"])."')";
        //  $sql = "INSERT INTO employee (emp_id, employee_type, firstname, middlename,lastname, department, designation, gender,joining_status,qualification,contact_no) VALUES ('".trim($temp["Employee Code"])."', '".trim($temp["Employee Type"])."', '".trim($temp["First Name"])."', '".trim($temp["Middle Name"])."','".trim($temp["Last Name"])."', '".trim($temp["Department"])."', '".trim($temp["Designation"])."','".trim($temp["Gender"])."','".trim($temp["Joining Status"])."','".trim($temp["Qualification"])."','".trim($temp["Contact No"])."')";
        // $sql = "INSERT INTO specification (specification_no, spec_type, supersede_no, material_code, issue_date, effective_date, review_date, molecular_formula, molecular_weight, sample_drawn_from, sample_drawn_with, reserve_sample, storage_condition, pack_size, retest_period, expiry_period, reference, lotwise_analysis, precautions, plan_type, upto_10, morethan_10, upto_25, 26_50, 51_100, above_100) VALUES ('".trim($temp["specification_no"])."', 'Packing Material Specification', '".trim($temp["supersede_no"])."', '".trim($temp["material_code"])."', '".trim($temp["issue_date"])."', '".trim($temp["effective_date"])."', '".trim($temp["review_date"])."', '".trim($temp["molecular_formula"])."', '".trim($temp["molecular_wt"])."', '".trim($temp["sample_drawn_from"])."', '".trim($temp["sample_drawn_with"])."', '".trim($temp["reserve_sample"])."', '".trim($temp["storage_condition"])."', '".trim($temp["pack_size"])."', '".trim($temp["retest_period"])."', '".trim($temp["expiry_period"])."', '".trim($temp["reference"])."', '".trim($temp["lotwise_analysis"])."', '".trim($temp["precautions"])."', '".trim($temp["plan_type"])."', '".trim($temp["Upto 10"])."', '".trim($temp["Morethan 10"])."', '".trim($temp["Upto 25"])."', '".trim($temp["26-50"])."', '".trim($temp["51-100"])."', '".trim($temp["Above 100"])."')";
        echo $sql."<br>";
        $conn->query($sql);
    }
}  else if ($_GET["type"] == "uploadRMSpec") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        $sql = "INSERT INTO specification (sampling_plan,sample_size,safety_precaution,specification_no, spec_type,supersede_no, material_code,effective_date, review_date,molecular_formula,molecular_weight,sample_drawn_from, sample_drawn_with, reserve_sample, storage_condition, pack_size, retest_period, expiry_period, reference, lotwise_analysis, precautions, plan_type,issue_date) VALUES ('".trim($temp["sampling_plan"])."','".trim($temp["sample_size"])."','".trim($temp["safety_precaution"])."','".trim($temp["specification_no"])."', 'Raw Material Specification', '".trim($temp["supersede_no"])."', '".trim($temp["material_code"])."',  '".trim($temp["effective_date"])."', '".trim($temp["review_date"])."', '".trim($temp["molecular_formula"])."', '".trim($temp["molecular_weight"])."', '".trim($temp["sample_drawn_from"])."', '".trim($temp["sample_drawn_with"])."', '".trim($temp["reserve_sample"])."', '".trim($temp["storage_condition"])."', '".trim($temp["pack_size"])."', '".trim($temp["retest_period"])."', '".trim($temp["expiry_period"])."', '".trim($temp["reference"])."', '".trim($temp["lotwise_analysis"])."', '".trim($temp["precautions"])."', '".trim($temp["plan_type"])."','".trim($temp["issue_date"])."')";
        echo $sql."<br>";
        $conn->query($sql);
    }
} else if ($_GET["type"] == "uploadRMSpecTest") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        $test = "";
        if ($temp["Test"] !== '') {
            $test = $temp["Test"];
        }
        $sql = "INSERT INTO spec_tests (specification_no, test_type, test, subtest, limits, limit_type, lower_limit, upper_limit, unit, reference_type) VALUES ('".trim($temp["specification_no"])."', 'Chemical', '".trim($temp["test"])."', '".trim($temp["subtest"])."', '".trim($temp["standard_limit"])."', '".trim($temp["limit_type"])."', '".trim($temp["Lower limit"])."', '".trim($temp["Upper Limit"])."', '".trim($temp["Unit"])."', '".trim($temp["Reference"])."')";
        echo $sql."<br>";
        $conn->query($sql);
    }
}else if ($_GET["type"] == "uploadFMSpec") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        $sql = "INSERT INTO specification (sampling_plan,sample_size,safety_precaution,specification_no, spec_type,supersede_no,product_code,molecular_formula,molecular_weight,sample_drawn_from, sample_drawn_with, reserve_sample, storage_condition, pack_size, retest_period, expiry_period, reference, lotwise_analysis) VALUES ('".trim($temp["Sampling Plan"])."','".trim($temp["Sample Size"])."','".trim($temp["hazardous and Precautions"])."','".trim($temp["Specification No"])."', 'Finish Product', '".trim($temp["Superseded"])."','".trim($temp["product_code"])."','".trim($temp["Molecular Formula"])."', '".trim($temp["Molecular Weight"])."', '".trim($temp["Sample to be drawn from"])."', '".trim($temp["Sample to be drawn with"])."', '".trim($temp["Reserve Sample"])."', '".trim($temp["Storage Condition"])."', '".trim($temp["Standard Pack Size"])."', '".trim($temp["Retest Period"])."', '".trim($temp["Expiry Period"])."', '".trim($temp["Reference"])."', '".trim($temp["Lot Wise analysis Plan"])."')";
        echo $sql."<br>";
        $conn->query($sql);
    }
}else if ($_GET["type"] == "uploadFMSpecTest") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        $test = "";
        if ($temp["Test"] !== '') {
            $test = $temp["Test"];
        }
        $sql = "INSERT INTO spec_tests (specification_no, test_type, test, subtest, limits, limit_type, lower_limit, upper_limit, unit, reference_type) VALUES ('".trim($temp["Specification no"])."', 'Chemical', '".trim($temp["Test"])."', '".trim($temp["Subtest"])."', '".trim($temp["Standard Limit"])."', '".trim($temp["Limit Type"])."', '".trim($temp["Lower limit"])."', '".trim($temp["Upper Limit"])."', '".trim($temp["Unit"])."', '".trim($temp["Reference"])."')";
        echo $sql."<br>";
        $conn->query($sql);
    }
}else if ($_GET["type"] == "uploadEquipment") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        $sql = "INSERT INTO equipment (equipment_type,equipment_code, equipment_name, make, capacity, unit, department,model, from_range,to_range,calibration_required,lastcalibration_date,calibration_frequency,section) VALUES ('".trim($temp["Equipment Type"])."', '".trim($temp["Equipment Code"])."','".trim($temp["Equipment Name"])."', '".trim($temp["Make"])."', '".trim($temp["Max Capacity"])."', '".trim($temp["Capacity Unit"])."', '".trim($temp["Dept. of installation"])."', '".trim($temp["Model No"])."', '".trim($temp["Working Range From"])."','".trim($temp["Working Range To"])."','".trim($temp["Calibration Required"])."','".trim($temp["Last Calibration date"])."','".trim($temp["Calibration Frequency"])."','".trim($temp["Section"])."')";
        echo $sql."<br>";
        $conn->query($sql);
    }
} else if ($_GET["type"] == "uploadPMSpecTest") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        $test = "";
        if ($temp["Test"] !== '') {
            $test = $temp["Test"];
        }
        $sql = "INSERT INTO spec_tests (specification_no, test_type, test, subtest, limits, limit_type, lower_limit, upper_limit, unit, reference_type) VALUES ('".trim($temp["Specification no"])."', 'Chemical', '$test', '".trim($temp["Subtest"])."', '".trim($temp["Standard Limit"])."', '".trim($temp["Limit Type"])."', '".trim($temp["Lower limit"])."', '".trim($temp["Upper Limit"])."', '".trim($temp["Unit"])."', '".trim($temp["Reference"])."')";
        echo $sql."<br>";
        $conn->query($sql);
    }
}/* else if ($_GET["type"] == "uploadStock") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        $inward_date = explode("/", $temp["inward_date"]);
        $inward_date = "20".$inward_date[2]."-".$inward_date[1]."-".$inward_date[0];
        
        $mfg_date = explode("/", $temp["mfg_date"]);
        $mfg_date = "20".$mfg_date[2]."-".$mfg_date[1]."-".$mfg_date[0];
        
        $exp_date = explode("/", $temp["exp_date"]);
        $exp_date = "20".$exp_date[2]."-".$exp_date[1]."-".$exp_date[0];
        
        $status = "Approved";
        if ($temp["ar_no"] == "") {
            $status="Under Test";
        }
        $sql = "INSERT INTO stock_book (material_code, inword_no, inword_date, grn_date, vendor_no, grn_no, batch_no, mfg_date, exp_date, qty, ar_no, status) VALUES ('".trim($temp["material_code"])."', '".trim($temp["inward_no"])."', '$inward_date', '$inward_date', '".trim($temp["vendor_no"])."', '".trim($temp["grn_no"])."', '".trim($temp["batch_no"])."', '$mfg_date', '$exp_date', '".trim($temp["qty"])."', '".trim($temp["ar_no"])."', '$status')";
        echo $sql."<br>";
        $conn->query($sql);
    }
}*/ else if ($_GET["type"] == "updateMedia") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        $sql = "INSERT INTO media (media_code, media_name, ph, sterilization) VALUES ('".trim($temp["media_code"])."', '".trim($temp["media_name"])."', '".trim($temp["ph"])."', '".trim($temp["sterilization"])."')";
        echo $sql."<br>";
        $conn->query($sql);
    }
} else if ($_GET["type"] == "uploadStock") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        $inward_date = explode("-", $temp["Inward Date"]);
        $inward_date = $inward_date[2]."-".$inward_date[1]."-".$inward_date[0];
        
        $sql = "INSERT INTO stock_book (challan_for, inword_no, inword_date, grn_no, ar_no, stock_type, vendor_no, manufacturer_no, material_code, batch_no, qty, unit, mfg_date, exp_date, status) VALUES ('".trim($temp["Material For"])."', '".trim($temp["Inward No"])."', '$inward_date', '".trim($temp["GRN No"])."', '".trim($temp["AR No"])."', 'Opening', '".trim($temp["Vendor No"])."', '".trim($temp["Manufacturer No"])."', '".trim($temp["Material Code"])."', '".trim($temp["Batch No"])."', '".trim($temp["Qty"])."', '".trim($temp["Unit"])."', '".trim($temp["Mfg Date"])."', '".trim($temp["Exp Date"])."', '".trim($temp["Status"])."')";
        echo $sql."<br>";
        $conn->query($sql);
    }
} else if ($_GET["type"] == "uploadProduct") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        $inward_date = explode("-", $temp["Inward Date"]);
        $inward_date = $inward_date[2]."-".$inward_date[1]."-".$inward_date[0];
        
        $sql = "INSERT INTO product (product_code, PARTACODE, EOUCODE, product_name, grade, purpose) VALUES ('".trim($temp["product_code"])."', '".trim($temp["PARTACODE"])."', '".trim($temp["EOUCODE"])."', '".trim($temp["product_name"])."', '".trim($temp["grade"])."', '".trim($temp["purpose"])."')";
        echo $sql."<br>";
        $conn->query($sql);
    }
} else if ($_GET["type"] == "uploadControlsample") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    
    $entry_date = '';
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        if (isset($temp["Date"]) && $temp["Date"] !== 'NA' && $temp["Date"] !== '') {
            $entry_date = explode("/", trim($temp["Date"]));
            $month = $entry_date[0] + '';
            if (strlen($month) == 1) {
                $month = "0".$month;
            }
            
            $date = $entry_date[1] + '';
            if (strlen($date) == 1) {
                $date = "0".$date;
            }
            $entry_date = "20".$entry_date[2]."-".$month."-".$date;
        }
        
        $mfg_date = explode("-", trim($temp["mfg_date"]));
        if ($mfg_date[0] == "Jan") {
            $mfg_date[0] = "01";
        } else if ($mfg_date[0] == "Feb") {
            $mfg_date[0] = "02";
        } else if ($mfg_date[0] == "Mar") {
            $mfg_date[0] = "03";
        } else if ($mfg_date[0] == "Apr") {
            $mfg_date[0] = "04";
        } else if ($mfg_date[0] == "May") {
            $mfg_date[0] = "05";
        } else if ($mfg_date[0] == "Jun") {
            $mfg_date[0] = "06";
        } else if ($mfg_date[0] == "Jul") {
            $mfg_date[0] = "07";
        } else if ($mfg_date[0] == "Aug") {
            $mfg_date[0] = "08";
        } else if ($mfg_date[0] == "Sep") {
            $mfg_date[0] = "09";
        } else if ($mfg_date[0] == "Oct") {
            $mfg_date[0] = "10";
        } else if ($mfg_date[0] == "Nov") {
            $mfg_date[0] = "11";
        } else if ($mfg_date[0] == "Dec") {
            $mfg_date[0] = "12";
        }
        $mfg_date = "20".$mfg_date[1]."-".$mfg_date[0];
        
        $exp_date = explode("-", trim($temp["exp_date"]));
        if ($exp_date[0] == "Jan") {
            $exp_date[0] = "01";
        } else if ($exp_date[0] == "Feb") {
            $exp_date[0] = "02";
        } else if ($exp_date[0] == "Mar") {
            $exp_date[0] = "03";
        } else if ($exp_date[0] == "Apr") {
            $exp_date[0] = "04";
        } else if ($exp_date[0] == "May") {
            $exp_date[0] = "05";
        } else if ($exp_date[0] == "Jun") {
            $exp_date[0] = "06";
        } else if ($exp_date[0] == "Jul") {
            $exp_date[0] = "07";
        } else if ($exp_date[0] == "Aug") {
            $exp_date[0] = "08";
        } else if ($exp_date[0] == "Sep") {
            $exp_date[0] = "09";
        } else if ($exp_date[0] == "Oct") {
            $exp_date[0] = "10";
        } else if ($exp_date[0] == "Nov") {
            $exp_date[0] = "11";
        } else if ($exp_date[0] == "Dec") {
            $exp_date[0] = "12";
        }
        $exp_date = "20".$exp_date[1]."-".$exp_date[0];
        
        $sql = "INSERT INTO control_sample (material_type, material_name, batch_no, mfg_date, exp_date, done_by, micro_qty, yearly_qty, logbook_no, remark, entry_date) VALUES ('Finish Product','".trim($temp["product_name"])."', '".trim($temp["batch_no"])."', '$mfg_date', '$exp_date', '".trim($temp["sample_by"])."', '".trim($temp["micro_sample"])."', '".trim($temp["yearly_qty"])."', '".trim($temp["logbook_no"])."', '".trim($temp["remark"])."', '$entry_date')";
        echo $sql."<br>";
        $conn->query($sql);
    }
}/* else if ($_GET["type"] == "uploadStability") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        $withdrawal_date = explode("/", $temp["withdrawal_date"]);
        $month = $withdrawal_date[0] + '';
        if (strlen($month) == 1) {
            $month = "0".$month;
        }
        
        $date = $withdrawal_date[1] + '';
        if (strlen($date) == 1) {
            $date = "0".$date;
        }
        $withdrawal_date = "20".$withdrawal_date[2]."-".$month."-".$date;
        
        $sql = "SELECT id FROM stability_charge WHERE product_name='".trim($temp["product_name"])."' AND batch_no='".trim($temp["batch_no"])."' AND stability_condition='".trim($temp["stability_condition"])."' AND inverval='".trim($temp["inverval"])."'";
        $result = $conn->query($sql);
        if ($result->num_rows == 0) {
            $sql = "INSERT INTO stability_charge (product_code, product_name, batch_no, stability_condition, inverval, withdrawal_date) VALUES ('".trim($temp["product_code"])."', '".trim($temp["product_name"])."', '".trim($temp["batch_no"])."', '".trim($temp["stability_condition"])."', '".trim($temp["inverval"])."', '$withdrawal_date')";
            echo $sql."<br>";
            $conn->query($sql);
        } else {
            $sql = "UPDATE stability_charge SET withdrawal_date='$withdrawal_date' WHERE product_name='".trim($temp["product_name"])."' AND batch_no='".trim($temp["batch_no"])."' AND stability_condition='".trim($temp["stability_condition"])."' AND inverval='".trim($temp["inverval"])."'";
            echo $sql."<br>";
            $conn->query($sql);
        }
    }
}*/ else if ($_GET["type"] == "uploadStability") {
    $str = file_get_contents('test.json');
    $json = json_decode($str, true);
    for ($i = 0; $i < count($json); $i++) {
        $temp = $json[$i];
        
        $withdrawal_date = "";
        if ($temp["withdrawal_date"] !== 'NA') {
            $withdrawal_date = explode("/", $temp["withdrawal_date"]);
            $month = $withdrawal_date[0] + '';
            if (strlen($month) == 1) {
                $month = "0".$month;
            }
            
            $date = $withdrawal_date[1] + '';
            if (strlen($date) == 1) {
                $date = "0".$date;
            }
            $withdrawal_date = "20".$withdrawal_date[2]."-".$month."-".$date;
        }
        
        $sql = "SELECT id FROM stability_charge WHERE product_name='Theobromine BP' AND batch_no='".trim($temp["batch_no"])."' AND stability_condition='".trim($temp["stability_condition"])."' AND inverval='".trim($temp["inverval"])."' AND withdrawal_date='$withdrawal_date'";
        $result = $conn->query($sql);
        if ($result->num_rows == 0) {
            $sql = "INSERT INTO stability_charge (product_code, product_name, batch_no, stability_condition, inverval, withdrawal_date) VALUES ('".trim($temp["product_code"])."', '".trim($temp["product_name"])."', '".trim($temp["batch_no"])."', '".trim($temp["stability_condition"])."', '".trim($temp["inverval"])."', '$withdrawal_date')";
            echo $sql."<br>";
            $conn->query($sql);
        } else {
            $sql = "UPDATE stability_charge SET withdrawal_date='$withdrawal_date' WHERE product_name='".trim($temp["product_name"])."' AND batch_no='".trim($temp["batch_no"])."' AND stability_condition='".trim($temp["stability_condition"])."' AND inverval='".trim($temp["inverval"])."'";
            echo $sql."<br>";
            $conn->query($sql);
        }
        
        /*$sql = "INSERT INTO stability_charge (product_code, product_name, batch_no, stability_condition, inverval, withdrawal_date) VALUES ('".trim($temp["product_code"])."', '".trim($temp["product_name"])."', '".trim($temp["batch_no"])."', '".trim($temp["stability_condition"])."', '".trim($temp["inverval"])."', '$withdrawal_date')";
        echo $sql."<br>";
        $conn->query($sql);*/
    }
}
?>