import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { CapaComponent } from './capa/capa.component';
import { LogComponent } from './log/log.component';
import { ReviewComponent } from './review/review.component';
import { CheckingComponent } from './checking/checking.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'log', component: LogComponent},
  { path: 'capa', component: CapaComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DeviationRoutingModule { }
