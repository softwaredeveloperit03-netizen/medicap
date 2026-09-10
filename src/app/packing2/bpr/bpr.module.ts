import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { StartComponent } from './start/start.component';
import { InprocessComponent } from './inprocess/inprocess.component';
import { CompletedComponent } from './completed/completed.component';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { SpinprocessComponent } from './spinprocess/spinprocess.component';
import { SpintimationComponent } from './spintimation/spintimation.component';
import { SpinprocesscheckComponent } from './spinprocesscheck/spinprocesscheck.component';
import { InprocheckingComponent } from './inprochecking/inprochecking.component';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';
import { QcModuleDashboardModule } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.module';
import { EbmrBprSharedModule } from 'src/app/master/ebmr-bpr/ebmr-bpr-shared.module';
import { BatchesComponent } from 'src/app/master/ebmr-bpr/batches/batches.component';
import { ExecutionComponent } from 'src/app/master/ebmr-bpr/execution/execution.component';
import { ReportsComponent } from 'src/app/master/ebmr-bpr/reports/reports.component';
import { ReportComponent } from 'src/app/master/ebmr-bpr/report/report.component';

const PACK_BPR_CLOSE = '/packing/bpr';
const PACK_EBPR_START = '/packing/bpr/start';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  {
    path: 'start',
    component: BatchesComponent,
    data: {
      recordType: 'eBPR',
      closeRoute: PACK_BPR_CLOSE,
      productionExec: true,
      executionBase: '/packing/bpr/execution',
      returnUrlDefault: PACK_EBPR_START,
    },
  },
  {
    path: 'execution/:id',
    component: ExecutionComponent,
    data: {
      productionContext: true,
      closeRoute: PACK_EBPR_START,
    },
  },
  {
    path: 'reports',
    component: ReportsComponent,
    data: {
      closeRoute: PACK_BPR_CLOSE,
      reportBase: '/packing/bpr/report',
      productionContext: true,
      recordType: 'eBPR',
      pageTitle: 'Batch Packing Reports',
    },
  },
  {
    path: 'report/:id',
    component: ReportComponent,
    data: {
      closeRoute: '/packing/bpr/reports',
      productionContext: true,
    },
  },
  { path: 'start-packing', component: StartComponent },
  { path: 'inprocess', component: InprocessComponent },
  { path: 'completed', component: CompletedComponent },
  { path: 'spinprocess', component: SpinprocessComponent },
  { path: 'spinprocess-check', component: SpinprocesscheckComponent },
  { path: 'spintimation', component: SpintimationComponent },
  { path: 'inproChecking', component: InprocheckingComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    StartComponent,
    InprocessComponent,
    CompletedComponent,
    InprocheckingComponent,
    SpinprocessComponent,
    SpintimationComponent,
    SpinprocesscheckComponent,
  ],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    QcModuleDashboardModule,
    EbmrBprSharedModule,
    RouterModule.forChild(routes),
  ],
})
export class BprModule {}
