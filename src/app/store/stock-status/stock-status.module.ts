import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { TranslateModule } from '@ngx-translate/core';




@NgModule({
  declarations: [
    DashboardComponent
  ],
  imports: [ TranslateModule,
    CommonModule
  ]
})
export class StockStatusModule { }
