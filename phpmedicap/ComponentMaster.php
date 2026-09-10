<?php


    require 'db.php';
    require 'token.php';
    require 'tcpdf/tcpdf.php';
    header('Access-Control-Allow-Origin: *');
    date_default_timezone_set("Asia/Kolkata");
    $token = $_GET["token"];
    $timestamp = time();
    $entry_date = date("Y-m-d h:i:s", $timestamp);
    $input = json_decode(file_get_contents('php://input'),true);

 
  
    // global $conn = $conn ;
    // $sql = "SELECT * FROM component_master";
    // $result = $conn->query($sql);
    // if($result->num_rows > 0){
    // while($row = $result->fetch_assoc()){
	   //$output[] = $row;
	   //$menu = [];
    //     foreach($output as $key=>$item){
            
    //         if($item["parent_id"]== ''){
    //             $menu[] = $item ;
    //             $menu[count($menu) - 1]["submenu"]=[];
    //         }
    //         //print_r($menu[]);
    //     }
        
    //     foreach($menu as $key=>$item){
    //         //print_r($item["id"]);
    //         foreach($output as $key1=>$item1){
    //             if($item["id"]==$item1["parent_id"]){
    //             $menu[$key]["submenu"][] = $item1;

    //             }
    //         }
    //         print_r($menu);
    //     }
        

    // }
    // // echo json_encode($output);
    // }
    
 
    
    function recursive( $conn,$menu,$id='')
    {
           
           
            if($id=='')
             $sql = "SELECT * FROM component_master where parent_id is NULL";
             else
             $sql = "SELECT * FROM component_master where parent_id =".$id;
             $result = $conn->query($sql);

          
                if($result->num_rows > 0)
                {
                    while($row = $result->fetch_assoc())
                    {
	                  
                           
                            //$menu[] = $item ;
                           
                            $menu[] = array("id"=>$row["id"],"component_name"=>$row['component_name'],
                                            "icon"=>$row["icon"]);
                            
                            $q="select * from component_master where parent_id=".$row["id"];


                            $r = $conn->query($q);
                            if($r->num_rows > 0){  
                                $menu[count($menu)-1]["submenu"]=recursive($conn,$menu[count($menu)-1]["submenu"],$row["id"]);

                            }
                            
                             
                           

                          
                       
                        
                    }
                    
                }
                return $menu ;
        
        
    }


    function getMenu( $conn)
    {
       
        $array = array();
       $menu =  recursive( $conn,$array);
       
       echo json_encode($menu);
    }

    getmenu( $conn);
    
    ?>