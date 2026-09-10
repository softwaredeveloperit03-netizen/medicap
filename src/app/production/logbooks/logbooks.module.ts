import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { MaintenanceComponent } from './maintenance/maintenance.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'maintenance', component: MaintenanceComponent},
  { path: 'equipments', loadChildren: () => import('./equipments/equipments.module').then(m=>m.EquipmentsModule)},
  { path: 'pressure', loadChildren: () => import('./pressure/pressure.module').then(m=>m.PressureModule)},
  { path: 'temperature', loadChildren: () => import('./temperature/temperature.module').then(m=>m.TemperatureModule)},
];

@NgModule({
  declarations: [DashboardComponent, MaintenanceComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class LogbooksModule { }
