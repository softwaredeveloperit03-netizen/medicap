import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'map-material-palate', loadChildren: () => import('./map-material-palate/map-material-palate.module').then(m=>m.MapMaterialPalateModule), data: {preload: false}},
  { path: 'map-palate-location', loadChildren: () => import('./map-palate-location/map-palate-location.module').then(m=>m.MapPalateLocationModule), data: {preload: false}},
  { path: 'empty-material-palate', loadChildren: () => import('./empty-material-palate/empty-material-palate.module').then(m=>m.EmptyMaterialPalateModule), data: {preload: false}},
  { path: 'empty-palate-location', loadChildren: () => import('./empty-palate-location/empty-palate-location.module').then(m=>m.EmptyPalateLocationModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MappingModule { }
