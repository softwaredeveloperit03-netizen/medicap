import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashbord',
  templateUrl: './dashbord.component.html',
  styleUrls: ['./dashbord.component.css'],
})
export class DashbordComponent implements OnInit {
  isView = false;
  results;
  vendors;
  selectedResult = [];
  vendor_no = '';
  from_date = '';
  to_date = '';
  status = 'approve';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getChallansLog();
    this.getVendors();
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

  getChallansLog() {
    this.service
      .get(
        'qc/indicator.php?type=getChallansLog&vendor_no=' +
          this.vendor_no +
          '&to_date=' +
          this.to_date +
          '&from_date=' +
          this.from_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe((response) => {
      this.vendors = response;
    });
  }
  viewf() {}

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  downloadLog() {
    this.service.open('pdf1/store.php?type=challanLog');
  }
  download() {
    this.service.open(
      'qc/reagents.php?type=downloadChallanLog&vendor_no=' +
        this.vendor_no +
        '&to_date=' +
        this.to_date +
        '&from_date=' +
        this.from_date
    );
  }
}
