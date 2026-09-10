import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { LogComponent } from './log/log.component';
import { ClarityModule } from '@clr/angular';
import { WithdrawalComponent } from './withdrawal/withdrawal.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'withdrawal', component: WithdrawalComponent},
  { path: 'log', component: LogComponent}
] ;

@NgModule({
  declarations: [DashboardComponent, LogComponent, WithdrawalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TechnicalInfoModule { }
