import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
 
import { MultiSelect, MultiSelectModule } from 'primeng/multiselect';
 
import { DropdownModule } from 'primeng/dropdown';
import { TranslateModule } from '@ngx-translate/core';

 

 const routes: Routes = [
  { path: '', component: DashboardComponent},
 
   { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},
  { path: 'packing', loadChildren: () => import('./packing/packing.module').then(m=>m.PackingModule), data: {preload: false}},
   { path: 'challan', loadChildren: () => import('./challan/challan.module').then(m=>m.ChallanModule), data: {preload: false}},
   { path: 'stock', loadChildren: () => import('./stock/stock.module').then(m=>m.StockModule), data: {preload: false}},
  
];


@NgModule({
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
     RouterModule.forChild(routes)
  ]
})
export class GodownstoreModule { }
