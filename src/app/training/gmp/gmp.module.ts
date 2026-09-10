import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { IdentificationComponent } from './identification/identification.component';
import { FrequencyComponent } from './frequency/frequency.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ScheduleComponent } from './schedule/schedule.component';
import { AnnouncementComponent } from './announcement/announcement.component';
import { AttendanceComponent } from './attendance/attendance.component';
import { LogComponent } from './log/log.component';
import { QuestionariesComponent } from './questionaries/questionaries.component';

import { FeedbackComponent } from './feedback/feedback.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'identification', component: IdentificationComponent},
  { path: 'annocement', component: AnnouncementComponent},
  { path: 'schedule', component: ScheduleComponent},
  {path: 'questions', component: QuestionariesComponent},
   {path: 'feedback', component: FeedbackComponent},
   {path: 'attendance', component: AttendanceComponent},
   {path: 'log', component: LogComponent}
 ];


@NgModule({
  declarations: [
    DashboardComponent,
    IdentificationComponent,
    FrequencyComponent,
    ScheduleComponent,
    AnnouncementComponent,
    QuestionariesComponent,
    AttendanceComponent,
    FeedbackComponent,
    LogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class GmpModule { }
