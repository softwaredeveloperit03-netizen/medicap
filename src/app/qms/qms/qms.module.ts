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
  { path: 'maintenance', loadChildren: () => import('./maintenance/maintenance.module').then(m=>m.MaintenanceModule), data: {preload: false}},
  { path: 'deviation', loadChildren: () => import('./deviation/deviation.module').then(m=>m.DeviationModule), data: {preload: false}},
  { path: 'changecontrol', loadChildren: () => import('./changecontrol/changecontrol.module').then(m=>m.ChangecontrolModule), data: {preload: false}},
  { path: 'sops', loadChildren: () => import('./sops/sops.module').then(m=>m.SOPSModule), data: {preload: false}},
  {path:'incident',loadChildren:() => import('./incidents/incidents-routing.module').then(m=>m.IncidentsRoutingModule),data:{preload:false}},
  // { path: 'incidents', loadChildren: () => import('./incidents/incidents.module').then(m=>m.IncidentsModule), data: {preload: false}},
  { path: 'capa', loadChildren: () => import('./capa/capa.module').then(m=>m.CapaModule), data: {preload: false}}
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
export class QmsModule { }
