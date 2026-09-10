import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'reqcollection', title: 'Factory Order/Forcast From Marketing', route: 'ReqCollection', icon: 'fa-industry', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'prepare', title: 'WorkOrder Processing', route: 'prepare', icon: 'fa-industry', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'clientwise', title: 'reqirement From Marketing', route: 'clientwise', icon: 'fa-user-tag', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'forder', title: 'Shortage Analysis', route: 'fOrder', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'req-analysis', title: 'Requirement Analysis', route: 'req_analysis', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'woplan', title: 'Workorders', route: 'woplan', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'log', title: 'Complete A. Purchase Log', route: 'log', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
