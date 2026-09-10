import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ReceiveComponent } from './receive/receive.component';
import { AllotComponent } from './allot/allot.component';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'receive', component: ReceiveComponent},
  { path: 'allot', component: AllotComponent},
  { path: 'log', component: LogComponent},
 ];


@NgModule({
  declarations: [
    DashboardComponent,
    ReceiveComponent,
    AllotComponent,
    LogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
        CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class UserreqModule { }
