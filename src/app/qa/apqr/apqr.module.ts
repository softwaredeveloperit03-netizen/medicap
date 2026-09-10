import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { ApqrRoutingModule } from './apqr-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ProcessComponent } from './process/process.component';
import { TranslateModule } from '@ngx-translate/core';

/* import { NgxChartsModule } from '@swimlane/ngx-charts'; */


@NgModule({
  declarations: [DashboardComponent, ProcessComponent],
  imports: [ TranslateModule,
    CommonModule,
    ApqrRoutingModule,
    /* NgxChartsModule */
  ]
})
export class ApqrModule { }
