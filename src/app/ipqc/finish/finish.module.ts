import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { CheckingComponent } from './checking/checking.component';
import { ArReportComponent } from './ar-report/ar-report.component';
import { RdsComponent } from './rds/rds.component';
import { CoaComponent } from './coa/coa.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes : Routes=[
  {path:'',component:DashboardComponent },
  { path:'awaiting' , component:AwaitingComponent},
  { path:'checking',component:CheckingComponent},
  { path:'ar',component:ArReportComponent},
  { path:'rds',component:RdsComponent},
  { path:'coa',component:CoaComponent},
];

@NgModule({
  declarations: [AwaitingComponent, DashboardComponent, CheckingComponent, ArReportComponent, RdsComponent, CoaComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class FinishModule { }
