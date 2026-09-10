import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from 'src/app/shared/shared.module';
import { EquipmentDeptCalibrationDashboardComponent } from './dashboard/dashboard.component';
import { EquipmentDeptCalibrationPendingComponent } from './pending/pending.component';
import { EquipmentDeptCalibrationLogComponent } from './log/log.component';

const routes: Routes = [
  { path: '', component: EquipmentDeptCalibrationDashboardComponent },
  { path: 'pending', component: EquipmentDeptCalibrationPendingComponent },
  { path: 'log', component: EquipmentDeptCalibrationLogComponent },
];

@NgModule({
  declarations: [
    EquipmentDeptCalibrationDashboardComponent,
    EquipmentDeptCalibrationPendingComponent,
    EquipmentDeptCalibrationLogComponent,
  ],
  imports: [CommonModule, FormsModule, ClarityModule, TranslateModule, SharedModule, RouterModule.forChild(routes)],
})
export class EquipmentDeptCalibrationModule {}
