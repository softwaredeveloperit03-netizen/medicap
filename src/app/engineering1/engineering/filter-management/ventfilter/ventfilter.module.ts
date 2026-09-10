import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CriticalComponent } from './critical/critical.component';
import { NoncriticalComponent } from './noncritical/noncritical.component';
import { ReplacementComponent } from './replacement/replacement.component';
import { IntegrityComponent } from './integrity/integrity.component';
import { ScheduleComponent } from './schedule/schedule.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  {path:'critical',component:CriticalComponent},
  {path:'non-critical',component:NoncriticalComponent},
  {path:'replacement',component:ReplacementComponent},
  {path:'integrity1',component:IntegrityComponent},
  {path:'schedule',component:ScheduleComponent},

];


@NgModule({
  declarations: [
    DashboardComponent,
    CriticalComponent,
    NoncriticalComponent,
    ReplacementComponent,
    IntegrityComponent,
    ScheduleComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class VentfilterModule { }
