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
      dashboardTitle="Sampling"
      sectionLabel="Sampling Modules"
      sidebarTitle="Sampling Modules"
      closeRouterLink="/qc"
      searchPlaceholder="Search sampling modules"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_sampling_dashboard_palette"
      sidebarStorageKey="qc_sampling_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'raw', title: 'Raw Material Sampling', route: 'raw', icon: 'fa-vial', category: 'Raw Material', gradient: G.blue },
    { id: 'retest', title: 'Retest Sampling', route: 'retest', icon: 'fa-redo', category: 'Retest', gradient: G.amber },
    { id: 'finish', title: 'Finish Sampling', route: 'finish', icon: 'fa-box-open', category: 'Finished Product', gradient: G.teal },
    { id: 'sampling-room', title: 'Sampling Room Usage and Cleaning Log', route: 'sampling-room', icon: 'fa-broom', category: 'Sampling Room', gradient: G.violet },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
