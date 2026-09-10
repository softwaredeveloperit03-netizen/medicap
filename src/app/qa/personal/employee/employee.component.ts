import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-employee',
  templateUrl: './employee.component.html',
  styleUrls: ['./employee.component.css']
})
export class EmployeeComponent implements OnInit {

  employees;
  isView = false;
  
  selectedEmployee;
  search_employee;
  filtervalue: Array<any> = [];
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getEmployees();
  }

  getEmployees() {
    this.service.get('hrDepartment.php?type=getEmployees')
    .subscribe(response => {
      this.employees = response;
      this.filtervalue = this.employees;
    });
  }

  viewEmployee(index) {
    this.selectedEmployee = this.employees[index];
    this.isView = true;
  }

  filterTable(value) {
   this.employees = this.filtervalue;
   this.employees = this.employees.filter(i => i.emp_name.toLowerCase().indexOf(value.toLowerCase()) !== -1);
  }

}
