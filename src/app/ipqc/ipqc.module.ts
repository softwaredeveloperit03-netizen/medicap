import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
 import { FgtestibComponent } from './fgtestib/fgtestib.component';
import { LabelapprovalComponent } from './labelapproval/labelapproval.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
   { path: 'FGTesting', component: FgtestibComponent},
  { path: 'labelapproval', component: LabelapprovalComponent},

  { path: 'inprocess', loadChildren: () => import('./inprocess/inprocess.module').then(m=>m.InprocessModule), data: {preload: false}},
  { path: 'finish', loadChildren: () => import('./finish/finish.module').then(m=>m.FinishModule), data: {preload: false}},
  { path: 'etp', loadChildren: () => import('./etp-water/etp-water.module').then(m=>m.EtpWaterModule), data: {preload: false}},
  { path: 'cleaning', loadChildren: () => import('./cleaning/cleaning.module').then(m=>m.CleaningModule), data: {preload: false}},
  { path: 'training', loadChildren: () => import('./training/training.module').then(m=>m.TrainingModule), data: {preload: false}},
 
];

@NgModule({
  declarations: [DashboardComponent, FgtestibComponent, LabelapprovalComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class IpqcModule { }
