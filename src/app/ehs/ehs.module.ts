import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ResignationComponent } from './resignation/resignation.component';
import { AttendenceComponent } from './attendence/attendence.component';
import { CommitteeComponent } from './committee/committee.component';
import { CommitteeAttendenceComponent } from './committee-attendence/committee-attendence.component';
import { AccidentreportComponent } from './accidentreport/accidentreport.component';
import { InspfireextinguisherComponent } from './inspfireextinguisher/inspfireextinguisher.component';
import { ToolboxattendComponent } from './toolboxattend/toolboxattend.component';
import { BiomedwasteComponent } from './biomedwaste/biomedwaste.component';
import { PhmeterComponent } from './phmeter/phmeter.component';
import { OptdsmeterComponent } from './optdsmeter/optdsmeter.component';
import { MocdrillComponent } from './mocdrill/mocdrill.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'resignation', component: ResignationComponent },
  { path: 'Attendence', component: AttendenceComponent },
  { path: 'Committee', component: CommitteeComponent },
  { path: 'CommitteeAttendence', component: CommitteeComponent },
  { path: 'Accidentreport', component: AccidentreportComponent },
  { path: 'Inspfireextinguisher', component: InspfireextinguisherComponent },
  { path: 'toolboxattend', component: ToolboxattendComponent},
  { path: 'biomedwaste', component: BiomedwasteComponent},
  { path: 'phmeter', component: PhmeterComponent},
  { path: 'optdsmeter', component: OptdsmeterComponent},
  { path: 'mocdrill', component: MocdrillComponent},

  {
    path: 'entry-permit',
    loadChildren: () =>
      import('./entry-permit/entry-permit.module').then(
        (m) => m.EntryPermitModule
      ),
    data: { preload: false },
  },
  {
    path: 'hot-work',
    loadChildren: () =>
      import('./hot-work/hot-work.module').then((m) => m.HotWorkModule),
    data: { preload: false },
  },
  {
    path: 'hotwork',
    loadChildren: () =>
      import('./hotwork/hotwork.module').then((m) => m.HotworkModule),
    data: { preload: false },
  },
  {
    path: 'excavation-work',
    loadChildren: () =>
      import('./excavation-work/excavation-work.module').then(
        (m) => m.ExcavationWorkModule
      ),
    data: { preload: false },
  },
  {
    path: 'work-height',
    loadChildren: () =>
      import('./work-height/work-height.module').then(
        (m) => m.WorkHeightModule
      ),
    data: { preload: false },
  },
  {
    path: 'inprocess',
    loadChildren: () =>
      import('./inprocess/inprocess.module').then((m) => m.InprocessModule),
    data: { preload: false },
  },
  {
    path: 'qms',
    loadChildren: () => import('./qms/qms.module').then((m) => m.QmsModule),
    data: { preload: false },
  },
  {
    path: 'training',
    loadChildren: () =>
      import('./training/training.module').then((m) => m.TrainingModule),
    data: { preload: false },
  },
  {
    path: 'firehydrant',
    loadChildren: () =>
      import('./firehydrant/firehydrant.module').then((m) => m.FirehydrantModule),
    data: { preload: false },
  },
  {
    path: 'firstaidbox',
    loadChildren: () =>
      import('./firstaidbox/firstaidbox.module').then((m) => m.FirstaidboxModule),
    data: { preload: false },
  },
  {
    path: 'elecweighbal',
    loadChildren: () =>
      import('./elecweighbal/elecweighbal.module').then((m) => m.ElecweighbalModule),
    data: { preload: false},
  },
];
@NgModule({
  declarations: [DashboardComponent,ResignationComponent, AttendenceComponent, CommitteeComponent, CommitteeAttendenceComponent, AccidentreportComponent, InspfireextinguisherComponent, ToolboxattendComponent, BiomedwasteComponent, PhmeterComponent, OptdsmeterComponent, MocdrillComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class EhsModule { }
