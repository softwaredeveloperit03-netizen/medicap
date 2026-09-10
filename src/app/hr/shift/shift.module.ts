import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { AllocationComponent } from './allocation/allocation.component';
import { ScheduleComponent } from './schedule/schedule.component';
import { NewComponent } from './new/new.component';
import { ShiftapprComponent } from './shiftappr/shiftappr.component';
import { SeeoffComponent } from './seeoff/seeoff.component';
import { LogComponent } from './log/log.component';
import { CheckshiftComponent } from './checkshift/checkshift.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'schedule', component: ScheduleComponent},
  { path: 'Shiftappr', component: ShiftapprComponent},
  { path: 'checkshift', component: CheckshiftComponent},
  { path: 'seeoff', component: SeeoffComponent},
  { path: 'log', component: LogComponent},
  { path: 'request', loadChildren: () => import('./request/request.module').then(m=>m.RequestModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, AllocationComponent, ScheduleComponent, ShiftapprComponent, SeeoffComponent, LogComponent, CheckshiftComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ShiftModule { }
