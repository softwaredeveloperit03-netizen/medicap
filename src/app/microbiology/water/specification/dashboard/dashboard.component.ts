import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  isView = false;
  results;
  tests;
  selectedSpecification = [];
  revision = [];

  constructor(private service: DataAccessService) { this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getSpecifications();
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
  getSpecifications() {
    this.service
      .get('qc/water.php?type=getSpecifications')
      .subscribe((response) => {
        this.results = response;
      });
  }
  downloadReport() {
    this.service.open('pdf1/water.php?type=specificationReport');
  }
  view(index) {
    this.selectedSpecification = this.results[index];
    //this.revision =JSON.parse(this.results[index]['revisions']);
    // console.log(JSON.parse(this.selectedSpecification['revisions']));
    this.isView = true;
  }

  downloadPDF(id, type) {
    if (type == 'manual') {
      this.service.open('pdf1/water.php?type=specification&id=' + id);
    } else {
      this.service.open('pdf1/water.php?type=specificationdigital&id=' + id);
    }
  }
}