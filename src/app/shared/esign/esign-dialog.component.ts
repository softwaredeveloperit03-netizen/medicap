import { Component } from '@angular/core';
import { EsignService } from './esign.service';

@Component({
  selector: 'app-esign-dialog',
  templateUrl: './esign-dialog.component.html',
  styleUrls: ['./esign-dialog.component.css'],
})
export class EsignDialogComponent {
  constructor(public es: EsignService) {}

  onKey(e: KeyboardEvent): void {
    if (e.key === 'Enter') {
      e.preventDefault();
      this.es.confirm();
    } else if (e.key === 'Escape') {
      this.es.cancel();
    }
  }

  meaningClass(): string {
    const m = (this.es.req?.meaning || '').toLowerCase();
    if (m.includes('approv')) return 'esg-m-approve';
    if (m.includes('check') || m.includes('review')) return 'esg-m-check';
    return 'esg-m-prepare';
  }
}
