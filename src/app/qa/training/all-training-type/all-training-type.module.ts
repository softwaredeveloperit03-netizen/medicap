import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AnnouncementComponent } from './announcement/announcement.component';
import { AttendanceComponent } from './attendance/attendance.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ReportComponent } from './report/report.component';
import { RouterModule, Routes } from '@angular/router';
import { EvaluationComponent } from '../job-training/evaluation/evaluation.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'announcement', component: AnnouncementComponent},
  { path: 'attendance', component: AttendanceComponent},
  { path: 'evaluation', component: EvaluationComponent},
  { path: 'report', component: ReportComponent},
];

@NgModule({
  declarations: [
    AnnouncementComponent,
    AttendanceComponent,
    DashboardComponent,
    NewComponent,
    ReportComponent,
    EvaluationComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes),
  ]
})
export class AllTrainingTypeModule { }
