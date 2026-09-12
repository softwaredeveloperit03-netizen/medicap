import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule,ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { QcDataService } from './qc-data.service';
import { WashwaterComponent } from './washwater/washwater.component';
import { ResignationComponent } from './resignation/resignation.component';
import { DatePipe } from '@angular/common';
import { ChemistqualificationComponent } from './chemistqualification/chemistqualification.component';
import { OoscheckComponent } from './ooscheck/ooscheck.component';
import { OoschecklistComponent } from './ooschecklist/ooschecklist.component';
import { MatApprovalComponent } from './mat-approval/mat-approval.component';
import { FgProductApprovalComponent } from './fg-product-approval/fg-product-approval.component';
import { TranslateModule } from '@ngx-translate/core';
import { ClientDocumentDeptModule } from '../shared/client-document-dept/client-document-dept.module';
import { ClientDocumentDeptComponent } from '../shared/client-document-dept/client-document-dept.component';
import { TemperatureModule } from '../store/temperature/temperature.module';
import { TemperatureComponent } from '../store/temperature/temperature.component';



const routes: Routes = [
  { path: '', component: DashboardComponent},
   {path:'washwater',component:WashwaterComponent},
   { path: 'dashboard', component: DashboardComponent},
   { path: 'resignation', component: ResignationComponent},
  { path: 'materialApproval', component: MatApprovalComponent},
  { path: 'fgProductApproval', component: FgProductApprovalComponent},

   { path: 'chemistqualification', component: ChemistqualificationComponent},
   { path: 'ooscheck', component: OoschecklistComponent},  
  { path: 'client-doc-request', component: ClientDocumentDeptComponent, data: { dept: 'QC', closeRoute: '/qc' } },
  { path: 'temperature', component: TemperatureComponent, data: { temperatureDepartment: 'QC', closeRoute: '/qc' } },
  { path: 'Samplevendor', loadChildren: () => import('./samplevendor/samplevendor.module').then(m=>m.SamplevendorModule), data: {preload: false}},
  { path: 'sampling', loadChildren: () => import('./sampling/sampling.module').then(m=>m.SamplingModule), data: {preload: false}},
  { path: 'specifications', loadChildren: () => import('./specifications/specifications.module').then(m=>m.SpecificationsModule), data: {preload: false}},
  { path: 'moa', loadChildren: () => import('./moa/moa.module').then(m=>m.MoaModule), data: {preload: false}},
  { path: 'volumetric', loadChildren: () => import('./volumetric/volumetric.module').then(m=>m.VolumetricModule), data: {preload: false}},
  { path: 'water', loadChildren: () => import('./water/water.module').then(m=>m.WaterModule), data: {preload: false}},
  { path: 'validation', loadChildren: () => import('./validation/validation.module').then(m=>m.ValidationModule), data: {preload: false}},
  { path: 'glassware', loadChildren: () => import('./glassware/glassware.module').then(m=>m.GlasswareModule), data: {preload: false}},
  { path: 'chemical', loadChildren: () => import('./chemical/chemical.module').then(m=>m.ChemicalModule), data: {preload: false}},
  { path: 'equipment', loadChildren: () => import('./equipment/equipment.module').then(m=>m.EquipmentModule), data: {preload: false}},
  { path: 'indend', loadChildren: () => import('./indend/indend.module').then(m=>m.IndendModule), data: {preload: false}},
  { path: 'retest', loadChildren: () => import('./retest/retest.module').then(m=>m.RetestModule), data: {preload: false}},
  { path: 'controlsample', loadChildren: () => import('./controlsample/controlsample.module').then(m=>m.ControlsampleModule), data: {preload: false}},
  { path: 'equipments', loadChildren: () => import('./equipments/equipments.module').then(m=>m.EquipmentsModule), data: {preload: false}},
  { path: 'reagents', loadChildren: () => import('./reagents/reagents.module').then(m=>m.ReagentsModule), data: {preload: false}},
   { path: 'standard', loadChildren: () => import('./standard/standard.module').then(m=>m.StandardModule), data: {preload: false}},
  { path: 'calibration', loadChildren: () => import('./calibration/calibration.module').then(m=>m.CalibrationModule), data: {preload: false}},
  { path: 'hpl', loadChildren: () => import('./hplc/hplc.module').then(m=>m.HplcModule), data: {preload: false}},
  { path: 'change-control', loadChildren: () => import('./change-control/change-control.module').then(m=>m.ChangeControlModule), data: {preload: false}},
  { path: 'deviation', loadChildren: () => import('./deviation/deviation.module').then(m=>m.DeviationModule), data: {preload: false}},
  { path: 'cleaning', loadChildren: () => import('./cleaning/cleaning.module').then(m=>m.CleaningModule), data: {preload: false}},
  { path: 'cleaning-validation', loadChildren: () => import('../qa/equipment-cleaning-verification/equipment-cleaning-verification.module').then(m => m.EquipmentCleaningVerificationModule), data: {preload: false}},
  { path: 'oos', loadChildren: () => import('./oos/oos.module').then(m=>m.OosModule), data: {preload: false}},
  { path: 'testing-rds', loadChildren: () => import('./testinggmp-rds/testinggmp-rds.module').then(m=>m.TestinggmpRdsmodule), data: {preload: false}},
  { path: 'anat1', loadChildren: () => import('./analytical/analytical.module').then(m=>m.AnalyticalModule), data: {preload: false}},
  { path: 'analytical-method-validation', loadChildren: () => import('./analytical-method-validation/analytical-method-validation.module').then(m=>m.AnalyticalMethodValidationModule), data: {preload: false}},
  { path: 'audit-trail', loadChildren: () => import('./audit-trail/audit-trail.module').then(m=>m.AuditTrailModule), data: {preload: false}},
  { path: 'analytical-test-request', loadChildren: () => import('./analytical-test-request/analytical-test-request.module').then(m=>m.AnalyticalTestRequestModule), data: {preload: false}},
  { path: 'processing-laboratory-samples', loadChildren: () => import('./processing-laboratory-samples/processing-laboratory-samples.module').then(m=>m.ProcessingLaboratorySamplesModule), data: {preload: false}},
  { path: 'shipping-order', loadChildren: () => import('./shipping-order/shipping-order.module').then(m=>m.ShippingOrderModule), data: {preload: false}},
  { path: 'in-process-product-sampling', loadChildren: () => import('./in-process-product-sampling/in-process-product-sampling.module').then(m=>m.InProcessProductSamplingModule), data: {preload: false}},
  { path: 'finished-product-sampling', loadChildren: () => import('./finished-product-sampling/finished-product-sampling.module').then(m=>m.FinishedProductSamplingModule), data: {preload: false}},
];
 @NgModule({ 
  declarations: [DashboardComponent, WashwaterComponent,MatApprovalComponent,OoscheckComponent,ResignationComponent, ChemistqualificationComponent,FgProductApprovalComponent, OoschecklistComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    DocsIconsModule,
    ClientDocumentDeptModule,
    TemperatureModule,
    RouterModule.forChild(routes)
  ],
  providers: [
    QcDataService, DatePipe
  ]
})
export class QcModule { }
