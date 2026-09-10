import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';
import { TemperatureModule } from '../temperature/temperature.module';
import { TemperatureComponent } from '../temperature/temperature.component';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'temperature', component: TemperatureComponent, data: { temperatureDepartment: 'Warehouse', closeRoute: '/store/wms' } },
  { path: 'track-material', loadChildren: () => import('./track-material/track-material.module').then(m=>m.TrackMaterialModule), data: {preload: false}},
  { path: 'track-palate', loadChildren: () => import('./track-palate/track-palate.module').then(m=>m.TrackPalateModule), data: {preload: false}},
  { path: 'track-location', loadChildren: () => import('./track-location/track-location.module').then(m=>m.TrackLocationModule), data: {preload: false}},
  { path: 'track-rack', loadChildren: () => import('./track-rack/track-rack.module').then(m=>m.TrackRackModule), data: {preload: false}},
  { path: 'track-lane', loadChildren: () => import('./track-lane/track-lane.module').then(m=>m.TrackLaneModule), data: {preload: false}},
  { path: 'mapping', loadChildren: () => import('./mapping/mapping.module').then(m=>m.MappingModule), data: {preload: false}},
  { path: 'graphical-view', loadChildren: () => import('./graphical-view/graphical-view.module').then(m=>m.GraphicalViewModule), data: {preload: false}},
  { path: 'logs', loadChildren: () => import('./logs/logs.module').then(m=>m.LogsModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    TemperatureModule,
    RouterModule.forChild(routes)
  ],
  providers: []
})
export class WmsModule { }
