import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ChngcontrolQAComponent } from './chngcontrol-qa/chngcontrol-qa.component';
import { CcPrimaryReviewComponent } from './cc-primary-review/cc-primary-review.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ApprovalofchangeComponent } from './approvalofchange/approvalofchange.component';
import { MonitoringfollowupComponent } from './monitoringfollowup/monitoringfollowup.component';
import { ClosedchangeqaheadComponent } from './closedchangeqahead/closedchangeqahead.component';
import { QAccCommentComponent } from './qacc-comment/qacc-comment.component';
import { ACTIONPLANAPPROVALComponent } from './action-plan-approval/action-plan-approval.component';
import { QaHeadReviewComponent } from './qa-head-review/qa-head-review.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'deptHead', component: CcPrimaryReviewComponent },
  { path: 'qa', component: ChngcontrolQAComponent },
  { path: 'approvalOfChangeByQaHead', component: ApprovalofchangeComponent },
  { path: 'Monitoring', component: MonitoringfollowupComponent },
  { path: 'closedByQaHead', component: ClosedchangeqaheadComponent },
  { path: 'ccVeriAndEff', component: QAccCommentComponent },
  { path: 'Actionplan', component: ACTIONPLANAPPROVALComponent },
  { path: 'qaHeadReview', component: QaHeadReviewComponent },
];

@NgModule({
  declarations: [
    DashboardComponent, ChngcontrolQAComponent, CcPrimaryReviewComponent, ApprovalofchangeComponent, MonitoringfollowupComponent, ClosedchangeqaheadComponent, QAccCommentComponent, ACTIONPLANAPPROVALComponent, QaHeadReviewComponent
  ],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ]
})
export class CcModule { }
