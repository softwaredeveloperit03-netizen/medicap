export const IPPS_FORM_TITLE = 'In Process Product Sampling, Testing and Reporting';

export const IPPS_API = 'qc/in_process_product_sampling.php?';

export const IPPS_SAMPLE_TYPES = [
  { value: 'in_process', label: 'In-process Product' },
  { value: 'finished', label: 'Finished Product' },
  { value: 'stability', label: 'Stability Product' }
];

export const IPPS_PROVIDE_TO_OPTIONS = [
  'QC Laboratory',
  'QC Lab — Receiving Log',
  'Production',
  'QA'
];

export const IPPS_WORKFLOW_STEPS = [
  { route: 'qc-receive', title: 'QC Sample Receiving', description: 'Verify sample, assign QC lab number (§2.1)', status: 'pending_qc_receive', icon: 'fa-inbox' },
  { route: 'testing', title: 'Sample Testing', description: 'Perform tests per specification (§3)', status: 'pending_testing', icon: 'fa-flask' },
  { route: 'qc-review', title: 'QC Review of Raw Data', description: 'Review notebooks, logbooks & audit trail (§4.1)', status: 'pending_qc_review', icon: 'fa-search' },
  { route: 'analyst-entry', title: 'Analyst Results Entry', description: 'Enter results in test specification form (§4.2)', status: 'pending_analyst_entry', icon: 'fa-edit' },
  { route: 'qc-approval', title: 'QC Management Approval', description: 'Review and approve results (§4.3)', status: 'pending_qc_approval', icon: 'fa-clipboard-check' }
];

export function emptyTestingData(): Record<string, any> {
  return {
    test_method_notes: '',
    blend_bottle_with_sample_g: '',
    blend_empty_bottle_g: '',
    blend_sample_weight_g: '',
    other_tests_notes: ''
  };
}

export function emptyResultsData(): Record<string, any> {
  return {
    specification_form_ref: '',
    results_summary: '',
    conforms: ''
  };
}

export function parseIppsResponse(raw: string): any {
  if (!raw || !String(raw).trim()) {
    return { status: 'error', message: 'Empty server response — upload in_process_product_sampling.php' };
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
