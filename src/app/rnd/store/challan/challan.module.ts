import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DirectComponent } from './direct/direct.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'direct', component: DirectComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ApprovalComponent, LogComponent, DirectComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ChallanModule { }
