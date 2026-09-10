import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { CoaComponent } from './coa/coa.component';
import { ArComponent } from './ar/ar.component';
import { RdsComponent } from './rds/rds.component';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { RouterModule, Routes } from '@angular/router';
import { RejectedComponent } from './rejected/rejected.component';
import { AllocationComponent } from './allocation/allocation.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'ar', component: ArComponent},
  { path: 'coa', component: CoaComponent},
  { path: 'rds', component: RdsComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'rejected', component: RejectedComponent}
];

@NgModule({
  declarations: [DashboardComponent, AwaitingComponent, CoaComponent, ArComponent, RdsComponent, CheckingComponent, ApprovalComponent, RejectedComponent, AllocationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class RawModule { }
