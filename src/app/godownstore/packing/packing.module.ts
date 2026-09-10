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
  { path: 'receiving', loadChildren: () => import('./receiving/receiving.module').then(m=>m.ReceivingModule), data: {preload: false}},
  { path: 'dedusting', loadChildren: () => import('./dedusting/dedusting.module').then(m=>m.DedustingModule), data: {preload: false}},
  { path: 'weighing', loadChildren: () => import('./weighing/weighing.module').then(m=>m.WeighingModule), data: {preload: false}},
  { path: 'grn', loadChildren: () => import('./grn/grn.module').then(m=>m.GrnModule), data: {preload: false}},
  { path: 'retest', loadChildren:()=>import('./retest/retest.module').then(m=>m.RetestModule),data:{preload:false}},
  { path: 'spillage', loadChildren: () => import('./spillage/spillage.module').then(m=>m.SpillageModule), data: {preload: false}},
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
export class PackingModule { }
