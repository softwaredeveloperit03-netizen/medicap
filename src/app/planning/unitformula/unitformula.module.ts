import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CheckingComponent } from './checking/checking.component';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ApprovalComponent, LogComponent, CheckingComponent],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class UnitformulaModule { }
