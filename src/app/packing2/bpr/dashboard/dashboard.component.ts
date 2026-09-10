import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    {
      id: 'ebpr-batches',
      title: 'eBPR Batch Execution',
      route: 'start',
      icon: 'fa-boxes-packing',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #0d9488 0%, #115e59 100%)',
    },
    {
      id: 'start-packing',
      title: 'Start Packing (Legacy)',
      route: 'start-packing',
      icon: 'fa-play-circle',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    },
    {
      id: 'inprocess',
      title: 'Under Packing Batches',
      route: 'inprocess',
      icon: 'fa-industry',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    },
    {
      id: 'completed',
      title: 'Completed Batches',
      route: 'completed',
      icon: 'fa-check-double',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    },
    {
      id: 'reports',
      title: 'Batch Packing Reports',
      route: 'reports',
      icon: 'fa-file-invoice',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%)',
    },
    {
      id: 'checklist',
      title: 'BMR / Packing Checklist',
      route: '/packing/bmr',
      icon: 'fa-clipboard-check',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    },
    {
      id: 'inproChecking',
      title: 'Completed Batches (Checking)',
      route: 'inproChecking',
      icon: 'fa-cogs',
      category: 'Legacy',
      gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    },
  ];

  constructor() {}

  ngOnInit() {}
}
