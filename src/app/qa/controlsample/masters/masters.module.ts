import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes=[
  {path:'',component:DashboardComponent},
  { path: 'rack', loadChildren: () => import('./rack/rack.module').then(m=>m.RackModule), data: {preload: false}},
  { path: 'frequency', loadChildren: () => import('./frequencies/frequencies.module').then(m=>m.FrequenciesModule), data: {preload: false}},
  { path: 'destruction', loadChildren: () => import('./destruction/destruction.module').then(m=>m.DestructionModule), data: {preload: false}}

]

@NgModule({
  declarations: [
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),


  ]
})
export class MastersModule { }
