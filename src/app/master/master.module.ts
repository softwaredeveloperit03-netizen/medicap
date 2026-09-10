import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { MattypeComponent } from './mattype/mattype.component';
import { SoftwareTypeComponent } from './software-type/software-type.component';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from '../shared/shared.module';
import { MasterExcelModule } from '../shared/master-excel/master-excel.module';
import { PackingConfigurationMasterComponent } from './packing-configuration-master/packing-configuration-master.component';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  /** Entry from Master hub â†’ RM/PM Material Master field customisation (QA Soft Restriction) */
  {
    path: 'material-master-form-customisation',
    redirectTo: '/qa/soft-restriction/rm-master-customisation',
    pathMatch: 'full',
  },
  {
    path: 'specification-form-customisation',
    redirectTo: '/qa/soft-restriction/specification-form-customisation',
    pathMatch: 'full',
  },
  {
    path: 'receiving-form-customisation',
    redirectTo: '/qa/soft-restriction/receiving-form-customisation',
    pathMatch: 'full',
  },
  { path: 'software-type', component: SoftwareTypeComponent },
  {
    path: 'software-customisation',
    loadChildren: () =>
      import('./software-customisation/software-customisation.module').then(
        (m) => m.SoftwareCustomisationModule
      ),
    data: { preload: false },
  },
  {
    path: 'process-stage-master',
    loadChildren: () =>
      import('./process-stage-master/process-stage-master.module').then(
        (m) => m.ProcessStageMasterModule
      ),
    data: { preload: false },
  },
  {
    path: 'process-type-master',
    loadChildren: () =>
      import('./process-type-master/process-type-master.module').then(
        (m) => m.ProcessTypeMasterModule
      ),
    data: { preload: false },
  },
  { path: 'mattype', component: MattypeComponent},
  { path: 'designation', loadChildren: () => import('../hr/master/designation/designation.module').then(m => m.DesignationModule), data: { preload: false } },
  { path: 'hra', loadChildren: () => import('../hr/master/hra/hra.module').then(m => m.HraModule), data: { preload: false } },
  { path: 'packing-configuration-master', component: PackingConfigurationMasterComponent },
  { path: 'commercial', loadChildren: () => import('./commercial/commercial.module').then(m=>m.CommercialModule), data: {preload: false}},
  { path: 'product', loadChildren: () => import('./product/product.module').then(m=>m.ProductModule), data: {preload: false}},
  { path: 'chemical', loadChildren: () => import('./chemical/chemical.module').then(m=>m.ChemicalModule), data: {preload: false}},
  { path: 'equipments', loadChildren: () => import('./equipments/equipments.module').then(m=>m.EquipmentsModule), data: {preload: false}},
  { path: 'glassware', loadChildren: () => import('./glassware/glassware.module').then(m=>m.GlasswareModule), data: {preload: false}},
  { path: 'hplc', loadChildren: () => import('./hplc/hplc.module').then(m=>m.HplcModule), data: {preload: false}},
  { path: 'lab', loadChildren: () => import('./lab/lab.module').then(m=>m.LabModule), data: {preload: false}},
  {
    path: 'material',
    loadChildren: () => import('./material/raw/raw.module').then((m) => m.RawModule),
    data: { preload: false },
  },
   { path: 'volumetric', loadChildren: () => import('./volumetric/volumetric.module').then(m=>m.VolumetricModule), data: {preload: false}},
  { path: 'test', loadChildren: () => import('./test/test.module').then(m=>m.TestModule), data: {preload: false}},
  { path: 'moa-stp-copy', loadChildren: () => import('./moa-stp-copy/moa-stp-copy.module').then(m=>m.MoaStpCopyModule), data: {preload: false}},
  { path: 'standard', loadChildren: () => import('./standard/standard.module').then(m=>m.StandardModule), data: {preload: false}},
  { path: 'blister', loadChildren: () => import('./blister/blister.module').then(m=>m.BlisterModule), data: {preload: false}},
  { path: 'company', loadChildren: () => import('./company/company.module').then(m=>m.CompanyModule), data: {preload: false}},
   { path: 'service-master', loadChildren: () => import('./service-master/service-master.module').then(m=>m.ServiceMasterModule), data: {preload: false}},
  { path: 'checklist', loadChildren: () => import('./checklist/checklist.module').then(m=>m.ChecklistModule), data: {preload: false}},
  { path: 'specification', loadChildren: () => import('./specification/specification.module').then(m=>m.SpecificationModule), data: {preload: false}},
  { path: 'mmaster', loadChildren: () => import('./mmaster/mmaster.module').then(m => m.MmasterModule), data: { preload: false } },
  { path: 'section', loadChildren: () => import('./section/section.module').then(m => m.SectionModule), data: { preload: false } },
  { path: 'linemaster', loadChildren: () => import('./linemaster/linemaster.module').then(m => m.LinemasterModule), data: { preload: false } },
  { path: 'productionstage', loadChildren: () => import('./productionstage/productionstage.module').then(m => m.ProductionstageModule), data: { preload: false } },
  { path: 'bmr', loadChildren: () => import('./bmr/bmr.module').then(m => m.BmrModule), data: { preload: false } },
  { path: 'wobmr', loadChildren: () => import('./wobmr/wobmr.module').then(m => m.WobmrModule), data: { preload: false } },
  { path: 'fg-sampling-checklist', loadChildren: () => import('./fg-sampling-checklist/fg-sampling-checklist.module').then(m => m.FgSamplingChecklistModule), data: { preload: false } },
  { path: 'bmr-master', loadChildren: () => import('./bmr-master/bmr-master.module').then(m => m.BmrMasterModule), data: { preload: false } },
  { path: 'ebmr-bpr', loadChildren: () => import('./ebmr-bpr/ebmr-bpr.module').then(m => m.EbmrBprModule), data: { preload: false } },

];

@NgModule({
  declarations: [
    DashboardComponent,
    MattypeComponent,
    SoftwareTypeComponent,
    PackingConfigurationMasterComponent,
  ],
  imports: [ TranslateModule,
    SharedModule,
    MasterExcelModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})

export class MasterModule { }
 