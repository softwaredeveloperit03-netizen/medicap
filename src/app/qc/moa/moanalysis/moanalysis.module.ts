import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LogComponent } from './log/log.component';
import { ApproveComponent } from './approve/approve.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { CheckingComponent } from './checking/checking.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'approve', component: ApproveComponent},
  { path: 'log', component: LogComponent},
  { path: 'checking', component: CheckingComponent},
];
@NgModule({
  declarations: [
    LogComponent,
    ApproveComponent,
    DashboardComponent,
    CheckingComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule ,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MoanalysisModule { }
 