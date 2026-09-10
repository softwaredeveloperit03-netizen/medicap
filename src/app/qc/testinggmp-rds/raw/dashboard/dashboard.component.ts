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
      dashboardTitle="RM/PM Testing Section"
      sectionLabel="Testing Raw Section"
      sidebarTitle="Raw Testing"
      closeRouterLink="/qc/testing-rds"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_testing_raw_dashboard_palette"
      sidebarStorageKey="qc_testing_raw_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'allocation', title: 'Testing Allocation', searchText: 'allocation', route: 'allocation', icon: 'fa-tasks', category: 'Workflow', gradient: G.blue },
    { id: 'awaiting', title: 'Awaiting Testing', searchText: 'awaiting', route: 'awaiting', icon: 'fa-hourglass-half', category: 'Workflow', gradient: G.amber },
    { id: 'checking', title: 'Testing for Checking', searchText: 'checking', route: 'checking', icon: 'fa-redo-alt', category: 'Workflow', gradient: G.indigo },
    { id: 'approval', title: 'Testing for Approval', searchText: 'approval', route: 'approval', icon: 'fa-check-circle', category: 'Workflow', gradient: G.teal },
    { id: 'log', title: 'Approved Testing Log', searchText: 'approved log', route: 'log', icon: 'fa-list', category: 'Records', gradient: G.emerald },
    { id: 'rejected', title: 'Rejected Tests', searchText: 'rejected', route: 'rejected', icon: 'fa-times-circle', category: 'Records', gradient: G.rose },
    { id: 'correction', title: 'Tests for Correction', searchText: 'correction', route: 'correction', icon: 'fa-edit', category: 'Records', gradient: G.steel },
    { id: 'outside', title: 'Outside Testing', searchText: 'outside', route: 'outside', icon: 'fa-external-link-alt', category: 'Records', gradient: G.violet },
    { id: 'usage-log', title: 'Equipment/Chemical Usage Log', searchText: 'usage log equipment chemical', route: 'usage-log', icon: 'fa-clipboard-list', category: 'Records', gradient: G.navy },
    // { id: 'rds', title: 'Raw Data Sheet', searchText: 'rds raw data sheet', route: 'rds', icon: 'fa-file', category: 'Reports', gradient: G.violet },
    { id: 'coa', title: 'COA', searchText: 'coa certificate of analysis', route: 'coa', icon: 'fa-certificate', category: 'Reports', gradient: G.amber },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
