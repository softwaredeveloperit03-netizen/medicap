import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { AreaComponent } from './area/area.component';
import { LineComponent } from './line/line.component';
import { SampleComponent } from './sample/sample.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'area', component: AreaComponent},
  { path: 'line', component: LineComponent},
  { path: 'sample', component: SampleComponent} 
];

@NgModule({
  declarations: [DashboardComponent, AreaComponent, LineComponent, SampleComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class SampleModule { }
