import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { CleaningComponent } from './cleaning/cleaning.component';
import { DailyCheckingComponent } from './daily-checking/daily-checking.component';
import { DailyComponent } from './daily/daily.component';
import { HomeComponent } from './home/home.component';
import { ListComponent } from './list/list.component';
import { UncertinityCheckingComponent } from './uncertinity-checking/uncertinity-checking.component';
import { UncertinityComponent } from './uncertinity/uncertinity.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'list', component: ListComponent},
  { path: 'daily', component: DailyComponent},
  { path: 'daily-checking', component: DailyCheckingComponent},
  { path: 'uncertinity', component: UncertinityComponent},
  { path: 'uncertinity-checking', component: UncertinityCheckingComponent},
  { path: 'cleaning', component: CleaningComponent},
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class BalanceRoutingModule { }
