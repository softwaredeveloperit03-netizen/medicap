import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';

import { InductionComponent } from './induction/induction.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranapprovalComponent } from './tranapproval/tranapproval.component';
import { QatrapprovalComponent } from './qatrapproval/qatrapproval.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
   { path: 'induction', component: InductionComponent},
   { path: 'trainingApproval', component: TranapprovalComponent},
   { path: 'GentrainingApproval', component: QatrapprovalComponent},

  // { path: 'indent', loadChildren: () => import('./indent/indent.module').then(m=>m.IndentModule), data: {preload: false}},
];

@NgModule({
  declarations: [
    DashboardComponent,
    InductionComponent,
    QatrapprovalComponent,
    TranapprovalComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class QheadModule { }
