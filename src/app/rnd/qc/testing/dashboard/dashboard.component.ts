import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'raw', title: 'Raw Material', route: 'raw', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'packing', title: 'Packing Material', route: 'packing', icon: 'fa-box', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'inprocess', title: 'Inprocess Material', route: 'inprocess', icon: 'fa-cog', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'stability', title: 'Stability Testing', route: 'stability', icon: 'fa-hourglass-half', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];

  
  constructor() {
    
  }

  ngOnInit() {
  }

}
