import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RequestComponent } from './request/request.component';
import { CcstatusComponent } from './ccstatus/ccstatus.component';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'status', component: CcstatusComponent},
  { path: 'request', component: RequestComponent},
  { path: 'log', component: LogComponent},
 
];

@NgModule({
  declarations: [
    DashboardComponent,
    RequestComponent,
    CcstatusComponent,
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
export class DocrevisionModule { }
