import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'baltolarance', title: 'List of Balance in EHS', route: 'baltolarance', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'perfchkrecord', title: 'Weighing Bal Daily', route: 'perfchkrecord', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'monthlyverif', title: 'Monthly Balance', route: 'monthlyverif', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'eccentricitychk', title: 'Repeatability and', route: 'eccentricitychk', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'stampreqform', title: 'Weighing Balances', route: 'stampreqform', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'caliblabel', title: 'Calibration Label', route: 'caliblabel', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'dailyverif', title: 'Daily Verif', route: 'dailyverif', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
