import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { RequestComponent } from './request/request.component';
import { HoldComponent } from './hold/hold.component';
import { ReportComponent } from './report/report.component';
import { ActivityComponent } from './activity/activity.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'request', component: RequestComponent},
  { path: 'hold', component: HoldComponent},
  { path: 'report', component: ReportComponent},
  { path: 'activity', component: ActivityComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    RequestComponent,
    HoldComponent,
    ReportComponent,
    ActivityComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class SolventModule { }
