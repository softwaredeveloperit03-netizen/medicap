import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { InvestigationReportComponent } from './investigation-report/investigation-report.component';
import { EmInvestigationComponent } from './em-investigation/em-investigation.component';
import { WaterInvestigationComponent } from './water-investigation/water-investigation.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import {MultiSelectModule} from 'primeng/multiselect';
import { NewReportComponent } from './new-report/new-report.component';
import { TrendComponent } from './trend/trend.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'em', component: EmInvestigationComponent},
  { path: 'report', component: InvestigationReportComponent},
  { path: 'water', component: WaterInvestigationComponent},
  { path: 'new', component: NewReportComponent},
  { path: 'trend', component: TrendComponent},
];
@NgModule({
  declarations: [
    DashboardComponent,
    InvestigationReportComponent,
    EmInvestigationComponent,
    WaterInvestigationComponent,
    NewReportComponent,
    TrendComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    MultiSelectModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class InvestigationModule { }
