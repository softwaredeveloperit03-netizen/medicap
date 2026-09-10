<html>
<head>
</head>
<body>
<?php
require_once "Classes/PHPExcel.php";
        $tmpfname = "products.xlsx";
        $excelReader = PHPExcel_IOFactory::createReaderForFile($tmpfname);
        $excelObj = $excelReader->load($tmpfname);
        $worksheet = $excelObj->getSheet(1);//
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
        echo json_encode($data);
?>
</body>
</html>