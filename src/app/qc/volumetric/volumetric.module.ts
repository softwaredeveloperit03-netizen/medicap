import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { StandardisationComponent } from './standardisation/standardisation.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { LogComponent } from './log/log.component';
import { ApprovalComponent } from './approval/approval.component';
import { RouterModule, Routes } from '@angular/router';
import { MannitolComponent } from './mannitol/mannitol.component';
import { StockComponent } from './stock/stock.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'standardisation', component: StandardisationComponent },
  { path: 'approval', component: ApprovalComponent },
  { path: 'log', component: LogComponent },
  { path: 'mannitol', component: MannitolComponent },
  { path: 'stock', component: StockComponent },
  {
    path: 'master',
    loadChildren: () =>
      import('./master/master.module').then((m) => m.MasterModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [
    DashboardComponent,
    ApprovalComponent,
    StandardisationComponent,
    LogComponent,
    MannitolComponent,
    MannitolComponent,
    StockComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class VolumetricModule {}
