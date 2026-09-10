import { Component } from '@angular/core';
import {
  buildSidebarTabsFromCards,
  QC_CARD_GRADIENTS,
} from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.constants';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = QC_CARD_GRADIENTS;

@Component({
  selector: 'app-amv-dashboard',
  template: `
    <app-qc-module-dashboard-shell
      themeVariant="marketing"
      sidebarNavMode="modules"
      [showDeptToolbar]="false"
      dashboardTitle="Analytical Method Validation"
      sectionLabel="AMV Modules"
      sidebarTitle="AMV"
      closeRouterLink="/qc"
      searchPlaceholder="Search modules"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_amv_dashboard_palette"
      sidebarStorageKey="qc_amv_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class AmvDashboardComponent {
  readonly cards: QcDeptCard[] = [
    {
      id: 'raw',
      title: 'Raw Material AMV',
      route: '/qc/anat1/raw',
      icon: 'fa-cubes',
      category: 'AMV',
      gradient: G.blue,
    },
    {
      id: 'finish',
      title: 'Finish Product AMV',
      route: '/qc/anat1/finish',
      icon: 'fa-box-open',
      category: 'AMV',
      gradient: G.teal,
    },
    {
      id: 'process',
      title: 'Process Validation',
      route: '/qc/validation',
      icon: 'fa-cogs',
      category: 'AMV',
      gradient: G.indigo,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
