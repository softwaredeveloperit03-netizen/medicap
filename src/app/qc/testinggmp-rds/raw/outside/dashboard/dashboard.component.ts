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
      themeVariant="marketing"
      sidebarNavMode="modules"
      [showDeptToolbar]="false"
      [showFloatingDocs]="true"
      dashboardTitle="Outside Testing"
      sectionLabel="Outside Testing Section"
      sidebarTitle="Outside Testing"
      closeRouterLink="/qc/testing-rds/raw"
      [closeUseHistory]="false"
      searchPlaceholder="Search outside testing modules"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      guideModuleId="testing"
      guideSectionOverride="Outside Testing"
      paletteStorageKey="qc_outside_dashboard_palette"
      sidebarStorageKey="qc_outside_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'sample', title: 'Sample', searchText: 'sample outside', route: 'sample', icon: 'fa-vial', category: 'Outside', gradient: G.blue },
    { id: 'checking', title: 'Checking', searchText: 'checking outside', route: 'checking', icon: 'fa-redo-alt', category: 'Outside', gradient: G.indigo },
    { id: 'report', title: 'Report', searchText: 'report outside', route: 'report', icon: 'fa-file-alt', category: 'Outside', gradient: G.emerald },
    { id: 'correction', title: 'Correction', searchText: 'correction outside', route: 'correction', icon: 'fa-edit', category: 'Outside', gradient: G.amber },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
