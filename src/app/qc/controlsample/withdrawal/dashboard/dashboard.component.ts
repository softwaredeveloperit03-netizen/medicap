import { Component } from '@angular/core';
import {
  buildSidebarTabsFromCards,
  QC_CARD_GRADIENTS,
  QC_SAMPLING_SHELL_CLASS,
} from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.constants';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = QC_CARD_GRADIENTS;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent {
  readonly shellClass = QC_SAMPLING_SHELL_CLASS;

  readonly cards: QcDeptCard[] = [
    {
      id: 'request',
      title: 'Request',
      searchText: 'Control sample withdrawal request',
      route: 'request',
      icon: 'fa-paper-plane',
      category: 'Withdrawal',
      gradient: G.violet,
    },
    {
      id: 'log',
      title: 'Log',
      searchText: 'Withdrawal request log book',
      route: 'log',
      icon: 'fa-list',
      category: 'Withdrawal',
      gradient: G.blue,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
