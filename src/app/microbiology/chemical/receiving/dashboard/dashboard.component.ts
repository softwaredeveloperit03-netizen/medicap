import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  isView = false;
  results;
  vendor_no = '';
  material_type = '';
  status = '';
  selectedReport = [];
  vendors;
  constructor(private service: DataAccessService) {  this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getReceivingLog();
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

  getReceivingLog() {
    this.service
      .get('qc/chemical.php?type=getReceivingLog&vendor_no=' + this.vendor_no)
      .subscribe((response) => {
        this.results = response;
      });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  viewfile(url) {
    url = this.service.url + 'upload/coa/' + url;
    window.open(url, '_blank');
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe((response) => {
      this.vendors = response;
    });
  }
  downloadPDF(sign) {
    this.service.open(
      'qc/chemical.php?type=receivingMaterialPDF&pdfsign=' +
        sign +
        '&id=' +
        this.selectedReport['id']
    );
  }

  downloadLog() {
    this.service.open(
      'qc/chemical.php?type=receivingMaterialLogPDF&vendor_no=' +
        this.vendor_no +
        '&material_type=' +
        this.material_type
    );
  }
}
