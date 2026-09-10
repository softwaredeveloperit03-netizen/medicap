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
  from_date = '';
  to_date = '';
  isview = false;
  selectedResult = [];
  company_unit = '';
  units;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
     this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getTechnicalLog();
    this.getUnits();
    this.get_rights();
  }

  getUnits() {
    this.service
      .get('common.php?type=getCompanyUnits')
      .subscribe((response) => {
        this.units = response;
      });
  }

  getTechnicalLog() {
    this.service
      .get(
        'production/technical.php?type=getTechnicalLog&company_unit=' +
          this.company_unit +
          '&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isview = true;
  }

  downloadLog() {
    this.service.open(
      'production/technical.php?type=downloadTechnicalLog&company_unit=' +
        this.company_unit +
        '&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }

  downloadRecord() {
    this.service.open(
      'production/technical.php?type=downloadTechnicalRecord&id=' +
        this.selectedResult['id']
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
