import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { AppointmentletterComponent } from './appointmentletter/appointmentletter.component';
import { EmployeeJoiningComponent } from './employee-joining/employee-joining.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'appointment', component: AppointmentletterComponent},
  { path: 'employeeJoining', component: EmployeeJoiningComponent},
];






@NgModule({
  declarations: [DashboardComponent, AppointmentletterComponent,EmployeeJoiningComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class JoiningModule { }
