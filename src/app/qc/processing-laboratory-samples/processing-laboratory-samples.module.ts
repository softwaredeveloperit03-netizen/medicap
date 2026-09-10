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
  { path: 'section-a', component: WorkflowListComponent, data: { step: 'section-a', status: 'pending_section_a', title: 'Section A — Sampling Instructions' } },
  { path: 'section-a/:id', component: WorkflowDetailComponent, data: { step: 'section-a' } },
  { path: 'section-b', component: WorkflowListComponent, data: { step: 'section-b', status: 'pending_section_b', title: 'Section B — Sampling & Inspection' } },
  { path: 'section-b/:id', component: WorkflowDetailComponent, data: { step: 'section-b' } },
  { path: 'lab-receive', component: WorkflowListComponent, data: { step: 'lab-receive', status: 'pending_lab_receive', title: 'Lab Sample Receiving' } },
  { path: 'lab-receive/:id', component: WorkflowDetailComponent, data: { step: 'lab-receive' } },
  { path: 'section-c', component: WorkflowListComponent, data: { step: 'section-c', status: 'pending_section_c', title: 'Section C — QC Review' } },
  { path: 'section-c/:id', component: WorkflowDetailComponent, data: { step: 'section-c' } },
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
export class ProcessingLaboratorySamplesModule {}
