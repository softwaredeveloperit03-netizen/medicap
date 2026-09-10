import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { ApproveComponent } from './approve/approve.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { LogbookComponent } from './logbook/logbook.component';
import { NewCapaComponent } from './new-capa/new-capa.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent},
  { path: 'new', component: NewCapaComponent},
  { path: 'approve', component: ApproveComponent},
  { path: 'logbook', component: LogbookComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CapaRoutingModule { }
