import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LogsComponent } from './logs.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DatePipe } from '@angular/common';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: LogsComponent}
];

@NgModule({
  declarations: [LogsComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ],
  providers: [DatePipe]
})
export class LogsModule { }
