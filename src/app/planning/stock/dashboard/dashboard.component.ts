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
      id: 'raw',
      title: 'Raw Material',
      route: 'raw',
      icon: 'fa-flask',
      category: 'Stock Book',
      gradient: G.teal,
    },
    {
      id: 'packing',
      title: 'Packing Material',
      route: 'packing',
      icon: 'fa-box',
      category: 'Stock Book',
      gradient: G.blue,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
