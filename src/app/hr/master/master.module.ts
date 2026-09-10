import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
 

const routes: Routes = [ 
  { path: 'holiday', loadChildren: () => import('./holiday/holiday.module').then(m=>m.HolidayModule), data: {preload: false}}, 
  { path: 'shift', loadChildren: () => import('./shift/shift.module').then(m=>m.ShiftModule), data: {preload: false}}, 
  { path: 'department', loadChildren: () => import('./department/department.module').then(m=>m.DepartmentModule), data: {preload: false}},
  { path: 'section', loadChildren: () => import('./section/section.module').then(m=>m.SectionModule), data: {preload: false}},
  { path: 'qualification', loadChildren: () => import('./qualification/qualification.module').then(m=>m.QualificationModule), data: {preload: false}},
  { path: 'appraisal', loadChildren: () => import('./appraisal/appraisal.module').then(m=>m.AppraisalModule), data: {preload: false}},
  

];

@NgModule({
  declarations: [],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MasterModule { }
