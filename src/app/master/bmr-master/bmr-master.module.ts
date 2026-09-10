import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { MaasterComponent } from './maaster/maaster.component';

const routes: Routes = [
  { path: '', component: MaasterComponent },
  {
    path: 'process',
    loadChildren: () =>
      import('./process/process.module').then((m) => m.ProcessModule),
    data: { preload: false },
  },
  {
    path: 'bmrdash',
    loadChildren: () =>
      import('./bmrdash/bmrdash.module').then((m) => m.BmrdashModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [MaasterComponent],
  imports: [
    SharedModule,CommonModule, FormsModule, ClarityModule, RouterModule.forChild(routes)],
})
export class BmrMasterModule {}

