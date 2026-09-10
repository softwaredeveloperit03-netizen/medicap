import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-salary-report',
  templateUrl: './salary-report.component.html',
  styleUrls: ['./salary-report.component.css']
})
export class SalaryReportComponent implements OnInit {
  employees;
  selectedmonth;
  d = new Date();
  m = ("0" + (this.d.getMonth() + 1)).slice(-2);
  y = this.d.getFullYear();
  month = this.y+"-"+this.m;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getEmployeeMonthSalary();
  }

  getEmployeeMonthSalary() {
    this.employees = [];
    this.service.get('hr/salary.php?type=getEmployeeMonthSalary&month='+this.month).subscribe(response => {
      this.employees = response;
    });
  }

  downloadEmployeeMonthSalary(){
    window.open(this.service.url+'pdf/pdfhrDepartment.php?type=downloadempsalaryreport&month='+this.month+'&token=' + localStorage.getItem('token'));
  }

  downloadSlip(emp_id) {
    this.service.open('hr/salary.php?type=downloadSalarySlip&month='+this.month + '&employee=' + emp_id);
  }

}
