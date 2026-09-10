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
      dashboardTitle="Stability Testing"
      sectionLabel="Stability Section"
      sidebarTitle="Stability"
      closeRouterLink="/qc/testing-rds"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_stability_dashboard_palette"
      sidebarStorageKey="qc_stability_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'allocation', title: 'Allocation', route: 'allocation', icon: 'fa-tasks', category: 'Workflow', gradient: G.blue },
    { id: 'awaiting', title: 'Awaiting', route: 'awaiting', icon: 'fa-hourglass-half', category: 'Workflow', gradient: G.amber },
    { id: 'approval', title: 'Approval', route: 'approval', icon: 'fa-check-circle', category: 'Workflow', gradient: G.teal },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-list', category: 'Records', gradient: G.emerald },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
