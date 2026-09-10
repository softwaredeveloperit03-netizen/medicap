import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { PoapprovalComponent } from './poapproval/poapproval.component';
import { InductionComponent } from './induction/induction.component';
import { TranslateModule } from '@ngx-translate/core';





const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'poapproval', component: PoapprovalComponent},
  { path: 'induction', component: InductionComponent},

  { path: 'indent', loadChildren: () => import('./indent/indent.module').then(m=>m.IndentModule), data: {preload: false}},
];
 

@NgModule({
  declarations: [
    DashboardComponent,PoapprovalComponent, InductionComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ViceprModule { }
