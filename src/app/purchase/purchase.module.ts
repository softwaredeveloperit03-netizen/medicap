import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  {
    path: 'indend',
    loadChildren: () => import('./indend/indend.module').then((m) => m.IndendModule),
    data: { preload: false },
  },
  {
    path: 'order',
    loadChildren: () => import('./order/order.module').then((m) => m.OrderModule),
    data: { preload: false },
  },
  {
    path: 'purchase-requisition',
    loadChildren: () =>
      import('./purchase-requisition/purchase-requisition.module').then((m) => m.PurchaseRequisitionModule),
    data: { preload: false },
  },
  {
    path: 'quotation',
    loadChildren: () => import('./quotation/quotation.module').then((m) => m.QuotationModule),
    data: { preload: false },
  },
  {
    path: 'vendor',
    loadChildren: () => import('./vendor/vendor.module').then((m) => m.VendorModule),
    data: { preload: false },
  },
  {
    path: 'reports',
    loadChildren: () => import('./reports/reports.module').then((m) => m.ReportsModule),
    data: { preload: false },
  },
  {
    path: 'post-receiving-status',
    loadChildren: () =>
      import('./post-receiving-status/post-receiving-status.module').then((m) => m.PostReceivingStatusModule),
    data: { preload: false },
  },
  {
    path: 'stock',
    loadChildren: () => import('./stock/stock.module').then((m) => m.StockModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [DashboardComponent],
  imports: [TranslateModule, SharedModule, CommonModule, FormsModule, ClarityModule, RouterModule.forChild(routes)],
})
export class PurchaseModule {}
