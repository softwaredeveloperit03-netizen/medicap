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
  {
    path: 'daily', loadChildren: ()=> import('./daily/daily.module').then(m=>m.DailyModule), data: {preload: false},
  },
  {
    path: 'label', loadChildren: ()=> import('./label/label.module').then(m=>m.LabelModule), data: {preload: false},
  }
];
@NgModule({
  declarations: [
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MeterModule { }
