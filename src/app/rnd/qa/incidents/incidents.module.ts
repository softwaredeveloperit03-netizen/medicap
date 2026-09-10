import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { FormComponent } from './form/form.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ApprovalComponent } from './approval/approval.component';
import { CheckerComponent } from './checker/checker.component';
import { IncidentLogComponent } from './incident-log/incident-log.component';
import { TrendComponent } from './trend/trend.component';
import { VerifyComponent } from './verify/verify.component';
import { RouterModule, Routes } from '@angular/router';
import { ReviewComponent } from './review/review.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'form', component: FormComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: IncidentLogComponent},
  { path: 'checker', component: CheckerComponent},
  { path: 'trend', component: TrendComponent},
  { path: 'verify', component: VerifyComponent},
  { path: 'review', component: ReviewComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    FormComponent,
    ApprovalComponent,
    IncidentLogComponent,
    CheckerComponent,
    TrendComponent,
    VerifyComponent,
    ReviewComponent
  ],

  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class IncidentsModule { }
