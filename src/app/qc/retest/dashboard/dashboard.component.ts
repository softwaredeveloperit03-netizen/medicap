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
      dashboardTitle="Retest Management"
      sectionLabel="Retest Section"
      sidebarTitle="Retest"
      closeRouterLink="/qc"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_retest_dashboard_palette"
      sidebarStorageKey="qc_retest_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'report', title: 'Report', route: 'report', icon: 'fa-file-alt', category: 'Workflow', gradient: G.indigo },
    { id: 'calender', title: 'Calender', route: 'calender', icon: 'fa-calendar-alt', category: 'Records', gradient: G.amber },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
