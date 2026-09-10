import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: 'chemical', loadChildren: () => import('./chemical/chemical.module').then(m=>m.ChemicalModule)},
  { path: 'standard', loadChildren: () => import('./standard/standard.module').then(m=>m.StandardModule)},
  { path: 'glassware', loadChildren: () => import('./glassware/glassware.module').then(m=>m.GlasswareModule)},
  { path: 'hplc', loadChildren:() => import('./hplc/hplc.module').then(m=>m.HplcModule)},
  { path: 'volumetric', loadChildren: () => import('./volumetric/volumetric.module').then(m=>m.VolumetricModule)},
  { path: 'indicator', loadChildren: () => import('./indicator/indicator.module').then(m=>m.IndicatorModule)},
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
