import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { AllocationComponent } from './allocation/allocation.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { ArComponent } from './ar/ar.component';
import { CoaComponent } from './coa/coa.component';
import { RdsComponent } from './rds/rds.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'ar', component: ArComponent},
  { path: 'coa', component: CoaComponent},
  { path: 'rds', component: RdsComponent}
];

@NgModule({
  declarations: [DashboardComponent, AllocationComponent, AwaitingComponent, CheckingComponent, ApprovalComponent, ArComponent, CoaComponent, RdsComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PackingModule { }
