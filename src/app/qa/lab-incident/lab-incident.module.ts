import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { CheckerComponent } from './checker/checker.component';
import { VerifyComponent } from './verify/verify.component';
import { ReviewComponent } from './review/review.component';
import { InitiateComponent } from './initiate/initiate.component';
import { ApprovalComponent } from './approval/approval.component';
import { Evaluate1Component } from './evaluate1/evaluate1.component';
import { Evaluate2Component } from './evaluate2/evaluate2.component';
import { ClosingComponent } from './closing/closing.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'checker', component: CheckerComponent},
  { path: 'verify', component: VerifyComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'initiate', component: InitiateComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'evaluate1', component: Evaluate1Component},
  { path: 'evaluate2', component: Evaluate2Component},
  { path: 'closing', component: ClosingComponent},
  { path: 'log', component: LogComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,NewComponent,CheckerComponent,VerifyComponent,ReviewComponent,
    InitiateComponent,ApprovalComponent,Evaluate1Component,Evaluate2Component,ClosingComponent,LogComponent
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
export class LabIncidentModule { }
