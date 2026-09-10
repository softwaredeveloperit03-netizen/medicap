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
  { path: 'qc-manager', component: WorkflowListComponent, data: { step: 'qc-manager', status: 'pending_qc_manager', title: 'QC Manager Approval' } },
  { path: 'qc-manager/:id', component: WorkflowDetailComponent, data: { step: 'qc-manager' } },
  { path: 'qa-approval', component: WorkflowListComponent, data: { step: 'qa-approval', status: 'pending_qa_approval', title: 'QA Approval' } },
  { path: 'qa-approval/:id', component: WorkflowDetailComponent, data: { step: 'qa-approval' } },
  { path: 'production', component: WorkflowListComponent, data: { step: 'production', status: 'pending_production', title: 'Production Sampling' } },
  { path: 'production/:id', component: WorkflowDetailComponent, data: { step: 'production' } },
  { path: 'qc-receive', component: WorkflowListComponent, data: { step: 'qc-receive', status: 'pending_qc_receive', title: 'QC Sample Receiving' } },
  { path: 'qc-receive/:id', component: WorkflowDetailComponent, data: { step: 'qc-receive' } },
  { path: 'results', component: WorkflowListComponent, data: { step: 'results', status: 'pending_results', title: 'Test Results Entry' } },
  { path: 'results/:id', component: WorkflowDetailComponent, data: { step: 'results' } },
  { path: 'coa-approval', component: WorkflowListComponent, data: { step: 'coa-approval', status: 'pending_coa_approval', title: 'C of A Approval' } },
  { path: 'coa-approval/:id', component: WorkflowDetailComponent, data: { step: 'coa-approval' } },
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
export class FinishedProductSamplingModule {}
