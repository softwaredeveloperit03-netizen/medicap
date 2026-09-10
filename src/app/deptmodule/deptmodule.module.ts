import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NgxDocViewerModule } from 'ngx-doc-viewer';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { RouterModule, Routes } from '@angular/router';
 import { ResignationComponent } from './resignation/resignation.component';
import { ArtworkComponent } from './artwork/artwork.component';
import { ArtworklogComponent } from './qa/artwork/artworklog/artworklog.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
   { path: 'artwork', component: ArtworkComponent},
  { path: 'outpass-approval', loadChildren: () => import('./outpass-approval/outpass-approval.module').then(m=>m.PlantOutpassApprovalModule), data: {preload: false}},

  { path: 'hr', loadChildren: () => import('./hr/hr.module').then(m=>m.HrModule), data: {preload: false}},
  { path: 'store', loadChildren: () => import('./store/store.module').then(m=>m.StoreModule), data: {preload: false}},
  { path: 'qc', loadChildren: () => import('./qc/qc.module').then(m=>m.QcModule), data: {preload: false}},
  { path: 'purchase', loadChildren: () => import('./purchase/purchase.module').then(m=>m.PurchaseModule), data: {preload: false}},
  { path: 'planning', loadChildren: () => import('./planning/planning.module').then(m=>m.PlanningModule), data: {preload: false}},
  { path: 'packing', loadChildren: () => import('./packing/packing.module').then(m=>m.PackingModule), data: {preload: false}},
  { path: 'generalStore', loadChildren: () => import('./gstore/gstore.module').then(m=>m.GstoreModule), data: {preload: false}},
  { path: 'fg', loadChildren: () => import('./fg/fg.module').then(m=>m.FgModule), data: {preload: false}},
  { path: 'engi', loadChildren: () => import('./engi/engi.module').then(m=>m.EngiModule), data: {preload: false}},
  { path: 'marketing', loadChildren: () => import('./bd/bd.module').then(m=>m.BdModule), data: {preload: false}},
  { path: 'admin', loadChildren: () => import('./admin/admin.module').then(m=>m.AdminModule), data: {preload: false}},
  { path: 'regulatory', loadChildren: () => import('./regulatory/regulatory.module').then(m=>m.RegulatoryModule), data: {preload: false}},
  { path: 'security', loadChildren: () => import('./security/security.module').then(m=>m.SecurityModule), data: {preload: false}},
  { path: 'stocktransfer', loadChildren: () => import('./stocktransfer/stocktransfer.module').then(m=>m.StocktransferModule), data: {preload: false}},
 
];

@NgModule({
  declarations: [
    DashboardComponent,
     ResignationComponent,
     ArtworkComponent,
    ArtworklogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    NgxDocViewerModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class DeptmoduleModule { }
