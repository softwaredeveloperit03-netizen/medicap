import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { StepwiseReviewComponent } from './stepwise-review/stepwise-review.component';
import { FinalReleaseComponent } from './final-release/final-release.component';
import { QaApprovalComponent } from './qa-approval/qa-approval.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'stepwise/new', component: StepwiseReviewComponent, data: { view: 'new' } },
  { path: 'stepwise/log', component: StepwiseReviewComponent, data: { view: 'log' } },
  { path: 'stepwise/edit/:id', component: StepwiseReviewComponent, data: { view: 'edit' } },
  { path: 'stepwise/view/:id', component: StepwiseReviewComponent, data: { view: 'view' } },
  { path: 'stepwise/qa-review/:id', component: StepwiseReviewComponent, data: { view: 'qa-review' } },
  { path: 'final/new', component: FinalReleaseComponent, data: { view: 'new' } },
  { path: 'final/log', component: FinalReleaseComponent, data: { view: 'log' } },
  { path: 'final/view/:id', component: FinalReleaseComponent, data: { view: 'view' } },
  { path: 'final/qa-review/:id', component: FinalReleaseComponent, data: { view: 'qa-review' } },
  { path: 'approval', component: QaApprovalComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    StepwiseReviewComponent,
    FinalReleaseComponent,
    QaApprovalComponent,
  ],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class StepwiseQaReleaseModule {}
