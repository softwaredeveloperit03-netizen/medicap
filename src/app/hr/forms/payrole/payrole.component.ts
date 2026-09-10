import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-payrole',
  templateUrl: './payrole.component.html',
  styleUrls: ['./payrole.component.css']
})
export class PayroleComponent implements OnInit {
  employees;
  from_month;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getEmployeeMonthSalary();
  }

  getEmployeeMonthSalary() {
    this.service.get('hrDepartment.php?type=getEmployeeMonthSalary').subscribe(response => {
      this.employees = response;
    });
  }

  reportpayrole() {
    window.location.href= this.service.url + 'reports/salary.php';
  }

}
