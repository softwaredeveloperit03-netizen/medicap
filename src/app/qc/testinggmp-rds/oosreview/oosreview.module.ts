import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { ReviewComponent } from './review/review.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
 import { ReanalysisComponent } from './reanalysis/reanalysis.component';
import { DocsIconsModule } from '../../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'log', component: LogComponent},
  { path: 'reanalysis', component: ReanalysisComponent},
  { path: 'phase1', loadChildren: () => import('./phase1/phase1.module').then(m=>m.Phase1Module), data: {preload: false}},
  { path: 'phase3', loadChildren: () => import('./phase3/phase3.module').then(m=>m.Phase3Module), data: {preload: false}},


 ]; 


@NgModule({
  declarations: [
    DashboardComponent,
    CheckingComponent,
    LogComponent,
    ReviewComponent,
     ReanalysisComponent,
   ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class OosreviewModule { }
