import { Component } from '@angular/core';
import {
  buildSidebarTabsFromCards,
  QC_CARD_GRADIENTS,
  QC_SAMPLING_SHELL_CLASS,
} from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.constants';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = QC_CARD_GRADIENTS;

@Component({
  selector: 'app-receving',
  template: `
    <app-qc-module-dashboard-shell
      [shellClass]="shellClass"
      themeVariant="marketing"
      sidebarNavMode="modules"
      [showDeptToolbar]="false"
      [showFloatingDocs]="true"
      dashboardTitle="Receive Material (For Sampling)"
      sectionLabel="RM/PM Sampling Intimation"
      sidebarTitle="Receiving Modules"
      closeRouterLink="/qc/sampling/raw"
      [closeUseHistory]="false"
      searchPlaceholder="Search receiving modules"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      guideModuleId="sampling"
      guideSectionOverride="Sampling Receiving"
      paletteStorageKey="qc_sampling_receiving_dashboard_palette"
      sidebarStorageKey="qc_sampling_receiving_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class RecevingComponent {
  readonly shellClass = QC_SAMPLING_SHELL_CLASS;

  readonly cards: QcDeptCard[] = [
    {
      id: 'intimation',
      title: 'Sampling Intimation',
      searchText: 'sampling intimation grn receiving',
      route: '/qc/sampling/raw/rmPmGrnReceiving',
      icon: 'fa-sign-in-alt',
      category: 'Receiving',
      gradient: G.blue,
    },
    {
      id: 'rejected',
      title: 'Rejected Sampling Intimation',
      searchText: 'rejected sampling intimation',
      route: '/qc/sampling/raw/rmPmGrnReceiving/rejectedReceiving',
      icon: 'fa-times-circle',
      category: 'Receiving',
      gradient: G.rose,
    },
    {
      id: 'on-hold',
      title: 'On Hold Sampling Intimation',
      searchText: 'on hold sampling intimation',
      route: '/qc/sampling/raw/rmPmGrnReceiving/onHoldReceiving',
      icon: 'fa-pause-circle',
      category: 'Receiving',
      gradient: G.amber,
    },
    {
      id: 'log',
      title: 'Sampling Intimation Log',
      searchText: 'sampling intimation log',
      route: '/qc/sampling/raw/rmPmGrnReceiving/log',
      icon: 'fa-clipboard-check',
      category: 'Receiving',
      gradient: G.emerald,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
