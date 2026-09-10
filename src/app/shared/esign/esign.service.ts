import { Injectable } from '@angular/core';
import { DataAccessService } from '../../data-access.service';

export interface EsignRequest {
  /** CFR meaning of the signature */
  meaning: 'Prepared By' | 'Checked By' | 'Approved By' | 'Performed By' | 'Reviewed By' | string;
  /** Optional dialog title override */
  title?: string;
  /** Logical module for the audit manifest, e.g. 'master:inprocess_check' or 'execution' */
  module: string;
  /** Record reference (id / batch id / batch no) */
  recordRef?: string | number;
  /** Human detail, e.g. step / activity name */
  detail?: string;
  /** Require a free-text reason (recommended for changes / approvals) */
  requireReason?: boolean;
  reasonLabel?: string;
  confirmLabel?: string;
  /** Override verify endpoint (default: master/ebmr_bpr.php?type=verifyEsign) */
  verifyUrl?: string;
}

export interface EsignResult {
  emp_id: string;
  emp_name: string;
  designation?: string;
  department?: string;
  signed_at: string;
  meaning: string;
  reason?: string;
  /** Human-readable auth method for audit trail */
  auth_method?: string;
  /** 'password' | 'pin' */
  auth_type?: string;
  signature_token?: string;
  esign_id?: number;
}

@Injectable({ providedIn: 'root' })
export class EsignService {
  visible = false;
  req: EsignRequest | null = null;
  password = '';
  reason = '';
  error = '';
  busy = false;

  private resolver: (r: EsignResult | null) => void = () => {};

  constructor(private svc: DataAccessService) {}

  /** Open the compact e-sign window; resolves with signer info, or null if cancelled. */
  request(req: EsignRequest): Promise<EsignResult | null> {
    this.req = req;
    this.password = '';
    this.reason = '';
    this.error = '';
    this.busy = false;
    this.visible = true;
    return new Promise<EsignResult | null>((resolve) => (this.resolver = resolve));
  }

  empId(): string {
    return localStorage.getItem('emp_id') || '';
  }
  empName(): string {
    return localStorage.getItem('username') || this.empId();
  }

  cancel(): void {
    this.visible = false;
    const r = this.resolver;
    this.resolver = () => {};
    r(null);
  }

  confirm(): void {
    if (this.busy) return;
    if (!this.password) {
      this.error = 'Enter your password or authorization PIN to sign.';
      return;
    }
    const pinLike = /^\d+$/.test(this.password.trim());
    if (pinLike && !/^\d{4}$/.test(this.password.trim())) {
      this.error = 'Authorization PIN must be exactly 4 digits.';
      return;
    }
    if (this.req?.requireReason && !this.reason.trim()) {
      this.error = 'A reason / remark is required for this signature.';
      return;
    }
    this.busy = true;
    this.error = '';
    const body = {
      emp_id: this.empId(),
      password: this.password,
      meaning: this.req?.meaning || '',
      module: this.req?.module || '',
      record_ref: String(this.req?.recordRef ?? ''),
      detail: this.req?.detail || '',
      reason: this.reason || '',
    };
    const verifyUrl = this.req?.verifyUrl || 'master/ebmr_bpr.php?type=verifyEsign';
    this.svc.post(verifyUrl, JSON.stringify(body)).subscribe({
      next: (r: any) => {
        this.busy = false;
        if (r && r.status === 'success') {
          const out: EsignResult = {
            emp_id: r.emp_id,
            emp_name: r.emp_name,
            designation: r.designation || localStorage.getItem('designation') || '',
            department: r.department || localStorage.getItem('department') || '',
            signed_at: r.signed_at,
            meaning: r.meaning,
            reason: this.reason,
            auth_method: r.auth_method,
            auth_type: r.auth_type,
            signature_token: r.signature_token,
            esign_id: r.esign_id,
          };
          this.visible = false;
          const res = this.resolver;
          this.resolver = () => {};
          res(out);
        } else {
          this.error = (r && r.message) || 'Signature rejected.';
        }
      },
      error: () => {
        this.busy = false;
        this.error = 'Server error while verifying signature. Try again.';
      },
    });
  }
}
