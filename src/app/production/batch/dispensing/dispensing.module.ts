import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ActivityComponent } from './activity/activity.component';
import { ReceivingComponent } from './receiving/receiving.component';
import { StatusComponent } from './status/status.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { RequisitionComponent } from './requisition/requisition.component';
import { ApprovalComponent } from './approval/approval.component';
import { ApprComponent } from './appr/appr.component';
import { InprocessFormulationComponent } from './inprocess-formulation/inprocess-formulation.component';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'requisition', component: RequisitionComponent},
  { path: 'activity', component: ActivityComponent},
  { path: 'receiving', component: ReceivingComponent},
  { path: 'status', component: StatusComponent},
  { path: 'appr', component: ApprComponent},
  { path: 'inprocess-formulation',component:InprocessFormulationComponent},

];

@NgModule({
  declarations: [DashboardComponent, ActivityComponent, ReceivingComponent, StatusComponent, RequisitionComponent, ApprComponent,InprocessFormulationComponent],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class DispensingModule { }
