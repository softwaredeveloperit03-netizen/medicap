import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  BreakdownIntimationEmbedStubComponent,
  QmsDeviationEmbedStubComponent,
  QmsIncidentEmbedStubComponent,
} from './qms-embed-stubs';

/**
 * eBMR execution embeds. Medicap uses lightweight stubs until Cyclone
 * QMS/Breakdown embed form modules are fully ported.
 */
@NgModule({
  declarations: [
    QmsDeviationEmbedStubComponent,
    QmsIncidentEmbedStubComponent,
    BreakdownIntimationEmbedStubComponent,
  ],
  imports: [CommonModule],
  exports: [
    QmsDeviationEmbedStubComponent,
    QmsIncidentEmbedStubComponent,
    BreakdownIntimationEmbedStubComponent,
  ],
})
export class QmsEmbedFormsModule {}
