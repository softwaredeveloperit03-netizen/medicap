export const PLS_FORM_ID = 'FQC-005-08-A';

export const PLS_API = 'qc/processing_laboratory_samples.php?';

/** Parse PLS API text response (handles PHP warnings / WAF HTML). */
export function parsePlsResponse(raw: string): any {
  if (!raw || !String(raw).trim()) {
    return { status: 'error', message: 'Empty server response — upload processing_laboratory_samples.php to server' };
  }
  try {
    return JSON.parse(raw);
  } catch {
    const text = String(raw).trim();
    if (text.indexOf('<') === 0) {
      return { status: 'error', message: 'Server blocked request (WAF) or PHP file missing on server' };
    }
    return { status: 'error', message: text.substring(0, 200) };
  }
}

export const PLS_SAMPLE_CATEGORIES = [
  'Raw Material - Excipient',
  'Raw Material - API',
  'Raw Material - Solvent',
  'Product Development (PD) including controlled substance',
  'In-process',
  'Finished Drug Product (Bio-batches and Commercial batches)',
  'Stability',
  'Validation Samples',
  'Secondary Standards',
  'Packaging Components',
  'Contract Laboratory or Service Requestor Sample'
];

export const PLS_SAMPLE_TYPE_LABELS = [
  'MEDICAP LABORATORIES',
  'Retain',
  'Contract Lab',
  'Microbial'
];

export const PLS_LAB_NUMBER_SCHEMES = [
  { value: 'qc', label: 'QC-YY-####.## (General QC)' },
  { value: 'qc_con', label: 'QC-CON-YY-####.## (Contract / Service Requestor)' },
  { value: 'lab', label: 'LAB-YY-####.## (PD / Non-GMP)' }
];

export const PLS_SECTION_A_ROWS = [
  { key: 'medicap_chemical', label: 'Chemical testing at Medicap Laboratories' },
  { key: 'contract_chemical', label: 'Chemical testing at Contract Lab' },
  { key: 'contract_micro', label: 'Microbiological testing at Contract Lab' },
  { key: 'retain', label: 'Retain sample' }
];

export const PLS_INSPECTION_ROWS = [
  { key: 'conform_description', label: 'Conform to description' },
  { key: 'absence_foreign_matter', label: 'Absence of foreign matter' }
];

export const PLS_WORKFLOW_STEPS = [
  { route: 'section-a', title: 'Section A — Sampling Instructions', description: 'QC Manager / designee (WI-QC-005-02)', status: 'pending_section_a', icon: 'fa-clipboard-list' },
  { route: 'section-b', title: 'Section B — Sampling & Inspection', description: 'Sampler — sampling and material inspection', status: 'pending_section_b', icon: 'fa-vial' },
  { route: 'lab-receive', title: 'Lab Sample Receiving', description: 'Lab designee — assign LAB # and log in receiving logbook', status: 'pending_lab_receive', icon: 'fa-inbox' },
  { route: 'section-c', title: 'Section C — QC Review', description: 'QC Manager — test review, CoA, release', status: 'pending_section_c', icon: 'fa-clipboard-check' }
];

export function emptySectionA(): Record<string, any> {
  const rows: Record<string, any> = {};
  PLS_SECTION_A_ROWS.forEach(r => {
    rows[r.key] = { full_testing_g: '', reduced_testing_g: '', comments: '' };
  });
  return rows;
}

export function emptySectionB(): Record<string, any> {
  const inspections: Record<string, any> = {};
  PLS_INSPECTION_ROWS.forEach(r => {
    inspections[r.key] = { answer: '', comment: '' };
  });
  return {
    inspections,
    sampled_by: '',
    sampled_date: '',
    retain_stored_by: '',
    retain_stored_date: '',
    storage_crt: false,
    storage_other: false,
    storage_other_text: ''
  };
}

export function emptySectionC(): Record<string, any> {
  return {
    test_review: '',
    coa_conforms: '',
    contract_lab_attached: '',
    qc_released_by: '',
    qc_released_date: '',
    assigned_retest_expiry_date: ''
  };
}

export function emptyRetestColumn(): Record<string, any> {
  return {
    fmm_attached: '',
    retest_date: '',
    remaining_drums: '',
    medicap_composite_g: '',
    id_test_g: '',
    contract_chemical_g: '',
    contract_micro_g: '',
    retain_g: '',
    sampled_by: '',
    sampled_date: '',
    test_review: '',
    coa_conforms: '',
    contract_lab_conforms: '',
    assigned_retest_expiry: '',
    released_to_qa_by: '',
    released_to_qa_date: '',
    comments: ''
  };
}

export function emptySectionD(): Record<string, any>[] {
  return [emptyRetestColumn()];
}
