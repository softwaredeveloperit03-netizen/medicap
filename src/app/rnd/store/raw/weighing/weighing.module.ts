import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { WeighingRoutingModule } from './weighing-routing.module';
import { HomeComponent } from './home/home.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [HomeComponent, AwaitingComponent, CheckingComponent, LogComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    WeighingRoutingModule,
    FormsModule,
    ClarityModule
  ]
})
export class WeighingModule { }
