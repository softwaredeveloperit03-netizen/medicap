import { Component, Input } from '@angular/core';
import { IPPS_SAMPLE_TYPES } from '../ipps.constants';

@Component({
  selector: 'app-ipps-form-display',
  templateUrl: './form-display.component.html',
  styleUrls: ['./form-display.component.css']
})
export class FormDisplayComponent {
  @Input() record: any = null;
  readonly sampleTypes = IPPS_SAMPLE_TYPES;

  sampleTypeLabel(v: string): string {
    const t = this.sampleTypes.find(x => x.value === v);
    return t ? t.label : v;
  }

  statusLabel(s: string): string {
    return (s || '').replace(/_/g, ' ').toUpperCase();
  }
}
