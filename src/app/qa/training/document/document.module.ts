import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { ScheduleComponent } from './schedule/schedule.component';
import { AttendanceComponent } from './attendance/attendance.component';
import { EvaluationComponent } from './evaluation/evaluation.component';
import { LogComponent } from './log/log.component';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
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
    ScheduleComponent,
    AttendanceComponent,
    EvaluationComponent,
    LogComponent,
    NewComponent,
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes) 
  ]
})
export class DocumentModule { }
