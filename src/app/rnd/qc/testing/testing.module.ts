import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},
  { path: 'packing', loadChildren: () => import('./packing/packing.module').then(m=>m.PackingModule), data: {preload: false}},
  { path: 'stability', loadChildren: () => import('./stability/stability.module').then(m=>m.StabilityModule), data: {preload: false}},
  { path: 'inprocess', loadChildren: () => import('./inprocess/inprocess.module').then(m=>m.InprocessModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class TestingModule { }
