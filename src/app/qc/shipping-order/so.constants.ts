export const SO_FORM_ID = 'FQC-005-09-A';

export const SO_API = 'qc/shipping_order.php?';

export const MEDICAP_FROM = {
  company: 'Medicap Laboratories',
  line1: '30 Worcester Rd.',
  line2: 'Toronto, Ontario, M9W 5X2'
};

export const SO_STATUS_LABELS: Record<string, string> = {
  draft: 'Draft',
  pending_check: 'Pending Check',
  checked: 'Checked',
  shipped: 'Shipped',
  pending_results_review: 'Pending QC Results Review',
  closed: 'Closed'
};

export function emptySampleLine(): Record<string, string> {
  return { lab_sample_no: '', quantity: '', lot_batch_no: '', description: '' };
}

export function defaultSampleLines(count = 4): Record<string, string>[] {
  return Array.from({ length: count }, () => emptySampleLine());
}

export function parseSoResponse(raw: string): any {
  if (!raw || !String(raw).trim()) {
    return { status: 'error', message: 'Empty server response — upload shipping_order.php to server' };
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
