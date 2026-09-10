import { Component } from '@angular/core';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

@Component({
  selector: 'app-retest-intimation-dashboard',
  template: `
    <app-qc-module-dashboard-shell
      themeVariant="marketing"
      sidebarNavMode="modules"
      [showDeptToolbar]="false"
      dashboardTitle="Retest Intimation Slip"
      sectionLabel="WH → QC Paper Trail"
      sidebarTitle="Intimation Slip"
      closeRouterLink="/store/raw/retest"
      searchPlaceholder="Search modules"
      [cards]="cards"
      paletteStorageKey="store-retest-intimation-slip-dashboard"
      sidebarStorageKey="store-retest-intimation-slip-sidebar"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    { id: 'new', title: 'New Intimation Slip', route: 'new', icon: 'fa-file-medical', category: 'Workflow', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'log', title: 'Intimation Slip Log', route: 'log', icon: 'fa-book', category: 'Records', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];
}
