import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ReviewComponent } from './review/review.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';

 

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'Review', component: ReviewComponent},
  { path: 'Approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
]

@NgModule({
  declarations: [
    DashboardComponent,
    ReviewComponent,
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
export class SpecificationModule { }
