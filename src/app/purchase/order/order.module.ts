import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { SearchFilterPipe } from 'src/app/pipes/search-filter.pipe';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', redirectTo: 'raw', pathMatch: 'full' },
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m => m.RawModule), data: { preload: false } },
];

@NgModule({
  declarations: [SearchFilterPipe],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule, 
    ClarityModule, 
    RouterModule.forChild(routes)
  ],
  exports: [
    SearchFilterPipe
  ]
})
export class OrderModule { }
