import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import {
  buildKeyFromDisplayName,
  buildDefaultSpecRuntimeFields,
  isKnownSpecDefaultFieldKey,
  isValidSpecFieldKey,
  mergeSpecLayoutRows,
  SpecificationFieldType,
  SpecificationLayoutGroup,
  SpecificationRuntimeField,
  SpecificationScope,
} from '../specification-form-customisation.constants';
import { SpecificationFormCustomisationService } from '../specification-form-customisation.service';
declare let alertify: any;

@Component({
  selector: 'app-specification-form-customisation-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  scope: SpecificationScope = 'Raw Material';
  rows: SpecificationRuntimeField[] = [];
  customFieldLabel = '';
  customFieldGroup: SpecificationLayoutGroup = 'other';
  customFieldType: SpecificationFieldType = 'TEXT';
  customFieldOptions = '';
  customFieldPattern = 'Custom';

  readonly scopes: SpecificationScope[] = ['Raw Material', 'Packing Material', 'Finish Product'];
  readonly groups: SpecificationLayoutGroup[] = ['core', 'version', 'general', 'sampling', 'tests', 'revision', 'other'];
  readonly types: SpecificationFieldType[] = ['STANDARD', 'TEXT', 'CHECKBOX', 'DROPDOWN', 'YESNO'];
  readonly dropdownPatterns = ['Custom', 'YES/No', 'Applicable/Not Applicable'];

  constructor(private service: SpecificationFormCustomisationService, private router: Router) {}

  ngOnInit(): void {
    this.loadCurrentScopeLayout();
  }

  trackByKey(_i: number, row: SpecificationRuntimeField): string {
    return row.field_key;
  }

  get autoCustomFieldKey(): string {
    return buildKeyFromDisplayName(this.customFieldLabel);
  }

  setScope(scope: SpecificationScope): void {
    this.scope = scope;
    this.loadCurrentScopeLayout();
  }

  rowsForGroup(group: SpecificationLayoutGroup): SpecificationRuntimeField[] {
    return this.rows.filter((r) => r.layout_group === group).sort((a, b) => a.sort_order - b.sort_order);
  }

  isDefaultRow(row: SpecificationRuntimeField): boolean {
    return isKnownSpecDefaultFieldKey(row.field_key);
  }

  removeCustomField(row: SpecificationRuntimeField): void {
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

  getRowPattern(row: SpecificationRuntimeField): string {
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

  setRowPattern(row: SpecificationRuntimeField, pattern: string): void {
    if (pattern === 'YES/No') {
      row.field_options = 'YES,No';
      return;
    }
    if (pattern === 'Applicable/Not Applicable') {
      row.field_options = 'Applicable,Not Applicable';
      return;
    }
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

  addCustomField(): void {
    const key = buildKeyFromDisplayName(this.customFieldLabel);
    if (!isValidSpecFieldKey(key)) {
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
      applicable: 'Applicable',
      field_options:
        this.customFieldType === 'DROPDOWN' || this.customFieldType === 'YESNO'
          ? this.normalizeDropdownOptions(this.customFieldOptions).join(',')
          : '',
    });
    this.customFieldLabel = '';
    this.customFieldGroup = 'other';
    this.customFieldType = 'TEXT';
    this.customFieldOptions = '';
    this.customFieldPattern = 'Custom';
  }

  private loadCurrentScopeLayout(): void {
    this.service.getActiveLayout(this.scope).subscribe({
      next: (res: any) => {
        const fields = Array.isArray(res?.fields) ? res.fields : null;
        this.rows = mergeSpecLayoutRows(fields);
      },
      error: () => {
        this.rows = buildDefaultSpecRuntimeFields();
      },
    });
  }

  submit(): void {
    const username = localStorage.getItem('username') || 'Unknown';
    const fields = this.rows.map((r) => ({
      field_key: r.field_key,
      field_label: (r.field_label || '').trim() || r.defaultLabel,
      layout_group: r.layout_group,
      field_type: r.allowTypeOverride ? r.field_type : 'STANDARD',
      sort_order: Number(r.sort_order) || 0,
      applicable: r.applicable,
      field_options:
        r.field_type === 'DROPDOWN' || r.field_type === 'YESNO'
          ? this.normalizeDropdownOptions(String(r.field_options || '')).join(',')
          : '',
    }));
    this.service.saveRequest({ scope: this.scope, entry_by: username, fields }).subscribe(
      (res: any) => {
        if (res?.status === 'success') {
          alertify.success('Saved and sent for approval.');
          this.router.navigate(['/qa/soft-restriction/specification-form-customisation']);
          return;
        }
        alertify.error(res?.message || 'Save failed');
      },
      () => alertify.error('Save failed')
    );
  }

  cancel(): void {
    this.router.navigate(['/qa/soft-restriction/specification-form-customisation']);
  }
}
