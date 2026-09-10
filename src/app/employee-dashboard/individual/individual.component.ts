import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ClrLoadingState } from '@clr/angular';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx/xlsx.mjs';
declare let alertify;
@Component({
  selector: 'app-individual',
  templateUrl: './individual.component.html',
  styleUrls: ['./individual.component.css']
})
export class IndividualComponent implements OnInit {
  willDownload = false;
  employees;
  departments;
  department_name = '';
  results;
  from_date = '';
  to_date = '';
  is_edit = false;
  columns;
  emp_code ;
  employeeList
  selectResult = [];
  pay_type ='';
  validateBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;
  constructor(private service: DataAccessService, private router: Router) {
    this.emp_code = localStorage.getItem('emp_id');
    var d = new Date();
    let day = d.getDate();
    let m = d.getMonth();
    m = +m + 1;
    let mon = "";
    if (day > 0 && day < 10) {
      mon = "0" + day;
    }
    if (m > 0 && m < 10) {
      mon = "0" + m;
    }
    this.to_date = d.getFullYear() + "-" + mon + "-" + day;
    this.from_date = d.getFullYear() + "-" + mon + "-01";
  }
  emp_id='';
  ngOnInit() {
    this.emp_code = localStorage.getItem('loger_id');
    this.department_name = localStorage.getItem('department');



    var d = new Date();
    let day = d.getDate();
    let m = d.getMonth();
    m = +m + 1;
    let mon = "";
    if (day > 0 && day < 10) {
      mon = "0" + day;
    }
    if (m > 0 && m < 10) {
      mon = "0" + m;
    }
    this.to_date = d.getFullYear() + "-" + mon + "-" + day;
    this.from_date = d.getFullYear() + "-" + mon + "-01";
    // this.getEmployees();
    // this.service.observableDepartment.subscribe(response => {
    //   this.departments = response;
    // });

    this.getIndividualAttendace();
  }
  getDepartmentsEmployee(value) {
    this.service.get('hr/attendance.php?type=getDepartmentEmployees&department_name=' + value).subscribe(response => {
      this.employees = response;
    });
  }
  getEmployees() {
    this.service.get('hr/task.php?type=getEmployees').subscribe((response: any) => {
      this.employeeList = response;
    });
  }
  edit(i) {
    this.selectResult = this.results[i];
    this.is_edit = true;
  }
  del(i) {
    this.selectResult = this.results[i];
    if(confirm("Are you sure to delete "+name)) {
      let obj = {
        "id" : this.selectResult['att_id']
      }
      this.service.post('hr/attendance.php?type=deleteIndividualAttendance',JSON.stringify(obj)).subscribe(response => {
        if (response['status'] == 'success') {
          alertify.success('Records deleted successfully');
          this.getIndividualAttendace();
          
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
    }
  } 
  getIndividualAttendace() {


    
    this.validateBtnState = ClrLoadingState.LOADING;
    this.service.get('hr/attendance.php?type=getIndividualAttendace&department_name=' + this.department_name + '&emp_code=' + this.emp_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
      this.validateBtnState = ClrLoadingState.DEFAULT;
      let temp = this.results[0];
      this.columns = Object.keys(temp);
    });
  }
  saveAttendance(data){ 
    if (!data.valid) {
      alertify.error('All Fields are Mandatory');
      return;
    }
    let obj = {
      "id" : this.selectResult['att_id'],
      "in_time" : data.value['from_time'],
      "out_time" : data.value['to_time'],      
      "leave" : data.value['leave'],      
      "pay_type" : data.value['pay_type'],      
      "date" : this.selectResult['date'],
      "status" : this.selectResult['status']
    }
    this.service.post('hr/attendance.php?type=saveIndividualAttendance&empid='+this.emp_code+'&id=',JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Records saved successfully');
        this.getIndividualAttendace();
        data.reset();
        this.is_edit=false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
 


  exportToExcel(): void {
    const fileName = 'individual_attendance_data.xlsx';
    const header = ['Sr.', 'Date', 'Status', 'Shift', 'Shift Start', 'In Time', 'Shift End', 'Out Time', 'Hours Worked', 'Early', 'Late', 'OT Hours'];
    const data = [header, ...this.results.map((result, index) => [
      index + 1,
      result.date,
      result.status,
      result.shift,
      result.shift_start,
      result.intime,
      result.shift_end,
      result.outtime,
      result.wh,
      result.early_arrival,
      result.late_arrival,
      result.ot_hrs
    ])];
  
    const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(data);
    const wb: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Individual Attendance Data');
    XLSX.writeFile(wb, fileName);
  }
}
