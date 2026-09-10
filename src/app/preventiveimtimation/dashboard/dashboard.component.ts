import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'preventive', title: 'Preventive Intimation', route: 'preventive', icon: 'fa-hard-hat', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'inspection', title: 'Inspection Intimation', route: 'inspection', icon: 'fa-search', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];


  constructor() { }

  department='';
  ngOnInit(): void {
    this.department = localStorage.getItem('department');
  }

}
