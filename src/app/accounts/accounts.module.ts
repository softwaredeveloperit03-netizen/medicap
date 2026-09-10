import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { MatApprovalComponent } from './mat-approval/mat-approval.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { FgProductApprovalComponent } from './fg-product-approval/fg-product-approval.component';
import { PoPaymentLogComponent } from './po-payment-log/po-payment-log.component';
import { MatRateComponent } from './mat-rate/mat-rate.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'materialApproval', component: MatApprovalComponent},
  { path: 'fgProductApproval', component: FgProductApprovalComponent},
  { path: 'poPayLog', component: PoPaymentLogComponent},
  { path: 'matRate', component: MatRateComponent},
  { path: 'costCenter', loadChildren: () => import('./cost-center/cost-center.module').then((m) => m.CostCenterModule), data: { preload: false }, },
  { path: 'prc', loadChildren: () => import('./prc/prc.module').then((m) => m.PrcModule), data: { preload: false }, },
 
 
  // { path: 'unit', loadChildren: () => import('./unit/unit.module').then(m=>m.UnitModule), data: {preload: false}},
];


@NgModule({
  declarations: [
    DashboardComponent,
    MatApprovalComponent,
    FgProductApprovalComponent,
    PoPaymentLogComponent,
    MatRateComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class AccountsModule { }
