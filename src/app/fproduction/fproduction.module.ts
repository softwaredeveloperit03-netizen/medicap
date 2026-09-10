import { QcModuleDashboardModule } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.module';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { WebmrComponent } from './webmr/webmr.component';
import { EbmrComponent } from './ebmr/ebmr.component';
import { OosComponent } from './oos/oos.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';
import { TemperatureModule } from '../store/temperature/temperature.module';
import { TemperatureComponent } from '../store/temperature/temperature.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'temperature', component: TemperatureComponent, data: { temperatureDepartment: 'Production', closeRoute: '/fproduction' } },
  { path: 'webmr', component: WebmrComponent },
  {
    path: 'ebmr/batch',
    loadChildren: () => import('../production/batch/batch.module').then((m) => m.BatchModule),
    data: { preload: false },
  },
  {
    path: 'ebmr',
    children: [
      { path: '', pathMatch: 'full', component: EbmrComponent },
      {
        path: '',
        loadChildren: () => import('./ebmr/ebmr-feature.module').then((m) => m.EbmrFeatureModule),
        data: { preload: false },
      },
    ],
  },
  { path: 'oos', component: OosComponent },
  {
    path: 'sampling',
    loadChildren: () => import('./sampling/sampling.module').then((m) => m.SamplingModule),
    data: { preload: false },
  },
  {
    path: 'bulk_stock',
    loadChildren: () => import('./bulk_stock/bulk_stock.module').then((m) => m.BulkStockModule),
    data: { preload: false },
  },
  {
    path: 'mfglines',
    loadChildren: () => import('./mfglines/mfglines.module').then((m) => m.MfglinesModule),
    data: { preload: false },
  },
  {
    path: 'bmr',
    loadChildren: () => import('./bmr/bmr.module').then((m) => m.BmrModule),
    data: { preload: false },
  },
  {
    path: 'wobmr',
    loadChildren: () => import('./wobmr/wobmr.module').then((m) => m.WobmrModule),
    data: { preload: false },
  },
  {
    path: 'purchase-requisition',
    loadChildren: () =>
      import('./purchase-requisition/purchase-requisition.module').then((m) => m.PurchaseRequisitionModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [DashboardComponent, WebmrComponent, OosComponent, EbmrComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    QcModuleDashboardModule,
    TemperatureModule,
    RouterModule.forChild(routes),
  ],
})
export class FproductionModule {}
