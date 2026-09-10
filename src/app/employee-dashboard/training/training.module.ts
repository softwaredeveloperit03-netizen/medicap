import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { SelfCertificateComponent } from './self-certificate/self-certificate.component';
import { ScheduleComponent } from './schedule/schedule.component';
import { FeedbackComponent } from './feedback/feedback.component';
import { IndividualComponent } from './individual/individual.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes=[
  {path:'',component:DashboardComponent},
  {path:'self_learning',component:SelfCertificateComponent},
  {path:'schedule',component:ScheduleComponent},
  {path:'feedback',component:FeedbackComponent},
  {path:'individual',component:IndividualComponent},
  { path: 'ojtevaluate', loadChildren: () => import('./ojtevaluate/ojtevaluate.module').then(m=>m.OjtevaluateModule), data: {preload: false}},
  { path:'evaluate', loadChildren: () => import('./evaluate/evaluate.module').then(m=>m.EvaluateModule)},
  { path:'retraining', loadChildren: () => import('./retraining/retraining.module').then(m=>m.RetrainingModule)}
]

@NgModule({
  declarations: [
    DashboardComponent,
    SelfCertificateComponent,
    ScheduleComponent,
    FeedbackComponent,
    IndividualComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TrainingModule { }
