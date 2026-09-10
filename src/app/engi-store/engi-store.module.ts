import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';

 import { LabelComponent } from './label/label.component';
 

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'barcodePriting', component: LabelComponent},
  { path: 'challan', loadChildren: () => import('./challan/challan.module').then(m=>m.ChallanModule), data: {preload: false}},
  { path: 'grn', loadChildren: () => import('./grn/grn.module').then(m=>m.GrnModule), data: {preload: false}},
  { path: 'stock', loadChildren: () => import('./stock/stock.module').then(m=>m.StockModule), data: {preload: false}},
  {path:'requisition',loadChildren:()=>import('./requisition/requisition.module').then(m=>m.RequisitionModule),data:{preload:false}},
  { path: 'receivingNew', loadChildren: () => import('./receiving-new/receiving-new.module').then(m=>m.ReceivingNewModule), data: {preload: false}},
  { path: 'weighing', loadChildren: () => import('./weighing/weighing.module').then(m=>m.WeighingModule), data: {preload: false}},
  { path: 'damage', loadChildren: () => import('./damage/damage.module').then(m=>m.DamageModule), data: {preload: false}},

];

@NgModule({
  declarations: [DashboardComponent,LabelComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class EngiStoreModule { }
