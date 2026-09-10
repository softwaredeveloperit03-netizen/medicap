import { DatePipe } from '@angular/common';

export const FORM_A_NO = 'FQA-070-A';
export const FORM_B_NO = 'FQA-070-B';
export const SOP_REF = 'SOP-QA-070';
export const REVISION_NO = '00';
export const EFFECTIVE_DATE = '2025-04-02';

export const CAPA_SOURCES = [
  'Investigations / Deviations',
  'Laboratory (OOS) Investigations',
  'Customer Complaints',
  'Product Recall',
  'Quality Management Reviews',
  'Other sources',
];

export const CAPA_CLASSIFICATIONS = ['Critical', 'Major', 'Minor'] as const;

export const EFFECTIVENESS_CHECK_TYPES = [
  'Trend analysis',
  'Review of batch records',
  'Review of validation',
  'Surveillance of supplier',
  'Review of systems',
  'Other',
];

/** Department codes for CAPA-XX-DP-YY numbering (SOP-QA-070 §2.2) */
export const DEPT_CODES: { code: string; name: string }[] = [
  { code: 'QA', name: 'QA & Compliance' },
  { code: 'QC', name: 'Quality Control' },
  { code: 'PR', name: 'Production' },
  { code: 'EM', name: 'Engineering & Maintenance' },
  { code: 'MM', name: 'Materials Management' },
  { code: 'RA', name: 'Regulatory Affairs' },
];

export function defaultFromDate(datePipe: DatePipe): string {
  return datePipe.transform(new Date(), 'yyyy-MM-01') || '';
}

export function defaultToDate(datePipe: DatePipe): string {
  return datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
}

export function getEmpDisplayName(): string {
  return (
    localStorage.getItem('emp_name') ||
    localStorage.getItem('username') ||
    localStorage.getItem('emp_id') ||
    ''
  );
}

export function stampNow(datePipe: DatePipe): string {
  return getEmpDisplayName() + ' - ' + datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm');
}

export function statusClass(status: string): string {
  switch (String(status || '')) {
    case 'pending_dept':
    case 'pending_qa':
    case 'pending_closure':
    case 'pending_mod_dept':
    case 'pending_mod_qa':
    case 'pending_effectiveness':
      return 'label-warning';
    case 'open':
      return 'label-info';
    case 'closed':
    case 'effectiveness_complete':
      return 'label-success';
    case 'rejected':
    case 'cancelled':
      return 'label-danger';
    default:
      return 'label';
  }
}

export function statusLabel(status: string): string {
  const map: Record<string, string> = {
    pending_dept: 'Pending Dept Approval',
    pending_qa: 'Pending QA Approval',
    open: 'Open / In Execution',
    pending_mod_dept: 'Pending Mod (Dept)',
    pending_mod_qa: 'Pending Mod (QA)',
    pending_closure: 'Pending Closure',
    closed: 'Closed',
    pending_effectiveness: 'Pending Effectiveness',
    effectiveness_complete: 'Effectiveness Complete',
    rejected: 'Rejected',
    cancelled: 'Cancelled',
  };
  return map[String(status || '')] || status || '—';
}

export function emptyCapaForm(): any {
  return {
    capa_no: '',
    capa_document_title: '',
    responsible_person: '',
    responsible_position: '',
    initiation_date: '',
    source_reference: '',
    source_type: '',
    classification: '',
    description_of_event: '',
    corrective_action_flag: '',
    preventative_action_flag: '',
    corrective_actions: '',
    corrective_target_date: '',
    corrective_followup: '',
    corrective_effectiveness_required: 'NO',
    corrective_effectiveness_types: [] as string[],
    corrective_batch_interval: '',
    corrective_other_specify: '',
    preventative_actions: '',
    preventative_target_date: '',
    preventative_followup: '',
    preventative_effectiveness_required: 'NO',
    preventative_effectiveness_types: [] as string[],
    preventative_batch_interval: '',
    preventative_other_specify: '',
    dept_code: 'QA',
    product_document_equipment: '',
    owner_department: '',
  };
}
