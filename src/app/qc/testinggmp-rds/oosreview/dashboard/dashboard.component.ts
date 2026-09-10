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
      dashboardTitle="OOS Review"
      sectionLabel="OOS Review Section"
      sidebarTitle="OOS Review"
      closeRouterLink="/qc/testing-rds"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_oosreview_dashboard_palette"
      sidebarStorageKey="qc_oosreview_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'checking', title: 'Checking', route: 'checking', icon: 'fa-redo-alt', category: 'Workflow', gradient: G.blue },
    { id: 'review', title: 'Review', route: 'review', icon: 'fa-search', category: 'Workflow', gradient: G.indigo },
    { id: 'reanalysis', title: 'Reanalysis', route: 'reanalysis', icon: 'fa-sync-alt', category: 'Workflow', gradient: G.amber },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-list', category: 'Records', gradient: G.teal },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
