import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { CheckingComponent } from './checking/checking.component';
import { Review1Component } from './review1/review1.component';
import { Review2Component } from './review2/review2.component';
import { Review3Component } from './review3/review3.component';
import { ExtensionComponent } from './extension/extension.component';
import { ClosingComponent } from './closing/closing.component';
import { Approve1Component } from './approve1/approve1.component';
import { Approve2Component } from './approve2/approve2.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { VerificationComponent } from './verification/verification.component';
import { Closing1Component } from './closing1/closing1.component';
import { Verification1Component } from './verification1/verification1.component';
import { PlanDeptHeadComponent } from './plan-dept-head/plan-dept-head.component';
import { HeadApprovalComponent } from './head-approval/head-approval.component';
import { PlanVerificationComponent } from './plan-verification/plan-verification.component';
import { PlanHeadApprovalComponent } from './plan-head-approval/plan-head-approval.component';
import { ClosurePlanComponent } from './closure-plan/closure-plan.component';
import { ClosureApprovalComponent } from './closure-approval/closure-approval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'review1', component: Review1Component},
  { path: 'review2', component: Review2Component},
  { path: 'review3', component: Review3Component},
  { path: 'extension', component: ExtensionComponent},
  { path: 'approve1', component: Approve1Component},
  { path: 'approve2', component: Approve2Component},
  { path: 'closing', component: ClosingComponent},
  { path: 'log',component:LogComponent},
  { path: 'verification',component:VerificationComponent},
  { path: 'closing1', component: Closing1Component},
  { path: 'verification1', component: Verification1Component},
  { path: 'plan-dept-head', component: PlanDeptHeadComponent},
  { path: 'head-approval', component: HeadApprovalComponent},
  { path: 'plan-verification', component: PlanVerificationComponent},
  { path: 'plan-head-approval', component: PlanHeadApprovalComponent},
  { path: 'closure-plan', component: ClosurePlanComponent},
  { path: 'closure-approval', component: ClosureApprovalComponent},
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, CheckingComponent, Review1Component, Review2Component, Review3Component, ExtensionComponent, ClosingComponent, Approve1Component, 
    Approve2Component, LogComponent, VerificationComponent, Closing1Component,Verification1Component, PlanDeptHeadComponent,
     HeadApprovalComponent, PlanVerificationComponent,PlanHeadApprovalComponent,ClosurePlanComponent,ClosureApprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CapaModule { }
