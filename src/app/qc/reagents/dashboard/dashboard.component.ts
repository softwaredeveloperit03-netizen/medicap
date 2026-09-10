import { Component } from '@angular/core';
import {
  buildSidebarTabsFromCards,
  QC_CARD_GRADIENTS,
} from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.constants';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = QC_CARD_GRADIENTS;

@Component({
  selector: 'app-dashboard',
  template: `
    <app-qc-module-dashboard-shell
      dashboardTitle="Reagent"
      sectionLabel="Reagent Section"
      sidebarTitle="Reagents"
      closeRouterLink="/qc"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_reagents_dashboard_palette"
      sidebarStorageKey="qc_reagents_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'challan', title: 'Payment Slip Entry', route: 'challan_reagent', icon: 'fa-clipboard', category: 'Procurement', gradient: G.blue },
    { id: 'receiving', title: 'Receiving of Reagents', route: 'receiving', icon: 'fa-truck', category: 'Procurement', gradient: G.teal },
    { id: 'weighing', title: 'Weighing of Reagents', route: 'weighing', icon: 'fa-balance-scale', category: 'Procurement', gradient: G.indigo },
    { id: 'grn', title: 'Sampling Receiving', route: 'grn', icon: 'fa-check-double', category: 'Procurement', gradient: G.emerald },
    { id: 'stock', title: 'Stock Book', route: 'stock', icon: 'fa-book', category: 'Inventory', gradient: G.amber },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
