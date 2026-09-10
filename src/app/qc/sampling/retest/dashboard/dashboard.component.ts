import { Component } from '@angular/core';
import { QC_CARD_GRADIENTS } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.constants';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

const G = QC_CARD_GRADIENTS;

@Component({
  selector: 'app-retest-dashboard',
  template: `
    <app-qc-module-dashboard-shell
      themeVariant="marketing"
      sidebarNavMode="modules"
      [showDeptToolbar]="false"
      dashboardTitle="Retest Sampling"
      sectionLabel="Retest Sampling Section"
      sidebarTitle="Retest Sampling Modules"
      closeRouterLink="/qc/sampling"
      searchPlaceholder="Search retest modules"
      [cards]="cards"
      paletteStorageKey="qc_sampling_retest_dashboard_palette"
      sidebarStorageKey="qc_sampling_retest_dashboard_sidebar"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    {
      id: 'receive',
      title: 'Receive Intimation Slip',
      searchText: 'Receive WH retest intimation slip from Store',
      route: 'receive',
      icon: 'fa-inbox',
      category: 'Retest',
      gradient: G.indigo,
    },
    {
      id: 'allocation',
      title: 'Retest Allocation',
      searchText: 'Allocate QC Micro sampling persons for due retests',
      route: 'allocation',
      icon: 'fa-user-check',
      category: 'Retest',
      gradient: G.blue,
    },
    {
      id: 'sampling',
      title: 'Sampling of Retest',
      searchText: 'Perform retest sampling activities',
      route: 'sampling',
      icon: 'fa-vial',
      category: 'Retest',
      gradient: G.teal,
    },
    {
      id: 'checking',
      title: 'Sampling Checking',
      searchText: 'Check and verify retest sampling data',
      route: 'checking',
      icon: 'fa-redo-alt',
      category: 'Retest',
      gradient: G.violet,
    },
    {
      id: 'approval',
      title: 'Sampling Approval',
      searchText: 'Approve retest sampled materials',
      route: 'approval',
      icon: 'fa-check-circle',
      category: 'Retest',
      gradient: G.blue,
    },
    {
      id: 'log',
      title: 'Sampling Log',
      searchText: 'Completed retest sampling logs; next step QC Testing Allocation',
      route: 'log',
      icon: 'fa-list',
      category: 'Retest',
      gradient: G.emerald,
    },
  ];
}
