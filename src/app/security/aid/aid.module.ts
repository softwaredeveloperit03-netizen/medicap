import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';




const routes:Routes=[
  {path:'',component:DashboardComponent},
 
 
  {path: 'form', loadChildren: () => import('./form/form.module').then(m=>m.FormModule), data: {preload: false}},
  {path: 'requistion', loadChildren: () => import('./requistion/requistion.module').then(m=>m.RequistionModule), data: {preload: false}},
  {path: 'consumption', loadChildren: () => import('./consumption/consumption.module').then(m=>m.ConsumptionModule), data: {preload: false}}

]

@NgModule({
  declarations: [
    DashboardComponent,
    
  
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    RouterModule.forChild(routes),
    ClarityModule,
    DocsIconsModule
  ]
})
export class AidModule { }
