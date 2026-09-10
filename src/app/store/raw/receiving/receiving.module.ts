import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { LogComponent } from './log/log.component';
import { MaterialLogComponent } from './material-log/material-log.component';
import { CheckingComponent } from './checking/checking.component';
import { RouterModule, Routes } from '@angular/router';
import { RejectedComponent } from './rejected/rejected.component';
import { AuditTrailComponent } from './audittrail/audittrail.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'log', component: LogComponent},
  { path: 'material-log', component: MaterialLogComponent},
  { path: 'rejected', component: RejectedComponent}, 
  { path: 'audit-trail', component: AuditTrailComponent},
  { path: 'label', loadChildren: () => import('./label/label.module').then(m=>m.LabelModule), data: {preload: false}},

];

@NgModule({
  declarations: [DashboardComponent, AwaitingComponent, LogComponent, MaterialLogComponent, CheckingComponent, RejectedComponent, AuditTrailComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ReceivingModule { }
