import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  isMobile = false;
  attendences;
  employees;
  employee;
  departments;
  selectedCountry = '';
  department_name = '';
  from_date = '';
  to_date = '';
  data = [];
  department='';
  today='';
  constructor(private service: DataAccessService, private datepipe: DatePipe) {
    this.isMobile = this.service.isMobile;
    this.from_date = this.datepipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datepipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datepipe.transform(Date.now(), 'yyyy-MM-dd');
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getAttendanceLog();
    this.getEmployees();
    this.getDepartments();
    this.get_rights();
  }
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }

 clear(){
   this.from_date='';
   this.to_date='';
   this.department_name='';
 }
  getAttendanceLog() {
    this.service.get('security/attendance.php?type=getAttendanceLog&from_date='+ this.from_date +'&to_date='+ this.to_date +'&department_name='+ encodeURIComponent(this.department_name || '')).subscribe((response: any) => {
      this.attendences = Array.isArray(response) ? response : [];
     });
  }

  getEmployees() {
    this.service.get('security/attendance.php?type=getEmployees').subscribe(response => {
      this.employees = response;
    });
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  download() {
    if (!this.attendences?.length) {
      alertify.error('No records to download');
      return;
    }
    this.service.open(
      'security/attendance.php?type=downloadAttendance&from_date=' +
        (this.from_date || '') +
        '&to_date=' +
        (this.to_date || '') +
        '&department_name=' +
        encodeURIComponent(this.department_name || '')
    );
  }

  // filterData() {
  //   this.data = [];
  //   for (let i = 0; i < this.attendences.length; i++) {
  //     let employee= this.attendences[i];
  //     if (employee['department_name'].toUpperCase().includes(this.department_name.toUpperCase())) {
  //       this.data[this.data.length] = employee;
  //     }
  //   }
  // }
  AllRecord(){
    this.service.get('security/attendance.php?type=getAllAttendanceLog').subscribe((response : any) => {
      this.attendences = response;
    });
    this.department_name='';
  }
}
