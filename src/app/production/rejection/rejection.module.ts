import { NgModule } from '@angular/core';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { CommonModule } from '@angular/common';

import { RejectionRoutingModule } from './rejection-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { OnlineRejectionComponent } from './online-rejection/online-rejection.component';
import { OnlineRejectionReportComponent } from './online-rejection-report/online-rejection-report.component';
import { DestructionComponent } from './destruction/destruction.component';
import { OnlineApprovalComponent } from './online-approval/online-approval.component';
import { DestructionReportComponent } from './destruction-report/destruction-report.component';
import { TranslateModule } from '@ngx-translate/core';


@NgModule({
  declarations: [DashboardComponent, OnlineRejectionComponent, OnlineRejectionReportComponent, DestructionComponent, OnlineApprovalComponent, DestructionReportComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    HttpClientModule,
    RejectionRoutingModule
  ]
})
export class RejectionModule { }
