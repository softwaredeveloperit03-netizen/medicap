<?php
if(!isset($_GET['user_no']) OR $_GET['user_no'] == 'null' OR $_GET['user_no'] == ''){
    $_GET['user_no'] = 'demo';
}
if($_GET['type'] == 'empdetail'){
    $sqlemp = "SELECT * FROM employee WHERE emp_id='".$row["entry_by"]."'";
    $resultemp = $conn->query($sqlemp);
    $rowemp = $resultemp->fetch_assoc();
    $_GET['emp_by'] = $row['entry_by'];
    if($row['entry_date'] != ''){
        $_GET['emp_entry'] = date('d/m/Y', strtotime($row['entry_date']));
    }
    else{
        $_GET['emp_entry'] = '';
    }
    $_GET['emp_name'] = $rowemp['emp_name'];
    $_GET['emp_department'] = $rowemp['department'];
    
    $sqlemp1 = "SELECT * FROM employee WHERE emp_id='".$row["check_by"]."'";
    $resultemp1 = $conn->query($sqlemp1);
    $rowemp1 = $resultemp1->fetch_assoc();
    $_GET['emp_by1'] = $row['check_by'];
    if($row['check_date'] != ''){
        $_GET['emp_entry1'] = date('d/m/Y', strtotime($row['check_date']));
    }
    else{
        $_GET['emp_entry1'] = '';
    }
    $_GET['emp_name1'] = $rowemp1['emp_name'];
    $_GET['emp_department1'] = $rowemp1['department'];
    
    $sqlemp2 = "SELECT * FROM employee WHERE emp_id='".$row["approve_by"]."'";
    $resultemp2 = $conn->query($sqlemp2);
    $rowemp2 = $resultemp2->fetch_assoc();
    $_GET['emp_by2'] = $row['approve_by'];
    if($row['approve_date'] != ''){
        $_GET['emp_entry2'] = date('d/m/Y', strtotime($row['approve_date']));
    }
    else{
        $_GET['emp_entry2'] = '';
    }
    $_GET['emp_name2'] = $rowemp2['emp_name'];
    $_GET['emp_department2'] = $rowemp2['department'];
}

else if($_GET['type'] == 'header'){
    $table= '
    <style>
        td { border:solid 1px BCBBBA;}
    </style>
    <table border="0" cellpadding="5">
        <tr>
            <td style="width:30%;"><br><br><br></td>
            <td style="width:40%; text-align:center;">';
            $this->Image('@'.file_get_contents('../upload/user/'.$_GET['user_no'].'-logo.png'),74,18,60,13);
   
            $table.='</td>
            <td style="width:30%;"></td>
        </tr>
    </table>';
    $this->SetY(15);
    $this->writeHTML($table, true, false, false, false, '');
}
else if($_GET['type'] == 'headerlandscape'){
    $table= '
    <style>
        td { border:solid 1px BCBBBA;}
    </style>
    <table border="0" cellpadding="5">
        <tr>
            <td style="width:30%;"><br><br><br></td>
            <td style="width:40%; text-align:center;">';
            $this->Image('@'.file_get_contents('../../assets/logo.png'),114,18,60,13);
   
            $table.='</td>
            <td style="width:30%;"></td>
        </tr>
    </table>';
    $this->SetY(15);
    $this->writeHTML($table, true, false, false, false, '');
}
else if($_GET['type'] == 'footerdigital'){
    $table='
        <style>
            td { border:solid 1px BCBBBA;}
        </style>
        <table cellpadding="5">
            <tr style="text-align:center;background-color:#DDDAD9;">
                <td style="width:33.33%">Prepared by</td>
                <td style="width:33.33%">Checked By</td>
                <td style="width:33.33%">Approved By</td>
            </tr>
            <tr>
                <td style="width:10.33%">Dept.</td>
                <td style="width:23%">'.$_GET['emp_department'].'</td>
                <td style="width:10.33%">Dept.</td>
                <td style="width:23%">'.$_GET['emp_department1'].'</td>
                <td style="width:10.33%">Dept.</td>
                <td style="width:23%">'.$_GET['emp_department2'].'</td>
            </tr>
            <tr>
                <td style="width:10.33%">Name.</td>
                <td style="width:23%">'.$_GET['emp_name'].'</td>
                <td style="width:10.33%">Name.</td>
                <td style="width:23%">'.$_GET['emp_name1'].'</td>
                <td style="width:10.33%">Name.</td>
                <td style="width:23%">'.$_GET['emp_name2'].'</td>
            </tr>
            <tr>
                <td style="width:10.33%">Sign.</td>
                <td style="width:23%">';
                    if($_GET['emp_name'] != ''){
                        $this->Image('@'.file_get_contents('1.png'),35,273,4,4);
                        $table.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_GET['emp_by'].'';
                    }
                    else{
                        $this->Image('@'.file_get_contents('2.png'),35,273,4,4);
                    }
                $table.='</td>
                <td style="width:10.33%">Sign.</td>
                <td style="width:23%">';
                    if($_GET['emp_name1'] != ''){
                        $this->Image('@'.file_get_contents('1.png'),95,273,4,4);
                        $table.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_GET['emp_by1'].'';
                    }else{
                        $this->Image('@'.file_get_contents('2.png'),95,273,4,4);
                    }
                $table.='</td>
                <td style="width:10.33%">Sign.</td>
                <td style="width:23%">';
                    if($_GET['emp_name2'] != ''){
                        $this->Image('@'.file_get_contents('1.png'),155,273,4,4);
                        $table.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_GET['emp_by2'].'';
                    }else{
                        $this->Image('@'.file_get_contents('2.png'),155,273,4,4);
                    }
                $table.='</td>
            </tr>
            <tr>
                <td style="width:10.33%">Date.</td>
                <td style="width:23%">'.$_GET['emp_entry'].'</td>
                <td style="width:10.33%">Date.</td>
                <td style="width:23%">'.$_GET['emp_entry1'].'</td>
                <td style="width:10.33%">Date.</td>
                <td style="width:23%">'.$_GET['emp_entry2'].'</td>
            </tr>
        </table>';
        $this->SetY(-50);
        $this->SetFont('Times', '', 10);
        $this->writeHTML($table, true, false, false, false, '');    
}
else if($_GET['type'] == 'footer'){
    $table='
        <style>
            td { border:solid 1px BCBBBA;}
        </style>
        <table cellpadding="5">
            <tr style="text-align:center;background-color:#DDDAD9;">
                <td style="width:33.33%">Prepared by</td>
                <td style="width:33.33%">Checked By</td>
                <td style="width:33.33%">Approved By</td>
            </tr>
            <tr>
                <td style="width:10.33%">Dept.</td>
                <td style="width:23%">'.$_GET['emp_department'].'</td>
                <td style="width:10.33%">Dept.</td>
                <td style="width:23%">'.$_GET['emp_department1'].'</td>
                <td style="width:10.33%">Dept.</td>
                <td style="width:23%">'.$_GET['emp_department2'].'</td>
            </tr>
            <tr>
                <td style="width:10.33%">Name.</td>
                <td style="width:23%">'.$_GET['emp_name'].'</td>
                <td style="width:10.33%">Name.</td>
                <td style="width:23%">'.$_GET['emp_name1'].'</td>
                <td style="width:10.33%">Name.</td>
                <td style="width:23%">'.$_GET['emp_name2'].'</td>
            </tr>
            <tr>
                <td style="width:10.33%">Sign.</td>
                <td style="width:23%">'.$_GET['emp_by'].'</td>
                <td style="width:10.33%">Sign.</td>
                <td style="width:23%">'.$_GET['emp_by1'].'</td>
                <td style="width:10.33%">Sign.</td>
                <td style="width:23%">'.$_GET['emp_by2'].'</td>
            </tr>
            <tr>
                <td style="width:10.33%">Date.</td>
                <td style="width:23%"></td>
                <td style="width:10.33%">Date.</td>
                <td style="width:23%"></td>
                <td style="width:10.33%">Date.</td>
                <td style="width:23%"></td>
            </tr>
        </table>';
        $this->SetY(-50);
        $this->SetFont('Times', '', 10);
        $this->writeHTML($table, true, false, false, false, '');    
}
?>