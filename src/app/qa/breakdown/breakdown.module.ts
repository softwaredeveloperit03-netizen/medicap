import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { PreapprovalComponent } from './preapproval/preapproval.component';
import { PreliminaryComponent } from './preliminary/preliminary.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ApprovalComponent } from './approval/approval.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'approval', component: PreapprovalComponent},
  { path: 'preliminary', component: PreliminaryComponent},
  { path: 'ebmapproval', component: ApprovalComponent},

];



@NgModule({
  declarations: [
    DashboardComponent,
    PreapprovalComponent,
    PreliminaryComponent,
    ApprovalComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class BreakdownModule { }
