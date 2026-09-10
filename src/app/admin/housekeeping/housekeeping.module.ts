import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DisinfectComponent } from './disinfect/disinfect.component';
import { DistructionComponent } from './distruction/distruction.component';
import { MaterialComponent } from './material/material.component';
import { IssuanceComponent } from './issuance/issuance.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'disinfect', component: DisinfectComponent},
  { path: 'distruction', component: DistructionComponent},
  { path: 'issuance', component: IssuanceComponent},
  { path: 'material', component: MaterialComponent},
  { path: 'daily', loadChildren: () => import('./daily/daily.module').then(m=>m.DailyModule),data: {preload: false}} 

];

@NgModule({
  declarations: [
    DisinfectComponent,
    DistructionComponent,
    MaterialComponent,
    IssuanceComponent,
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class HousekeepingModule { }
