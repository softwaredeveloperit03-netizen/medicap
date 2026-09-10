import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DistributionReconciliationComponent } from './distribution-reconciliation/distribution-reconciliation.component';
import { BiennialReviewLogComponent } from './biennial-review-log/biennial-review-log.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'distribution/new', component: DistributionReconciliationComponent, data: { view: 'new' } },
  { path: 'distribution/log', component: DistributionReconciliationComponent, data: { view: 'log' } },
  { path: 'biennial/new', component: BiennialReviewLogComponent, data: { view: 'new' } },
  { path: 'biennial/log', component: BiennialReviewLogComponent, data: { view: 'log' } },
];

@NgModule({
  declarations: [DashboardComponent, DistributionReconciliationComponent, BiennialReviewLogComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class StandardOperatingDocumentsModule {}
