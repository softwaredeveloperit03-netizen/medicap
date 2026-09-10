import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AnnouncementComponent } from './announcement/announcement.component';
import { StartComponent } from './start/start.component';
import { InprocessComponent } from './inprocess/inprocess.component';
import { LogComponent } from './log/log.component';
import { MomViewComponent } from './mom-view/mom-view.component';
import { AssessmentDashboardComponent } from './assessment/assessment-dashboard.component';
import { AssessmentNewComponent } from './assessment/assessment-new.component';
import { AssessmentApprovalComponent } from './assessment/assessment-approval.component';
import { AssessmentLogComponent } from './assessment/assessment-log.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'announcement', component: AnnouncementComponent },
  { path: 'start', component: StartComponent },
  { path: 'inprocess', component: InprocessComponent },
  { path: 'mom-view', component: MomViewComponent },
  { path: 'log', component: LogComponent },
  { path: 'assessment', component: AssessmentDashboardComponent },
  { path: 'assessment/new', component: AssessmentNewComponent },
  { path: 'assessment/approval', component: AssessmentApprovalComponent },
  { path: 'assessment/log', component: AssessmentLogComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    AnnouncementComponent,
    StartComponent,
    InprocessComponent,
    MomViewComponent,
    LogComponent,
    AssessmentDashboardComponent,
    AssessmentNewComponent,
    AssessmentApprovalComponent,
    AssessmentLogComponent,
  ],
  imports: [SharedModule, TranslateModule, CommonModule, FormsModule, ClarityModule, RouterModule.forChild(routes)],
})
export class ManagementReviewQualitySystemsModule {}
