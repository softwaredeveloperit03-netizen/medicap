import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'admin', loadChildren: () => import('./admin/admin.module').then(m=>m.AdminModule), data: {preload: false}},
  { path: 'canteen', loadChildren: () => import('./canteen/canteen.module').then(m=>m.CanteenModule), data: {preload: false}},
  { path: 'etp', loadChildren: () => import('./etp/etp.module').then(m=>m.EtpModule), data: {preload: false}},
  { path: 'security', loadChildren: () => import('./security/security.module').then(m=>m.SecurityModule), data: {preload: false}},
  { path: 'production', loadChildren: () => import('./production/production.module').then(m=>m.ProductionModule), data: {preload: false}},
  { path: 'srp', loadChildren: () => import('./srp/srp.module').then(m=>m.SrpModule), data: {preload: false}},
  { path: 'warehouse', loadChildren: () => import('./warehouse/warehouse.module').then(m=>m.WarehouseModule), data: {preload: false}},
  { path: 'fgstore', loadChildren: () => import('./fgstore/fgstore.module').then(m=>m.FgstoreModule), data: {preload: false}}
];

@NgModule({
  declarations: [
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PremisesModule { }
