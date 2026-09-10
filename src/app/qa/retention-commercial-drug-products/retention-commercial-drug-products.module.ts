import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ReceiptStorageComponent } from './receipt-storage/receipt-storage.component';
import { PeriodicReviewComponent } from './periodic-review/periodic-review.component';
import { DisposalComponent } from './disposal/disposal.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'receipt/new', component: ReceiptStorageComponent, data: { view: 'new' } },
  { path: 'receipt/log', component: ReceiptStorageComponent, data: { view: 'log' } },
  { path: 'review/new', component: PeriodicReviewComponent, data: { view: 'new' } },
  { path: 'review/log', component: PeriodicReviewComponent, data: { view: 'log' } },
  { path: 'disposal/new', component: DisposalComponent, data: { view: 'new' } },
  { path: 'disposal/log', component: DisposalComponent, data: { view: 'log' } },
];

@NgModule({
  declarations: [DashboardComponent, ReceiptStorageComponent, PeriodicReviewComponent, DisposalComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class RetentionCommercialDrugProductsModule {}
