import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { HomeComponent } from './home/home.component';
import { IdentificationComponent } from './identification/identification.component';
import { ScheduleComponent } from './schedule/schedule.component';
import { AttendanceComponent } from './attendance/attendance.component';
import { IndividualComponent } from './individual/individual.component';
import { SelfCertificateComponent } from './self-certificate/self-certificate.component';
import { LogComponent } from './log/log.component';
import { InductionComponent } from './induction/induction.component';
import { FeedbackComponent } from './feedback/feedback.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
 import { NewTrainingAttendanceComponent } from './new-training-attendance/new-training-attendance.component';
import { TrainingAttendanceLogComponent } from './training-attendance-log/training-attendance-log.component';
import { TrainingActivityComponent } from './training-activity/training-activity.component';
import { TrainingLogActivityComponent } from './training-log-activity/training-log-activity.component';
import { TrainingScheduleLogComponent } from './training-schedule-log/training-schedule-log.component';
import { TrainingAttendanceComponent } from './training-attendance/training-attendance.component';
import { SelfLearnSubDashComponent } from './self-learn-sub-dash/self-learn-sub-dash.component';
import { SelfLearningCardsComponent } from './self-learning-cards/self-learning-cards.component';
import { OjtlogComponent } from './ojtlog/ojtlog.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', component: HomeComponent},
  {path: 'identification', component: IdentificationComponent},
  {path: 'schedule', component: ScheduleComponent},
  {path: 'attendance', component: AttendanceComponent},
  {path: 'individual', component: IndividualComponent},
  {path: 'self-certificate', component: SelfCertificateComponent},
  {path: 'log', component: LogComponent},
  {path: 'induction', component: InductionComponent},
  { path: 'feedback', component: FeedbackComponent },
  { path: 'new-training-attendance', component: NewTrainingAttendanceComponent },
  { path: 'training-attendance-log', component: TrainingAttendanceLogComponent },
  { path: 'ojtLog', component: OjtlogComponent },

  {path: 'training-activity', component:TrainingActivityComponent},
  {path: 'self-learn-sub-dash', component: SelfLearnSubDashComponent},
  {path: 'self-learning-cards', component: SelfLearningCardsComponent},
  {path: 'training-attendance', component: TrainingAttendanceComponent},
  {path: 'training-schedule-log', component: TrainingScheduleLogComponent},
  {path: 'training-log-activity', component: TrainingLogActivityComponent},

  { path: 'topic', loadChildren: () => import('./topic/topic.module').then(m=>m.TopicModule)},
  { path: 'evaluate', loadChildren: () => import('./evaluate/evaluate.module').then(m=>m.EvaluateModule)},
  { path: 'daily', loadChildren: () => import('./daily/daily.module').then(m=>m.DailyModule)},
  { path: 'trainers', loadChildren: () => import('./trainers/trainers.module').then(m=>m.TrainersModule)},
  { path: 'retraining', loadChildren: () => import('./retraining/retraining.module').then(m=>m.RetrainingModule)},
  { path: 'job-training', loadChildren: () => import('./job-training/job-training.module').then(m=>m.JobTrainingModule)},
  { path: 'document', loadChildren: () => import('./document/document.module').then(m=>m.DocumentModule)},
  { path: 'need', loadChildren: () => import('./need-base/need-base.module').then(m=>m.NeedBaseModule)},
  { path: 'qms', loadChildren: () => import('./qmst/qmst.module').then(m=>m.QmstModule)},
  { path: 'certificate', loadChildren: () => import('./certificate/certificate.module').then(m=>m.CertificateModule)},
  { path: 'schedule-training', loadChildren: () => import('./schedule-training/schedule-training.module').then(m=>m.ScheduleTrainingModule)},
  { path: 'self-learning', loadChildren: () => import('./self-learning/self-learning.module').then(m=>m.SelfLearningModule)},
  { path: 'all-training-type', loadChildren: () => import('./all-training-type/all-training-type.module').then(m=>m.AllTrainingTypeModule)},
];

@NgModule({
  declarations: [
    HomeComponent,
    IdentificationComponent,
    ScheduleComponent,
    AttendanceComponent,
    IndividualComponent,
    SelfCertificateComponent,
    LogComponent,
    InductionComponent,
    FeedbackComponent,
    DashboardComponent,
     NewTrainingAttendanceComponent,
    TrainingAttendanceLogComponent,
    TrainingActivityComponent,
    SelfLearnSubDashComponent,
    SelfLearningCardsComponent,
    TrainingAttendanceComponent,
    TrainingScheduleLogComponent,
    TrainingLogActivityComponent,
    OjtlogComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes),
  ],
})
export class TrainingModule {}
