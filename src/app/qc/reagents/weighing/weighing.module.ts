import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { HotWaterPermitComponent } from './ehs/hot-water-permit/hot-water-permit.component';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes = [
  {path:'',component:DashboardComponent},
  {path:'new',component:NewComponent},
];


@NgModule({
  declarations: [DashboardComponent, NewComponent, HotWaterPermitComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class WeighingModule { }
