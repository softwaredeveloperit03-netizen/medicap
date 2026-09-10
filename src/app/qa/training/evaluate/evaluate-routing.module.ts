import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { HomeComponent } from './home/home.component';
import { LogComponent } from './log/log.component';
import { QuestionariesComponent } from './questionaries/questionaries.component';
import { ResultComponent } from './result/result.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', component: HomeComponent},
  {path: 'questions', component: QuestionariesComponent},
  {path: 'evaluation', component: ResultComponent},
  {path: 'log', component: LogComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class EvaluateRoutingModule { }
