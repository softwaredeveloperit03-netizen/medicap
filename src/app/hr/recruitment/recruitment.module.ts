import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';

import { RouterModule, Routes } from '@angular/router';
import { OfferletterComponent } from './offerletter/offerletter.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { JoiningComponent } from './joining/joining.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from 'src/app/shared/shared.module';



const routes: Routes = [
  { path: '', component: DashboardComponent},
 
  { path: 'offerletter', component: OfferletterComponent},
  { path: 'joining', component: JoiningComponent},
  {path:'new',component: NewComponent},
  { path: 'candidate', loadChildren: () => import('./candidate/candidate.module').then(m=>m.CandidateModule), data: {preload: false}},
  { path: 'premedical', loadChildren: () => import('./premedical/premedical.module').then(m=>m.PremedicalModule), data: {preload: false}},
  { path: 'interview', loadChildren: () => import('./interview/interview.module').then(m=>m.InterviewModule), data: {preload: false}},
  { path: 'requisition', loadChildren: () => import('./requisition/requisition.module').then(m=>m.RequisitionModule), data: {preload: false}},
  
 
];

@NgModule({
  declarations: [DashboardComponent, OfferletterComponent,  JoiningComponent,NewComponent],
  imports: [ TranslateModule,
    SharedModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    MultiSelectModule,
    DropdownModule,
    RouterModule.forChild(routes)

  ]
})
export class RecruitmentModule { }
