import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { HomeComponent } from './home/home.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: HomeComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class AnalyticalRoutingModule { }
