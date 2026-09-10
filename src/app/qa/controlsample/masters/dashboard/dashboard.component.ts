import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'rack', title: 'Rack Master', route: 'rack', icon: 'fa-box-open', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'frequency', title: 'Frequency', route: 'frequency', icon: 'fa-clock', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'destruction', title: 'Destruction Timeline', route: 'destruction', icon: 'fa-calendar-times', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'destruction', title: 'Distruction Timeline', route: 'destruction', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
