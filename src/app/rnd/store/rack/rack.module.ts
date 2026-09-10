import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { LocationChartComponent } from './location-chart/location-chart.component';
import { AllocationComponent } from './allocation/allocation.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
  { path: 'location-chart', component: LocationChartComponent},
  { path: 'allocation', component: AllocationComponent}
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ApprovalComponent, LogComponent, LocationChartComponent, AllocationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class RackModule { }
