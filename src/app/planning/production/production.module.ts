import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { PlanFromPoComponent } from './plan-from-po/plan-from-po.component';
import { DirectPlanComponent } from './direct-plan/direct-plan.component';
import { NewApiComponent } from './new-api/new-api.component';
import { BulkplanComponent } from './bulkplan/bulkplan.component';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewApiComponent},
  { path: 'new-formulation', component: NewComponent},
  { path: 'direct-plant', component: DirectPlanComponent},
  { path: 'bulk_plan', component: PlanFromPoComponent},
  { path: 'bulk', component: BulkplanComponent},

];

@NgModule({
  declarations: [DashboardComponent,NewComponent, PlanFromPoComponent, DirectPlanComponent, NewApiComponent, BulkplanComponent],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ProductionModule { }
