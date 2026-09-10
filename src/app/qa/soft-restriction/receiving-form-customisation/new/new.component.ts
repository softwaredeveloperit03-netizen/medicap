import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import {
  buildReceivingKeyFromDisplayName,
  buildDefaultReceivingRuntimeFields,
  isKnownReceivingDefaultFieldKey,
  isValidReceivingFieldKey,
  mergeReceivingLayoutRows,
  ReceivingFieldType,
  ReceivingLayoutGroup,
  ReceivingRuntimeField,
} from '../receiving-form-customisation.constants';
import { ReceivingFormCustomisationService } from '../receiving-form-customisation.service';
declare let alertify: any;

@Component({
  selector: 'app-receiving-form-customisation-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  rows: ReceivingRuntimeField[] = [];
  customFieldLabel = '';
  customFieldGroup: ReceivingLayoutGroup = 'other';
  customFieldType: ReceivingFieldType = 'TEXT';
  customFieldOptions = '';
  customFieldPattern = 'Custom';
  customVisibleWhenKey = '';
  customVisibleWhenValue = '';
  customDefaultValue = '';

  readonly groups: ReceivingLayoutGroup[] = ['labeling', 'receiving', 'container', 'others', 'checklist', 'other'];
  readonly types: ReceivingFieldType[] = ['STANDARD', 'TEXT', 'CHECKBOX', 'DROPDOWN', 'YESNO'];
  readonly dropdownPatterns = ['Custom', 'YES/No', 'Applicable/Not Applicable'];

  constructor(private service: ReceivingFormCustomisationService, private router: Router) {}

  ngOnInit(): void {
    this.loadCurrentLayout();
  }

  trackByKey(_i: number, row: ReceivingRuntimeField): string {
    return row.field_key;
  }

  get autoCustomFieldKey(): string {
    return buildReceivingKeyFromDisplayName(this.customFieldLabel);
  }

  rowsForGroup(group: ReceivingLayoutGroup): ReceivingRuntimeField[] {
    return this.rows.filter((r) => r.layout_group === group).sort((a, b) => a.sort_order - b.sort_order);
  }

  isDefaultRow(row: ReceivingRuntimeField): boolean {
    return isKnownReceivingDefaultFieldKey(row.field_key);
  }

  isReadOnlyLocked(row: ReceivingRuntimeField): boolean {
    return !!row.lockReadOnly;
  }

  removeCustomField(row: ReceivingRuntimeField): void {
    if (this.isDefaultRow(row)) {
      return;
    }
    this.rows = this.rows.filter((x) => x.field_key !== row.field_key);
  }

  onCustomFieldTypeChange(): void {
    if (this.customFieldType !== 'DROPDOWN' && this.customFieldType !== 'YESNO') {
      this.customFieldPattern = 'Custom';
      this.customFieldOptions = '';
      return;
    }
    if (this.customFieldType === 'YESNO' && !this.customFieldOptions) {
      this.customFieldPattern = 'YES/No';
      this.customFieldOptions = 'YES,No';
    }
  }

  onCustomPatternChange(): void {
    if (this.customFieldPattern === 'YES/No') {
      this.customFieldOptions = 'YES,No';
    } else if (this.customFieldPattern === 'Applicable/Not Applicable') {
      this.customFieldOptions = 'Applicable,Not Applicable';
    }
  }

  getRowPattern(row: ReceivingRuntimeField): string {
    const options = this.normalizeDropdownOptions(row.field_options);
    const normalized = options.map((x) => x.toLowerCase()).join(',');
    if (normalized === 'yes,no') {
      return 'YES/No';
    }
    if (normalized === 'applicable,not applicable') {
      return 'Applicable/Not Applicable';
    }
    return 'Custom';
  }

  setRowPattern(row: ReceivingRuntimeField, pattern: string): void {
    if (pattern === 'YES/No') {
      row.field_options = 'YES,No';
      return;
    }
    if (pattern === 'Applicable/Not Applicable') {
      row.field_options = 'Applicable,Not Applicable';
      return;
    }
  }

  getConditionCandidates(excludeKey = ''): Array<{ key: string; label: string }> {
    return this.rows
      .filter((r) => r.field_key !== excludeKey)
      .map((r) => ({ key: r.field_key, label: r.field_label || r.defaultLabel || r.field_key }));
  }

  getConditionValueOptionsByKey(fieldKey: string): string[] {
    const key = String(fieldKey || '').trim();
    if (!key) return [];
    const row = this.rows.find((r) => r.field_key === key);
    if (!row) return [];
    if (row.field_type === 'YESNO') return ['Yes', 'No'];
    if (row.field_type === 'DROPDOWN') return this.normalizeDropdownOptions(row.field_options);
    if (row.field_type === 'CHECKBOX') return ['true', 'false'];
    return [];
  }

  onCustomConditionFieldChange(): void {
    if (!this.customVisibleWhenKey) {
      this.customVisibleWhenValue = '';
      return;
    }
    const options = this.getConditionValueOptionsByKey(this.customVisibleWhenKey);
    if (options.length > 0 && options.indexOf(this.customVisibleWhenValue) === -1) {
      this.customVisibleWhenValue = options[0];
    }
  }

  onRowConditionFieldChange(row: ReceivingRuntimeField): void {
    if (!row.visible_when_key) {
      row.visible_when_value = '';
      return;
    }
    const options = this.getConditionValueOptionsByKey(row.visible_when_key);
    if (options.length > 0 && options.indexOf(row.visible_when_value) === -1) {
      row.visible_when_value = options[0];
    }
  }

  addCustomField(): void {
    const key = buildReceivingKeyFromDisplayName(this.customFieldLabel);
    if (!isValidReceivingFieldKey(key)) {
      alertify.error('Display name is required to auto-create input field name.');
      return;
    }
    if (this.rows.some((r) => r.field_key === key)) {
      alertify.error('Input field name already exists.');
      return;
    }
    const maxSort = this.rows
      .filter((r) => r.layout_group === this.customFieldGroup)
      .reduce((m, r) => Math.max(m, Number(r.sort_order) || 0), 0);
    this.rows.push({
      field_key: key,
      defaultLabel: this.customFieldLabel || key,
      field_label: this.customFieldLabel || key,
      layout_group: this.customFieldGroup,
      defaultSort: maxSort + 10,
      sort_order: maxSort + 10,
      defaultType: this.customFieldType,
      field_type: this.customFieldType,
      allowTypeOverride: true,
      lockReadOnly: false,
      applicable: 'Applicable',
      field_options:
        this.customFieldType === 'DROPDOWN' || this.customFieldType === 'YESNO'
          ? this.normalizeDropdownOptions(this.customFieldOptions).join(',')
          : '',
      visible_when_key: String(this.customVisibleWhenKey || '').trim(),
      visible_when_value: String(this.customVisibleWhenValue || '').trim(),
      default_value: String(this.customDefaultValue || '').trim(),
    });
    this.customFieldLabel = '';
    this.customFieldGroup = 'other';
    this.customFieldType = 'TEXT';
    this.customFieldOptions = '';
    this.customFieldPattern = 'Custom';
    this.customVisibleWhenKey = '';
    this.customVisibleWhenValue = '';
    this.customDefaultValue = '';
  }

  private loadCurrentLayout(): void {
    this.service.getActiveLayout().subscribe({
      next: (res: any) => {
        const fields = Array.isArray(res?.fields) ? res.fields : null;
        this.rows = mergeReceivingLayoutRows(fields);
      },
      error: () => {
        this.rows = buildDefaultReceivingRuntimeFields();
      },
    });
  }

  private normalizeDropdownOptions(raw: string): string[] {
    const value = String(raw || '').trim();
    if (!value) {
      return [];
    }
    return value
      .split(/[,|\n;\/]+/)
      .map((x) => String(x || '').trim())
      .filter((x) => x.length > 0);
  }

  submit(): void {
    const username = localStorage.getItem('username') || 'Unknown';
    const fields = this.rows.map((r) => ({
      field_key: r.field_key,
      field_label: (r.field_label || '').trim() || r.defaultLabel,
      layout_group: r.layout_group,
      field_type: r.allowTypeOverride ? r.field_type : 'STANDARD',
      sort_order: Number(r.sort_order) || 0,
      applicable: r.lockReadOnly ? 'Applicable' : r.applicable,
      field_options:
        r.field_type === 'DROPDOWN' || r.field_type === 'YESNO'
          ? this.normalizeDropdownOptions(String(r.field_options || '')).join(',')
          : '',
      visible_when_key: String(r.visible_when_key || '').trim(),
      visible_when_value: String(r.visible_when_value || '').trim(),
      default_value: String(r.default_value || '').trim(),
    }));
    this.service.saveRequest({ entry_by: username, fields }).subscribe(
      (res: any) => {
        if (res?.status === 'success') {
          alertify.success('Saved and sent for approval.');
          this.router.navigate(['/qa/soft-restriction/receiving-form-customisation']);
          return;
        }
        alertify.error(res?.message || 'Save failed');
      },
      () => alertify.error('Save failed')
    );
  }

  cancel(): void {
    this.router.navigate(['/qa/soft-restriction/receiving-form-customisation']);
  }
}
