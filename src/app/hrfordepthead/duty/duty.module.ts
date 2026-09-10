import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { DutyApprovalComponent } from './approval/approval.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  {path: '', component: DashboardComponent},
  {path: 'new', component: NewComponent},
  {path: 'approval', component: DutyApprovalComponent},
];
@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    DutyApprovalComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class DutyModule { }
