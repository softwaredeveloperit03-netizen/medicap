import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { LogComponent } from './log/log.component';
import { CheckingComponent } from './checking/checking.component';
import { LabelComponent } from './label/label.component';
import { RouterModule, Routes } from '@angular/router';
import { RejectedComponent } from './rejected/rejected.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'log', component: LogComponent},
  { path: 'label', component: LabelComponent },
  { path: 'rejected', component: RejectedComponent}
];

@NgModule({
  declarations: [DashboardComponent, AwaitingComponent, LogComponent, CheckingComponent, LabelComponent, RejectedComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ReceivingModule { }
