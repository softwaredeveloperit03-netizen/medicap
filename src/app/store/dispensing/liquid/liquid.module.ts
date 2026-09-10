import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ManualComponent } from './manual/manual.component';
import { RequestComponent } from './request/request.component';
import { HoldComponent } from './hold/hold.component';
import { ReportComponent } from './report/report.component';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'manual', component: ManualComponent},
  { path: 'request', component: RequestComponent},
  { path: 'hold', component: HoldComponent},
  { path: 'report', component: ReportComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,ManualComponent,RequestComponent,HoldComponent,ReportComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class LiquidModule { }
