import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { BulkDensityComponent } from './bulk-density/bulk-density.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { PolarimeterComponent } from './polarimeter/polarimeter.component';
import { PhMeterComponent } from './ph-meter/ph-meter.component';
import { FtirComponent } from './ftir/ftir.component';
import { PotentiometerComponent } from './potentiometer/potentiometer.component';
import { DailyComponent } from './daily/daily.component';
import { PdaComponent } from './pda/pda.component';
import { ProtocolComponent } from './protocol/protocol.component';
import { GcComponent } from './gc/gc.component';
import { KfComponent } from './kf/kf.component';
import { RiDetectorComponent } from './ri-detector/ri-detector.component';
import { UvCalibrationComponent } from './uv-calibration/uv-calibration.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'bulk' , component: BulkDensityComponent},
  { path: 'polarimeter', component: PolarimeterComponent},
  { path: 'ph-meter', component: PhMeterComponent},
  { path: 'ftir', component: FtirComponent},
  { path: 'potentiometer', component: PotentiometerComponent},
  { path: 'daily', component: DailyComponent},
  { path: 'pda', component: PdaComponent},
  { path: 'protocol', component: ProtocolComponent},
  { path: 'gc', component: GcComponent},
  { path: 'kf', component: KfComponent},
  { path: 'ri-detector', component: RiDetectorComponent},
  { path: 'uv-calibration', component: UvCalibrationComponent},
];


@NgModule({
  declarations: [
    BulkDensityComponent,
    DashboardComponent,
    PolarimeterComponent,
    PhMeterComponent,
    FtirComponent,
    PotentiometerComponent,
    DailyComponent,
    PdaComponent,
    ProtocolComponent,
    GcComponent,
    KfComponent,
    RiDetectorComponent,
    UvCalibrationComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class QcCalibrationModule { }
