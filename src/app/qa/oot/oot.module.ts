import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { YieldComponent } from './yield/yield.component';
import { ProcessComponent } from './process/process.component';
import { AnalyticalComponent } from './analytical/analytical.component';
import { RawComponent } from './raw/raw.component';
import { WaterComponent } from './water/water.component';
import { InprocessComponent } from './inprocess/inprocess.component';
import { CriticalComponent } from './critical/critical.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'yield', component: YieldComponent},
  { path: 'process', component: ProcessComponent},
  { path: 'analytical', component: AnalyticalComponent},
  { path: 'raw', component: RawComponent},
  { path: 'water', component:WaterComponent},
  { path: 'inprocess', component: InprocessComponent},
  { path: 'critical', component: CriticalComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  declarations: [DashboardComponent, YieldComponent, ProcessComponent, AnalyticalComponent, RawComponent, WaterComponent, InprocessComponent, CriticalComponent, LogComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class OotModule { }
