import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { IssuanceComponent } from './issuance/issuance.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'issuance', component: IssuanceComponent},
  { path: 'log', component: LogComponent},

];
@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    IssuanceComponent,
    ApprovalComponent,
    LogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class RequisitionModule { }
