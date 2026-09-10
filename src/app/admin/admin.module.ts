import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from '../shared/shared.module';

  

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'stationary', loadChildren: () => import('./stationary/stationary.module').then(m=>m.StationaryModule), data: {preload: false}},
  { path: 'favility', loadChildren: () => import('./favility/favility.module').then(m=>m.FavilityModule), data: {preload: false}},
  { path: 'first-aid', loadChildren: () => import('./first-aid/first-aid.module').then(m=>m.FirstAidModule), data: {preload: false}},
  { path: 'gardev', loadChildren: () => import('./gardev/gardev.module').then(m=>m.GardevModule), data: {preload: false}},
  { path: 'housekeeping', loadChildren: () => import('./housekeeping/housekeeping.module').then(m=>m.HousekeepingModule), data: {preload: false}},
  { path: 'stationary', loadChildren: () => import('./stationary/stationary.module').then(m=>m.StationaryModule), data: {preload: false}},
  { path: 'aggremment', loadChildren: () => import('./aggremment/aggremment.module').then(m=>m.AggremmentModule),data: {preload: false}},
  { path: 'saftey', loadChildren: () => import('./saftey/saftey.module').then(m=>m.SafteyModule),data: {preload: false}},
  { path: 'transport', loadChildren: () => import('./transport/transport.module').then(m=>m.TransportModule),data: {preload: false}},
  { path: 'transporter', loadChildren: () => import('./transporter/transporter.module').then(m=>m.TransporterModule),data: {preload: false}},
  { path: 'vehicle-management', loadChildren: () => import('./vehicle-management/vehicle-management.module').then(m=>m.VehicleManagementModule),data: {preload: false}},
  { path: 'laundry', loadChildren: () => import('./laundry/laundry.module').then(m=>m.LaundryModule),data: {preload: false}},
  { path: 'scrap', loadChildren: () => import('./scrap-management/scrap-management.module').then(m=>m.ScrapManagementModule),data: {preload: false}},
  { path: 'courier', loadChildren: () => import('./courier/courier.module').then(m=>m.CourierModule),data: {preload: false}},
  { path: 'hygin', loadChildren: () => import('./hygin/hygin.module').then(m=>m.HyginModule),data: {preload: false}},
  { path: 'assetManagement', loadChildren: () => import('./asset-management/asset-management.module').then(m=>m.AssetManagementModule),data: {preload: false}},
 
];

@NgModule({
  declarations: [DashboardComponent],
  imports: [ TranslateModule,
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class AdminModule { }
