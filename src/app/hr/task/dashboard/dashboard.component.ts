import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'new', title: 'Assign Task', route: 'new', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'todays', title: 'Todays Assig. Tasks', route: 'todays', icon: 'fa-calendar-day', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'check', title: 'Tasks Check', route: 'check', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'log', title: 'Tasks Report', route: 'log', icon: 'fa-clipboard-list', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'todays', title: 'Todays Assinged Tasks', route: 'todays', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'check', title: 'Tasks check', route: 'check', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit() {
  }

}
