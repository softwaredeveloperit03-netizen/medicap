import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { EmptyPalateLocationComponent } from './empty-palate-location.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: EmptyPalateLocationComponent}
];

@NgModule({
  declarations: [EmptyPalateLocationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class EmptyPalateLocationModule { }
