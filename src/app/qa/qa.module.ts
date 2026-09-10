import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { LabelComponent } from './label/label.component';
import { AuditLogReportComponent } from './audit-log-report/audit-log-report.component';
import { ResignationComponent } from './resignation/resignation.component';
import { LabelapprovalComponent } from './labelapproval/labelapproval.component';
import { AdditionalComponent } from './additional/additional.component';
import { MatApprovalComponent } from './mat-approval/mat-approval.component';
import { FgProductApprovalComponent } from './fg-product-approval/fg-product-approval.component';
import { BatchFormulaLogComponent } from './batch-formula-log/batch-formula-log.component';
import { DisplayDocumentsComponent } from './display-documents/display-documents.component';
import { RestrictionComponent } from './restriction/restriction.component';
import { ApprovalDashboardComponent } from './approval-dashboard/approval-dashboard.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'display-documents', component: DisplayDocumentsComponent},
  { path: 'label', component: LabelComponent},
  { path: 'auditreport', component: AuditLogReportComponent},
  { path: 'resignation', component: ResignationComponent},
  { path: 'Additional', component: AdditionalComponent},
  { path: 'label-approval', component: LabelapprovalComponent},
  { path: 'batch-formula-log', component: BatchFormulaLogComponent},
  { path: 'restriction', component: RestrictionComponent},

  { path: 'approval', component: ApprovalDashboardComponent, children: [
    { path: 'sampling', loadChildren: () => import('./sampling/sampling.module').then(m=>m.SamplingModule), data: {preload: false} },
    { path: 'fgProductApproval', component: FgProductApprovalComponent },
    { path: 'materialApproval', component: MatApprovalComponent },
  ]},
  { path: 'rej-approvals', loadChildren: () => import('./rej-approvals/rej-approvals.module').then(m=>m.RejApprovalsModule), data: {preload: false}},

  { path: 'request', loadChildren: () => import('./docrevision/docrevision.module').then(m=>m.DocrevisionModule), data: {preload: false}},
  { path: 'audit', loadChildren: () => import('./audit/audit.module').then(m=>m.AuditModule), data: {preload: false}},
  { path: 'training', loadChildren: () => import('./training/training.module').then(m=>m.TrainingModule), data: {preload: false}},
  { path: 'controlsample', loadChildren: () => import('./controlsample/controlsample.module').then(m=>m.ControlsampleModule), data: {preload: false}},
  {
    path: 'equipment-calibration',
    loadChildren: () =>
      import('src/app/shared/equipment-dept-calibration/equipment-dept-calibration.module').then(
        (m) => m.EquipmentDeptCalibrationModule
      ),
    data: { performDepartment: 'Quality Assurance', closeRoute: '/qa' },
  },
  { path: 'enviornmental', loadChildren: () => import('./enviornmental/enviornmental.module').then(m=>m.EnviornmentalModule), data: {preload: false}},
  { path: 'deviation', loadChildren: () => import('./deviation/deviation.module').then(m=>m.DeviationModule)},
  { path: 'capa', loadChildren: () => import('./capa/capa.module').then(m=>m.CapaModule), data: {preload: false}},
  { path: 'batchrelease', loadChildren: () => import('./batchrelease/batchrelease.module').then(m=>m.BatchReleaseModule), data: {preload: false}},
  { path: 'complaints', loadChildren: () => import('./complaint/complaint.module').then(m=>m.ComplaintModule), data: {preload: false}},
  { path: 'stability', loadChildren: () => import('./stability/stability.module').then(m=>m.StabilityModule), data: {preload: false}},
  { path: 'apqr', loadChildren: () => import('./apqr/apqr.module').then(m=>m.ApqrModule), data: {preload: false}},
  { path: 'incident', loadChildren: () => import('./incidents/incidents.module').then(m=>m.IncidentsModule), data: {preload: false}},
  { path: 'personal', loadChildren: () => import('./personal/personal.module').then(m=>m.PersonalModule), data: {preload: false}},
  { path: 'risk', loadChildren: () => import('./risk/risk.module').then(m=>m.RiskModule), data: {preload: false}},
  { path: 'technical-document', loadChildren: () => import('./technical-document/technical-document.module').then(m=>m.TechnicalDocumentModule), data: {preload: false}},
  { path: 'oos', loadChildren: () => import('./oos/oos.module').then(m=>m.OosModule), data: {preload: false}},
  { path: 'damage', loadChildren: () => import('./damage/damage.module').then(m=>m.DamageModule), data: {preload: false}},
  { path: 'changecontrol', loadChildren: () => import('./changecontrol/changecontrol.module').then(m=>m.ChangecontrolModule)},
  { path: 'incident', loadChildren: () => import('./incidents/incidents.module').then(m=>m.IncidentsModule), data: {preload: false}},
  { path: 'document', loadChildren: () => import('./document/document.module').then(m=>m.DocumentModule), data: {preload: false}},
  { path: 'rejection', loadChildren: () => import('./rejection/rejection.module').then(m=>m.RejectionModule), data: {preload: false}},
  { path: 'recall', loadChildren: () => import('./recall/recall.module').then(m=>m.RecallModule), data: {preload: false}},
  { path: 'equip-qualification', loadChildren: () => import('./equip-qualification/equip-qualification.module').then(m=>m.EquipQualificationModule), data: {preload: false}},
  { path: 'daily', loadChildren: () => import('./daily/daily.module').then(m=>m.DailyModule), data: {preload: false}},
  { path: 'maintenance', loadChildren: () => import('./maintenance/maintenance.module').then(m=>m.MaintenanceModule), data: {preload: false}},  { path: 'maintenance', loadChildren: () => import('./maintenance/maintenance.module').then(m=>m.MaintenanceModule), data: {preload: false}},
  { path: 'user', loadChildren: () => import('./user/user.module').then(m=>m.UserModule), data: {preload: false}},
  { path: 'sops', loadChildren: () => import('./sops/sops.module').then(m=>m.SOPSModule), data: {preload: false}},
  { path: 'bmr', loadChildren: () => import('./bmr/bmr.module').then(m=>m.BmrModule ), data: {preload: false}},
  { path: 'ipqa', loadChildren: () => import('./ipqa/ipqa.module').then(m=>m.IpqaModule ), data: {preload: false}},
  { path: 'oot', loadChildren: () => import('./oot/oot.module').then(m=>m.OotModule ), data: {preload: false}},
  { path: 'audit-agenda', loadChildren: () => import('./audit-agenda/audit-agenda.module').then(m=>m.AuditAgendaModule ), data: {preload: false}},
  { path: 'responsibility', loadChildren: () => import('./responsibility/responsibility.module').then(m=>m.ResponsibilityModule ), data: {preload: false}},
  { path: 'artwork', loadChildren: () => import('./artwork/artwork.module').then(m=>m.ArtworkModule ), data: {preload: false}},
  { path: 'stereo', loadChildren: () => import('./stereo/stereo.module').then(m=>m.StereoModule ), data: {preload: false}},
  { path: 'monitoring', loadChildren: () => import('./gmp-monitoring/gmp-monitoring.module').then(m=>m.GMPMonitoringModule ), data: {preload: false}},
  { path: 'vendor', loadChildren: () => import('./vendor/vendor.module').then(m=>m.VendorModule ), data: {preload: false}},
  { path: 'ccpermanant', loadChildren: () => import('./ccpermanant/ccpermanant.module').then(m=>m.CcpermanantModule), data: {preload: false}},
  { path: 'cctemporary', loadChildren: () => import('./cctemporary/cctemporary.module').then(m=>m.CctemporaryModule), data: {preload: false}},
  { path: 'expenses', loadChildren: () => import('./expenses/expenses.module').then(m=>m.ExpensesModule), data: {preload: false}},
  { path: 'ooc', loadChildren: () => import('./ooc/ooc.module').then(m=>m.OocModule), data: {preload: false}},
  { path: 'soft-restriction', loadChildren: () => import('./soft-restriction/soft-restriction.module').then(m=>m.SoftRestrictionModule), data: {preload: false}},
  { path: 'lab-incident', loadChildren: () => import('./lab-incident/lab-incident.module').then(m=>m.LabIncidentModule), data: {preload: false}},
  { path: 'qms', loadChildren: () => import('./qms/qms.module').then(m=>m.QmsModule), data: {preload: false}},
  { path: 'indent', loadChildren: () => import('./indent/indent.module').then(m=>m.IndentModule), data: {preload: false}},
  { path: 'specification', loadChildren: () => import('./specification/specification.module').then(m=>m.SpecificationModule), data: {preload: false}},
  { path: 'approvalLicences', loadChildren: () => import('./approval-licences/approval-licences.module').then(m=>m.ApprovalLicencesModule), data: {preload: false}},
  { path: 'revision', loadChildren: () => import('./revision/revision.module').then(m=>m.RevisionModule), data: {preload: false}},
  { path: 'breakdown', loadChildren: () => import('./breakdown/breakdown.module').then(m=>m.BreakdownModule), data: {preload: false}},
  {path: 'rootcause', loadChildren: () => import('./root-cause/root-cause.module').then(m=>m.RootCauseModule), data: {preload: false}},
  { path: 'equipment-cleaning-verification', loadChildren: () => import('./equipment-cleaning-verification/equipment-cleaning-verification.module').then(m => m.EquipmentCleaningVerificationModule), data: { preload: false } },
  { path: 'employee-training-records', loadChildren: () => import('./employee-training-records/employee-training-records.module').then(m => m.EmployeeTrainingRecordsModule), data: { preload: false } },
  { path: 'employee-initials-signature', loadChildren: () => import('./employee-initials-signature/employee-initials-signature.module').then(m => m.EmployeeInitialsSignatureModule), data: { preload: false } },
  { path: 'gmp-inspection-qc-labs', loadChildren: () => import('./gmp-inspection-qc-labs/gmp-inspection-qc-labs.module').then(m => m.GmpInspectionQcLabsModule), data: { preload: false } },
  { path: 'gmp-inspection-clinical-site', loadChildren: () => import('./gmp-inspection-clinical-site/gmp-inspection-clinical-site.module').then(m => m.GmpInspectionClinicalSiteModule), data: { preload: false } },
  { path: 'standard-operating-documents', loadChildren: () => import('./standard-operating-documents/standard-operating-documents.module').then(m => m.StandardOperatingDocumentsModule), data: { preload: false } },
  { path: 'retention-commercial-drug-products', loadChildren: () => import('./retention-commercial-drug-products/retention-commercial-drug-products.module').then(m => m.RetentionCommercialDrugProductsModule), data: { preload: false } },
  { path: 'conditional-release', loadChildren: () => import('./conditional-release/conditional-release.module').then(m => m.ConditionalReleaseModule), data: { preload: false } },
  { path: 'waste-disposal-pharma', loadChildren: () => import('./waste-disposal-pharma/waste-disposal-pharma.module').then(m => m.WasteDisposalPharmaModule), data: { preload: false } },
  { path: 'returned-finished-products', loadChildren: () => import('./returned-finished-products/returned-finished-products.module').then(m => m.ReturnedFinishedProductsModule), data: { preload: false } },
  { path: 'annual-product-quality-review', loadChildren: () => import('./annual-product-quality-review/annual-product-quality-review.module').then(m => m.AnnualProductQualityReviewModule), data: { preload: false } },
  { path: 'stepwise-qa-release', loadChildren: () => import('./stepwise-qa-release/stepwise-qa-release.module').then(m => m.StepwiseQaReleaseModule), data: { preload: false } },
  { path: 'management-review-quality-systems', loadChildren: () => import('./management-review-quality-systems/management-review-quality-systems.module').then(m => m.ManagementReviewQualitySystemsModule), data: { preload: false } },
  { path: 'corrective-preventive-action', loadChildren: () => import('./corrective-preventive-action/corrective-preventive-action.module').then(m => m.CorrectivePreventiveActionModule), data: { preload: false } },
];

@NgModule({
  declarations: [
    DashboardComponent,
    ApprovalDashboardComponent,
    AdditionalComponent,
    LabelComponent,
    AuditLogReportComponent,
    ResignationComponent,
    LabelapprovalComponent,
    BatchFormulaLogComponent,
    MatApprovalComponent,
    FgProductApprovalComponent,
    DisplayDocumentsComponent,
    RestrictionComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes),
  ],
})
export class QaModule {}
