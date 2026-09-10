import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { InitiateComponent } from './initiate/initiate.component';
import { AttachmentComponent } from './attachment/attachment.component';
import { CheckingComponent } from './checking/checking.component';
import { VerifyComponent } from './verify/verify.component';
import { ApprovalComponent } from './approval/approval.component';
import { InvestigationComponent } from './investigation/investigation.component';
import { AssessmentComponent } from './assessment/assessment.component';
import { ActionplanComponent } from './actionplan/actionplan.component';
import { RecommendationComponent } from './recommendation/recommendation.component';
import { EvaluationComponent } from './evaluation/evaluation.component';
import { ClosingComponent } from './closing/closing.component';
import { LogComponent } from './log/log.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { ConditionComponent } from './condition/condition.component';
import {NewlogComponent}  from './newlog/newlog.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'initiate', component: InitiateComponent},
  { path: 'attachment',component:AttachmentComponent},
  { path: 'checking', component:CheckingComponent},
  { path: 'verify', component:VerifyComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'investigation',component:InvestigationComponent},
  { path: 'assessment',component:AssessmentComponent},
  { path: 'action_plan',component:ActionplanComponent},
  { path: 'recommendation',component:RecommendationComponent},
  { path: 'evaluation',component:EvaluationComponent},
  { path: 'closing',component:ClosingComponent},
  { path: 'log',component:LogComponent},
  { path: 'condition',component:ConditionComponent},
  { path: 'newlog',component:NewlogComponent}


];

@NgModule({
  declarations: [DashboardComponent, InitiateComponent, AttachmentComponent,NewlogComponent, CheckingComponent, VerifyComponent, ApprovalComponent, InvestigationComponent, AssessmentComponent, ActionplanComponent, RecommendationComponent, EvaluationComponent, ClosingComponent, LogComponent, ConditionComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class DeviationModule { }
