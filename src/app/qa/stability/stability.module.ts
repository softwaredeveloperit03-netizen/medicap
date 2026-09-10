import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { StabilityRoutingModule } from './stability-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { TestingComponent } from './testing/testing.component';
import { InitiateComponent } from './initiate/initiate.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ScheduleComponent } from './schedule/schedule.component';
import { SampleChargingComponent } from './sample-charging/sample-charging.component';
import { ChemberComponent } from './chember/chember.component';
import { AllocationComponent } from './allocation/allocation.component';
import { SamplingComponent } from './sampling/sampling.component';
import { WithdrawalComponent } from './withdrawal/withdrawal.component';
import { ChargingComponent } from './charging/charging.component';
import { DeviationComponent } from './deviation/deviation.component';
import { SamplingAllocationComponent } from './sampling-allocation/sampling-allocation.component';
import { SamplingFormComponent } from './sampling-form/sampling-form.component';
import { SummaryComponent } from './summary/summary.component';
import { CalenderComponent } from './calender/calender.component';
import { TrendComponent } from './trend/trend.component';
import { LogsandreportComponent } from './logsandreport/logsandreport.component';
import { TranslateModule } from '@ngx-translate/core';

// import { NgxChartsModule } from '@swimlane/ngx-charts';


@NgModule({
  declarations: [DashboardComponent, TestingComponent, InitiateComponent, ScheduleComponent, SampleChargingComponent, ChemberComponent, AllocationComponent, SamplingComponent, WithdrawalComponent, ChargingComponent, DeviationComponent, SamplingAllocationComponent, SamplingFormComponent, SummaryComponent, CalenderComponent, TrendComponent, LogsandreportComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    StabilityRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    // NgxChartsModule
  ]
})
export class StabilityModule { }
