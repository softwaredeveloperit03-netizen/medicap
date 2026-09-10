import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'stores', loadChildren: () => import('./stores/stores.module').then(m=>m.StoresModule), data: {preload: false}},
  { path: 'dispatch', loadChildren: () => import('./dispatch/dispatch.module').then(m=>m.DispatchModule), data: {preload: false}},
  { path: 'engineering', loadChildren: () => import('./engineering/engineering.module').then(m=>m.EngineeringModule), data: {preload: false}},
  { path: 'qc', loadChildren: () => import('./qc/qc.module').then(m=>m.QcModule), data: {preload: false}},
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
export class SelectAuditModule { }
