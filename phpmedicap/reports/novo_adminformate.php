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

    $sql = "SELECT * FROM token WHERE token='".$_GET["token"]."'";
    $result = $conn->query($sql);
    $_GET["emp_id"] = "";
    $_GET["department"] = "";
    

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
	    $string = decrypt('decrypt',$_GET["token"],$row["key1"],$row["key2"]);
	    $string = explode("$",$string);
	    $_GET["emp_id"] = $string[0];
	    $_GET["department"] = $string[1];
	    break;
    }

}
    $txt = '{"process": "FRONTEND", "token": "'.$token.'", "action": "'.$_GET["type"].'", "actiontime": "'.$entry_date.'", "department": "'.$_GET["department"].'", "emp_id": "'.$_GET["emp_id"].'", "method": "'.$_SERVER['REQUEST_METHOD'].'", "REMOTE_ADDR": "'.$_SERVER['REMOTE_ADDR'].'"}';
    $myfile = file_put_contents('../logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
      
       if($_GET['type'] == 'firstaid'){
        $_GET['filename'] = 'List of first Aid Boxes'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimpnovo.php');
           $html= "";

        $html.='
        <div></div><div></div>  <div></div><div></div>  
     <table cellpadding="5" border="0.1">
     <tr>
     <td style="width:20%;text-align:center"><b>Sr. No.</b></td>
      <td style="width:40%;text-align:center"><b>First Aid Box Location</b></td>
       <td style="width:40%;text-align:center"><b>First Aid Box No</b></td>
     </tr>
     <tr>
     <td style="width:20%;text-align:center"></td>
      <td style="width:40%;text-align:center"></td>
       <td style="width:40%;text-align:center"></td>
     </tr>
     <tr>
     <td style="width:20%;text-align:center"></td>
      <td style="width:40%;text-align:center"></td>
       <td style="width:40%;text-align:center"></td>
     </tr>
     <tr>
     <td style="width:20%;text-align:center"></td>
      <td style="width:40%;text-align:center"></td>
       <td style="width:40%;text-align:center"></td>
     </tr>
      </table><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
       <table cellpadding="5" border="0.1">
       <tr>
     <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
       <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
         <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
     </tr>
      <tr>
     <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
       <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
         <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
     </tr>
      <tr>
     <td style="width:25%;"><b> Name</b></td>
      <td style="width:25%;"></td>
       <td style="width:25%;"></td>
         <td style="width:25%;"></td>
     </tr>
      <tr>
     <td style="width:25%;"><b> Sign/Date</b></td>
      <td style="width:25%;"></td>
       <td style="width:25%;"></td>
         <td style="width:25%;"></td>
     </tr>
     <tr>
     <td style="width:25%;"><b> Designation</b></td>
      <td style="width:25%;"></td>
       <td style="width:25%;"></td>
         <td style="width:25%;"></td>
     </tr>
     <tr>
     <td style="width:25%;"><b> Department</b></td>
      <td style="width:25%;"></td>
       <td style="width:25%;"></td>
         <td style="width:25%;"></td>
     </tr>
       </table>';
     $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
    
    }
     else if($_GET['type'] == 'listofitem'){
        $_GET['filename'] = 'List of Item available in First Aid box'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimpnovo.php');
           $html= "";
           
           $html.='
            <div></div><div></div>  <div></div><div></div>  
           <table cellpadding="5" border="0.1">
           <tr>
            <td style="width:5%;text-align:center"><b>Sr No</b></td>
           <td style="width:10%;text-align:center"><b>Item Name</b></td>
           <td style="width:8%;text-align:center"><b>Jan</b></td>
           <td style="width:8%;text-align:center"><b>Feb</b></td>
           <td style="width:8%;text-align:center"><b>Mar</b></td>
           <td style="width:8%;text-align:center"><b>Apr</b></td>
           <td style="width:8%;text-align:center"><b>May</b></td>
            <td style="width:8%;text-align:center"><b>Jun</b></td>
             <td style="width:8%;text-align:center"><b>July</b></td>
              <td style="width:8%;text-align:center"><b>Aug</b></td>
               <td style="width:7%;text-align:center"><b>Sep</b></td>
                <td style="width:5%;text-align:center"><b>Oct</b></td>
                 <td style="width:7%;text-align:center"><b>Nov</b></td>
                  <td style="width:5%;text-align:center"><b>Dec</b></td>
           </tr>
           <tr>
            <td style="width:5%;text-align:center"></td>
            <td style="width:10%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
            <td style="width:8%;text-align:center"></td>
             <td style="width:8%;text-align:center"></td>
              <td style="width:8%;text-align:center"></td>
               <td style="width:7%;text-align:center"></td>
                <td style="width:5%;text-align:center"></td>
                 <td style="width:7%;text-align:center"></td>
                  <td style="width:5%;text-align:center"></td>
           </tr>
            <tr>
            <td style="width:5%;text-align:center"></td>
            <td style="width:10%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
            <td style="width:8%;text-align:center"></td>
             <td style="width:8%;text-align:center"></td>
              <td style="width:8%;text-align:center"></td>
               <td style="width:7%;text-align:center"></td>
                <td style="width:5%;text-align:center"></td>
                 <td style="width:7%;text-align:center"></td>
                  <td style="width:5%;text-align:center"></td>
           </tr>
            <tr>
            <td style="width:5%;text-align:center"></td>
            <td style="width:10%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
            <td style="width:8%;text-align:center"></td>
             <td style="width:8%;text-align:center"></td>
              <td style="width:8%;text-align:center"></td>
               <td style="width:7%;text-align:center"></td>
                <td style="width:5%;text-align:center"></td>
                 <td style="width:7%;text-align:center"></td>
                  <td style="width:5%;text-align:center"></td>
           </tr>
            <tr>
            <td style="width:15%;text-align:center"><b>Checked By Sign & Date</b></td>
           
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
           <td style="width:8%;text-align:center"></td>
            <td style="width:8%;text-align:center"></td>
             <td style="width:8%;text-align:center"></td>
              <td style="width:8%;text-align:center"></td>
               <td style="width:7%;text-align:center"></td>
                <td style="width:5%;text-align:center"></td>
                 <td style="width:7%;text-align:center"></td>
                  <td style="width:5%;text-align:center"></td>
           </tr>
           
          </table><div></div>
          <tr>
         <td style="width:100%"><b>Note :-</b>Tick (√) to be done for availability of the item.</td>
          </tr>
  
   <div></div><div></div><div></div><div></div><div></div><div></div><div></div>
       <table cellpadding="5" border="0.1">
       <tr>
     <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
       <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
         <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
     </tr>
      <tr>
     <td style="width:25%;text-align:center"></td>
      <td style="width:25%;text-align:center"><b>PREPARED BY</b></td>
       <td style="width:25%;text-align:center"><b>REVIEWED BY</b></td>
         <td style="width:25%;text-align:center"><b>APPROVED BY</b></td>
     </tr>
      <tr>
     <td style="width:25%;"><b> Name</b></td>
      <td style="width:25%;"></td>
       <td style="width:25%;"></td>
         <td style="width:25%;"></td>
     </tr>
      <tr>
     <td style="width:25%;"><b> Sign/Date</b></td>
      <td style="width:25%;"></td>
       <td style="width:25%;"></td>
         <td style="width:25%;"></td>
     </tr>
     <tr>
     <td style="width:25%;"><b> Designation</b></td>
      <td style="width:25%;"></td>
       <td style="width:25%;"></td>
         <td style="width:25%;"></td>
     </tr>
     <tr>
     <td style="width:25%;"><b> Department</b></td>
      <td style="width:25%;"></td>
       <td style="width:25%;"></td>
         <td style="width:25%;"></td>
     </tr>
       </table>';
     $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
     }
     
     else if($_GET['type'] == 'SOP'){
        $_GET['filename'] = 'First Aid'; $_GET['pdftype'] = 'onlyheader'; include('../pdfimpnovo.php');
           $html= "";
           
           $html.='
            <div></div><div></div>  
          <ol>
          <li><b>OBJECTIVE:</b></li>
          <ol>
           <br>
           <li>  The objective of this SOP is to ensure adequate arrangements for maintaining necessary first<br>
           aid facilities in the factory. Also to provide a documented procedure for ensuring first aid<br>
           boxes which provided in the factory fully equipped and maintained all the time to deal with<br>
           any emergency first aid for an injury in the factory.
           </li>
         
          </ol>
          </ol>
           ';
     $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('','I');
     }
    $conn->close();
?>