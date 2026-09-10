import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { HttpClientModule } from '@angular/common/http';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TrasferComponent } from './trasfer/trasfer.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', component: DashboardComponent},
  {path: 'transfer', component: TrasferComponent},
  { path: 'plan', loadChildren: () => import('./plan/plan.module').then(m=>m.PlanModule), data: {preload: false}},
  { path: 'batch', loadChildren: () => import('./batch/batch.module').then(m=>m.BatchModule), data: {preload: false}},
  { path: 'sampling', loadChildren: () => import('./sampling/sampling.module').then(m=>m.SamplingModule), data: {preload: false}}
];

@NgModule({
  declarations: [DashboardComponent, TrasferComponent, ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    HttpClientModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class LmrModule { }
