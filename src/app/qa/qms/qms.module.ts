import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DeptreviewComponent } from './change-control/deptreview/deptreview.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'dept_review', component: DeptreviewComponent },
  {
    path: 'change-control',
    loadChildren: () =>
      import('./change-control/change-control.module').then(
        (m) => m.ChangeControlModule
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
    path: 'sops',
    loadChildren: () => import('./sops/sops.module').then((m) => m.SOPSModule),
    data: { preload: false },
  },
  {
    path: 'risk',
    loadChildren: () => import('./risk/risk.module').then((m) => m.RiskModule),
    data: { preload: false },
  },
  {
    path: 'capa',
    loadChildren: () => import('./capa/capa.module').then((m) => m.CapaModule),
    data: { preload: false },
  },
  {
    path: 'incident',
    loadChildren: () => import('./incident/incident.module').then((m) => m.IncidentModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [DashboardComponent, DeptreviewComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class QmsModule {}
