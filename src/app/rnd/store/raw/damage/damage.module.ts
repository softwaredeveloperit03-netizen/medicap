import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DamageRoutingModule } from './damage-routing.module';
import { HomeComponent } from './home/home.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ApprovalComponent } from './approval/approval.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [HomeComponent, AwaitingComponent, LogComponent, ApprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    DamageRoutingModule,
    FormsModule,
    ClarityModule
  ]
})
export class DamageModule { }
