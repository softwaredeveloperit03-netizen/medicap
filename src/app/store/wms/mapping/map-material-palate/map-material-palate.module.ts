import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MapMaterialPalateComponent } from './map-material-palate.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: MapMaterialPalateComponent}
];

@NgModule({
  declarations: [MapMaterialPalateComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MapMaterialPalateModule { }
