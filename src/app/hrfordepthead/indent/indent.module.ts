import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ApprovalComponent } from './approval/approval.component';
 import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';

 

const routes: Routes = [
  { path: '', component: DashboardComponent},
   { path: 'approval', component: ApprovalComponent},
]

@NgModule({
  declarations: [DashboardComponent,ApprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})


export class IndentModule { }
