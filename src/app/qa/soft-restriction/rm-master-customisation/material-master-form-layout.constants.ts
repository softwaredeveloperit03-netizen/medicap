/**
 * Material Master (master/material/new) — default form layout metadata.
 * Sync field_key list with backend softcust/gmp_customisation_form.php $VALID_FIELD_KEYS
 */
export type MaterialLayoutGroup = 'core' | 'full' | 'grid' | 'other';
/** STANDARD = native controls (selects, special blocks). TEXT / CHECKBOX = optional overrides where allowed */
export type MaterialFieldType = 'STANDARD' | 'TEXT' | 'CHECKBOX';

export interface MaterialFormFieldDef {
  field_key: string;
  defaultLabel: string;
  layout_group: MaterialLayoutGroup;
  /** default sort within the layout group */
  defaultSort: number;
  defaultType: MaterialFieldType;
  /** If false, customisation UI only allows STANDARD */
  allowTypeOverride: boolean;
}

export const MATERIAL_MASTER_FORM_FIELD_DEFS: MaterialFormFieldDef[] = [
  { field_key: 'source_type', defaultLabel: 'Source Type', layout_group: 'core', defaultSort: 5, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'material_group_type', defaultLabel: 'Material Group/Type', layout_group: 'core', defaultSort: 10, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'sub_group_type', defaultLabel: 'Sub Group/Type', layout_group: 'core', defaultSort: 20, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'nature_of_material', defaultLabel: 'Nature of material', layout_group: 'core', defaultSort: 30, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'category', defaultLabel: 'Category', layout_group: 'core', defaultSort: 40, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'material_name', defaultLabel: 'Material name', layout_group: 'core', defaultSort: 48, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'material_code', defaultLabel: 'Material code', layout_group: 'core', defaultSort: 52, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'material_name_report', defaultLabel: 'Material name (report)', layout_group: 'core', defaultSort: 60, defaultType: 'STANDARD', allowTypeOverride: true },

  { field_key: 'grade', defaultLabel: 'Grade', layout_group: 'full', defaultSort: 10, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'mother_material_code', defaultLabel: 'Copy from mother material', layout_group: 'full', defaultSort: 20, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'equiv_table', defaultLabel: 'Equivalent materials', layout_group: 'full', defaultSort: 30, defaultType: 'STANDARD', allowTypeOverride: false },

  { field_key: 'item_unit', defaultLabel: 'Item unit', layout_group: 'grid', defaultSort: 10, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'billing_unit', defaultLabel: 'Billing unit', layout_group: 'grid', defaultSort: 20, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'packing_type', defaultLabel: 'Type', layout_group: 'grid', defaultSort: 30, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'packing_size', defaultLabel: 'Size/Specification', layout_group: 'grid', defaultSort: 40, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'packing_color', defaultLabel: 'Color', layout_group: 'grid', defaultSort: 50, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'packing_dimension', defaultLabel: 'Dimension', layout_group: 'grid', defaultSort: 60, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'packing_made_of', defaultLabel: 'Material Made of', layout_group: 'grid', defaultSort: 70, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'hsn', defaultLabel: 'HSN', layout_group: 'grid', defaultSort: 80, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'tax_type', defaultLabel: 'Tax Type', layout_group: 'grid', defaultSort: 90, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'tax_gst', defaultLabel: 'GST / Tax', layout_group: 'grid', defaultSort: 100, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'salt_equivalency', defaultLabel: 'Salt Equivalency', layout_group: 'grid', defaultSort: 110, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'assay_calculation', defaultLabel: 'Assay Calculation', layout_group: 'grid', defaultSort: 120, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'packing_sub_type', defaultLabel: 'Sub Type', layout_group: 'grid', defaultSort: 130, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'artwork', defaultLabel: 'Artwork', layout_group: 'grid', defaultSort: 140, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'packing_product', defaultLabel: 'Product', layout_group: 'grid', defaultSort: 150, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'retest_month', defaultLabel: 'Retest Month', layout_group: 'grid', defaultSort: 160, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'indent_type', defaultLabel: 'Purchase Requisition Type', layout_group: 'grid', defaultSort: 170, defaultType: 'STANDARD', allowTypeOverride: false },

  { field_key: 'density', defaultLabel: 'Density (G/Ml)', layout_group: 'other', defaultSort: 10, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'storage_condition', defaultLabel: 'Storage Condition', layout_group: 'other', defaultSort: 20, defaultType: 'STANDARD', allowTypeOverride: false },
  { field_key: 'min_inventory_level', defaultLabel: 'Min. Inventory Level', layout_group: 'other', defaultSort: 30, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'max_inventory_level', defaultLabel: 'Max. Inventory Level', layout_group: 'other', defaultSort: 40, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'specific_gravity', defaultLabel: 'Specific Gravity', layout_group: 'other', defaultSort: 50, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'texture', defaultLabel: 'Texture', layout_group: 'other', defaultSort: 60, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'moq', defaultLabel: 'MOQ', layout_group: 'other', defaultSort: 70, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'plastic_type', defaultLabel: 'Plastic Type', layout_group: 'other', defaultSort: 80, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'inventory_value_max', defaultLabel: 'Inventory Value Max', layout_group: 'other', defaultSort: 90, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'premix_item', defaultLabel: 'Premix Item', layout_group: 'other', defaultSort: 100, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'qc_lead_time_days', defaultLabel: 'QC Lead Time(Days)', layout_group: 'other', defaultSort: 110, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'description', defaultLabel: 'Description', layout_group: 'other', defaultSort: 120, defaultType: 'STANDARD', allowTypeOverride: true },
  { field_key: 'safety', defaultLabel: 'Safety', layout_group: 'other', defaultSort: 130, defaultType: 'STANDARD', allowTypeOverride: true },
];

/** Map legacy RM Master Customisation keys -> layout field_key (for fallback visibility) */
export const RM_KEY_TO_LAYOUT_KEY: Record<string, string> = {
  source_type: 'source_type',
  material_group_type: 'material_group_type',
  sub_group_type: 'sub_group_type',
  nature_of_material: 'nature_of_material',
  category: 'category',
  material_code: 'material_code',
  material_name: 'material_name',
  grade: 'grade',
  item_unit: 'item_unit',
  billing_unit: 'billing_unit',
  hsn: 'hsn',
  tax_type: 'tax_type',
  retest_month: 'retest_month',
  storage_condition: 'storage_condition',
  min_inventory_level: 'min_inventory_level',
  max_inventory_level: 'max_inventory_level',
  specific_gravity: 'specific_gravity',
  moq: 'moq',
  plastic_type: 'plastic_type',
  inventory_value_max: 'inventory_value_max',
  premix_item: 'premix_item',
  qc_lead_time_days: 'qc_lead_time_days',
  description: 'description',
  safety: 'safety',
};

export interface MaterialFormFieldRuntime extends MaterialFormFieldDef {
  field_label: string;
  sort_order: number;
  field_type: MaterialFieldType;
  applicable: 'Applicable' | 'Not Applicable';
}

export function isValidFieldKey(key: string): boolean {
  return /^[a-z][a-z0-9_]{1,127}$/.test((key || '').trim());
}

export function isKnownDefaultFieldKey(key: string): boolean {
  return MATERIAL_MASTER_FORM_FIELD_DEFS.some((d) => d.field_key === key);
}

export interface GmpFormLayoutRow {
  field_key: string;
  field_label: string;
  layout_group: string;
  field_type: string;
  sort_order: number;
  applicable: string;
}

export interface GmpActiveLayoutResponse {
  form_code: string;
  log_id: number;
  fields: GmpFormLayoutRow[];
}

export function buildDefaultRuntimeFields(): MaterialFormFieldRuntime[] {
  return MATERIAL_MASTER_FORM_FIELD_DEFS.map((d) => ({
    ...d,
    field_label: d.defaultLabel,
    sort_order: d.defaultSort,
    field_type: d.defaultType,
    applicable: 'Applicable' as const,
  }));
}

export function mergeGmpLayout(api: GmpActiveLayoutResponse | null): MaterialFormFieldRuntime[] {
  const map = new Map<string, GmpFormLayoutRow>();
  if (api?.fields?.length) {
    api.fields.forEach((r) => map.set(r.field_key, r));
  }
  const known: MaterialFormFieldRuntime[] = MATERIAL_MASTER_FORM_FIELD_DEFS.map((def) => {
    const row = map.get(def.field_key);
    let ft = (row?.field_type as MaterialFieldType) || def.defaultType;
    if (!def.allowTypeOverride) {
      ft = 'STANDARD';
    } else if (ft !== 'TEXT' && ft !== 'CHECKBOX') {
      ft = def.defaultType;
    }
    const applicable: 'Applicable' | 'Not Applicable' = row?.applicable === 'Not Applicable' ? 'Not Applicable' : 'Applicable';
    return {
      ...def,
      field_label: row?.field_label || def.defaultLabel,
      sort_order: row != null ? Number(row.sort_order) : def.defaultSort,
      field_type: ft,
      applicable,
    };
  });
  const knownKeys = new Set(known.map((k) => k.field_key));
  const extra: MaterialFormFieldRuntime[] = [];
  map.forEach((row, key) => {
    if (knownKeys.has(key) || !isValidFieldKey(key)) {
      return;
    }
    const lg = row.layout_group === 'core' || row.layout_group === 'full' || row.layout_group === 'grid' || row.layout_group === 'other'
      ? row.layout_group
      : 'other';
    const ft = row.field_type === 'CHECKBOX' ? 'CHECKBOX' : row.field_type === 'TEXT' ? 'TEXT' : 'TEXT';
    const applicable: 'Applicable' | 'Not Applicable' = row.applicable === 'Not Applicable' ? 'Not Applicable' : 'Applicable';
    extra.push({
      field_key: key,
      defaultLabel: row.field_label || key,
      layout_group: lg,
      defaultSort: Number(row.sort_order) || 999,
      defaultType: ft,
      allowTypeOverride: true,
      field_label: row.field_label || key,
      sort_order: Number(row.sort_order) || 999,
      field_type: ft,
      applicable,
    });
  });
  return [...known, ...extra];
}

export function sortByGroup(fields: MaterialFormFieldRuntime[], group: MaterialLayoutGroup): MaterialFormFieldRuntime[] {
  return fields.filter((f) => f.layout_group === group).sort((a, b) => a.sort_order - b.sort_order);
}

/** Apply legacy RM visibility when no GMP active layout */
export function applyRmVisibilityFallback(
  fields: MaterialFormFieldRuntime[],
  rm: Record<string, string> | null
): MaterialFormFieldRuntime[] {
  if (!rm) {
    return fields;
  }
  const hideOther = rm.other_information === 'Not Applicable';
  return fields.map((f) => {
    // Storage Condition has its own visibility key; do not hide it when only Other Information is off
    if (f.layout_group === 'other' && hideOther && f.field_key !== 'storage_condition') {
      return { ...f, applicable: 'Not Applicable' };
    }
    const legacyKeys = Object.keys(RM_KEY_TO_LAYOUT_KEY).filter((k) => RM_KEY_TO_LAYOUT_KEY[k] === f.field_key);
    let app: 'Applicable' | 'Not Applicable' = f.applicable;
    for (const lk of legacyKeys) {
      const v = rm[lk];
      if (v === 'Not Applicable') {
        app = 'Not Applicable';
      }
    }
    return { ...f, applicable: app };
  });
}
