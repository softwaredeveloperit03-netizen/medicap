import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { StockComponent } from './stock/stock.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'stock', component: StockComponent},
  { path: 'equipments', loadChildren: () => import('./equipments/equipments.module').then(m=>m.EquipmentsModule), data: {preload: false}},
  { path: 'rack', loadChildren: () => import('./rack/rack.module').then(m=>m.RackModule), data: {preload: false}},
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},
  { path: 'packing', loadChildren: () => import('./packing/packing.module').then(m=>m.PackingModule), data: {preload: false}},
  { path: 'balance', loadChildren: () => import('./balance/balance.module').then(m=>m.BalanceModule), data: {preload: false}},
  { path: 'dispensing', loadChildren: () => import('./dispensing/dispensing.module').then(m=>m.DispensingModule), data: {preload: false}},
  { path: 'bincard', loadChildren: () => import('./bincard/bincard.module').then(m=>m.BincardModule), data: {preload: false}},
  { path: 'challan', loadChildren: () => import('./challan/challan.module').then(m=>m.ChallanModule), data: {preload: false}},
  { path: 'rejection', loadChildren: () => import('./rejection/rejection.module').then(m=>m.RejectionModule), data: {preload: false}},
  { path: 'indend', loadChildren: () => import('./indend/indend.module').then(m=>m.IndendModule), data: {preload: false}},
  { path: 'outword', loadChildren: () => import('./outword/outword.module').then(m=>m.OutwordModule), data: {preload: false}},

];

@NgModule({
  declarations: [DashboardComponent, StockComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class StoreModule { }
