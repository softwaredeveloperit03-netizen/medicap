import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'frequency', title: 'Frequency of Equipment PM', route: 'frequency', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'preventive', title: 'Preventive Maintenance', route: 'preventive', icon: 'fa-tools', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'inspection', title: 'Inspection of Equipments', route: 'inspection', icon: 'fa-search', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'history', title: 'History Card', route: 'history', icon: 'fa-history', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    
  ];


  constructor() { }

  ngOnInit() {
  }

}
