import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { AllocationComponent } from './allocation/allocation.component';
import { ApprovalComponent } from './approval/approval.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { LogComponent } from './log/log.component';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  imports: [
    SharedModule, TranslateModule,RouterModule.forChild(routes)],
  exports: [ CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule]
})
export class StabilityRoutingModule { }
