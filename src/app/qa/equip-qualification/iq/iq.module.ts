import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { ApprovalComponent } from './approval/approval.component';
import { CheckingComponent } from './checking/checking.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  {path:'log',component:LogComponent},
  {path:'approval',component:ApprovalComponent},
  {path:'checking',component:CheckingComponent}
];


@NgModule({
  declarations: [DashboardComponent, NewComponent, LogComponent, ApprovalComponent, CheckingComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class IqModule { }
