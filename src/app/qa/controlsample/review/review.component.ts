import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css'],
})
export class ReviewComponent implements OnInit {
  isView = false;
  results;
  fromdate;
  todate;

  selectedResult = [];
  constructor(private service: DataAccessService, private router: Router) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getReviewsLog();
    this.get_rights();
  }

  getReviewsLog() {
    this.service
      .get('qa/controlsample.php?type=getReviewsLog')
      .subscribe((response) => {
        this.results = response;
      });
  }

  close() {
    this.router.navigate(['/controlsample']);
  }

  getprint() {
    this.service.open(
      'pdf1/controlsample.php?type=controlsamplelog&fromdate=' +
        this.fromdate +
        '&todate=' +
        this.todate
    );
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
