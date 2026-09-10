import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReceiveComponent } from './receive/receive.component';
import { AllocationComponent } from './allocation/allocation.component';
import { TestComponent } from './test/test.component';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'test', component: TestComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'receive', component: ReceiveComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent}
];


@NgModule({
  declarations: [ReceiveComponent, AllocationComponent, TestComponent, CheckingComponent, ApprovalComponent, LogComponent, DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class InprocessModule { }
