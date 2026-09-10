import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { ProductionRoutingModule } from './production-routing.module';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [],
  imports: [ TranslateModule,
    CommonModule,
    ProductionRoutingModule
  ]
})
export class ProductionModule { }
