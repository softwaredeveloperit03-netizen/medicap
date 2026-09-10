import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'approval', title: 'Pre-Approval', route: 'approval', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'preliminary', title: 'Preliminary Assessment', route: 'preliminary', icon: 'fa-search', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'ebmapproval', title: 'EBM Approval', route: 'ebmapproval', icon: 'fa-check-double', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'preliminary', title: 'Preliminary Assement', route: 'preliminary', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  trainig_cordinator = 'No';
  rights;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.get_rights();
  }
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' +localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department')).subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
        this.trainig_cordinator = this.rights[0].trainig_cordinator;
      });
  }
}
