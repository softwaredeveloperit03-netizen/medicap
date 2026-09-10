import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'dispensing-allocation', title: 'Awaiting Dispensing Requests', route: 'dispensing/allocation', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'dispensing-dispense', title: 'Dispensing Form', route: 'dispensing/dispense', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'dispensing-clearance-approval', title: 'Line Clearance Approval', route: 'dispensing/clearance-approval', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'dispensing-checking', title: 'Dispensing for Checking', route: 'dispensing/checking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'dispensing-approval', title: 'Dispensing for Approval', route: 'dispensing/approval', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'dispensing-receive', title: 'Receive Dispensing Masterial', route: 'dispensing/receive', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'dispensing-log', title: 'Dispensing Log', route: 'dispensing/log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];


  constructor(public service: DataAccessService) { }

  ngOnInit() {
  }

}
