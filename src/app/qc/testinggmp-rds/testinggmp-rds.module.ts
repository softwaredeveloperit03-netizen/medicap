import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { OosreviewComponent } from './oosreview/oosreview.component';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';
// import { HomeComponent } from '../testing/home/home.component';


const routes: Routes = [
  // { path: '', component: HomeComponent},
  { path: '', component: DashboardComponent},
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},
  { path: 'stability', loadChildren: () => import('./stability/stability.module').then(m=>m.StabilityModule), data: {preload: false}},
  { path: 'inprocess', loadChildren: () => import('./inprocess/inprocess.module').then(m=>m.InprocessModule), data: {preload: false}},
   { path: 'finish', loadChildren: () => import('./finish/finish.module').then(m=>m.FinishModule), data: {preload: false}},
  { path: 'oosreview', loadChildren: () => import('./oosreview/oosreview.module').then(m=>m.OosreviewModule), data: {preload: false}},

];


//{ path: 'packing1', loadChildren: () => import('./rdspacking/rdspacking.module').then(m=>m.RdspackingModule), data: {preload: false}},

@NgModule({
  declarations: [DashboardComponent, OosreviewComponent],
  imports: [
    SharedModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    DocsIconsModule,
    TranslateModule,
    RouterModule.forChild(routes)
  ]
})
export class TestinggmpRdsmodule { }
