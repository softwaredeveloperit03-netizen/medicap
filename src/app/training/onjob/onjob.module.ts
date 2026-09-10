import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { IdentificationComponent } from './identification/identification.component';
import { AttendanceComponent } from './attendance/attendance.component';
import { AnnocementComponent } from './annocement/annocement.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { ScheduleComponent } from './schedule/schedule.component';
import { QuestionariesComponent } from './questionaries/questionaries.component';
import { FeedbackComponent } from './feedback/feedback.component';
import { TrainComponent } from './train/train.component';
import { HoldComponent } from './hold/hold.component';
import { RejectComponent } from './reject/reject.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  {path: 'attendance', component: AttendanceComponent},
  { path: 'identification', component: IdentificationComponent},
  { path: 'annocement', component: AnnocementComponent},
  { path: 'schedule', component: ScheduleComponent},
  {path: 'questions', component: QuestionariesComponent},
  {path: 'feedback', component: FeedbackComponent},
  {path: 'train', component: TrainComponent},
  {path: 'hold', component: HoldComponent},
  {path: 'rejected', component: RejectComponent},

  {path: 'log', component: LogComponent}
 ];

@NgModule({
  declarations: [
    DashboardComponent,
    IdentificationComponent,
    AttendanceComponent,
    LogComponent,
    AnnocementComponent,
    ScheduleComponent,
    QuestionariesComponent,
    FeedbackComponent,
    TrainComponent,
    HoldComponent,
    RejectComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class OnjobModule { }
