import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { Observable } from 'rxjs';

export const SOP_REF = 'SOP-QA-042';
export const FORM_ANNOUNCEMENT = 'FQA-042-A';
export const FORM_MOM = 'FQA-042-A';
export const FORM_ASSESSMENT = 'FQA-042-B';
export const API = 'qa/managementReviewQualitySystems.php';

export const REVIEW_PERIODS = ['Quarterly', 'Half-Yearly', 'Annual', 'Ad-hoc'];

export const ASSESSMENT_INDICATORS = [
  'Deviation',
  'Corrective and Preventive Action (CAPA)',
  'Out of Specification (OOS)',
  'Change Control',
  'Rejection of Material',
  'Out of Trend (OOT)',
  'Market Complaints',
  'Product Quality Review',
  'Training',
  'Product Recall / Withdrawal',
  'Qualification / Requalification',
  'Vendor',
  'Risk Management',
  'Validation',
  'Regulatory Inspections',
  'Self-inspection / Internal Audit',
  'Environmental Monitoring',
  'Calibration',
];

export const INDICATOR_STATUSES = ['Satisfactory', 'Needs Improvement', 'Unsatisfactory', 'Not Applicable'];

export function buildEmptyIndicators(): any[] {
  return ASSESSMENT_INDICATORS.map((name) => ({
    name,
    status: '',
    count: '',
    summary: '',
    remarks: '',
  }));
}

export const AGENDA_OPTIONS = [
  'Previous Management Review Action Items',
  'Quality Policy & Objectives',
  'Audit Results (Internal/External/Regulatory)',
  'Customer Feedback & Market Complaints',
  'Process Performance & Product Quality Monitoring',
  'Deviations & OOS/OOT Trends',
  'CAPA Status Review',
  'Change Control Status Review',
  'Incidents & Risk Assessment',
  'Training & Competency Assessment',
  'Supplier/Vendor Performance',
  'Regulatory Updates',
  'Recommendations for Improvement',
  'Resource Requirements',
  'Other',
];

export function defaultFromDate(datePipe: DatePipe): string {
  const d = new Date();
  d.setMonth(d.getMonth() - 3);
  return datePipe.transform(d, 'yyyy-MM-dd') || '';
}

export function defaultToDate(datePipe: DatePipe): string {
  return datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
}

export function stampNow(datePipe: DatePipe): string {
  const empId = localStorage.getItem('emp_id') || '';
  const name = localStorage.getItem('username') || localStorage.getItem('firstname') || empId;
  const dt = datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm') || '';
  return `${name} (${empId}) - ${dt}`;
}

export function hasStamp(value: string | null | undefined): boolean {
  return !!(value && String(value).trim());
}

export function loadManagers(service: DataAccessService): Observable<any> {
  return service.get(`${API}?type=getManagers`);
}

export function participantLabel(p: any): string {
  if (!p) return '';
  const name = [p.firstname, p.middlename, p.lastname].filter(Boolean).join(' ').trim()
    || p.emp_name
    || p.emp_id
    || '';
  const dept = p.department ? `${p.department} - ` : '';
  const code = p.emp_id ? ` (${p.emp_id})` : '';
  return `${dept}${name}${code}`.trim();
}
