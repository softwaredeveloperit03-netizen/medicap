import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { ManageapprovalComponent } from './manageapproval/manageapproval.component';
import { ExpmanComponent } from './expman/expman.component';
import { GeneralStoreComponent } from './general-store/general-store.component';
import { TranslateModule } from '@ngx-translate/core';

 
const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'manage_approval', component: ManageapprovalComponent},
  { path: 'expManagement', component: ExpmanComponent},
  { path: 'hr', loadChildren: () => import('./hr/hr.module').then(m=>m.HrModule), data: {preload: false}},
  { path: 'testing', loadChildren: () => import('./testing/testing.module').then(m=>m.TestingModule), data: {preload: false}},
  { path: 'inventory', loadChildren: () => import('./inventory/inventory.module').then(m=>m.InventoryModule), data: {preload: false}},
  { path: 'meeting', loadChildren: () => import('./meeting/meeting.module').then(m=>m.MeetingModule), data: {preload: false}},
  { path: 'costing', loadChildren: () => import('./costing/costing.module').then(m=>m.CostingModule), data: {preload: false}},
  { path: 'report', loadChildren: () => import('./report/report.module').then(m=>m.ReportModule), data: {preload: false}},
  { path: 'production', loadChildren: () => import('./production/production.module').then(m=>m.ProductionModule), data: {preload: false}},
  { path: 'purchase', loadChildren: () => import('./purchase/purchase.module').then(m=>m.PurchaseModule), data: {preload: false}},
  { path: 'product', loadChildren: () => import('./product/product.module').then(m=>m.ProductModule), data: {preload: false}},
  { path: 'qms', loadChildren: () => import('./qms/qms.module').then(m=>m.QmsModule), data: {preload: false}},
   { path: 'vp', loadChildren: () => import('./vp/vp.module').then(m=>m.VpModule), data: {preload: false}},
  { path: 'director', loadChildren: () => import('./director/director.module').then(m=>m.DirectorModule), data: {preload: false}},
  { path: 'vice', loadChildren: () => import('./vicepr/vicepr.module').then(m=>m.ViceprModule), data: {preload: false}},

];

@NgModule({
  declarations: [DashboardComponent, ManageapprovalComponent, ExpmanComponent, GeneralStoreComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class ManagementModule { }
