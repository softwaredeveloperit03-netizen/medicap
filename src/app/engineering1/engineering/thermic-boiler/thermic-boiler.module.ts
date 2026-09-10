import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { OperationComponent } from './operation/operation.component';
import { MaintenanceComponent } from './maintenance/maintenance.component';
import { PreventiveComponent } from './preventive/preventive.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'operation', component: OperationComponent},
  { path: 'maintenance', component: MaintenanceComponent},
  { path: 'preventive', component: PreventiveComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    OperationComponent,
    MaintenanceComponent,
    PreventiveComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ThermicBoilerModule { }
