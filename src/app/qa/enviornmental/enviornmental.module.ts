import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'temperature', loadChildren: () => import('./temperature/temperature.module').then(m=>m.TemperatureModule), data: {preload: false}},
  { path: 'pressure', loadChildren: () => import('./pressure/pressure.module').then(m=>m.PressureModule), data: {preload: false}},
  { path: 'microbiological', loadChildren: () => import('./microbiological/microbiological.module').then(m=>m.MicrobiologicalModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, NewComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class EnviornmentalModule { }
