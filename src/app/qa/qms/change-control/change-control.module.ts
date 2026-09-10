import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { ReviewComponent } from './review/review.component';
import { ImplementationComponent } from './implementation/implementation.component';
import { ActionComponent } from './action/action.component';
import { DepreviewComponent } from './depreview/depreview.component';
import { DeptreviewComponent } from './deptreview/deptreview.component';
import { ClosureComponent } from './closure/closure.component';
import { AssmentbyqaComponent } from './assmentbyqa/assmentbyqa.component';
import { ImpactreguComponent } from './impactregu/impactregu.component';
import { ActionreviewqaComponent } from './actionreviewqa/actionreviewqa.component';
import { QaassementcheckComponent } from './qaassementcheck/qaassementcheck.component';
import { ClosinchecklistComponent } from './closinchecklist/closinchecklist.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'deptConAndRev', component: DeptreviewComponent },
  { path: 'action', component: ActionComponent },
  { path: 'consernAndReview', component: DepreviewComponent },
  { path: 'log', component: LogComponent },
  { path: 'approvalOfChange', component: ReviewComponent },
  { path: 'implementation', component: ImplementationComponent },
  { path: 'CC_closure', component: ClosureComponent },
  { path: 'assessmentByQa', component: AssmentbyqaComponent },
  { path: 'impactRegulatory', component: ImpactreguComponent },
  { path: 'actionAndReviewByQA', component: ActionreviewqaComponent },
  { path: 'qaAssementChecklist', component: QaassementcheckComponent },
  { path: 'closinChecklist', component: ClosinchecklistComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    LogComponent,
    ReviewComponent,
    ImplementationComponent,
    ActionComponent,
    DepreviewComponent,
    DeptreviewComponent,
    ClosureComponent,
    AssmentbyqaComponent,
    ImpactreguComponent,
    ActionreviewqaComponent,
    QaassementcheckComponent,
    ClosinchecklistComponent
  ],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ChangeControlModule { }
