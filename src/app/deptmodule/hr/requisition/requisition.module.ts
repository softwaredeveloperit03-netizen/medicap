import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import  {DashboardComponent } from '../requisition/dashboard/dashboard.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DropdownModule } from 'primeng/dropdown';
import { MultiSelectModule } from 'primeng/multiselect';
import { ApprovalComponent } from './approval/approval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'approval', component: ApprovalComponent},
  
];
@NgModule({
  declarations: [
    DashboardComponent,
    ApprovalComponent
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
