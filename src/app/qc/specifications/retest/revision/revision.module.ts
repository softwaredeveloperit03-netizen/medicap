import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RevisionViewComponent } from './revision-view/revision-view.component';
import { RequestComponent } from './request/request.component';
import { RouterModule, Routes } from '@angular/router';
import { PeriodicComponent } from './periodic/periodic.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'request', component: RequestComponent},
  { path: 'periodic', component: PeriodicComponent},
  { path: 'revision-view', component: RevisionViewComponent }
];

@NgModule({
  declarations: [
    DashboardComponent,
    RevisionViewComponent,
    RequestComponent,
    PeriodicComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes)
  ]
})
export class RevisionModule { }
