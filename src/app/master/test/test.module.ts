import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { MasterExcelModule } from 'src/app/shared/master-excel/master-excel.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ApprovalComponent } from './approval/approval.component';
import { InactiveComponent } from './inactive/inactive.component';
import { ObsoleteComponent } from './obsolete/obsolete.component';
import { RevisionDashboardComponent } from './revision/dashboard/dashboard.component';
import { RevisionRequestComponent } from './revision/revision-request/revision-request.component';
import { RevisionApprovalComponent } from './revision/revision-approval/revision-approval.component';
import { RaiseChangeControlComponent } from './revision/raise-change-control/raise-change-control.component';
import { UpdateFormComponent } from './revision/update-form/update-form.component';
import { RevisionHistoryComponent } from './revision/revision-history/revision-history.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'approval', component: ApprovalComponent },
  { path: 'inactive', component: InactiveComponent },
  { path: 'obsolete', component: ObsoleteComponent },
  { path: 'revision', component: RevisionDashboardComponent },
  { path: 'revision/request', component: RevisionRequestComponent },
  { path: 'revision/approval', component: RevisionApprovalComponent },
  { path: 'revision/change-control', component: RaiseChangeControlComponent },
  { path: 'revision/update-form', component: UpdateFormComponent },
  { path: 'revision/history', component: RevisionHistoryComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    ApprovalComponent,
    InactiveComponent,
    ObsoleteComponent,
    RevisionDashboardComponent,
    RevisionRequestComponent,
    RevisionApprovalComponent,
    RaiseChangeControlComponent,
    UpdateFormComponent,
    RevisionHistoryComponent,
  ],
  imports: [
    SharedModule,
    MasterExcelModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TestModule { }
