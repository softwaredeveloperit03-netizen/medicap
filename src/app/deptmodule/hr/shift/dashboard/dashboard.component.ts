import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'hr-master-shift', title: 'Shift Master', route: '/hr/master/shift', icon: 'fa-clock', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'allocation', title: 'Shift Initiator', route: 'allocation', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'schedule', title: 'Schedule', route: 'schedule', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'plant-head-hr-shift-request', title: 'Shift Change Request', route: 'plant_head/hr/shift/request', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'request', title: 'Shift Change Request', route: 'request', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'seeoff', title: 'See Off', route: 'seeoff', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit() {
  }

}
