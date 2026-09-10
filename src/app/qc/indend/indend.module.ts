import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'chemical', loadChildren: () => import('./chemical/chemical.module').then(m=>m.ChemicalModule), data: {preload: false}},
  { path: 'glassware', loadChildren: () => import('./glassware/glassware.module').then(m=>m.GlasswareModule), data: {preload: false}},
  { path: 'general', loadChildren: () => import('./general/general.module').then(m=>m.GeneralModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class IndendModule { }
