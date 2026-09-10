import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { AwaitingComponent } from './awaiting/awaiting.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { HomeComponent } from './home/home.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { HoldComponent } from './hold/hold.component';
import { RejectComponent } from './reject/reject.component';
import { LogsComponent } from './logs/logs.component';
import { ReportsComponent } from './reports/reports.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'hold', component: HoldComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'log', component: LogComponent},
  { path: 'reject', component: RejectComponent},
  { path: 'label', loadChildren: () => import('./label/label.module').then(m=>m.LabelModule), data: {preload: false}},
];

@NgModule({
  declarations: [AwaitingComponent, CheckingComponent, LogComponent, HomeComponent, HoldComponent, RejectComponent, LogsComponent, ReportsComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class GrnModule { }
