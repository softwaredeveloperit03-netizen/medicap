import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { IntimationComponent } from './intimation/intimation.component';
import { GoodsLogComponent } from './goods-log/goods-log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { IntimationLogComponent } from './intimation-log/intimation-log.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'intimation', component: IntimationComponent},
  { path: 'log', component: GoodsLogComponent},
  { path: 'intimation-log', component: IntimationLogComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    IntimationComponent,
    GoodsLogComponent,
    IntimationLogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class GoodsModule { }
