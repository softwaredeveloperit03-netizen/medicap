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
      icon: 'fa-box',
      category: 'Indent',
      gradient: G.teal,
    },
    {
      id: 'packing',
      title: 'Packing Material',
      route: 'packing',
      icon: 'fa-box-open',
      category: 'Indent',
      gradient: G.blue,
    },
    {
      id: 'general',
      title: 'General Material',
      route: 'general',
      icon: 'fa-archive',
      category: 'Indent',
      gradient: G.amber,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
