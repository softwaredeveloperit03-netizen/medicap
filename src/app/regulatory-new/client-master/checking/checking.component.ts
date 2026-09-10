import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

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
   
    this.service.get('/hr/achievement.php?type=getSample').subscribe(response => {
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

  viewEnter_stp(url) {
     window.open(this.service.url+'../../upload/product/' + this.selectedResult['enter_stp']);
    window.open(url, '_blank');
  }
  viewVendor(url) {
     window.open(this.service.url+'../../upload/product/' + this.selectedResult['stp']);
    window.open(url, '_blank');
  }
  viewSTP(url) {
     window.open(this.service.url+'../../upload/product/' + this.selectedResult['stp']);
    window.open(url, '_blank');
  }


  updateApprove() {
   
      this.service.post('hr/achievement.php?type=updateApprove&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


  updateReject() {

    this.service.post('hr/achievement.php?type=updateReject&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success("update successfully");
        this.remark = '';
       
      } else {
        alertify.error('Failed to Update , Please try again!');
      }

    });
  }
}
