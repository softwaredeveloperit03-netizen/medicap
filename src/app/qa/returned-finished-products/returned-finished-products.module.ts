import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ReturnMerchandiseReportComponent } from './return-merchandise-report/return-merchandise-report.component';
import { ReturnMerchandiseLogComponent } from './return-merchandise-log/return-merchandise-log.component';
import { QaApprovalComponent } from './qa-approval/qa-approval.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'report/new', component: ReturnMerchandiseReportComponent, data: { view: 'new' } },
  { path: 'report/log', component: ReturnMerchandiseReportComponent, data: { view: 'log' } },
  { path: 'report/qa-review/:id', component: ReturnMerchandiseReportComponent, data: { view: 'qa-review' } },
  { path: 'approval', component: QaApprovalComponent },
  { path: 'log', component: ReturnMerchandiseLogComponent },
];

@NgModule({
  declarations: [DashboardComponent, ReturnMerchandiseReportComponent, ReturnMerchandiseLogComponent, QaApprovalComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class ReturnedFinishedProductsModule {}
