import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { HttpClientModule } from '@angular/common/http';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', component: DashboardComponent},
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},
  { path: 'packing', loadChildren: () => import('./packing/packing.module').then(m=>m.PackingModule), data: {preload: false}},
  { path: 'finish', loadChildren: () => import('./finish/finish.module').then(m=>m.FinishModule), data: {preload: false}},
  { path: 'inprocess', loadChildren: () => import('./inprocess/inprocess.module').then(m=>m.InprocessModule), data: {preload: false}},
  { path: 'retest', loadChildren: () => import('./retest/retest.module').then(m=>m.RetestModule), data: {preload: false}},
  { path: 'stability', loadChildren: () => import('./stability/stability.module').then(m=>m.StabilityModule), data: {preload: false}},
  { path: 'water', loadChildren: () => import('./water/water.module').then(m=>m.WaterModule), data: {preload: false}},
  { path: 'checking', loadChildren: () => import('./checking/checking.module').then(m=>m.CheckingModule), data: {preload: false}},
];

@NgModule({
  declarations: [
    DashboardComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    HttpClientModule,
    RouterModule.forChild(routes)
  ],
})
export class SpecificationsModule { }
