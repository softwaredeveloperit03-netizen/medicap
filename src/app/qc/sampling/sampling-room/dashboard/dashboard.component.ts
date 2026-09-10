import { Component } from '@angular/core';
import {
  buildSidebarTabsFromCards,
  QC_CARD_GRADIENTS,
  QC_SAMPLING_SHELL_CLASS,
} from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.constants';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = QC_CARD_GRADIENTS;

@Component({
  selector: 'app-sampling-room-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  readonly shellClass = QC_SAMPLING_SHELL_CLASS;

  readonly cards: QcDeptCard[] = [
    {
      id: 'log',
      title: 'Usage & Cleaning Log',
      searchText: 'Sampling room usage and cleaning log',
      route: 'log',
      icon: 'fa-book',
      category: 'Sampling Room',
      gradient: G.blue,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
