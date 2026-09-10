import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { QuillModule } from 'ngx-quill';

import { SharedModule } from 'src/app/shared/shared.module';
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
import { ProcedureDraftEditorComponent } from './shared/procedure-draft-editor/procedure-draft-editor.component';
import { ProductApprovalPageComponent } from './shared/product-approval-page/product-approval-page.component';
import { SafetyPrecautionsComponent } from './shared/safety-precautions/safety-precautions.component';
import { GeneralInstructionsPageComponent } from './shared/general-instructions-page/general-instructions-page.component';
import { TableFormMasterComponent } from './table-form-master/table-form-master.component';
import { BmrPrepComponent } from './bmr-prep/bmr-prep.component';
import { StageStepIndexTableComponent } from './shared/stage-step-index-table/stage-step-index-table.component';
import { MasterFinalHtmlBmrComponent } from './shared/master-final-html-bmr/master-final-html-bmr.component';
import { QmsEmbedFormsModule } from './qms-embed/qms-embed-forms.module';

const COMPONENTS = [
  DashboardComponent,
  StageStepComponent,
  InprocessChecksComponent,
  CheckpointsComponent,
  IpqcSpecComponent,
  WorkAllocationComponent,
  ProfilesComponent,
  BuilderComponent,
  BatchesComponent,
  ExecutionComponent,
  ReportsComponent,
  ReportComponent,
  ConfigMasterComponent,
  ConfigStageStepComponent,
  MapProductComponent,
  BmrPrepComponent,
  FormMastersComponent,
  ProcedureMasterComponent,
  ProcedureDraftEditorComponent,
  ProductApprovalPageComponent,
  SafetyPrecautionsComponent,
  GeneralInstructionsPageComponent,
  TableFormMasterComponent,
  StageStepIndexTableComponent,
  MasterFinalHtmlBmrComponent,
];

/** Declarations/exports only — no routes (safe to import from fproduction). */
@NgModule({
  declarations: COMPONENTS,
  imports: [CommonModule, FormsModule, ClarityModule, SharedModule, QuillModule.forRoot(), QmsEmbedFormsModule],
  exports: COMPONENTS,
})
export class EbmrBprSharedModule {}
