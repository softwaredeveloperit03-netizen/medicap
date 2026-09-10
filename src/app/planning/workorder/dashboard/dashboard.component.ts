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
      id: 'receive',
      title: 'Factory Order',
      route: 'receive',
      icon: 'fa-industry',
      category: 'Factory Order',
      gradient: G.teal,
    },
    {
      id: 'new',
      title: 'Requirement Analysis',
      route: 'new',
      icon: 'fa-chart-line',
      category: 'Factory Order',
      gradient: G.blue,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
