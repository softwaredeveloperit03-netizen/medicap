import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { UsagesComponent } from './usages/usages.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'usages', component: UsagesComponent},
  { path: 'calibraion', loadChildren: () => import('./calibration/calibration.module').then(m=>m.CalibrationModule), data: {preload: false}}
];

@NgModule({
  declarations: [
    DashboardComponent,
    UsagesComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ColonyCounterModule { }
