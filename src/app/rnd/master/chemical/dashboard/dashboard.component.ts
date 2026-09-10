import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  reports;
  selectedReview = [];
  isView = false;
  grades;
  grade = '';
  constructor(private service: DataAccessService) { this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.get_rights();
    this.getChemiclLog();
    this.getGrades();
  }

  getChemiclLog() {
    this.service
      .get('qc/chemical.php?type=getChemicalsLog&grade=' + this.grade)
      .subscribe((response) => {
        this.reports = response;
      });
  }

  download() {
    this.service.open(
      'qc/chemical.php?type=downloadChemicalsLog&grade=' + this.grade
    );
  }

  viewReviews(index) {
    this.selectedReview = this.reports[index];
    this.isView = true;
  }
  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe((response) => {
      this.grades = response;
    });
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
