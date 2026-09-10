import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ConcernhodaaprComponent } from './concernhodaapr/concernhodaapr.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { QareviewComponent } from './qareview/qareview.component';
import { DeviationassassmentComponent } from './deviationassassment/deviationassassment.component';
import { AppofdeviatioqaComponent } from './appofdeviatioqa/appofdeviatioqa.component';
import { ClosureComponent } from './closure/closure.component';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'Concern-Hod-Comment', component: ConcernhodaaprComponent},
  { path: 'qaReview', component: QareviewComponent},
  { path: 'devAssassment', component: DeviationassassmentComponent},
  { path: 'apprvlOfQa', component: AppofdeviatioqaComponent},
  { path: 'closure', component: ClosureComponent},
 ]

 
 

@NgModule({
  declarations: [
    ConcernhodaaprComponent,
    DashboardComponent,
    QareviewComponent,
    DeviationassassmentComponent,
    AppofdeviatioqaComponent,
    ClosureComponent
  ],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class DeviationModule { }
