import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MapPalateLocationComponent } from './map-palate-location.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: MapPalateLocationComponent}
];

@NgModule({
  declarations: [MapPalateLocationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MapPalateLocationModule { }
