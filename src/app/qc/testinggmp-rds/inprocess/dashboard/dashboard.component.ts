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
      dashboardTitle="Testing In Process"
      sectionLabel="In Process Section"
      sidebarTitle="In Process"
      closeRouterLink="/qc/testing-rds"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_inprocess_dashboard_palette"
      sidebarStorageKey="qc_inprocess_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'test', title: 'Test', route: 'test', icon: 'fa-flask', category: 'Workflow', gradient: G.blue },
    { id: 'allocation', title: 'Allocation', route: 'allocation', icon: 'fa-tasks', category: 'Workflow', gradient: G.indigo },
    { id: 'receive', title: 'Receive', route: 'receive', icon: 'fa-truck-loading', category: 'Workflow', gradient: G.teal },
    { id: 'checking', title: 'Checking', route: 'checking', icon: 'fa-redo-alt', category: 'Workflow', gradient: G.amber },
    { id: 'approval', title: 'Approval', route: 'approval', icon: 'fa-check-circle', category: 'Workflow', gradient: G.emerald },
    { id: 'intrecive', title: 'Intrecive', route: 'intrecive', icon: 'fa-exchange-alt', category: 'Workflow', gradient: G.violet },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-list', category: 'Records', gradient: G.steel },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
