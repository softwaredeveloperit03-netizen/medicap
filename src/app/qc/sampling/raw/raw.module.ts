import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CheckingComponent } from './checking/checking.component';
import { ApproveComponent } from './approve/approve.component';
import { LogComponent } from './log/log.component';
import { LineComponent } from './line/line.component';
import { RawSampleSharedModule } from './raw-sample-shared.module';
import { SampleComponent } from './sample/sample.component';
import { LabelComponent } from './label/label.component';
import { RecevingComponent } from './receving/receving.component';
import { AllocationComponent } from './allocation/allocation.component';
import { AllocationLogComponent } from './allocation-log/allocation-log.component';
import { CorrectionComponent } from './correction/correction.component';
import { DocsIconsModule } from '../../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'checking', component: CheckingComponent },
  { path: 'approval', component: ApproveComponent },
  { path: 'rejectedSampling', component: CorrectionComponent },
  { path: 'log', component: LogComponent },
  { path: 'line', component: LineComponent },
  { path: 'sample', component: SampleComponent },
  { path: 'label', component: LabelComponent },
  { path: 'receving', component: RecevingComponent },
  { path: 'allocation', component: AllocationComponent },
  { path: 'allocationLog', component: AllocationLogComponent },
  { path: 'PreSampling', loadChildren: () => import('./presampling/presampling.module').then((m) => m.PresamplingModule),data: { preload: false },},
  {path: 'rmPmGrnReceiving',loadChildren: () =>import('./rm-pm-grn-receiving/rm-pm-grn-receiving.module').then((m) => m.RmPmGrnReceivingModule),data: { preload: false },},
];

@NgModule({
  declarations: [
    DashboardComponent,
    LineComponent,
    LabelComponent,
    RecevingComponent,
    AllocationComponent,
    CorrectionComponent,
    AllocationLogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RawSampleSharedModule,
    RouterModule.forChild(routes)
  ]
})
export class RawModule { }
