import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { IdentificationComponent } from './identification/identification.component';
import { EvaluationComponent } from './evaluation/evaluation.component';
import { AssessmentComponent } from './assessment/assessment.component';
import { ControlComponent } from './control/control.component';
import { CapaComponent } from './capa/capa.component';
import { ReviewComponent } from './review/review.component';
import { AnalysisComponent } from './analysis/analysis.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { LogComponent } from './log/log.component';
import { ApprovalComponent } from './approval/approval.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'identification', component: IdentificationComponent},
  { path: 'evaluation', component: EvaluationComponent},
  { path: 'assessment', component: AssessmentComponent},
  { path: 'control', component: ControlComponent},
  { path: 'capa', component: CapaComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'analysis', component: AnalysisComponent},
  { path: 'log', component: LogComponent},
  { path: 'approval', component: ApprovalComponent},
];

@NgModule({
  declarations: [DashboardComponent, IdentificationComponent, EvaluationComponent, AssessmentComponent, ControlComponent, CapaComponent, ReviewComponent, AnalysisComponent, LogComponent, ApprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class RiskModule { }
