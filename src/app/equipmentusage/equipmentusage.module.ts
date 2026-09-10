import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
 
import { UsageComponent } from './usage/usage.component';
import {  FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { DashbaordComponent } from './dashbaord/dashbaord.component';
import { BarcodeComponent } from './barcode/barcode.component';
import { HomeComponent } from './home/home.component';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashbaordComponent},
  { path: 'dashbaord', component: DashbaordComponent},
  { path: 'usage', component: UsageComponent},
  { path: 'barcodess', component: BarcodeComponent},
     {
    path: 'checkings',
    loadChildren: () =>
      import('./checkings/checkings.module').then((m) => m.CheckingsModule),
    data: { preload: false },
  },
     {
    path: 'temprature',
    loadChildren: () =>
      import('./temprature/temprature.module').then((m) => m.TempratureModule),
    data: { preload: false },
  },
     {
    path: 'Area',
    loadChildren: () =>
      import('./area/area.module').then((m) => m.AreaModule),
    data: { preload: false },
  },


];

@NgModule({
  declarations: [
    DashbaordComponent,UsageComponent, BarcodeComponent, HomeComponent, 
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class EquipmentusageModule { }
