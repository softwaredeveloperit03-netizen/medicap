import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AllocationComponent } from './allocation/allocation.component';
import { ReceiveComponent } from './receive/receive.component';
import { CheckingComponent } from '../raw/checking/checking.component';
import { ApproveComponent } from '../raw/approve/approve.component';
import { LogComponent } from '../raw/log/log.component';
import { SampleComponent } from '../raw/sample/sample.component';
import { RawSampleSharedModule } from '../raw/raw-sample-shared.module';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'receive', component: ReceiveComponent },
  { path: 'allocation', component: AllocationComponent },
  { path: 'sampling', component: SampleComponent, data: { retestMode: true } },
  { path: 'checking', component: CheckingComponent, data: { retestMode: true } },
  { path: 'approval', component: ApproveComponent, data: { retestMode: true } },
  { path: 'log', component: LogComponent, data: { retestMode: true } },
];

@NgModule({
  declarations: [DashboardComponent, ReceiveComponent, AllocationComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RawSampleSharedModule,
    RouterModule.forChild(routes),
  ],
})
export class RetestModule {}
