import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AppraisalChecklistComponent } from './appraisal-checklist/appraisal-checklist.component';
import { CrossChecklistComponent } from './cross-checklist/cross-checklist.component';
import { ManagerChecklistComponent } from './manager-checklist/manager-checklist.component';
import { ReviewChecklistComponent } from './review-checklist/review-checklist.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'appraisal-checklist', component: AppraisalChecklistComponent},
  { path: 'cross-checklist', component: CrossChecklistComponent},
  { path: 'manager-checklist', component: ManagerChecklistComponent},
  { path: 'review-checklist', component: ReviewChecklistComponent},
 
];

@NgModule({
  declarations: [
    DashboardComponent,
    AppraisalChecklistComponent,
    CrossChecklistComponent,
    ManagerChecklistComponent,
    ReviewChecklistComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class AppraisalModule { }
