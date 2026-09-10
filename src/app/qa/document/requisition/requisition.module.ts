import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import  {DashboardComponent } from '../requisition/dashboard/dashboard.component';
import { NewComponent } from '../requisition/new/new.component';
import { Other_deptComponent } from '../requisition/other_dept/other_dept.component';

import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DropdownModule } from 'primeng/dropdown';
import { MultiSelectModule } from 'primeng/multiselect';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'other', component: Other_deptComponent},
  
];
@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    Other_deptComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule, 
    DropdownModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes)
  ]
})
export class RequisitionModule { }
