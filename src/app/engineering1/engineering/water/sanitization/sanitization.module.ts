import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ScheduleComponent } from './schedule/schedule.component';
import { ProcessComponent } from './process/process.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: 'sanitization_schedule', component: ScheduleComponent },
  { path: 'sanitization_processs', component: ProcessComponent },
  { path: 'sanitization_logbook', component: LogComponent },
];

@NgModule({
  declarations: [
    ScheduleComponent,
    ProcessComponent,
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
export class SanitizationModule { }
