import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { EnqFromMarketingComponent } from './enq-from-marketing/enq-from-marketing.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import {  ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { RouterModule, Routes } from '@angular/router';
import { FesbilityFormComponent } from './fesbility-form/fesbility-form.component';
import { FeasibilityLogComponent } from './feasibility-log/feasibility-log.component';
import { ApproveFormulaAAndGenerateMaterialCodeComponent } from './approve-formula-aand-generate-material-code/approve-formula-aand-generate-material-code.component';

import { MaterialIssueRequestComponent } from './material-issue-request/material-issue-request.component';
import { PackingDevComponent } from './packing-dev/packing-dev.component';
import { ReqMatStatusLogComponent } from './req-mat-status-log/req-mat-status-log.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
   { path: 'eqnFromBd', component: EnqFromMarketingComponent},
   { path: 'fesibilityForm', component: FesbilityFormComponent},
   { path: 'fesibilityFormLog', component: FeasibilityLogComponent},
   { path: 'approveTentitiveFormula', component: ApproveFormulaAAndGenerateMaterialCodeComponent},
       { path: 'material-issue-request', component: MaterialIssueRequestComponent},
       { path: 'rndMatREqIssueStatusLog', component: ReqMatStatusLogComponent},
       { path: 'packingMAtDevRequest', component: PackingDevComponent},
  { path: 'npdApproval', redirectTo: '/rnd/master/material/approval', pathMatch: 'full' },

  
];



@NgModule({
  declarations: [
    DashboardComponent,
    EnqFromMarketingComponent,
    FesbilityFormComponent,
    FeasibilityLogComponent,
    ApproveFormulaAAndGenerateMaterialCodeComponent,MaterialIssueRequestComponent, PackingDevComponent, ReqMatStatusLogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class NpdModule { }
