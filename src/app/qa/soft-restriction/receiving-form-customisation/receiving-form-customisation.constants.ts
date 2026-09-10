export type ReceivingFieldType = 'STANDARD' | 'TEXT' | 'CHECKBOX' | 'DROPDOWN' | 'YESNO';
export type ReceivingLayoutGroup = 'labeling' | 'receiving' | 'container' | 'others' | 'checklist' | 'other';

export interface ReceivingFieldDef {
  field_key: string;
  defaultLabel: string;
  layout_group: ReceivingLayoutGroup;
  defaultSort: number;
  defaultType: ReceivingFieldType;
  allowTypeOverride: boolean;
  lockReadOnly: boolean;
}

export interface ReceivingRuntimeField extends ReceivingFieldDef {
  field_label: string;
  sort_order: number;
  field_type: ReceivingFieldType;
  applicable: 'Applicable' | 'Not Applicable';
  field_options: string;
  visible_when_key: string;
  visible_when_value: string;
  default_value: string;
}

export interface ReceivingCustomisationLogMeta {
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

export const RECEIVING_FORM_CODE = 'RECEIVING_AWAITING_FORM';

export const RECEIVING_DEFAULT_FIELDS: ReceivingFieldDef[] = [
  { field_key: 'batch_no', defaultLabel: 'Medicap Lot No', layout_group: 'labeling', defaultSort: 10, defaultType: 'TEXT', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'qty_received', defaultLabel: 'Batch Qty', layout_group: 'labeling', defaultSort: 20, defaultType: 'TEXT', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'pack_size', defaultLabel: 'Pack Size', layout_group: 'labeling', defaultSort: 30, defaultType: 'TEXT', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'total_containers', defaultLabel: 'No Of Containers', layout_group: 'labeling', defaultSort: 40, defaultType: 'TEXT', allowTypeOverride: false, lockReadOnly: true },
  { field_key: 'mfg_date', defaultLabel: 'Mfg Date', layout_group: 'labeling', defaultSort: 50, defaultType: 'STANDARD', allowTypeOverride: false, lockReadOnly: false },
  { field_key: 'exp_date', defaultLabel: 'Exp Date', layout_group: 'labeling', defaultSort: 60, defaultType: 'STANDARD', allowTypeOverride: false, lockReadOnly: false },
  { field_key: 'coa_received', defaultLabel: 'COA Received', layout_group: 'labeling', defaultSort: 70, defaultType: 'DROPDOWN', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'container_type', defaultLabel: 'Container Type', layout_group: 'receiving', defaultSort: 10, defaultType: 'DROPDOWN', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'container_subtype', defaultLabel: 'Container Subtype', layout_group: 'receiving', defaultSort: 20, defaultType: 'DROPDOWN', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'challan_qty', defaultLabel: 'Qty. As Per Challan', layout_group: 'receiving', defaultSort: 30, defaultType: 'TEXT', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'received_qty', defaultLabel: 'Actual Received Qty', layout_group: 'receiving', defaultSort: 40, defaultType: 'TEXT', allowTypeOverride: false, lockReadOnly: true },
  { field_key: 'containerTotal', defaultLabel: 'No. Of Containers', layout_group: 'receiving', defaultSort: 50, defaultType: 'TEXT', allowTypeOverride: false, lockReadOnly: true },
  { field_key: 'short_qty', defaultLabel: 'Short / Extra Quantity', layout_group: 'receiving', defaultSort: 60, defaultType: 'TEXT', allowTypeOverride: false, lockReadOnly: true },
  { field_key: 'po_status', defaultLabel: 'PO Status', layout_group: 'receiving', defaultSort: 70, defaultType: 'DROPDOWN', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'isdamagecontainer', defaultLabel: 'Damage Container Observed', layout_group: 'container', defaultSort: 10, defaultType: 'YESNO', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'outer_damage', defaultLabel: 'Total Damage Containers', layout_group: 'container', defaultSort: 20, defaultType: 'TEXT', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'hold_qty', defaultLabel: 'Hold Quantity', layout_group: 'container', defaultSort: 30, defaultType: 'TEXT', allowTypeOverride: false, lockReadOnly: true },
  { field_key: 'dedusting_applicable', defaultLabel: 'Dedusting Applicable', layout_group: 'others', defaultSort: 10, defaultType: 'YESNO', allowTypeOverride: true, lockReadOnly: false },
  { field_key: 'entry_time_start', defaultLabel: 'Receiving Start Time', layout_group: 'others', defaultSort: 20, defaultType: 'STANDARD', allowTypeOverride: false, lockReadOnly: false },
  { field_key: 'entry_time_end', defaultLabel: 'Receiving End Time', layout_group: 'others', defaultSort: 30, defaultType: 'STANDARD', allowTypeOverride: false, lockReadOnly: false },
];

export function buildDefaultReceivingRuntimeFields(): ReceivingRuntimeField[] {
  return RECEIVING_DEFAULT_FIELDS.map((d) => ({
    ...d,
    field_label: d.defaultLabel,
    sort_order: d.defaultSort,
    field_type: d.defaultType,
    applicable: 'Applicable',
    field_options: '',
    visible_when_key: '',
    visible_when_value: '',
    default_value: '',
  }));
}

export function isValidReceivingFieldKey(key: string): boolean {
  return /^[a-z][a-z0-9_]{1,127}$/.test((key || '').trim());
}

export function isKnownReceivingDefaultFieldKey(key: string): boolean {
  return RECEIVING_DEFAULT_FIELDS.some((d) => d.field_key === key);
}

export function buildReceivingKeyFromDisplayName(label: string): string {
  return String(label || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '');
}

export function mergeReceivingLayoutRows(activeRows: any[] | null | undefined): ReceivingRuntimeField[] {
  const defaults = buildDefaultReceivingRuntimeFields();
  const rows = Array.isArray(activeRows) ? activeRows : [];
  const byKey = new Map<string, any>();
  rows.forEach((r) => {
    const key = String(r?.field_key || '').trim();
    if (key) {
      byKey.set(key, r);
    }
  });

  const merged: ReceivingRuntimeField[] = defaults.map((d) => {
    const row = byKey.get(d.field_key);
    return {
      ...d,
      field_label: String(row?.field_label || d.defaultLabel),
      sort_order: Number(row?.sort_order ?? d.defaultSort),
      field_type: (row?.field_type as ReceivingFieldType) || d.defaultType,
      applicable: d.lockReadOnly ? 'Applicable' : (String(row?.applicable || 'Applicable') === 'Not Applicable' ? 'Not Applicable' : 'Applicable'),
      field_options: String(row?.field_options || ''),
      visible_when_key: String(row?.visible_when_key || ''),
      visible_when_value: String(row?.visible_when_value || ''),
      default_value: String(row?.default_value || ''),
    };
  });

  const existingKeys = new Set(merged.map((x) => x.field_key));
  rows.forEach((r) => {
    const key = String(r?.field_key || '').trim();
    if (!key || existingKeys.has(key) || !isValidReceivingFieldKey(key)) {
      return;
    }
    const lg = String(r?.layout_group || 'other').toLowerCase();
    const layout_group: ReceivingLayoutGroup =
      lg === 'labeling' || lg === 'receiving' || lg === 'container' || lg === 'others' || lg === 'checklist' || lg === 'other'
        ? (lg as ReceivingLayoutGroup)
        : 'other';
    const ft = String(r?.field_type || 'TEXT').toUpperCase();
    const field_type: ReceivingFieldType =
      ft === 'STANDARD' || ft === 'TEXT' || ft === 'CHECKBOX' || ft === 'DROPDOWN' || ft === 'YESNO'
        ? (ft as ReceivingFieldType)
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
      lockReadOnly: false,
      applicable: String(r?.applicable || 'Applicable') === 'Not Applicable' ? 'Not Applicable' : 'Applicable',
      field_options: String(r?.field_options || ''),
      visible_when_key: String(r?.visible_when_key || ''),
      visible_when_value: String(r?.visible_when_value || ''),
      default_value: String(r?.default_value || ''),
    });
  });

  return merged;
}
