import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DeviationApprovalComponent } from './approval/deviation-approval.component';
import { NewDeviationComponent } from './new/new-deviation.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { CapaComponent } from './capa/capa.component';
import { VerifyComponent } from './verify/verify.component';
import { ReviewComponent } from './review/review.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent },
  { path: 'new', component: NewDeviationComponent },
  { path: 'checking', component: CheckingComponent },
  { path: 'verify', component: VerifyComponent },
  { path: 'review', component: ReviewComponent },
  { path: 'approval', component: DeviationApprovalComponent },
  { path: 'log', component: LogComponent },
  { path: 'capa', component: CapaComponent }
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DeviationRoutingModule { }
