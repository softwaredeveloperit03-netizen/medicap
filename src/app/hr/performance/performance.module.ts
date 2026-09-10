import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { Report1Component } from './report1/report1.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'goal', loadChildren: () => import('./goal/goal.module').then(m=>m.GoalModule), data: {preload: false}},
  { path: 'initiate', loadChildren: () => import('./initiate/initiate.module').then(m=>m.InitiateModule), data: {preload: false}},
  { path: 'master', loadChildren: () => import('./master/master.module').then(m=>m.MasterModule), data: {preload: false}},
  { path: 'feedback', loadChildren: () => import('./feedback/feedback.module').then(m=>m.FeedbackModule), data: {preload: false}},
  { path: 'ordinate', loadChildren: () => import('./ordinate/ordinate.module').then(m=>m.OrdinateModule), data: {preload: false}},
  { path: 'functional', loadChildren: () => import('./functional/functional.module').then(m=>m.FunctionalModule), data: {preload: false}},
  { path: 'customers', loadChildren: () => import('./customers/customers.module').then(m=>m.CustomersModule), data: {preload: false}},
  { path: 'superrior', loadChildren: () => import('./superrior/superrior.module').then(m=>m.SuperriorModule), data: {preload: false}},
  { path: 'team', loadChildren: () => import('./team/team.module').then(m=>m.TeamModule), data: {preload: false}},
  { path: 'management', loadChildren: () => import('./management/management.module').then(m=>m.ManagementModule), data: {preload: false}},
  { path: 'report', loadChildren: () => import('./report/report.module').then(m=>m.ReportModule), data: {preload: false}},
  {path: 'report1', component: Report1Component},
  
  

 




];

@NgModule({
  declarations: [
    DashboardComponent,Report1Component
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PerformanceModule { }
