export const ATR_SAMPLE_CATEGORIES = [
  'API', 'Excipient', 'In-process Product', 'Bulk Product',
  'Semi - Finished Product', 'Cleaning Verification', 'Finished Product', 'Stability Samples',
  'Pilot Bio batch', 'Innovator', 'Validation', 'Complaint Samples',
  'Water Sample', 'Retest', 'Other'
];

export const ATR_PRODUCTION_CATEGORIES = [
  'In-process Product', 'Bulk Product', 'Semi - Finished Product', 'Finished Product', 'Pilot Bio batch'
];

export const ATR_TEST_OPTIONS = [
  { key: 'description', label: 'Description' },
  { key: 'impurities', label: 'Impurities' },
  { key: 'blend_uniformity', label: 'Blend uniformity' },
  { key: 'potency', label: 'Potency (Target Potency)', hasText: true, textKey: 'potency_target' },
  { key: 'assay', label: 'Assay' },
  { key: 'id_test', label: 'ID', hasText: true, textKey: 'id_detail' },
  { key: 'content_uniformity', label: 'Content Uniformity' },
  { key: 'ph', label: 'pH' },
  { key: 'residual_solvent', label: 'Residual Solvent', hasText: true, textKey: 'residual_solvent_detail' },
  { key: 'moisture', label: 'Moisture(KF/LOD)' },
  { key: 'particle_size', label: 'Particle Size' },
  { key: 'residue_on_ignition', label: 'Residue on Ignition' },
  { key: 'heavy_metal', label: 'Heavy Metal' },
  { key: 'dsc', label: 'DSC' },
  { key: 'tlc', label: 'TLC' },
  { key: 'micro', label: 'Micro' },
  { key: 'toc', label: 'TOC' },
  { key: 'dissolution', label: 'Dissolution Media', hasText: true, textKey: 'dissolution_media' },
  { key: 'other_test', label: 'Other', hasText: true, textKey: 'other_test_detail' },
];

export const ATR_BINDER_OPTIONS = [
  'Dissolution', 'Finished Product', 'In-Process', 'Water testing Binder',
  'Raw Material', 'Residual Solvent', 'Stability', 'Contract Lab', 'Others'
];

export const ATR_DEPT_FORWARD_OPTIONS = [
  'QA', 'RA', 'Production', 'Product Development', 'Quality and Compliance', 'Others'
];

export const ATR_WORKFLOW_STEPS = [
  { route: 'qa-verify', title: 'QA Verification', description: 'QA & Compliance verify Section A (Production samples)', status: 'pending_qa_verify', icon: 'fa-user-check' },
  { route: 'production-verify', title: 'Production Verification', description: 'Production second-person sample verification', status: 'pending_production_verify', icon: 'fa-industry' },
  { route: 'lab-receive', title: 'Lab Sample Receiving', description: 'Log sample in QC receiving logbook', status: 'pending_lab_receive', icon: 'fa-inbox' },
  { route: 'analyst', title: 'Laboratory Analyst', description: 'Validate Section A and specify tests', status: 'pending_analyst', icon: 'fa-flask' },
  { route: 'alt-method-qa', title: 'Alternative Method QA Approval', description: 'QA approval for alternative test procedure', status: 'pending_alt_method_qa', icon: 'fa-exclamation-triangle' },
  { route: 'lab-manager', title: 'Lab Manager Review', description: 'Review and approve Section B', status: 'pending_lab_manager', icon: 'fa-user-tie' },
  { route: 'qa-disposition', title: 'QA Final Disposition', description: 'QA Manager final sample disposition', status: 'pending_qa_disposition', icon: 'fa-clipboard-check' },
];

export function emptyTestsRequested(): Record<string, any> {
  const tests: Record<string, any> = { has_approved_specs: false };
  ATR_TEST_OPTIONS.forEach(t => {
    tests[t.key] = false;
    if (t.hasText && t.textKey) {
      tests[t.textKey] = '';
    }
  });
  return tests;
}

export function emptyAnalystTests(): Record<string, any> {
  const tests: Record<string, any> = {};
  ATR_TEST_OPTIONS.forEach(t => {
    tests[t.key] = { selected: false, initials: '' };
  });
  return tests;
}
