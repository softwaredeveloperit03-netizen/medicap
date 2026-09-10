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
  loading = false;
  results: any[] = [];
  vendors;
  selectedResult: any = {};
  vendor_no = '';
  from_date = '';
  to_date = '';
  status = 'approve';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01') || '';
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
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

  getChallansLog() {
    this.loading = true;
    const vendor = this.vendor_no || '';
    const from = this.from_date || '';
    const to = this.to_date || '';
    this.service
      .getJsonArray(
        'qc/standard/receiving.php?type=getChallansLog&vendor_no=' +
          encodeURIComponent(vendor) +
          '&to_date=' +
          encodeURIComponent(to) +
          '&from_date=' +
          encodeURIComponent(from)
      )
      .subscribe({
        next: (response: any[]) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.loading = false;
        },
      });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe((response) => {
      this.vendors = response;
    });
  }

  view(result: any) {
    this.selectedResult = result || {};
    this.isView = true;
  }

  downloadLog() {
    this.download();
  }
  download() {
    const vendor = this.vendor_no || '';
    const from = this.from_date || '';
    const to = this.to_date || '';
    this.service.open(
      'qc/standard/receiving.php?type=downloadChallanLog&vendor_no=' +
        encodeURIComponent(vendor) +
        '&to_date=' +
        encodeURIComponent(to) +
        '&from_date=' +
        encodeURIComponent(from)
    );
  }
}
