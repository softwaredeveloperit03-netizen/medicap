import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { ApproveComponent } from './approve/approve.component';
import { CheckingComponent } from './checking/checking.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { LogbookComponent } from './logbook/logbook.component';
import { NewComponent } from './new/new.component';
import { ReviewComponent } from './review/review.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component:DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approve', component: ApproveComponent},
  { path: 'log', component: LogbookComponent},
  { path: 'review', component: ReviewComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CapaRoutingModule { }
