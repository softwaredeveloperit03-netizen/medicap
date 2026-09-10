import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    {
      id: 'master',
      title: 'Culture Master',
      route: 'master',
      icon: 'fa-globe',
      category: 'Master',
      gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    },
    {
      id: 'maint-entry',
      title: 'Maintenance Entry',
      route: 'maintenance',
      icon: 'fa-tools',
      category: 'Maintenance Flow',
      gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    },
    {
      id: 'maint-checking',
      title: 'Maintenance Checking',
      route: 'maintenance/checking',
      icon: 'fa-clipboard-check',
      category: 'Maintenance Flow',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    },
    {
      id: 'maint-approval',
      title: 'Maintenance Approval',
      route: 'maintenance/approval',
      icon: 'fa-stamp',
      category: 'Maintenance Flow',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    },
    {
      id: 'maint-log',
      title: 'Maintenance Log',
      route: 'maintenance/log',
      icon: 'fa-book',
      category: 'Maintenance Flow',
      gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
    },
    {
      id: 'id-entry',
      title: 'Identification Entry',
      route: 'identification',
      icon: 'fa-id-card',
      category: 'Identification Flow',
      gradient: 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)',
    },
    {
      id: 'id-checking',
      title: 'Identification Checking',
      route: 'identification/checking',
      icon: 'fa-clipboard-check',
      category: 'Identification Flow',
      gradient: 'linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%)',
    },
    {
      id: 'id-approval',
      title: 'Identification Approval',
      route: 'identification/approval',
      icon: 'fa-stamp',
      category: 'Identification Flow',
      gradient: 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
    },
    {
      id: 'id-log',
      title: 'Identification Log',
      route: 'identification/log',
      icon: 'fa-book',
      category: 'Identification Flow',
      gradient: 'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)',
    },
  ];

  constructor() {}

  ngOnInit() {}
}
