import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: 'product', loadChildren: () => import('./product/product.module').then(m=>m.ProductModule)},
  { path: 'material', loadChildren: () => import('./material/material.module').then(m=>m.MaterialModule)},
  { path: 'equipment', loadChildren: () => import('./equipment/equipment.module').then(m=>m.EquipmentModule)},
];

@NgModule({
  declarations: [],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MasterModule { }
