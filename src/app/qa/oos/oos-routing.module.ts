import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { OosApprovedComponent } from './oos-approved/oos-approved.component';
import { OosDashboardComponent } from './oos-dashboard/oos-dashboard.component';
import { OosLogComponent } from './oos-log/oos-log.component';
import { TrendComponent } from './trend/trend.component';
import { ChecklistComponent } from './checklist/checklist.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', redirectTo: 'oos-dashboard', pathMatch: 'full' },
  { path: 'oos-dashboard', component: OosDashboardComponent },
  { path: 'oos-log', component: OosLogComponent },
  { path: 'oos-approved', component: OosApprovedComponent},
  { path: 'trend', component: TrendComponent},
  { path: 'checklist', component: ChecklistComponent},


];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class OosRoutingModule { }
