import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DropdownModule } from 'primeng/dropdown';
import { MultiSelectModule } from 'primeng/multiselect';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: 'new', component: NewComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  declarations: [ NewComponent, LogComponent],
  imports: [
    SharedModule, TranslateModule,
   CommonModule,
    FormsModule,
    ClarityModule,
    DropdownModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class FeedbackModule { }
