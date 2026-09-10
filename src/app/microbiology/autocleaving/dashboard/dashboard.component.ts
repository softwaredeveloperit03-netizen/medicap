import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    {
      id: 'new',
      title: 'New Autoclave Cycle',
      route: 'new',
      icon: 'fa-plus-circle',
      category: 'Entry',
      gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    },
    {
      id: 'checking',
      title: 'Checking',
      route: 'checking',
      icon: 'fa-clipboard-check',
      category: 'Workflow',
      gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    },
    {
      id: 'approval',
      title: 'Approval',
      route: 'approval',
      icon: 'fa-stamp',
      category: 'Workflow',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    },
    {
      id: 'sterilization',
      title: 'Sterilization Log',
      route: 'sterilization',
      icon: 'fa-thermometer-half',
      category: 'Logs',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    },
    {
      id: 'discarding',
      title: 'Destruction Log',
      route: 'discarding',
      icon: 'fa-biohazard',
      category: 'Logs',
      gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
    },
  ];

  constructor() {}

  ngOnInit(): void {}
}
