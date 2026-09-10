import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
// import { RequisitionComponent } from './requisition/requisition.component';
import { DistributionComponent } from './distribution/distribution.component';
import { LogbookComponent } from './logbook/logbook.component';
import { ProcessComponent } from './process/process.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  // { path: 'requisition', component: RequisitionComponent},
  // { path: 'distribution', component: DistributionComponent},
  { path: 'logbook', component: LogbookComponent},
  { path: 'process', component: ProcessComponent},
  { path: 'requisition', loadChildren: () => import('./requisition/requisition.module').then(m=>m.RequisitionModule), data: {preload: false}},
  { path: 'distribution', loadChildren: () => import('./distribution/distribution.module').then(m=>m.DistributionModule), data: {preload: false}},
  { path: 'retrival', loadChildren: () => import('./retrival/retrival.module').then(m=>m.RetrivalModule), data: {preload: false}},
  { path: 'index', loadChildren: () => import('./index_master/index_master.module').then(m=>m.Index_masterModule), data: {preload: false}},
  { path: 'dist_retrival', loadChildren: () => import('./distribution_retrival/distribution_retrival.module').then(m=>m.Distribution_retrivalModule), data: {preload: false}},
  
];

@NgModule({
  declarations: [DashboardComponent, DistributionComponent, LogbookComponent, ProcessComponent],
  imports: [ TranslateModule,
    CommonModule,
    RouterModule.forChild(routes)
  ]
})
export class DocumentModule { }
