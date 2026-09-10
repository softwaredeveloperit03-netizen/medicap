import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RawComponent } from './raw/raw.component';
import { PackingComponent } from './packing/packing.component';
import { FinishComponent } from './finish/finish.component';
import { InprocessComponent } from './inprocess/inprocess.component';
import { StabilityComponent } from './stability/stability.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { WaterComponent } from './water/water.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'raw', component: RawComponent},
  { path: 'packing', component: PackingComponent},
  { path: 'finish', component: FinishComponent},
  { path: 'inprocess', component: InprocessComponent},
  { path: 'water', component: WaterComponent},
  { path: 'stability', component: StabilityComponent}
];

@NgModule({
  declarations: [RawComponent, PackingComponent, FinishComponent, InprocessComponent, StabilityComponent, DashboardComponent, WaterComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TestingModule { }
