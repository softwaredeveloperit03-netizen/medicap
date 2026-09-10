import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ApprovalComponent } from './approval/approval.component';
import { SharedModule } from 'src/app/shared/shared.module';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  declarations: [DashboardComponent, AwaitingComponent, LogComponent, ApprovalComponent],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    SharedModule,
    RouterModule.forChild(routes)
  ]
})
export class RetrainingModule { }
