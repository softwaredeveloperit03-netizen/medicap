import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { TrendComponent } from './trend/trend.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { SpecificationComponent } from './specification/specification.component';
import { SamplingComponent } from './sampling/sampling.component';
import { SamplingAllocationComponent } from './sampling-allocation/sampling-allocation.component';
import { RouterModule, Routes } from '@angular/router';
import { PlanComponent } from './plan/plan.component';
   
const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'specification', component: SpecificationComponent},
   { path: 'sampling-allocation', component: SamplingAllocationComponent},
  { path: 'sampling', component: SamplingComponent},
  { path: 'testing', redirectTo: 'test/testing', pathMatch: 'full'},
  { path: 'plan', component: PlanComponent},
  { path: 'trend', component: TrendComponent},
  { path: 'points', loadChildren: () => import('./points/points.module').then(m=>m.PointsModule), data: {preload: false}},
  { path: 'test', loadChildren: () => import('./test/test.module').then(m=>m.TestModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, TrendComponent, SpecificationComponent, SamplingComponent, SamplingAllocationComponent, PlanComponent],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class WaterModule { }
