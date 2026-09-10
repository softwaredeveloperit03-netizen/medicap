import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { CheckingComponent } from './checking/checking.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { LogComponent } from './log/log.component';
import { NewChangeControlComponent } from './new/new.component';
import { ChangeControlReviewComponent } from './review/review.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent},
  { path: 'new', component: NewChangeControlComponent},
  { path: 'log', component: LogComponent},
  { path: 'review', component: ChangeControlReviewComponent},
  { path: 'checking', component: CheckingComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class ChangecontrolRoutingModule { }
