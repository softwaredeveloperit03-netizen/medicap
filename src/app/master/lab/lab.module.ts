import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { MasterExcelModule } from 'src/app/shared/master-excel/master-excel.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { RevisionHistoryComponent } from './revision-history/revision-history.component';
import { ReactiveFormsModule } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent },
  { path: 'revision-history', component: RevisionHistoryComponent}
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ApprovalComponent, LogComponent, RevisionHistoryComponent],
  imports: [
    SharedModule,
    MasterExcelModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes)
  ]
})
export class LabModule { }
