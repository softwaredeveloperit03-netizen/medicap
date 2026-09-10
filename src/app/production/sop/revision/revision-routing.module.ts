import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { LogComponent } from './log/log.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { EditComponent } from './edit/edit.component';
import { HistoryComponent } from './history/history.component';
import { RequestComponent } from './request/request.component';
import { RequestCheckingComponent } from './request-checking/request-checking.component';
import { ChangecontrolComponent } from './changecontrol/changecontrol.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'request', component: RequestComponent},
  { path: 'request-checking', component: RequestCheckingComponent},
  { path: 'changecontrol', component: ChangecontrolComponent},
  { path: 'edit', component: EditComponent},
  { path: 'history', component: HistoryComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class RevisionRoutingModule { }
