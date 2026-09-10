import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
 
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { VisitorManagementComponent } from './visitor-management/visitor-management.component';
import { AppointmentCalendarComponent } from './appointment-calendar/appointment-calendar.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
   { path: '', component: DashboardComponent},
   { path: 'visitor-management', component: VisitorManagementComponent},
   { path: 'appointment-calendar', component: AppointmentCalendarComponent},
 
];


@NgModule({
  declarations: [
    DashboardComponent,
    VisitorManagementComponent,
    AppointmentCalendarComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)  ]
})

export class ReceptionModule { }
