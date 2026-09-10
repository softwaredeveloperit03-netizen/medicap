import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  isPassword = false;
  employees;
  emp_id = '';
  departments;
  designations;

  department_name = '';
  designation = '';
  status = '';

  selectedResult = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getEmployees();
    this.getDepartments();
    this.getDesignations();
  }

  getEmployees() {
    this.service.get('hr/employee.php?type=getEmployeesList&department_name=' + this.department_name + '&designation=' + this.designation + '&status=' + this.status).subscribe(response => {
      this.employees = response;
    });
  }

  view(index) {
    this.selectedResult = this.employees[index];
    this.isView = true;
  }
  
  getDepartments() {
    this.service.get('hr/employee.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getDesignations() {
    this.service.get('hr/employee.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }

  edit(index) {
    this.router.navigate(['/employees/edit/' + this.employees[index].emp_id]);
  }

  del(emp_id) {
    this.emp_id = emp_id;
    this.isPassword = true;
  }

  verify(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('hr/employee.php?type=delEmployee&emp_code=' + this.emp_id, JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Updated Successfully');
        this.isPassword = false;
        this.emp_id = '';
        this.getEmployees();
      } else {
        alert('Invalid Password');
      }
    });
  }

}
