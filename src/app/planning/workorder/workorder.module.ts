import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ConsoladatedComponent } from './consoladated/consoladated.component';
import { ApprovalComponent } from './approval/approval.component';
import { ReceiveComponent } from './receive/receive.component';
import { SharedModule } from 'src/app/shared/shared.module';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'log', component: LogComponent},
  { path: 'consoladated', component: ConsoladatedComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'receive', component: ReceiveComponent}
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, LogComponent, ConsoladatedComponent, ApprovalComponent, ReceiveComponent],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    SharedModule,
    RouterModule.forChild(routes)
  ]
})
export class WorkorderModule { }
