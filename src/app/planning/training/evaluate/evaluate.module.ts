import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { EvaluateRoutingModule } from './evaluate-routing.module';
import { HomeComponent } from './home/home.component';
import { QuestionariesComponent } from './questionaries/questionaries.component';
import { ResultComponent } from './result/result.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { Routes } from '@angular/router';


const routes: Routes = [
  {path: '', component: HomeComponent},
  {path: 'questions', component: QuestionariesComponent},
  {path: 'evaluation', component: ResultComponent},
  {path: 'log', component: LogComponent}
];

@NgModule({
  declarations: [HomeComponent, QuestionariesComponent, ResultComponent, LogComponent],
  imports: [
    CommonModule,
    EvaluateRoutingModule,
    FormsModule,
    ClarityModule
  ]
})
export class EvaluateModule { }
