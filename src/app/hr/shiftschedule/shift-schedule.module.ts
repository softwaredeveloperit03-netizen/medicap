import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from 'src/app/hr/shiftschedule/dashboard/dashboard.component';
import { ShiftChangeComponent } from './shift-change/shift-change.component';
import { ShiftChangeDeptComponent } from './shift-change-dept/shift-change-dept.component';
import { ShiftallocationComponent } from './shiftallocation/shiftallocation.component';
import { ShiftmanagementComponent } from './shiftmanagement/shiftmanagement.component';
import { ShiftscheduleComponent } from './shiftschedule/shiftschedule.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'shift-change', component: ShiftChangeComponent},
  { path: 'shift-chnage-dept', component: ShiftChangeDeptComponent},
  { path: 'shift-allocation', component: ShiftallocationComponent},
  { path: 'shift-management', component: ShiftmanagementComponent},
  { path: 'shift-schedule', component: ShiftscheduleComponent},
];

@NgModule({
  declarations: [DashboardComponent, ShiftChangeComponent, ShiftChangeDeptComponent,ShiftallocationComponent,ShiftmanagementComponent,ShiftscheduleComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ShiftScheduleModule { }
