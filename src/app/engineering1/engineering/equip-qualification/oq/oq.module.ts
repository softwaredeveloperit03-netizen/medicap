import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FinalReportComponent } from './final-report/final-report.component';
import { InitialOqComponent } from './initial-oq/initial-oq.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'final-report', component: FinalReportComponent },
  { path: 'InitialOq', component: InitialOqComponent },

  {
    path: 'calibration',
    loadChildren: () =>
      import('./calibration/calibration.module').then(
        (m) => m.CalibrationModule
      ),
    data: { preload: false },
  },
  {
    path: 'deviation',
    loadChildren: () =>
      import('./deviation/deviation.module').then((m) => m.DeviationModule),
    data: { preload: false },
  },
  {
    path: 'manufacture',
    loadChildren: () =>
      import('./manufacture/manufacture.module').then(
        (m) => m.ManufactureModule
      ),
    data: { preload: false },
  },
  {
    path: 'operator-check',
    loadChildren: () =>
      import('./operator-check/operator-check.module').then(
        (m) => m.OperatorCheckModule
      ),
    data: { preload: false },
  },
  {
    path: 'trials',
    loadChildren: () =>
      import('./trials/trials.module').then((m) => m.TrialsModule),
    data: { preload: false },
  },
  {
    path: 'utility',
    loadChildren: () =>
      import('./utility/utility.module').then((m) => m.UtilityModule),
    data: { preload: false },
  },
  {
    path: 'varification-sop',
    loadChildren: () =>
      import('./varification-sop/varification-sop.module').then(
        (m) => m.VarificationSopModule
      ),
    data: { preload: false },
  },
  {
    path: 'standard',
    loadChildren: () =>
      import('./standard/standard.module').then((m) => m.StandardModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [
    DashboardComponent,
    FinalReportComponent,
    InitialOqComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class OqModule { }
