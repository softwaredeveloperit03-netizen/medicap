import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ReviewComponent } from './review/review.component';
import { LogComponent } from './log/log.component';

import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { MultiSelectModule } from 'primeng/multiselect';
import { SharedModule } from 'src/app/shared/shared.module';
const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'log', component: LogComponent},
  { path: 'review', component: ReviewComponent},
]


@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    ReviewComponent,
    LogComponent
  ],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    SharedModule,
    RouterModule.forChild(routes)
  ]
})
export class DeviationModule { }
