import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { DashboardComponent } from './dashboard/dashboard.component';
import { StageStepComponent } from './stage-step/stage-step.component';
import { InprocessChecksComponent } from './inprocess-checks/inprocess-checks.component';
import { CheckpointsComponent } from './checkpoints/checkpoints.component';
import { IpqcSpecComponent } from './ipqc-spec/ipqc-spec.component';
import { WorkAllocationComponent } from './work-allocation/work-allocation.component';
import { ProfilesComponent } from './profiles/profiles.component';
import { BuilderComponent } from './builder/builder.component';
import { BatchesComponent } from './batches/batches.component';
import { ExecutionComponent } from './execution/execution.component';
import { ReportsComponent } from './reports/reports.component';
import { ReportComponent } from './report/report.component';
import { ConfigMasterComponent } from './config-master/config-master.component';
import { ConfigStageStepComponent } from './config-stage-step/config-stage-step.component';
import { MapProductComponent } from './map-product/map-product.component';
import { FormMastersComponent } from './form-masters/form-masters.component';
import { ProcedureMasterComponent } from './procedure-master/procedure-master.component';
import { TableFormMasterComponent } from './table-form-master/table-form-master.component';
import { BmrPrepComponent } from './bmr-prep/bmr-prep.component';
import { EbmrBprSharedModule } from './ebmr-bpr-shared.module';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'config-master', component: ConfigMasterComponent },
  { path: 'config-stage-step', component: ConfigStageStepComponent },
  { path: 'map-product', component: MapProductComponent },
  { path: 'bmr-prep', component: BmrPrepComponent },
  { path: 'form-masters', component: FormMastersComponent },
  { path: 'procedure-master', component: ProcedureMasterComponent },
  { path: 'yield-table-master', component: TableFormMasterComponent, data: { tableType: 'yield' } },
  { path: 'weighing-table-master', component: TableFormMasterComponent, data: { tableType: 'weighing' } },
  { path: 'stage-step', component: StageStepComponent },
  { path: 'inprocess-checks', component: InprocessChecksComponent },
  { path: 'checkpoints', component: CheckpointsComponent },
  { path: 'ipqc-spec', component: IpqcSpecComponent },
  { path: 'work-allocation', component: WorkAllocationComponent },
  { path: 'profiles', component: ProfilesComponent },
  { path: 'builder/:id', component: BuilderComponent },
  { path: 'batches', component: BatchesComponent },
  { path: 'execution/:id', component: ExecutionComponent },
  { path: 'reports', component: ReportsComponent },
  { path: 'report/:id', component: ReportComponent },
];

@NgModule({
  imports: [EbmrBprSharedModule, RouterModule.forChild(routes)],
  exports: [EbmrBprSharedModule],
})
export class EbmrBprModule {}
