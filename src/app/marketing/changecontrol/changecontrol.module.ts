import { NgModule } from '@angular/core';
import { ClarityModule } from '@clr/angular';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { NewChangeControlComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { ChangeControlReviewComponent } from './review/review.component';
import { CheckingComponent } from './checking/checking.component';
import { ImplementationComponent } from './implementation/implementation.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent},
  { path: 'new', component: NewChangeControlComponent},
  { path: 'log', component: LogComponent},
  { path: 'review', component: ChangeControlReviewComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'implementation', component: ImplementationComponent}
];

@NgModule({
  declarations: [
    DashboardComponent, 
    NewChangeControlComponent,
    LogComponent,
    ChangeControlReviewComponent,
    CheckingComponent,
    ImplementationComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    HttpClientModule,
    RouterModule.forChild(routes)
  ],
})
export class ChangecontrolModule { }
