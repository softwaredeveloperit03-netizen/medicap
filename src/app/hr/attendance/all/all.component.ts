import { Component, OnInit } from '@angular/core';
import { FormBuilder } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
import { ChangeDetectorRef } from '@angular/core';


@Component({
  selector: 'app-all',
  templateUrl: './all.component.html',
  styleUrls: ['./all.component.css'],
})
export class AllComponent implements OnInit {
  
  employees;
  departments;
  department_name = 'Production';
  results;
  month = '';
  columns;
  columns1;

  constructor(
    private service: DataAccessService,
    private router: Router,
    private http: HttpClient,private cdr: ChangeDetectorRef
  ) {
    var d1 = new Date();
    let m1 = d1.getMonth();
    m1 = +m1 + 1;
    let mon1 = '';
    if (m1 > 0 && m1 < 10) {
      mon1 = '0' + m1;
    }
    this.month = d1.getFullYear() + '-' + mon1;


    const d = new Date();

    // Get current date components
    const day = d.getDate();
    const month = d.getMonth() + 1;  // getMonth() returns 0-11, so add 1 to get 1-12
    const year = d.getFullYear();

    // Add leading zero to day if necessary
    const formattedDay = day < 10 ? '0' + day : day;

    // Add leading zero to month if necessary
    const formattedMonth = month < 10 ? '0' + month : month;

    // Construct to_date as the current date
    this.end_date = `${year}-${formattedMonth}-${formattedDay}`;

    // Construct from_date as the first day of the current month
    this.start_date = `${year}-${formattedMonth}-01`;

 
  }

  ngOnInit() {
    this.service.observableDepartment.subscribe((response) => {
      this.departments = response;
    });
    this.onSearch();
    this.getEmployee(this.department_name);
  }

  close() {
    this.router.navigate(['/hr/dashboard']);
  }


   


  start_date='';
  end_date='';
  employeeId='';


  forExcel;

onSearch() {


  this.service.get('hr/attendance.php?type=getMonthlyAttendance1&department_name=' +this.department_name + '&month=' +this.month+'&employeeId=' +this.employeeId +'&start_date=' +this.start_date +'&end_date=' +this.end_date ).subscribe((data: any[]) => {

    if (!Array.isArray(data)) {
      return;
    }

    
    // Extract all unique keys from the data array
    const allKeysSet = new Set<string>();
    data.forEach(obj => Object.keys(obj).forEach(key => allKeysSet.add(key)));
    const allKeys = Array.from(allKeysSet);
    // Filter out the date keys
    const dateKeys = allKeys.filter(key => key.match(/^\d{4}-\d{2}-\d{2}$/));
    // Filter out the date keys to exclude time-related fields
    const columns1 = allKeys.filter(key => !dateKeys.includes(key));
    // Set the columns1 and results arrays for use in the template
    this.columns1 = allKeys;
    this.results = data;

     
  });
  



  this.service.get('hr/attendance.php?type=getMonthlyAttendance1ForBuddhiJivi&department_name='
    +this.department_name + '&month=' +this.month+'&employeeId=' +this.employeeId +
    '&start_date=' +this.start_date +'&end_date=' +this.end_date ).
  subscribe((response) => {
      this.forExcel  = response;
      this.cdr.detectChanges()
    });



 

  
}




 

isOld = true; 
isNew = false; 

jadu(value){


  if(value ==1){

    this.isOld = true; 
    this.isNew = false; 

  }else if(value == 2){

    this.isOld = false; 
    this.isNew = true; 
  }


}


  getEmployee(serch) {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' +serch ).
    subscribe((response) => {
        this.employees  = response;
      });
  }
  emp_id='';

  cleanColumnHeader(header: string): string {
    return header.replace(/^\d{1,2}_/, '');
  }

  downloadReport() {
    this.service.open(
      'hr/attendance.php?type=downloadAttendance&department_name=' +
        this.department_name +
        '&month=' +
        this.month
    );
  }

  AllRecord() {
    var d = new Date();
    let m = d.getMonth();
    m = +m + 1;
    let mon = '';
    if (m > 0 && m < 10) {
      mon = '0' + m;
    }
    this.month = d.getFullYear() + '-' + mon;

    this.department_name = '';
    //this.searchEmployee('Month');
  }



  exportToExcel(): void {
    const ws: XLSX.WorkSheet = XLSX.utils.table_to_sheet(
      document.getElementById('dataTable')
    );
    const wb: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
    XLSX.writeFile(wb, 'attendance.xlsx');
  }

  exportToExcel1(): void {
    const ws: XLSX.WorkSheet = XLSX.utils.table_to_sheet(
      document.getElementById('dataTable1')
    );
    const wb: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
    XLSX.writeFile(wb, 'attendance.xlsx');
  }



  exportToExcelIntimeOuttime() {
    // Extract only the columns needed for export (excluding "work_hrs")
  // Extract only the columns needed for export (excluding "work_hrs")
  const exportData = this.results.map(result => {
    const exportResult = {};
    for (const col of this.columns) {
      if (col !== 'work_hrs') {
        exportResult[col] = result[col];
      }
    }
    return exportResult;
  });

  const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(exportData, {
    header: this.columns1.map(col => this.cleanColumnHeader(col)) // Clean the column headers
  });

  const workbook: XLSX.WorkBook = {
    Sheets: { 'Data': worksheet },
    SheetNames: ['Data']
  };
    // Save the Excel file
    XLSX.writeFile(workbook, 'attendace_with_intime_outtime.xlsx');
  }
}
