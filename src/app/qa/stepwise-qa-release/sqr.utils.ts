import { DatePipe } from '@angular/common';

export const SOP_REF = 'SOP-QA-035';
export const FORM_B_NO = 'FQA-035-B';
export const FORM_A_NO = 'FQA-035-A';
export const REVISION_NO = '00';
export const EFFECTIVE_DATE = '2025-04-15';

export const PRODUCT_STAGES = ['In-Process', 'Bulk', 'Finished Product'];
export const MANUAL_PRODUCT = '__manual__';

export const STAGE_SECTION_MAP: Record<string, 'in_process' | 'bulk' | 'finished'> = {
  'In-Process': 'in_process',
  Bulk: 'bulk',
  'Finished Product': 'finished',
};

export const SECTION_STAGE_LABEL: Record<string, string> = {
  in_process: 'IN-PROCESS BATCH RECORD REVIEW (Items #1 — 14)',
  bulk: 'BULK PRODUCT BATCH RECORD REVIEW (Items #15 — 17)',
  finished: 'FINISHED PRODUCT BATCH RECORD REVIEW (Items #18 — 19)',
};
export const CHECKLIST_STATUS = ['Ok', 'Not Ok', 'N/A'];
export const YES_NO_NA = ['Yes', 'No', 'N/A'];
export const NCR_STATUS = ['OPEN', 'CLOSED', 'Not Applicable'];
export const OPEN_ITEMS_STATUS = ['OPEN', 'CLOSED', 'Not Applicable'];

export const IN_PROCESS_DISPOSITION = ['Released', 'Rejected'];
export const BULK_DISPOSITION = ['Released', 'Submission Only', 'Commercial Use', 'Rejected'];
export const FINISHED_DISPOSITION = ['Released', 'Submission Only', 'Commercial Use', 'Rejected'];

export interface ChecklistRow {
  item_no: number;
  label: string;
  section: 'in_process' | 'bulk' | 'finished';
  status: string;
  remark: string;
}

export interface InprocessRecordRow {
  stage: string;
  checked_by: string;
  checked_date: string;
}

export const STEPWISE_CHECKLIST: Omit<ChecklistRow, 'status' | 'remark'>[] = [
  { item_no: 1, section: 'in_process', label: 'Batch record contains Production Review Checklist (FPR-007-01-A), Line Clearance (FPR-007-01-B), Material Issuance Forms, clean status tickets' },
  { item_no: 2, section: 'in_process', label: 'Scales/balances used were subject to calibration verification prior to use' },
  { item_no: 3, section: 'in_process', label: 'Calculations recorded for quantity of material(s) required for the batch are accurate' },
  { item_no: 4, section: 'in_process', label: 'Materials used per approved Material Issue Form; dispensing records complete and consistent with master formula' },
  { item_no: 5, section: 'in_process', label: 'Materials issued including in-process material from preceding step were within validity date at start of production' },
  { item_no: 6, section: 'in_process', label: 'In-process checks were as per defined frequency; breaks and stoppages documented' },
  { item_no: 7, section: 'in_process', label: 'In-process analytical test results are attached and have met specifications' },
  { item_no: 8, section: 'in_process', label: 'ID of all non-room specific equipment and instruments used for the batch are recorded' },
  { item_no: 9, section: 'in_process', label: 'Accountability and yield calculations are correct and results met limits' },
  { item_no: 10, section: 'in_process', label: 'Sample record form shows samples obtained as specified' },
  { item_no: 11, section: 'in_process', label: 'All other required forms and worksheets included (FQA-071-A, FQC-017-01-D, FQA-008-A)' },
  { item_no: 12, section: 'in_process', label: 'All applicable SOPs and manufacturing instructions were followed' },
  { item_no: 13, section: 'in_process', label: 'All entries legible; corrections and cancelled entries adequately explained' },
  { item_no: 14, section: 'in_process', label: 'All raw materials are listed and released' },
  { item_no: 15, section: 'bulk', label: 'OOS results obtained during in-process checks handled per SOP-QA-012' },
  { item_no: 16, section: 'bulk', label: 'Analytical test results (CoA) for bulk product attached and met specifications' },
  { item_no: 17, section: 'bulk', label: 'Routine Production Equipment Cleaning Verification results included' },
  { item_no: 18, section: 'finished', label: 'Printed labels were as per approved instructions' },
  { item_no: 19, section: 'finished', label: 'Visual inspection for retain sample conducted for commercial products (FQA-008-C)' },
];

export const INPROCESS_RECORD_STAGES = ['MPR', 'PST', 'R', 'MBR'];

export function defaultFromDate(datePipe: DatePipe): string {
  return datePipe.transform(new Date(), 'yyyy-MM-01') || '';
}

export function defaultToDate(datePipe: DatePipe): string {
  return datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
}

export function getEmpDisplayName(): string {
  return localStorage.getItem('emp_name') || localStorage.getItem('username') || localStorage.getItem('emp_id') || '';
}

export function stampNow(datePipe: DatePipe): string {
  return getEmpDisplayName() + ' - ' + datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm');
}

export function statusClass(status: string): string {
  switch (status) {
    case 'Pending QA Manager':
    case 'Pending Final Release':
      return 'label-warning';
    case 'Approved':
    case 'Released':
      return 'label-success';
    case 'Rejected':
      return 'label-danger';
    case 'Draft':
      return 'label-info';
    default:
      return 'label-info';
  }
}

export function parseProductStages(value: string | null | undefined): string[] {
  if (!value || !String(value).trim()) {
    return ['Finished Product'];
  }
  const parts = String(value)
    .split(',')
    .map((s) => s.trim())
    .filter((s) => PRODUCT_STAGES.includes(s));
  return parts.length ? parts : ['Finished Product'];
}

export function formatProductStages(stages: string[]): string {
  const unique = PRODUCT_STAGES.filter((s) => stages.includes(s));
  return unique.join(', ');
}

export function createDefaultChecklist(stages: string | string[]): ChecklistRow[] {
  const selected = Array.isArray(stages) ? stages : parseProductStages(stages);
  const sections = selected.map((s) => STAGE_SECTION_MAP[s]).filter(Boolean);
  return STEPWISE_CHECKLIST.filter((item) => sections.includes(item.section)).map((item) => ({
    ...item,
    status: '',
    remark: '',
  }));
}

export function mergeChecklistWithStages(existing: ChecklistRow[], stages: string[]): ChecklistRow[] {
  const defaults = createDefaultChecklist(stages);
  const byNo = new Map(existing.map((row) => [row.item_no, row]));
  return defaults.map((row) => {
    const saved = byNo.get(row.item_no);
    return saved ? { ...row, status: saved.status || '', remark: saved.remark || '' } : row;
  });
}

export function parseChecklist(raw: any, stages?: string[]): ChecklistRow[] {
  if (Array.isArray(raw) && raw.length) {
    if (stages?.length) {
      return mergeChecklistWithStages(raw, stages);
    }
    return raw;
  }
  return createDefaultChecklist(stages?.length ? stages : 'Finished Product');
}

export function dispositionOptionsForStages(stages: string[]): string[] {
  const selected = stages.length ? stages : ['Finished Product'];
  if (selected.includes('Finished Product') || selected.includes('Bulk')) {
    return BULK_DISPOSITION;
  }
  return IN_PROCESS_DISPOSITION;
}

/** @deprecated use dispositionOptionsForStages */
export function dispositionOptions(stage: string): string[] {
  return dispositionOptionsForStages(parseProductStages(stage));
}

export function getChecklistSections(
  checklist: ChecklistRow[],
  stages: string[]
): { title: string; key: string; items: ChecklistRow[] }[] {
  const order: ('in_process' | 'bulk' | 'finished')[] = ['in_process', 'bulk', 'finished'];
  return PRODUCT_STAGES.filter((s) => stages.includes(s))
    .map((s) => STAGE_SECTION_MAP[s])
    .filter((key, idx, arr) => key && arr.indexOf(key) === idx)
    .sort((a, b) => order.indexOf(a) - order.indexOf(b))
    .map((key) => ({
      key,
      title: SECTION_STAGE_LABEL[key],
      items: checklist.filter((c) => c.section === key),
    }));
}

export function createDefaultInprocessRecords(): InprocessRecordRow[] {
  return INPROCESS_RECORD_STAGES.map((stage) => ({ stage, checked_by: '', checked_date: '' }));
}

export function parseInprocessRecords(raw: any): InprocessRecordRow[] {
  if (Array.isArray(raw) && raw.length) {
    return raw;
  }
  return createDefaultInprocessRecords();
}
