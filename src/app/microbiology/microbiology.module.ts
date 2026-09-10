import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { LabCleaningComponent } from './lab-cleaning/lab-cleaning.component';
import { DisSolutionComponent } from './dis-solution/dis-solution.component';
import { BACTERIOLOGICALLogComponent } from './bacteriological-log/bacteriological-log.component';
import { BodIncubatorComponent } from './bod-incubator/bod-incubator.component';
import { HygineComponent } from './hygine/hygine.component';
import { EnvLibraryComponent } from './env-library/env-library.component';
import { CalendarComponent } from './calendar/calendar.component';
import { PressureComponent } from './pressure/pressure.component';
import { DocumentComponent } from './document/document.component';
import { LafComponent } from './laf/laf.component';
import { TemperatureComponent } from './temperature/temperature.component';
import { PassboxComponent } from './passbox/passbox.component';
import { HDPEComponent } from './hdpe/hdpe.component';
import { PersonnelComponent } from './personnel/personnel.component';
import { DigitalComponent } from './digital/digital.component';
import { PhComponent } from './ph/ph.component';
import { MicropipetteComponent } from './micropipette/micropipette.component';
import { BalanceComponent } from './balance/balance.component';
import { ExternalComponent } from './external/external.component';
import { ResignationComponent } from './resignation/resignation.component';
import { TranslateModule } from '@ngx-translate/core';

import {InwardgoodreceiveComponent} from './inwardgoodreceive/inwardgoodreceive.component'
import {StockbookComponent} from './stockbook/stockbook.component'

const routes: Routes = [
  { path: '', component: DashboardComponent}, 
  { path: 'document', component: DocumentComponent},
  { path: 'resignation', component: ResignationComponent},
  { path: 'calendar', component: CalendarComponent},
  { path: 'log', component: BACTERIOLOGICALLogComponent},
  { path: 'bod-incubator', component: BodIncubatorComponent},
  { path: 'dis-solution', component: DisSolutionComponent},
  { path: 'lab', component: LabCleaningComponent},
  { path: 'env-library', component: EnvLibraryComponent},
  { path: 'hygine', component: HygineComponent},
  { path: 'pressure', component: PressureComponent},
  { path: 'laf', component: LafComponent},
  { path: 'temperature', component: TemperatureComponent},
  { path: 'passbox', component: PassboxComponent},
  { path: 'HDPE', component: HDPEComponent},
  { path: 'personnel', component: PersonnelComponent},
  {path:'digital',component:DigitalComponent},
  {path:'hp',component:PhComponent},
  {path:'balance',component:BalanceComponent},
  {path:'external',component:ExternalComponent},
  {path:'inwardgoodreceive',component:InwardgoodreceiveComponent},
  {path:'stockbook',component:StockbookComponent},




  {path:'micropipette',component:MicropipetteComponent},
  { path: 'sampling', loadChildren: () => import('./sampling/sampling.module').then(m=>m.SamplingModule), data: {preload: false}},
  { path: 'testing', loadChildren: () => import('./testing/testing.module').then(m=>m.TestingModule), data: {preload: false}},
  { path: 'autocleave', loadChildren: () => import('./autocleaving/autocleaving.module').then(m=>m.AutocleavingModule), data: {preload: false}},
  { path: 'culture', loadChildren: () => import('./culture/culture.module').then(m=>m.CultureModule), data: {preload: false}},
  { path: 'isolate', loadChildren: () => import('./isolate/isolate.module').then(m=>m.IsolateModule), data: {preload: false}},
  { path:'glassware_cleaning',loadChildren:()=>import('./glassware-cleaning/glassware-cleaning.module').then(m => m.GlasswareCleaningModule),data:{preload:false}},
  { path:'bacteriological',loadChildren:()=>import('./bacteriological/bacteriological.module').then(m => m.BacteriologicalModule),data:{preload:false}},
  { path: 'refrigerator',loadChildren:()=>import('./refrigerator/refrigerator.module').then(m => m.RefrigeratorModule),data:{preload:false}},
  { path: 'microbial-culture',loadChildren:()=>import('./microbial-culture/microbial-culture.module').then(m => m.MicrobialCultureModule),data:{preload:false}},
  { path: 'investigation',loadChildren:()=>import('./investigation/investigation.module').then(m => m.InvestigationModule),data:{preload:false}},
  { path: 'media-preparton',loadChildren:()=>import('./media-prepartion/media-prepartion.module').then(m => m.MediaPrepartionModule),data:{preload:false}},
  { path: 'incubator',loadChildren:()=>import('./incubator/incubator.module').then(m => m.IncubatorModule),data:{preload:false}},
  { path: 'media', loadChildren: () => import('./media/media.module').then(m=>m.MediaModule), data: {preload: false}},
  { path: 'fogging', loadChildren: () => import('./fogging/fogging.module').then(m=>m.FoggingModule), data: {preload: false}},
  { path: 'colony-counter', loadChildren: () => import('./colony-counter/colony-counter.module').then(m=>m.ColonyCounterModule), data: {preload: false}},
  { path: 'airtesting', loadChildren: () => import('./airtesting/airtesting.module').then(m=>m.AirtestingModule), data: {preload: false}},
  { path: 'water', loadChildren: () => import('./water/water.module').then(m=>m.WaterModule), data: {preload: false}},
  { path: 'induction-training', loadChildren: () => import('./induction-training/induction-training.module').then(m=>m.InductionTrainingModule), data: {preload: false}},
  { path: 'rawMaterial-testing', loadChildren: () => import('./raw-material-testing/raw-material-testing.module').then(m=>m.RawMaterialTestingModule), data: {preload: false}},
  { path: 'calibration', loadChildren: () => import('./calibration/calibration.module').then(m=>m.CalibrationModule), data: {preload: false}},
  { path: 'rinse', loadChildren: () => import('./rinse/rinse.module').then(m=>m.RinseModule), data: {preload: false}},
  { path: 'swap', loadChildren: () => import('./swap/swap.module').then(m=>m.SwapModule), data: {preload: false}},
  { path: 'environment', loadChildren: () => import('./environment/environment.module').then(m=>m.EnvironmentModule), data: {preload: false}},
  { path: 'microMaterial', loadChildren: () => import('./chemical/chemical.module').then(m=>m.ChemicalModule), data: {preload: false}},

  { path: '**', redirectTo: '/'},
  
];
@NgModule({
  declarations: [DashboardComponent, LabCleaningComponent, DisSolutionComponent, BACTERIOLOGICALLogComponent, BodIncubatorComponent, HygineComponent, EnvLibraryComponent, CalendarComponent, PressureComponent, DocumentComponent, LafComponent, TemperatureComponent, PassboxComponent, HDPEComponent, PersonnelComponent, DigitalComponent, PhComponent, MicropipetteComponent, BalanceComponent, ExternalComponent, ResignationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class MicrobiologyModule { }
