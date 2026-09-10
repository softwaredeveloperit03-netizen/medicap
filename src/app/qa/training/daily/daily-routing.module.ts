import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { LogComponent } from './log/log.component';
import { NewComponent } from './new/new.component';
import { AnnoucementComponent } from './annoucement/annoucement.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AnnoucementCheckingComponent } from './annoucement-checking/annoucement-checking.component';
import { AnnoucementApprovalComponent } from './annoucement-approval/annoucement-approval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  // { path: '', component: DashboardComponent},
  { path: '', component: AnnoucementComponent},
  { path: 'annoucement-checking', component: AnnoucementCheckingComponent},
  { path: 'annoucement-approval', component: AnnoucementApprovalComponent},
  { path: 'new', component: NewComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DailyRoutingModule { }
