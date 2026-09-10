import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-letter-form',
  templateUrl: './letter-form.component.html',
  styleUrls: ['./letter-form.component.css']
})
export class LetterFormComponent implements OnInit {

  letter_type;
  departments;
  designations;
  employees;  
  department;
  designation;
  employee;
  emp_id;
  goverment;
  to;
  address;
  subject;
  body;
  result=[];
  agency;

 
  
   constructor(private service: DataAccessService,private router: Router) { }
  

  ngOnInit() {
    this.service.observableDepartment
    .subscribe(response => {
      this.departments = response;
    });
    this.getDepartments();
    this.getGovagency();
  }
 

  savenew(Form) {
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    } 
    let temp = Form.value;
    temp['result']=this.result;
    this.service.post('hr/shift.php?type=saveLetter', JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] === 'success') {       
        Form.resetForm();
        alertify.success("save successfully");
      } else {
        alertify.error('Please Try Again'); 
      }
      })
    }

  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }
  getEmployees(dept, designation) {
    this.service.get('hr/employee.php?type=getDepartmentEmployees' + '&department_name=' + dept + '&designation=' + designation)
    .subscribe(response => {
      this.employees = response;
    });
  }
  getGovagency() {
    this.service.get('hr/shift.php?type=getGovagency')
    .subscribe(response => {
      this.agency = response;
    });
  }
  getDesignation(data) {
    let department = data.value;
    for (let i=0; i< this.departments.length;i++){
      if(this.departments[i]['department_name'] == department){
        this.designations = this.departments[i]['designations'];
      }
    } 
  }
  // getDesignation(idx) {
  //   this.designations =[];
  //   this.designations = this.departments[idx-1]['designations']
  //   this.emp_id = this.departments[idx-1]['emp_id']
  // }
}
