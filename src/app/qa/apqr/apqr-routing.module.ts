import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ProcessComponent } from './process/process.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'process', component: ProcessComponent},
  { path: 'analytical', loadChildren: () => import('./analytical/analytical.module').then(m=>m.AnalyticalModule), data: {preload: false}},
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class ApqrRoutingModule { }
