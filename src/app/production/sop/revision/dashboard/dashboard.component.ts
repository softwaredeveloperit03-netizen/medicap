import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'sop-revision-request', title: 'SOP Revision Request', route: 'sop/revision/request', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'sop-revision-request-checking', title: 'SOP Revision Request Approval', route: 'sop/revision/request-checking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'sop-revision-changecontrol', title: 'Raise Change Control', route: 'sop/revision/changecontrol', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'sop-revision-edit', title: 'Edit SOP', route: 'sop/revision/edit', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'sop-revision-history', title: 'SOP Revision History', route: 'sop/revision/history', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'sop-revision-log', title: 'Requests Log', route: 'sop/revision/log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit() {
  }

}
