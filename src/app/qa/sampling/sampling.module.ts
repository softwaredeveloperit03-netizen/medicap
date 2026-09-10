import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ReceiveComponent } from './receive/receive.component';
import { WithdrawComponent } from './withdraw/withdraw.component';
import { LogComponent } from './log/log.component';
import { ToolsComponent } from './tools/tools.component';
import { DropdownModule } from 'primeng/dropdown';
import { ApprovalComponent } from './approval/approval.component';
import { QaApprovalComponent } from './qa-approval/qa-approval.component';
import { SpreceiveComponent } from './spreceive/spreceive.component';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes=[
  { path: '',component: DashboardComponent},
  { path: 'receive',component:ReceiveComponent},
  { path: 'spreceive',component:SpreceiveComponent},
  { path: 'withdraw',component:WithdrawComponent},
  { path: 'log',component:LogComponent},
  { path: 'tools', component: ToolsComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'qa-approval', component: QaApprovalComponent},
];

@NgModule({
  declarations: [DashboardComponent, ReceiveComponent, WithdrawComponent, LogComponent, ToolsComponent, ApprovalComponent, QaApprovalComponent, SpreceiveComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
})
export class SamplingModule { }
