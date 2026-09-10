import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common'; 
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { LogComponent } from './log/log.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: LogComponent},
  { path: 'new', component: NewComponent},
];

@NgModule({
  declarations: [LogComponent, NewComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ChecklistModule { }
