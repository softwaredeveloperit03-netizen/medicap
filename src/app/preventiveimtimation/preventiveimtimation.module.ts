import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { PreventiveComponent } from './preventive/preventive.component';
import { InspectionComponent } from './inspection/inspection.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { CalendarModule } from 'angular-calendar';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'preventive', component: PreventiveComponent},
  { path: 'inspection', component: InspectionComponent},
 ];


@NgModule({
  declarations: [
    DashboardComponent,
    PreventiveComponent,
    InspectionComponent
  ],
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
export class PreventiveimtimationModule { }
