import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { EbmrBprSharedModule } from 'src/app/master/ebmr-bpr/ebmr-bpr-shared.module';
import { BatchesComponent } from 'src/app/master/ebmr-bpr/batches/batches.component';
import { BmrPrepComponent } from 'src/app/master/ebmr-bpr/bmr-prep/bmr-prep.component';
import { ProfilesComponent } from 'src/app/master/ebmr-bpr/profiles/profiles.component';
import { BuilderComponent } from 'src/app/master/ebmr-bpr/builder/builder.component';
import { ExecutionComponent } from 'src/app/master/ebmr-bpr/execution/execution.component';
import { ReportsComponent } from 'src/app/master/ebmr-bpr/reports/reports.component';
import { ReportComponent } from 'src/app/master/ebmr-bpr/report/report.component';
import { FprodEbmrWorkAllocationComponent } from './work-allocation/work-allocation.component';
import { FprodEbmrAuditTrailComponent } from './audit-trail/audit-trail.component';

const PROD_CLOSE = '/fproduction/ebmr';

const routes: Routes = [
  {
    path: 'work-allocation',
    component: FprodEbmrWorkAllocationComponent,
  },
  {
    path: 'start',
    component: FprodEbmrWorkAllocationComponent,
  },
  {
    path: 'audit-trail',
    component: FprodEbmrAuditTrailComponent,
  },
  {
    path: 'under-production',
    component: BatchesComponent,
    data: {
      recordType: 'eBMR',
      closeRoute: PROD_CLOSE,
      productionExec: true,
      executionBase: '/fproduction/ebmr/execution',
      returnUrlDefault: '/fproduction/ebmr/under-production',
    },
  },
  {
    path: 'batch/inprocess',
    redirectTo: '/packing/bpr/start',
    pathMatch: 'full',
  },
  {
    path: 'bmr-prep',
    component: BmrPrepComponent,
    data: {
      closeRoute: PROD_CLOSE,
      builderBase: '/fproduction/ebmr/builder',
      productionContext: true,
    },
  },
  {
    path: 'profiles',
    component: ProfilesComponent,
    data: {
      closeRoute: PROD_CLOSE,
      builderBase: '/fproduction/ebmr/builder',
      productionContext: true,
    },
  },
  {
    path: 'builder/:id',
    component: BuilderComponent,
    data: {
      productionContext: true,
      closeRoute: PROD_CLOSE,
      prepRoute: '/fproduction/ebmr/bmr-prep',
      profilesRoute: '/fproduction/ebmr/profiles',
    },
  },
  {
    path: 'execution/:id',
    component: ExecutionComponent,
    data: {
      productionContext: true,
      closeRoute: '/fproduction/ebmr/under-production',
    },
  },
  {
    path: 'reports',
    component: ReportsComponent,
    data: {
      closeRoute: PROD_CLOSE,
      reportBase: '/fproduction/ebmr/report',
      productionContext: true,
    },
  },
  {
    path: 'completed',
    component: ReportsComponent,
    data: {
      closeRoute: PROD_CLOSE,
      reportBase: '/fproduction/ebmr/report',
      productionContext: true,
      statusFilter: 'Released',
      recordType: 'eBMR',
      pageTitle: 'Completed BMR',
    },
  },
  {
    path: 'batch/log',
    component: ReportsComponent,
    data: {
      closeRoute: '/fproduction',
      reportBase: '/fproduction/ebmr/report',
      productionContext: true,
      statusFilter: 'Released',
      recordType: 'eBMR',
      pageTitle: 'Completed BMR',
    },
  },
  {
    path: 'report/:id',
    component: ReportComponent,
    data: {
      closeRoute: '/fproduction/ebmr/reports',
      productionContext: true,
    },
  },
];

/**
 * Production eBMR / eBPR operational modules under /fproduction/ebmr/*
 */
@NgModule({
  declarations: [FprodEbmrWorkAllocationComponent, FprodEbmrAuditTrailComponent],
  imports: [CommonModule, FormsModule, EbmrBprSharedModule, RouterModule.forChild(routes)],
})
export class EbmrFeatureModule {}
