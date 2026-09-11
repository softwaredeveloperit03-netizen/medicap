import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    {
      id: 'configuration',
      title: 'Line Configuration',
      route: 'Configuration',
      icon: 'fa-cogs',
      category: 'Line Master',
      gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    },
    {
      id: 'checking',
      title: 'Line Checking',
      route: 'Checking',
      icon: 'fa-clipboard-check',
      category: 'Line Master',
      gradient: 'linear-gradient(135deg, #f6d365 0%, #fda085 100%)',
    },
    {
      id: 'approval',
      title: 'Line Approval',
      route: 'Approval',
      icon: 'fa-check-circle',
      category: 'Line Master',
      gradient: 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)',
    },
    {
      id: 'log',
      title: 'Line Configuration Log',
      route: 'Log',
      icon: 'fa-list',
      category: 'Line Master',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    },
    {
      id: 'map',
      title: 'Map Product',
      route: 'Map',
      icon: 'fa-link',
      category: 'Line Master',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    },
  ];

  constructor() {}

  ngOnInit(): void {}
}
