import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DailyRoutingModule } from './daily-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AnnoucementComponent } from './annoucement/annoucement.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { LogComponent } from './log/log.component';
import { AnnoucementCheckingComponent } from './annoucement-checking/annoucement-checking.component';
import { AnnoucementApprovalComponent } from './annoucement-approval/annoucement-approval.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [DashboardComponent, AnnoucementComponent, LogComponent, AnnoucementCheckingComponent, AnnoucementApprovalComponent, NewComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    DailyRoutingModule,
    FormsModule,
    ClarityModule
  ]
})
export class DailyModule { }
