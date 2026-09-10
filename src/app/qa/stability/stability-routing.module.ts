import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { AllocationComponent } from './allocation/allocation.component';
import { CalenderComponent } from './calender/calender.component';
import { ChargingComponent } from './charging/charging.component';
import { ChemberComponent } from './chember/chember.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DeviationComponent } from './deviation/deviation.component';
// import { InitiateComponent } from './initiate/initiate.component';
import { SampleChargingComponent } from './sample-charging/sample-charging.component';
import { SamplingAllocationComponent } from './sampling-allocation/sampling-allocation.component';
import { SamplingFormComponent } from './sampling-form/sampling-form.component';
import { SamplingComponent } from './sampling/sampling.component';
import { ScheduleComponent } from './schedule/schedule.component';
import { SummaryComponent } from './summary/summary.component';
import { TestingComponent } from './testing/testing.component';
import { TrendComponent } from './trend/trend.component';
import { LogsandreportComponent } from './logsandreport/logsandreport.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'testing', component: TestingComponent},
  // { path: 'initiate', component: InitiateComponent},
  { path: 'schedule', component: ScheduleComponent},
  { path: 'sample-charging', component: SampleChargingComponent},
  { path: 'chember', component: ChemberComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'sampling', component: SamplingComponent},
  { path: 'charging', component: ChargingComponent},
  { path: 'deviation', component: DeviationComponent},
  { path: 'sampling-allocation', component: SamplingAllocationComponent},
  { path: 'sampling-form', component: SamplingFormComponent},
  { path: 'summary', component: SummaryComponent},
  { path: 'calender', component: CalenderComponent},
  { path: 'trend', component: TrendComponent},
  { path: 'logsandreport', component: LogsandreportComponent},
  { path: 'initiate', loadChildren: () => import('./initiate/initiate.module').then(m=>m.InitiateModule), data: {preload: false}}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class StabilityRoutingModule { }
