import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { LabComponent } from './lab/lab.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'lab', component: LabComponent},
  { path: 'master', loadChildren: () => import('./master/master.module').then(m=>m.MasterModule)},
  { path: 'specification', loadChildren: () => import('./specification/specification.module').then(m=>m.SpecificationModule)},
  { path: 'sampling', loadChildren: () => import('./sampling/sampling.module').then(m=>m.SamplingModule), data: {preload: false}},
  { path: 'specifications', loadChildren: () => import('./specifications/specifications.module').then(m=>m.SpecificationsModule), data: {preload: false}},
  { path: 'moa', loadChildren: () => import('./moa/moa.module').then(m=>m.MoaModule), data: {preload: false}},
  { path: 'testing', loadChildren: () => import('./testing/testing.module').then(m=>m.TestingModule), data: {preload: false}},
  { path: 'volumetric', loadChildren: () => import('./volumetric/volumetric.module').then(m=>m.VolumetricModule), data: {preload: false}},
  { path: 'water', loadChildren: () => import('./water/water.module').then(m=>m.WaterModule), data: {preload: false}},
  { path: 'validation', loadChildren: () => import('./validation/validation.module').then(m=>m.ValidationModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, LabComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class QcModule { }
