import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';

import { DashboardComponent } from './dashboard/dashboard.component';
import { RequestComponent } from './request/request.component';
import { LogComponent } from './log/log.component';
import { HoldComponent } from './hold/hold.component';
import { ReportComponent } from './report/report.component';
import { ReqcheckingComponent } from './reqchecking/reqchecking.component';
import { ReqapprovalComponent } from './reqapproval/reqapproval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'request', component: RequestComponent},
  { path: 'reqchecking', component: ReqcheckingComponent},
  { path: 'reqapproval', component: ReqapprovalComponent},
  { path: 'hold', component: HoldComponent},
  { path: 'log', component: LogComponent},
  { path: 'report', component: ReportComponent},
  { path: 'activity', loadChildren: () => import('./activity/activity.module').then(m=>m.ActivityModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, RequestComponent, LogComponent, HoldComponent, ReportComponent, ReqcheckingComponent,ReqapprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PackingModule { }
