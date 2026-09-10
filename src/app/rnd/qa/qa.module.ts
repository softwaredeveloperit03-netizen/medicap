import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'master', loadChildren: () => import('./master/master.module').then(m=>m.MasterModule)},
  { path: 'controlsample', loadChildren: () => import('./controlsample/controlsample.module').then(m=>m.ControlsampleModule)},
  { path: 'risk', loadChildren: () => import('./risk/risk.module').then(m=>m.RiskModule)},
  { path: 'technicaldoc', loadChildren: () => import('./technicaldoc/technicaldoc.module').then(m=>m.TechnicaldocModule)},
  { path: 'deviation', loadChildren: () => import('./deviation/deviation.module').then(m=>m.DeviationModule)},
  { path: 'changecontrol', loadChildren: () => import('./changecontrol/changecontrol.module').then(m=>m.ChangecontrolModule)},
  { path: 'incident', loadChildren: () => import('./incidents/incidents.module').then(m=>m.IncidentsModule)},
  { path: 'soops', loadChildren: () => import('./sops/sops.module').then(m=>m.SopsModule)},
  
  { path: 'stability', loadChildren: () => import('./stability/stability.module').then(m=>m.StabilityModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class QaModule { }
