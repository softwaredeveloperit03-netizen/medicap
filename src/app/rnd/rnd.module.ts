import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'qa', loadChildren: () => import('./qa/qa.module').then(m=>m.QaModule)},
  { path: 'qc', loadChildren: () => import('./qc/qc.module').then(m=>m.QcModule)},
  { path: 'vendor', loadChildren: () => import('./vendor/vendor.module').then(m=>m.VendorModule)},
  { path: 'purVendor', loadChildren: () => import('./pur-vendor/pur-vendor.module').then(m => m.PurVendorModule), data: { preload: false } },
  { path: 'master', loadChildren: () => import('./master/master.module').then(m=>m.MasterModule)},
  { path: 'store', loadChildren: () => import('./store/store.module').then(m=>m.StoreModule)},
  { path: 'optimisation', loadChildren: () => import('./optimisation/optimisation.module').then(m=>m.OptimisationModule)},
  { path: 'dev-trial', loadChildren: () => import('./dev-trial/dev-trial.module').then(m=>m.DevTrialModule)},
  { path: 'predevelopment', loadChildren: () => import('./predevelopment/predevelopment.module').then(m=>m.PredevelopmentModule)},
  { path: 'product-dev', loadChildren: () => import('./product-dev/product-dev.module').then(m=>m.ProductDevModule)},
  { path: 'training', loadChildren: () => import('./training/training.module').then(m=>m.TrainingModule), data: {preload: false}},
  { path: 'qms', loadChildren: () => import('./qms/qms.module').then(m=>m.QmsModule), data: {preload: false}},
  // { path: 'unitformula', loadChildren: () => import('./unitformula/unitformula.module').then(m=>m.UnitformulaModule)}, // Removd and moved to main module
  { path: 'product', loadChildren: () => import('./product/product.module').then(m=>m.ProductModule)},
  { path: 'scaleup', loadChildren: () => import('./scaleup/scaleup.module').then(m=>m.ScaleupModule)},
  { path: 'development', loadChildren: () => import('./development/development.module').then(m=>m.DevelopmentModule)},
  { path: 'technology', loadChildren: () => import('./technology/technology.module').then(m=>m.TechnologyModule)},
  { path: 'stability', loadChildren: () => import('./stability/stability.module').then(m=>m.StabilityModule)},
  { path: 'indend', loadChildren: () => import('./indend/indend.module').then(m=>m.IndendModule)},  
  {
    path: 'purchase-requisition',
    loadChildren: () =>
      import('./purchase-requisition/purchase-requisition.module').then(
        (m) => m.PurchaseRequisitionModule
      ),
    data: { preload: false },
  },
];
@NgModule({
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
}) 
export class RndModule { }
