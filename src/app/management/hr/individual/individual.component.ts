import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from '@angular/common'
import { Router } from '@angular/router';
@Component({
  selector: 'app-individual',
  templateUrl: './individual.component.html',
  styleUrls: ['./individual.component.css'],
  providers: [DatePipe],
})
export class IndividualComponent implements OnInit {
  employees;
  departments;
  results;
  from_date = '';
  to_date = '';
  columns;
  emp_code = '';
  today = '';
  loading;
  validateBtnState;
  department_name = 'Production';
  month = '';
  columns1;

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe
  ) {
    var d = new Date();
    let day = d.getDate();
    let m = d.getMonth();
    m = +m + 1;
    let mon = '';
    if (day > 0 && day < 10) {
      mon = '0' + day;
    }
    if (m > 0 && m < 10) {
      mon = '0' + m;
    }
    this.to_date = d.getFullYear() + '-' + mon + '-' + day;
    this.from_date = d.getFullYear() + '-' + mon + '-01';
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getDepartments();
    this.getIndividualAttendace();
    this.onSearch();
    this.getEmployee(this.department_name);
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }
  getEmployee(serch) {
    this.service
      .get(
        'hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' + serch
      )
      .subscribe((response) => {
        this.employees = response;
      });
  }
  getDepartmentsEmployee(value) {
    this.service
      .get(
        'hr/attendance.php?type=getDepartmentEmployees&department_name=' + value
      )
      .subscribe((response) => {
        this.employees = response;
      });
  }

  // getIndividualAttendace(){
  //   this.service.get('hr/attendance.php?type=getIndividualAttendace&department_name=' + this.department_name + '&emp_code='+ this.emp_code + '&from_date=' + this.from_date + '&to_date=' +this.to_date).subscribe(response=>{
  //     this.results=response;

  //     let temp = this.results[0];
  //     this.columns = Object.keys(temp);
  //   });
  // }

  getIndividualAttendace() {
    //  this.validateBtnState = ClrLoadingState.LOADING;
    this.service
      .get(
        'hr/attendance.php?type=getIndividualAttendace&department_name=' +
          this.department_name +
          '&emp_code=' +
          this.emp_code +
          '&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results = response;
        // this.validateBtnState = ClrLoadingState.DEFAULT;
        let temp = this.results[0];
        this.columns = Object.keys(temp);
      });
  }

  start_date = '';
  end_date = '';
  employeeId = '';

  onSearch() {
    this.service.get(
      'hr/attendance.php?type=getMonthlyAttendance1&department_name=' +
        this.department_name + '&month=' +this.month+'&employeeId=' +this.employeeId +
        '&start_date=' +this.start_date +'&end_date=' +this.end_date
    ).subscribe((data: any[]) => {
      // Ensure data is an array of objects
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
  }

  downloadReport() {
    this.service.open('hr/attendance.php?type=downloadIndividualAttendace');
  }
  // -------------------------------------------------------------------------------------------
  //    employees;
  //   departments;
  //   department_name = 'Production';
  //   results;
  //   month = '';
  //   columns;
  //   columns1;

  //   constructor(
  //     private service: DataAccessService,
  //     private router: Router,
  //     private http: HttpClient
  //   ) {
  //     var d = new Date();
  //     let m = d.getMonth();
  //     m = +m + 1;
  //     let mon = '';
  //     if (m > 0 && m < 10) {
  //       mon = '0' + m;
  //     }
  //     this.month = d.getFullYear() + '-' + mon;
  //   }

  //   ngOnInit() {
  //     this.service.observableDepartment.subscribe((response) => {
  //       this.departments = response;
  //     });
  //     this.onSearch();
  //     this.getEmployee(this.department_name);
  //   }

  //   close() {
  //     this.router.navigate(['/hr/dashboard']);
  //   }

  //   start_date='';
  //   end_date='';
  //   employeeId='';

  // onSearch() {
  //   this.service.get(
  //     'hr/attendance.php?type=getMonthlyAttendance1&department_name=' +
  //       this.department_name + '&month=' +this.month+'&employeeId=' +this.employeeId +
  //       '&start_date=' +this.start_date +'&end_date=' +this.end_date
  //   ).subscribe((data: any[]) => {
  //     // Ensure data is an array of objects
  //     if (!Array.isArray(data)) {
  //       return;
  //     }
  //     // Extract all unique keys from the data array
  //     const allKeysSet = new Set<string>();
  //     data.forEach(obj => Object.keys(obj).forEach(key => allKeysSet.add(key)));
  //     const allKeys = Array.from(allKeysSet);
  //     // Filter out the date keys
  //     const dateKeys = allKeys.filter(key => key.match(/^\d{4}-\d{2}-\d{2}$/));
  //     // Filter out the date keys to exclude time-related fields
  //     const columns1 = allKeys.filter(key => !dateKeys.includes(key));
  //     // Set the columns1 and results arrays for use in the template
  //     this.columns1 = allKeys;
  //     this.results = data;
  //   });
  // }

  //   getEmployee(serch) {
  //     this.service.get('hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' +serch ).
  //     subscribe((response) => {
  //         this.employees  = response;
  //       });
  //   }
  //   emp_id='';

  //   cleanColumnHeader(header: string): string {
  //     return header.replace(/^\d{1,2}_/, '');
  //   }

  //   downloadReport() {
  //     this.service.open(
  //       'hr/attendance.php?type=downloadAttendance&department_name=' +
  //         this.department_name +
  //         '&month=' +
  //         this.month
  //     );
  //   }

  //   AllRecord() {
  //     var d = new Date();
  //     let m = d.getMonth();
  //     m = +m + 1;
  //     let mon = '';
  //     if (m > 0 && m < 10) {
  //       mon = '0' + m;
  //     }
  //     this.month = d.getFullYear() + '-' + mon;

  //     this.department_name = '';
  //     //this.searchEmployee('Month');
  //   }

  //   exportToExcel(): void {
  //     const ws: XLSX.WorkSheet = XLSX.utils.table_to_sheet(
  //       document.getElementById('dataTable')
  //     );
  //     const wb: XLSX.WorkBook = XLSX.utils.book_new();
  //     XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
  //     XLSX.writeFile(wb, 'attendance.xlsx');
  //   }
  //   exportToExcelIntimeOuttime() {
  //     // Extract only the columns needed for export (excluding "work_hrs")
  //   // Extract only the columns needed for export (excluding "work_hrs")
  //   const exportData = this.results.map(result => {
  //     const exportResult = {};
  //     for (const col of this.columns) {
  //       if (col !== 'work_hrs') {
  //         exportResult[col] = result[col];
  //       }
  //     }
  //     return exportResult;
  //   });

  //   const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(exportData, {
  //     header: this.columns1.map(col => this.cleanColumnHeader(col)) // Clean the column headers
  //   });

  //   const workbook: XLSX.WorkBook = {
  //     Sheets: { 'Data': worksheet },
  //     SheetNames: ['Data']
  //   };
  //     // Save the Excel file
  //     XLSX.writeFile(workbook, 'attendace_with_intime_outtime.xlsx');
  //   }
  // }
}
