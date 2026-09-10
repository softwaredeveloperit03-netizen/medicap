import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DailyComponent } from './daily/daily.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  {
    path: 'equipment',
    loadChildren: () =>
      import('src/app/shared/equipment-dept-calibration/equipment-dept-calibration.module').then(
        (m) => m.EquipmentDeptCalibrationModule
      ),
    data: { performDepartment: 'Store', closeRoute: '/store/calibration' },
  },
  { path: 'daily', component: DailyComponent},
  { path: 'monthly', loadChildren: () => import('./monthly/monthly.module').then(m=>m.MonthlyModule), data: {preload: false}},
  { path: 'ooc', loadChildren: () => import('./ooc/ooc.module').then(m=>m.OocModule), data: {preload: false}},
];

@NgModule({
  declarations: [
    DashboardComponent,
    DailyComponent,
    
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CalibrationModule { }
