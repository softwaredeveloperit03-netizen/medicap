import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { InhouseReportComponent } from './inhouse-report/inhouse-report.component';
import { ThirdPartyReviewComponent } from './third-party-review/third-party-review.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'inhouse/new', component: InhouseReportComponent, data: { view: 'new' } },
  { path: 'inhouse/log', component: InhouseReportComponent, data: { view: 'log' } },
  { path: 'inhouse/edit/:id', component: InhouseReportComponent, data: { view: 'edit' } },
  { path: 'third-party/new', component: ThirdPartyReviewComponent, data: { view: 'new' } },
  { path: 'third-party/log', component: ThirdPartyReviewComponent, data: { view: 'log' } },
];

@NgModule({
  declarations: [DashboardComponent, InhouseReportComponent, ThirdPartyReviewComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class AnnualProductQualityReviewModule {}
