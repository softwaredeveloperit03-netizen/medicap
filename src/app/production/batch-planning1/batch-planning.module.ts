import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { PlanFromPoComponent } from './plan-from-po/plan-from-po.component';
import {RouterModule, Routes} from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ApprovalComponent } from './approval/approval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes=[
  {path: '', component:DashboardComponent},
  {path: 'new', component:NewComponent},
  {path: 'approval', component:ApprovalComponent},
  {path: 'plan-from-po', component:PlanFromPoComponent},
]

@NgModule({
  declarations: [
    NewComponent,
    DashboardComponent,
    PlanFromPoComponent,
    ApprovalComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class BatchPlanningModule { }
