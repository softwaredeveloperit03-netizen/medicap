import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { GmpMaterialFormCustomisationService } from '../gmp-material-form-customisation.service';
import {
  MATERIAL_MASTER_FORM_FIELD_DEFS,
  MaterialFormFieldRuntime,
  MaterialFieldType,
  MaterialLayoutGroup,
  buildDefaultRuntimeFields,
  isValidFieldKey,
  isKnownDefaultFieldKey,
} from '../material-master-form-layout.constants';
declare let alertify: any;

@Component({
  selector: 'app-rm-master-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  readonly defs = MATERIAL_MASTER_FORM_FIELD_DEFS;
  /** Editable copy — one row per field */
  rows: MaterialFormFieldRuntime[] = [];
  readonly layoutGroups: MaterialLayoutGroup[] = ['core', 'full', 'grid', 'other'];
  readonly typeOptions: MaterialFieldType[] = ['STANDARD', 'TEXT', 'CHECKBOX'];
  customFieldKey = '';
  customFieldLabel = '';
  customFieldGroup: MaterialLayoutGroup = 'other';
  customFieldType: MaterialFieldType = 'TEXT';

  constructor(private gmp: GmpMaterialFormCustomisationService, private router: Router) {}

  ngOnInit(): void {
    this.rows = buildDefaultRuntimeFields();
  }

  trackByKey(_i: number, r: MaterialFormFieldRuntime): string {
    return r.field_key;
  }

  rowsForGroup(g: MaterialLayoutGroup): MaterialFormFieldRuntime[] {
    return this.rows.filter((r) => r.layout_group === g).sort((a, b) => a.sort_order - b.sort_order);
  }

  setApplicable(row: MaterialFormFieldRuntime, v: 'Applicable' | 'Not Applicable'): void {
    row.applicable = v;
  }

  setFieldType(row: MaterialFormFieldRuntime, t: MaterialFieldType): void {
    if (!row.allowTypeOverride && t !== 'STANDARD') {
      return;
    }
    row.field_type = t;
  }

  addCustomFieldRow(): void {
    const key = (this.customFieldKey || '').trim().toLowerCase();
    const label = (this.customFieldLabel || '').trim();
    if (!isValidFieldKey(key)) {
      alertify.error('Field name must be snake_case like custom_batch_note.');
      return;
    }
    if (isKnownDefaultFieldKey(key) || this.rows.some((r) => r.field_key === key)) {
      alertify.error('Field name already exists.');
      return;
    }
    const maxSort = this.rows
      .filter((r) => r.layout_group === this.customFieldGroup)
      .reduce((m, r) => Math.max(m, Number(r.sort_order) || 0), 0);

    this.rows.push({
      field_key: key,
      defaultLabel: label || key,
      field_label: label || key,
      layout_group: this.customFieldGroup,
      defaultSort: maxSort + 10,
      sort_order: maxSort + 10,
      defaultType: this.customFieldType,
      field_type: this.customFieldType,
      allowTypeOverride: true,
      applicable: 'Applicable',
    });
    this.customFieldKey = '';
    this.customFieldLabel = '';
    this.customFieldGroup = 'other';
    this.customFieldType = 'TEXT';
  }

  removeCustomFieldRow(row: MaterialFormFieldRuntime): void {
    if (isKnownDefaultFieldKey(row.field_key)) {
      return;
    }
    this.rows = this.rows.filter((r) => r.field_key !== row.field_key);
  }

  isDefaultRow(row: MaterialFormFieldRuntime): boolean {
    return isKnownDefaultFieldKey(row.field_key);
  }

  submit(form: NgForm): void {
    if (!form.valid) {
      alertify.error('Please complete the form.');
      return;
    }
    const username = localStorage.getItem('username') || 'Unknown';
    const fields = this.rows.map((r) => ({
      field_key: r.field_key,
      field_label: (r.field_label || '').trim() || r.defaultLabel,
      layout_group: r.layout_group,
      field_type: r.allowTypeOverride ? r.field_type : 'STANDARD',
      sort_order: Number(r.sort_order) || 0,
      applicable: r.applicable,
    }));
    this.gmp.saveRequest({ entry_by: username, fields }).subscribe(
      (res: any) => {
        if (res && res.status === 'success') {
          alertify.success(res.message || 'Saved. Sent for approval.');
          this.router.navigate(['/qa/soft-restriction/rm-master-customisation']);
        } else {
          alertify.error(res?.message || 'Save failed.');
        }
      },
      () => alertify.error('Request failed.')
    );
  }

  cancel(): void {
    this.router.navigate(['/qa/soft-restriction/rm-master-customisation']);
  }
}
