import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { StockComponent } from './stock/stock.component';
import { MaterialComponent } from './material/material.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'stock', component: StockComponent},
  { path: 'challan', loadChildren: () => import('./challan/challan.module').then(m=>m.ChallanModule), data: {preload: false}},
  { path: 'receiving', loadChildren: () => import('./receiving/receiving.module').then(m=>m.ReceivingModule), data: {preload: false}},
  { path: 'dedusting', loadChildren: () => import('./dedusting/dedusting.module').then(m=>m.DedustingModule), data: {preload: false}},
  { path: 'weighing', loadChildren: () => import('./weighing/weighing.module').then(m=>m.WeighingModule), data: {preload: false}},
  { path: 'grn', loadChildren: () => import('./grn/grn.module').then(m=>m.GrnModule), data: {preload: false}},
  { path: 'dispensing', loadChildren: () => import('./dispensing/dispensing.module').then(m=>m.DispensingModule), data: {preload: false}},
  { path: 'retest', loadChildren:()=>import('./retest/retest.module').then(m=>m.RetestModule),data:{preload:false}},
  { path: 'spillage', loadChildren: () => import('./spillage/spillage.module').then(m=>m.SpillageModule), data: {preload: false}},
  {path:'material',component:MaterialComponent}
];

@NgModule({
  declarations: [DashboardComponent, StockComponent, MaterialComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PackingModule { }
