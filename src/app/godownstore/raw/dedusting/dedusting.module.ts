import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { CorrectionComponent } from './correction/correction.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'log', component: LogComponent},
  { path: 'correction', component: CorrectionComponent},
];

@NgModule({
  declarations: [AwaitingComponent, CheckingComponent, LogComponent, DashboardComponent, CorrectionComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
   

    RouterModule.forChild(routes)
  ]
})
export class DedustingModule { }
