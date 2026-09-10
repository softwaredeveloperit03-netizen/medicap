import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormBuilder, FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { InventoryComponent } from './inventory/inventory.component';
import { OpeningStockComponent } from './opening-stock/opening-stock.component';
import { DispatchReportComponent } from './dispatch-report/dispatch-report.component';
import { PoComponent } from './po/po.component';
import { ClosingStockComponent } from './closing-stock/closing-stock.component';
import { SpintimationComponent } from './spintimation/spintimation.component';
import { BmrlogComponent } from './bmrlog/bmrlog.component';
import { TranslateModule } from '@ngx-translate/core';

 

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'inventory', component: InventoryComponent},
  { path: 'spintimation', component: SpintimationComponent},
  { path: 'bmr', component: BmrlogComponent},
  { path: 'opening-stock', component: OpeningStockComponent},
  { path: 'dispatch-report', component: DispatchReportComponent },
  { path: 'closing-stock', component: ClosingStockComponent },
 
  { path: 'po', component: PoComponent},
  { path: 'sales', loadChildren: () => import('./sales/sales.module').then(m=>m.SalesModule), data: {preload: false}},
  { path: 'tax', loadChildren: () => import('./tax/tax.module').then(m=>m.TaxModule), data: {preload: false}},
  { path: 'release', loadChildren: () => import('./release/release.module').then(m=>m.ReleaseModule), data: {preload: false}},
  { path: 'manual', loadChildren: () => import('./manual/manual.module').then(m=>m.ManualModule), data: {preload: false}},
  { path: 'fg-stock-statement', loadChildren: () => import('./fg-stock-statement1/fg-stock-statement1.module').then(m=>m.FgStockStatement1Module), data: {preload: false}},
  { path: 'add-product-value', loadChildren: () => import('./add-product-value/add-product-value.module').then(m=>m.AddProductValueModule), data: {preload: false}},
  { path: 'data-logger', loadChildren: () => import('./data-logger/data-logger.module').then(m=>m.DataLoggerModule), data: {preload: false}},
  { path: 'data-logger-master', loadChildren: () => import('./data-logger-master/data-logger-master.module').then(m=>m.DataLoggerMasterModule), data: {preload: false}},
  { path: 'fg-sampling', loadChildren: () => import('./fg-sampling/fg-sampling.module').then(m=>m.FgSamplingModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, InventoryComponent, OpeningStockComponent,DispatchReportComponent, PoComponent, ClosingStockComponent, SpintimationComponent, BmrlogComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes)
  ]
})
export class DispatchModule { }
