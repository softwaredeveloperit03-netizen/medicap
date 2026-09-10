import { DatePipe } from '@angular/common';

export const SOP_REF = 'SOP-QA-027';
export const FORM_INHOUSE_NO = 'APQR Template';
export const FORM_THIRD_PARTY_NO = 'FQA-027-A';
export const REVISION_NO = '00';
export const EFFECTIVE_DATE = '2025-04-09';
export const MANUAL_PRODUCT = '__manual__';

export const OVERALL_RATING_OPTIONS = ['Acceptable', 'Acceptable with conditions'];
export const THIRD_PARTY_DISPOSITION = ['Acceptable', 'Additional Information Required'];
export const QA_AGREEMENT_STATUS = ['Up to date', 'Revision required'];

export const APQR_SECTIONS = [
  { key: 'executive_summary', label: 'Executive Summary' },
  { key: 'introduction', label: 'Introduction' },
  { key: 'manufacturing', label: 'Master Processing Evaluation' },
  { key: 'packaging', label: 'Packaging Summary' },
  { key: 'equipment', label: 'Equipment and System Qualification' },
  { key: 'complaints', label: 'Product Complaints' },
  { key: 'investigations', label: 'Investigations (NCR / LIR)' },
  { key: 'recall', label: 'Product Recall' },
  { key: 'returns', label: 'Returned or Salvaged Products' },
  { key: 'change_control', label: 'Change Control' },
  { key: 'testing', label: 'Testing Commitments' },
  { key: 'stability', label: 'Stability Data' },
  { key: 'validation', label: 'Validation' },
  { key: 'agreements', label: 'Quality Agreements' },
  { key: 'retained_samples', label: 'Retained Samples' },
  { key: 'recommendations', label: 'Recommendations' },
];

export interface ManufacturingBatchRow {
  batch_no: string;
  mfg_date: string;
  exp_date: string;
  batch_size: string;
  qty_produced: string;
  yield_percent: string;
  status: string;
  remarks: string;
}

export interface InvestigationRow {
  ref_no: string;
  lot_numbers: string;
  investigation: string;
  root_cause: string;
  capa: string;
  status: string;
}

export interface RecallRow {
  classification: string;
  lot_numbers: string;
  reason: string;
  units_distributed: string;
  percent_returned: string;
  disposition: string;
  ncr_ref: string;
}

export interface ReturnRow {
  return_no: string;
  lot_number: string;
  investigation: string;
  result: string;
  disposition: string;
}

export interface ChangeControlRow {
  ctrl_no: string;
  change_title: string;
  change_related: string;
  status: string;
  remarks: string;
}

export interface ApqrReportData {
  executive_summary: string;
  introduction: string;
  manufacturing_history_notes: string;
  master_formula_review: string;
  manufacturing_batches: ManufacturingBatchRow[];
  manufacturing_yield_notes: string;
  packaging_components: string;
  master_packaging_review: string;
  packaging_summary: string;
  equipment_qualification: string;
  quality_complaints_summary: string;
  adverse_drug_events: string;
  complaint_trends: string;
  investigations: InvestigationRow[];
  investigation_effectiveness: string;
  recalls: RecallRow[];
  returns: ReturnRow[];
  change_controls: ChangeControlRow[];
  inprocess_physical_testing: string;
  inprocess_chemical_testing: string;
  testing_statistical_analysis: string;
  stability_summary: string;
  validation_summary: string;
  agreements_review: string;
  retained_samples_evaluation: string;
  previous_report_actions: string;
  overall_rating: string;
  recommendations: string;
  capa_completion_dates: string;
}

export function defaultFromDate(datePipe: DatePipe): string {
  const d = new Date();
  d.setFullYear(d.getFullYear() - 1);
  return datePipe.transform(d, 'yyyy-MM-dd') || '';
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
  switch (status) {
    case 'Draft':
      return 'label-info';
    case 'Pending Review':
      return 'label-warning';
    case 'Approved':
    case 'Completed':
    case 'Acceptable':
      return 'label-success';
    case 'Additional Information Required':
      return 'label-danger';
    default:
      return 'label-info';
  }
}

export function createEmptyBatchRow(): ManufacturingBatchRow {
  return {
    batch_no: '',
    mfg_date: '',
    exp_date: '',
    batch_size: '',
    qty_produced: '',
    yield_percent: '',
    status: '',
    remarks: '',
  };
}

export function createEmptyInvestigationRow(): InvestigationRow {
  return { ref_no: '', lot_numbers: '', investigation: '', root_cause: '', capa: '', status: '' };
}

export function createEmptyRecallRow(): RecallRow {
  return {
    classification: '',
    lot_numbers: '',
    reason: '',
    units_distributed: '',
    percent_returned: '',
    disposition: '',
    ncr_ref: '',
  };
}

export function createEmptyReturnRow(): ReturnRow {
  return { return_no: '', lot_number: '', investigation: '', result: '', disposition: '' };
}

export function createEmptyChangeControlRow(): ChangeControlRow {
  return { ctrl_no: '', change_title: '', change_related: '', status: '', remarks: '' };
}

export function createDefaultReportData(): ApqrReportData {
  return {
    executive_summary: '',
    introduction: '',
    manufacturing_history_notes: '',
    master_formula_review: '',
    manufacturing_batches: [createEmptyBatchRow()],
    manufacturing_yield_notes: '',
    packaging_components: '',
    master_packaging_review: '',
    packaging_summary: '',
    equipment_qualification: '',
    quality_complaints_summary: '',
    adverse_drug_events: '',
    complaint_trends: '',
    investigations: [createEmptyInvestigationRow()],
    investigation_effectiveness: '',
    recalls: [createEmptyRecallRow()],
    returns: [createEmptyReturnRow()],
    change_controls: [createEmptyChangeControlRow()],
    inprocess_physical_testing: '',
    inprocess_chemical_testing: '',
    testing_statistical_analysis: '',
    stability_summary: '',
    validation_summary: '',
    agreements_review: '',
    retained_samples_evaluation: '',
    previous_report_actions: '',
    overall_rating: '',
    recommendations: '',
    capa_completion_dates: '',
  };
}

export function parseReportData(raw: any): ApqrReportData {
  const defaults = createDefaultReportData();
  if (!raw || typeof raw !== 'object') {
    return defaults;
  }
  return {
    ...defaults,
    ...raw,
    manufacturing_batches: Array.isArray(raw.manufacturing_batches) && raw.manufacturing_batches.length
      ? raw.manufacturing_batches
      : defaults.manufacturing_batches,
    investigations: Array.isArray(raw.investigations) && raw.investigations.length
      ? raw.investigations
      : defaults.investigations,
    recalls: Array.isArray(raw.recalls) && raw.recalls.length ? raw.recalls : defaults.recalls,
    returns: Array.isArray(raw.returns) && raw.returns.length ? raw.returns : defaults.returns,
    change_controls: Array.isArray(raw.change_controls) && raw.change_controls.length
      ? raw.change_controls
      : defaults.change_controls,
  };
}
