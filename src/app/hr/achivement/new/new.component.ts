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
  employees;
  departments;
  designations;
  currentsalary;
  departmenthead;
  lastapprovaldate;
  approvaldue;
  typeofdapproval=[];
  department_name;
  emp_code;
  today;
  employeeList;


  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getEmployees();
    this.service.observableDepartment.subscribe(response => {
      this.departments = response;
    });

    // this.getDepartments();
  this.getDesignations();
  }
  // getDepartments() {
  //   this.service.get('common.php?type=getNonTechnicalDepartments').subscribe(response => {
  //     this.departments = response;
  //   });
  // }
 
  getEmployees() {
    this.service.get('hr/task.php?type=getEmployees').subscribe((response: any) => {
      this.employeeList = response;
    });
  }
  getDepartmentsEmployee(value) {
    this.service.get('hr/attendance.php?type=getDepartmentEmployees&department_name=' + value).subscribe(response => {
      this.employees = response;
    });
  }
  getDesignations() {
    this.service.get('hr/employee.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }

  save(Form){
    if(!Form.valid){
      alertify.error('All fields are required');
      return;
    }
    let temp=Form.value
    this.service.post('hr/achievement.php?type=saveAchievement',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        this.router.navigate(['/hr/achievement']);
        alertify.success('data save Successfuly');
        Form.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }

}
