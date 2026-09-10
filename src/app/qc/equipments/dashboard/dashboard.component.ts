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
      dashboardTitle="Equipment Usages & Cleaning"
      sectionLabel="Equipment Section"
      sidebarTitle="Equipments"
      closeRouterLink="/qc"
      [cards]="cards"
      [sidebarTabs]="sidebarTabs"
      paletteStorageKey="qc_equipments_dashboard_palette"
      sidebarStorageKey="qc_equipments_sidebar_collapsed"
    ></app-qc-module-dashboard-shell>
  `,
})
export class DashboardComponent {
  readonly cards: QcDeptCard[] = [
    {
      id: 'cleaning',
      title: 'Cleaning Entry (shortcut)',
      route: 'cleaning',
      icon: 'fa-broom',
      category: 'Operations',
      gradient: G.blue,
    },
    {
      id: 'usages',
      title: 'Usage & Cleaning Logbook',
      route: 'usages',
      icon: 'fa-clipboard-list',
      category: 'Operations',
      gradient: G.teal,
      searchText: 'sequential cleaning usage barcode scan log checking approval',
    },
    { id: 'list', title: 'Equipment List', route: 'list', icon: 'fa-list', category: 'Records', gradient: G.indigo },
    {
      id: 'barcodes',
      title: 'Equipment Barcodes',
      route: 'usages/barcodes',
      icon: 'fa-barcode',
      category: 'Tools',
      gradient: G.steel,
      searchText: 'print barcode label equipment code scan usage cleaning log',
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);
}
