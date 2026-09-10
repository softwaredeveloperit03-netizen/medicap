import { Component, ElementRef, Input, OnChanges, SimpleChanges, ViewChild } from '@angular/core';
import { masterStepSigners, stepDetailsSummary, stepWorkflowStatus } from '../master-step-workflow.util';
import { CfrSigner } from 'src/app/shared/cfr-signature-block/cfr-signature-block.component';
import { normalizeProductApprovalPage, productApprovalHasContent } from '../product-approval.util';
import { normalizeSafetyPrecautions, safetyHasContent } from '../safety-precautions.util';
import {
  generalInstructionsHasContent,
  normalizeGeneralInstructions,
} from '../general-instructions.util';
import {
  DispensingPage,
  DispensingSection,
  dispensingHasContent,
  evaluationParameterLabel,
  normalizeDispensingPage,
} from '../dispensing-store.util';

@Component({
  selector: 'app-master-final-html-bmr',
  templateUrl: './master-final-html-bmr.component.html',
  styleUrls: ['./master-final-html-bmr.component.css'],
})
export class MasterFinalHtmlBmrComponent implements OnChanges {
  /** Master builder profile (preferred when set). */
  @Input() profile: any;
  /** Execution batch — builds the same HTML document from live batch data. */
  @Input() batch: any;
  /** Force document type for title / PDF. */
  @Input() recordKind: 'eBMR' | 'eBPR' = 'eBMR';
  /** When true (PDF tab), open print dialog after render. */
  @Input() autoPrint = false;
  /** Optional context label override (e.g. Completed / Archive). */
  @Input() contextHint = '';
  @ViewChild('docRoot') docRoot?: ElementRef<HTMLElement>;

  staticPages = [
    { key: 'product_approval', label: 'Product Approval Page' },
    { key: 'general_instructions', label: 'General Instructions' },
    { key: 'safety', label: 'Safety & Precautions' },
    { key: 'equipment_selection', label: 'Equipment Selection' },
    { key: 'unit_formula', label: 'Unit Formula' },
    { key: 'product_spec', label: 'Product Specification' },
    { key: 'dispensing', label: 'Dispensing in Production' },
  ];

  private cachedFromBatch: any = null;

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['batch'] || changes['recordKind'] || changes['profile']) {
      this.cachedFromBatch = null;
    }
    if (changes['autoPrint'] && this.autoPrint) {
      setTimeout(() => this.printDoc(), 600);
    }
  }

  /** Unified document model (master profile or mapped batch). */
  get doc(): any {
    if (this.profile) return this.profile;
    if (!this.batch) return null;
    if (!this.cachedFromBatch) this.cachedFromBatch = this.buildProfileFromBatch(this.batch);
    return this.cachedFromBatch;
  }

  get isExecutionDoc(): boolean {
    return !!this.batch && !this.profile;
  }

  get header(): any {
    return this.doc?.header || {};
  }

  get staticBag(): any {
    return this.doc?.static || {};
  }

  get versionNo(): string {
    return String(this.doc?.version || '1.0');
  }

  get isPacking(): boolean {
    return this.recordKind === 'eBPR' || this.doc?.record_type === 'eBPR';
  }

  get docTitle(): string {
    return this.isPacking ? 'BATCH PACKING RECORD' : 'BATCH MANUFACTURING RECORD';
  }

  get docShortLabel(): string {
    return this.isPacking ? 'Batch Packing Record' : 'Batch Manufacturing Record';
  }

  get docCodeLabel(): string {
    return this.isPacking ? 'eBPR' : 'eBMR';
  }

  get contextLabel(): string {
    if (this.contextHint) return this.contextHint;
    return this.isExecutionDoc ? 'Execution' : 'Master';
  }

  get printFileTitle(): string {
    const code = this.header?.bmr_no || this.doc?.profile_code || this.batch?.batch_no || this.docCodeLabel;
    return `${this.docTitle} — ${code}`;
  }

  get stages(): any[] {
    return Array.isArray(this.doc?.stages) ? this.doc.stages : [];
  }

  get batchNoDisplay(): string {
    if (this.batch?.batch_no) return String(this.batch.batch_no);
    return 'BLOCKED — filled at execution';
  }

  get commencementDisplay(): string {
    const v = this.batch?.commencement_date || this.batch?.mfg_date || this.batch?.started_at || this.batch?.start_date;
    return v ? String(v) : 'BLOCKED — filled at execution';
  }

  get completionDisplay(): string {
    const v = this.batch?.completion_date || this.batch?.completed_at || this.batch?.released_at || this.batch?.exp_date;
    return v ? String(v) : 'BLOCKED — filled at execution';
  }

  get batchNoBlocked(): boolean {
    return !this.batch?.batch_no;
  }

  get commencementBlocked(): boolean {
    return !(this.batch?.commencement_date || this.batch?.mfg_date || this.batch?.started_at || this.batch?.start_date);
  }

  get completionBlocked(): boolean {
    return !(this.batch?.completion_date || this.batch?.completed_at || this.batch?.released_at);
  }

  staticPageNo(i: number): number {
    return i + 2;
  }

  stagePageNo(si: number): number {
    return this.staticPages.length + si + 2;
  }

  stageSteps(stage: any): any[] {
    return Array.isArray(stage?.steps) ? stage.steps : [];
  }

  stepStatus(step: any): string {
    if (this.isExecutionDoc) return String(step?.status || step?.workflow_status || 'Pending');
    return stepWorkflowStatus(step);
  }

  stepDetails(step: any): string {
    return stepDetailsSummary(step);
  }

  stepSigners(step: any): CfrSigner[] {
    return masterStepSigners(step);
  }

  private buildProfileFromBatch(batch: any): any {
    const header = {
      ...(batch.header || {}),
      product_code: batch.product_code || batch.header?.product_code || '',
      product_name: batch.product_name || batch.header?.product_name || '',
      generic_name: batch.header?.generic_name || '',
      bmr_no: batch.header?.bmr_no || batch.bmr_no || batch.profile_code || '',
      batch_size: batch.batch_size || batch.header?.batch_size || '',
      batch_size_uom: batch.batch_size_uom || batch.header?.batch_size_uom || '',
    };
    const groups: any[] = [];
    let cur: any = null;
    (batch.steps || []).forEach((s: any) => {
      if (!cur || cur.stage_seq !== s.stage_seq || cur.stage_name !== s.stage_name) {
        cur = {
          stage_seq: s.stage_seq,
          stage_name: s.stage_name,
          seq_no: s.stage_seq,
          frozen: 0,
          config: { instructions: s.stage_instructions || '' },
          steps: [],
        };
        groups.push(cur);
      }
      const tpl = s.template || {};
      cur.steps.push({
        ...s,
        step_name: s.step_name,
        workflow_status: s.status || 'Pending',
        config: this.templateToConfig(tpl),
        prepared: s.prepared || this.snapFrom(s.done_by, s.done_at, s.done_designation),
        checked: s.checked || this.snapFrom(s.checked_by, s.checked_at, s.checked_designation),
        reviewed: s.reviewed || this.snapFrom(s.reviewed_by, s.reviewed_at, s.reviewed_designation),
        approved: s.approved || this.snapFrom(s.approved_by, s.approved_at, s.approved_designation),
      });
    });
    return {
      record_type: this.recordKind || batch.record_type || 'eBMR',
      version: batch.version || batch.profile_version || '1.0',
      dosage_form: batch.dosage_form || '',
      profile_code: batch.profile_code || '',
      header,
      static: batch.static || {},
      stages: groups,
    };
  }

  private snapFrom(name: any, at: any, designation?: any): any {
    if (!name) return null;
    return {
      emp_name: String(name),
      emp_id: '',
      designation: designation || '',
      signed_at: at || '',
    };
  }

  private templateToConfig(tpl: any): any {
    const t = tpl || {};
    return {
      master_forms: {
        line_clearance: !!(t.line_clearance && t.line_clearance.length),
        procedure: !!(t.procedure || t.procedure_text),
        inprocess_checks: !!(t.inprocess_checks && t.inprocess_checks.length),
        dept_checks: !!(t.dept_checks && t.dept_checks.length),
        qa_checks: !!(t.qa_checks && t.qa_checks.length),
        ipqc: !!(t.ipqc && t.ipqc.length),
        yield_table: !!(t.yield_table && t.yield_table.length),
        weighing_table: !!(t.weighing_table && t.weighing_table.length),
        equipment: !!(t.equipment && t.equipment.length),
      },
      procedure: t.procedure || t.procedure_text || '',
      line_clearance: t.line_clearance || [],
      inprocess_checks: t.inprocess_checks || [],
      dept_checks: t.dept_checks || [],
      qa_checks: t.qa_checks || [],
      ipqc: t.ipqc || [],
      equipment: t.equipment || [],
      step_timestamp: t.step_timestamp || { enabled: false },
      holding: t.holding || null,
      yield_table: t.yield_table || [],
    };
  }

  staticStatus(key: string): string {
    return this.staticHasContent(key) ? 'Configured' : 'Blank';
  }

  staticHasContent(key: string): boolean {
    const s = this.staticBag;
    switch (key) {
      case 'product_approval':
        return productApprovalHasContent(normalizeProductApprovalPage(s.product_approval, this.header));
      case 'general_instructions':
        return generalInstructionsHasContent(normalizeGeneralInstructions(s.general_instructions));
      case 'safety':
        return safetyHasContent(normalizeSafetyPrecautions(s.safety));
      case 'equipment_selection':
        return Array.isArray(s.equipment_selection) && s.equipment_selection.length > 0;
      case 'unit_formula':
        return Array.isArray(s.unit_formula) && s.unit_formula.length > 0;
      case 'product_spec':
        return Array.isArray(s.product_spec) && s.product_spec.length > 0;
      case 'dispensing':
        return (
          dispensingHasContent(normalizeDispensingPage(s.dispensing)) ||
          this.dispensingMaterials().length > 0
        );
      default:
        return false;
    }
  }

  productApprovalPage() {
    return normalizeProductApprovalPage(this.staticBag.product_approval, this.header);
  }

  generalInstructionsContent() {
    return normalizeGeneralInstructions(this.staticBag.general_instructions);
  }

  safetyContent() {
    return normalizeSafetyPrecautions(this.staticBag.safety);
  }

  equipmentSelection(): any[] {
    return Array.isArray(this.staticBag.equipment_selection) ? this.staticBag.equipment_selection : [];
  }

  unitFormulaRows(): any[] {
    return Array.isArray(this.staticBag.unit_formula) ? this.staticBag.unit_formula : [];
  }

  productSpecRows(): any[] {
    return Array.isArray(this.staticBag.product_spec) ? this.staticBag.product_spec : [];
  }

  dispensingPage(): DispensingPage {
    return normalizeDispensingPage(this.staticBag.dispensing);
  }

  dispensingMaterials(): any[] {
    const page = this.dispensingPage();
    if (Array.isArray(page.materials) && page.materials.length) return page.materials;
    if (Array.isArray(page.store_records) && page.store_records.length) return page.store_records;
    const uf = this.unitFormulaRows();
    return uf.filter((r) => {
      const stage = String(r?.stage || r?.process_stage || '').toLowerCase();
      return stage.includes('dispens');
    });
  }

  isGeneralInstructionSection(section: DispensingSection): boolean {
    const h = String(section?.heading || '').toLowerCase();
    return h.includes('general instruction');
  }

  dispensingEvalLabel(v: string | undefined): string {
    return evaluationParameterLabel(v);
  }

  productSpecText(r: any): string {
    if (r?.specification) return String(r.specification);
    if (r?.spec) return String(r.spec);
    const min = r?.min_limit != null && r?.min_limit !== '' ? String(r.min_limit) : '';
    const max = r?.max_limit != null && r?.max_limit !== '' ? String(r.max_limit) : '';
    const uom = r?.uom ? ' ' + r.uom : '';
    if (min || max) return (min && max ? min + ' - ' + max : min || max) + uom;
    return '—';
  }

  masterAuthSigners(): CfrSigner[] {
    const roles = ['Prepared By', 'Checked By', 'Reviewed By', 'Approved By'] as const;
    const keys = ['prepared', 'checked', 'reviewed', 'approved'] as const;
    const best: any[] = [null, null, null, null];
    this.stages.forEach((st) => {
      this.stageSteps(st).forEach((step) => {
        keys.forEach((k, i) => {
          const snap = step?.[k];
          if (snap && (snap.emp_name || snap.emp_id)) {
            const prev = best[i];
            const prevAt = prev?.signed_at || '';
            const nextAt = snap.signed_at || '';
            if (!prev || String(nextAt) >= String(prevAt)) best[i] = snap;
          }
        });
      });
    });
    return roles.map((role, i) => {
      const snap = best[i];
      return {
        role,
        name: snap?.emp_name || '',
        empId: snap?.emp_id || '',
        designation: snap?.designation || '',
        department: snap?.department || '',
        dateTime: snap?.signed_at || '',
        signed: !!(snap && (snap.emp_name || snap.emp_id)),
        hashToken: snap?.signature_token || '',
        authMethod: snap?.auth_method || '',
        signatureType: snap?.auth_type
          ? String(snap.auth_type).toUpperCase() === 'PIN'
            ? 'Authorization PIN'
            : 'Password'
          : '',
        reason: snap?.reason || '',
      } as CfrSigner;
    });
  }

  enabledForms(step: any): string[] {
    const mf = step?.config?.master_forms || {};
    return Object.keys(mf).filter((k) => !!mf[k]);
  }

  formLabel(key: string): string {
    return String(key || '').replace(/_/g, ' ');
  }

  equipmentRows(step: any): any[] {
    return Array.isArray(step?.config?.equipment) ? step.config.equipment : [];
  }

  checkpointRows(step: any, key: string): any[] {
    return Array.isArray(step?.config?.[key]) ? step.config[key] : [];
  }

  printDoc(): void {
    const el = this.docRoot?.nativeElement;
    if (!el) return;
    const win = window.open('', '_blank', 'width=1024,height=768');
    if (!win) return;
    const styles = Array.from(document.querySelectorAll('style, link[rel="stylesheet"]'))
      .map((n) => n.outerHTML)
      .join('\n');
    win.document.write(`<!DOCTYPE html><html><head><title>${this.printFileTitle}</title>${styles}
      <style>
        body { margin: 12px; background: #fff; }
        .fh-toolbar { display: none !important; }
        .fh-page { page-break-after: always; }
        .fh-page:last-child { page-break-after: auto; }
        @media print { .fh-toolbar { display: none !important; } }
      </style>
      </head><body>${el.innerHTML}</body></html>`);
    win.document.close();
    setTimeout(() => {
      win.focus();
      win.print();
    }, 350);
  }
}
