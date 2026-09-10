import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { EmplistComponent } from './emplist/emplist.component';
import { LeavelogComponent } from './leavelog/leavelog.component';
import { AttendanceComponent } from './attendance/attendance.component';
import { ResignedComponent } from './hr/employees/resigned/resigned.component';
import { GovtagencyComponent } from './govtagency/govtagency.component';
import { HolidayComponent } from './holiday/holiday.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NgxDocViewerModule } from 'ngx-doc-viewer';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ApprisalComponent } from './apprisal/apprisal.component';
import { ShiftapprComponent } from './shiftappr/shiftappr.component';
import { LeaveappComponent } from './leaveapp/leaveapp.component';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';






const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'emp_list', component: EmplistComponent},
  { path: 'apprisal', component: ApprisalComponent},
  { path: 'leaveapp', component: LeaveappComponent},
  { path: 'goveAgency', component: GovtagencyComponent},
  { path: 'holiday', component: HolidayComponent},
  { path: 'attendance', component: AttendanceComponent},
  { path: 'leaveLog', component: LeavelogComponent},

 
  { path: 'shiftappr', component: ShiftapprComponent},
  { path: 'requisition', loadChildren: () => import('./requisition/requisition.module').then(m=>m.RequisitionModule), data: {preload: false}},
  { path: 'shift', loadChildren: () => import('./shift/shift.module').then(m=>m.ShiftModule), data: {preload: false}},

 
 
];


@NgModule({
  declarations: [
    EmplistComponent,
    LeavelogComponent,
    AttendanceComponent,
    ResignedComponent,
    GovtagencyComponent,
    HolidayComponent,
    LeaveappComponent,
    ShiftapprComponent,
    ApprisalComponent,
    DashboardComponent,
  ],
  imports: [ TranslateModule,
    SharedModule,
    CommonModule,
    NgxDocViewerModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class HrModule { }
