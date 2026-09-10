  import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ExternalComponent } from './external/external.component';
import { NewComponent } from './external/new/new.component';
import { LogbookComponent } from './logbook/logbook.component';
import { NewLogComponent } from './logbook/new-log/new-log.component';
import { ResignationComponent } from './resignation/resignation.component';
import { InhouseComponent } from './inhouse/inhouse.component';
import { EurothermComponent } from './eurotherm/eurotherm.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'resignation', component: ResignationComponent},

  { path: 'indend', loadChildren: () => import('./indend/indend.module').then(m=>m.IndendModule), data: {preload: false}},
  { path: 'calibration', loadChildren: () => import('./calibration/calibration.module').then(m=>m.CalibrationModule), data: {preload: false}},
  { path: 'maintenance', loadChildren: () => import('./maintenance/maintenance.module').then(m=>m.MaintenanceModule), data: {preload: false}},
  { path: 'preventive', loadChildren: () => import('./preventive/preventive.module').then(m=>m.PreventiveModule), data: {preload: false}},
  { path: 'inspection', loadChildren: () => import('./inspection/inspection.module').then(m=>m.InspectionModule), data: {preload: false}},
  { path: 'inword', loadChildren: () => import('./inword/inword.module').then(m=>m.InwordModule), data: {preload: false}},
  { path: 'spare', loadChildren: () => import('./spare/spare.module').then(m=>m.SpareModule), data: {preload: false}},
  { path: 'water', loadChildren: () => import('./water/water.module').then(m=>m.WaterModule), data: {preload: false}},
  { path: 'ahu', loadChildren: () => import('./ahu/ahu.module').then(m=>m.AhuModule), data: {preload: false}},
  { path: 'insectocutor', loadChildren: () => import('./insectocutor/insectocutor.module').then(m=>m.InsectocutorModule), data: {preload: false}},
  { path: 'electricity', loadChildren: () => import('./electricity/electricity.module').then(m=>m.ElectricityModule), data: {preload: false}},
  { path: 'aircompressor', loadChildren: () => import('./aircompressor/aircompressor.module').then(m=>m.AircompressorModule), data: {preload: false}},
  { path: 'earthing', loadChildren: () => import('./earthing/earthing.module').then(m=>m.EarthingModule), data: {preload: false}},
  { path: 'instrument', loadChildren: () => import('./instrument/instrument.module').then(m=>m.InstrumentModule), data: {preload: false}},
  { path: 'premises', loadChildren: () => import('./premises/premises.module').then(m=>m.PremisesModule), data: {preload: false}},
  { path: 'ventfilter', loadChildren: () => import('./ventfilter/ventfilter.module').then(m=>m.VentfilterModule), data: {preload: false}},
  { path: 'nitrogen', loadChildren: () => import('./nitrogen/nitrogen.module').then(m=>m.NitrogenModule), data: {preload: false}},
  { path: 'purifiedwater', loadChildren: () => import('./purifiedwater/purifiedwater.module').then(m=>m.PurifiedwaterModule), data: {preload: false}},
  { path: 'gaussmeter', loadChildren: () => import('./gaussmeter/gaussmeter.module').then(m=>m.GaussmeterModule), data: {preload: false}},
  { path: 'luxmeter', loadChildren: () => import('./luxmeter/luxmeter.module').then(m=>m.LuxmeterModule), data: {preload: false}},
  { path: 'facility', loadChildren: () => import('./facility/facility.module').then(m=>m.FacilityModule), data: {preload: false}},
  { path: 'electric', loadChildren: () => import('./electric/electric.module').then(m=>m.ElectricModule), data: {preload: false}},
  { path: 'en020', loadChildren: () => import('./en020/en020.module').then(m=>m.EN020Module), data: {preload: false}},
   { path: 'thermic-boiler', loadChildren: () => import('./thermic-boiler/thermic-boiler.module').then(m=>m.ThermicBoilerModule)},
  { path: 'equip-qualification', loadChildren: () => import('./equip-qualification/equip-qualification.module').then(m=>m.EquipQualificationModule), data: {preload: false}},
  { path: 'filter_management', loadChildren: () => import('./filter-management/filter-management.module').then(m=>m.FilterManagementModule), data: {preload: false}},
  { path: 'external', loadChildren: () => import('./external/external.module').then(m=>m.ExternalModule), data: {preload: false}},
   {path:'logbook',component:LogbookComponent},
  {path:'inhouse',component:InhouseComponent},
   {path:'newlog',component:NewLogComponent},
  {path:'eurotherm',component:EurothermComponent}

];
@NgModule({ 
  declarations: [DashboardComponent,ResignationComponent, ExternalComponent, InhouseComponent,NewComponent, LogbookComponent, NewLogComponent, EurothermComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class EngineeringModule { }
 