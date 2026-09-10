import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { GrnRoutingModule } from './grn-routing.module';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { HomeComponent } from './home/home.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { LabelComponent } from './label/label.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [AwaitingComponent, CheckingComponent, LogComponent, HomeComponent, LabelComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    GrnRoutingModule,
    FormsModule,
    ClarityModule
  ]
})
export class GrnModule { }
