import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { CheckingComponent } from './checking/checking.component';
import { HomeComponent } from './home/home.component';
import { LabelComponent } from './label/label.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: HomeComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'log', component: LogComponent},
  { path: 'label', component: LabelComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class GrnRoutingModule { }
