import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { IdentificationComponent } from './identification/identification.component';
import { InductionComponent } from './induction/induction.component';
import { IndividualComponent } from './individual/individual.component';
import { SharedModule } from 'src/app/shared/shared.module';
const routes: Routes = [
  { path: '', component: DashboardComponent},
  // { path: 'identification', component: IdentificationComponent},
  {path: 'induction', component: InductionComponent},
  { path: 'identification', component: IdentificationComponent},
  {path: 'individual', component: IndividualComponent},
  { path: 'retraining', loadChildren: () => import('./retraining/retraining.module').then(m=>m.RetrainingModule)},
  { path: 'certificate', loadChildren: () => import('./certificate/certificate.module').then(m=>m.CertificateModule)},
  { path: 'evaluate', loadChildren: () => import('./evaluate/evaluate.module').then(m=>m.EvaluateModule)},
];



@NgModule({
  declarations: [
    DashboardComponent,
    IdentificationComponent,
    InductionComponent,
    IndividualComponent
  ],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    SharedModule,
    RouterModule.forChild(routes)
  ]
})
export class TrainingModule { }
