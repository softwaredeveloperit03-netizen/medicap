import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { DasboardComponent } from './dasboard/dasboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DasboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'log', component: LogComponent},
];
@NgModule({
  declarations: [
    NewComponent,
    DasboardComponent,
    LogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CleaningModule { }
