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
      id: 'new',
      title: 'New Deviation',
      route: 'new',
      icon: 'fa-exclamation-triangle',
      category: 'Deviation',
      gradient: G.amber,
    },
    {
      id: 'review',
      title: 'Deviation For Review',
      route: 'review',
      icon: 'fa-edit',
      category: 'Deviation',
      gradient: G.blue,
    },
    {
      id: 'log',
      title: 'Log',
      route: 'log',
      icon: 'fa-book',
      category: 'Deviation',
      gradient: G.navy,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
