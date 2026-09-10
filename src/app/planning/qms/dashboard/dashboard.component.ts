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
      id: 'deviation',
      title: 'Deviation',
      route: 'deviation',
      icon: 'fa-exclamation-triangle',
      category: 'QMS',
      gradient: G.amber,
    },
    {
      id: 'change-control',
      title: 'Change Control',
      route: 'change-control',
      icon: 'fa-edit',
      category: 'QMS',
      gradient: G.blue,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
