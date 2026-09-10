import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe],
})
export class DashboardComponent implements OnInit {
  results;
  to_date = '';
  from_date = '';
  departments;
  department_name = '';
  selectedResult = [];
  isView = false;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');  this.loggedInDept = localStorage.getItem('department');
  }
  ngOnInit() {
    this.getDeptIndendsLog();
    this.getDepartment();
    this.get_rights();
  }
  // -----------------------------------------12th july------------------------------------------//

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

  get_rights() {
   this.service
     .get(
       'hr/employee.php?type=getrights&emp_id=' +
         localStorage.getItem('emp_id') +
         '&dep_name=' +
         localStorage.getItem('department')
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
  //---------------------------------------------------------------------------------//

  getDepartment() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }

  getDeptIndendsLog() {
    this.service
      .get(
        'purchase/indend/general.php?type=getPurchaseIndendsLog&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date +
          '&department_name=' +
          this.department_name
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  download() {
    this.service.open(
      'purchase/indend/general.php?type=downloadPurchaseIndendsLog&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date +
        '&department_name=' +
        this.department_name
    );
  }
}
