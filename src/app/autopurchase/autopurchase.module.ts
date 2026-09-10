import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FoComponent } from './fo/fo.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ReqAnalysisComponent } from './req-analysis/req-analysis.component';
import { LogComponent } from './log/log.component';
import { ReqCollectionComponent } from './req-collection/req-collection.component';
import { ClientwiseComponent } from './clientwise/clientwise.component';
import { WoplanComponent } from './woplan/woplan.component';
import { PrepareComponent } from './prepare/prepare.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [ 
  { path: '', component: DashboardComponent},
  { path: 'fOrder', component: FoComponent},
  { path: 'req_analysis', component: ReqAnalysisComponent},
  { path: 'log', component: LogComponent},
  { path: 'ReqCollection', component: ReqCollectionComponent},
  { path: 'clientwise', component: ClientwiseComponent},
  { path: 'woplan', component: WoplanComponent},
  { path: 'prepare', component: PrepareComponent},
  
  
];
@NgModule({
  declarations: [

    DashboardComponent,
     FoComponent,
     LogComponent,
     ReqAnalysisComponent,
     ReqCollectionComponent,
     ClientwiseComponent,
     WoplanComponent,
     PrepareComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class AutopurchaseModule { }
