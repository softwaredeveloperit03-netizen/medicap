import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { RouterModule, Routes } from '@angular/router';
import { FormulaComponent } from './formula/formula.component';
import { PlanningComponent } from './planning/planning.component';
import { PlanningApprovalComponent } from './planning-approval/planning-approval.component';
import { QaApprovedPlansComponent } from './qa-approved-plans/qa-approved-plans.component';
import { FromProductionComponent } from './from-production/from-production.component';
import { BatchplanComponent } from './batchplan/batchplan.component';
import { PromproductionComponent } from './promproduction/promproduction.component';
import { PkcheclitComponent } from './pkcheclit/pkcheclit.component';
import { SpplanningComponent } from './spplanning/spplanning.component';
import { SpplanapproveComponent } from './spplanapprove/spplanapprove.component';
import { ReconciliationComponent } from './reconciliation/reconciliation.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'formula', component: FormulaComponent}, 
  { path: 'transfer', component: FromProductionComponent},
  { path: 'batchplan', component: BatchplanComponent},
  { path: 'packing_cheklist', component: PkcheclitComponent},
  { path: 'Promproduction', component: PromproductionComponent},
  { path: 'reconciliation', component: ReconciliationComponent},

  { path: 'workorder', loadChildren: () => import('./workorder/workorder.module').then(m=>m.WorkorderModule), data: {preload: false}},
  { path: 'ebpr', loadChildren: () => import('./ebpr/ebpr.module').then(m=>m.EbprModule), data: {preload: false}},
  { path: 'receiving', loadChildren: () => import('./receiving/receiving.module').then(m=>m.ReceivingModule), data: {preload: false}},
  { path: 'plan', loadChildren: () => import('./plan/plan.module').then(m=>m.PlanModule), data: {preload: false}},
  { path: 'dispensing', loadChildren: () => import('./dispensing/dispensing.module').then(m=>m.DispensingModule), data: {preload: false}},
  { path: 'artwork', loadChildren: () => import('./artwork/artwork.module').then(m=>m.ArtworkModule), data: {preload: false}},
  { path: 'bpr', loadChildren: () => import('./bpr/bpr.module').then(m=>m.BprModule), data: {preload: false}},
  { path: 'packing-list', loadChildren: () => import('./packing-list/packing-list.module').then(m=>m.PackingListModule), data: {preload: false}},
  { path: 'logbook', loadChildren: () => import('./logbook/logbook.module').then(m=>m.LogbookModule), data: {preload: false}},
  { path: 'configuration', loadChildren: () => import('./configuration/configuration.module').then(m=>m.ConfigurationModule), data: {preload: false}},
  { path: 'sampling', loadChildren: () => import('./sampling/sampling.module').then(m=>m.SamplingModule), data: {preload: false}},
  { path: 'transfer', loadChildren: () => import('./transfer/transfer.module').then(m=>m.TransferModule), data: {preload: false}},
  { path: 'qms', loadChildren: () => import('./qms/qms.module').then(m=>m.QmsModule), data: {preload: false}},
   { path: 'checklist', loadChildren: () => import('./checklist/checklist.module').then(m=>m.ChecklistModule), data: {preload: false}},
  { path: 'planning', component: PlanningComponent},
  { path: 'planningSp', component: SpplanningComponent},
  { path: 'spplanning-approval', component: SpplanapproveComponent},
  { path: 'planning-approval', component: PlanningApprovalComponent},
  { path: 'qa-approved-plans', component: QaApprovedPlansComponent},
  { path: 'bmr', loadChildren: () => import('./bmr/bmr.module').then(m=>m.BmrModule), data: {preload: false}},


];

@NgModule({
  declarations: [
    DashboardComponent,
    FormulaComponent,
    PlanningComponent,
    PlanningApprovalComponent,
    QaApprovedPlansComponent,
    FromProductionComponent,
    BatchplanComponent,
    PromproductionComponent,
    PkcheclitComponent,
    SpplanningComponent,
    SpplanapproveComponent,
    ReconciliationComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class PackingModule { }
