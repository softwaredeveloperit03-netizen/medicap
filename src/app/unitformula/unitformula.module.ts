import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CheckingComponent } from './checking/checking.component';
import { NewApiComponent } from './new-api/new-api.component';
import {MultiSelectModule} from 'primeng/multiselect';
import { BfrreviewComponent } from './bfrreview/bfrreview.component';
import { BfrapprovalComponent } from './bfrapproval/bfrapproval.component';
import { BfrlogComponent } from './bfrlog/bfrlog.component';
const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new-api', component: NewApiComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
  { path: 'bfrreview', component: BfrreviewComponent},
  { path: 'bfrapproval', component: BfrapprovalComponent},
  { path: 'bfrlog', component: BfrlogComponent}
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ApprovalComponent, LogComponent, CheckingComponent, NewApiComponent, BfrreviewComponent, BfrapprovalComponent, BfrlogComponent],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class UnitformulaModule { }
