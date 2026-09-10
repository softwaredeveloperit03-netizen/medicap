import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  
  isView = false;
  isViewData = false;
  results;
  equipments;
  employees;
  preApproval = '';
  prili_req = 'NO';

  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getEmployee();
    this.getbreakdown_data();
  }

  getEmployee(){
    this.service.get('engineering/maintenance.php?type=getEmployee').subscribe(response => {
      this.employees = response;
    });
  }

  getEquipments(department_name){
    this.service.get('engineering/maintenance.php?type=getEquipmentForBreakdownByDepartment&department_name='+department_name).subscribe(response => {
      this.equipments = response;
    });
  }
 
 
  getbreakdown_data(){
    this.service.get('engineering/maintenance.php?type=getreplacementEqupiment').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
    this.getEquipments(this.selectedResult['department_name']);
  }
 
  view1(index){
    this.selectedResult = this.results[index];
    this.isViewData = true;
   }
 

  selectedEquip=[];
  selectedEquipment(index){
    this.selectedEquip = this.equipments[index-1];
  }
 

  addreplesment(data) {

    if(!data.valid){
      alertify.error("All fields are required");
      return;
    }

    let temp =data.value; 
    temp['replacement_equipment'] = this.selectedEquip['id'];
    
 
    this.service.post('engineering/maintenance.php?type=saveReplacement&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getbreakdown_data();
        data.reset();
        
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }




  savereplacementReport(data) {

    if(!data.valid){
      alertify.error("All fields are required");
      return;
    }

    let temp =data.value; 
     
 
    this.service.post('engineering/maintenance.php?type=savereplacementReport&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isViewData = false;
        this.getbreakdown_data();
        data.reset();
        
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



  

}
