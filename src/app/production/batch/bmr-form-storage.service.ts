import { Injectable } from '@angular/core';
import { Observable, of } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
import {
  buildEbmrPayloadView,
  payloadViewToPrintHtml,
} from './ebmr-payload-view';

const EBMR_FILLED_API = 'bmr/ebmr_filled_step_api.php';

export type BmrApprovalStatus = 'pending' | 'approved';

export interface BmrStepSnapshot {
  stepId: string;
  stepName?: string;
  data: unknown;
  savedAt: string;
}

export interface BmrSubstepSnapshot {
  stepId: string;
  subId: string;
  data: unknown;
  savedAt: string;
}

export interface BmrLocalSnapshot {
  product_code: string;
  work_order_no: string;
  batch_number: string;
  product_name?: string;
  steps: Record<string, BmrStepSnapshot>;
  substeps: Record<string, BmrSubstepSnapshot>;
  approvalStatus: BmrApprovalStatus;
  approvedAt?: string;
  /** Employee id who approved from eBMR checking (server: ebmr_filled_batch_approval.approved_by_emp_id). */
  approvedByEmpId?: string;
  /** Last Proceed save for this batch (server: ebmr_filled_step by updated_at). */
  lastStepEmpId?: string;
  lastStepSavedAt?: string;
  updatedAt: string;
}

/** BMR master order for print (same shape as eBMR checking screen). */
export interface BmrPrintOrderedSubstepBlock {
  subOrdinal: number;
  subId: string;
  subName: string;
  saved: BmrSubstepSnapshot;
}

export interface BmrPrintOrderedStepBlock {
  stepOrdinal: number;
  stepId: string;
  stepName: string;
  stepSaved: BmrStepSnapshot | null;
  substeps: BmrPrintOrderedSubstepBlock[];
}

export interface BmrPrintOrderedStageSection {
  stageOrdinal: number;
  stageTitle: string;
  steps: BmrPrintOrderedStepBlock[];
}

export interface BmrPrintOptions {
  orderedSections?: BmrPrintOrderedStageSection[] | null;
  orphanSteps?: BmrStepSnapshot[] | null;
  orphanSubsteps?: BmrSubstepSnapshot[] | null;
  /** Product cell: name + grade (same as awaiting check screen). */
  coverProductLine?: string | null;
}

const PREFIX = 'bmr_local_';

@Injectable({
  providedIn: 'root',
})
export class BmrFormStorageService {
  constructor(private dataAccess: DataAccessService) {}

  /**
   * `bmr/process.php` save actions often return plain `1` / `"1"` instead of `{ status: 'success' }`.
   * Use for every POST to that script so success paths (alerts, reload, ebmr mirror) still run.
   */
  isProcessPhpSaveSuccess(response: any): boolean {
    if (response == null || response === false) {
      return false;
    }
    const st = response['status'];
    if (st === 'success' || st == 'success') {
      return true;
    }
    if (response === 1 || response === true) {
      return true;
    }
    if (typeof response === 'string') {
      const t = response.replace(/^\uFEFF/, '').trim();
      if (t === '1' || t.toLowerCase() === 'success') {
        return true;
      }
    }
    if (typeof response === 'object' && response !== null) {
      const anyr = response as Record<string, unknown>;
      if (anyr['success'] === true || anyr['success'] === 1 || anyr['success'] === '1') {
        return true;
      }
    }
    return false;
  }

  /** After postTextResponse: trim BOM, try JSON.parse, else use last line (PHP notices before `1`) */
  parsePhpLooseResponse(body: string | null | undefined): any {
    if (body == null) {
      return null;
    }
    const s = String(body).replace(/^\uFEFF/g, '').trim();
    if (!s) {
      return null;
    }
    try {
      return JSON.parse(s);
    } catch {
      const lines = s
        .split(/\r?\n/)
        .map((l) => l.trim())
        .filter((l) => l.length > 0);
      const last = lines.length ? lines[lines.length - 1] : s;
      if (/^(1|success|ok)$/i.test(last)) {
        return last === '1' ? 1 : last;
      }
      return s;
    }
  }

  storageKey(
    product_code: string,
    work_order_no: string,
    batch_number: string
  ): string {
    return (
      PREFIX +
      encodeURIComponent(String(product_code)) +
      '_' +
      encodeURIComponent(String(work_order_no)) +
      '_' +
      encodeURIComponent(String(batch_number))
    );
  }

  getSnapshot(
    product_code: string,
    work_order_no: string,
    batch_number: string
  ): BmrLocalSnapshot | null {
    try {
      const raw = localStorage.getItem(
        this.storageKey(product_code, work_order_no, batch_number)
      );
      if (!raw) {
        return null;
      }
      return JSON.parse(raw) as BmrLocalSnapshot;
    } catch {
      return null;
    }
  }

  private saveSnapshot(snap: BmrLocalSnapshot): void {
    snap.updatedAt = new Date().toISOString();
    localStorage.setItem(
      this.storageKey(snap.product_code, snap.work_order_no, snap.batch_number),
      JSON.stringify(snap)
    );
  }

  /**
   * Browser localStorage only (no HTTP). Server save uses {@link saveFillStep}.
   */
  mergeStep(
    product_code: string,
    work_order_no: string,
    batch_number: string,
    stepId: string | number,
    payload: { data: unknown; stepName?: string },
    product_name?: string
  ): void {
    const id = String(stepId);
    try {
      let snap = this.getSnapshot(product_code, work_order_no, batch_number);
      if (!snap) {
        snap = {
          product_code: String(product_code),
          work_order_no: String(work_order_no),
          batch_number: String(batch_number),
          product_name,
          steps: {},
          substeps: {},
          approvalStatus: 'pending',
          updatedAt: new Date().toISOString(),
        };
      }
      if (product_name) {
        snap.product_name = product_name;
      }
      snap.steps[id] = {
        stepId: id,
        stepName: payload.stepName,
        data: this.clone(payload.data),
        savedAt: new Date().toISOString(),
      };
      if (snap.approvalStatus === 'approved') {
        snap.approvalStatus = 'pending';
        delete snap.approvedAt;
        delete snap.approvedByEmpId;
      }
      this.saveSnapshot(snap);
    } catch (e) {
      console.error('ebmr localStorage snapshot failed', e);
    }
  }

  /** LocalStorage only; server save uses {@link saveFillSubstep}. */
  mergeSubstep(
    product_code: string,
    work_order_no: string,
    batch_number: string,
    stepId: string | number,
    subId: string | number,
    data: unknown,
    product_name?: string
  ): void {
    const sId = String(stepId);
    const sub = String(subId);
    const key = `${sId}__${sub}`;
    try {
      let snap = this.getSnapshot(product_code, work_order_no, batch_number);
      if (!snap) {
        snap = {
          product_code: String(product_code),
          work_order_no: String(work_order_no),
          batch_number: String(batch_number),
          product_name,
          steps: {},
          substeps: {},
          approvalStatus: 'pending',
          updatedAt: new Date().toISOString(),
        };
      }
      if (product_name) {
        snap.product_name = product_name;
      }
      snap.substeps[key] = {
        stepId: sId,
        subId: sub,
        data: this.clone(data),
        savedAt: new Date().toISOString(),
      };
      if (snap.approvalStatus === 'approved') {
        snap.approvalStatus = 'pending';
        delete snap.approvedAt;
        delete snap.approvedByEmpId;
      }
      this.saveSnapshot(snap);
    } catch (e) {
      console.error('ebmr localStorage substep snapshot failed', e);
    }
  }

  setApproved(
    product_code: string,
    work_order_no: string,
    batch_number: string,
    approvedByEmpId?: string
  ): void {
    let snap = this.getSnapshot(product_code, work_order_no, batch_number);
    if (!snap) {
      snap = {
        product_code: String(product_code),
        work_order_no: String(work_order_no),
        batch_number: String(batch_number),
        steps: {},
        substeps: {},
        approvalStatus: 'pending',
        updatedAt: new Date().toISOString(),
      };
    }
    snap.approvalStatus = 'approved';
    snap.approvedAt = new Date().toISOString();
    if (approvedByEmpId != null && String(approvedByEmpId).trim() !== '') {
      snap.approvedByEmpId = String(approvedByEmpId).trim();
    }
    this.saveSnapshot(snap);
  }

  /** Approve batch on server (ebmr_proceed_batch_approval) and refresh local snapshot flags. */
  approveOnServer(selectedResult: {
    product_code: string;
    work_order_no: string;
    batch_number: string;
  }): Observable<unknown> {
    const body = {
      product_code: selectedResult.product_code,
      work_order_no: selectedResult.work_order_no,
      batch_number: selectedResult.batch_number,
    };
    let bodyStr: string;
    try {
      bodyStr = JSON.stringify(body);
    } catch {
      return of(null);
    }
    return this.dataAccess
      .postJson(
        EBMR_FILLED_API + '?type=approve_batch',
        bodyStr
      )
      .pipe(
        tap((res: any) => {
          if (this.isProcessPhpSaveSuccess(res) || (res && res['status'] === 'success')) {
            const approver =
              (typeof localStorage !== 'undefined' &&
                (localStorage.getItem('emp_id') || localStorage.getItem('loger_id'))) ||
              '';
            this.setApproved(
              selectedResult.product_code,
              selectedResult.work_order_no,
              selectedResult.batch_number,
              approver || undefined
            );
          }
        })
      );
  }

  fetchServerSnapshot(
    product_code: string,
    work_order_no: string,
    batch_number: string,
    product_name?: string
  ): Observable<BmrLocalSnapshot | null> {
    const q =
      EBMR_FILLED_API +
      '?type=get_batch_steps&product_code=' +
      encodeURIComponent(String(product_code)) +
      '&work_order_no=' +
      encodeURIComponent(String(work_order_no)) +
      '&batch_number=' +
      encodeURIComponent(String(batch_number));
    return this.dataAccess.get(q).pipe(
      map((resp: any) => this.snapshotFromApi(resp, product_code, work_order_no, batch_number, product_name)),
      catchError(() => of(null))
    );
  }

  private snapshotFromApi(
    resp: any,
    pc: string,
    wo: string,
    bn: string,
    product_name?: string
  ): BmrLocalSnapshot | null {
    if (!resp || resp['status'] == null || String(resp['status']).toLowerCase() !== 'success') {
      return null;
    }
    const now = new Date().toISOString();
    const steps: Record<string, BmrStepSnapshot> = {};
    const rawSteps =
      resp['steps'] && typeof resp['steps'] === 'object' && !Array.isArray(resp['steps'])
        ? resp['steps']
        : {};
    for (const id of Object.keys(rawSteps)) {
      const row = rawSteps[id];
      if (!row || typeof row !== 'object') {
        continue;
      }
      steps[id] = {
        stepId: id,
        stepName: row['step_name'],
        data: row['data'],
        savedAt: now,
      };
    }
    const substeps: Record<string, BmrSubstepSnapshot> = {};
    const rawSub =
      resp['substeps'] &&
      typeof resp['substeps'] === 'object' &&
      !Array.isArray(resp['substeps'])
        ? resp['substeps']
        : {};
    for (const key of Object.keys(rawSub)) {
      const cell = rawSub[key];
      const parts = key.split('__');
      const sid = parts[0] || '';
      const subid = parts[1] || '';
      substeps[key] = {
        stepId: sid,
        subId: subid,
        data: cell && typeof cell === 'object' ? cell['data'] : cell,
        savedAt: now,
      };
    }
    const approved = Number(resp['batch_approved']) === 1;
    const abe = resp['approved_by_emp_id'];
    const lse = resp['last_step_emp_id'];
    const lss = resp['last_step_saved_at'];
    return {
      product_code: String(pc),
      work_order_no: String(wo),
      batch_number: String(bn),
      product_name,
      steps,
      substeps,
      approvalStatus: approved ? 'approved' : 'pending',
      approvedAt: resp['approved_at'] || undefined,
      approvedByEmpId:
        abe != null && String(abe).trim() !== '' ? String(abe).trim() : undefined,
      lastStepEmpId:
        lse != null && String(lse).trim() !== '' ? String(lse).trim() : undefined,
      lastStepSavedAt:
        lss != null && String(lss).trim() !== '' ? String(lss).trim() : undefined,
      updatedAt: now,
    };
  }

  /**
   * Prefer server rows for the same step/substep key; union otherwise.
   * Use on the checking screen so DB fills always show even without localStorage.
   */
  mergeServerAndLocal(
    local: BmrLocalSnapshot | null,
    server: BmrLocalSnapshot | null
  ): BmrLocalSnapshot | null {
    if (!local && !server) {
      return null;
    }
    if (!local) {
      return server;
    }
    if (!server) {
      return local;
    }
    const steps: Record<string, BmrStepSnapshot> = { ...local.steps };
    for (const id of Object.keys(server.steps)) {
      steps[id] = server.steps[id];
    }
    const substeps: Record<string, BmrSubstepSnapshot> = { ...local.substeps };
    for (const key of Object.keys(server.substeps)) {
      substeps[key] = server.substeps[key];
    }
    const serverApproved = server.approvalStatus === 'approved';
    return {
      product_code: server.product_code || local.product_code,
      work_order_no: server.work_order_no || local.work_order_no,
      batch_number: server.batch_number || local.batch_number,
      product_name: server.product_name || local.product_name,
      steps,
      substeps,
      approvalStatus: serverApproved ? 'approved' : local.approvalStatus === 'approved' ? 'approved' : 'pending',
      approvedAt: server.approvedAt || local.approvedAt,
      approvedByEmpId: server.approvedByEmpId || local.approvedByEmpId,
      lastStepEmpId: server.lastStepEmpId || local.lastStepEmpId,
      lastStepSavedAt: server.lastStepSavedAt || local.lastStepSavedAt,
      updatedAt: new Date().toISOString(),
    };
  }

  /**
   * Save one step fill to `ebmr_filled_step_api.php` + update local snapshot on success.
   */
  saveFillStep(
    product_code: string,
    work_order_no: string,
    batch_number: string,
    stepId: string | number,
    data: unknown,
    stepName?: string,
    product_name?: string
  ): Observable<boolean> {
    const body = {
      product_code,
      work_order_no,
      batch_number,
      step_id: String(stepId),
      step_name: stepName || '',
      data,
    };
    let bodyStr: string;
    try {
      bodyStr = JSON.stringify(body);
    } catch (e) {
      console.error('saveFillStep JSON.stringify failed', e);
      return of(false);
    }
    return this.dataAccess
      .postJson(EBMR_FILLED_API + '?type=save_step', bodyStr)
      .pipe(
        map((res: any) => res && res['status'] === 'success'),
        tap((ok) => {
          if (ok) {
            this.mergeStep(
              product_code,
              work_order_no,
              batch_number,
              stepId,
              { data, stepName },
              product_name
            );
          }
        }),
        catchError((err) => {
          console.error('ebmr_filled_step_api.php save_step', err);
          return of(false);
        })
      );
  }

  saveFillSubstep(
    product_code: string,
    work_order_no: string,
    batch_number: string,
    stepId: string | number,
    substepId: string | number,
    data: unknown,
    product_name?: string
  ): Observable<boolean> {
    const body = {
      product_code,
      work_order_no,
      batch_number,
      step_id: String(stepId),
      substep_id: String(substepId),
      data,
    };
    let bodyStr: string;
    try {
      bodyStr = JSON.stringify(body);
    } catch (e) {
      console.error('saveFillSubstep JSON.stringify failed', e);
      return of(false);
    }
    return this.dataAccess
      .postJson(EBMR_FILLED_API + '?type=save_substep', bodyStr)
      .pipe(
        map((res: any) => res && res['status'] === 'success'),
        tap((ok) => {
          if (ok) {
            this.mergeSubstep(
              product_code,
              work_order_no,
              batch_number,
              stepId,
              substepId,
              data,
              product_name
            );
          }
        }),
        catchError((err) => {
          console.error('ebmr_filled_step_api.php save_substep', err);
          return of(false);
        })
      );
  }

  isApproved(
    product_code: string,
    work_order_no: string,
    batch_number: string
  ): boolean {
    const s = this.getSnapshot(product_code, work_order_no, batch_number);
    return s?.approvalStatus === 'approved';
  }

  hasSnapshot(
    product_code: string,
    work_order_no: string,
    batch_number: string
  ): boolean {
    return this.getSnapshot(product_code, work_order_no, batch_number) != null;
  }

  stepEntries(snap: BmrLocalSnapshot): BmrStepSnapshot[] {
    return Object.keys(snap.steps)
      .sort()
      .map((k) => snap.steps[k]);
  }

  substepEntries(snap: BmrLocalSnapshot): BmrSubstepSnapshot[] {
    return Object.keys(snap.substeps)
      .sort()
      .map((k) => snap.substeps[k]);
  }

  openPrintableReport(snap: BmrLocalSnapshot, options?: BmrPrintOptions | null): void {
    const w = window.open('', '_blank');
    if (!w) {
      return;
    }
    const title = `BMR saved data — ${snap.product_name || snap.product_code} / ${snap.batch_number}`;
    const mainSections = this.buildPrintMainSections(snap, options);
    const coverHtml = this.buildPrintCoverTable(snap, options);
    const introHtml = this.buildPrintBmrOrderIntro(options);
    w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>${this.escapeHtml(title)}</title>
      <style>
        @page { margin: 14mm; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 16px; color: #111; font-size: 11pt; }
        h1 { font-size: 14pt; font-weight: bold; margin: 0 0 8px; }
        .bmr-cover { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .bmr-cover th, .bmr-cover td { border: 1px solid #000; padding: 6px 8px; text-align: left; vertical-align: top; }
        .bmr-cover th { background: #f0f0f0; font-weight: bold; width: 180px; max-width: 32%; }
        .bmr-print-section-title { font-size: 13pt; font-weight: bold; margin: 16px 0 6px; }
        .bmr-print-intro { font-size: 10pt; color: #666; margin: 0 0 14px; line-height: 1.35; }
        .ebmr-print-stage { margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #e0e0e0; page-break-inside: avoid; }
        .ebmr-print-stage-title { color: #0e4370; font-size: 12pt; font-weight: 600; margin: 12px 0 8px; }
        .ebmr-print-step-block { margin: 10px 0 10px 10px; padding-left: 8px; border-left: 3px solid #c5d4e8; page-break-inside: avoid; }
        .ebmr-print-step-heading, .ebmr-print-substep-heading { font-size: 11pt; margin-bottom: 4px; }
        .ebmr-print-idx { font-weight: 700; color: #333; margin-right: 10px; }
        .ebmr-print-id { color: #555; margin-right: 10px; font-family: ui-monospace, Consolas, monospace; font-size: 10pt; }
        .ebmr-print-name { color: #222; }
        .ebmr-print-step-body { margin-bottom: 8px; }
        .ebmr-print-substep-block { margin: 8px 0 8px 14px; padding: 8px 10px; background: #f9fafb; border: 1px solid #e8e8e8; border-radius: 3px; page-break-inside: avoid; }
        .ebmr-print-saved-line { font-size: 9pt; color: #666; margin: 0 0 6px; }
        .ebmr-print-orphan-section { margin-top: 20px; padding-top: 10px; border-top: 2px dashed #ccc; }
        .ebmr-print-orphan-title { color: #856404; font-size: 12pt; font-weight: 600; margin-bottom: 6px; }
        .bmr-h3 { font-size: 11pt; font-weight: bold; margin: 16px 0 4px; padding-bottom: 2px; border-bottom: 1px solid #000; }
        .bmr-meta { font-size: 9pt; color: #333; margin: 0 0 8px; }
        .bmr-block { margin-bottom: 20px; page-break-inside: avoid; }
        .bmr-payload { margin-top: 4px; }
        table.bmr { width: 100%; border-collapse: collapse; margin: 6px 0 10px; font-size: 10pt; }
        table.bmr th, table.bmr td { border: 1px solid #000; padding: 5px 6px; text-align: left; vertical-align: top; }
        table.bmr thead th { background: #e8e8e8; font-weight: bold; }
        table.bmr .bmr-idx { width: 3.5em; text-align: center; background: #f5f5f5; }
        table.bmr-kv th.bmr-k { width: 32%; background: #f0f0f0; font-weight: normal; }
        .bmr-subheading { font-weight: bold; font-size: 10pt; margin: 10px 0 4px; }
        .bmr-empty { color: #555; margin: 4px 0; }
        .bmr-text { white-space: pre-wrap; margin: 4px 0; font-size: 10pt; }
        .bmr-signatures { margin-top: 28px; page-break-inside: avoid; }
        .bmr-signatures-h2 { font-size: 11pt; font-weight: bold; margin: 0 0 10px; padding-bottom: 4px; border-bottom: 1px solid #000; }
        table.bmr-sign-table { margin: 0 0 10px; font-size: 10pt; }
        table.bmr-sign-table thead th { background: #e8e8e8; white-space: nowrap; }
        table.bmr-sign-table .bmr-sign-role { font-weight: bold; }
        table.bmr-sign-table .bmr-sign-slot { font-size: 9pt; color: #333; white-space: nowrap; }
      </style></head><body>
      <h1>${this.escapeHtml(title)}</h1>
      ${coverHtml}
      ${introHtml}
      ${mainSections}
      ${this.buildPrintSignatureSection(snap)}
      </body></html>`);
    w.document.close();
  }

  /** Cover table aligned with awaiting check screen (Product, Local save status, Last Proceed save). */
  private buildPrintCoverTable(
    snap: BmrLocalSnapshot,
    options?: BmrPrintOptions | null
  ): string {
    const opts = options || {};
    const productCell =
      opts.coverProductLine != null && String(opts.coverProductLine).trim() !== ''
        ? String(opts.coverProductLine).trim()
        : snap.product_name || '—';
    let statusHtml = '';
    if (snap.approvalStatus === 'pending') {
      statusHtml = this.escapeHtml('Pending approval');
    } else if (snap.approvalStatus === 'approved') {
      const bits: string[] = [this.escapeHtml('Approved')];
      if (snap.approvedAt) {
        bits.push(this.escapeHtml(snap.approvedAt));
      }
      if (snap.approvedByEmpId) {
        bits.push(
          this.escapeHtml(
            '· Checker emp ID: ' + String(snap.approvedByEmpId)
          )
        );
      }
      statusHtml = bits.join(' ');
    } else {
      statusHtml = this.escapeHtml(String(snap.approvalStatus));
    }
    let lastProceedRow = '';
    if (snap.lastStepEmpId) {
      lastProceedRow = `<tr><th>Last Proceed save (server)</th><td>Emp ID <strong>${this.escapeHtml(snap.lastStepEmpId)}</strong>`;
      if (snap.lastStepSavedAt) {
        lastProceedRow += ` <span style="color:#666;font-size:11px">${this.escapeHtml(snap.lastStepSavedAt)}</span>`;
      }
      lastProceedRow += `</td></tr>`;
    }
    return `<table class="bmr-cover" role="presentation"><tbody>
          <tr><th>Product</th><td>${this.escapeHtml(productCell)}</td></tr>
          <tr><th>Product code</th><td>${this.escapeHtml(snap.product_code)}</td></tr>
          <tr><th>Work order</th><td>${this.escapeHtml(snap.work_order_no)}</td></tr>
          <tr><th>Batch no.</th><td>${this.escapeHtml(snap.batch_number)}</td></tr>
          <tr><th>Local save status</th><td>${statusHtml}</td></tr>
          ${lastProceedRow}
        </tbody></table>`;
  }

  private buildPrintBmrOrderIntro(options?: BmrPrintOptions | null): string {
    const opts = options || {};
    const hasTree =
      (!!opts.orderedSections && opts.orderedSections.length > 0) ||
      (!!opts.orphanSteps && opts.orphanSteps.length > 0) ||
      (!!opts.orphanSubsteps && opts.orphanSubsteps.length > 0);
    const para = hasTree
      ? 'Grouped by <strong>Stage → Step → Substep</strong> using the same master as Proceed (<code>getProcesses_BMRviewProceed</code>). Payloads come from <code>ebmr_filled_step_api.php</code>.'
      : 'Printed without the BMR stage/step tree for this session (e.g. from Awaiting). Steps and substeps are listed in id order. Payloads come from <code>ebmr_filled_step_api.php</code>.';
    return `<h2 class="bmr-print-section-title">Saved data in BMR order</h2><p class="bmr-print-intro">${para}</p>`;
  }

  private buildPrintMainSections(
    snap: BmrLocalSnapshot,
    options?: BmrPrintOptions | null
  ): string {
    const opts = options || {};
    const ordered = opts.orderedSections;
    const hasOrder = !!(ordered && ordered.length > 0);
    const oSteps = opts.orphanSteps;
    const oSubs = opts.orphanSubsteps;
    const hasOrphans =
      (!!oSteps && oSteps.length > 0) || (!!oSubs && oSubs.length > 0);
    let html = '';
    if (hasOrder) {
      html += this.buildOrderedSectionsPrintHtml(ordered!);
    }
    if (hasOrphans) {
      html += this.buildOrphansPrintHtml(oSteps || [], oSubs || []);
    }
    if (!hasOrder && !hasOrphans) {
      html += this.buildFlatPrintSections(snap);
    }
    return html;
  }

  private buildOrderedSectionsPrintHtml(
    sections: BmrPrintOrderedStageSection[]
  ): string {
    const parts: string[] = [];
    for (const sec of sections) {
      parts.push(`<div class="ebmr-print-stage">`);
      parts.push(
        `<h5 class="ebmr-print-stage-title">Stage ${sec.stageOrdinal} — ${this.escapeHtml(sec.stageTitle)}</h5>`
      );
      for (const blk of sec.steps) {
        const stepRef = `${sec.stageOrdinal}.${blk.stepOrdinal}`;
        const stepNameDisp = blk.stepName
          ? this.escapeHtml(blk.stepName)
          : '—';
        parts.push(`<div class="ebmr-print-step-block">`);
        parts.push(
          `<div class="ebmr-print-step-heading"><span class="ebmr-print-idx">Step ${this.escapeHtml(stepRef)}</span><span class="ebmr-print-id">ID ${this.escapeHtml(blk.stepId)}</span><span class="ebmr-print-name">${stepNameDisp}</span></div>`
        );
        if (blk.stepSaved) {
          const s = blk.stepSaved;
          const body = payloadViewToPrintHtml(
            buildEbmrPayloadView(s.stepName || blk.stepName, s.data)
          );
          parts.push(`<div class="ebmr-print-step-body">`);
          parts.push(
            `<p class="ebmr-print-saved-line">Saved: ${this.escapeHtml(s.savedAt)}</p>`
          );
          parts.push(`<div class="bmr-payload">${body}</div></div>`);
        }
        for (const sub of blk.substeps) {
          const subRef = `${sec.stageOrdinal}.${blk.stepOrdinal}.${sub.subOrdinal}`;
          const subNameDisp = sub.subName
            ? this.escapeHtml(sub.subName)
            : '—';
          const body = payloadViewToPrintHtml(
            buildEbmrPayloadView(sub.subName, sub.saved.data)
          );
          parts.push(`<div class="ebmr-print-substep-block">`);
          parts.push(
            `<div class="ebmr-print-substep-heading"><span class="ebmr-print-idx">Substep ${this.escapeHtml(subRef)}</span><span class="ebmr-print-id">ID ${this.escapeHtml(sub.subId)}</span><span class="ebmr-print-name">${subNameDisp}</span></div>`
          );
          parts.push(
            `<p class="ebmr-print-saved-line">Saved: ${this.escapeHtml(sub.saved.savedAt)}</p>`
          );
          parts.push(`<div class="bmr-payload">${body}</div></div>`);
        }
        parts.push(`</div>`);
      }
      parts.push(`</div>`);
    }
    return parts.join('');
  }

  private buildOrphansPrintHtml(
    orphanSteps: BmrStepSnapshot[],
    orphanSubsteps: BmrSubstepSnapshot[]
  ): string {
    const parts: string[] = [];
    parts.push(`<div class="ebmr-print-orphan-section">`);
    parts.push(
      `<h5 class="ebmr-print-orphan-title">Outside current BMR tree</h5>`
    );
    parts.push(
      `<p class="bmr-print-intro">Saved ids not found under the loaded Stages/Steps/Substeps (older template, different product build, or API mismatch).</p>`
    );
    for (const s of orphanSteps) {
      const body = payloadViewToPrintHtml(
        buildEbmrPayloadView(s.stepName, s.data)
      );
      const name = s.stepName ? this.escapeHtml(s.stepName) : '—';
      parts.push(`<div class="ebmr-print-step-block">`);
      parts.push(
        `<div class="ebmr-print-step-heading"><span class="ebmr-print-idx">Step (orphan)</span><span class="ebmr-print-id">ID ${this.escapeHtml(s.stepId)}</span><span class="ebmr-print-name">${name}</span></div>`
      );
      parts.push(
        `<p class="ebmr-print-saved-line">Saved: ${this.escapeHtml(s.savedAt)}</p>`
      );
      parts.push(`<div class="bmr-payload">${body}</div></div>`);
    }
    for (const s of orphanSubsteps) {
      const body = payloadViewToPrintHtml(
        buildEbmrPayloadView(undefined, s.data)
      );
      parts.push(`<div class="ebmr-print-substep-block">`);
      parts.push(
        `<div class="ebmr-print-substep-heading"><span class="ebmr-print-idx">Substep (orphan)</span><span class="ebmr-print-id">Step ${this.escapeHtml(s.stepId)} / Sub ${this.escapeHtml(s.subId)}</span></div>`
      );
      parts.push(
        `<p class="ebmr-print-saved-line">Saved: ${this.escapeHtml(s.savedAt)}</p>`
      );
      parts.push(`<div class="bmr-payload">${body}</div></div>`);
    }
    parts.push(`</div>`);
    return parts.join('');
  }

  /** Fallback when no BMR tree was passed (e.g. print from Awaiting list). */
  private buildFlatPrintSections(snap: BmrLocalSnapshot): string {
    const stepBlocks = this.stepEntries(snap)
      .map((s) => {
        const body = payloadViewToPrintHtml(
          buildEbmrPayloadView(s.stepName, s.data)
        );
        const name = s.stepName ? this.escapeHtml(s.stepName) : '—';
        return `<div class="ebmr-print-step-block">
        <div class="ebmr-print-step-heading"><span class="ebmr-print-idx">Step</span><span class="ebmr-print-id">ID ${this.escapeHtml(s.stepId)}</span><span class="ebmr-print-name">${name}</span></div>
        <p class="ebmr-print-saved-line">Saved: ${this.escapeHtml(s.savedAt)}</p>
        <div class="bmr-payload">${body}</div></div>`;
      })
      .join('');
    const subBlocks = this.substepEntries(snap)
      .map((s) => {
        const body = payloadViewToPrintHtml(
          buildEbmrPayloadView(undefined, s.data)
        );
        return `<div class="ebmr-print-substep-block">
        <div class="ebmr-print-substep-heading"><span class="ebmr-print-idx">Substep</span><span class="ebmr-print-id">Step ${this.escapeHtml(s.stepId)} / Sub ${this.escapeHtml(s.subId)}</span></div>
        <p class="ebmr-print-saved-line">Saved: ${this.escapeHtml(s.savedAt)}</p>
        <div class="bmr-payload">${body}</div></div>`;
      })
      .join('');
    return stepBlocks + subBlocks;
  }

  /** Human-readable date/time for print footer (SQL or ISO). */
  private formatBmrReportDate(raw: string | undefined): string {
    if (raw == null || String(raw).trim() === '') {
      return '—';
    }
    const s = String(raw).trim();
    const sql = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/.exec(s);
    const d = sql
      ? new Date(
          Number(sql[1]),
          Number(sql[2]) - 1,
          Number(sql[3]),
          Number(sql[4]),
          Number(sql[5]),
          Number(sql[6])
        )
      : new Date(s);
    if (!Number.isNaN(d.getTime())) {
      return d.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    }
    return s;
  }

  private buildPrintSignatureSection(snap: BmrLocalSnapshot): string {
    const prodEmp = snap.lastStepEmpId || '—';
    const prodDt = this.formatBmrReportDate(snap.lastStepSavedAt);
    const apprEmp =
      snap.approvalStatus === 'approved'
        ? snap.approvedByEmpId || '—'
        : 'Pending checking approval';
    const apprDt =
      snap.approvalStatus === 'approved'
        ? this.formatBmrReportDate(snap.approvedAt)
        : '—';
    return `
      <section class="bmr-signatures">
        <h2 class="bmr-signatures-h2">Digital signature / authorization</h2>
        <table class="bmr bmr-sign-table">
          <thead>
            <tr>
              <th scope="col">Sr. No.</th>
              <th scope="col">Particulars</th>
              <th scope="col">Employee ID</th>
              <th scope="col">Date &amp; time</th>
              <th scope="col">Signature</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="bmr-idx">1</td>
              <td class="bmr-sign-role">Production (Proceed) — last BMR step / substep recorded</td>
              <td>${this.escapeHtml(prodEmp)}</td>
              <td>${this.escapeHtml(prodDt)}</td>
              <td class="bmr-sign-slot">_________________________</td>
            </tr>
            <tr>
              <td class="bmr-idx">2</td>
              <td class="bmr-sign-role">eBMR checking — batch record reviewed &amp; approved</td>
              <td>${this.escapeHtml(apprEmp)}</td>
              <td>${this.escapeHtml(apprDt)}</td>
              <td class="bmr-sign-slot">_________________________</td>
            </tr>
          </tbody>
        </table>
      </section>`;
  }

  private clone<T>(v: T): T {
    try {
      return JSON.parse(JSON.stringify(v)) as T;
    } catch {
      return v;
    }
  }

  private escapeHtml(s: string): string {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
}
