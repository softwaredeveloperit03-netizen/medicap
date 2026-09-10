import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RequestComponent } from './request/request.component';
import { LogComponent } from './log/log.component';
import { EssuranceComponent } from './essurance/essurance.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'request', component: RequestComponent},
  { path: 'log', component: LogComponent},
  { path: 'essurance', component: EssuranceComponent},
];
@NgModule({
  declarations: [
    DashboardComponent,
    RequestComponent,
    LogComponent,
    EssuranceComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class AdditionalModule { }
