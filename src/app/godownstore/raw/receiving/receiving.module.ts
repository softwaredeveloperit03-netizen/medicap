import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { LogComponent } from './log/log.component';
import { CheckingComponent } from './checking/checking.component';
import { RouterModule, Routes } from '@angular/router';
import { RejectedComponent } from './rejected/rejected.component';
import { CorrectionComponent } from './correction/correction.component';
import { New_correctionComponent } from './new_correction/new_correction.component';
import { HoldComponent } from './hold/hold.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'hold', component: HoldComponent},
  { path: 'log', component: LogComponent},
  // { path: 'history', component: HistoryComponent},
  { path: 'rejected', component: RejectedComponent}, 
  // { path: 'correction', component: CorrectionComponent}, 
  { path: 'label', loadChildren: () => import('./label/label.module').then(m=>m.LabelModule), data: {preload: false}},
  { path: 'new_correction', component: New_correctionComponent}, 
  { path: 'correction', loadChildren: () => import('./correction/correction.module').then(m=>m.CorrectionModule), data: {preload: false}},

];

@NgModule({
  declarations: [DashboardComponent, AwaitingComponent, LogComponent, CheckingComponent, RejectedComponent, CorrectionComponent,New_correctionComponent, HoldComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ReceivingModule { }
