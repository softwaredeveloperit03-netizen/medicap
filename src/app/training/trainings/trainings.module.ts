import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { IdentificationComponent } from './identification/identification.component';
import { ScheduleComponent } from './schedule/schedule.component';
import { AnnouncementComponent } from './announcement/announcement.component';
import { AttendanceComponent } from './attendance/attendance.component';
import { LogComponent } from './log/log.component';
import { FeedbackComponent } from './feedback/feedback.component';
import { QuestioneriesComponent } from './questioneries/questioneries.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';






const routes: Routes = [
  { path: '', component: DashboardComponent},
  {path: 'attendance', component: AttendanceComponent},
  { path: 'identification', component: IdentificationComponent},
  { path: 'annocement', component: AnnouncementComponent},
  { path: 'schedule', component: ScheduleComponent},
  {path: 'questions', component: QuestioneriesComponent},
  {path: 'feedback', component: FeedbackComponent},
  {path: 'log', component: LogComponent}
 ];


@NgModule({
  declarations: [
    DashboardComponent,
    IdentificationComponent,
    ScheduleComponent,
    AnnouncementComponent,
    AttendanceComponent,
    LogComponent,
    FeedbackComponent,
    QuestioneriesComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TrainingsModule { }
