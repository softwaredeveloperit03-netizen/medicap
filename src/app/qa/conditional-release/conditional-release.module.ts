import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RequestFormComponent } from './request-form/request-form.component';
import { TrackingLogComponent } from './tracking-log/tracking-log.component';
import { QaApprovalComponent } from './qa-approval/qa-approval.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'request/new', component: RequestFormComponent, data: { view: 'new' } },
  { path: 'request/log', component: RequestFormComponent, data: { view: 'log' } },
  { path: 'request/edit/:id', component: RequestFormComponent, data: { view: 'edit' } },
  { path: 'tracking/log', component: TrackingLogComponent },
  { path: 'approval', component: QaApprovalComponent },
];

@NgModule({
  declarations: [DashboardComponent, RequestFormComponent, TrackingLogComponent, QaApprovalComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class ConditionalReleaseModule {}
