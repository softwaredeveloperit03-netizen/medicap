import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { StabilityRoutingModule } from './stability-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AllocationComponent } from './allocation/allocation.component';
import { LogComponent } from './log/log.component';
import { ApprovalComponent } from './approval/approval.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { DocsIconsModule } from '../../../floating-docs-popup/docs-icons.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


@NgModule({
  declarations: [DashboardComponent, AllocationComponent, LogComponent, ApprovalComponent, AwaitingComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    StabilityRoutingModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule
  ]
})
export class StabilityModule { }
