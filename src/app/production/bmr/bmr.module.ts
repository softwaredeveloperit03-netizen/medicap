import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { CompletedComponent } from './completed/completed.component';
import { YieldComponent } from './yield/yield.component';
import { RouterModule, Routes } from '@angular/router';
import { TransferComponent } from './transfer/transfer.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'completed', component: CompletedComponent},
  { path: 'yield', component: YieldComponent},
  { path: 'transfer', component: TransferComponent},
  { path: 'plan', loadChildren: () => import('./plan/plan.module').then(m=>m.PlanModule)},
  { path: 'manufacturing', loadChildren: () => import('./manufacturing/manufacturing.module').then(m=>m.ManufacturingModule)},
  { path: 'sampling', loadChildren: () => import('./sampling/sampling.module').then(m=>m.SamplingModule)},
];

@NgModule({
  declarations: [DashboardComponent, CompletedComponent, YieldComponent, TransferComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class BmrModule { }
