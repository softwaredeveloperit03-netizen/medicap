import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  orders;
  loading;
  isView=false;
  selectedResult=[];
  name_client;
  address;
  temp_name;
  temp_address;
  temp_country;
  temp_no;
  temp_email;
    remark: string;

  constructor(private service: DataAccessService)  { }

  ngOnInit() {
    this.getData();
  }
  getData() {
   
    this.service.get('/hr/achievement.php?type=getSampleLog').subscribe(response => {
      this.orders = response;
    
    });
  } 

  requirement ='';
  type ='';
  avd_country ='';
  grade ='';
  testing ='';
  unit ='';
  solvent_system ='';
  color ='';
  core_tablet ='';
  enter ='';
  equip_avail ='';
   
  view(index){
    this.selectedResult=this.orders[index];
    this.name_client= this.selectedResult['LglNm'];
    this.address= this.selectedResult['address'];
    this.temp_name= this.selectedResult['temp_name'];
    this.temp_address= this.selectedResult['temp_address'];
    this.temp_country= this.selectedResult['temp_country'];
    this.temp_no= this.selectedResult['temp_no'];
    this.temp_email= this.selectedResult['temp_email'];
 
    this.equip_avail= this.selectedResult['equip_avail'];
    this.enter= this.selectedResult['enter'];
    this.core_tablet= this.selectedResult['core_tablet'];
    this.color= this.selectedResult['color'];
    this.solvent_system= this.selectedResult['solvent_system'];
    this.unit= this.selectedResult['unit'];
    this.testing= this.selectedResult['testing'];
    this.grade= this.selectedResult['grade'];
    this.avd_country= this.selectedResult['avd_country'];
    this.type= this.selectedResult['type'];
    this.requirement= this.selectedResult['requirement'];
 
    
    this.isView=true;
  }

   
  
  


 

}
