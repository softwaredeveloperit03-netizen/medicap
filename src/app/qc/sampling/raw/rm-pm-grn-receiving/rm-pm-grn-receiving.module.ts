import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ReceiveComponent } from './receive/receive.component';
import { OnholdComponent } from './onhold/onhold.component';
import { RejectedComponent } from './rejected/rejected.component';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../../../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'receive', component: ReceiveComponent },
  { path: 'onHoldReceiving', component: OnholdComponent },
  { path: 'rejectedReceiving', component: RejectedComponent },
  { path: 'log', component: LogComponent },
 
]

@NgModule({
  declarations: [
    DashboardComponent,
    ReceiveComponent,
    OnholdComponent,
    RejectedComponent,
    LogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class RmPmGrnReceivingModule { }
