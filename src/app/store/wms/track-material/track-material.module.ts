import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TrackMaterialComponent } from './track-material.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: TrackMaterialComponent}
];

@NgModule({
  declarations: [TrackMaterialComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TrackMaterialModule { }
