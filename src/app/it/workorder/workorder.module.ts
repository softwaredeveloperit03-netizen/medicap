import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { SummarryComponent } from './summarry/summarry.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'receiveWorkOrder', component: NewComponent},
  { path: 'summarryOfWorkOrder', component: SummarryComponent},
  { path: 'workOrderChecking', component: ApprovalComponent},
  { path: 'log1', component: LogComponent},

 ];

@NgModule({
  declarations: [
    NewComponent,
    ApprovalComponent,
    LogComponent,
    DashboardComponent,SummarryComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})

export class WorkorderModule { }
