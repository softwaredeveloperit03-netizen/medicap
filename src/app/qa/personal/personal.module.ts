import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { EmployeeComponent } from './employee/employee.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TheroticalTestComponent } from './therotical-test/therotical-test.component';
import { PracticalTestComponent } from './practical-test/practical-test.component';
import { TheroticalTestApprovalComponent } from './therotical-test-approval/therotical-test-approval.component';
import { RouterModule, Routes } from '@angular/router';
import { AnalystComponent } from './analyst/analyst.component';
import { QualCertComponent } from './qual-cert/qual-cert.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'employee', component: EmployeeComponent },
  { path: 'therotical-test', component: TheroticalTestComponent },
  { path: 'pratical-test', component: PracticalTestComponent },
  { path: 'analyst', component: AnalystComponent },
  { path: 'qual-cert', component: QualCertComponent },
  { path: 'therotical', loadChildren: () => import('./therotical/therotical.module').then(m=>m.TheroticalModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, EmployeeComponent, TheroticalTestComponent, PracticalTestComponent, TheroticalTestApprovalComponent, AnalystComponent,QualCertComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PersonalModule { }
