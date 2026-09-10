import { Component, Input } from '@angular/core';
import {
  PLS_FORM_ID, PLS_SECTION_A_ROWS, PLS_INSPECTION_ROWS, PLS_LAB_NUMBER_SCHEMES
} from '../pls.constants';

@Component({
  selector: 'app-pls-form-display',
  templateUrl: './form-display.component.html',
  styleUrls: ['./form-display.component.css']
})
export class FormDisplayComponent {
  @Input() sample: any = null;

  readonly formId = PLS_FORM_ID;
  readonly sectionARows = PLS_SECTION_A_ROWS;
  readonly inspectionRows = PLS_INSPECTION_ROWS;
  readonly labSchemes = PLS_LAB_NUMBER_SCHEMES;

  schemeLabel(val: string): string {
    const s = this.labSchemes.find(x => x.value === val);
    return s ? s.label : val || '—';
  }

  ynLabel(val: string): string {
    if (val === 'Y' || val === 'Yes') return 'Yes';
    if (val === 'N' || val === 'No') return 'No';
    return val || '—';
  }
}
