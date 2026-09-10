import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LogComponent } from './log/log.component';
import { NewComponent } from './new/new.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { FrequencyComponent } from './frequency/frequency.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', component: LogComponent},
  {path: 'new', component: NewComponent},
  {path: 'frequency', component: FrequencyComponent},
 
]

@NgModule({
  declarations: [
    LogComponent,
    NewComponent,
    FrequencyComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes)
  ]
})
export class TopicModule { }
