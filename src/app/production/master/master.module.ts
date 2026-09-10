import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { InprocessComponent } from './inprocess/inprocess.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { CheckingComponent } from './checking/checking.component';
import { ApprovalComponent } from './approval/approval.component';
import { StageComponent } from './stage/stage.component';
import { RouterModule, Routes } from '@angular/router';
import { StepComponent } from './step/step.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'inprocess', component: InprocessComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
  { path: 'stage', component: StageComponent},
  { path: 'step/:id', component: StepComponent}
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, InprocessComponent, LogComponent, CheckingComponent, ApprovalComponent, StageComponent, StepComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MasterModule { }
