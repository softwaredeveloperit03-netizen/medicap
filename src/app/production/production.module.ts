import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { QaApprovedPlansComponent } from './qa-approved-plans/qa-approved-plans.component';
import { DashboardNewComponent } from './dashboard-new/dashboard-new.component';
import { TranslateModule } from '@ngx-translate/core';
import { CheckShortagesComponent } from './forms/check-shortages/check-shortages.component';
import { TemperatureModule } from '../store/temperature/temperature.module';
import { TemperatureComponent } from '../store/temperature/temperature.component';


const routes: Routes = [
  { path: '', component: DashboardNewComponent},
  { path: 'qa-approved-plans', component: QaApprovedPlansComponent},
  { path: 'check-shortages', component: CheckShortagesComponent},
  { path: 'temperature', component: TemperatureComponent, data: { temperatureDepartment: 'Production', closeRoute: '/production' } },
  {
    path: 'equipment-calibration',
    loadChildren: () =>
      import('src/app/shared/equipment-dept-calibration/equipment-dept-calibration.module').then(
        (m) => m.EquipmentDeptCalibrationModule
      ),
    data: { performDepartment: 'Production', closeRoute: '/production' },
  },
  { path: 'ebmr', loadChildren: () => import('./ebmr/ebmr.module').then(m=>m.EbmrModule), data: {preload: false}},
  { path: 'rejection', loadChildren: () => import('./rejection/rejection.module').then(m=>m.RejectionModule), data: {preload: false}},
  { path: 'lmr', loadChildren: () => import('./lmr/lmr.module').then(m=>m.LmrModule), data: {preload: false}},
  { path: 'bmr', loadChildren: () => import('./bmr/bmr.module').then(m=>m.BmrModule), data: {preload: false}},
  { path: 'logbooks', loadChildren: () => import('./logbooks/logbooks.module').then(m=>m.LogbooksModule), data: {preload: false}},
  { path: 'deviation', loadChildren: () => import('./deviation/deviation.module').then(m=>m.DeviationModule), data: {preload: false}},
  { path: 'changecontrol', loadChildren: () => import('./changecontrol/changecontrol.module').then(m=>m.ChangecontrolModule), data: {preload: false}},
  { path: 'incidents', loadChildren: () => import('./incidents/incidents.module').then(m=>m.IncidentsModule), data: {preload: false}},
  { path: 'maintenance', loadChildren: () => import('./maintenance/maintenance.module').then(m=>m.MaintenanceModule), data: {preload: false}},
  { path: 'technical', loadChildren: () => import('./technical/technical.module').then(m=>m.TechnicalModule), data: {preload: false}},
  { path: 'technical-info', loadChildren: () => import('./technical-info/technical-info.module').then(m=>m.TechnicalInfoModule), data: {preload: false}},
  { path: 'additional', loadChildren: () => import('./additional/additional.module').then(m=>m.AdditionalModule), data: {preload: false}},
   { path: 'qms', loadChildren: () => import('./qms/qms.module').then(m=>m.QmsModule), data: {preload: false}},
  { path: 'stages-master', loadChildren: () => import('./stages-master/stages-master.module').then(m=>m.StagesMasterModule), data: {preload: false}},
  { path: 'yield-master', loadChildren: () => import('./yield-master/yield-master.module').then(m=>m.YieldMasterModule), data: {preload: false}},
  { path: 'batch-planning', loadChildren: () => import('./batch-planning/batch-planning.module').then(m=>m.BatchPlanningModule), data: {preload: false}},
  { path: 'batch', loadChildren: () => import('./batch/batch.module').then(m=>m.BatchModule), data: {preload: false}},
  { path: 'process', loadChildren: () => import('./process/process.module').then(m=>m.ProcessModule), data: {preload: false}},
  

];

@NgModule({
  declarations: [DashboardComponent, QaApprovedPlansComponent, DashboardNewComponent, CheckShortagesComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    TemperatureModule,
    RouterModule.forChild(routes)
  ]
})
export class ProductionModule { }
