import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { RouterModule, Routes } from '@angular/router';
import { HrDataService } from './hr-data.service';
import { GovagencyComponent}  from './masters/govagency/govagency.component';
import { PayroleComponent } from './payrole/payrole.component';
import { EmployeeFormComponent } from './employee-form/employee-form.component';
import { LetterComponent } from './letter/letter.component';
import { Payroll_dashboardComponent } from './payroll_dashboard/payroll_dashboard.component';
import { LetterFormComponent } from './letter-form/letter-form.component';
import { ResignationsComponent } from './resignations/resignations.component';
import { ApprisalrequestComponent } from './apprisalrequest/apprisalrequest.component';
import { AppraisaldashComponent } from './appraisaldash/appraisaldash.component';
import { ImportantdocumentComponent } from './importantdocument/importantdocument.component';
import { WhatsappNoComponent } from './whatsapp-no/whatsapp-no.component';
import { ExistingEmpComponent } from './existing-emp/existing-emp.component';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from '../shared/shared.module';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'Apprisalrequest', component: ApprisalrequestComponent},
  { path: 'apprisaldash', component: AppraisaldashComponent},
  { path: 'whatsappNo', component: WhatsappNoComponent},
  { path: 'JobResp', component: ExistingEmpComponent},
  { path: 'emp-form', component: EmployeeFormComponent},
  { path: 'employee-onboarding', loadChildren: () => import('./joining/joining.module').then(m=>m.JoiningModule), data: {preload: false}},
  { path: 'loan', loadChildren: () => import('./loan/loan.module').then(m=>m.LoanModule), data: {preload: false}},
  { path: 'payrole', loadChildren: () => import('./payrole_salary/payrole_salary.module').then(m=>m.Payrole_salaryModule), data: {preload: false}},
  { path: 'recruitment', loadChildren: () => import('./recruitment/recruitment.module').then(m=>m.RecruitmentModule), data: {preload: false}},
  { path: 'attendance', loadChildren: () => import('./attendance/attendance.module').then(m=>m.AttendanceModule), data: {preload: false}},
  { path: 'joining', loadChildren: () => import('./joining/joining.module').then(m=>m.JoiningModule), data: {preload: false}},
  { path: 'master', loadChildren: () => import('./master/master.module').then(m=>m.MasterModule), data: {preload: false}},
  { path: 'employees', loadChildren: () => import('./employees/employees.module').then(m=>m.EmployeesModule), data: {preload: false}},
  { path: 'labours', loadChildren: () => import('./labours/labours.module').then(m=>m.LaboursModule), data: {preload: false}},
  { path: 'medical', loadChildren: () => import('./medical/medical.module').then(m=>m.MedicalModule), data: {preload: false}},
  { path: 'training', loadChildren: () => import('./training/training.module').then(m=>m.TrainingModule), data: {preload: false}},
  { path: 'user', loadChildren: () => import('./user/user.module').then(m=>m.UserModule), data: {preload: false}},
  { path: 'leaves', loadChildren: () => import('./leaves/leaves.module').then(m=>m.LeavesModule), data: {preload: false}},
  { path: 'resignation', loadChildren: () => import('./resignation/resignation.module').then(m=>m.ResignationModule), data: {preload: false}},
  { path: 'task', loadChildren: () => import('./task/task.module').then(m=>m.TaskModule), data: {preload: false}},
  { path: 'mid-year', loadChildren: () => import('./mid-year/mid-year.module').then(m=>m.MidYearModule), data: {preload: false}},
  { path: 'achivement', loadChildren: () => import('./achivement/achivement.module').then(m=>m.AchivementModule), data: {preload: false}},
  { path: 'promotion', loadChildren: () => import('./promotion/promotion.module').then(m=>m.PromotionModule), data: {preload: false}},
  { path: 'performance', loadChildren: () => import('./performance/performance.module').then(m=>m.PerformanceModule), data: {preload: false}},
  { path: 'shift', loadChildren: () => import('./shift/shift.module').then(m=>m.ShiftModule), data: {preload: false}},
  { path: 'shift-schedule', loadChildren: () => import('./shiftschedule/shift-schedule.module').then(m=>m.ShiftScheduleModule), data: {preload: false}},
  {path:'ImportantdocumentComponent',component:ImportantdocumentComponent},
 
];
@NgModule({
  declarations: [DashboardComponent,ExistingEmpComponent,ResignationsComponent,
    GovagencyComponent,PayroleComponent, EmployeeFormComponent, LetterComponent, 
    LetterFormComponent, ApprisalrequestComponent, ImportantdocumentComponent,
    AppraisaldashComponent, WhatsappNoComponent],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    TranslateModule,
    RouterModule.forChild(routes)
  ],
  providers: [
    HrDataService
  ]
})
export class HrModule { }
