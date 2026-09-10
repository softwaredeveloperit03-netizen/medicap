import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { HomeComponent } from './home/home.component';
import { NewComponent } from './new/new.component';
import { ReportComponent } from './report/report.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'new', component: NewComponent},
  { path: 'report', component: ReportComponent},
  { path: 'revision', loadChildren: () => import('./revision/revision.module').then(m=>m.RevisionModule)},
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class FinishRoutingModule { }
