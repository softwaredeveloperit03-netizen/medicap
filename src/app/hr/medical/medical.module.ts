import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { LogComponent } from './log/log.component';
import { DueComponent } from './due/due.component';
import { UploadReportComponent } from './upload-report/upload-report.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'checkup-log', component: LogComponent},
  { path: 'checkup-due', component: DueComponent},
  { path: 'upload-report', component: UploadReportComponent},
  { path: 'physicians', loadChildren: () => import('./physicians/physicians.module').then(m=>m.PhysiciansModule), data: {preload: false}},
  { path: 'reports', loadChildren: () => import('./reports/reports.module').then(m=>m.ReportsModule), data: {preload: false}},
  { path: 'employeemed', loadChildren: () => import('./employeemed/employeemed.module').then(m=>m.EmployeemedModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, NewComponent,LogComponent, DueComponent, UploadReportComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MedicalModule { }
