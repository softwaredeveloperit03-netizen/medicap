import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { Routes, RouterModule } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { NgModule } from '@angular/core';

import { DashboardComponent } from './dashboard/dashboard.component';
import { MasterCheckListComponent } from './master-checklist/master-checklist.component';
import { RevisionComponent } from './revision/revision.component';
import { MonitoringComponent } from './monitoring/monitoring.component';
import { MemoComponent } from './memo/memo.component';
import { ReportComponent } from './report/report.component';
import { ComplianceComponent } from './compliance/compliance.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'master-checklist', component: MasterCheckListComponent},
  { path: 'revision', component: RevisionComponent},
  { path: 'monitoring', component: MonitoringComponent },
  { path: 'memo', component: MemoComponent },
  { path: 'report', component: ReportComponent },
  { path: 'compliance', component: ComplianceComponent }
];

@NgModule({
  declarations: [
    DashboardComponent,
    MasterCheckListComponent,
    RevisionComponent,
    MonitoringComponent,
    MemoComponent,
    ReportComponent,
    ComplianceComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    FormsModule,
    CommonModule,
    ReactiveFormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class GMPMonitoringModule { }
