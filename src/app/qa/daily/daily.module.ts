import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { MicrobiologicalComponent } from './microbiological/microbiological.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'microbiological', component: MicrobiologicalComponent},
  { path: 'temperature', loadChildren: () => import('./temperature/temperature.module').then(m=>m.TemperatureModule), data: {preload: false}},
  { path: 'presure', loadChildren: () => import('./pressure/pressure.module').then(m=>m.PressureModule), data: {preload: false}},

];

@NgModule({
  declarations: [DashboardComponent,MicrobiologicalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class DailyModule { }
