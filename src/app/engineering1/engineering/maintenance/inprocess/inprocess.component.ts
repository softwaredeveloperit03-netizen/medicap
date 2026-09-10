import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-inprocess',
  templateUrl: './inprocess.component.html',
  styleUrls: ['./inprocess.component.css']
})
export class InprocessComponent implements OnInit {

 
  isView = false;
  isViewData = false;
  results;
  equipments;
  employees;
  preApproval = '';
  prili_req = 'NO';

  selectedResult = [];
  department;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getEquipment(localStorage.getItem('department'));
     this.getEmployee();
    this.getbreakdown_data();
    this.getdepartment();
  }

  getdepartment(){
    this.service.get('engineering/maintenance.php?type=getdepartment').subscribe(response => {
      this.department = response;
    });
  }
  
  getEmployee(){
    this.service.get('engineering/maintenance.php?type=getEmployee').subscribe(response => {
      this.employees = response;
    });
  }


  getEquipment(deptName){
    this.service.get('engineering/maintenance.php?type=getEquipmentForBreakdownBydept&deptName='+deptName).subscribe(response => {
      this.equipments = response;
    });
  }
 
  getbreakdown_data(){
    this.service.get('engineering/maintenance.php?type=getbreakdown_data').subscribe(response => {
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
    temp['equipment_code'] = this.selectedEquip['equipment_code']
    temp['equipment_id'] = this.selectedEquip['id']
    
    this.service.post('engineering/maintenance.php?type=saveMaintainance',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getbreakdown_data();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  


  updateBreakdown(data) {

    if(!data.valid){
      alertify.error("All fields are required");
      return;
    }

    if(this.steps_data.length ==0){
      alertify.error("Please Add Steps");
      return;
    }

    let temp =data.value; 



    if(this.preApproval == 'YES'){
      temp['preApproval'] = 'Pending';

    }else if(this.preApproval == 'NO'){
      if(this.prili_req == 'YES'){
        temp['prili_req'] = 'Pending';
      }else{
        temp['prili_req'] = 'NA';
      }
      temp['preApproval'] = 'NA';
    }
    
    this.prili_req
  
    temp['steps_data'] = this.steps_data;
    this.service.post('engineering/maintenance.php?type=updateSteps&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isViewData = false;
        this.getbreakdown_data();
        this.steps_data =[];
        data.reset();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

   


  steps_data=[];

  addsteps(data){
    if(!data.valid){
      alertify.error("Add Atleast One Make");
      return;
    }
    let temp=data.value;
    this.steps_data[this.steps_data.length] = temp;
    data.reset();
  }

  delsteps(index){
    this.steps_data.splice(index,1);
  }
















 
 

}
