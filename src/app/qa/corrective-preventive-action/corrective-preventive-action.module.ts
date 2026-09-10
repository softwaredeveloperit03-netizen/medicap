import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ReportComponent } from './report/report.component';
import { DeptApprovalComponent } from './dept-approval/dept-approval.component';
import { QaApprovalComponent } from './qa-approval/qa-approval.component';
import { ClosingComponent } from './closing/closing.component';
import { EffectivenessComponent } from './effectiveness/effectiveness.component';
import { LogComponent } from './log/log.component';

/**
 * SOP-QA-070 Corrective Action / Preventative Action (CAPA)
 * Forms: FQA-070-A (Tracking Log), FQA-070-B (CAPA Report)
 *
 * Flow:
 * 1. Owner initiates FQA-070-B → pending_dept
 * 2. Dept Head approval → pending_qa
 * 3. QA approval + CAPA-XX-DP-YY number → open
 * 4. Owner submits closure proof → pending_closure
 * 5. QA verifies close → closed OR pending_effectiveness
 * 6. Effectiveness check (if required) → effectiveness_complete
 */
const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'report/new', component: ReportComponent, data: { view: 'new' } },
  { path: 'report/log', component: ReportComponent, data: { view: 'log' } },
  { path: 'report/view/:id', component: ReportComponent, data: { view: 'view' } },
  { path: 'report/edit/:id', component: ReportComponent, data: { view: 'edit' } },
  { path: 'dept-approval', component: DeptApprovalComponent },
  { path: 'qa-approval', component: QaApprovalComponent },
  { path: 'closing', component: ClosingComponent },
  { path: 'effectiveness', component: EffectivenessComponent },
  { path: 'log', component: LogComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    ReportComponent,
    DeptApprovalComponent,
    QaApprovalComponent,
    ClosingComponent,
    EffectivenessComponent,
    LogComponent,
  ],
  imports: [SharedModule, TranslateModule, CommonModule, FormsModule, ClarityModule, RouterModule.forChild(routes)],
})
export class CorrectivePreventiveActionModule {}
