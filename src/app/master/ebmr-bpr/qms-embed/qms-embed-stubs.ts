import { Component, EventEmitter, Input, Output } from '@angular/core';
import { EbmrQmsEmbedContext } from 'src/app/shared/qms-embed/ebmr-qms-embed.types';

/** Lightweight embeds so eBMR execution builds without Cyclone QMS form modules. */
@Component({
  selector: 'app-qms-deviation-new',
  template: `
    <div class="clr-row" style="padding:1rem">
      <div class="clr-col-12">
        <h4>Deviation (eBMR context)</h4>
        <p *ngIf="embedContextLine()">{{ embedContextLine() }}</p>
        <p>Open full Deviation from QA → QMS to complete Part I, or close this panel.</p>
        <button class="btn btn-sm btn-primary" type="button" (click)="embedSaved.emit({ stub: true })">Mark linked</button>
        <button class="btn btn-sm btn-outline" type="button" (click)="embedClosed.emit()">Close</button>
      </div>
    </div>
  `,
})
export class QmsDeviationEmbedStubComponent {
  @Input() embedMode = false;
  @Input() embedContext: EbmrQmsEmbedContext | null = null;
  @Output() embedClosed = new EventEmitter<void>();
  @Output() embedSaved = new EventEmitter<any>();

  embedContextLine(): string {
    const c = this.embedContext;
    if (!c) return '';
    return [c.batch_no && 'Batch: ' + c.batch_no, c.stage_name && 'Stage: ' + c.stage_name, c.step_name && 'Step: ' + c.step_name]
      .filter(Boolean)
      .join(' · ');
  }
}

@Component({
  selector: 'app-qms-incident-new',
  template: `
    <div class="clr-row" style="padding:1rem">
      <div class="clr-col-12">
        <h4>Incident (eBMR context)</h4>
        <p *ngIf="embedContextLine()">{{ embedContextLine() }}</p>
        <p>Open full Incident from QA → QMS to complete the form, or close this panel.</p>
        <button class="btn btn-sm btn-primary" type="button" (click)="embedSaved.emit({ stub: true })">Mark linked</button>
        <button class="btn btn-sm btn-outline" type="button" (click)="embedClosed.emit()">Close</button>
      </div>
    </div>
  `,
})
export class QmsIncidentEmbedStubComponent {
  @Input() embedMode = false;
  @Input() embedContext: EbmrQmsEmbedContext | null = null;
  @Output() embedClosed = new EventEmitter<void>();
  @Output() embedSaved = new EventEmitter<any>();

  embedContextLine(): string {
    const c = this.embedContext;
    if (!c) return '';
    return [c.batch_no && 'Batch: ' + c.batch_no, c.stage_name && 'Stage: ' + c.stage_name]
      .filter(Boolean)
      .join(' · ');
  }
}

@Component({
  selector: 'app-breakdown-intimation-entry',
  template: `
    <div class="clr-row" style="padding:1rem">
      <div class="clr-col-12">
        <h4>Breakdown Intimation (eBMR context)</h4>
        <p *ngIf="embedContextLine()">{{ embedContextLine() }}</p>
        <p>Open Engineering Breakdown Intimation for a full entry, or close this panel.</p>
        <button class="btn btn-sm btn-primary" type="button" (click)="embedSaved.emit({ stub: true })">Mark linked</button>
        <button class="btn btn-sm btn-outline" type="button" (click)="embedClosed.emit()">Close</button>
      </div>
    </div>
  `,
})
export class BreakdownIntimationEmbedStubComponent {
  @Input() embedMode = false;
  @Input() embedContext: EbmrQmsEmbedContext | null = null;
  @Output() embedClosed = new EventEmitter<void>();
  @Output() embedSaved = new EventEmitter<any>();

  embedContextLine(): string {
    const c = this.embedContext;
    if (!c) return '';
    return [c.batch_no && 'Batch: ' + c.batch_no, c.product_code && 'Product: ' + c.product_code]
      .filter(Boolean)
      .join(' · ');
  }
}
