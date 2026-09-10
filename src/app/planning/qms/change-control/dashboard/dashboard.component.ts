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
      title: 'New Initiate',
      route: 'new',
      icon: 'fa-plus-circle',
      category: 'Change Control',
      gradient: G.teal,
    },
    {
      id: 'review',
      title: 'Change Control For Review',
      route: 'review',
      icon: 'fa-edit',
      category: 'Change Control',
      gradient: G.blue,
    },
    {
      id: 'implementation',
      title: 'Change Control For Implementation',
      route: 'implementation',
      icon: 'fa-check-circle',
      category: 'Change Control',
      gradient: G.emerald,
    },
    {
      id: 'log',
      title: 'Log',
      route: 'log',
      icon: 'fa-book',
      category: 'Change Control',
      gradient: G.navy,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
