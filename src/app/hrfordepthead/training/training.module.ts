import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { HodapprovalComponent } from './hodapproval/hodapproval.component';
import { InductionComponent } from './induction/induction.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { QatrapprovalComponent } from './qatrapproval/qatrapproval.component';
import { InducevalComponent } from './induceval/induceval.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'approval', component: HodapprovalComponent},
  { path: 'QaApproval', component: QatrapprovalComponent},
  { path: 'induction', component: InductionComponent},
  { path: 'qaInductionApproval', component: InducevalComponent},


 
]



@NgModule({
  declarations: [
    DashboardComponent,
    HodapprovalComponent,
    InductionComponent,
    QatrapprovalComponent,
    InducevalComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TrainingModule { }
