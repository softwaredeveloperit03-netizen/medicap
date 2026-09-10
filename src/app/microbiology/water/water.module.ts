import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { TrendComponent } from './trend/trend.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { SamplingComponent } from './sampling/sampling.component';
import { TestingComponent } from './testing/testing.component';
import { SamplingAllocationComponent } from './sampling-allocation/sampling-allocation.component';
import { TestCheckingComponent } from './test-checking/test-checking.component';
import { ReportComponent } from './report/report.component';
import { RouterModule, Routes } from '@angular/router';
import { PlanComponent } from './plan/plan.component';
import { COAComponent } from './coa/coa.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'coa', component: COAComponent},
  { path: 'sampling-allocation', component: SamplingAllocationComponent},
  { path: 'sampling', component: SamplingComponent},
  { path: 'testing', component: TestingComponent},
  { path: 'testing-checking', component: TestCheckingComponent},
  { path: 'report', component: ReportComponent},
  { path: 'trend', component: TrendComponent},
  { path: 'plan', component: PlanComponent},
  {path:'specification',loadChildren:()=>import('./specification/specification.module').then(m=>m.SpecificationModule),data:{preload : false}},
  { path: 'points', loadChildren: () => import('./points/points.module').then(m=>m.PointsModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, TrendComponent,SamplingComponent, TestingComponent, SamplingAllocationComponent, TestCheckingComponent, ReportComponent, PlanComponent, COAComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class WaterModule { }
