import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'master-specification-raw-log', title: 'New Specification', route: 'master/specification/raw/log', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'qc-specifications-master-checklist', title: 'Checklist', route: 'qc/specifications/master/checklist', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'qc-specifications-checking', title: 'Specif. Checking', route: 'qc/specifications/checking', icon: 'fa-search', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'master-specification-raw-approval', title: 'Specif. For Apprpval', route: 'master/specification/raw/approval', icon: 'fa-edit', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'master-specification-raw-log', title: 'RM Specification Log', route: 'master/specification/raw/log', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'master-specification-raw-pm-report', title: 'PM Specification Log', route: 'master/specification/raw/pm-report', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'master-specification-raw-fgreport', title: 'FG Specif. Report', route: 'master/specification/raw/fgreport', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'inprocess', title: 'Inprocess', route: 'inprocess', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'raw', title: 'New Specifications', route: 'raw', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'qc-specifications-checking', title: 'Specification Checking', route: 'qc/specifications/checking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'raw-approval', title: 'Specification For Approval', route: 'raw/approval', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'revision-periodic', title: 'Implementation & Training', route: 'revision/periodic', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'raw-log', title: 'RM Specification Log', route: 'raw/log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'raw-pm-report', title: 'PM Specification Log', route: 'raw/pm-report', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'raw-fgreport', title: 'FG Specification Report', route: 'raw/fgreport', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'revision', title: 'Revision Of Specification', route: 'revision', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'new', title: 'Obsolete Specification Log', route: 'new', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];

  constructor(private service: DataAccessService) {  this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    let software_type = this.service.getPlantConfigFields('software_type');
    this.get_rights();
    if (software_type == 'Pharma ERP') {
      document.getElementById('Implementation').hidden = true;
      document.getElementById('Revision').hidden = true;
      document.getElementById('Absolute').hidden = true;
    }
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
