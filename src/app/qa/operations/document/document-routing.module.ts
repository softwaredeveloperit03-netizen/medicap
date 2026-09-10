import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DistributionComponent } from './distribution/distribution.component';
import { LogbookComponent } from './logbook/logbook.component';
import { ProcessComponent } from './process/process.component';
import { RequisitionComponent } from './requisition/requisition.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'requisition', component: RequisitionComponent},
  { path: 'distribution', component: DistributionComponent},
  { path: 'logbook', component: LogbookComponent},
  { path: 'process', component: ProcessComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DocumentRoutingModule { }
