import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { ReviewComponent } from './review/review.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { MultiSelectModule } from 'primeng/multiselect';
import { ExternalagencyComponent } from './externalagency/externalagency.component';


import { RootcauseComponent } from './rootcause/rootcause.component';
import { ApprovalsComponent } from './approvals/approvals.component';
import {DocumentComponent} from './document/document.component';
import { RiskassessmentComponent } from './riskassessment/riskassessment.component';
import { DeviationApprovalComponent } from './deviation-approval/deviation-approval.component';
import { CorrectionComponent } from './correction/correction.component';
import { ImpactassimentreviewComponent } from './impactassimentreview/impactassimentreview.component';
import { FinalCommentDeptComponent } from './final-comment-dept/final-comment-dept.component';
import { RaCommentsComponent } from './ra-comments/ra-comments.component';
import { ReviewQaComponent } from './review-qa/review-qa.component';
import { DelayComponent } from './delay/delay.component';
import { DelayApprovalComponent } from './delay-approval/delay-approval.component';
 import { QamanagerdelayapprovalComponent } from './qamanagerdelayapproval/qamanagerdelayapproval.component';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'log', component: LogComponent },
  { path: 'review', component: ReviewComponent },
  { path: 'reviewQa', component: ExternalagencyComponent },
  { path: 'ClosureByQA', component: RiskassessmentComponent },

  { path: 'approvals', component: ApprovalsComponent },
  { path: 'correction', component: CorrectionComponent },
  { path: 'doc_upload', component: DocumentComponent },
  { path: 'rootcause', component: RootcauseComponent },
  { path: 'deviation_aprvl', component: DeviationApprovalComponent },
  { path: 'monitoringFollowup', component: ImpactassimentreviewComponent },
  { path: 'final_comment', component: FinalCommentDeptComponent },
  { path: 'racomment', component: RaCommentsComponent },
  { path: 'Review_qa', component: ReviewQaComponent },
  { path: 'delay', component: DelayComponent },
  { path: 'delay_approval', component: DelayApprovalComponent },
  { path: 'qa_manager_delay_approval', component: QamanagerdelayapprovalComponent },
]; 
;
 

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    LogComponent,
    ReviewComponent,
    RootcauseComponent,
    DocumentComponent,
    ApprovalsComponent,
    DocumentComponent,
    RiskassessmentComponent,
    DeviationApprovalComponent,
    CorrectionComponent,
    ImpactassimentreviewComponent,
    FinalCommentDeptComponent,
    RaCommentsComponent,
    ReviewQaComponent,
    ExternalagencyComponent,
    DelayComponent,
    DelayApprovalComponent,
     QamanagerdelayapprovalComponent
  ],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class DeviationModule { }
