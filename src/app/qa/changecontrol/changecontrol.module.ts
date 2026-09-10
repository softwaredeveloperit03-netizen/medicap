import { NgModule } from '@angular/core';
import { ClarityModule } from '@clr/angular';
import { CommonModule } from '@angular/common';

import { ChangecontrolRoutingModule } from './changecontrol-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ChangeControlApprovalComponent } from './approval/approval.component';
import { NewChangeControlComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { CheckingComponent } from './checking/checking.component';
import { VerifyComponent } from './verify/verify.component';
import { ImplementationComponent } from './implementation/implementation.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [
    DashboardComponent, 
    NewChangeControlComponent, 
    ChangeControlApprovalComponent,
    LogComponent,
    CheckingComponent,
    VerifyComponent,
    ImplementationComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    HttpClientModule,
    ChangecontrolRoutingModule
  ],
})
export class ChangecontrolModule { }
