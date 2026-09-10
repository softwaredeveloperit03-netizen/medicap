import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { PlanComponent } from './plan/plan.component';
import { TeamComponent } from './team/team.component';
import { AnnoucementComponent } from './annoucement/annoucement.component';
import { ChecklistComponent } from './checklist/checklist.component';
import { ReportComponent } from './report/report.component';
import { LogComponent } from './log/log.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { AuditcheckComponent } from './auditcheck/auditcheck.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'plan', component: PlanComponent},
  { path: 'team', component: TeamComponent},
  { path: 'annoucement', component: AnnoucementComponent},
  { path: 'checklist', component: ChecklistComponent},
  { path: 'report', component: ReportComponent},
  { path: 'log', component: LogComponent},
  { path: 'Auditcheck', component: AuditcheckComponent}
];

@NgModule({
  declarations: [DashboardComponent, PlanComponent, TeamComponent, AnnoucementComponent, ChecklistComponent, ReportComponent, LogComponent, AuditcheckComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class AuditModule { }
