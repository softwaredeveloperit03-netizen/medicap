import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  results;
  isView = false;
  selectedResult = [];
  selectedReport = [];
  selectedReport2 = [];
  isShow = false;
  hide = false;

  constructor(private service: DataAccessService) { this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getDevTrials();
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

  getDevTrials() {
    this.service
      .get('rnd/optimisation.php?type=getOptimisationsLog')
      .subscribe((response) => {
        this.results = response;
      });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.selectedReport = this.selectedResult['trials'];
    this.isView = true;
  }
  view1(index) {
    this.selectedReport2 = this.selectedReport[index];
    this.isShow = true;
    this.isView = false;
  }
}
