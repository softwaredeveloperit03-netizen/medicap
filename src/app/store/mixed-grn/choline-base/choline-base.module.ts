import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { RequistionComponent } from './requistion/requistion.component';
import { ActivityComponent } from './activity/activity.component';
import { ReportComponent } from './report/report.component';
import { StockComponent } from './stock/stock.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'requisition', component: RequistionComponent},
  { path: 'activity', component: ActivityComponent},
  { path: 'report', component: ReportComponent},
  { path: 'stock', component: StockComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    RequistionComponent,
    ActivityComponent,
    ReportComponent,
    StockComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CholineBaseModule { }
