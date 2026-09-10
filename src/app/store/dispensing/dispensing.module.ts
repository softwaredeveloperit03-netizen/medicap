import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { UtensilComponent } from './utensil/utensil.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'utensil', component: UtensilComponent},
  { path: 'ahu', loadChildren: () => import('./ahu/ahu.module').then(m=>m.AhuModule), data: {preload: false}},
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},
  { path: 'packing', loadChildren: () => import('./packing/packing.module').then(m=>m.PackingModule), data: {preload: false}},
  { path: 'liquid', loadChildren: () => import('./liquid/liquid.module').then(m=>m.LiquidModule), data: {preload: false}},
  { path: 'solvent', loadChildren: () => import('./solvent/solvent.module').then(m=>m.SolventModule), data: {preload: false}},
  { path: 'activity', loadChildren: () => import('./activity/activity.module').then(m=>m.ActivityModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, UtensilComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class DispensingModule { }
