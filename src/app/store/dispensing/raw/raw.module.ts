import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RequestComponent } from './request/request.component';
import { LogComponent } from './log/log.component';
import { HoldComponent } from './hold/hold.component';
import { ReportComponent } from './report/report.component';
import { ManualComponent } from './manual/manual.component';
import { RejectedComponent } from './rejected/rejected.component';
import { SprequestComponent } from './sprequest/sprequest.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'request', component: RequestComponent},
  { path: 'sprequest', component: SprequestComponent},
  { path: 'hold', component: HoldComponent},
  { path: 'log', component: LogComponent},
  { path: 'report', component: ReportComponent},
  { path: 'manual', component: ManualComponent},
  { path: 'rejected', component: RejectedComponent},
  { path: 'activity', loadChildren: () => import('./activity/activity.module').then(m=>m.ActivityModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, RequestComponent, LogComponent, HoldComponent, ReportComponent, ManualComponent, RejectedComponent, SprequestComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class RawModule { }
