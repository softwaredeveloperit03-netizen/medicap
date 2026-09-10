import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TrackPalateComponent } from './track-palate.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: TrackPalateComponent}
];

@NgModule({
  declarations: [TrackPalateComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TrackPalateModule { }
