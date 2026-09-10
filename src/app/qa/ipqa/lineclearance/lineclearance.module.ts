import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { RequestComponent } from './request/request.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { FormComponent } from './form/form.component';
import { LogComponent } from './log/log.component';
import { ChecklistComponent } from './checklist/checklist.component';
import { ChecklistApprovalComponent } from './checklist-approval/checklist-approval.component';
import { ChecklistLogComponent } from './checklist-log/checklist-log.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'request', component: RequestComponent},
  { path: 'form', component: FormComponent},
  { path: 'log', component: LogComponent},
  { path: 'checklist', component: ChecklistComponent},
  { path: 'checklist-approval', component: ChecklistApprovalComponent},
  { path: 'checklist-log', component: ChecklistLogComponent}
];

@NgModule({
  declarations: [DashboardComponent, RequestComponent, FormComponent, LogComponent, ChecklistComponent, ChecklistApprovalComponent, ChecklistLogComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class LineclearanceModule { }
