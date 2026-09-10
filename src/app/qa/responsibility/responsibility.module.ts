import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { StandardComponent } from './standard/standard.component';
import { PendingComponent } from './pending/pending.component';
import { LogComponent } from './log/log.component';
import { ApprovalComponent } from './approval/approval.component';
import { ResponsibilitiesComponent } from './responsibilities/responsibilities.component';
import { NewEmpComponent } from './new-emp/new-emp.component';
import { ExistingEmpComponent } from './existing-emp/existing-emp.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: ExistingEmpComponent},
  { path: 'standard', component: StandardComponent},
  { path: 'pending', component: PendingComponent},
  { path: 'log', component: LogComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'responsibilities', component: ResponsibilitiesComponent},
  { path: 'new-emp', component: NewEmpComponent},
  { path: 'existing-emp', component: ExistingEmpComponent},
  { path: 'new', component: NewComponent}
  // { path: '', component: DashboardComponent},
  // { path: 'standard', component: StandardComponent},
  // { path: 'pending', component: PendingComponent},
  // { path: 'log', component: LogComponent},
  // { path: 'approval', component: ApprovalComponent},
  // { path: 'responsibilities', component: ResponsibilitiesComponent},
  // { path: 'new-emp', component: NewEmpComponent},
  // { path: 'existing-emp', component: ExistingEmpComponent},
  // { path: 'new', component: NewComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    StandardComponent,
    PendingComponent, 
    LogComponent, 
    ApprovalComponent, 
    ResponsibilitiesComponent, 
    NewEmpComponent, 
    ExistingEmpComponent, NewComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
    ReactiveFormsModule
  ]
})
export class ResponsibilityModule { }
