import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-induction',
  templateUrl: './induction.component.html',
  styleUrls: ['./induction.component.css']
})
export class InductionComponent implements OnInit {
  isView=false;
  trainings = [];
  selectedResult=[];
  trainers=[];
  activities=[];
  departments;
  eqp=[];
  employees;
  equipments;
  learnings=[];
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getTranings();
    this.service.observableDepartment.subscribe(response=>{
      this.departments=response;
    })
  }

  getTranings() {
    this.service.get('hr/induction.php?type=getInductionLog').subscribe((response: any) => {
      this.trainings = response;
    });
  }


  // getEquipments(value) {
   
  // }


  getAllEmployee(value) {
    this.service.get('employee.php?type=getEmployeeByDepartment&department_name='+value).subscribe((response: any) => {
      this.employees = response;
    });
    this.service.get('equipments.php?type=getDepartmentEquipments&department_name='+value).subscribe((response: any) => {
      this.equipments = response;
    });
  }

  download() {
    this.service.open('hr/induction.php?type=downloadInductionTrainingLog' );
  }

  proceed(index) {
    this.selectedResult = this.trainings[index];
    this.isView = true;
  }



  
  AddEquipment(data){
    this.activities[this.activities.length]=data.value;
    data.reset();
  }
  delEquipment(index){
    this.activities.splice(index,1);
  }

  save() {
    let temp={};
    temp['activities']=this.activities;
    this.service.post('hr/induction.php?type=saveInduction', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }





}
