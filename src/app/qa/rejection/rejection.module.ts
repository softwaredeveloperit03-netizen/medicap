import { NgModule } from '@angular/core';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { OnlineRejectionReportComponent } from './online-rejection-report/online-rejection-report.component';
import { RawpackingreportComponent } from './rawpackingreport/rawpackingreport.component';
import { DestructionComponent } from './destruction/destruction.component';
import { ReturnComponent } from './return/return.component';
import { OnlineApprovalComponent } from './online-approval/online-approval.component';
import { RawpackingApprovalComponent } from './rawpacking-approval/rawpacking-approval.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent },
  { path: 'onlinereport', component: OnlineRejectionReportComponent},
  { path: 'rawpackingreport', component: RawpackingreportComponent},
  { path: 'destruction', component: DestructionComponent },
  { path: 'return', component: ReturnComponent },
  { path: 'online-approval', component: OnlineApprovalComponent },
  { path: 'rawpacking-approval', component: RawpackingApprovalComponent}
];

@NgModule({
  declarations: [DashboardComponent, OnlineRejectionReportComponent, RawpackingreportComponent, DestructionComponent, ReturnComponent, OnlineApprovalComponent, RawpackingApprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    HttpClientModule,
    RouterModule.forChild(routes)
  ]
})
export class RejectionModule { }
