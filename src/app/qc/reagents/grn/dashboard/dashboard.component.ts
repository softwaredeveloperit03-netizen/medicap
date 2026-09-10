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
  from_date = '';
  to_date = '';
  isView = false;
  isView1 = false;
  results;
  grndetails = [];
  selectedReport = [];
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getGRNLog();
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

  getGRNLog() {
    this.service
      .get(
        'qc/indicator.php?type=getGRNLog&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.grndetails = this.selectedReport['grn_details'];
    this.isView = true;
    this.isView1 = true;
  }

  download() {
    this.service.open(
      'qc/indicator.php?type=GRNPDF&id=' + this.selectedReport['id']
    );
  }

  downloadLog() {
    this.service.open(
      'qc/indicator.php?type=GRNLogPDF&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }
}
