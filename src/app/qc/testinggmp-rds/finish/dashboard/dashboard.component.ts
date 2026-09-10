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
      dashboardTitle="Testing Finish"
      sectionLabel="Testing Finish Section"
      sidebarTitle="Finish Testing"
      closeRouterLink="/qc/testing-rds"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_finish_dashboard_palette"
      sidebarStorageKey="qc_finish_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'allocation', title: 'Allocation', route: 'allocation', icon: 'fa-tasks', category: 'Workflow', gradient: G.blue },
    { id: 'awaiting', title: 'Awaiting', route: 'awaiting', icon: 'fa-hourglass-half', category: 'Workflow', gradient: G.amber },
    { id: 'new', title: 'New', route: 'new', icon: 'fa-plus-circle', category: 'Workflow', gradient: G.teal },
    { id: 'ar', title: 'AR', route: 'ar', icon: 'fa-file-alt', category: 'Documents', gradient: G.indigo },
    { id: 'coa', title: 'COA', route: 'coa', icon: 'fa-certificate', category: 'Documents', gradient: G.violet },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
