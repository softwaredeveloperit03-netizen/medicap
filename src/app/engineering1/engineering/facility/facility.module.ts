import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { AreaComponent } from './area/area.component';
import { UtilityComponent } from './utility/utility.component';
import { MeasuringComponent } from './measuring/measuring.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'area', component: AreaComponent},
  { path: 'utility', component: UtilityComponent},
  { path: 'measuring', component: MeasuringComponent},
];
 
@NgModule({
  declarations: [
    DashboardComponent,
    AreaComponent,
    UtilityComponent,
    MeasuringComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class FacilityModule { }
