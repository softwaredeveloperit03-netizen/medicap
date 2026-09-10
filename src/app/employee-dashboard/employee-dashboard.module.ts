import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { PendingDocumentComponent } from './pending-document/pending-document.component';
import { InductionComponent } from './induction/induction.component';
import { LeaveComponent } from './leave/leave.component';
import { LeaveStatusComponent } from './leave-status/leave-status.component';
import { LeaveCardComponent } from './leave-card/leave-card.component';
import { ChargeAcceptanceComponent } from './charge-acceptance/charge-acceptance.component';
import { TasksComponent } from './tasks/tasks.component';
import { ShiftchangeComponent } from './shiftchange/shiftchange.component';
import { ApprisalrequestComponent } from './apprisalrequest/apprisalrequest.component';
import { ShiftsComponent } from './shifts/shifts.component';
 import { IndividualComponent } from './individual/individual.component';
import { VisitorsComponent } from './visitors/visitors.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: 'dashboard', component: DashboardComponent},
  {path: 'pending-document', component: PendingDocumentComponent},
  {path: 'shift_change', component: ShiftchangeComponent},
  {path: 'pending-leavs', component: LeaveComponent},
  {path: 'leave_status', component: LeaveStatusComponent},
  {path: 'card_leave', component: LeaveCardComponent},
  {path: 'charge_acceptance', component: ChargeAcceptanceComponent},
  {path: 'apprisal', component: ApprisalrequestComponent},
  {path: 'shifts', component: ShiftsComponent},
  {path: 'individual', component: IndividualComponent},
  {path: 'tasks', component: TasksComponent},
  {path: 'visitors', component: VisitorsComponent},
  {path: 'induction', component:InductionComponent},
  { path: 'payrole', loadChildren: () => import('./salary/salary.module').then(m=>m.SalaryModule), data: {preload: false}},
  { path: 'training', loadChildren: () => import('./training/training.module').then(m=>m.TrainingModule)},
  { path: 'interview', loadChildren: () => import('./interview/interview.module').then(m=>m.InterviewModule), data: {preload: false}},
  { path: 'resignation', loadChildren: () => import('./resignation/resignation.module').then(m=>m.ResignationModule), data: {preload: false}},
  { path: 'pending', loadChildren: () => import('./pending/pending.module').then(m=>m.PendingModule), data: {preload: false}},
  { path: 'loan', loadChildren: () => import('./loan/loan.module').then(m=>m.LoanModule), data: {preload: false}},
  { path: 'duty', loadChildren: () => import('./duty/duty.module').then(m=>m.DutyModule), data: {preload: false}},
  { path: 'jobres', loadChildren: () => import('./jobres/jobres.module').then(m=>m.JobresModule), data: {preload: false}},
  { path: 'outpass', loadChildren: () => import('./outpass/outpass.module').then(m=>m.OutpassModule), data: {preload: false}},

];

@NgModule({
  declarations: [
    DashboardComponent,
    PendingDocumentComponent,
    InductionComponent,
    LeaveComponent,
    LeaveStatusComponent,
    LeaveCardComponent,
    ChargeAcceptanceComponent,
    TasksComponent,
    ApprisalrequestComponent,
    ShiftchangeComponent,
    ShiftsComponent,
     IndividualComponent,
     VisitorsComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class EmployeeDashboardModule { }
