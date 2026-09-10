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
      dashboardTitle="Volumetric Solutions"
      sectionLabel="Volumetric Solutions Section"
      sidebarTitle="Volumetric"
      closeRouterLink="/qc"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_volumetric_dashboard_palette"
      sidebarStorageKey="qc_volumetric_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'master', title: 'Master', route: 'master', icon: 'fa-book', category: 'Master', gradient: G.indigo },
    { id: 'standardisation', title: 'Standardisation', route: 'standardisation', icon: 'fa-balance-scale', category: 'Workflow', gradient: G.teal },
    { id: 'approval', title: 'Approval', route: 'approval', icon: 'fa-check-circle', category: 'Workflow', gradient: G.emerald },
    { id: 'log', title: 'Log Book', route: 'log', icon: 'fa-book-open', category: 'Records', gradient: G.steel },
    { id: 'stock', title: 'Stock', route: 'stock', icon: 'fa-boxes', category: 'Inventory', gradient: G.amber },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
