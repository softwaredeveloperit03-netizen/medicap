import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { IdentificationComponent } from './identification/identification.component';
import { InductionComponent } from './induction/induction.component';
import { IndividualComponent } from './individual/individual.component';
import { QuestionariesComponent } from './questionaries/questionaries.component';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  {path: 'Induction', component: InductionComponent},
  { path: 'identification', component: IdentificationComponent},
  {path: 'Individual', component: IndividualComponent},
  {path: 'question', component: QuestionariesComponent},
  { path: 'retraining', loadChildren: () => import('./retraining/retraining.module').then(m=>m.RetrainingModule)},
  { path: 'certificate', loadChildren: () => import('./certificate/certificate.module').then(m=>m.CertificateModule)},
  { path: 'evaluate', loadChildren: () => import('./evaluate/evaluate.module').then(m=>m.EvaluateModule)},
  { path: 'onjob', loadChildren: () => import('./onjob/onjob.module').then(m=>m.OnjobModule)},
  { path: 'trainings', loadChildren: () => import('./trainings/trainings.module').then(m=>m.TrainingsModule)},
  { path: 'gmp', loadChildren: () => import('./gmp/gmp.module').then(m=>m.GmpModule)},
];



@NgModule({
  declarations: [
    DashboardComponent,
    IdentificationComponent,
    InductionComponent,
    IndividualComponent,
    QuestionariesComponent
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
