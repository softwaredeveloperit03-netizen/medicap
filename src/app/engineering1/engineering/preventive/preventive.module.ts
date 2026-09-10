import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { PlanComponent } from './plan/plan.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { HistoryComponent } from './history/history.component';
import { FrequencyComponent } from './frequency/frequency.component';
import { InspectionComponent } from './inspection/inspection.component';
import { CalenderComponent } from './calender/calender.component';
import { PrevencheckComponent } from './prevencheck/prevencheck.component';
import { PrevenapprovalComponent } from './prevenapproval/prevenapproval.component';
import { PreventiveComponent } from './preventive/preventive.component';
import { InspapproveComponent } from './inspapprove/inspapprove.component';
import { InspcheckComponent } from './inspcheck/inspcheck.component';
import { CalendarModule } from 'angular-calendar';
import { TranslateModule } from '@ngx-translate/core';
 

const routes: Routes = [ 
  { path: '', component: DashboardComponent},
  { path: 'plan', component: PlanComponent},
  { path: 'awiating', component: AwaitingComponent},
  { path: 'history', component: HistoryComponent},
  { path: 'frequency', component: FrequencyComponent},
  { path: 'inspection', component: InspectionComponent},
  { path: 'preventive', component: PreventiveComponent},
  { path: 'calender', component: CalenderComponent},
  { path: 'prevenChecking', component: PrevencheckComponent},
  { path: 'prevenApprove', component: PrevenapprovalComponent},
  { path: 'inspApproval', component: InspapproveComponent}, 
  { path: 'inspCheck', component: InspcheckComponent},
  // { path: 'checklist', loadChildren: () => import('./checklist/checklist.module').then(m=>m.ChecklistModule)},
  { path: 'checklist', loadChildren: () => import('./checklist/checklist.module').then(m=>m.ChecklistModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, PlanComponent, AwaitingComponent, HistoryComponent,FrequencyComponent, InspectionComponent,InspapproveComponent,InspcheckComponent, PrevencheckComponent,PrevenapprovalComponent,CalenderComponent,PreventiveComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    CalendarModule, 
    ReactiveFormsModule,
    RouterModule.forChild(routes)
  ]
})
export class PreventiveModule { }
