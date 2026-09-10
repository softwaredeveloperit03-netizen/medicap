export const FPS_FORM_ID = 'FQC-005-12-A';

export const FPS_FORM_TITLE = 'Finished Products Sampling, Testing and Reporting Results';

export const FPS_API = 'qc/finished_product_sampling.php?';

export const FPS_BATCH_TYPES = [
  { value: 'commercial', label: 'Commercial Batch (§1.3)' },
  { value: 'stability', label: 'Stability Study (§1.4)' }
];

export const FPS_TEST_ROWS = [
  { key: 'description_id', label: 'Description/Identification' },
  { key: 'assay_impurities', label: 'Assay and Organic Impurities' },
  { key: 'uniformity', label: 'Uniformity' },
  { key: 'water_content', label: 'Water Content' },
  { key: 'dissolution', label: 'Dissolution' },
  { key: 'microbial_limits', label: 'Microbial Limits (where applicable)' },
  { key: 'total_qty', label: 'Total Quantity Required for Full testing (excluding microbial limits)' }
];

export const FPS_WORKFLOW_STEPS = [
  { route: 'qc-manager', title: 'QC Manager Approval', description: 'Approve §2.0 sampling quantities (§1.2)', status: 'pending_qc_manager', icon: 'fa-user-tie' },
  { route: 'qa-approval', title: 'QA Approval', description: 'QA approval on sampling plan (§1.1)', status: 'pending_qa_approval', icon: 'fa-user-check' },
  { route: 'production', title: 'Production Sampling', description: '§3.0 quantity sampled by production (§1.3.1)', status: 'pending_production', icon: 'fa-industry' },
  { route: 'qc-receive', title: 'QC Sample Receiving', description: 'Log in QC receiving log, assign lab # (§1.3.2.3)', status: 'pending_qc_receive', icon: 'fa-inbox' },
  { route: 'results', title: 'Test Results Entry', description: 'Record on Test Specification Form (§1.3.3.1)', status: 'pending_results', icon: 'fa-flask' },
  { route: 'coa-approval', title: 'C of A Approval', description: 'QC Management approval — form becomes C of A (§1.3.3.2)', status: 'pending_coa_approval', icon: 'fa-certificate' }
];

export function emptyTestRows(): any[] {
  return FPS_TEST_ROWS.map(r => ({ ...r, required_qty: '', sampled_qty: '' }));
}

export function mergeTestRows(rows: any[]): any[] {
  const base = emptyTestRows();
  if (!Array.isArray(rows)) {
    return base;
  }
  const map: Record<string, any> = {};
  rows.forEach(r => { if (r?.key) map[r.key] = r; });
  return base.map(b => ({
    ...b,
    required_qty: map[b.key]?.required_qty || '',
    sampled_qty: map[b.key]?.sampled_qty || ''
  }));
}

export function emptyResultsData(): Record<string, string> {
  return {
    specification_form_ref: '',
    results_summary: '',
    conforms: '',
    stability_date_of_analysis: ''
  };
}

export function parseFpsResponse(raw: string): any {
  if (!raw || !String(raw).trim()) {
    return { status: 'error', message: 'Empty server response — upload finished_product_sampling.php' };
  }
  try {
    return JSON.parse(raw);
  } catch {
    const text = String(raw).trim();
    if (text.indexOf('<') === 0) {
      return { status: 'error', message: 'Server blocked request or PHP file missing' };
    }
    return { status: 'error', message: text.substring(0, 200) };
  }
}

export function statusLabel(s: string): string {
  return (s || '').replace(/_/g, ' ').toUpperCase();
}
