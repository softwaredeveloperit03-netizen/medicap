import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { AwaitingComponent } from './awaiting/awaiting.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { HomeComponent } from './home/home.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { HoldComponent } from './hold/hold.component';
import { RejectComponent } from './reject/reject.component';
 import { ReportsComponent } from './reports/reports.component';
import {MultiSelectModule} from 'primeng/multiselect';
import { HistoryComponent } from './history/history.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'hold', component: HoldComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'log', component: LogComponent},
  { path: 'history', component: HistoryComponent},
  { path: 'reject', component: RejectComponent},
  { path: 'report', component: ReportsComponent},
  { path: 'label', loadChildren: () => import('./label/label.module').then(m=>m.LabelModule), data: {preload: false}},
  { path: 'correction', loadChildren: () => import('./correction/correction.module').then(m=>m.CorrectionModule), data: {preload: false}},

];

@NgModule({
  declarations: [AwaitingComponent, CheckingComponent, LogComponent, HomeComponent, HoldComponent, RejectComponent, ReportsComponent,HistoryComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class GrnModule { }
 