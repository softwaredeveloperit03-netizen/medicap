import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { LabelComponent } from './label/label.component';
import { MasterComponent } from './master/master.component';
import { BridgeComponent } from './bridge/bridge.component';
import { CorrectionComponent } from './correction/correction.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'master', component: MasterComponent},
  { path: 'bridge', component: BridgeComponent},
  { path: 'correction', component: CorrectionComponent},
  { path: 'receiving', loadChildren: () => import('./receiving/receiving.module').then(m=>m.ReceivingModule), data: {preload: false}},
  { path: 'damage', loadChildren: () => import('./damage/damage.module').then(m=>m.DamageModule), data: {preload: false}},
  { path: 'grn', loadChildren: () => import('./grn/grn.module').then(m=>m.GrnModule), data: {preload: false}},
  { path: 'retest', loadChildren: () => import('./retest/retest.module').then(m=>m.RetestModule), data: {preload: false}},
  { path: 'spillage', loadChildren: () => import('./spillage/spillage.module').then(m=>m.SpillageModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, LabelComponent, MasterComponent, BridgeComponent, CorrectionComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ]
})
export class RawModule { }
