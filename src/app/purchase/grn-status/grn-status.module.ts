import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RawComponent } from './raw/raw.component';
import { PackingComponent } from './packing/packing.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes=[
  {path:'',component:DashboardComponent},
  {path:'raw',component:RawComponent},
  {path:'packing',component:PackingComponent}
]


@NgModule({
  declarations: [
    DashboardComponent,
    RawComponent,
    PackingComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class GrnStatusModule { }
