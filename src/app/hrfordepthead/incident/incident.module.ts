import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ReviewComponent } from './review/review.component';
import { LogComponent } from './log/log.component';
import { QareviewComponent } from './qareview/qareview.component';
import { HeadreviewComponent } from './headreview/headreview.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'Review', component: ReviewComponent},
  { path: 'QAReview', component: QareviewComponent},
  { path: 'QAheadReview', component: HeadreviewComponent},
  { path: 'Log', component: LogComponent},
]
@NgModule({
  declarations: [
    DashboardComponent,
    ReviewComponent,
    LogComponent,
    QareviewComponent,
    HeadreviewComponent
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
export class IncidentModule { }
