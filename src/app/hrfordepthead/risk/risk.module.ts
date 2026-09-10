import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
 import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AwaitingreviewComponent } from './awaitingreview/awaitingreview.component';
import { LogreviewComponent } from './logreview/logreview.component';
import { FinalreviewComponent } from './finalreview/finalreview.component';
import { TranslateModule } from '@ngx-translate/core';

 

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'dept_review', component: AwaitingreviewComponent },
  { path: 'final_review', component: FinalreviewComponent },
  { path: 'log', component: LogreviewComponent },
   
];
@NgModule({
  declarations: [
    DashboardComponent,
    AwaitingreviewComponent,
    LogreviewComponent,
    FinalreviewComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class RiskModule { }
