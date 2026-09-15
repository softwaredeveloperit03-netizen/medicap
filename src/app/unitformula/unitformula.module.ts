import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CheckingComponent } from './checking/checking.component';
import { NewApiComponent } from './new-api/new-api.component';
import {MultiSelectModule} from 'primeng/multiselect';
import { BfrreviewComponent } from './bfrreview/bfrreview.component';
import { BfrapprovalComponent } from './bfrapproval/bfrapproval.component';
const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new-api', component: NewApiComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', loadChildren: () => import('./unitformula-log.module').then(m => m.UnitformulaLogModule) },
  { path: 'bfrreview', component: BfrreviewComponent},
  { path: 'bfrapproval', component: BfrapprovalComponent},
  { path: 'bfrlog', loadChildren: () => import('./unitformula-bfrlog.module').then(m => m.UnitformulaBfrlogModule) },
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ApprovalComponent, CheckingComponent, NewApiComponent, BfrreviewComponent, BfrapprovalComponent],
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
