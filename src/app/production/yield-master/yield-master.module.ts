import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';




@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent
  ],
  imports: [ TranslateModule,
    CommonModule
  ]
})
export class YieldMasterModule { }
