import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';

import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { Pressure1Component } from './pressure1/pressure1.component';
import { Pressure2Component } from './pressure2/pressure2.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'pressure1', component: Pressure1Component},
  { path: 'pressure2', component: Pressure2Component},
];
@NgModule({
  declarations: [DashboardComponent, Pressure1Component, Pressure2Component],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class AhuModule { }
