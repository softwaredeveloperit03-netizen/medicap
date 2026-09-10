import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';

import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TypeSoftwareComponent } from './type-software/type-software.component';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes=[
  { path: '', component: DashboardComponent},
  { path: 'software-type', component: TypeSoftwareComponent},
  { path: 'qa-master', loadChildren: () => import('./qa-master/qa-master.module').then(m=>m.QaMasterModule), data: {preload: false}},
  { path: 'qc-master', loadChildren: () => import('./qc/qc.module').then(m=>m.QcModule), data: {preload: false}},
  { path: 'rm-master-customisation', loadChildren: () => import('./rm-master-customisation/rm-master-customisation.module').then(m => m.RmMasterCustomisationModule), data: { preload: false }},
  { path: 'specification-form-customisation', loadChildren: () => import('./specification-form-customisation/specification-form-customisation.module').then(m => m.SpecificationFormCustomisationModule), data: { preload: false }},
  { path: 'receiving-form-customisation', loadChildren: () => import('./receiving-form-customisation/receiving-form-customisation.module').then(m => m.ReceivingFormCustomisationModule), data: { preload: false }},
];

@NgModule({
  declarations: [
    DashboardComponent, TypeSoftwareComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
   
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class SoftRestrictionModule { }
