import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { ChangeControlApprovalComponent } from './approval/approval.component';
import { CheckingComponent } from './checking/checking.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ImplementationComponent } from './implementation/implementation.component';
import { LogComponent } from './log/log.component';
import { NewChangeControlComponent } from './new/new.component';
import { VerifyComponent } from './verify/verify.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent},
  { path: 'new', component: NewChangeControlComponent},
  { path: 'approve', component: ChangeControlApprovalComponent},
  { path: 'log', component: LogComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'verify', component: VerifyComponent},
  { path: 'implement', component: ImplementationComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class ChangecontrolRoutingModule { }
