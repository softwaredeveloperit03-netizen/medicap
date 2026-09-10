import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { TrendComponent } from './trend/trend.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { SpecificationComponent } from './specification/specification.component';
import { SamplingComponent } from './sampling/sampling.component';
import { TestingComponent } from './testing/testing.component';
import { SamplingAllocationComponent } from './sampling-allocation/sampling-allocation.component';
import { TestCheckingComponent } from './test-checking/test-checking.component';
import { ReportComponent } from './report/report.component';
import { RouterModule, Routes } from '@angular/router';
import { PlanComponent } from './plan/plan.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'specification', component: SpecificationComponent},
  { path: 'sampling-allocation', component: SamplingAllocationComponent},
  { path: 'sampling', component: SamplingComponent},
  { path: 'testing', component: TestingComponent},
  { path: 'testing-checking', component: TestCheckingComponent},
  { path: 'report', component: ReportComponent},
  { path: 'trend', component: TrendComponent},
  { path: 'plan', component: PlanComponent}
];

@NgModule({
  declarations: [DashboardComponent, TrendComponent, SpecificationComponent, SamplingComponent, TestingComponent, SamplingAllocationComponent, TestCheckingComponent, ReportComponent, PlanComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class WaterModule { }
