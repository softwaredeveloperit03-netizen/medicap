import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  departments;
  designations;
  emp_name;
  department;
  designation;
  resignation_date;
  expected_releaving;
// getEmployees: any;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.service.observableDepartment
    .subscribe(response => {
      this.departments = response;
    });
    this.getDepartments();
  }


  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }
  employees;
  getEmployees(){
    this.service.get('hr/employee.php?type=get_EMP_by_department&department1='+this.department)
    .subscribe(response => {
      this.employees = response;
    });
  }
  selectedEmp;
  emp_ids;
  getEmployees_id(index){
    this.selectedEmp=this.employees[index-1];
    this.emp_ids=this.selectedEmp['emp_id']
    this.designation=this.selectedEmp['designation']
    console.log(this.selectedEmp)
    console.log(this.emp_ids)
  }
 
   
  

  submit(Form){
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = Form.value;
    // temp['result']=this.result;
    this.service.post('hr/resignation.php?type=saveResignation', JSON.stringify(temp)) 
    .subscribe(response => {
      if (response['status'] === 'success') {       
        Form.resetForm();
        alertify.success("save successfully");
      } else {
        alertify.error('Please Try Again');
      }
      })
    }
}

