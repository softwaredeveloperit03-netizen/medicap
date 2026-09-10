import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},

];

@NgModule({
  declarations: [],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    RouterModule.forChild(routes)
  ]
})
export class TestingModule { }
