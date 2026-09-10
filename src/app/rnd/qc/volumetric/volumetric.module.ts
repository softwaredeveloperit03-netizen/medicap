import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { PreparationComponent } from './preparation/preparation.component';
import { StandardisationComponent } from './standardisation/standardisation.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'preparation', component: PreparationComponent},
  { path: 'standardisation', component: StandardisationComponent},
  { path: 'log', component: LogComponent},
  { path: 'master', loadChildren: () => import('./master/master.module').then(m=>m.MasterModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, PreparationComponent, StandardisationComponent, LogComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class VolumetricModule { }
