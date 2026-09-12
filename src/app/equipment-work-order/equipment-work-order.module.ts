import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewFormComponent } from './new-form/new-form.component';
import { EnggReviewComponent } from './engg-review/engg-review.component';
import { PartBComponent } from './part-b/part-b.component';
import { VerifyComponent } from './verify/verify.component';
import { QaApprovalComponent } from './qa-approval/qa-approval.component';
import { LogComponent } from './log/log.component';
import { IssuanceLogComponent } from './issuance-log/issuance-log.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewFormComponent },
  { path: 'engg-review', component: EnggReviewComponent },
  { path: 'work-performed', component: PartBComponent },
  { path: 'part-b', component: PartBComponent },
  { path: 'interior-checklist', redirectTo: 'work-performed', pathMatch: 'full' },
  { path: 'exterior-checklist', redirectTo: 'work-performed', pathMatch: 'full' },
  { path: 'verification', component: VerifyComponent },
  { path: 'verify', component: VerifyComponent },
  { path: 'qa-approval', component: QaApprovalComponent },
  { path: 'log', component: LogComponent },
  { path: 'issuance-log', component: IssuanceLogComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewFormComponent,
    EnggReviewComponent,
    PartBComponent,
    VerifyComponent,
    QaApprovalComponent,
    LogComponent,
    IssuanceLogComponent,
  ],
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    TranslateModule,
    SharedModule,
    RouterModule.forChild(routes),
  ],
})
export class EquipmentWorkOrderModule {}
