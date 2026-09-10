import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  
  isView=false;
  selectedResult = [];
  department_name;
  checklist = [];
  checklistList = [];
  employees;
  equipments;
  departments;
  equipment_name;
  depequipmentsCode;
  depequipments;
  evaluation_parameter;
  employeeList;
  equipment;
  department;
  equipment_code;

  constructor(private service: DataAccessService, private router: Router, private fb: FormBuilder) {
   }

  ngOnInit() {
    this.getEquipmentList();
    this.getDepartments();
    this.getEmployees();
    // this.service.observableDepartment.subscribe(response => {
    //   this.departments = response;
    // });
  }

  getEquipmentList() {
    this.service.get('engineering/preventive.php?type=getPreventChecklist').subscribe(response => {
      this.equipments = response;
    });
  }

  getEmployees() {
    this.service.get('hr/task.php?type=getEmployees').subscribe((response: any) => {
      this.employeeList = response;
    });
  }
  // getDepartmentsEmployee1(value) {
  //   this.service.get('hr/attendance.php?type=getDepartmentEmployees&department_name=' + value).subscribe(response => {
  //     this.employees = response;
  //   });
  // }

  save(data) {
    if (this.checklistList.length == 0) {
      alertify.error('All fields are required');
      return;
    }
    if(!data.valid){
      alertify.error("not valid")
    }
    var obj ={
      "department" : data.value['department'],
      "frequency_type" : data.value['frequency_type'],
      "equipment_name" : data.value['equipment_name'],
      "equipment_id" : data.value['equipment_id'],
      "frequency" : data.value['frequency'],
      "partname" : data.value['partname'],
      "checktype" : 'priventive',
      "checklist_details" : this.checklistList
    }
    this.service.post('engineering/preventive.php?type=savePreventChecklist', JSON.stringify(obj)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.getEquipmentList();
        this.router.navigate(['/engineering/preventive/checklist']);
        this.isView = false;
        this.checklistList = [];
        alertify.success(this.service.t('common.savedSuccess'));
      } else {
        alertify.error('Failed: An error has occurred, please try again');
      }
    });
  }

  // addCheckList(data) {
  //   if(!data.valid){
  //     alertify.error("not valid")
  //   }
  //   this.checklist[this.checklist.length] = data.value;
  //   data.resetForm();
  // }
  addCheckList(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
   
    this.checklistList[this.checklistList.length] = temp;
    console.log(this.checklistList);
    data.resetForm();
  }
  delData(index) {
    this.checklistList.splice(index, 1);
  }

  view(index){
    this.selectedResult=this.equipments[index];
    this.isView=true;
  }

  getDepartments(){
    this.service.get('engineering/preventive.php?type=get_department').subscribe((response: any) => {
      this.departments = response;
  
    });}
    
  getDepartmentsEqupment(){
    this.service.get('engineering/preventive.php?type=getDepartmentsEqupment&department_name='+this.department_name).subscribe((response: any) => {
      this.depequipments = response;
    });
  }
    getDepartmentsEqupmentCode(){

    this.service.get('engineering/preventive.php?type=getDepartmentsEqupmentCode&equipment_name='+this.equipment_name).subscribe((response: any) => {
      this.depequipmentsCode = response;
  
    });}
  
}
