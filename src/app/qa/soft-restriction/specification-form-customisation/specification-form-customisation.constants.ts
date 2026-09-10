export type SpecificationScope = 'Raw Material' | 'Packing Material' | 'Finish Product';
export type SpecificationFieldType = 'STANDARD' | 'TEXT' | 'CHECKBOX' | 'DROPDOWN' | 'YESNO';
export type SpecificationLayoutGroup = 'core' | 'version' | 'general' | 'sampling' | 'tests' | 'revision' | 'other';

export interface SpecificationFormFieldDef {
  field_key: string;
  defaultLabel: string;
  layout_group: SpecificationLayoutGroup;
  defaultSort: number;
  defaultType: SpecificationFieldType;
  allowTypeOverride: boolean;
}

export interface SpecificationRuntimeField extends SpecificationFormFieldDef {
  field_label: string;
  sort_order: number;
  field_type: SpecificationFieldType;
  applicable: 'Applicable' | 'Not Applicable';
  field_options: string;
}

export interface SpecificationCustomisationLogMeta {
  id?: number;
  request_no?: string;
  revision_number?: string;
  entry_by?: string;
  entry_date?: string;
  approval_by?: string;
  approval_date?: string;
  status?: string;
  form_code?: string;
  fields?: any[];
}

export const SPEC_SCOPE_TO_FORM_CODE: Record<SpecificationScope, string> = {
  'Raw Material': 'SPECIFICATION_RAW_MATERIAL',
  'Packing Material': 'SPECIFICATION_PACKING_MATERIAL',
  'Finish Product': 'SPECIFICATION_FINISH_PRODUCT',
};

export const SPECIFICATION_FORM_DEFAULT_FIELDS: SpecificationFormFieldDef[] = [
  { field_key: 'specIs', defaultLabel: 'Specification Type', layout_group: 'core', defaultSort: 10, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'spec_type', defaultLabel: 'Material Type', layout_group: 'core', defaultSort: 20, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'material_subtype', defaultLabel: 'Subtype / Product Type', layout_group: 'core', defaultSort: 30, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'material_name', defaultLabel: 'Material / Product Name', layout_group: 'core', defaultSort: 40, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'material_code', defaultLabel: 'Item Code', layout_group: 'core', defaultSort: 50, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'material_grade', defaultLabel: 'Grade', layout_group: 'core', defaultSort: 60, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'specification_no', defaultLabel: 'Specification No.', layout_group: 'version', defaultSort: 10, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'supersede_no', defaultLabel: 'Supersede No.', layout_group: 'version', defaultSort: 20, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'version_no', defaultLabel: 'Version No.', layout_group: 'version', defaultSort: 30, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'issuedDate', defaultLabel: 'Issued Date', layout_group: 'version', defaultSort: 40, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'review_date', defaultLabel: 'Review Date', layout_group: 'version', defaultSort: 50, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'effective_date', defaultLabel: 'Effective Date', layout_group: 'version', defaultSort: 60, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'retest_period', defaultLabel: 'Reanalysis Period', layout_group: 'version', defaultSort: 70, defaultType: 'DROPDOWN', allowTypeOverride: true },
  { field_key: 'sampling_plan', defaultLabel: 'Sampling Plan', layout_group: 'version', defaultSort: 80, defaultType: 'DROPDOWN', allowTypeOverride: true },
  { field_key: 'test_method_no', defaultLabel: 'Test Method No.', layout_group: 'version', defaultSort: 90, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'storage_condition', defaultLabel: 'Storage Requirement', layout_group: 'general', defaultSort: 10, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'samplingDetails', defaultLabel: 'Sampling', layout_group: 'general', defaultSort: 20, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'hazardAndPrecautions', defaultLabel: 'Hazard & Precautions', layout_group: 'general', defaultSort: 30, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'stockTransfer', defaultLabel: 'Stock Transfer (STO)', layout_group: 'general', defaultSort: 40, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'note', defaultLabel: 'Note', layout_group: 'general', defaultSort: 50, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'sample_qty', defaultLabel: 'Sample Qty', layout_group: 'sampling', defaultSort: 10, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'control_sample', defaultLabel: 'Control Sample Qty', layout_group: 'sampling', defaultSort: 20, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'additional_sample', defaultLabel: 'Additional Sample Qty', layout_group: 'sampling', defaultSort: 30, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'totalsample_qty', defaultLabel: 'Total Sample Qty', layout_group: 'sampling', defaultSort: 40, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'unit', defaultLabel: 'Unit', layout_group: 'sampling', defaultSort: 50, defaultType: 'DROPDOWN', allowTypeOverride: true },
  { field_key: 'reference_type', defaultLabel: 'Reference', layout_group: 'tests', defaultSort: 10, defaultType: 'DROPDOWN', allowTypeOverride: true },
  { field_key: 'outside_testing', defaultLabel: 'Outside Testing', layout_group: 'tests', defaultSort: 20, defaultType: 'YESNO', allowTypeOverride: true },
  { field_key: 'retest', defaultLabel: 'Recurring Inspection', layout_group: 'tests', defaultSort: 30, defaultType: 'YESNO', allowTypeOverride: true },
  { field_key: 'release_stability', defaultLabel: 'Stability Indicating', layout_group: 'tests', defaultSort: 40, defaultType: 'YESNO', allowTypeOverride: true },
  { field_key: 'bulk_release', defaultLabel: 'Bulk Release', layout_group: 'tests', defaultSort: 50, defaultType: 'YESNO', allowTypeOverride: true },
  { field_key: 'sto', defaultLabel: 'Stock Transfer(STO)', layout_group: 'tests', defaultSort: 60, defaultType: 'YESNO', allowTypeOverride: true },
  { field_key: 'change_mode', defaultLabel: 'Change Control No.', layout_group: 'revision', defaultSort: 10, defaultType: 'TEXT', allowTypeOverride: true },
  { field_key: 'reason', defaultLabel: 'Reason for Change', layout_group: 'revision', defaultSort: 20, defaultType: 'TEXT', allowTypeOverride: true },
];

export function isValidSpecFieldKey(key: string): boolean {
  return /^[a-z][a-z0-9_]{1,127}$/.test((key || '').trim());
}

export function isKnownSpecDefaultFieldKey(key: string): boolean {
  return SPECIFICATION_FORM_DEFAULT_FIELDS.some((d) => d.field_key === key);
}

export function buildDefaultSpecRuntimeFields(): SpecificationRuntimeField[] {
  return SPECIFICATION_FORM_DEFAULT_FIELDS.map((d) => ({
    ...d,
    field_label: d.defaultLabel,
    sort_order: d.defaultSort,
    field_type: d.defaultType,
    applicable: 'Applicable',
    field_options: '',
  }));
}

export function mergeSpecLayoutRows(activeRows: any[] | null | undefined): SpecificationRuntimeField[] {
  const defaults = buildDefaultSpecRuntimeFields();
  const rows = Array.isArray(activeRows) ? activeRows : [];
  const byKey = new Map<string, any>();
  rows.forEach((r) => {
    const key = String(r?.field_key || '').trim();
    if (key) {
      byKey.set(key, r);
    }
  });

  const merged: SpecificationRuntimeField[] = defaults.map((d) => {
    const row = byKey.get(d.field_key);
    return {
      ...d,
      field_label: String(row?.field_label || d.defaultLabel),
      sort_order: Number(row?.sort_order ?? d.defaultSort),
      field_type: (row?.field_type as SpecificationFieldType) || d.defaultType,
      applicable: String(row?.applicable || 'Applicable') === 'Not Applicable' ? 'Not Applicable' : 'Applicable',
      field_options: String(row?.field_options || ''),
    };
  });

  const existingKeys = new Set(merged.map((x) => x.field_key));
  rows.forEach((r) => {
    const key = String(r?.field_key || '').trim();
    if (!key || existingKeys.has(key) || !isValidSpecFieldKey(key)) {
      return;
    }
    const lg = String(r?.layout_group || 'other').toLowerCase();
    const layout_group: SpecificationLayoutGroup =
      lg === 'core' || lg === 'version' || lg === 'general' || lg === 'sampling' || lg === 'tests' || lg === 'revision' || lg === 'other'
        ? (lg as SpecificationLayoutGroup)
        : 'other';
    const ft = String(r?.field_type || 'TEXT').toUpperCase();
    const field_type: SpecificationFieldType =
      ft === 'STANDARD' || ft === 'TEXT' || ft === 'CHECKBOX' || ft === 'DROPDOWN' || ft === 'YESNO'
        ? (ft as SpecificationFieldType)
        : 'TEXT';
    merged.push({
      field_key: key,
      defaultLabel: String(r?.field_label || key),
      field_label: String(r?.field_label || key),
      layout_group,
      defaultSort: Number(r?.sort_order || 999),
      sort_order: Number(r?.sort_order || 999),
      defaultType: field_type,
      field_type,
      allowTypeOverride: true,
      applicable: String(r?.applicable || 'Applicable') === 'Not Applicable' ? 'Not Applicable' : 'Applicable',
      field_options: String(r?.field_options || ''),
    });
  });

  return merged;
}

export function buildKeyFromDisplayName(label: string): string {
  return String(label || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '');
}
