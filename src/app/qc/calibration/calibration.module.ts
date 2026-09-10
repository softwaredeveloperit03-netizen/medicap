import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent}, 
  {
    path: 'equipment',
    loadChildren: () =>
      import('src/app/shared/equipment-dept-calibration/equipment-dept-calibration.module').then(
        (m) => m.EquipmentDeptCalibrationModule
      ),
    data: { performDepartment: 'Quality Control', closeRoute: '/qc/calibration' },
  },
  {
    path: 'calender', loadChildren: ()=> import('./calender/calender.module').then(m=>m.CalenderModule), data: {preload: false},
  },
];

@NgModule({
  declarations: [
    DashboardComponent,                     
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
