import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'certificate', loadChildren: () => import('./certificate/certificate.module').then(m=>m.CertificateModule), data: {preload: false}},
  { path: 'cost-report', loadChildren: () => import('./cost-report/cost-report.module').then(m=>m.CostReportModule), data: {preload: false}},
  { path: 'destruction', loadChildren: () => import('./destruction/destruction.module').then(m=>m.DestructionModule), data: {preload: false}},
  { path: 'expiry', loadChildren: () => import('./expiry/expiry.module').then(m=>m.ExpiryModule), data: {preload: false}},
  { path: 'master', loadChildren: () => import('./master/master.module').then(m=>m.MasterModule), data: {preload: false}},
  { path: 'matrix', loadChildren: () => import('./matrix/matrix.module').then(m=>m.MatrixModule), data: {preload: false}},
  { path: 'ordering', loadChildren: () => import('./ordering/ordering.module').then(m=>m.OrderingModule), data: {preload: false}},
  { path: 'qualification', loadChildren: () => import('./qualification/qualification.module').then(m=>m.QualificationModule), data: {preload: false}},
  { path: 'receiving', loadChildren: () => import('./receiving/receiving.module').then(m=>m.ReceivingModule), data: {preload: false}},
  { path: 'stock-report', loadChildren: () => import('./stock-report/stock-report.module').then(m=>m.StockReportModule), data: {preload: false}},
  { path: 'storage', loadChildren: () => import('./storage/storage.module').then(m=>m.StorageModule), data: {preload: false}},
  { path: 'issuance', loadChildren: () => import('./issuance/issuance.module').then(m=>m.IssuanceModule), data: {preload: false}}

];

@NgModule({
  declarations: [
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class StandardModule { }
