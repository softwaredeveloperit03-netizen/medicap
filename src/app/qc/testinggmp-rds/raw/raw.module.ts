import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { CoaComponent } from './coa/coa.component';
import { RdsComponent } from './rds/rds.component';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { RouterModule, Routes } from '@angular/router';
import { RejectedComponent } from './rejected/rejected.component';
import { AllocationComponent } from './allocation/allocation.component';
import { CorrectionComponent } from './correction/correction.component';
import { LogComponent } from './log/log.component';
import { HomeComponent } from './home/home.component';
import { ReanalysisComponent } from './reanalysis/reanalysis.component';
import { AllocationLogComponent } from './allocation-log/allocation-log.component';
import { UsageLogComponent } from './usage-log/usage-log.component';
import { DocsIconsModule } from '../../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'home', component: HomeComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'allocationLog', component: AllocationLogComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'coa', component: CoaComponent},
  { path: 'rds', component: RdsComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'rejected', component: RejectedComponent},
  { path: 'reanalysis', component: ReanalysisComponent},
  { path: 'correction', component: CorrectionComponent},
  { path: 'log', component: LogComponent},
  { path: 'usage-log', component: UsageLogComponent},
  { path: 'outside', loadChildren: () => import('./outside/outside.module').then(m=>m.OutsideModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, AwaitingComponent, CoaComponent, RdsComponent, CheckingComponent, ApprovalComponent, RejectedComponent, AllocationComponent, CorrectionComponent, LogComponent, HomeComponent, ReanalysisComponent, AllocationLogComponent, UsageLogComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class RawModule { }
