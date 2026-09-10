import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'lmr-shortage-calculation', title: 'Shortages Calculation', route: 'lmr/shortage-calculation', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'plan', title: 'Lot Planning', route: 'plan', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'batch', title: 'Lot Processing (Mfg.)', route: 'batch', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'sampling', title: 'Sampling', route: 'sampling', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'batch-yield', title: 'Yield Reconcillation', route: 'batch/yield', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'batch-completed', title: 'Production Report', route: 'batch/completed', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'transfer', title: 'Transfer Material', route: 'transfer', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'production-rejection', title: 'Rejection', route: 'production/rejection', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit() {
  }

}
