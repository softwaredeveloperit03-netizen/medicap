import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { AllocationComponent } from './allocation/allocation.component';
import { ApprovalComponent } from './approval/approval.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class StabilityRoutingModule { }
