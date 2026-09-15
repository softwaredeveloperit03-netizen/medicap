import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { ShortageComponent } from './shortage/shortage.component';
import { DocumentComponent } from './document/document.component';
import { BatchComponent } from './batch/batch.component';
import { ResignationComponent } from './resignation/resignation.component';
import { SharedModule } from 'src/app/shared/shared.module';
import { BatchFormulaComponent } from './batch-formula/batch-formula.component';
import { MaterialMasterDataComponent } from './material-master-data/material-master-data.component';


const routes: Routes = [
  { path: '', component: DashboardComponent, pathMatch: 'full'},
  { path: 'shortage', component: ShortageComponent},
  { path: 'document', component: DocumentComponent},
  { path: 'batch', component: BatchComponent},
  { path: 'resignation', component: ResignationComponent},


  { path: 'stock', loadChildren: () => import('./stock/stock.module').then(m=>m.StockModule)},
  { path: 'workorder', loadChildren: () => import('./workorder/workorder.module').then(m=>m.WorkorderModule)},
  { path: 'plan', loadChildren: () => import('./plan/plan.module').then(m=>m.PlanModule)},
  { path: 'indend', loadChildren: () => import('./indend/indend.module').then(m=>m.IndendModule)},
  { path: 'consoladated', loadChildren: () => import('./consoladated/consoladated.module').then(m=>m.ConsoladatedModule)},
  { path: 'purchase', loadChildren: () => import('./purchase/purchase.module').then(m=>m.PurchaseModule)},
  { path: 'production', loadChildren: () => import('./production/production.module').then(m=>m.ProductionModule)},
  { path: 'monthly', loadChildren: () => import('./monthly/monthly.module').then(m=>m.MonthlyModule)},
  { path: 'induction-training', loadChildren: () => import('./induction-training/induction-training.module').then(m=>m.InductionTrainingModule), data: {preload: false}},
  { path: 'unitformula', loadChildren: () => import('./unitformula/unitformula.module').then(m=>m.UnitformulaModule), data: {preload: false}},
  { path: 'training', loadChildren: () => import('./training/training.module').then(m=>m.TrainingModule), data: {preload: false}},
  { path: 'qms', loadChildren: () => import('./qms/qms.module').then(m=>m.QmsModule), data: {preload: false}},
  { path: 'batch-plan-approval', loadChildren: () => import('./batch-plan-approval/batch-plan-approval.module').then(m=>m.BatchPlanApprovalModule), data: {preload: false}},
  { path: 'masterformula', loadChildren: () => import('./masterformula/masterformula.module').then(m=>m.MasterformulaModule), data: {preload: false}},
  { path: 'batch-formula', component: BatchFormulaComponent },
  { path: 'material-master-data', component: MaterialMasterDataComponent },
  { path: 'mrp/Log', redirectTo: 'material-master-data', pathMatch: 'full' },
  { path: 'sops', loadChildren: () => import('./sops/sops.module').then(m=>m.SOPSModule), data: {preload: false}},
  { path: 'stplan', loadChildren: () => import('./stplan/stplan.module').then(m=>m.StplanModule), data: {preload: false}},
  // STP/Planning screens from attached mrp/src (Receivepofo, Generatewo, Shortages, Canplan, ...)
  {
    path: '',
    loadChildren: () =>
      import('./stplan/planning/planning.module').then((m) => m.PlanningModule),
  },
  { path: '**', redirectTo: '/'}
];

@NgModule({
  declarations: [DashboardComponent, ShortageComponent, DocumentComponent, BatchComponent, ResignationComponent, BatchFormulaComponent, MaterialMasterDataComponent],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    SharedModule,
    RouterModule.forChild(routes)
  ] 
})
export class PlanningModule { }
