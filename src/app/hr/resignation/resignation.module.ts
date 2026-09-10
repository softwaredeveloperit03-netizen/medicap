import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AcceptanceComponent } from './acceptance/acceptance.component';
import { ClearanceComponent } from './clearance/clearance.component';
import { InterviewComponent } from './interview/interview.component';
import { SettementComponent } from './settement/settement.component';
import { LetterComponent } from './letter/letter.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { ResignationComponent } from './resignation/resignation.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'acceptance', component: AcceptanceComponent},
  { path: 'clearance', component: ClearanceComponent},
  { path: 'interview', component: InterviewComponent},
  { path: 'letter', component: LetterComponent},
  { path: 'new', component: NewComponent},
  { path: 'res', component: ResignationComponent}
];

@NgModule({
  declarations: [DashboardComponent, AcceptanceComponent, ClearanceComponent, InterviewComponent, SettementComponent, LetterComponent,NewComponent,ResignationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes)
  ]
})
export class ResignationModule { }
