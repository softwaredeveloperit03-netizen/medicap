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
  { path: 'qc-receive', component: WorkflowListComponent, data: { step: 'qc-receive', status: 'pending_qc_receive', title: 'QC Sample Receiving' } },
  { path: 'qc-receive/:id', component: WorkflowDetailComponent, data: { step: 'qc-receive' } },
  { path: 'testing', component: WorkflowListComponent, data: { step: 'testing', status: 'pending_testing', title: 'Sample Testing' } },
  { path: 'testing/:id', component: WorkflowDetailComponent, data: { step: 'testing' } },
  { path: 'qc-review', component: WorkflowListComponent, data: { step: 'qc-review', status: 'pending_qc_review', title: 'QC Review of Raw Data' } },
  { path: 'qc-review/:id', component: WorkflowDetailComponent, data: { step: 'qc-review' } },
  { path: 'analyst-entry', component: WorkflowListComponent, data: { step: 'analyst-entry', status: 'pending_analyst_entry', title: 'Analyst Results Entry' } },
  { path: 'analyst-entry/:id', component: WorkflowDetailComponent, data: { step: 'analyst-entry' } },
  { path: 'qc-approval', component: WorkflowListComponent, data: { step: 'qc-approval', status: 'pending_qc_approval', title: 'QC Management Approval' } },
  { path: 'qc-approval/:id', component: WorkflowDetailComponent, data: { step: 'qc-approval' } },
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
export class InProcessProductSamplingModule {}
