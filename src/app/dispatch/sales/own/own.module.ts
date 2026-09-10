import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { BulkComponent } from './bulk/bulk.component';
import { CheckingComponent } from './checking/checking.component';
import { LogfinishedComponent } from './logfinished/logfinished.component';
import { RawlogComponent } from './rawlog/rawlog.component';
import { RawbulkComponent } from './rawbulk/rawbulk.component';
import { RawcheckingComponent } from './rawchecking/rawchecking.component';
import { RawapprovalComponent } from './rawapproval/rawapproval.component';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'bulk', component: BulkComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'LogfinishedComponent', component: LogfinishedComponent},
  { path: 'RawlogComponent', component: RawlogComponent},
  { path: 'rawbulk', component: RawbulkComponent},
  { path: 'rawchec', component: RawcheckingComponent},
  { path: 'rawappr', component: RawapprovalComponent}
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ApprovalComponent, BulkComponent, CheckingComponent, LogfinishedComponent, RawlogComponent, RawbulkComponent, RawcheckingComponent, RawapprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class OwnModule { }
