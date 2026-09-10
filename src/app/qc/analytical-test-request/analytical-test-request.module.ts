import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';

import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { WorkflowListComponent } from './workflow-list/workflow-list.component';
import { WorkflowDetailComponent } from './workflow-detail/workflow-detail.component';
import { FormDisplayComponent } from './form-display/form-display.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'log', component: LogComponent },
  { path: 'view/:id', component: WorkflowDetailComponent, data: { mode: 'view' } },
  { path: 'qa-verify', component: WorkflowListComponent, data: { step: 'qa-verify', status: 'pending_qa_verify', title: 'QA Verification (Section A)' } },
  { path: 'qa-verify/:id', component: WorkflowDetailComponent, data: { step: 'qa-verify' } },
  { path: 'production-verify', component: WorkflowListComponent, data: { step: 'production-verify', status: 'pending_production_verify', title: 'Production Verification' } },
  { path: 'production-verify/:id', component: WorkflowDetailComponent, data: { step: 'production-verify' } },
  { path: 'lab-receive', component: WorkflowListComponent, data: { step: 'lab-receive', status: 'pending_lab_receive', title: 'Lab Sample Receiving' } },
  { path: 'lab-receive/:id', component: WorkflowDetailComponent, data: { step: 'lab-receive' } },
  { path: 'analyst', component: WorkflowListComponent, data: { step: 'analyst', status: 'pending_analyst', title: 'Laboratory Analyst' } },
  { path: 'analyst/:id', component: WorkflowDetailComponent, data: { step: 'analyst' } },
  { path: 'alt-method-qa', component: WorkflowListComponent, data: { step: 'alt-method-qa', status: 'pending_alt_method_qa', title: 'Alternative Method QA Approval' } },
  { path: 'alt-method-qa/:id', component: WorkflowDetailComponent, data: { step: 'alt-method-qa' } },
  { path: 'lab-manager', component: WorkflowListComponent, data: { step: 'lab-manager', status: 'pending_lab_manager', title: 'Lab Manager Review' } },
  { path: 'lab-manager/:id', component: WorkflowDetailComponent, data: { step: 'lab-manager' } },
  { path: 'qa-disposition', component: WorkflowListComponent, data: { step: 'qa-disposition', status: 'pending_qa_disposition', title: 'QA Final Disposition' } },
  { path: 'qa-disposition/:id', component: WorkflowDetailComponent, data: { step: 'qa-disposition' } },
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    LogComponent,
    WorkflowListComponent,
    WorkflowDetailComponent,
    FormDisplayComponent,
  ],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    SharedModule,
    TranslateModule,
    DocsIconsModule,
    RouterModule.forChild(routes),
  ],
})
export class AnalyticalTestRequestModule {}
