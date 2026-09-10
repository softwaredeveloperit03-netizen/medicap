import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { TranslateModule } from '@ngx-translate/core';




@NgModule({
  declarations: [
    NewComponent,
    DashboardComponent
  ],
  imports: [ TranslateModule,
    CommonModule
  ]
})
export class WeighBridgeModule { }
