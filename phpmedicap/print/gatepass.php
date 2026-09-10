<style>
body {
    background-color: #d7d6d3;
    font-family:'verdana';
}
.id-card-holder {
    width: 225px;
    padding: 4px;
    margin: 0 auto;
    background-color: #1f1f1f;
    border-radius: 5px;
    position: relative;
}
.id-card-holder:after {
    content: '';
    width: 7px;
    display: block;
    background-color: #0a0a0a;
    height: 100px;
    position: absolute;
    top: 105px;
    border-radius: 0 5px 5px 0;
}
.id-card-holder:before {
    content: '';
    width: 7px;
    display: block;
    background-color: #0a0a0a;
    height: 100px;
    position: absolute;
    top: 105px;
    left: 222px;
    border-radius: 5px 0 0 5px;
}
.id-card {
    
    background-color: #fff;
    padding: 10px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 0 1.5px 0px #b9b9b9;
}
.id-card img {
    margin: 0 auto;
}
.header img {
    width: 100px;
    margin-top: 5px;
}
.photo img {
    width: 80px;
    margin-top: 15px;
}
h2 {
    font-size: 15px;
    margin: 5px 0;
}
h3 {
    font-size: 12px;
    margin: 2.5px 0;
    font-weight: 300;
}
h4 {
    font-size: 10px;
    margin: 2.5px 0;
    font-weight: 300;
}
table {
    margin-left: 10px;
}
table td {
    font-size: 10px;
    font-weight: 300;
}
.qr-code img {
    width: 50px;
}
p {
    font-size: 5px;
    margin: 2px;
}
.id-card-hook {
    background-color: #000;
    width: 70px;
    margin: 0 auto;
    height: 15px;
    border-radius: 5px 5px 0 0;
}
.id-card-hook:after {
    content: '';
    background-color: #d7d6d3;
    width: 47px;
    height: 6px;
    display: block;
    margin: 0px auto;
    position: relative;
    top: 6px;
    border-radius: 4px;
}
.id-card-tag-strip {
    width: 45px;
    height: 40px;
    background-color: #0950ef;
    margin: 0 auto;
    border-radius: 5px;
    position: relative;
    top: 9px;
    z-index: 1;
    border: 1px solid #0041ad;
}
.id-card-tag-strip:after {
    content: '';
    display: block;
    width: 100%;
    height: 1px;
    background-color: #c1c1c1;
    position: relative;
    top: 10px;
}
.id-card-tag {
    width: 0;
    height: 0;
    border-left: 100px solid transparent;
    border-right: 100px solid transparent;
    border-top: 100px solid #0958db;
    margin: -10px auto -30px auto;
}
.id-card-tag:after {
    content: '';
    display: block;
    width: 0;
    height: 0;
    border-left: 50px solid transparent;
    border-right: 50px solid transparent;
    border-top: 100px solid #d7d6d3;
    margin: -10px auto -30px auto;
    position: relative;
    top: -130px;
    left: -50px;
}

body * {
    visibility: hidden;
  }
  #section-to-print, #section-to-print * {
    visibility: visible;
  }
  #section-to-print {
    position: absolute;
    left: 0;
    top: 0;
  }

@media print {
@page { margin: 0; }
  body * {
    visibility: visible;
  }
  #section-to-print, #section-to-print * {
    visibility: visible;
  }
  #section-to-print {
    position: absolute;
    left: 0;
    top: 0;
  }
}
</style>

<?php
require '../db.php';
header('Access-Control-Allow-Origin: *');


$sql = "SELECT * FROM gatepass a left join plant b on a.plant_id=b.plant_id WHERE a.id='".$_GET["id"]."'";
$result = $conn->query($sql);
$pass_no;
$visitor_name;
$company;
$meeting_with;
$department;
$intime;
$photo;
$given_by;
$meeting;
$email;
$mobile;
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
    $pass_no = $row["pass_number"];
    $visitor_name = $row["name"];
    $company = $row["company"];
    $meeting_with = $row["meeting_with"];
    $department = $row["department"];
    $intime = date('H:i d-m-Y',strtotime($row["in_time"]));
    $photo = $row["photo"];
    $given_by = $row["inentry_by"];
    $email=$row['email'];
    $mobile=$row['mobile'];
    $logo_path=$row['logo_path'];
    }
}
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
 $logo = "../logos/$logo_path";

$photo = "/upload/userphoto/user.png";

$user_contact_no = "";
$user_email = "";
$Addr1 = "";
$Addr2 = "";
$meeting="";
//$email='';
$sql1 = "SELECT email, firstname FROM employee WHERE emp_id='".$meeting_with."'";
		$result1 = $conn->query($sql1);
		if ($result1->num_rows > 0) {
		    while ($row1 = $result1->fetch_assoc()) {
		        //$email = $row1["email"];
		        $meeting= $row1["firstname"];
    }
}
?>
<body onload="window.print();setTimeout(window.close, 0);">
<div class="id-card-holder" id="section-to-print">
    <div class="id-card">
        <div class="header">
            <img src="<?php echo $logo; ?>">
         
        </div>
        <h2>Gate Pass</h2>
        <!--<div class="photo">-->
        <!--    <img src="<?php echo $photo; ?>">-->
        <!--</div>-->
        <h2><?php echo $visitor_name; ?></h2>
        <!-- <div style="text-align: left">
            <h3>Company: <?php echo $company; ?></h3>
            <h3>Meeting with: <?php echo $meeting; ?></h3>
            <h3>Department: <?php echo $department; ?></h3>
            <h4>In Time: <?php echo $intime; ?></h4>
            <h4>Given By: <?php echo $given_by; ?></h4>
            <h4>Sign: </h4>
        </div> -->
        <table>
            <tr>
                <td>Company</td>
                <td>: <?php echo $company; ?></td>
            </tr>
            <tr>
                <td>To Meet</td>
                <td>: <?php echo $meeting; ?></td>
            </tr>
            <tr>
                <td>Dept</td>
                <td>: <?php echo $department; ?></td>
            </tr>
            <tr>
                <td>In Time</td>
                <td>: <?php echo $intime; ?></td>
            </tr>
            <tr>
                <td>Given By</td>
                <td>: <?php echo $given_by; ?></td>
            </tr>
            <tr>
                <td>Sign</td>
                <td>:</td>
            </tr>
        </table>
        <hr>
        <p><?php echo $Addr1; ?><p>
        <p><?php echo $Addr2; ?></p>
        <p>Ph: <?php echo $mobile; ?> | E-mail: <?php echo $email; ?></p>
    </div>
</div>

</body>