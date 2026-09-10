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
  isView = false;
  results;
  departments;
  department_name = '';
  from_date = '';
  to_date = '';

  selectedResult = [];
  constructor(private service: DataAccessService, private datePip: DatePipe) {
    this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePip.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePip.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getMaintenanceHistory();
    this.getDepartments();
    this.getMaintenanceDetails();
    this.get_rights();
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }

  getMaintenanceHistory() {
    this.service
      .get(
        'engineering/maintenance.php?type=getMaintenanceHistory&department_name=' +
          this.department_name +
          '&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  getMaintenanceDetails() {
    this.service
      .get(
        'engineering/maintenance.php?type=getMaintenanceDetails&maintenance_no=' +
          this.selectedResult['maintenance_no']
      )
      .subscribe((response: any) => {
        this.selectedResult = response;
      });
  }
  viewfile(link) {
    window.open(this.service.url + 'upload/maintenance/' + link);
  }

  download() {
    this.service.open(
      'engineering/maintenance.php?type=downloadMaintenanceHistory&department_name=' +
        this.department_name +
        '&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
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
          this.loggedInDept
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
}
