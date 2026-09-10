import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { IssuanceComponent } from './issuance/issuance.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  
  { path: 'issuance', component: IssuanceComponent},
  { path: '', component: DashboardComponent},
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},
  { path: 'packing', loadChildren: () => import('./packing/packing.module').then(m=>m.PackingModule), data: {preload: false}},
  { path: 'finish', loadChildren: () => import('./finish/finish.module').then(m=>m.FinishModule), data: {preload: false}}
 
];

@NgModule({
  declarations: [

  
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
export class GodownModule { }
