import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { PressureComponent } from './pressure/pressure.component';
import { FilterComponent } from './filter/filter.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path:'', component: DashboardComponent},
  {path:'pressure', component: PressureComponent},
  // {path:'filter', component : FilterComponent},
  { path: 'operation', loadChildren: () => import('./operation/operation.module').then(m=>m.OperationModule), data: {preload: false}},
  { path: 'filter', loadChildren: () => import('./filter/filter.module').then(m=>m.FilterModule), data: {preload: false}},
];
@NgModule({
  declarations: [
    DashboardComponent,
    PressureComponent,
    FilterComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
     CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class AircompressorModule { }

