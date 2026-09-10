import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DomesticApprovalsComponent } from './domestic-approvals/domestic-approvals.component';
import { OverSecureApprovalsComponent } from './over-secure-approvals/over-secure-approvals.component';
import { CoopComponent } from './coop/coop.component';
import { TranslateModule } from '@ngx-translate/core';

const routes:Routes=[
  {path:'',component:DashboardComponent},
  {path:'domesticApprovals',component:DomesticApprovalsComponent},
  {path:'copp',component:CoopComponent},
  {path:'overSecure',component:CoopComponent},
]

@NgModule({
  declarations: [
    DashboardComponent,
    DomesticApprovalsComponent,
    OverSecureApprovalsComponent,
    CoopComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ApprovalLicencesModule { }
