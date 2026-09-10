import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';

/** Cyclone-style wrapper: /master/material → RawModule list */
const routes: Routes = [
  {
    path: '',
    loadChildren: () => import('./raw/raw.module').then((m) => m.RawModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [],
  imports: [
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class MaterialModule {}
