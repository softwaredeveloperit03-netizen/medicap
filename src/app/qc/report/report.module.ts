import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: 'sampling', loadChildren: () => import('./sampling/sampling.module').then(m=>m.SamplingModule), data: {preload: false}},
  { path: 'specification', loadChildren: () => import('./specification/specification.module').then(m=>m.SpecificationModule), data: {preload: false}},
  { path: 'testing', loadChildren: () => import('./testing/testing.module').then(m=>m.TestingModule), data: {preload: false}},
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
export class ReportModule { }
