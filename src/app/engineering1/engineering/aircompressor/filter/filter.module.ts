import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ScheduleComponent } from './schedule/schedule.component';
import { ProcessComponent } from './process/process.component';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path:'', component: DashboardComponent},
  {path:'schedule', component: ScheduleComponent},
  {path:'process', component: ProcessComponent},
  {path:'log', component: LogComponent},

 ];

@NgModule({
  declarations: [
    DashboardComponent,
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
export class FilterModule { }
