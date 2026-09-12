import { Component } from '@angular/core';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { ecvHomePath } from '../ecv.utils';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  homePath = ecvHomePath();

  cards: QcDeptCard[] = [
    {
      id: 'sample-new',
      title: 'Sample Collection New',
      searchText: 'FQA-002-01-A Equipment Cleaning Verification Sample Collection New',
      route: 'new',
      icon: 'fa-file-alt',
      category: 'Sample Collection',
      gradient: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
    },
    {
      id: 'sample-log',
      title: 'Sample Collection Log',
      searchText: 'FQA-002-01-A Equipment Cleaning Verification Sample Collection Log',
      route: 'log',
      icon: 'fa-book',
      category: 'Sample Collection',
      gradient: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
    },
    {
      id: 'approval-new',
      title: 'Cleaning Approval New',
      searchText: 'WI-QA-002-02 Approval of Equipment Cleaning Before Start of Production New',
      route: 'approval/new',
      icon: 'fa-clipboard-check',
      category: 'Production Approval',
      gradient: 'linear-gradient(135deg, #334155 0%, #475569 100%)',
    },
    {
      id: 'approval-log',
      title: 'Cleaning Approval Log',
      searchText: 'WI-QA-002-02 Approval of Equipment Cleaning Before Start of Production Log',
      route: 'approval/log',
      icon: 'fa-clipboard-list',
      category: 'Production Approval',
      gradient: 'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
    },
  ];
}
