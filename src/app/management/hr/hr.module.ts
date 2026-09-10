import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { EmployeeComponent } from './employee/employee.component';
import { AttenanceComponent } from './attenance/attenance.component';
import { IndividualComponent } from './individual/individual.component';
import { PerformanceComponent } from './performance/performance.component';
import { SalaryComponent } from './salary/salary.component';
import { WorkingComponent } from './working/working.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { LeaveRecordComponent } from './leave-record/leave-record.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'employee', component: EmployeeComponent },
  { path: 'attendance', component: AttenanceComponent },
  { path: 'individual', component: IndividualComponent },
  { path: 'salary', component: SalaryComponent },
  { path: 'performance', component: PerformanceComponent },
  { path: 'leave-record', component: LeaveRecordComponent },
  { path: 'working', component: WorkingComponent },
];

@NgModule({
  declarations: [DashboardComponent, EmployeeComponent, AttenanceComponent, IndividualComponent, PerformanceComponent, SalaryComponent, WorkingComponent, LeaveRecordComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
})
export class HrModule { }
