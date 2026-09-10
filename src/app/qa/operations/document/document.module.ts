import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { DocumentRoutingModule } from './document-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RequisitionComponent } from './requisition/requisition.component';
import { DistributionComponent } from './distribution/distribution.component';
import { LogbookComponent } from './logbook/logbook.component';
import { ProcessComponent } from './process/process.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [DashboardComponent, RequisitionComponent, DistributionComponent, LogbookComponent, ProcessComponent],
  imports: [ TranslateModule,
    CommonModule,
    DocumentRoutingModule
  ]
})
export class DocumentModule { }
