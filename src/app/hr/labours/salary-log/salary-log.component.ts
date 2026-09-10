import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import * as XLSX from 'xlsx';

@Component({
  selector: 'app-salary-log',
  templateUrl: './salary-log.component.html',
  styleUrls: ['./salary-log.component.css']
})
export class SalaryLogComponent implements OnInit {

  labourlist;
  selectedmonth;
  d = new Date();
  m = ("0" + (this.d.getMonth() + 1)).slice(-2);
  y = this.d.getFullYear();
  // month = this.y+"-"+this.m;
  month;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getMonthSalary1();
  }
   getMonthSalary() {
    this.labourlist = [];
    this.service.get('hrDepartment.php?type=getLabourMonthSalary&month='+this.month).subscribe(response => {
      this.labourlist = response;
    });
  }
   getMonthSalary1() {
    this.labourlist = [];
    this.service.get('hrDepartment.php?type=getLabourMonthSalaryall').subscribe(response => {
      this.labourlist = response;
    });
  }

  downloadLabourMonthSalary(){
    window.open(this.service.url+'pdf/pdfhrDepartment.php?type=downloadlaboursalaryreport&month='+this.month+'&token=' + localStorage.getItem('token'));
  }


  exportToExcel(): void {
    const formattedData = this.labourlist.map(user => ({
      'ID': user.labour_no,
      'Name': user.labour_name,
      'Month Days': user.total_days,
      'Absent Days': user.total_days - user.present_days,
      'Present Days': user.present_days,
      'Total Ot': user.ot,
      'Salary/Day': user.daily_wages,
      'Total Earning': (user.present_days * user.daily_wages) + user.ot_total
    }));

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);
    const workbook: XLSX.WorkBook = {
      Sheets: { 'data': worksheet },
      SheetNames: ['data']
    };

    XLSX.writeFile(workbook, 'Salary_Log.xlsx');
  }
}
