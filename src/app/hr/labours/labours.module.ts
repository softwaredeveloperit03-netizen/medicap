import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { AttendanceComponent } from './attendance/attendance.component';
import { SalaryComponent } from './salary/salary.component';
import { LogComponent } from './log/log.component';
import { SalaryLogComponent } from './salary-log/salary-log.component';
import { LabourContractorComponent } from './labour-contractor/labour-contractor.component';
import { ContractorAgreementComponent } from './contractor-agreement/contractor-agreement.component';
import { BillContractorComponent } from './bill-contractor/bill-contractor.component';
import { LabourMangmentComponent } from './labour-mangment/labour-mangment.component';
import { ContractorManagementDashboardComponent } from './contractor-management/contractor-management-dashboard.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'contractor-management', component: ContractorManagementDashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'log', component: LogComponent},
  { path: 'attendance', component: AttendanceComponent},
  { path: 'salary', component: SalaryComponent},
  { path: 'salary-log', component: SalaryLogComponent},
  { path: 'labour-contractor', component: LabourContractorComponent},
  { path: 'contractor-agreement', component:  ContractorAgreementComponent},
  { path: 'bill-contractor', component:BillContractorComponent},
  { path: 'labour-management', component:LabourMangmentComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    ContractorManagementDashboardComponent,
    NewComponent,
    AttendanceComponent,
    SalaryComponent,
    LogComponent,
    SalaryLogComponent,
    LabourContractorComponent,
    ContractorAgreementComponent,
    LabourMangmentComponent,
    BillContractorComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class LaboursModule { }
