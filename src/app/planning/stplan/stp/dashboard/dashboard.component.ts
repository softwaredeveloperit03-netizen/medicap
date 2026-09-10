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
      id: 'canplan',
      title: 'Process Plan',
      route: '/planning/stplan/stStp/Canplan',
      icon: 'fa-project-diagram',
      category: 'STP Sections',
      gradient: G.indigo,
    },
    {
      id: 'verifystock',
      title: 'Verify & Book Stock',
      route: '/planning/stplan/stStp/VerifyStock',
      icon: 'fa-check-circle',
      category: 'STP Sections',
      gradient: G.emerald,
    },
    {
      id: 'forplan',
      title: 'Line Booking',
      route: 'Forplan',
      icon: 'fa-vial',
      category: 'STP Sections',
      gradient: G.navy,
    },
    {
      id: 'lineapproval',
      title: 'Line Approval',
      route: 'Lineapproval',
      icon: 'fa-clipboard-check',
      category: 'STP Sections',
      gradient: G.slate,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
