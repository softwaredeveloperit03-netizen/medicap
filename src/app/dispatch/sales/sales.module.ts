import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  // { path: '', component: DashboardComponent},
  { path: '', loadChildren: () => import('./own/own.module').then(m=>m.OwnModule), data: {preload: false}},
  { path: 'loan', loadChildren: () => import('./loan/loan.module').then(m=>m.LoanModule), data: {preload: false}},
  { path: 'third', loadChildren: () => import('./third/third.module').then(m=>m.ThirdModule), data: {preload: false}},
  { path: 'tender', loadChildren: () => import('./tender/tender.module').then(m=>m.TenderModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class SalesModule { }
