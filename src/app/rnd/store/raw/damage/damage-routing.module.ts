import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { ApprovalComponent } from './approval/approval.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { HomeComponent } from './home/home.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DamageRoutingModule { }
