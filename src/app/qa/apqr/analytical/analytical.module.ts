import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { AnalyticalRoutingModule } from './analytical-routing.module';
import { HomeComponent } from './home/home.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [HomeComponent],
  imports: [ TranslateModule,
    CommonModule,
    AnalyticalRoutingModule
  ]
})
export class AnalyticalModule { }
