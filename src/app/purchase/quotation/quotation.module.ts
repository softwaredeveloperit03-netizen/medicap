import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ReportComponent } from './report/report.component';
import { ComparativeComponent } from './comparative/comparative.component';
import { CorrectionComponent } from './correction/correction.component';
import { GennewComponent } from './gennew/gennew.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
  { path: 'report', component: ReportComponent},
  { path: 'comparative', component: ComparativeComponent},
  { path: 'correction', component: CorrectionComponent},
  { path: 'genNew', component: GennewComponent}
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ApprovalComponent, 
    LogComponent, ReportComponent, ComparativeComponent, CorrectionComponent, GennewComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class QuotationModule { }
