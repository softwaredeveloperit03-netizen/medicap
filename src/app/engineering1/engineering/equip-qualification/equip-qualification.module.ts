import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { QualificationRequestComponent } from './qualification-request/qualification-request.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { EquipmentVendorComponent } from './equipment-vendor/equipment-vendor.component';
import { RouterModule, Routes } from '@angular/router';
import { ApprovalComponent } from './approval/approval.component';
import { RequalificationComponent } from './requalification/requalification.component';
import { HubComponent } from './hub/hub.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'hub', component: HubComponent },
  { path: 'qualification-request', component: QualificationRequestComponent },
  { path: 'equipment-vendor', component: EquipmentVendorComponent },
  { path: 'requalification', component: RequalificationComponent },

  {
    path: 'urs',
    loadChildren: () => import('./urs/urs.module').then((m) => m.UrsModule),
    data: { preload: false },
  },
  {
    path: 'dq',
    loadChildren: () => import('./dq/dq.module').then((m) => m.DqModule),
    data: { preload: false },
  },
  {
    path: 'factory',
    loadChildren: () =>
      import('./factory/factory.module').then((m) => m.FactoryModule),
    data: { preload: false },
  },
  {
    path: 'site',
    loadChildren: () => import('./site/site.module').then((m) => m.SiteModule),
    data: { preload: false },
  },
  {
    path: 'iq',
    loadChildren: () => import('./iq/iq.module').then((m) => m.IqModule),
    data: { preload: false },
  },
  {
    path: 'pq',
    loadChildren: () => import('./pq/pq.module').then((m) => m.PqModule),
    data: { preload: false },
  },
  {
    path: 'oq',
    loadChildren: () => import('./oq/oq.module').then((m) => m.OqModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [DashboardComponent, QualificationRequestComponent, EquipmentVendorComponent, ApprovalComponent, RequalificationComponent, HubComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class EquipQualificationModule { }
