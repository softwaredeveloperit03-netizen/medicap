import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-right',
  templateUrl: './right.component.html',
  styleUrls: ['./right.component.css']
})
export class RightComponent implements OnInit {

  ismail = 'No';
  iscontact = 'No';
  isNew = false;
  isView = false;
  results;
  departments;
  designations;

  department_name = '';
  designation = '';
  user = '';
  checker = '';
  approver = '';
  auditor = '';

  selectedResult = [];
  additionalList = [];

  departments1 = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getTechnicalEmployees();
    this.getDepartments();
    this.getDesignations();
  }

  getTechnicalEmployees(){
    this.service.get('hr/employee.php?type=getTechnicalEmployees&department_name=' + this.department_name + '&designation=' + this.designation).subscribe(response => {
      this.results = response;
    });
  }

  // getDepartments() {
  //   this.service.get('common.php?type=getTechnicalDepartments').subscribe(response => {
  //     this.departments = response;
  //   });
  // }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
  
  getDesignations() {
    this.service.get('common.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }

  view(index, value){
    this.selectedResult = this.results[index];

    this.departments1 = [];
    for (let  i = 0; i < this.departments.length; i++) {
      if (this.departments[i].department_name !== this.selectedResult['department']) {
        this.departments1[this.departments1.length] = this.departments[i];
      }
    }

    if (value == 'new') {
      this.isNew = true;
    } else {
      this.isView = true;
    }
  }

  submit(data) {
    let temp = data.value;
    /* temp['user'] = this.user;
    temp['checker'] = this.checker;
    temp['approver'] = this.approver; */
    temp['additional'] = this.additionalList;
    this.service.post('hr/employee.php?type=assignRights&id=' + this.selectedResult['emp_id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
        this.isView = false;
        this.getTechnicalEmployees();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }

  update(data) {
    let temp = data.value;
    temp['user'] = this.user;
    temp['checker'] = this.checker;
    temp['approver'] = this.approver;
    temp['additional'] = this.selectedResult['additional'];
    this.service.post('hr/employee.php?type=changeRights&id=' + this.selectedResult['emp_id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
        this.isView = false;
        this.getTechnicalEmployees();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }

  add(data, value) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (value == 'change') {
      let additionalList = this.selectedResult['additional'];
      additionalList[additionalList.length] = data.value;
      data.resetForm();
    } else {
      this.additionalList[this.additionalList.length] = data.value;
      data.resetForm();
    }
  }

  del(index, value) {
    if (value == 'change') {
      let additionalList = this.selectedResult['additional'];
      additionalList.splice(index, 1);
    } else {
      this.additionalList.splice(index, 1);
    }
  }

}
