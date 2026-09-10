import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'contract', title: 'Purchase Rate Contract', route: 'contract', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'log', title: 'Contract Log', route: 'log', icon: 'fa-list-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];


  constructor() { 
    console.log('PRC Dashboard Component Constructor Called');
  }

  ngOnInit(): void {
    console.log('PRC Dashboard Component ngOnInit Called');
  }

}
