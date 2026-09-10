import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { AllocationComponent } from './allocation/allocation.component';
import { CalenderComponent } from './calender/calender.component';
import { ChargingComponent } from './charging/charging.component';
import { ChemberComponent } from './chember/chember.component';
import { DeviationComponent } from './deviation/deviation.component';
import { SamplingComponent } from './sampling/sampling.component';
import { ScheduleComponent } from './schedule/schedule.component';
import { SummaryComponent } from './summary/summary.component';
import { TestingComponent } from './testing/testing.component';
import { TrendComponent } from './trend/trend.component';
import { InitiateComponent } from './initiate/initiate.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'calender', component: CalenderComponent},
  { path: 'charging', component: ChargingComponent},
  { path: 'chember', component: ChemberComponent},
  { path: 'chember', component: ChemberComponent},
  { path: 'sampling', component: SamplingComponent},
  { path: 'schedule', component: ScheduleComponent},
  { path: 'summary', component: SummaryComponent},
  { path: 'testing', component: TestingComponent},
  { path: 'trend', component: TrendComponent},
  { path: 'initiate', component: InitiateComponent},

 ];


@NgModule({
  declarations: [
    DashboardComponent,
    AllocationComponent,
    CalenderComponent,
    ChargingComponent,
    ChemberComponent,
    DeviationComponent,
    InitiateComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class StabilityModule { }
