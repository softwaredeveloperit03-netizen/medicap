import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashbaord',
  templateUrl: './dashbaord.component.html',
  styleUrls: ['./dashbaord.component.css'],
})
export class DashbaordComponent implements OnInit {
  isView = false;
  specifications;
  loading = false;

  selectedSpec = [];

  water_type = '';
  status = '';

  constructor(private service: DataAccessService) {  this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.getSpecificationsLog();
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

  getSpecificationsLog() {
    this.loading = true;
    this.service
      .get(
        'qc/specification/water.php?type=getSpecificationsLog&water_type=' +
          this.water_type +
          '&status=' +
          this.status
      )
      .subscribe((response) => {
        this.specifications = Array.isArray(response) ? response : [];
        this.loading = false;
      }, () => {
        this.specifications = [];
        this.loading = false;
      });
  }

  clear() {
    this.water_type = '';
    this.status = '';
    this.getSpecificationsLog();
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }

  isExistingSpec(): boolean {
    const sup = String(this.selectedSpec?.['supersede_no'] || '').trim().toUpperCase();
    return sup !== '' && sup !== 'NA' && sup !== 'N/A';
  }

  download() {
    this.service.open(
      'qc/specification/water.php?type=downloadSpecificationsLog&water_type=' +
        this.water_type
    );
  }

  downloadRecord() {
    this.service.open(
      'qc/specification/water.php?type=downloadSpecificationsRecord&id=' +
        this.selectedSpec['id']
    );
  }

  displayStatus(status: string): string {
    const key = String(status || '').toLowerCase();
    if (key === '' || key === 'pending' || key === 'checking') {
      return 'Checking';
    }
    if (key === 'pending_approval' || key === 'checked') {
      return 'Pending Approval';
    }
    if (key === 'approved' || key === 'approve') {
      return 'Approved';
    }
    if (key === 'rejected' || key === 'reject') {
      return 'Rejected';
    }
    return status || '-';
  }
}

