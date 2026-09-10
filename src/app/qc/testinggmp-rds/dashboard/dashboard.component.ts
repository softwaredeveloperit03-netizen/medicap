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
      dashboardTitleKey="qc.testing.title"
      sectionLabelKey="qc.testing.section"
      sidebarTitleKey="qc.testing.modulesTitle"
      closeRouterLink="/qc"
      closeLabelKey="common.close"
      searchPlaceholderKey="common.searchPlaceholder"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_testinggmp_dashboard_palette"
      sidebarStorageKey="qc_testinggmp_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'raw', title: 'RM/PM Testing', searchText: 'raw material rm pm testing', route: 'raw', icon: 'fa-vial', category: 'RM/PM Testing', gradient: G.blue },
    { id: 'stability', title: 'Stability Testing', searchText: 'stability testing', route: 'stability', icon: 'fa-hourglass-half', category: 'Stability', gradient: G.teal },
    { id: 'inprocess', title: 'In Process Testing', searchText: 'in process testing', route: 'inprocess', icon: 'fa-cogs', category: 'In Process', gradient: G.indigo },
    { id: 'finish', title: 'Finished Product Testing', searchText: 'finished product goods finish testing', route: 'finish', icon: 'fa-box', category: 'Finished Goods', gradient: G.amber },
    { id: 'coa', title: 'COA-Certificate of Analysis', searchText: 'coa certificate of analysis approved released download pdf raw material packing', route: 'raw/coa', icon: 'fa-certificate', category: 'Reports', gradient: G.amber },
    { id: 'oosreview', title: 'OOS Review', searchText: 'oos out of specification review', route: 'oosreview', icon: 'fa-search', category: 'OOS Review', gradient: G.rose },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
