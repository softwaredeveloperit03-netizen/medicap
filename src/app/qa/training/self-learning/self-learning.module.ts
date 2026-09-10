import { LearningScheduleLogComponent } from './learning-schedule-log/learning-schedule-log.component';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { SubDashboardSelfLearningComponent } from './sub-dashboard-self-learning/sub-dashboard-self-learning.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ContentMasterComponent } from './content-master/content-master.component';
import { ContentMasterLogComponent } from './content-master-log/content-master-log.component';
import { EvaluationComponent } from './evaluation/evaluation.component';
import { LearningActivityLogComponent } from './learning-activity-log/learning-activity-log.component';
import { NewContentMasterComponent } from './new-content-master/new-content-master.component';
import { NewLearningScheduleComponent } from './new-learning-schedule/new-learning-schedule.component';
import { PreviewDashboardComponent } from './preview-dashboard/preview-dashboard.component';
import { PreviewComponent } from './preview/preview.component';
import { PdfViewerModule } from 'ng2-pdf-viewer';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  {path: '', component: DashboardComponent},
  {path: 'sub-dashboard', component: SubDashboardSelfLearningComponent},
  {path: 'content-master', component: ContentMasterComponent},
  {path: 'content-master-log', component: ContentMasterLogComponent},
  {path: 'evaluation', component: EvaluationComponent},
  {path: 'learning-activity-log', component: LearningActivityLogComponent},
  {path: 'learning-schedule-log', component: LearningScheduleLogComponent},
  {path: 'new-content-master', component: NewContentMasterComponent},
  {path: 'new-learning-schedule', component: NewLearningScheduleComponent},
  {path: 'preview', component: PreviewComponent},
  {path: 'preview-dashboard', component: PreviewDashboardComponent}
]

@NgModule({
  declarations: [
    DashboardComponent,
    SubDashboardSelfLearningComponent,
    ContentMasterComponent,
    ContentMasterLogComponent, 
    EvaluationComponent, 
    LearningActivityLogComponent, 
    NewContentMasterComponent, 
    NewLearningScheduleComponent, 
    PreviewDashboardComponent, 
    PreviewComponent,
    LearningScheduleLogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    PdfViewerModule,
    RouterModule.forChild(routes)
  ]
})
export class SelfLearningModule { }
