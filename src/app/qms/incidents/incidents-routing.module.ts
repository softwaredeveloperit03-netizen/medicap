import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormComponent } from './form/form.component';
import { CheckerComponent } from './checker/checker.component';
import { IncidentLogComponent } from './incident-log/incident-log.component';
import { TrendComponent } from './trend/trend.component';
import { ResignationComponent } from './resignation/resignation.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'form', component: FormComponent},
  { path: 'log', component: IncidentLogComponent},
  { path: 'checker', component: CheckerComponent},
  { path: 'trend', component: TrendComponent},
  { path: 'resignation', component: ResignationComponent},

];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class IncidentsRoutingModule { }
