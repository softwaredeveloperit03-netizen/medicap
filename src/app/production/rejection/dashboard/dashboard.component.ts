import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  closeLink = '/fproduction';

  cards: QcDeptCard[] = [
    {
      id: 'onlinerejection',
      title: 'Online Rejection',
      route: '../onlinerejection',
      icon: 'fa-times-circle',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    },
    {
      id: 'onlinerejection-approval',
      title: 'Online Rejection Approval',
      route: '../onlinerejection-approval',
      icon: 'fa-check-circle',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)',
    },
    {
      id: 'destruction',
      title: 'Rejection For Destruction',
      route: '../destruction',
      icon: 'fa-trash',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)',
    },
    {
      id: 'destruction-report',
      title: 'Destruction Report',
      route: '../destruction-report',
      icon: 'fa-file-alt',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)',
    },
    {
      id: 'onlinereport',
      title: 'Online Rejection LogBook',
      route: '../onlinereport',
      icon: 'fa-book',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)',
    },
  ];

  constructor(private router: Router) {}

  ngOnInit(): void {
    const url = this.router.url || '';
    this.closeLink = url.includes('prod-f-ebmr') ? '/fproduction' : '/production';
  }
}
