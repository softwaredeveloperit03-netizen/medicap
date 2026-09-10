import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { EvaluationComponent } from './evaluation/evaluation.component';
import { AttendanceComponent } from './attendance/attendance.component';
import { ScheduleComponent } from './schedule/schedule.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'annoncement', component: ScheduleComponent},
  { path: 'attendance', component: AttendanceComponent},
  { path: 'evaluation', component: EvaluationComponent},
  { path: 'log', component: LogComponent},
];

@NgModule({
  declarations: [
    NewComponent,
    LogComponent,
    EvaluationComponent,
    AttendanceComponent,
    ScheduleComponent,
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)  ]
})
export class NeedBaseModule { }
