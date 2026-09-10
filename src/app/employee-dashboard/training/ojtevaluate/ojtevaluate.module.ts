import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { HomeComponent } from './home/home.component';
import { QuestionariesComponent } from './questionaries/questionaries.component';
import { ResultComponent } from './result/result.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  {path: '', component: HomeComponent},
  {path: 'questions', component: QuestionariesComponent},
  {path: 'evaluation', component: ResultComponent},
  {path: 'log', component: LogComponent}
];


@NgModule({
  declarations: [HomeComponent, QuestionariesComponent, ResultComponent, LogComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
     FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class OjtevaluateModule { }
