import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DeviationRoutingModule } from './deviation-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { CheckingComponent } from './checking/checking.component';
import { ReviewComponent } from './review/review.component';
import { LogComponent } from './log/log.component';
import { CapaComponent } from './capa/capa.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [DashboardComponent, NewComponent, CheckingComponent, ReviewComponent, LogComponent, CapaComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    DeviationRoutingModule,
    FormsModule,
    ClarityModule
  ]
})
export class DeviationModule { }
