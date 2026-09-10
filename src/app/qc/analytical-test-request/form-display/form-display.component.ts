import { Component, Input } from '@angular/core';
import {
  ATR_SAMPLE_CATEGORIES, ATR_TEST_OPTIONS, ATR_BINDER_OPTIONS, ATR_DEPT_FORWARD_OPTIONS
} from '../atr.constants';

@Component({
  selector: 'app-atr-form-display',
  templateUrl: './form-display.component.html',
  styleUrls: ['./form-display.component.css']
})
export class FormDisplayComponent {
  @Input() req: any = null;
  @Input() showSectionB = true;

  readonly sampleCategories = ATR_SAMPLE_CATEGORIES;
  readonly testOptions = ATR_TEST_OPTIONS;
  readonly binderOptions = ATR_BINDER_OPTIONS;
  readonly deptOptions = ATR_DEPT_FORWARD_OPTIONS;

  isCategoryChecked(cat: string): boolean {
    return Array.isArray(this.req?.sample_categories) && this.req.sample_categories.indexOf(cat) >= 0;
  }

  inList(list: string[] | null | undefined, val: string): boolean {
    return Array.isArray(list) && list.indexOf(val) >= 0;
  }

  hasSectionBData(): boolean {
    if (!this.req) return false;
    return !!(this.req.analyst_by || this.req.lab_manager_by || this.req.completed_by ||
      (this.req.binder_forward && this.req.binder_forward.length) ||
      (this.req.dept_forward && this.req.dept_forward.length));
  }

  isTestRequested(key: string): boolean {
    return !!(this.req?.tests_requested && this.req.tests_requested[key] === true);
  }
}
