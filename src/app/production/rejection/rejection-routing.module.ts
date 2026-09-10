import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { OnlineRejectionComponent } from './online-rejection/online-rejection.component';
import { OnlineRejectionReportComponent } from './online-rejection-report/online-rejection-report.component';
import { DestructionComponent } from './destruction/destruction.component';
import { OnlineApprovalComponent } from './online-approval/online-approval.component';
import { DestructionReportComponent } from './destruction-report/destruction-report.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent },
  { path: 'onlinerejection', component: OnlineRejectionComponent },
  { path: 'onlinerejection-approval', component: OnlineApprovalComponent },
  { path: 'destruction', component: DestructionComponent },
  { path: 'destruction-report', component: DestructionReportComponent },
  { path: 'onlinereport', component: OnlineRejectionReportComponent },
  
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class RejectionRoutingModule { }
