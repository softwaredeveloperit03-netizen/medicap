import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { LmrComponent } from './lmr/lmr.component';
import { BmrComponent } from './bmr/bmr.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ProductionReportComponent } from './production-report/production-report.component';
import { PlanListComponent } from './plan-list/plan-list.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'lmr', component: LmrComponent },
  { path: 'bmr', component: BmrComponent },
  { path: 'production-report', component: ProductionReportComponent },
  { path: 'plan-list', component: PlanListComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    LmrComponent,
    BmrComponent,
    ProductionReportComponent,
    PlanListComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class ProductionModule {}
