import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DashboardComponent } from './dashboard/dashboard.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  {
    path: 'productionstage',
    loadChildren: () =>
      import('./productionstage/productionstage.module').then(
        (m) => m.ProductionstageModule
      ),
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
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class BmrModule {}
