import { Component } from '@angular/core';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

@Component({
  selector: 'app-bill-of-material-dashboard',
  templateUrl: './dashboard.component.html',
})
export class BillOfMaterialDashboardComponent {
  readonly cards: QcDeptCard[] = [
    {
      id: 'prepare',
      title: 'Prepare Batch Formula',
      route: '/master/bill-of-material/prepare',
      icon: 'fa-edit',
      category: 'Prepare Batch Formula',
      gradient: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
    },
    {
      id: 'review-bom',
      title: 'Review Batch Formula',
      route: '/unitformula/bfrreview',
      icon: 'fa-search',
      category: 'Prepare Batch Formula',
      gradient: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
    },
    {
      id: 'approve-bom',
      title: 'Approve Batch Formula',
      route: '/unitformula/bfrapproval',
      icon: 'fa-check-circle',
      category: 'Prepare Batch Formula',
      gradient: 'linear-gradient(135deg, #b45309 0%, #d97706 100%)',
    },
    {
      id: 'bom-log',
      title: 'Batch Formula Log',
      route: '/unitformula/bfrlog',
      icon: 'fa-list',
      category: 'Prepare Batch Formula',
      gradient: 'linear-gradient(135deg, #3730a3 0%, #6366f1 100%)',
    },
  ];
}
