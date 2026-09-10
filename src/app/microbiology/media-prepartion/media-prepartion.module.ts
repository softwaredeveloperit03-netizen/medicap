import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { MediaStockComponent } from './media-stock/media-stock.component';
import { MediaConsumptionComponent } from './media-consumption/media-consumption.component';
import { MediaPreComponent } from './media-pre/media-pre.component';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes = [
  {path:'',component:DashboardComponent},
  {path:'media-stock',component:MediaStockComponent},
  {path:'media-consumption',component:MediaConsumptionComponent},
  {path:'media-pre',component:MediaPreComponent}
]

@NgModule({
  declarations: [
    DashboardComponent,
    MediaStockComponent,
    MediaConsumptionComponent,
    MediaPreComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MediaPrepartionModule { }
