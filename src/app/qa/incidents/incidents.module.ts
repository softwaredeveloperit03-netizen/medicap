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
import { Review1Component } from './review1/review1.component';
import { Review2Component } from './review2/review2.component';
import { Evaluate1Component } from './evaluate1/evaluate1.component';
import { Evaluate2Component } from './evaluate2/evaluate2.component';
import { ClosingComponent } from './closing/closing.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'form', component: FormComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: IncidentLogComponent},
  { path: 'checker', component: CheckerComponent},
  { path: 'trend', component: TrendComponent},
  { path: 'verify', component: VerifyComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'review1', component: Review1Component},
  { path: 'review2', component: Review2Component},
  { path: 'evaluate1', component: Evaluate1Component},
  { path: 'evaluate2', component: Evaluate2Component},
  { path: 'closing', component: ClosingComponent}
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
    ReviewComponent,
    Review1Component,
    Review2Component,
    Evaluate1Component,
    Evaluate2Component,
    ClosingComponent
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
