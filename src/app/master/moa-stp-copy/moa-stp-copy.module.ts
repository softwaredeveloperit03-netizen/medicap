import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { TableComponent } from './table/table.component';
import { RevisionComponent } from './revision/revision.component';
import { RevisionLogComponent } from './revision-log/revision-log.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new-moa-stp', component: TableComponent, data: { title: 'MOA Master', view: 'pending' } },
  { path: 'checking', component: TableComponent, data: { title: 'MOA Checking', view: 'checking' } },
  { path: 'approval', component: TableComponent, data: { title: 'MOA Approval', view: 'approval' } },
  { path: 'log', component: TableComponent, data: { title: 'MOA Log', view: 'log' } },
  { path: 'correction', component: TableComponent, data: { title: 'MOA Correction', view: 'correction' } },
  { path: 'revision', component: RevisionComponent },
  { path: 'revision/moa-stp-revision', component: RevisionLogComponent },
  { path: 'revision/revision-status', component: RevisionLogComponent },
  { path: 'revision/revision-history-log', component: RevisionLogComponent },
  { path: 'revision/periodic-review', component: RevisionLogComponent },
  { path: 'revision/training', component: RevisionLogComponent },
  { path: 'revision/implementation', component: RevisionLogComponent },
];

@NgModule({
  declarations: [DashboardComponent, TableComponent, RevisionComponent, RevisionLogComponent],
  imports: [
    SharedModule,CommonModule, FormsModule, ClarityModule, TranslateModule, RouterModule.forChild(routes)],
})
export class MoaStpCopyModule {}

