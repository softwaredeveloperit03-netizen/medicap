
 import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ShortagesComponent } from './shortages/shortages.component';
import { ConfirmIndentComponent } from './confirm-indent/confirm-indent.component';
import { RmpmindentComponent } from './rmpmindent/rmpmindent.component';
import { WoplanningComponent } from './woplanning/woplanning.component';
import { WoplanapprovalComponent } from './woplanapproval/woplanapproval.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { ScrollingModule } from '@angular/cdk/scrolling';
import { FormsModule } from '@angular/forms';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ReceivepofoComponent } from './receivepofo/receivepofo.component';
import { GeneratewoComponent } from './generatewo/generatewo.component';
import { AnalysisComponent } from './analysis/analysis.component';
import { PendingplanComponent } from './pendingplan/pendingplan.component';
import { RecforcastComponent } from './recforcast/recforcast.component';
import { LogComponent } from './log/log.component';
import { ExelComponent } from './exel/exel.component';
import { WowiseComponent } from './wowise/wowise.component';
import { WowiseCancellationLogComponent } from './wowise/cancellation-log/wowise-cancellation-log.component';
import { ReceivepofologComponent } from './receivepofolog/receivepofolog.component';
import { GeneratewologComponent } from './generatewolog/generatewolog.component';
import { PlanningStatusLogComponent } from './status-log/status-log.component';
import { WoPlanningHubComponent } from './wo-planning-hub/wo-planning-hub.component';
import { PlanningSharedModule } from './planning-shared.module';
import { CanplanComponent } from './canplan/canplan.component';
import { ConfirmedsalesorderComponent } from './confirmedsalesorder/confirmedsalesorder.component';
import { WoLiveStatusComponent } from './wo-live-status/wo-live-status.component';
import { C2cTransferComponent } from './c2c-transfer/c2c-transfer.component';
import { SharedModule } from 'src/app/shared/shared.module';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'Shortages', component: ShortagesComponent},
  {
    path: 'WoPlanningHub',
    component: WoPlanningHubComponent,
    children: [
      { path: '', redirectTo: 'confirm-indent', pathMatch: 'full' },
      { path: 'confirm-indent', component: ConfirmIndentComponent },
      { path: 'indent-log', component: RmpmindentComponent },
      { path: 'can-plan', component: CanplanComponent },
      { path: 'indent-confirmation', redirectTo: 'confirm-indent', pathMatch: 'full' },
      { path: 'rmpm-indent', redirectTo: 'indent-log', pathMatch: 'full' },
    ],
  },
  { path: 'ConfirmIndent', redirectTo: 'WoPlanningHub/confirm-indent', pathMatch: 'full' },
  { path: 'Rmpmindent', redirectTo: 'WoPlanningHub/indent-log', pathMatch: 'full' },
  { path: 'Woplanning', component: WoplanningComponent},
  { path: 'Woplanapproval', component: WoplanapprovalComponent},
  { path: 'ConfirmedSalesOrder', component: ConfirmedsalesorderComponent},
  { path: 'Receivepofo', component: ReceivepofoComponent},
  { path: 'Receivepofolog', component: ReceivepofologComponent},
  { path: 'Canplan', redirectTo: 'WoPlanningHub/can-plan', pathMatch: 'full' },
  { path: 'VerifyStock', redirectTo: '/planning/stplan/stStp/VerifyStock', pathMatch: 'full' },
  { path: 'Wolog', redirectTo: '/planning/stplan/stStp/Wolog', pathMatch: 'full' },
  { path: 'Rmpmbooking', redirectTo: '/planning/stplan/stStp/Rmpmbooking', pathMatch: 'full' },
  { path: 'WoLiveStatus', component: WoLiveStatusComponent},
  { path: 'Generatewo', component: GeneratewoComponent},
  { path: 'Generatewolog', component: GeneratewologComponent},
  
  { path: 'Pendingplan', component: PendingplanComponent},
  { path: 'recForcast', component: RecforcastComponent},
  { path: 'log', component: LogComponent},
  { path: 'excel', component: ExelComponent},
  { path: 'StatusLog', component: PlanningStatusLogComponent},
  { path: 'Wowise', component: WowiseComponent},
  { path: 'Wowisecancellog', component: WowiseCancellationLogComponent},
  { path: 'C2cTransfer', component: C2cTransferComponent},
  { path: 'logs', loadChildren: () => import('./logs/logs.module').then(m=>m.LogsModule)},


 ];



@NgModule({
  declarations: [
    ShortagesComponent,
    ConfirmIndentComponent,
    RmpmindentComponent,
    WoplanningComponent,
    WoplanapprovalComponent,
    ConfirmedsalesorderComponent,
    ReceivepofoComponent,
    DashboardComponent,
    GeneratewoComponent,
    AnalysisComponent,
    RecforcastComponent,
    LogComponent,
    PendingplanComponent,
    ExelComponent,
    WowiseComponent,
    WowiseCancellationLogComponent,
    ReceivepofologComponent,
    GeneratewologComponent,
    PlanningStatusLogComponent,
    WoPlanningHubComponent,
    WoLiveStatusComponent,
    C2cTransferComponent,

  ],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    ScrollingModule,
    PlanningSharedModule,
    SharedModule,
    RouterModule.forChild(routes)
  ]
})
export class PlanningModule { }
