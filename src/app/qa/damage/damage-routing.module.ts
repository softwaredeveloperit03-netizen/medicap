import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { ApprovalComponent } from './approval/approval.component';
import { HomeComponent } from './home/home.component';
import { LogComponent } from './log/log.component';
import { DeptheadappComponent } from './deptheadapp/deptheadapp.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'deptheadapp', component: DeptheadappComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DamageRoutingModule { }
