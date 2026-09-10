import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-breakdown',
  templateUrl: './breakdown.component.html',
  styleUrls: ['./breakdown.component.css']
})
export class BreakdownComponent implements OnInit {

 
 
  isView = false;
  isViewData = false;
  results;
  equipments;
  employees;
  preApproval = '';
  prili_req = 'NO';
  department_name;
  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getEquipments();
     this.getEmployee();
    this.getbreakdown_data();
    this.department_name = localStorage.getItem('department');
  }

  getEmployee(){
    this.service.get('engineering/maintenance.php?type=getempbydept&department_name='+localStorage.getItem('department')).subscribe(response => {
      this.employees = response;
    });
  }

  getEquipments(){
    this.service.get('engineering/maintenance.php?type=getEquipmentForBreakdownByDepartment&department_name='+localStorage.getItem('department')).subscribe(response => {
      this.equipments = response;
    });
  }
 
  getbreakdown_data(){
    this.service.get('engineering/maintenance.php?type=getebmlogbydepartment&department_name='+localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }
 

  
  view(index){
    this.selectedResult = this.results[index];
    this.isViewData = true;
  }

    

  selectedEquip=[];
  selectedEquipment(index){
    this.selectedEquip = this.equipments[index];
  }

 
  attend(data) {
    if(!data.valid){
      alertify.error("All fields are required");
      return;
    }
    let  temp = data.value;
    temp['equipment_code'] = this.selectedEquip['equipment_code'];
    temp['equipment_id'] = this.selectedEquip['id'];
    temp['department_name'] = this.department_name;
    
    this.service.post('engineering/maintenance.php?type=saveMaintainance',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getbreakdown_data();
        this.department_name  = localStorage.getItem('department');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  
 
 
}
