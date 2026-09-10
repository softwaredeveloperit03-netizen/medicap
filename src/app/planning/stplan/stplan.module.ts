import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  // stPlanning workspace moved up to /planning; keep an alias so old deep links still resolve.
  { path: 'stPlanning', redirectTo: '/planning', pathMatch: 'prefix'},
  { path: 'stStp', loadChildren: () => import('./stp/stp.module').then(m=>m.StpModule)},
 
];



@NgModule({
  declarations: [
    DashboardComponent,

  ],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    SharedModule,
    RouterModule.forChild(routes)
  ]
})
export class StplanModule { }
