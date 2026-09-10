import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'qualifi', title: 'Equipment For Qualif.', route: 'qualifi', icon: 'fa-microscope', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'urs', title: 'User Requirem Spec.', route: 'urs', icon: 'fa-clipboard-list', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'dq', title: 'Design Qualif.', route: 'dq', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'factory', title: 'Factory Acceptance', route: 'factory', icon: 'fa-warehouse', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'site', title: 'Site Acceptance', route: 'site', icon: 'fa-building', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'qualification-request', title: 'Qualif. Request (IQ)', route: 'qualification-request', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'iq', title: 'Installation Qualif.', route: 'iq', icon: 'fa-tools', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'oq', title: 'Operational Qualif.', route: 'oq', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'pq', title: 'Performance Qualif.', route: 'pq', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 're', title: 'Requalification', route: 're', icon: 'fa-sync-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'qr', title: 'Qualif. Records', route: 'qr', icon: 'fa-file-contract', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'equipment-vendor', title: 'Equipment Vendor', route: 'equipment-vendor', icon: 'fa-truck', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'qualifi', title: 'Equipment For Qualification', route: 'qualifi', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'urs', title: 'User Requirement Spec.', route: 'urs', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'dq', title: 'Design Qualification', route: 'dq', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'qualification-request', title: 'Qualification Request (IQ)', route: 'qualification-request', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'iq', title: 'Installation Qualification', route: 'iq', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'oq', title: 'Operational Qualification', route: 'oq', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'pq', title: 'Performance Qualification', route: 'pq', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'qr', title: 'Qualification Records', route: 'qr', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
  ];

  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
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
}
