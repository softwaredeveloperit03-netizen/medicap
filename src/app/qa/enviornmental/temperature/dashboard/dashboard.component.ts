import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  results;

  isUser = false;
  isChecker = false;
  isApprover = false;

  department = '';
  section_name = '';

  departments;
  sections;
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
    // this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    // this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    // this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }

  ngOnInit(): void {
    this.getTemperaturesLog();
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

  getTemperaturesLog() {
    this.service
      .get(
        'qa/temperature.php?type=getTemperaturesLog&department_name=' +
          this.department +
          '&section=' +
          this.section_name
      )
      .subscribe((response) => {
        this.results = response;
      });
  }

  getDepartment() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }

  getSections(index) {
    index = index - 1;
    if (index !== -1) {
      let department = this.departments[index];
      this.sections = department['sections'];
    }
  }
}
