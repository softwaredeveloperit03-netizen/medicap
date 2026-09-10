import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'product', loadChildren: () => import('./product/product.module').then(m=>m.ProductModule), data: {preload: false}},
  { path: 'chemical', loadChildren: () => import('./chemical/chemical.module').then(m=>m.ChemicalModule), data: {preload: false}},
  { path: 'clients', loadChildren: () => import('./clients/clients.module').then(m=>m.ClientsModule), data: {preload: false}},
  { path: 'equipments', loadChildren: () => import('./equipments/equipments.module').then(m=>m.EquipmentsModule), data: {preload: false}},
  { path: 'glassware', loadChildren: () => import('./glassware/glassware.module').then(m=>m.GlasswareModule), data: {preload: false}},
  { path: 'hplc', loadChildren: () => import('./hplc/hplc.module').then(m=>m.HplcModule), data: {preload: false}},
  { path: 'indicator', loadChildren: () => import('./indicator/indicator.module').then(m=>m.IndicatorModule), data: {preload: false}},
  { path: 'lab', loadChildren: () => import('./lab/lab.module').then(m=>m.LabModule), data: {preload: false}},
  { path: 'material', loadChildren: () => import('./material/material.module').then(m=>m.MaterialModule), data: {preload: false}},
  { path: 'stationary', loadChildren: () => import('./stationary/stationary.module').then(m=>m.StationaryModule), data: {preload: false}},
  { path: 'volumetric', loadChildren: () => import('./volumetric/volumetric.module').then(m=>m.VolumetricModule), data: {preload: false}},
  { path: 'test', loadChildren: () => import('./test/test.module').then(m=>m.TestModule), data: {preload: false}},
  { path: 'subtest', loadChildren: () => import('./subtest/subtest.module').then(m=>m.SubtestModule), data: {preload: false}},
  { path: 'standard', loadChildren: () => import('./standard/standard.module').then(m=>m.StandardModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MasterModule { }
