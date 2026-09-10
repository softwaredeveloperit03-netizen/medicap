/** Field keys used in RM Master Customisation and in master/material/raw/new visibility */
export const RM_CUSTOMISATION_FIELDS: { key: string; label: string }[] = [
  { key: 'material_group_type', label: 'Material Group/Type' },
  { key: 'sub_group_type', label: 'Sub Group/Type' },
  { key: 'nature_of_material', label: 'Nature Of Material' },
  { key: 'category', label: 'Category' },
  { key: 'in_active', label: 'In Active' },
  { key: 'material_name', label: 'Material Name' },
  { key: 'grade', label: 'Grade' },
  { key: 'item_unit', label: 'Item Unit' },
  { key: 'billing_unit', label: 'Billing Unit' },
  { key: 'hsn', label: 'HSN (4-8 digits)' },
  { key: 'tax_type', label: 'Tax Type' },
  { key: 'retest_month', label: 'Retest Month' },
  { key: 'other_information', label: 'Other Information' },
  { key: 'storage_condition', label: 'Storage Condition' },
  { key: 'min_inventory_level', label: 'Min. Inventory Level' },
  { key: 'max_inventory_level', label: 'Max. Inventory Level' },
  { key: 'specific_gravity', label: 'Specific Gravity' },
  { key: 'moq', label: 'MOQ' },
  { key: 'plastic_type', label: 'Plastic Type' },
  { key: 'inventory_value_max', label: 'Inventory Value Max' },
  { key: 'premix_item', label: 'Premix Item' },
  { key: 'qc_lead_time_days', label: 'QC Lead Time(Days)' },
  { key: 'description', label: 'Description' },
  { key: 'safety', label: 'Safety' },
];

export type ApplicableValue = 'Applicable' | 'Not Applicable';

export interface RMCustomisationRecord {
  id?: number;
  request_no?: string;
  revision_number?: string;
  entry_by?: string;
  entry_date?: string;
  approval_by?: string;
  approval_date?: string;
  status?: 'Pending' | 'Approved' | 'Rejected';
  [fieldKey: string]: string | number | undefined;
}
