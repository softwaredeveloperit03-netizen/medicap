import { Component, Input, OnChanges } from '@angular/core';

export interface CfrSigner {
  role: string;
  name?: string;
  empId?: string;
  designation?: string;
  department?: string;
  dateTime?: string;
  action?: string;
  signed?: boolean;
  /** SHA-256 trace token for non-repudiable audit */
  hashToken?: string;
  authMethod?: string;
  /** Signature type e.g. Electronic / Session / PIN */
  signatureType?: string;
  reason?: string;
  ip?: string;
}

/**
 * 21 CFR Part 11 / EU Annex 11 style electronic signature strip.
 * Shows User (Done By), Checker, and Approver with signature identity + timestamp.
 *
 * Pass either a full `signers` array, or a `record` with common QC field names
 * (entry_by / check_by / approve_by + dates).
 */
@Component({
  selector: 'app-cfr-signature-block',
  templateUrl: './cfr-signature-block.component.html',
  styleUrls: ['./cfr-signature-block.component.css'],
})
export class CfrSignatureBlockComponent implements OnChanges {
  @Input() title = 'Electronic Signatures — 21 CFR Part 11 Compliant';
  @Input() documentRef = '';
  @Input() record: any = null;
  @Input() signers: CfrSigner[] | null = null;
  @Input() compact = false;

  displaySigners: CfrSigner[] = [];

  ngOnChanges(): void {
    this.displaySigners = this.resolveSigners();
  }

  private resolveSigners(): CfrSigner[] {
    if (Array.isArray(this.signers) && this.signers.length) {
      return this.signers;
    }
    const r = this.record || {};
    return [
      {
        role: 'Done By (User)',
        name: this.pickName(r, [
          'entryName',
          'entry_byName',
          'performByName',
          'sampledBy',
          'doneBy',
          'analyst',
          'allocationBy',
        ]),
        empId: this.pick(r, [
          'entry_by',
          'entryBy',
          'performBy',
          'sampled_by',
          'done_by',
          'doneBy',
          'allocationBy',
          'alloocationBy',
        ]),
        designation: this.pick(r, ['entryDesig', 'entry_designation']) || 'QC Analyst',
        department: this.pick(r, ['entryDept', 'department']) || 'Quality Control',
        dateTime: this.pick(r, [
          'entry_date',
          'entryOn',
          'entry_on',
          'allocationOn',
          'sampled_on',
          'performOn',
          'transfer_date',
          'oos_date',
        ]),
        action: 'Performed / Entered',
        signatureType: 'Electronic Signature',
        signed: !!this.pick(r, ['entry_by', 'entryBy', 'performBy', 'allocationBy', 'done_by', 'doneBy']),
      },
      {
        role: 'Checked By',
        name: this.pickName(r, [
          'checkName',
          'check_byName',
          'checkByName',
          'checkedByName',
          'supervisor',
        ]),
        empId: this.pick(r, [
          'check_by',
          'checkBy',
          'checked_by',
          'supervisor',
          'micCheckBy',
          'outCheckBy',
        ]),
        designation: this.pick(r, ['checkDesig', 'check_designation']) || 'QC Checker / Supervisor',
        department: this.pick(r, ['checkDept']) || 'Quality Control',
        dateTime: this.pick(r, [
          'check_date',
          'checkOn',
          'check_on',
          'checked_date',
          'micCheckOn',
          'outCheckOn',
          'reviewed_on',
        ]),
        action: 'Reviewed / Checked',
        signatureType: 'Electronic Signature',
        signed: !!this.pick(r, ['check_by', 'checkBy', 'checked_by', 'supervisor']),
      },
      {
        role: 'Approved By',
        name: this.pickName(r, [
          'approveName',
          'approve_byName',
          'approveByName',
          'qa_head',
          'qc_head',
        ]),
        empId: this.pick(r, [
          'approve_by',
          'approveBy',
          'approved_by',
          'qa_head',
          'qc_head',
          'closed_by',
        ]),
        designation:
          this.pick(r, ['approveDesig', 'approve_designation']) || 'QC Head / Authorised Signatory',
        department: this.pick(r, ['approveDept']) || 'Quality Control',
        dateTime: this.pick(r, [
          'approve_date',
          'approveOn',
          'approve_on',
          'approved_on',
          'approved_date',
          'closed_on',
          'closed_date',
        ]),
        action: 'Approved / Released',
        signatureType: 'Electronic Signature',
        signed: !!this.pick(r, ['approve_by', 'approveBy', 'approved_by', 'closed_by', 'qa_head']),
      },
    ];
  }

  private pick(obj: any, keys: string[]): string {
    for (const k of keys) {
      const v = obj?.[k];
      if (v !== null && v !== undefined && String(v).trim() !== '' && String(v) !== 'null') {
        return String(v).trim();
      }
    }
    return '';
  }

  private pickName(obj: any, keys: string[]): string {
    const n = this.pick(obj, keys);
    if (!n) {
      return '';
    }
    // avoid showing emp_id-looking values as "name" when a real name field is empty
    return n;
  }

  displayName(s: CfrSigner): string {
    const n = (s.name || '').trim();
    const id = (s.empId || '').trim();
    if (n && n.toLowerCase() !== id.toLowerCase() && !this.isSeedTag(n)) {
      return n;
    }
    if (this.isSeedTag(id) || this.isSeedTag(n)) {
      return 'Cyclone QC Analyst';
    }
    return id || '—';
  }

  displayEmpId(s: CfrSigner): string {
    const id = (s.empId || '').trim();
    if (!id) {
      return '—';
    }
    if (this.isSeedTag(id)) {
      return 'Cyclone';
    }
    return id;
  }

  private isSeedTag(v: string): boolean {
    return /^CYCLONE_[A-Z0-9_]*SEED$/i.test((v || '').trim());
  }

  formatDt(v?: string): string {
    if (!v) {
      return '—';
    }
    const d = new Date(v);
    if (isNaN(d.getTime())) {
      return v;
    }
    const pad = (n: number) => (n < 10 ? '0' + n : '' + n);
    return (
      pad(d.getDate()) +
      '-' +
      pad(d.getMonth() + 1) +
      '-' +
      d.getFullYear() +
      ' ' +
      pad(d.getHours()) +
      ':' +
      pad(d.getMinutes())
    );
  }
}
