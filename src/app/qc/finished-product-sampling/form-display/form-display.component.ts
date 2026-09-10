import { Component, Input } from '@angular/core';
import { FPS_BATCH_TYPES, statusLabel } from '../fps.constants';

@Component({
  selector: 'app-fps-form-display',
  templateUrl: './form-display.component.html',
  styleUrls: ['./form-display.component.css']
})
export class FormDisplayComponent {
  @Input() record: any = null;
  @Input() editableRequired = false;
  @Input() editableSampled = false;
  @Input() testRows: any[] = [];

  readonly batchTypes = FPS_BATCH_TYPES;

  batchTypeLabel(v: string): string {
    const t = this.batchTypes.find(x => x.value === v);
    return t ? t.label : (v || '—');
  }

  statusLabel = statusLabel;

  displayRows(): any[] {
    if (this.testRows?.length) {
      return this.testRows;
    }
    return this.record?.test_rows || [];
  }
}
