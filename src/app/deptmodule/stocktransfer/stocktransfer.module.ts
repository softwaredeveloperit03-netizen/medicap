import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApprovalComponent } from './approval/approval.component';
import { RequestComponent } from './request/request.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';

 



const routes: Routes = [
  { path: '', component: DashboardComponent},

  { path: 'approval', component: ApprovalComponent},
  { path: 'request', component: RequestComponent},

 
 
];





@NgModule({
  declarations: [
    ApprovalComponent,
    RequestComponent,
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
     FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class StocktransferModule { }
