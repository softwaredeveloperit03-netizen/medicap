import { Component } from '@angular/core';
import {
  buildSidebarTabsFromCards,
  QC_CARD_GRADIENTS,
} from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.constants';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = QC_CARD_GRADIENTS;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    {
      id: 'receivepofo',
      title: 'Receive FO/PO',
      route: '/planning/Receivepofo',
      icon: 'fa-inbox',
      category: 'Planning Sections',
      gradient: G.teal,
    },
    {
      id: 'generatewo',
      title: 'Generate Work Order',
      route: '/planning/Generatewo',
      icon: 'fa-file-alt',
      category: 'Planning Sections',
      gradient: G.blue,
    },
    {
      id: 'shortages',
      title: 'Shortage Analysis',
      route: '/planning/Shortages',
      icon: 'fa-chart-bar',
      category: 'Planning Sections',
      gradient: G.amber,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
