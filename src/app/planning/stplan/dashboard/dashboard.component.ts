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
      category: 'Planning',
      gradient: G.teal,
    },
    {
      id: 'generatewo',
      title: 'Generate Work Order',
      route: '/planning/Generatewo',
      icon: 'fa-file-alt',
      category: 'Planning',
      gradient: G.blue,
    },
    {
      id: 'shortages',
      title: 'Shortage Analysis',
      route: '/planning/Shortages',
      icon: 'fa-chart-bar',
      category: 'Planning',
      gradient: G.amber,
    },
    {
      id: 'canplan',
      title: 'Process Plan',
      route: '/planning/stplan/stStp/Canplan',
      icon: 'fa-project-diagram',
      category: 'STP',
      gradient: G.indigo,
    },
    {
      id: 'verifystock',
      title: 'Verify & Book Stock',
      route: '/planning/stplan/stStp/VerifyStock',
      icon: 'fa-check-circle',
      category: 'STP',
      gradient: G.emerald,
    },
    {
      id: 'forplan',
      title: 'Line Booking',
      route: '/planning/stplan/stStp/Forplan',
      icon: 'fa-vial',
      category: 'STP',
      gradient: G.navy,
    },
    {
      id: 'lineapproval',
      title: 'Line Approval',
      route: '/planning/stplan/stStp/Lineapproval',
      icon: 'fa-clipboard-check',
      category: 'STP',
      gradient: G.slate,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
