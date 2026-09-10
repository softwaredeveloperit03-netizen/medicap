import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { YieldComponent } from './yield/yield.component';
import { DispatchReportComponent } from './dispatch-report/dispatch-report.component';
import { BatchreleaseComponent } from './batchrelease/batchrelease.component';
import { ShortageComponent } from './shortage/shortage.component';
import { DeviationComponent } from './deviation/deviation.component';
import { ChangecontrolComponent } from './changecontrol/changecontrol.component';
import { IncidentComponent } from './incident/incident.component';
import { CapaComponent } from './capa/capa.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DispatchComponent } from './dispatch/dispatch.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: 'batchrelease', component: BatchreleaseComponent},
  { path: 'capa', component: CapaComponent},
  { path: 'change-control', loadChildren: () => import('./change-control/change-control.module').then(m=>m.ChangeControlModule), data: {preload: false}},
  { path: 'deviation', loadChildren: () => import('./deviation/deviation.module').then(m=>m.DeviationModule), data: {preload: false}},
 
  { path: 'incident', component: IncidentComponent},
  { path: 'shortage', component: ShortageComponent},
  { path: 'statement', component: YieldComponent},
  { path: 'report', component: DispatchReportComponent},
];

@NgModule({
  declarations: [YieldComponent, DispatchComponent, BatchreleaseComponent, ShortageComponent, DeviationComponent, ChangecontrolComponent, IncidentComponent, CapaComponent,DispatchReportComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
    
  ]
})
export class ReportModule { }
