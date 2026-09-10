import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { ReceiveComponent } from './receive/receive.component';
import { DispenseComponent } from './dispense/dispense.component';
import { AllocationComponent } from './allocation/allocation.component';
import { ClearanceApprovalComponent } from './clearance-approval/clearance-approval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'clearance-approval', component: ClearanceApprovalComponent},
  { path: 'dispense', component: DispenseComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'receive', component: ReceiveComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  declarations: [DashboardComponent, LogComponent, CheckingComponent, ApprovalComponent, ReceiveComponent, DispenseComponent, AllocationComponent, ClearanceApprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class DispensingModule { }
