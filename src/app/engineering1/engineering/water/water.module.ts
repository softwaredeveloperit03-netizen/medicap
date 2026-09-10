import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ListComponent } from './list/list.component';
import { ScheduleComponent } from './schedule/schedule.component';
import { CleaningComponent } from './cleaning/cleaning.component';
import { ChecklistComponent } from './checklist/checklist.component';
import { WfilogComponent } from './wfilog/wfilog.component';

import { PurifiedwaterComponent } from './purifiedwater/purifiedwater.component';

import { PurifiedwaterplantComponent } from './purifiedwaterplant/purifiedwaterplant.component';
import { WaterclrecordComponent } from './waterclrecord/waterclrecord.component';
import { UvcabinateComponent } from './uvcabinate/uvcabinate.component';
import { DailychecksComponent } from './dailychecks/dailychecks.component';
import { WaterhardnessilComponent } from './waterhardnessil/waterhardnessil.component';
import { OperationComponent } from './operation/operation.component';
import { WatersystemComponent } from './watersystem/watersystem.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'list', component: ListComponent},
  { path: 'checklist', component: ChecklistComponent},
  { path: 'wfilog', component: WfilogComponent},
  { path: 'purifierlog', component: PurifiedwaterComponent},
  { path: 'purifierPlant', component: PurifiedwaterplantComponent},
  { path: 'waterclrecord', component: WaterclrecordComponent},
  { path: 'uvcabinate', component: UvcabinateComponent},
  { path: 'dailychecks', component: DailychecksComponent},
  { path: 'waterhardnessil', component: WaterhardnessilComponent},
  { path: 'operation', component: OperationComponent},
  { path: 'Watersystem', component: WatersystemComponent},
  { path: 'points', loadChildren: () => import('./points/points.module').then(m=>m.PointsModule), data: {preload: false}},

  {path:"cleaning",loadChildren:()=>import('./cleaning/cleaning.module').then(m => m.CleaningModule),data:{preload:false}},
  {path:"sanitization",loadChildren:()=>import('./sanitization/sanitization.module').then(m => m.SanitizationModule),data:{preload:false}},
  {path:"Soft_Water_Generation_Sys",loadChildren:()=>import('./soft-water/soft-water.module').then(m => m.SoftWaterModule),data:{preload:false}},

];
@NgModule({
  declarations: [DashboardComponent, ListComponent, ScheduleComponent, CleaningComponent, ChecklistComponent, WfilogComponent, WaterclrecordComponent, PurifiedwaterComponent, PurifiedwaterplantComponent, UvcabinateComponent, DailychecksComponent, WatersystemComponent, WaterhardnessilComponent, OperationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class WaterModule { }
