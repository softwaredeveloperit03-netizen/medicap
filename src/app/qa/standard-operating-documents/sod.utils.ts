export const SOP_REF = 'SOP-QA-013';
export const REVISION_NO = '00';
export const EFFECTIVE_DATE = '2025-03-24';

export const FORM_A_NO = 'FQA-013-A';
export const FORM_B_NO = 'FQA-013-B';

export const SOD_DEPARTMENTS = [
  'Quality Control',
  'Quality Assurance',
  'Regulatory Affairs',
  'Production',
  'Material Management',
  'Formulation',
  'Eng./ Maintenance',
  'Warehouse S/R',
  'Corporate/ HR',
];

export const REVISION_COUNT = 1;
export const BIENNIAL_ROW_COUNT = 15;

export interface RevDistributionCell {
  effective_date: string;
  distributed: string;
  recovered: string;
  cc_no: string;
}

export interface RevReconciliation {
  copies_recovered_destroyed: string;
  date_destroyed: string;
  destroyed_by: string;
}

export interface DepartmentDistributionRow {
  department: string;
  revisions: RevDistributionCell[];
}

export interface BiennialReviewRow {
  revision_no: string;
  effective_date: string;
  review_dept: string;
  review_by: string;
  review_by_emp_id: string;
  change_control_no: string;
  comment: string;
  initial_date: string;
  /** @deprecated legacy combined field */
  reviewed_by_dept?: string;
}

export function createEmptyRevCell(): RevDistributionCell {
  return { effective_date: '', distributed: '', recovered: '', cc_no: '' };
}

export function createEmptyReconciliation(): RevReconciliation {
  return { copies_recovered_destroyed: '', date_destroyed: '', destroyed_by: '' };
}

export function createDefaultDepartmentRows(): DepartmentDistributionRow[] {
  return SOD_DEPARTMENTS.map((department) => ({
    department,
    revisions: Array.from({ length: REVISION_COUNT }, () => createEmptyRevCell()),
  }));
}

export function createDefaultReconciliations(): RevReconciliation[] {
  return Array.from({ length: REVISION_COUNT }, () => createEmptyReconciliation());
}

export function createEmptyBiennialRow(): BiennialReviewRow {
  return {
    revision_no: '',
    effective_date: '',
    review_dept: '',
    review_by: '',
    review_by_emp_id: '',
    change_control_no: '',
    comment: '',
    initial_date: '',
  };
}

export function createDefaultBiennialRows(): BiennialReviewRow[] {
  return [createEmptyBiennialRow()];
}

export function defaultFromDate(datePipe: { transform: (v: Date, f: string) => string | null }): string {
  const d = new Date();
  d.setMonth(d.getMonth() - 3);
  return datePipe.transform(d, 'yyyy-MM-dd') || '';
}

export function defaultToDate(datePipe: { transform: (v: Date, f: string) => string | null }): string {
  return datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
}
