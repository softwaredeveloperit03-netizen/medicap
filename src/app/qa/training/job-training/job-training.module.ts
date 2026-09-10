import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { AnnoncementComponent } from './annoncement/annoncement.component';
import { AttendanceComponent } from './attendance/attendance.component';
import { EvaluationComponent } from './evaluation/evaluation.component';
import { ReportComponent } from './report/report.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'annoncement', component: AnnoncementComponent},
  { path: 'attendance', component: AttendanceComponent},
  { path: 'evaluation', component: EvaluationComponent},
  { path: 'report', component: ReportComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    AnnoncementComponent,
    AttendanceComponent,
    EvaluationComponent,
    ReportComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class JobTrainingModule { }
