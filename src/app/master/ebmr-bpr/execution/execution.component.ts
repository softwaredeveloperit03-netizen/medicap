import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';
import { EbmrQmsEmbedContext } from 'src/app/shared/qms-embed/ebmr-qms-embed.types';
import { normalizeProductApprovalPage, productApprovalHasContent, ProductApprovalPage, applyMasterLockedFields } from '../shared/product-approval.util';
import { normalizeSafetyPrecautions, SafetyPrecautionsContent } from '../shared/safety-precautions.util';
import {
  GeneralInstructionsContent,
  normalizeGeneralInstructions,
} from '../shared/general-instructions.util';
import {
  DispensingMaterialRow,
  DispensingPage,
  DispensingSection,
  evaluationParameterLabel,
  normalizeDispensingPage,
} from '../shared/dispensing-store.util';
import {
  checkpointActiveOnTemplate,
  resolveExecutionCheckpointSequence,
} from '../shared/step-checkpoint-sequence.util';
import {
  StageStepIndexRow,
  buildStageStepIndexRows,
} from '../shared/master-step-workflow.util';
import {
  EquipmentLogbook,
  MachineLogRow,
  buildMachineLogRows,
  collectBatchEquipment,
  emptyEquipmentUsage,
  equipmentKey,
  findEquipmentMeta,
  getEquipmentLogbook,
  hasEquipmentUsage,
  normalizeExecLogbook,
  rebuildLogbookFromBatch,
  syncStepEquipmentToLogbook,
} from '../shared/equipment-logbook.util';
import {
  calcYieldReconciliation,
  emptyYieldReconciliation,
  formatYieldNum,
} from '../shared/yield-reconciliation.util';

declare let alertify: any;

interface ExecSel {
  kind: 'header' | 'static' | 'step' | 'yield' | 'status' | 'stage';
  staticKey?: string;
  stepId?: number;
  stageIndex?: number;
}

@Component({
  selector: 'app-ebmr-execution',
  templateUrl: './execution.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', '../builder/builder.component.css', './execution.component.css'],
})
export class ExecutionComponent implements OnInit {
  batchId = 0;
  loading = false;
  saving = false;
  batch: any = null;

  selected: ExecSel = { kind: 'header' };
  rightTab = 'corrections';

  // checker review mode unlocks flag-entry + verification controls
  reviewMode = false;

  /** Stage-scoped visibility from work allocation (previous + allocated stage only). */
  stageAccess: any = { mode: 'full' };

  /** Execution workflow tab */
  executionMode: 'open' | 'correction' | 'checking' | 'review' | 'approval' | 'final' | 'html_record' | 'pdf' = 'open';
  batchStages: any[] = [];
  stageCheckRemark = '';
  stageApproveRemark = '';

  staticPages = [
    { key: 'product_approval', label: 'Product Approval Page', icon: 'fas fa-stamp' },
    { key: 'general_instructions', label: 'General Instructions', icon: 'fas fa-list-ol' },
    { key: 'safety', label: 'Safety & Precautions', icon: 'fas fa-hard-hat' },
    { key: 'equipment_selection', label: 'Equipment Selection', icon: 'fas fa-tools' },
    { key: 'unit_formula', label: 'Unit Formula', icon: 'fas fa-flask' },
    { key: 'product_spec', label: 'Product Specification', icon: 'fas fa-file-contract' },
    { key: 'dispensing', label: 'Dispensing in Production', icon: 'fas fa-balance-scale' },
  ];

  rightTabs = [
    { key: 'deviations', label: 'Deviations', icon: 'fas fa-code-branch', action: 'Raise / manage deviations', detail: 'QMS Part-I deviation with Production Head continue/hold remark.' },
    { key: 'incidents', label: 'Incidents', icon: 'fas fa-triangle-exclamation', action: 'Report incidents', detail: 'Stage-wise INR in QMS with next-stage proceed remark.' },
    { key: 'breakdown', label: 'Breakdown', icon: 'fas fa-wrench', action: 'Breakdown intimation', detail: 'Engineering breakdown maintenance intimation linked to batch stage.' },
    { key: 'ipqc', label: 'IPQC Test', icon: 'fas fa-vial', action: 'Send Sample to QC', detail: 'Stage specifications & results; sample, print label/TIS, send to QC in-process testing.' },
    { key: 'corrections', label: 'Corrections', icon: 'fas fa-pen-ruler', action: 'Review corrected entries', detail: 'View struck-through wrong values, corrections, and verification status (ALCOA+).' },
    { key: 'critical_params', label: 'Critical Params', icon: 'fas fa-exclamation-triangle', action: 'Record CPP observations', detail: 'Enter observed values against critical process parameters for this batch.' },
    { key: 'manpower', label: 'Manpower', icon: 'fas fa-users', action: 'Manpower deployment log', detail: 'Record who worked on which stage/activity with designation.' },
    { key: 'logbook', label: 'Machine Log', icon: 'fas fa-book', action: 'Equipment log books', detail: 'View equipment-wise log entries auto-fed from step equipment timestamps.' },
    { key: 'audit', label: 'Audit Trail', icon: 'fas fa-list-check', action: 'Batch audit trail', detail: 'Chronological log of batch actions, sign-offs and system events.' },
  ];

  rightPanelModal: string | null = null;
  logbookViewKey = '';
  logbookViewBook: EquipmentLogbook | null = null;

  equipmentPmByKey: Record<string, any> = {};
  execTabs: any = {};
  auditLog: any[] = [];
  deviations: any[] = [];
  incidents: any[] = [];
  breakdowns: any[] = [];
  samplings: any[] = [];

  showSamplingForm = false;
  samplingForm: any = {};
  /** Parked Inprocess Specification tests for current step (right panel). */
  stepIpqcPreview: any = { specs: [], results: [] };

  /** Inline full QMS form in right-panel tab (same components as /qa/qms/...). */
  inlineQmsForm: 'deviation' | 'incident' | 'breakdown' | null = null;
  qmsEmbedContext: EbmrQmsEmbedContext | null = null;

  qmsDevProdRemark: any = { id: 0, prod_head_continue: 'No', prod_head_remark: '' };
  qmsIncRemark: any = { id: 0, next_stage_remark: '' };

  departmentName = localStorage.getItem('department') || 'Production';

  // correction modal
  corrModalOpen = false;
  corrForm: any = {};

  /** Shown when batch id is missing / demo ensure failed */
  loadError = '';
  ensuringDemo = false; // unused — pipeline data is DB-seeded; no runtime demo create

  constructor(private service: DataAccessService, private router: Router, private route: ActivatedRoute, private esign: EsignService) {}

  ngOnInit(): void {
    this.route.paramMap.subscribe((pm) => {
      this.batchId = Number(pm.get('id') || 0);
      this.load();
    });
    this.route.queryParams.subscribe((p) => {
      const m = (p['mode'] || 'open') as typeof this.executionMode;
      if (['open', 'correction', 'checking', 'review', 'approval', 'final', 'html_record', 'pdf'].includes(m)) {
        this.executionMode = m;
      }
    });
  }

  setExecutionMode(mode: typeof this.executionMode): void {
    this.executionMode = mode;
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { mode },
      queryParamsHandling: 'merge',
    });
    if (!this.isHtmlDocMode) this.ensureSelectionForMode();
  }

  get isHtmlDocMode(): boolean {
    return this.executionMode === 'html_record' || this.executionMode === 'pdf';
  }

  get htmlRecordKind(): 'eBMR' | 'eBPR' {
    return this.batch?.record_type === 'eBPR' ? 'eBPR' : 'eBMR';
  }

  get recordDocLabel(): string {
    return this.htmlRecordKind === 'eBPR' ? 'Batch Packing Record' : 'Batch Manufacturing Record';
  }

  get finalRecordTabLabel(): string {
    return this.htmlRecordKind === 'eBPR' ? 'Final eBPR' : 'Final eBMR';
  }

  get htmlRecordTabLabel(): string {
    return this.htmlRecordKind === 'eBPR' ? 'HTML BPR' : 'HTML BMR';
  }

  private ensureSelectionForMode(): void {
    const steps = this.navSteps;
    if (!steps.length) {
      this.selected = { kind: 'header' };
      return;
    }
    const cur = this.currentStep;
    if (cur && steps.some((s: any) => s.id === cur.id)) {
      return;
    }
    let pick: any = null;
    if (this.executionMode === 'correction') {
      pick = steps.find((s: any) => s.status === 'Correction Required');
    } else if (this.executionMode === 'checking') {
      pick = steps.find((s: any) => {
        const stg = this.stageRecord(s.stage_seq);
        return stg?.status === 'Awaiting Check' || stg?.status === 'Awaiting QA Check';
      });
    } else if (this.executionMode === 'review') {
      pick = steps.find((s: any) => {
        const stg = this.stageRecord(s.stage_seq);
        return stg?.status === 'Checked' || stg?.status === 'Awaiting Approval' || s.status === 'Checked';
      });
    } else if (this.executionMode === 'approval') {
      pick = steps.find((s: any) => {
        const stg = this.stageRecord(s.stage_seq);
        return stg?.status === 'Awaiting Approval' || stg?.status === 'Awaiting QA Approval';
      });
    }
    if (!pick) pick = steps[0];
    this.ensureStepData(pick);
    this.selected = { kind: 'step', stepId: pick.id };
  }

  load(): void {
    if (!this.batchId) {
      this.loading = false;
      this.batch = null;
      this.loadError = 'Select a batch from Under Production. Pipeline BMRs are already seeded in the database.';
      return;
    }
    this.loading = true;
    this.loadError = '';
    this.service.get('master/ebmr_bpr.php?type=getBatch&id=' + this.batchId).subscribe({
      next: (r: any) => {
        try {
          if (r && r.status === 'success' && r.batch) {
            this.batch = r.batch;
            this.batch.header = this.batch.header || {};
            this.batch.static = this.batch.static || {};
            this.batch.static.product_approval = normalizeProductApprovalPage(
              this.batch.static.product_approval,
              this.batch.header
            );
            this.batch.static.safety = normalizeSafetyPrecautions(this.batch.static.safety);
            this.batch.static.general_instructions = normalizeGeneralInstructions(this.batch.static.general_instructions);
            this.batch.static.dispensing = normalizeDispensingPage(this.batch.static.dispensing);
            this.hydrateProductApprovalForExecution();
            this.refreshProductApprovalMasterFields();
            this.batch.yield = this.batch.yield || { final_pct: '', remark: '' };
            (this.batch.steps || []).forEach((s: any) => {
              s.template = s.template || {};
              s.data = s.data || {};
              s.corrections = s.corrections || [];
              this.ensureStepData(s);
            });
            this.execTabs = this.mergeDefaults(this.batch.exec_tabs || {}, this.emptyExecTabs());
            normalizeExecLogbook(this.execTabs);
            rebuildLogbookFromBatch(this.batch, this.execTabs);
            this.stageAccess = r.batch.stage_access || { mode: 'full' };
            this.batchStages = r.batch.batch_stages || [];
            this.equipmentPmByKey = (r.batch.equipment_pm && r.batch.equipment_pm.by_key) || {};
            if (this.isRestrictedView && this.canUseCheckerMode) {
              this.reviewMode = true;
            }
            // Always start on a safe selection so the page never renders a null step
            this.selected = { kind: 'header' };
            this.ensureRestrictedSelection();
            if (this.navSteps.length) {
              this.ensureSelectionForMode();
            }
            this.loadSamplings();
            this.loadError = '';
          } else {
            this.batch = null;
            this.loadError = (r && r.message) || 'Batch not found. Open Under Production and pick a seeded batch.';
          }
        } catch (e: any) {
          this.batch = null;
          this.loadError = 'Failed to render batch: ' + (e?.message || 'unknown error');
        } finally {
          this.loading = false;
        }
      },
      error: () => {
        this.loading = false;
        this.batch = null;
        this.loadError = 'Failed to load batch. Open Under Production and try Open again.';
      },
    });
  }

  /** Navigate to Under Production — pipeline data is already in DB (no demo create). */
  ensureDemoBatch(): void {
    const close = (this.route.snapshot.data?.['closeRoute'] as string) || '/fproduction/ebmr/under-production';
    this.router.navigateByUrl(close);
  }

  private executionNavBase(): string {
    const url = (this.router.url || '').split('?')[0];
    if (url.indexOf('/fproduction/ebmr/') === 0) {
      return '/fproduction/ebmr/execution';
    }
    if (url.indexOf('/packing/bpr/') === 0) {
      return '/packing/bpr/execution';
    }
    if (url.indexOf('/production/ebmr/') === 0) {
      return '/production/ebmr/execution';
    }
    return '/master/ebmr-bpr/execution';
  }

  private mergeDefaults(target: any, defaults: any): any {
    const out = target && typeof target === 'object' && !Array.isArray(target) ? target : {};
    for (const k of Object.keys(defaults)) {
      const dv = defaults[k];
      if (dv && typeof dv === 'object' && !Array.isArray(dv)) out[k] = this.mergeDefaults(out[k], dv);
      else if (out[k] === undefined || out[k] === null) out[k] = Array.isArray(dv) ? [] : dv;
    }
    return out;
  }

  emptyExecTabs(): any {
    return {
      critical_params: { rows: [] },
      manpower: { rows: [] },
      logbook: { rows: [], equipment_books: {} },
      breakdown: { rows: [] },
    };
  }

  ensureStepData(s: any): void {
    const d = s.data || {};
    d.line_clearance = d.line_clearance || {};
    d.lc_signoff = d.lc_signoff || { prod_by: '', prod_at: '', qa_by: '', qa_at: '' };
    d.dept_checks = d.dept_checks || {};
    d.qa_checks = d.qa_checks || {};
    d.inprocess = d.inprocess || {};
    d.ipqc = d.ipqc || {};
    d.holding = d.holding || { condition: '', duration: '', from: '', to: '' };
    d.step_timestamp = d.step_timestamp || { date: '', start_time: '', end_time: '', substeps: {} };
    if (!d.step_timestamp.substeps || typeof d.step_timestamp.substeps !== 'object') {
      d.step_timestamp.substeps = {};
    }
    const tsSubs = s.template?.step_timestamp?.substeps || [];
    (Array.isArray(tsSubs) ? tsSubs : []).forEach((ss: any, i: number) => {
      const key = String(ss?.id || i);
      if (!d.step_timestamp.substeps[key]) {
        d.step_timestamp.substeps[key] = { date: '', start_time: '', end_time: '' };
      }
    });
    d.equipment_usage = d.equipment_usage || {};
    d.yield = d.yield || {};
    d.yield_reconciliation = d.yield_reconciliation || emptyYieldReconciliation(s.template?.yield_reconciliation?.uom || 'kg');
    if (!d.yield_reconciliation.uom) {
      d.yield_reconciliation.uom = s.template?.yield_reconciliation?.uom || 'kg';
    }
    d.custom = d.custom || {};
    d.equipment_confirmed = d.equipment_confirmed || false;
    const tpl = s.template?.equipment || [];
    tpl.forEach((eq: any) => {
      const k = equipmentKey(eq);
      if (k && !d.equipment_usage[k]) d.equipment_usage[k] = emptyEquipmentUsage();
    });
    s.data = d;
  }

  equipmentUsageEntry(eq: any): any {
    const d = this.currentStep?.data;
    if (!d) return emptyEquipmentUsage();
    if (!d.equipment_usage) d.equipment_usage = {};
    const k = equipmentKey(eq);
    if (!d.equipment_usage[k]) d.equipment_usage[k] = emptyEquipmentUsage();
    return d.equipment_usage[k];
  }

  batchEquipmentList(): any[] {
    return collectBatchEquipment(this.batch);
  }

  equipmentKeyFor(eq: any): string {
    return equipmentKey(eq);
  }

  equipmentMetaFor(eq: any): { code: string; name: string; id: string; location: string } {
    return findEquipmentMeta(this.batch, equipmentKey(eq), eq);
  }

  /** PM status from Engineering equipment_maintenance (perform queue rules). */
  equipmentPmFor(eq: any): any | null {
    const k = equipmentKey(eq);
    if (!k) return null;
    if (this.equipmentPmByKey[k]) return this.equipmentPmByKey[k];
    const code = this.equipmentMetaFor(eq).code;
    return code && this.equipmentPmByKey[code] ? this.equipmentPmByKey[code] : null;
  }

  equipmentPmBlocked(eq: any): boolean {
    return !!this.equipmentPmFor(eq)?.blocked;
  }

  equipmentPmAlert(eq: any): boolean {
    return !!this.equipmentPmFor(eq)?.alert;
  }

  equipmentPmLabel(eq: any): string {
    const pm = this.equipmentPmFor(eq);
    if (!pm) return 'OK';
    if (pm.overdue) return 'PM overdue (' + pm.remaining_days + ' d)';
    if (pm.blocked) return 'PM due today';
    return 'PM due in ' + pm.remaining_days + ' d';
  }

  equipmentCalibrationLabel(eq: any): string {
    const raw = eq?.calibration_applicable ?? eq?.calibration_required ?? 'Not Applicable';
    const s = String(raw || '').trim().toLowerCase();
    if (s === 'applicable' || s === 'yes' || s === 'y' || s === '1' || s === 'true') return 'Applicable';
    return 'Not Applicable';
  }

  stageEquipmentList(stage: any): any[] {
    const map = new Map<string, any>();
    (stage?.steps || []).forEach((s: any) => {
      (s.template?.equipment || []).forEach((eq: any) => {
        const k = equipmentKey(eq);
        if (k && !map.has(k)) map.set(k, eq);
      });
    });
    return Array.from(map.values());
  }

  stagePmAlerts(stage: any): { eq: any; pm: any }[] {
    return this.stageEquipmentList(stage)
      .map((eq) => ({ eq, pm: this.equipmentPmFor(eq) }))
      .filter((x) => x.pm?.alert);
  }

  stageHasPmAlert(stage: any): boolean {
    return this.stagePmAlerts(stage).length > 0;
  }

  stageHasPmBlock(stage: any): boolean {
    return this.stagePmAlerts(stage).some((x) => x.pm?.blocked);
  }

  currentStepPmAlerts(): { eq: any; pm: any }[] {
    const s = this.currentStep;
    if (!s?.template?.equipment?.length) return [];
    return (s.template.equipment as any[])
      .map((eq) => ({ eq, pm: this.equipmentPmFor(eq) }))
      .filter((x) => x.pm?.alert);
  }

  currentStepPmBlocked(): boolean {
    return this.currentStepPmAlerts().some((x) => x.pm?.blocked);
  }

  batchHasPmAlert(): boolean {
    return this.batchEquipmentList().some((eq) => this.equipmentPmAlert(eq));
  }

  equipmentUsageDisabled(eq: any): boolean {
    return this.readOnly || this.equipmentPmBlocked(eq);
  }

  logbookEntryCount(key: string): number {
    const book = getEquipmentLogbook(this.execTabs, key, this.batch);
    return (book.entries || []).length;
  }

  openRightPanelModal(key: string): void {
    this.rightPanelModal = key;
    this.rightTab = key;
    this.closeInlineQmsForm();
    if (key === 'audit') this.loadAudit();
    if (key === 'deviations') {
      this.loadDeviations();
      if (!this.isFinalised) this.openQmsDevForm();
    }
    if (key === 'incidents') {
      this.loadIncidents();
      if (!this.isFinalised) this.openQmsIncForm();
    }
    if (key === 'breakdown') {
      this.loadBreakdowns();
      if (!this.readOnly && !this.isFinalised) this.openBreakdownForm();
    }
    if (key === 'ipqc') {
      this.loadSamplings();
      this.loadStepIpqcPreview();
    }
    if (key === 'logbook') {
      rebuildLogbookFromBatch(this.batch, this.execTabs);
    }
  }

  closeRightPanelModal(): void {
    this.rightPanelModal = null;
    this.closeInlineQmsForm();
  }

  rightPanelTitle(): string {
    const t = this.rightTabs.find((x) => x.key === this.rightPanelModal);
    return t ? t.label : '';
  }

  machineLogRows(): MachineLogRow[] {
    return buildMachineLogRows(this.batch);
  }

  openEquipmentLogbook(eq: any): void {
    const key = equipmentKey(eq);
    this.logbookViewKey = key;
    this.logbookViewBook = getEquipmentLogbook(this.execTabs, key, this.batch, eq);
    const stored = this.execTabs?.logbook?.equipment_books?.[key];
    if (stored) this.logbookViewBook = stored;
  }

  closeEquipmentLogbook(): void {
    this.logbookViewKey = '';
    this.logbookViewBook = null;
  }

  recordEquipmentUsage(eq: any): void {
    const s = this.currentStep;
    if (!s || this.readOnly) return;
    if (this.equipmentPmBlocked(eq)) {
      alertify.error('Equipment usage restricted — preventive maintenance is overdue or due: ' + this.equipmentPmLabel(eq));
      return;
    }
    const u = this.equipmentUsageEntry(eq);
    if (!hasEquipmentUsage(u)) {
      alertify.warning('Enter start date/time before recording');
      return;
    }
    if (!u.end_date) u.end_date = u.start_date;
    this.esign
      .request({
        meaning: 'Performed By',
        module: 'execution',
        recordRef: this.batchId,
        detail: 'Record equipment usage: ' + (eq.name || '') + ' · ' + (s.step_name || ''),
      })
      .then((sig) => {
        if (!sig) return;
        const who = sig.emp_name + ' (' + sig.emp_id + ')';
        u.user_sign = who;
        u.user_sign_at = sig.signed_at;
        syncStepEquipmentToLogbook(s, this.batch, this.execTabs, { name: who, at: sig.signed_at });
        this.saving = true;
        const payload = { step_id: s.id, batch_id: this.batchId, data: s.data, remarks: s.remarks || '' };
        this.service.post('master/ebmr_bpr.php?type=saveStepData', JSON.stringify(payload)).subscribe(() => {
          this.service
            .post('master/ebmr_bpr.php?type=saveExecTabs', JSON.stringify({ batch_id: this.batchId, exec_tabs: this.execTabs }))
            .subscribe(() => {
              this.saving = false;
              alertify.success('Equipment usage recorded in log book');
            });
        });
      });
  }

  /* ---------- grouping for left tree + progress ---------- */
  /** All steps shown in left rail / progress (not filtered by workflow tab). */
  get navSteps(): any[] {
    const steps = this.batch?.steps || [];
    if (!this.isRestrictedView) return steps;
    return steps.filter((s: any) => this.isStepVisible(s));
  }

  get groupedStages(): any[] {
    const groups: any[] = [];
    let cur: any = null;
    (this.navSteps || []).forEach((s: any) => {
      if (!cur || cur.stage_seq !== s.stage_seq || cur.stage_name !== s.stage_name) {
        cur = { stage_seq: s.stage_seq, stage_name: s.stage_name, stage_instructions: s.stage_instructions, steps: [] };
        groups.push(cur);
      }
      cur.steps.push(s);
    });
    return groups;
  }

  /** Steps highlighted/filtered for the active workflow tab (banner hints only). */
  get visibleSteps(): any[] {
    const steps = this.batch?.steps || [];
    switch (this.executionMode) {
      case 'correction':
        return steps.filter((s: any) => s.status === 'Correction Required');
      case 'checking':
        return steps.filter((s: any) => {
          const stg = this.stageRecord(s.stage_seq);
          return (
            stg?.status === 'Awaiting Check' ||
            stg?.status === 'Awaiting QA Check' ||
            (stg?.check_status === 'Approved' && s.status === 'Checked')
          );
        });
      case 'review':
        return steps.filter((s: any) => {
          const stg = this.stageRecord(s.stage_seq);
          return (
            s.status === 'Checked' ||
            s.status === 'Done' ||
            stg?.status === 'Checked' ||
            stg?.status === 'Awaiting Approval' ||
            stg?.status === 'Awaiting QA Approval'
          );
        });
      case 'approval':
        return steps.filter((s: any) => {
          const stg = this.stageRecord(s.stage_seq);
          return stg?.status === 'Awaiting Approval' || stg?.status === 'Awaiting QA Approval' || stg?.status === 'Approved';
        });
      case 'final':
      case 'html_record':
      case 'pdf':
        return steps;
      default:
        return steps;
    }
  }

  get correctionSteps(): any[] {
    return (this.batch?.steps || []).filter((s: any) => s.status === 'Correction Required');
  }

  get checkingSteps(): any[] {
    return (this.batch?.steps || []).filter((s: any) => {
      const stg = this.stageRecord(s.stage_seq);
      return s.status === 'Done' || stg?.status === 'Awaiting Check';
    });
  }

  stageRecord(stageSeq: number): any {
    return (this.batchStages || []).find((s: any) => Number(s.stage_seq) === Number(stageSeq));
  }

  canSendStageForChecking(): boolean {
    const s = this.currentStep;
    if (!s || this.executionMode !== 'open') return false;
    const stg = this.stageRecord(s.stage_seq);
    const blocked = ['Awaiting Check', 'Awaiting Approval', 'Awaiting QA Check', 'Awaiting QA Approval', 'Approved'];
    if (!stg || blocked.includes(stg.status)) return false;
    const stageSteps = (this.batch?.steps || []).filter((x: any) => x.stage_seq === s.stage_seq);
    return stageSteps.length > 0 && stageSteps.every((x: any) => x.status === 'Done' || x.status === 'Checked');
  }

  sendStageForChecking(): void {
    const s = this.currentStep;
    if (!s) return;
    this.esign
      .request({ meaning: 'Doer / Performed By', module: 'execution', recordRef: this.batchId, detail: 'Send stage for checking: ' + s.stage_name })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post(
            'master/ebmr_bpr.php?type=sendStageForChecking',
            JSON.stringify({
              batch_id: this.batchId,
              stage_seq: s.stage_seq,
              signer_name: sig.emp_name,
              signer_designation: sig.designation || '',
            })
          )
          .subscribe((r: any) => {
            if (r?.status === 'success') {
              alertify.success('Stage sent for checking');
              this.load();
            } else alertify.error(r?.message || 'Failed');
          });
      });
  }

  checkStageAction(action: 'Approved' | 'Rejected'): void {
    const s = this.currentStep;
    if (!s) return;
    this.esign
      .request({ meaning: 'Checked By', module: 'execution', recordRef: this.batchId, detail: 'Stage check ' + action + ': ' + s.stage_name })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post(
            'master/ebmr_bpr.php?type=checkStage',
            JSON.stringify({
              batch_id: this.batchId,
              stage_seq: s.stage_seq,
              action,
              remark: this.stageCheckRemark,
              signer_name: sig.emp_name,
              signer_designation: sig.designation || '',
            })
          )
          .subscribe((r: any) => {
            if (r?.status === 'success') {
              alertify.success(action === 'Approved' ? 'Stage checked' : 'Stage rejected — sent for correction');
              this.stageCheckRemark = '';
              this.load();
            } else alertify.error(r?.message || 'Failed');
          });
      });
  }

  approveStageAction(action: 'Approved' | 'Rejected'): void {
    const s = this.currentStep;
    if (!s) return;
    this.esign
      .request({ meaning: 'Final Approver', module: 'execution', recordRef: this.batchId, detail: 'Stage approval ' + action })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post(
            'master/ebmr_bpr.php?type=approveStage',
            JSON.stringify({
              batch_id: this.batchId,
              stage_seq: s.stage_seq,
              action,
              remark: this.stageApproveRemark,
              signer_name: sig.emp_name,
              signer_designation: sig.designation || '',
            })
          )
          .subscribe((r: any) => {
            if (r?.status === 'success') {
              alertify.success('Stage approval recorded');
              this.stageApproveRemark = '';
              this.load();
            } else alertify.error(r?.message || 'Failed');
          });
      });
  }

  get currentStageRecord(): any {
    const s = this.currentStep;
    return s ? this.stageRecord(s.stage_seq) : null;
  }

  get isQaStageAction(): boolean {
    const stg = this.currentStageRecord;
    if (!stg) return false;
    return stg.status === 'Awaiting QA Check' || stg.status === 'Awaiting QA Approval';
  }

  canCheckCurrentStage(): boolean {
    const stg = this.currentStageRecord;
    if (!stg || this.executionMode !== 'checking') return false;
    return stg.status === 'Awaiting Check' || stg.status === 'Awaiting QA Check';
  }

  canApproveCurrentStage(): boolean {
    const stg = this.currentStageRecord;
    if (!stg || this.executionMode !== 'approval') return false;
    return stg.status === 'Awaiting Approval' || stg.status === 'Awaiting QA Approval';
  }

  stageStatusClass(stg: any): string {
    const s = (stg?.status || '').toLowerCase();
    if (s === 'approved' || s === 'checked') return 'eb-badge-ok';
    if (s === 'correction required' || s === 'rejected') return 'eb-badge-crit';
    if (s.includes('awaiting')) return 'eb-badge-type';
    return 'eb-badge-warn';
  }

  /** Format digital signature date/time for display */
  formatSignDate(v: string | null | undefined): string {
    if (!v) return '—';
    const d = new Date(String(v).replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(v);
    const dd = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const mon = months[d.getMonth()];
    const yyyy = d.getFullYear();
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    const ss = String(d.getSeconds()).padStart(2, '0');
    return `${dd}-${mon}-${yyyy} ${hh}:${mm}:${ss}`;
  }

  private pad2(n: number): string {
    return String(n).padStart(2, '0');
  }

  /** Local date YYYY-MM-DD for process-time stamp. */
  nowStampDate(): string {
    const d = new Date();
    return `${d.getFullYear()}-${this.pad2(d.getMonth() + 1)}-${this.pad2(d.getDate())}`;
  }

  /** Local time HH:mm for process-time stamp. */
  nowStampTime(): string {
    const d = new Date();
    return `${this.pad2(d.getHours())}:${this.pad2(d.getMinutes())}`;
  }

  timestampSubsteps(): any[] {
    const list = this.currentStep?.template?.step_timestamp?.substeps;
    return Array.isArray(list) ? list.filter((ss: any) => !!(ss && (ss.name || '').trim())) : [];
  }

  timestampSubEntry(ss: any, index: number): any {
    this.ensureStepData(this.currentStep);
    const key = String(ss?.id || index);
    const bag = this.currentStep.data.step_timestamp.substeps;
    if (!bag[key]) bag[key] = { date: '', start_time: '', end_time: '' };
    return bag[key];
  }

  stampStepStart(): void {
    const s = this.currentStep;
    if (!s || this.readOnly) return;
    this.ensureStepData(s);
    const ts = s.data.step_timestamp;
    ts.date = this.nowStampDate();
    ts.start_time = this.nowStampTime();
    ts.end_time = '';
    this.syncCurrentStepToLogbook();
    this.persistCurrentStep(() => alertify.success('Step start time captured'));
  }

  stampStepEnd(): void {
    const s = this.currentStep;
    if (!s || this.readOnly) return;
    this.ensureStepData(s);
    const ts = s.data.step_timestamp;
    if (!ts.start_time) {
      alertify.warning('Press Start before End');
      return;
    }
    if (!ts.date) ts.date = this.nowStampDate();
    ts.end_time = this.nowStampTime();
    this.syncCurrentStepToLogbook();
    this.persistCurrentStep(() => alertify.success('Step end time captured'));
  }

  /** Push step equipment + timestamp into Machine Log (exec_tabs). */
  private syncCurrentStepToLogbook(): void {
    const s = this.currentStep;
    if (!s || !(s.template?.equipment || []).length) return;
    syncStepEquipmentToLogbook(s, this.batch, this.execTabs);
    this.service
      .post('master/ebmr_bpr.php?type=saveExecTabs', JSON.stringify({ batch_id: this.batchId, exec_tabs: this.execTabs }))
      .subscribe();
  }

  stampSubstepStart(ss: any, index: number): void {
    if (!this.currentStep || this.readOnly) return;
    const entry = this.timestampSubEntry(ss, index);
    entry.date = this.nowStampDate();
    entry.start_time = this.nowStampTime();
    entry.end_time = '';
    this.persistCurrentStep(() => alertify.success('Sub-step start time captured'));
  }

  stampSubstepEnd(ss: any, index: number): void {
    if (!this.currentStep || this.readOnly) return;
    const entry = this.timestampSubEntry(ss, index);
    if (!entry.start_time) {
      alertify.warning('Press Start before End');
      return;
    }
    if (!entry.date) entry.date = this.nowStampDate();
    entry.end_time = this.nowStampTime();
    this.persistCurrentStep(() => alertify.success('Sub-step end time captured'));
  }

  private stepTimestampIncomplete(s: any): string | null {
    const tpl = s?.template?.step_timestamp;
    if (!tpl?.enabled) return null;
    const ts = s?.data?.step_timestamp || {};
    if (!ts.date || !ts.start_time || !ts.end_time) {
      return 'Capture Date, Start Time and End Time for this step (use Start / End buttons)';
    }
    const subs = Array.isArray(tpl.substeps) ? tpl.substeps.filter((x: any) => !!(x && (x.name || '').trim())) : [];
    for (let i = 0; i < subs.length; i++) {
      const key = String(subs[i].id || i);
      const e = (ts.substeps && ts.substeps[key]) || {};
      if (!e.date || !e.start_time || !e.end_time) {
        return 'Capture Date, Start Time and End Time for sub-step: ' + (subs[i].name || (i + 1));
      }
    }
    return null;
  }

  qaCheckStageAction(action: 'Approved' | 'Rejected'): void {
    const s = this.currentStep;
    if (!s) return;
    this.esign
      .request({ meaning: 'QA Checked By', module: 'execution', recordRef: this.batchId, detail: 'QA stage check ' + action })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post(
            'master/ebmr_bpr.php?type=qaCheckStage',
            JSON.stringify({ batch_id: this.batchId, stage_seq: s.stage_seq, action, remark: this.stageCheckRemark })
          )
          .subscribe((r: any) => {
            if (r?.status === 'success') {
              alertify.success('QA check recorded');
              this.stageCheckRemark = '';
              this.load();
            } else alertify.error(r?.message || 'Failed');
          });
      });
  }

  qaApproveStageAction(action: 'Approved' | 'Rejected'): void {
    const s = this.currentStep;
    if (!s) return;
    this.esign
      .request({ meaning: 'QA Approved By', module: 'execution', recordRef: this.batchId, detail: 'QA stage approval ' + action })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post(
            'master/ebmr_bpr.php?type=qaApproveStage',
            JSON.stringify({ batch_id: this.batchId, stage_seq: s.stage_seq, action, remark: this.stageApproveRemark })
          )
          .subscribe((r: any) => {
            if (r?.status === 'success') {
              alertify.success('QA approval recorded');
              this.stageApproveRemark = '';
              this.load();
            } else alertify.error(r?.message || 'Failed');
          });
      });
  }

  get activeStageIndex(): number {
    if (this.selected.kind === 'stage' && this.selected.stageIndex != null) {
      return this.selected.stageIndex;
    }
    if (this.selected.kind !== 'step' || this.selected.stepId == null) return -1;
    const groups = this.groupedStages;
    const step = this.currentStep;
    if (!step) return -1;
    return groups.findIndex((g) => g.steps.some((s: any) => s.id === step.id));
  }

  goStageByIndex(i: number): void {
    const g = this.groupedStages[i];
    if (!g) return;
    this.selected = { kind: 'stage', stageIndex: i };
  }

  executionStageStepRows(stageIndex: number): StageStepIndexRow[] {
    const g = this.groupedStages[stageIndex];
    if (!g) return [];
    return buildStageStepIndexRows(g.stage_name || 'Stage', g.steps || [], {
      stageIndex,
      mode: 'execution',
    });
  }

  onExecStepOpen(row: StageStepIndexRow): void {
    const step = row.stepRef;
    if (step) this.selectStep(step);
  }

  onExecStepView(row: StageStepIndexRow): void {
    const step = row.stepRef;
    if (step) this.selectStep(step);
  }

  selectHeader(): void { this.selected = { kind: 'header' }; }

  get currentStep(): any {
    if (this.selected.kind !== 'step' || this.selected.stepId == null || !this.batch) return null;
    const want = Number(this.selected.stepId);
    return (this.batch.steps || []).find((s: any) => Number(s.id) === want) || null;
  }

  stepCheckpointSequence(): string[] {
    return resolveExecutionCheckpointSequence(this.currentStep?.template);
  }

  checkpointVisible(key: string): boolean {
    return checkpointActiveOnTemplate(key, this.currentStep?.template);
  }

  get currentStageInstructions(): string {
    const s = this.currentStep;
    return s ? s.stage_instructions || '' : '';
  }

  selectStep(s: any): void {
    if (this.isRestrictedView && !this.isStepVisible(s)) {
      alertify.error('This stage is not visible for your allocation');
      return;
    }
    this.ensureStepData(s);
    this.selected = { kind: 'step', stepId: s.id };
    if (this.stepRequiresIpqc(s) || this.rightPanelModal === 'ipqc') {
      this.loadStepIpqcPreview();
    }
  }
  selectYield(): void {
    if (this.isRestrictedView) return;
    this.selected = { kind: 'yield' };
  }
  selectStatus(): void {
    if (this.isRestrictedView) return;
    this.selected = { kind: 'status' };
  }
  selectStatic(key: string): void {
    if (this.isRestrictedView) return;
    this.selected = { kind: 'static', staticKey: key };
  }

  get isRestrictedView(): boolean {
    return this.stageAccess?.mode === 'restricted';
  }

  isStepVisible(step: any): boolean {
    if (!this.isRestrictedView) return true;
    const seq = Number(step?.stage_seq);
    return (this.stageAccess?.visible_stage_seqs || []).some((v: number) => Number(v) === seq);
  }

  isStageReadOnly(stageSeq: number): boolean {
    if (!this.isRestrictedView) return false;
    return (this.stageAccess?.read_only_stage_seqs || []).some((v: number) => Number(v) === Number(stageSeq));
  }

  get isPreviousStageReadOnly(): boolean {
    const s = this.currentStep;
    return s ? this.isStageReadOnly(Number(s.stage_seq)) : false;
  }

  get canUseCheckerMode(): boolean {
    if (!this.isRestrictedView) return true;
    const s = this.currentStep;
    if (!s) return false;
    const seq = Number(s.stage_seq);
    const roles = this.rolesForStage(seq);
    return roles.some((r) => ['office', 'alt_officer', 'reviewer', 'approver'].includes(r));
  }

  rolesForStage(stageSeq: number): string[] {
    const roles = this.stageAccess?.allocated_roles || {};
    return roles[stageSeq] || roles[String(stageSeq)] || [];
  }

  get restrictedBannerText(): string {
    const editable = (this.stageAccess?.editable_stage_seqs || []).join(', ');
    const readOnly = (this.stageAccess?.read_only_stage_seqs || []).join(', ');
    const rolesMap = this.stageAccess?.allocated_roles || {};
    const roleBits: string[] = [];
    Object.keys(rolesMap).forEach((k) => {
      const roles = rolesMap[k] || [];
      if (roles.length) roleBits.push('Stage ' + k + ' (' + roles.join(', ') + ')');
    });
    let text = 'You are viewing only stages allocated to your login';
    if (editable) text += ' — editable stage(s): ' + editable;
    if (readOnly) text += '; previous stage(s) ' + readOnly + ' are read-only for reference';
    if (roleBits.length) text += '. Roles: ' + roleBits.join('; ');
    return text;
  }

  ensureRestrictedSelection(): void {
    if (!this.isRestrictedView) return;
    const editable = (this.stageAccess?.editable_stage_seqs || []).map((v: number) => Number(v));
    const step = (this.batch?.steps || []).find((s: any) => editable.includes(Number(s.stage_seq)));
    if (step) {
      this.ensureStepData(step);
      this.selected = { kind: 'step', stepId: step.id };
    } else {
      this.selected = { kind: 'header' };
    }
  }
  staticLabel(key: string): string {
    return (this.staticPages.find((p) => p.key === key) || { label: key }).label;
  }

  get safetyContent(): SafetyPrecautionsContent {
    return normalizeSafetyPrecautions(this.batch?.static?.safety);
  }

  get generalInstructionsContent(): GeneralInstructionsContent {
    return normalizeGeneralInstructions(this.batch?.static?.general_instructions);
  }

  get dispensingPage(): DispensingPage {
    return normalizeDispensingPage(this.batch?.static?.dispensing);
  }

  get dispensingStoreRecords(): DispensingMaterialRow[] {
    return this.dispensingPage.store_records || [];
  }

  get dispensingMaterials() {
    return this.dispensingPage.materials || [];
  }

  get bmrNo(): string {
    const h = this.batch?.header || {};
    return (h.bmr_no || this.batch?.profile_code || '—').toString();
  }

  get bmrProductName(): string {
    return this.batch?.product_name || this.batch?.product_code || this.batch?.header?.product_name || '—';
  }

  get batchSizeDisplay(): string {
    const parts = [this.batch?.batch_size, this.batch?.batch_size_uom].filter((v) => v != null && String(v).trim() !== '');
    return parts.length ? parts.join(' ') : '—';
  }

  formatBmrDate(value: string | null | undefined): string {
    if (!value) return '—';
    const d = String(value).substring(0, 10);
    if (!/^\d{4}-\d{2}-\d{2}$/.test(d)) return value;
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
  }

  dispensingEvalLabel(value: string | undefined): string {
    return evaluationParameterLabel(value);
  }

  isGeneralInstructionSection(section: DispensingSection): boolean {
    return (section?.heading || '').trim() === 'General Instructions';
  }

  /** Product approval page with batch-level overrides for execution view. */
  get productApprovalPage(): ProductApprovalPage {
    return this.batch?.static?.product_approval || ({} as ProductApprovalPage);
  }

  private hydrateProductApprovalForExecution(): void {
    const pap = this.batch?.static?.product_approval;
    if (!pap || !this.batch) return;
    if (!pap.product_code && this.batch.product_code) pap.product_code = this.batch.product_code;
    const actual = [this.batch.batch_size, this.batch.batch_size_uom].filter(Boolean).join(' ').trim();
    if (!pap.actual_batch_size && actual) pap.actual_batch_size = actual;
    if (!pap.mfg_date && this.batch.mfg_date) pap.mfg_date = this.batch.mfg_date;
    if (!pap.expiry_date && this.batch.exp_date) pap.expiry_date = this.batch.exp_date;
  }

  private refreshProductApprovalMasterFields(): void {
    const pap = this.batch?.static?.product_approval;
    if (!pap) return;
    const code = (this.batch.product_code || '').trim();
    if (!code) {
      applyMasterLockedFields(pap, this.batch.header || {});
      return;
    }
    this.service.get('master/ebmr_bpr.php?type=getProductForBmrPrep&product_code=' + encodeURIComponent(code)).subscribe({
      next: (r: any) => {
        if (r?.status === 'success' && r.product) {
          applyMasterLockedFields(pap, r.product);
        } else {
          applyMasterLockedFields(pap, this.batch.header || {});
        }
      },
      error: () => applyMasterLockedFields(pap, this.batch.header || {}),
    });
  }

  onProductApprovalChange(page: ProductApprovalPage): void {
    if (this.batch?.static) this.batch.static.product_approval = page;
  }

  saveProductApproval(): void {
    if (this.readOnly) return;
    this.esign
      .request({ meaning: 'Prepared By', module: 'execution', recordRef: this.batchId, detail: 'Save Product Approval Page' })
      .then((sig) => {
        if (!sig) return;
        this.saving = true;
        this.service
          .post('master/ebmr_bpr.php?type=saveBatchStatic', JSON.stringify({ batch_id: this.batchId, static: this.batch.static }))
          .subscribe({
            next: (r: any) => {
              this.saving = false;
              if (r?.status === 'success') alertify.success('Product Approval Page saved');
              else alertify.error((r && r.message) || 'Save failed');
            },
            error: () => {
              this.saving = false;
              alertify.error('Save failed');
            },
          });
      });
  }

  stepStatusIcon(s: any): string {
    if (s.status === 'Checked') return 'fas fa-circle-check';
    if (s.status === 'Done') return 'fas fa-circle-half-stroke';
    if (s.status === 'Correction Required') return 'fas fa-circle-exclamation';
    return 'far fa-circle';
  }
  stepStatusClass(s: any): string {
    if (s.status === 'Checked') return 'checked';
    if (s.status === 'Done') return 'done';
    if (s.status === 'Correction Required') return 'correction';
    return 'pending';
  }

  /* ---------- progress ---------- */
  get totalSteps(): number { return (this.batch?.steps || []).length; }
  get checkedSteps(): number { return (this.batch?.steps || []).filter((s: any) => s.status === 'Checked').length; }
  get progressPct(): number { return this.totalSteps ? Math.round((this.checkedSteps / this.totalSteps) * 100) : 0; }
  get openCorrectionCount(): number {
    let n = 0;
    (this.batch?.steps || []).forEach((s: any) => (s.corrections || []).forEach((c: any) => { if (c.status !== 'Verified') n++; }));
    return n;
  }

  /* ---------- in-process pass/fail evaluation ---------- */
  evaluate(check: any, value: any): string {
    if (value === '' || value === null || value === undefined) return '';
    const num = parseFloat(value);
    const min = parseFloat(check.min_limit);
    const max = parseFloat(check.max_limit);
    const target = parseFloat(check.target_value);
    const tol = parseFloat(check.tolerance);
    switch (check.check_type) {
      case 'numeric':
        if (!isNaN(target) && !isNaN(tol)) return Math.abs(num - target) <= tol ? 'Pass' : 'Fail';
        if (!isNaN(target)) return num === target ? 'Pass' : 'Fail';
        return '';
      case 'range':
        if (isNaN(num)) return 'Fail';
        return (isNaN(min) || num >= min) && (isNaN(max) || num <= max) ? 'Pass' : 'Fail';
      case 'min':
        return !isNaN(num) && (isNaN(min) || num >= min) ? 'Pass' : 'Fail';
      case 'max':
        return !isNaN(num) && (isNaN(max) || num <= max) ? 'Pass' : 'Fail';
      case 'boolean':
        return value === 'Pass' || value === 'Yes' ? 'Pass' : 'Fail';
      default:
        return '';
    }
  }
  /** Canonical performed-by: Production | QA | Both | Alternate */
  ipcRoleMode(check: any): 'Production' | 'QA' | 'Both' | 'Alternate' {
    const v = String(check?.responsibility || check?.role || 'Production').toLowerCase();
    if (v.includes('alternate') || v.includes('alternet')) return 'Alternate';
    if (v === 'both' || v.includes('production & qa') || v.includes('production and qa')) return 'Both';
    if (v === 'qa' || v === 'quality' || v === 'ipqa' || v.includes('quality assurance')) return 'QA';
    return 'Production';
  }

  isQaDept(): boolean {
    const d = String(this.departmentName || '').toLowerCase();
    return d.includes('qa') || d.includes('quality') || d.includes('ipqa');
  }

  /** Actor role for the logged-in department (Production or QA). */
  ipcActorRole(): 'Production' | 'QA' {
    return this.isQaDept() ? 'QA' : 'Production';
  }

  private ipcObserverLabel(): string {
    const name = localStorage.getItem('username') || localStorage.getItem('firstname') || localStorage.getItem('user') || '';
    const emp = localStorage.getItem('emp_id') || '';
    if (name && emp) return name + ' (' + emp + ')';
    return name || emp || '';
  }

  private ipcNowStamp(): string {
    const d = new Date();
    const p = (n: number) => (n < 10 ? '0' + n : '' + n);
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
  }

  /** Normalize stored inprocess[checkId] to { entries: [...] }; migrates legacy { value, result }. */
  ensureIpcBucket(check: any): { entries: any[] } {
    if (!this.currentStep) return { entries: [] };
    this.ensureStepData(this.currentStep);
    const d = this.currentStep.data.inprocess;
    const id = check.id;
    const cur = d[id];
    if (!cur) {
      d[id] = { entries: [] };
      return d[id];
    }
    if (Array.isArray(cur.entries)) return cur;
    const hasLegacy = cur.value !== undefined || cur.result !== undefined;
    if (hasLegacy && (String(cur.value || '').trim() !== '' || String(cur.result || '').trim() !== '')) {
      const mode = this.ipcRoleMode(check);
      const role = mode === 'QA' ? 'QA' : 'Production';
      d[id] = {
        entries: [{
          seq: 1,
          role,
          value: cur.value || '',
          result: cur.result || this.evaluate(check, cur.value || ''),
          observed_at: cur.observed_at || '',
          observed_by: cur.observed_by || '',
        }],
      };
      return d[id];
    }
    d[id] = { entries: [] };
    return d[id];
  }

  ipcEntries(check: any): any[] {
    return this.ensureIpcBucket(check).entries;
  }

  /** Next required role for Alternate; for others the actor role when they may add. */
  nextIpcRole(check: any): 'Production' | 'QA' | null {
    const mode = this.ipcRoleMode(check);
    const entries = this.ipcEntries(check);
    if (mode === 'Alternate') {
      if (!entries.length) return 'Production';
      const last = String(entries[entries.length - 1].role || 'Production');
      return last === 'QA' ? 'Production' : 'QA';
    }
    if (mode === 'Production') return 'Production';
    if (mode === 'QA') return 'QA';
    return this.ipcActorRole();
  }

  canAddIpcEntry(check: any): boolean {
    if (this.readOnly || this.isPreviousStageReadOnly) return false;
    const mode = this.ipcRoleMode(check);
    const actor = this.ipcActorRole();
    if (mode === 'Production') return actor === 'Production';
    if (mode === 'QA') return actor === 'QA';
    if (mode === 'Both') return actor === 'Production' || actor === 'QA';
    // Alternate: only the next role may add
    return this.nextIpcRole(check) === actor;
  }

  canEditIpcEntry(entry: any): boolean {
    if (this.readOnly || this.isPreviousStageReadOnly) return false;
    return String(entry?.role || '') === this.ipcActorRole();
  }

  addIpcEntry(check: any): void {
    if (!this.canAddIpcEntry(check)) return;
    const bucket = this.ensureIpcBucket(check);
    const role = this.nextIpcRole(check) || this.ipcActorRole();
    bucket.entries.push({
      seq: bucket.entries.length + 1,
      role,
      value: '',
      result: '',
      observed_at: this.ipcNowStamp(),
      observed_by: this.ipcObserverLabel(),
    });
  }

  onIpcEntryChange(check: any, index: number): void {
    const entries = this.ipcEntries(check);
    const e = entries[index];
    if (!e) return;
    e.result = this.evaluate(check, e.value);
  }

  /** @deprecated legacy single-value accessor — prefer ipcEntries */
  onIpcChange(check: any): void {
    const entries = this.ipcEntries(check);
    if (!entries.length) this.addIpcEntry(check);
    const e = this.ipcEntries(check)[0];
    if (e) e.result = this.evaluate(check, e.value);
  }
  ipcEntry(check: any): any {
    const entries = this.ipcEntries(check);
    if (!entries.length) {
      return { value: '', result: '' };
    }
    return entries[entries.length - 1];
  }
  cpEntry(group: string, cp: any): any {
    const d = this.currentStep.data[group];
    if (!d[cp.id]) d[cp.id] = { response: '', remark: '' };
    return d[cp.id];
  }

  /* ---------- line clearance: dual sign-off (Production + IPQA) ---------- */
  lcSignoff(): any {
    const s = this.currentStep;
    if (!s) return {};
    if (!s.data.lc_signoff) s.data.lc_signoff = { prod_by: '', prod_at: '', qa_by: '', qa_at: '' };
    return s.data.lc_signoff;
  }
  lcAllAnswered(): boolean {
    const s = this.currentStep;
    if (!s) return false;
    const cps = s.template.line_clearance || [];
    return cps.length > 0 && cps.every((cp: any) => {
      const e = (s.data.line_clearance || {})[cp.id];
      return e && e.response;
    });
  }
  signLineClearance(role: 'prod' | 'qa'): void {
    const s = this.currentStep;
    if (!s) return;
    if (!this.lcAllAnswered()) { alertify.error('Record a response for every line-clearance checkpoint before signing'); return; }
    const prod = role === 'prod';
    this.esign
      .request({
        meaning: prod ? 'Performed By' : 'Checked By',
        module: 'execution',
        recordRef: this.batchId,
        detail: 'Line Clearance ' + (prod ? '(Production)' : '(IPQA)') + ' — ' + (s.stage_name || '') + ' / ' + (s.step_name || ''),
        title: prod ? 'Line Clearance — Production' : 'Line Clearance — IPQA',
        confirmLabel: 'Sign Line Clearance',
      })
      .then((sig) => {
        if (!sig) return;
        const lc = this.lcSignoff();
        const who = sig.emp_name + ' (' + sig.emp_id + ')';
        if (prod) { lc.prod_by = who; lc.prod_at = sig.signed_at; }
        else { lc.qa_by = who; lc.qa_at = sig.signed_at; }
        const s = this.currentStep;
        if (!s) return;
        const payload = this.stepSavePayload(s, sig);
        this.service.post('master/ebmr_bpr.php?type=saveStepData', JSON.stringify(payload)).subscribe({
          next: (r: any) => {
            if (r?.status === 'success') alertify.success('Line clearance signed by ' + (prod ? 'Production' : 'IPQA'));
            else alertify.error(r?.message || 'Save failed');
          },
          error: () => alertify.error('Network error while saving line clearance'),
        });
      });
  }
  private persistCurrentStep(onDone?: () => void): void {
    const s = this.currentStep;
    if (!s) return;
    const payload = { step_id: s.id, batch_id: this.batchId, data: s.data, remarks: s.remarks || '' };
    this.service.post('master/ebmr_bpr.php?type=saveStepData', JSON.stringify(payload)).subscribe({
      next: (r: any) => {
        if (r?.status === 'success') {
          if (onDone) onDone();
        } else {
          alertify.error((r && r.message) || 'Auto-save failed');
        }
      },
      error: () => alertify.error('Network error while saving step'),
    });
  }

  /** Shared signed payload for step mutations */
  private stepSavePayload(s: any, sig: any): any {
    return {
      step_id: s.id,
      batch_id: this.batchId,
      data: s.data,
      remarks: s.remarks || '',
      signature_token: sig?.signature_token || '',
      esign_id: sig?.esign_id || 0,
    };
  }

  /** Client validation before save / complete */
  validateStepForms(s: any, forComplete: boolean): string | null {
    if (!s) return 'No step selected';
    const ipc = s.template?.inprocess_checks || [];
    for (const c of ipc) {
      const entries = this.ipcEntries(c);
      for (const e of entries) {
        if (c.check_type === 'numeric' || c.check_type === 'range' || c.check_type === 'min' || c.check_type === 'max') {
          if (e.value !== '' && e.value != null && isNaN(Number(e.value))) {
            return (c.check_name || 'In-process check') + ': observed value must be numeric';
          }
        }
      }
      if (forComplete && (!entries.length || !entries.some((e: any) => String(e.value || '').trim() !== ''))) {
        return 'Record at least one observation for: ' + (c.check_name || 'in-process check');
      }
    }
    if (forComplete) {
      const tsMsg = this.stepTimestampIncomplete(s);
      if (tsMsg) return tsMsg;
      if ((s.template?.line_clearance || []).length) {
        const lc = (s.data && s.data.lc_signoff) || {};
        if (!lc.prod_by || !lc.qa_by) {
          return 'Line clearance must be signed by BOTH Production and IPQA';
        }
        for (const cp of s.template.line_clearance) {
          const e = (s.data?.line_clearance || {})[cp.id];
          if (!e?.response) return 'Answer all line-clearance checkpoints';
        }
      }
      for (const cp of s.template?.dept_checks || []) {
        const e = (s.data?.dept_checks || {})[cp.id];
        if (!e?.response) return 'Answer all department checkpoints';
      }
      for (const cp of s.template?.qa_checks || []) {
        const e = (s.data?.qa_checks || {})[cp.id];
        if (!e?.response) return 'Answer all QA checkpoints';
      }
      if (this.stepRequiresIpqc(s) && !this.stepIpqcApproved(s)) {
        return 'Wait for QC to release IPQC results before completing';
      }
    }
    return null;
  }

  saveStep(): void {
    const s = this.currentStep;
    if (!s) return;
    const v = this.validateStepForms(s, false);
    if (v) {
      alertify.error(v);
      return;
    }
    this.esign
      .request({ meaning: 'Performed By', module: 'execution', recordRef: this.batchId, detail: 'Save step data: ' + (s.stage_name || '') + ' / ' + (s.step_name || '') })
      .then((sig) => {
        if (!sig) return;
        this.saving = true;
        const payload = this.stepSavePayload(s, sig);
        this.service.post('master/ebmr_bpr.php?type=saveStepData', JSON.stringify(payload)).subscribe({
          next: (r: any) => {
            syncStepEquipmentToLogbook(s, this.batch, this.execTabs, {
              name: sig.emp_name + ' (' + sig.emp_id + ')',
              at: sig.signed_at,
            });
            this.service
              .post('master/ebmr_bpr.php?type=saveExecTabs', JSON.stringify({ batch_id: this.batchId, exec_tabs: this.execTabs }))
              .subscribe({ error: () => {} });
            this.saving = false;
            if (r && r.status === 'success') alertify.success('Saved');
            else alertify.error((r && r.message) || 'Save failed');
          },
          error: () => {
            this.saving = false;
            alertify.error('Network error — step not saved');
          },
        });
      });
  }
  ipqcEntry(spec: any): any {
    const d = this.currentStep.data.ipqc;
    if (!d[spec.id]) d[spec.id] = { value: '', result: '' };
    return d[spec.id];
  }
  yieldEntry(i: number): any {
    const d = this.currentStep.data.yield;
    if (!d[i]) d[i] = { actual: '', pct: '' };
    return d[i];
  }
  onYieldChange(i: number, row: any): void {
    const e = this.yieldEntry(i);
    const th = parseFloat(row.theoretical);
    const ac = parseFloat(e.actual);
    e.pct = !isNaN(th) && th !== 0 && !isNaN(ac) ? ((ac / th) * 100).toFixed(2) : '';
  }

  yieldReconData(): any {
    if (!this.currentStep) return emptyYieldReconciliation();
    this.ensureStepData(this.currentStep);
    return this.currentStep.data.yield_reconciliation;
  }

  yieldReconCalc(): any {
    return calcYieldReconciliation(this.yieldReconData());
  }

  formatYieldReconNum(v: number | null | undefined): string {
    return formatYieldNum(v);
  }

  isYieldBalanceOff(): boolean {
    const b = this.yieldReconCalc().balance;
    return b != null && Math.abs(b) > 0.01;
  }

  onYieldReconChange(): void {
    // Trigger recalculation via change detection; values stay on data object.
  }

  limitText(check: any): string {
    switch (check.check_type) {
      case 'numeric': return `${check.target_value || '—'}${check.tolerance ? ' ± ' + check.tolerance : ''} ${check.uom || ''}`;
      case 'range': return `${check.min_limit || '—'} to ${check.max_limit || '—'} ${check.uom || ''}`;
      case 'min': return `NLT ${check.min_limit || '—'} ${check.uom || ''}`;
      case 'max': return `NMT ${check.max_limit || '—'} ${check.uom || ''}`;
      case 'selection': return 'Select';
      case 'boolean': return 'Pass / Fail';
      default: return 'Descriptive';
    }
  }
  parseOptions(json: any): string[] {
    if (!json) return [];
    if (Array.isArray(json)) return json;
    try { const v = JSON.parse(json); return Array.isArray(v) ? v : []; } catch { return []; }
  }

  /* ---------- step actions ---------- */
  get isFinalised(): boolean {
    if (!this.batch) return false;
    const st = this.batch.status;
    return (
      st === 'Approved' ||
      st === 'Released' ||
      st === 'Released for Packing' ||
      st === 'Cancelled'
    );
  }

  get isOnHold(): boolean {
    return this.batch?.status === 'On Hold';
  }

  get isCancelled(): boolean {
    return this.batch?.status === 'Cancelled';
  }

  get isReleasedForPacking(): boolean {
    const st = this.batch?.status;
    return st === 'Released for Packing' || st === 'Released';
  }

  /** Can put active execution batches on hold. */
  get canHoldBatch(): boolean {
    if (this.isRestrictedView || !this.batch) return false;
    return ['In Progress', 'Correction Required', 'Submitted for Approval', 'Rejected', 'Approved'].includes(this.batch.status);
  }

  get canCancelBatch(): boolean {
    if (this.isRestrictedView || !this.batch) return false;
    return !['Released', 'Released for Packing', 'Cancelled', 'Deleted'].includes(this.batch.status);
  }

  get canReleaseForPacking(): boolean {
    if (this.isRestrictedView || !this.batch) return false;
    return this.batch.status === 'Approved';
  }

  get releaseActionLabel(): string {
    return this.batch?.record_type === 'eBPR' ? 'Release Batch' : 'Release for Packing';
  }
  // operators may edit while batch is not finalised and step not yet verified
  get readOnly(): boolean {
    if (
      this.executionMode === 'final' ||
      this.executionMode === 'checking' ||
      this.executionMode === 'review' ||
      this.executionMode === 'approval' ||
      this.executionMode === 'html_record' ||
      this.executionMode === 'pdf'
    ) {
      return true;
    }
    if (this.isFinalised || this.isOnHold) return true;
    if (this.isPreviousStageReadOnly) return true;
    if (this.executionMode === 'correction') {
      const s = this.currentStep;
      return !(s && s.status === 'Correction Required');
    }
    const s = this.currentStep;
    if (s && s.status === 'Checked' && !this.reviewMode) return true;
    return false;
  }

  openCorrectionsFor(step: any): any[] {
    return (step?.corrections || []).filter((c: any) => c.status !== 'Verified');
  }

  stepFailCount(s: any): number {
    const ipc = (s && s.data && s.data.inprocess) || {};
    let fails = 0;
    Object.keys(ipc).forEach((k) => {
      const bucket = ipc[k];
      if (!bucket) return;
      if (Array.isArray(bucket.entries)) {
        fails += bucket.entries.filter((e: any) => e && e.result === 'Fail').length;
      } else if (bucket.result === 'Fail') {
        fails += 1;
      }
    });
    return fails;
  }
  completeStep(): void {
    const s = this.currentStep;
    if (!s) return;
    const v = this.validateStepForms(s, true);
    if (v) {
      alertify.error(v);
      return;
    }
    if (this.currentStepPmBlocked()) {
      const names = this.currentStepPmAlerts()
        .filter((x) => x.pm?.blocked)
        .map((x) => (x.eq.name || x.pm.equipment_name) + ' (' + (x.pm.equipment_code || equipmentKey(x.eq)) + ')')
        .join('; ');
      alertify.error('Complete preventive maintenance before completing this step. Blocked equipment: ' + names);
      return;
    }
    const proceed = () => {
      this.esign
        .request({ meaning: 'Performed By', module: 'execution', recordRef: this.batchId, detail: 'Complete step: ' + (s.stage_name || '') + ' / ' + (s.step_name || '') })
        .then((sig) => {
          if (!sig) return;
          this.saving = true;
          const payload = this.stepSavePayload(s, sig);
          this.service.post('master/ebmr_bpr.php?type=completeStep', JSON.stringify(payload)).subscribe({
            next: (r: any) => {
              this.saving = false;
              if (r && r.status === 'success') {
                s.status = 'Done';
                alertify.success('Step completed');
              } else alertify.error((r && r.message) || 'Failed');
            },
            error: () => {
              this.saving = false;
              alertify.error('Network error — step not completed');
            },
          });
        });
    };
    const fails = this.stepFailCount(s);
    if (fails > 0) {
      alertify.confirm('Out-of-limit checks', `${fails} in-process check(s) are outside limits (Fail). Complete the step anyway? The checker may require a deviation/correction.`, proceed, () => {});
    } else {
      proceed();
    }
  }
  verifyStep(): void {
    const s = this.currentStep;
    if (!s) return;
    const open = this.openCorrectionsFor(s);
    if (open.length) { alertify.error('Resolve & verify all corrections before verifying the step'); return; }
    this.esign
      .request({ meaning: 'Checked By', module: 'execution', recordRef: this.batchId, detail: 'Verify step: ' + (s.stage_name || '') + ' / ' + (s.step_name || '') })
      .then((sig) => {
        if (!sig) return;
        this.service.post('master/ebmr_bpr.php?type=verifyStep', JSON.stringify({ step_id: s.id, batch_id: this.batchId, remarks: s.remarks || '' })).subscribe((r: any) => {
          if (r && r.status === 'success') { s.status = 'Checked'; alertify.success('Step verified'); }
          else alertify.error((r && r.message) || 'Verify failed');
        });
      });
  }
  reopenStep(): void {
    const s = this.currentStep;
    if (!s) return;
    this.esign
      .request({ meaning: 'Checked By', module: 'execution', recordRef: this.batchId, detail: 'Reopen step: ' + (s.step_name || ''), requireReason: true, reasonLabel: 'Reason for reopening' })
      .then((sig) => {
        if (!sig) return;
        this.service.post('master/ebmr_bpr.php?type=reopenStep', JSON.stringify({ step_id: s.id })).subscribe((r: any) => {
          if (r && r.status === 'success') { s.status = 'Pending'; s.checked_by = null; alertify.message('Step reopened'); }
        });
      });
  }
  sendBackStep(): void {
    const s = this.currentStep;
    if (!s) return;
    alertify.prompt('Send Back to Operator', 'Reason / instruction for the operator:', '', (e: any, val: string) => {
      this.service.post('master/ebmr_bpr.php?type=sendBackStep', JSON.stringify({ step_id: s.id, batch_id: this.batchId, remark: val })).subscribe((r: any) => {
        if (r && r.status === 'success') { s.status = 'Correction Required'; alertify.message('Sent back for correction'); }
      });
    }, () => {});
  }

  /* ---------- correction workflow ---------- */
  openCorrection(fieldLabel: string, oldVal: any): void {
    const s = this.currentStep;
    if (!s) return;
    this.corrForm = {
      batch_id: this.batchId,
      batch_step_id: s.id,
      stage_name: s.stage_name,
      step_name: s.step_name,
      field_label: fieldLabel || '',
      field_key: fieldLabel || '',
      old_value: oldVal == null ? '' : String(oldVal),
      action_type: 'Correction',
      reason: '',
      checker_remark: '',
    };
    this.corrModalOpen = true;
  }
  saveCorrection(): void {
    if (!this.corrForm.field_label || !this.corrForm.reason) { alertify.error('Field label and reason are required'); return; }
    this.esign
      .request({ meaning: 'Checked By', module: 'execution', recordRef: this.batchId, detail: 'Raise correction on: ' + (this.corrForm.field_label || '') })
      .then((sig) => {
        if (!sig) return;
        this.service.post('master/ebmr_bpr.php?type=raiseCorrection', JSON.stringify(this.corrForm)).subscribe((r: any) => {
          if (r && r.status === 'success') {
            alertify.success('Correction raised — step sent back for correction');
            this.corrModalOpen = false;
            this.load();
          } else alertify.error((r && r.message) || 'Failed');
        });
      });
  }
  submitCorrection(c: any): void {
    if (!c.new_value && c.new_value !== 0) { alertify.error('Enter the corrected value'); return; }
    this.esign
      .request({ meaning: 'Performed By', module: 'execution', recordRef: this.batchId, detail: 'Submit corrected value for: ' + (c.field_label || '') })
      .then((sig) => {
        if (!sig) return;
        const payload = { id: c.id, batch_id: this.batchId, new_value: c.new_value, reason: c.op_reason || '', ref_no: c.ref_no || '', action_type: c.action_type };
        // persist the actual edited step data first
        const s = this.currentStep;
        this.service.post('master/ebmr_bpr.php?type=saveStepData', JSON.stringify({ step_id: s.id, batch_id: this.batchId, data: s.data, remarks: s.remarks || '' })).subscribe(() => {
          this.service.post('master/ebmr_bpr.php?type=submitCorrection', JSON.stringify(payload)).subscribe((r: any) => {
            if (r && r.status === 'success') { alertify.success('Correction submitted to checker'); this.load(); }
            else alertify.error((r && r.message) || 'Failed');
          });
        });
      });
  }
  verifyCorrectionEntry(c: any): void {
    this.esign
      .request({ meaning: 'Checked By', module: 'execution', recordRef: this.batchId, detail: 'Verify correction for: ' + (c.field_label || '') })
      .then((sig) => {
        if (!sig) return;
        this.service.post('master/ebmr_bpr.php?type=verifyCorrection', JSON.stringify({ id: c.id, batch_id: this.batchId, checker_remark: c.checker_remark || '' })).subscribe((r: any) => {
          if (r && r.status === 'success') { alertify.success('Correction verified'); this.load(); }
          else alertify.error((r && r.message) || 'Failed');
        });
      });
  }
  get allCorrections(): any[] {
    const out: any[] = [];
    (this.batch?.steps || []).forEach((s: any) => (s.corrections || []).forEach((c: any) => out.push(c)));
    return out.sort((a, b) => b.id - a.id);
  }

  /* ---------- deviation / incident / breakdown (QMS linked) ---------- */
  get stageOptions(): any[] {
    const seen = new Set<number>();
    const out: any[] = [];
    (this.navSteps || []).forEach((s: any) => {
      const seq = Number(s.stage_seq);
      if (seen.has(seq)) return;
      seen.add(seq);
      out.push({ stage_seq: seq, stage_name: s.stage_name });
    });
    return out.sort((a, b) => a.stage_seq - b.stage_seq);
  }

  currentStageContext(): { stage_seq: number; stage_name: string; step_name: string; batch_step_id: number } {
    const s = this.currentStep;
    if (s) {
      return { stage_seq: Number(s.stage_seq), stage_name: s.stage_name || '', step_name: s.step_name || '', batch_step_id: s.id };
    }
    const g = this.groupedStages[0];
    if (g) {
      return {
        stage_seq: Number(g.stage_seq),
        stage_name: g.stage_name || '',
        step_name: g.steps?.[0]?.step_name || '',
        batch_step_id: g.steps?.[0]?.id || 0,
      };
    }
    return { stage_seq: 0, stage_name: '', step_name: '', batch_step_id: 0 };
  }

  buildQmsEmbedContext(): EbmrQmsEmbedContext {
    const ctx = this.currentStageContext();
    return {
      batch_id: this.batchId,
      batch_no: this.batch?.batch_no,
      batch_step_id: ctx.batch_step_id,
      stage_seq: ctx.stage_seq,
      stage_name: ctx.stage_name,
      step_name: ctx.step_name,
      product_code: this.batch?.product_code,
      product_name: this.batch?.product_name,
      profile_code: this.batch?.profile_code,
    };
  }

  openInlineQmsForm(kind: 'deviation' | 'incident' | 'breakdown'): void {
    this.qmsEmbedContext = this.buildQmsEmbedContext();
    this.inlineQmsForm = kind;
  }

  closeInlineQmsForm(): void {
    this.inlineQmsForm = null;
  }

  onQmsEmbedSaved(): void {
    const kind = this.inlineQmsForm;
    if (kind === 'deviation') this.loadDeviations();
    if (kind === 'incident') this.loadIncidents();
    if (kind === 'breakdown') this.loadBreakdowns();
    this.closeInlineQmsForm();
  }

  qmsFormRecord(row: any): any {
    return row?.qms_form || row?.qms || {};
  }

  formatQmsList(val: any): string {
    if (val == null || val === '') return '—';
    if (Array.isArray(val)) return val.join(', ') || '—';
    if (typeof val === 'string') {
      try {
        const p = JSON.parse(val);
        if (Array.isArray(p)) return p.join(', ') || '—';
      } catch {
        /* plain string */
      }
      return val;
    }
    return String(val);
  }

  rightPanelUsesQmsForm(): boolean {
    return (
      !!this.inlineQmsForm &&
      (this.rightPanelModal === 'deviations' ||
        this.rightPanelModal === 'incidents' ||
        this.rightPanelModal === 'breakdown')
    );
  }

  loadDeviations(): void {
    this.service.get('master/ebmr_bpr.php?type=getDeviations&id=' + this.batchId).subscribe((r: any) => {
      this.deviations = Array.isArray(r) ? r : [];
    });
  }
  loadIncidents(): void {
    this.service.get('master/ebmr_bpr.php?type=getIncidents&id=' + this.batchId).subscribe((r: any) => {
      this.incidents = Array.isArray(r) ? r : [];
    });
  }
  loadBreakdowns(): void {
    this.service.get('master/ebmr_bpr.php?type=getBreakdowns&id=' + this.batchId).subscribe((r: any) => {
      this.breakdowns = Array.isArray(r) ? r : [];
    });
  }

  openQmsDevForm(): void {
    this.openInlineQmsForm('deviation');
  }

  openQmsIncForm(): void {
    this.openInlineQmsForm('incident');
  }

  openBreakdownForm(): void {
    this.openInlineQmsForm('breakdown');
  }

  saveDevProdHeadRemark(d: any): void {
    const remark = (this.qmsDevProdRemark.prod_head_remark || '').trim();
    if (!remark) {
      alertify.error('Production Head remark required');
      return;
    }
    this.esign
      .request({ meaning: 'Approved By', module: 'execution', recordRef: this.batchId, detail: 'Prod Head — continue next stage: ' + this.qmsDevProdRemark.prod_head_continue })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post(
            'master/ebmr_bpr.php?type=saveDeviationProdHeadRemark',
            JSON.stringify({
              id: d.id,
              batch_id: this.batchId,
              prod_head_continue: this.qmsDevProdRemark.prod_head_continue,
              prod_head_remark: remark,
            })
          )
          .subscribe((r: any) => {
            if (r?.status === 'success') {
              alertify.success('Production Head remark saved');
              this.qmsDevProdRemark = { id: 0, prod_head_continue: 'No', prod_head_remark: '' };
              this.loadDeviations();
            } else alertify.error(r?.message || 'Failed');
          });
      });
  }

  editDevProdRemark(d: any): void {
    this.qmsDevProdRemark = {
      id: d.id,
      prod_head_continue: d.prod_head_continue || 'No',
      prod_head_remark: d.prod_head_remark || '',
    };
  }

  isDeviationQmsClosed(d: any): boolean {
    const st = (d.qms_status || d.status || '').toLowerCase();
    return st.includes('close') || st.includes('approved') || st.includes('complete') || d.status === 'Closed';
  }

  saveIncNextStageRemark(i: any): void {
    const remark = (this.qmsIncRemark.next_stage_remark || i.next_stage_remark || '').trim();
    if (!remark) {
      alertify.error('Next stage remark required');
      return;
    }
    this.esign
      .request({ meaning: 'Performed By', module: 'execution', recordRef: this.batchId, detail: 'Incident next-stage remark' })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post('master/ebmr_bpr.php?type=saveIncidentNextStageRemark', JSON.stringify({ id: i.id, batch_id: this.batchId, next_stage_remark: remark }))
          .subscribe((r: any) => {
            if (r?.status === 'success') {
              alertify.success('Next stage remark saved');
              this.qmsIncRemark = { id: 0, next_stage_remark: '' };
              this.loadIncidents();
            } else alertify.error(r?.message || 'Failed');
          });
      });
  }

  breakdownStatusLabel(st: string): string {
    return (st || 'pending').replace(/_/g, ' ');
  }

  breakdownStatusClass(st: string): string {
    const s = (st || '').toLowerCase();
    if (s.includes('complete') || s.includes('closed')) return 'eb-badge-ok';
    if (s.includes('pending')) return 'eb-badge-warn';
    return 'eb-badge-type';
  }

  stepRequiresIpqc(s: any): boolean {
    return String(s?.ipqc_testing || '').toUpperCase() === 'YES';
  }

  stepIpqcApproved(s: any): boolean {
    if (!this.stepRequiresIpqc(s)) return true;
    return (this.samplings || []).some((x) => Number(x.batch_step_id) === Number(s.id) && x.status === 'approved');
  }

  currentStepSampling(): any | null {
    const s = this.currentStep;
    if (!s) return null;
    return (this.samplings || []).find((x) => Number(x.batch_step_id) === Number(s.id)) || null;
  }

  loadSamplings(): void {
    this.service.get('master/ebmr_bpr.php?type=getSamplings&id=' + this.batchId).subscribe((r: any) => {
      this.samplings = Array.isArray(r) ? r : [];
      this.mergeSyncedResultsIntoStep();
    });
  }

  loadStepIpqcPreview(): void {
    const s = this.currentStep;
    if (!s || !this.batchId) {
      this.stepIpqcPreview = { specs: [], results: [] };
      return;
    }
    this.service
      .get('master/ebmr_bpr.php?type=getStepIpqcPreview&batch_id=' + this.batchId + '&step_id=' + s.id)
      .subscribe((r: any) => {
        if (r?.status === 'success') {
          this.stepIpqcPreview = r;
        } else {
          this.stepIpqcPreview = { specs: s.template?.ipqc || [], results: [] };
        }
      });
  }

  refreshIpqcPanel(): void {
    this.loadSamplings();
    this.loadStepIpqcPreview();
  }

  parkedIpqcResults(): any[] {
    const fromPreview = this.stepIpqcPreview?.results;
    if (Array.isArray(fromPreview) && fromPreview.length) return fromPreview;
    const sm = this.currentStepSampling();
    if (sm?.tests?.length) return sm.tests;
    return this.syncedIpqcResults();
  }

  syncedIpqcResults(): any[] {
    const d = this.currentStep?.data;
    if (Array.isArray(d?.ipqc_results) && d.ipqc_results.length) return d.ipqc_results;
    const map = d?.ipqc || {};
    return Object.keys(map)
      .filter((k) => map[k] && (map[k].result || map[k].value || map[k].test))
      .map((k) => map[k]);
  }

  ipqcDisplayResult(sp: any): string {
    const e = this.ipqcEntry(sp);
    if (e?.value || e?.result) return e.value || e.result;
    const tests = this.syncedIpqcResults();
    const name = (sp.parameter || sp.spec_name || sp.test || '').toLowerCase();
    const hit = tests.find((t) => String(t.test || '').toLowerCase() === name || String(t.subtest || '').toLowerCase() === name);
    return hit ? (hit.result || hit.value || '') : '';
  }

  private mergeSyncedResultsIntoStep(): void {
    const s = this.currentStep;
    if (!s) return;
    const sm = this.currentStepSampling();
    if (sm?.status === 'approved' && sm.tests?.length) {
      this.ensureStepData(s);
      if (!s.data.ipqc_results?.length) {
        s.data.ipqc_results = sm.tests;
        s.data.ipqc_status = 'approved';
        s.data.ipqc_hold_released = true;
        if (sm.ar_no) s.data.ipqc_ar_no = sm.ar_no;
      }
    }
  }

  openSamplingForm(): void {
    const s = this.currentStep;
    if (!s || !this.stepRequiresIpqc(s)) {
      alertify.error('Current step is not configured for IPQC testing');
      return;
    }
    if (this.currentStepSampling()) {
      alertify.warning('Sample already sent for this step');
      return;
    }
    const name = localStorage.getItem('username') || localStorage.getItem('firstname') || '';
    const emp = localStorage.getItem('emp_id') || '';
    this.samplingForm = {
      batch_id: this.batchId,
      batch_step_id: s.id,
      stage_seq: s.stage_seq,
      stage_name: s.stage_name,
      step_name: s.step_name,
      sampling_by: s.sampling_by || 'Production',
      sample_by: name && emp ? name + ' (' + emp + ')' : name || emp,
      sample_qty: '',
      unit: this.batch?.batch_size_uom || '',
      sample_id: (this.batch?.batch_no || '') + '-IPQC-' + (s.step_seq || s.id),
      equipment_code: '',
    };
    this.showSamplingForm = true;
    this.loadStepIpqcPreview();
  }

  submitSamplingIntimation(): void {
    if (!this.samplingForm.sample_qty?.toString().trim()) {
      alertify.error('Sample quantity required');
      return;
    }
    if (isNaN(Number(this.samplingForm.sample_qty)) || Number(this.samplingForm.sample_qty) <= 0) {
      alertify.error('Sample quantity must be a positive number');
      return;
    }
    if (!this.samplingForm.sample_by?.toString().trim()) {
      alertify.error('Sampled by is required');
      return;
    }
    this.esign
      .request({
        meaning: 'Performed By',
        module: 'execution',
        recordRef: this.batchId,
        detail: 'Send IPQC sample to QC — ' + (this.samplingForm.step_name || ''),
      })
      .then((sig) => {
        if (!sig) return;
        if (!this.samplingForm.sample_by) {
          this.samplingForm.sample_by = sig.emp_name + ' (' + sig.emp_id + ')';
        }
        const body = {
          ...this.samplingForm,
          signature_token: sig.signature_token || '',
          esign_id: sig.esign_id || 0,
        };
        this.service.post('master/ebmr_bpr.php?type=raiseSamplingIntimation', JSON.stringify(body)).subscribe({
          next: (r: any) => {
            if (r?.status === 'success') {
              const route =
                r.sampling_by === 'IPQA'
                  ? 'IPQA (withdraw) then QC in-process'
                  : 'QC / IPQC in-process testing (pending receive)';
              alertify.success('Sample sent to ' + route);
              this.showSamplingForm = false;
              this.refreshIpqcPanel();
              if (r.id) {
                setTimeout(() => {
                  const sm = (this.samplings || []).find((x) => Number(x.id) === Number(r.id));
                  if (sm) {
                    this.printSampleLabel(sm);
                  }
                }, 600);
              }
            } else alertify.error(r?.message || 'Failed');
          },
          error: () => alertify.error('Network error — sample not sent'),
        });
      });
  }

  printSampleLabel(sm: any): void {
    const b = this.batch || {};
    const html = `<!DOCTYPE html><html><head><meta charset="utf-8"/><title>IPQC Sample Label</title>
      <style>
        @page { size: 90mm 60mm; margin: 4mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; color: #0f172a; }
        .lbl {
          border: 2px solid #0b4f6c; border-radius: 4px; padding: 8px 10px;
          width: 100%; min-height: 52mm;
        }
        .lbl-top {
          display: flex; justify-content: space-between; align-items: baseline;
          border-bottom: 2px solid #0b4f6c; padding-bottom: 4px; margin-bottom: 6px;
        }
        .lbl-top h1 { margin: 0; font-size: 13px; letter-spacing: 0.04em; color: #0b4f6c; }
        .lbl-top span { font-size: 9px; color: #64748b; font-weight: 600; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3px 10px; font-size: 10px; }
        .grid .full { grid-column: 1 / -1; }
        .k { color: #64748b; font-size: 8px; text-transform: uppercase; letter-spacing: 0.04em; display: block; }
        .v { font-weight: 700; font-size: 11px; }
        .sid { background: #eef7fb; border: 1px dashed #0b4f6c; padding: 4px 6px; border-radius: 3px; margin-top: 4px; }
        .sid .v { font-size: 13px; letter-spacing: 0.02em; }
        @media print { .noprint { display: none !important; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
        .noprint { margin: 12px; }
        .noprint button { background:#0b4f6c; color:#fff; border:0; padding:8px 14px; border-radius:6px; cursor:pointer; }
      </style></head><body>
      <div class="lbl">
        <div class="lbl-top"><h1>IPQC SAMPLE</h1><span>WITH SAMPLE</span></div>
        <div class="grid">
          <div class="full"><span class="k">Product</span><span class="v">${this.escHtml(b.product_name || sm.product_name || '—')}</span></div>
          <div><span class="k">Code</span><span class="v">${this.escHtml(b.product_code || sm.product_code || '—')}</span></div>
          <div><span class="k">Medicap Lot No</span><span class="v">${this.escHtml(b.batch_no || sm.batch_no || '—')}</span></div>
          <div><span class="k">Stage</span><span class="v">${this.escHtml(sm.stage_name || '—')}</span></div>
          <div><span class="k">Step</span><span class="v">${this.escHtml(sm.step_name || '—')}</span></div>
          <div><span class="k">Qty</span><span class="v">${this.escHtml(sm.sample_qty || '—')} ${this.escHtml(sm.unit || '')}</span></div>
          <div><span class="k">Sampled by</span><span class="v">${this.escHtml(sm.sample_by || '—')}</span></div>
          <div class="full sid"><span class="k">Sample ID</span><span class="v">${this.escHtml(sm.sample_id || '—')}</span></div>
        </div>
      </div>
      <p class="noprint"><button type="button" onclick="window.print()">Print label</button></p>
      <script>setTimeout(function(){window.print()},350)</script>
      </body></html>`;
    this.openPrintHtml(html);
  }

  printTechnicalInfoSheet(sm: any): void {
    const b = this.batch || {};
    const specs = sm.specs || this.stepIpqcPreview?.specs || [];
    const printedAt = this.ipcNowStamp();
    const docRef = 'TIS-IPQC-' + (sm.id || sm.sample_id || 'NEW');
    const testRows = (specs || [])
      .map((sp: any, i: number) => {
        const test = this.escHtml(sp.test || sp.parameter || sp.spec_name || '—');
        const sub = this.escHtml(sp.subtest || '—');
        const lim = this.escHtml(this.specLimitText(sp));
        return `<tr>
          <td class="c">${i + 1}</td>
          <td>${test}</td>
          <td>${sub}</td>
          <td class="lim">${lim}</td>
          <td class="blank">&nbsp;</td>
          <td class="blank">&nbsp;</td>
        </tr>`;
      })
      .join('');
    const emptyRows =
      !testRows
        ? `<tr><td class="c">1</td><td colspan="3" class="muted">No in-process tests mapped — attach specification manually</td><td class="blank">&nbsp;</td><td class="blank">&nbsp;</td></tr>`
        : '';

    const html = `<!DOCTYPE html><html><head><meta charset="utf-8"/>
      <title>Technical Information Sheet — ${this.escHtml(sm.sample_id || '')}</title>
      <style>
        @page { size: A4; margin: 12mm 12mm 14mm; }
        * { box-sizing: border-box; }
        body {
          margin: 0; color: #0f172a;
          font-family: 'Segoe UI', Calibri, Arial, sans-serif;
          font-size: 10.5px; line-height: 1.35;
          -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .sheet { width: 100%; max-width: 190mm; margin: 0 auto; }
        .band {
          display: grid; grid-template-columns: 1.1fr 1.6fr 1fr;
          border: 1.5px solid #0b4f6c; background: linear-gradient(180deg, #f0f9fb 0%, #fff 100%);
        }
        .band > div { padding: 8px 10px; border-right: 1px solid #b6d4e0; }
        .band > div:last-child { border-right: 0; }
        .org { font-size: 11px; font-weight: 700; color: #0b4f6c; text-transform: uppercase; letter-spacing: 0.03em; }
        .org small { display: block; font-weight: 500; color: #64748b; font-size: 9px; margin-top: 2px; letter-spacing: 0; text-transform: none; }
        .title-block { text-align: center; }
        .title-block h1 {
          margin: 0; font-size: 15px; letter-spacing: 0.06em; color: #0b4f6c;
          font-family: Georgia, 'Times New Roman', serif;
        }
        .title-block .sub { margin-top: 3px; font-size: 9.5px; color: #475569; font-weight: 600; }
        .meta-doc { font-size: 9px; color: #334155; text-align: right; }
        .meta-doc b { color: #0b4f6c; }
        .route {
          display: grid; grid-template-columns: 1fr 40px 1fr;
          margin-top: 8px; border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden;
        }
        .route .box { padding: 7px 10px; background: #fff; }
        .route .box.from { background: #f8fafc; }
        .route .box.to { background: #f0fdf9; }
        .route .arrow {
          display: flex; align-items: center; justify-content: center;
          background: #0b4f6c; color: #fff; font-size: 16px; font-weight: 700;
        }
        .route .lbl { font-size: 8px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; font-weight: 700; }
        .route .val { font-size: 12px; font-weight: 700; margin-top: 2px; color: #0f172a; }
        .route .hint { font-size: 9px; color: #64748b; margin-top: 2px; }

        .sec { margin-top: 10px; }
        .sec-h {
          display: flex; align-items: center; gap: 8px;
          background: #0b4f6c; color: #fff; padding: 5px 10px;
          font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
          border-radius: 3px 3px 0 0;
        }
        .sec-h .num {
          background: #fff; color: #0b4f6c; width: 16px; height: 16px; border-radius: 50%;
          display: inline-flex; align-items: center; justify-content: center; font-size: 9px;
        }
        .panel { border: 1px solid #cbd5e1; border-top: 0; border-radius: 0 0 3px 3px; }

        table.kv { width: 100%; border-collapse: collapse; }
        table.kv td { border: 1px solid #e2e8f0; padding: 5px 8px; vertical-align: top; }
        table.kv .k {
          width: 18%; background: #f1f5f9; color: #475569;
          font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.04em; font-weight: 700;
        }
        table.kv .v { width: 32%; font-weight: 600; font-size: 11px; }

        .sid-chip {
          display: inline-block; background: #eef7fb; border: 1px solid #7dd3e8;
          color: #0b4f6c; font-weight: 800; padding: 2px 8px; border-radius: 3px; letter-spacing: 0.02em;
        }

        table.tests { width: 100%; border-collapse: collapse; }
        table.tests th {
          background: #e8f4f8; color: #0b4f6c; font-size: 9px; text-transform: uppercase;
          letter-spacing: 0.04em; padding: 6px 6px; border: 1px solid #b6d4e0; text-align: left;
        }
        table.tests td { border: 1px solid #cbd5e1; padding: 5px 6px; vertical-align: middle; }
        table.tests tr:nth-child(even) td { background: #fafcfd; }
        table.tests .c { text-align: center; width: 28px; color: #64748b; }
        table.tests .lim { font-size: 9.5px; color: #334155; }
        table.tests .blank { min-height: 22px; background: #fffef8 !important; width: 14%; }
        table.tests .muted { color: #94a3b8; font-style: italic; }

        .note {
          margin-top: 8px; border: 1px dashed #94a3b8; border-radius: 3px; padding: 8px 10px; min-height: 42px;
          background: #fafafa;
        }
        .note .lbl { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700; margin-bottom: 4px; }
        .note .lines { color: #cbd5e1; font-size: 11px; line-height: 1.7; }

        .signs {
          display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 12px;
        }
        .sign {
          border: 1px solid #94a3b8; border-radius: 3px; padding: 8px; min-height: 88px;
          background: #fff;
        }
        .sign h4 {
          margin: 0 0 6px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em;
          color: #0b4f6c; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;
        }
        .sign .row { margin: 5px 0; font-size: 9.5px; color: #64748b; }
        .sign .line { border-bottom: 1px solid #94a3b8; height: 16px; margin-top: 2px; }

        .footer {
          margin-top: 12px; padding-top: 6px; border-top: 1px solid #cbd5e1;
          display: flex; justify-content: space-between; font-size: 8px; color: #64748b;
        }
        .footer b { color: #0b4f6c; }

        .badge-warn {
          display: inline-block; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;
          font-size: 8.5px; font-weight: 700; padding: 2px 6px; border-radius: 3px; letter-spacing: 0.03em;
        }

        @media print {
          .noprint { display: none !important; }
          body { margin: 0; }
          .sheet { max-width: none; }
        }
        .noprint {
          position: sticky; top: 0; z-index: 2; background: #0f172a; color: #fff;
          padding: 10px 14px; display: flex; gap: 10px; align-items: center; justify-content: space-between;
        }
        .noprint span { font-size: 12px; }
        .noprint button {
          background: #14b8a6; color: #042f2e; border: 0; font-weight: 700;
          padding: 8px 16px; border-radius: 6px; cursor: pointer;
        }
      </style></head><body>
      <div class="noprint">
        <span>Technical Information Sheet — attach with physical sample to QC</span>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
      </div>
      <div class="sheet">
        <div class="band">
          <div>
            <div class="org">${this.escHtml(b.plant_name || b.company_name || 'Quality Control')}
              <small>In-Process Quality Control</small>
            </div>
          </div>
          <div class="title-block">
            <h1>Technical Information Sheet</h1>
            <div class="sub">IPQC Sample Intimation &amp; Test Request</div>
          </div>
          <div class="meta-doc">
            <div><b>Doc Ref</b> ${this.escHtml(docRef)}</div>
            <div><b>Printed</b> ${this.escHtml(printedAt)}</div>
            <div style="margin-top:4px"><span class="badge-warn">SEND WITH SAMPLE</span></div>
          </div>
        </div>

        <div class="route">
          <div class="box from">
            <div class="lbl">From</div>
            <div class="val">Production / IPQA</div>
            <div class="hint">Sampling route: ${this.escHtml(sm.sampling_by || 'Production')}</div>
          </div>
          <div class="arrow">→</div>
          <div class="box to">
            <div class="lbl">To</div>
            <div class="val">Quality Control (IPQC)</div>
            <div class="hint">In-process testing / receive</div>
          </div>
        </div>

        <div class="sec">
          <div class="sec-h"><span class="num">1</span> Product &amp; batch particulars</div>
          <div class="panel">
            <table class="kv">
              <tr>
                <td class="k">Product name</td><td class="v">${this.escHtml(b.product_name || sm.product_name || '—')}</td>
                <td class="k">Product code</td><td class="v">${this.escHtml(b.product_code || sm.product_code || '—')}</td>
              </tr>
              <tr>
                <td class="k">Batch no.</td><td class="v">${this.escHtml(b.batch_no || sm.batch_no || '—')}</td>
                <td class="k">Batch size</td><td class="v">${this.escHtml(b.batch_size || sm.batch_size || '—')}${b.batch_size_uom ? ' ' + this.escHtml(b.batch_size_uom) : ''}</td>
              </tr>
              <tr>
                <td class="k">BMR / Profile</td><td class="v">${this.escHtml(b.profile_code || sm.profile_code || '—')}</td>
                <td class="k">Medicap lot no</td><td class="v">${this.escHtml(sm.ar_no || 'To be allotted by QC')}</td>
              </tr>
            </table>
          </div>
        </div>

        <div class="sec">
          <div class="sec-h"><span class="num">2</span> Sampling information</div>
          <div class="panel">
            <table class="kv">
              <tr>
                <td class="k">Stage</td><td class="v">${this.escHtml(sm.stage_name || '—')}</td>
                <td class="k">Step / activity</td><td class="v">${this.escHtml(sm.step_name || '—')}</td>
              </tr>
              <tr>
                <td class="k">Sample ID</td><td class="v"><span class="sid-chip">${this.escHtml(sm.sample_id || '—')}</span></td>
                <td class="k">Equipment ID</td><td class="v">${this.escHtml(sm.equipment_code || '—')}</td>
              </tr>
              <tr>
                <td class="k">Sample quantity</td><td class="v">${this.escHtml(sm.sample_qty || '—')} ${this.escHtml(sm.unit || '')}</td>
                <td class="k">Sampled by</td><td class="v">${this.escHtml(sm.sample_by || '—')}</td>
              </tr>
              <tr>
                <td class="k">Date / time</td><td class="v">${this.escHtml(sm.sampled_at || sm.raised_at || printedAt)}</td>
                <td class="k">Container / pack</td><td class="v">________________</td>
              </tr>
            </table>
          </div>
        </div>

        <div class="sec">
          <div class="sec-h"><span class="num">3</span> In-process specification / tests requested</div>
          <div class="panel">
            <table class="tests">
              <thead>
                <tr>
                  <th class="c">Sr</th>
                  <th>Test / Parameter</th>
                  <th>Sub-test</th>
                  <th>Specification / Limits</th>
                  <th>Result</th>
                  <th>Pass / Fail</th>
                </tr>
              </thead>
              <tbody>
                ${testRows || emptyRows}
              </tbody>
            </table>
          </div>
        </div>

        <div class="note">
          <div class="lbl">Sampling / special instructions &amp; remarks</div>
          <div class="lines">_______________________________________________________________<br/>
          _______________________________________________________________<br/>
          _______________________________________________________________</div>
        </div>

        <div class="signs">
          <div class="sign">
            <h4>Sampled by (Production / IPQA)</h4>
            <div class="row">Name<div class="line">${this.escHtml(sm.sample_by || '')}</div></div>
            <div class="row">Sign / Date<div class="line"></div></div>
          </div>
          <div class="sign">
            <h4>Received by (QC / IPQC)</h4>
            <div class="row">Name<div class="line"></div></div>
            <div class="row">Sign / Date / AR<div class="line"></div></div>
          </div>
          <div class="sign">
            <h4>Checked / Released (QC)</h4>
            <div class="row">Name<div class="line"></div></div>
            <div class="row">Sign / Date<div class="line"></div></div>
          </div>
        </div>

        <div class="footer">
          <div>This sheet must accompany the physical sample. Retain with batch record after QC release.</div>
          <div><b>eBMR IPQC</b> · Controlled print</div>
        </div>
      </div>
      <script>setTimeout(function(){window.print()},400)</script>
      </body></html>`;
    this.openPrintHtml(html);
  }

  private escHtml(v: any): string {
    return String(v ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  private openPrintHtml(html: string): void {
    const w = window.open('', '_blank', 'width=900,height=1100');
    if (!w) {
      alertify.error('Allow pop-ups to print');
      return;
    }
    w.document.write(html);
    w.document.close();
  }

  samplingStatusLabel(st: string): string {
    const map: any = {
      pending_ipqa: 'Awaiting IPQA',
      pending_qc_receive: 'Awaiting IPQC receive',
      qc_accepted: 'QC accepted',
      in_testing: 'In QC testing',
      approved: 'QC released',
      rejected: 'Rejected',
      cancelled: 'Cancelled',
    };
    return map[st] || (st || 'pending').replace(/_/g, ' ');
  }

  samplingStatusClass(st: string): string {
    if (st === 'approved') return 'eb-badge-ok';
    if (st === 'in_testing' || st === 'qc_accepted') return 'eb-badge-type';
    if (st === 'rejected') return 'eb-badge-crit';
    return 'eb-badge-warn';
  }

  specLabel(sp: any): string {
    if (sp.spec_name) return sp.spec_name;
    if (sp.parameter) return sp.parameter;
    if (sp.test) return (sp.test || '') + (sp.subtest ? ' — ' + sp.subtest : '');
    return 'Spec';
  }

  specLimitText(sp: any): string {
    if (sp.specification) return sp.specification;
    if (sp.limits) return sp.limits;
    if (sp.min_limit || sp.max_limit) return (sp.min_limit || '') + ' – ' + (sp.max_limit || '');
    if (sp.lower_limit || sp.upper_limit) return (sp.lower_limit || '') + ' – ' + (sp.upper_limit || '');
    return '—';
  }

  trackById(_i: number, row: any): any {
    return row?.id ?? row?.key ?? _i;
  }

  trackByIndex(i: number): number {
    return i;
  }

  /** Highlight steps relevant to the active workflow tab in the left rail. */
  isStepHighlightedForMode(step: any): boolean {
    if (this.executionMode === 'open' || this.executionMode === 'final' || this.isHtmlDocMode) return false;
    return (this.visibleSteps || []).some((s: any) => s.id === step.id);
  }

  /* ---------- exec tabs (simple logs) ---------- */
  saveExecTabs(): void {
    this.esign
      .request({ meaning: 'Performed By', module: 'execution', recordRef: this.batchId, detail: 'Save batch logs (critical params / manpower / logbook / breakdown)' })
      .then((sig) => {
        if (!sig) return;
        this.service.post('master/ebmr_bpr.php?type=saveExecTabs', JSON.stringify({ batch_id: this.batchId, exec_tabs: this.execTabs })).subscribe((r: any) => {
          if (r && r.status === 'success') alertify.success('Saved');
        });
      });
  }
  addTabRow(tab: string): void {
    const tpl: any = {
      critical_params: { parameter: '', observed: '', limit: '', remark: '' },
      manpower: { stage: '', name: '', designation: '', from: '', to: '' },
      logbook: { equipment: '', activity: '', from: '', to: '', by: '' },
      breakdown: { equipment: '', problem: '', action: '', from: '', to: '' },
    };
    this.execTabs[tab].rows.push({ ...(tpl[tab] || {}) });
  }
  removeTabRow(tab: string, i: number): void { this.execTabs[tab].rows.splice(i, 1); }

  /* ---------- yield statement ---------- */
  get yieldRows(): any[] {
    const rows: any[] = [];
    (this.batch?.steps || []).forEach((s: any) => {
      const y = s.template?.yield;
      if (y && y.enabled && Array.isArray(y.rows)) {
        y.rows.forEach((r: any, i: number) => {
          const d = (s.data?.yield || {})[i] || {};
          rows.push({ stage: s.stage_name, step: s.step_name, label: r.label, theoretical: r.theoretical, actual: d.actual, uom: r.uom, pct: d.pct, limit: r.limit });
        });
      }
    });
    return rows;
  }
  yieldWithinLimit(r: any): boolean {
    const pct = parseFloat(r.pct);
    const lim = parseFloat(r.limit);
    if (isNaN(pct)) return true;
    if (isNaN(lim)) return true;
    return pct >= lim;
  }
  saveYield(): void {
    this.esign
      .request({ meaning: 'Prepared By', module: 'execution', recordRef: this.batchId, detail: 'Save yield statement' })
      .then((sig) => {
        if (!sig) return;
        this.service.post('master/ebmr_bpr.php?type=saveYieldStatement', JSON.stringify({ batch_id: this.batchId, yield: this.batch.yield })).subscribe((r: any) => {
          if (r && r.status === 'success') alertify.success('Yield statement saved');
        });
      });
  }

  /* ---------- submit / sign-off / release ---------- */
  submit(): void {
    if (this.openCorrectionCount > 0) { alertify.error('There are ' + this.openCorrectionCount + ' open corrections — resolve them before submission'); return; }
    alertify.confirm('Submit Batch', 'Submit this batch for Production & QA sign-off? All steps must be verified.', () => {
      this.esign
        .request({ meaning: 'Prepared By', module: 'execution', recordRef: this.batchId, detail: 'Submit batch for approval' })
        .then((sig) => {
          if (!sig) return;
          this.service.post('master/ebmr_bpr.php?type=submitForApproval', JSON.stringify({ batch_id: this.batchId })).subscribe((r: any) => {
            if (r && r.status === 'success') { this.batch.status = 'Submitted for Approval'; alertify.success('Submitted for approval'); }
            else alertify.error((r && r.message) || 'Submit failed');
          });
        });
    }, () => {});
  }
  signoff(role: string, action: string): void {
    const doIt = (rmk: string) => {
      const roleLabel = role === 'prod' ? 'Production Head' : 'QA Head';
      this.esign
        .request({
          meaning: action === 'Rejected' ? 'Reviewed By' : 'Approved By',
          module: 'execution',
          recordRef: this.batchId,
          detail: roleLabel + ' ' + action.toLowerCase() + ' batch',
          requireReason: action === 'Rejected',
          reasonLabel: 'Rejection reason',
          title: roleLabel + ' Sign-off',
        })
        .then((sig) => {
          if (!sig) return;
          const remark = sig.reason || rmk;
          this.service.post('master/ebmr_bpr.php?type=signoff', JSON.stringify({ batch_id: this.batchId, role, action, remark })).subscribe((r: any) => {
            if (r && r.status === 'success') {
              alertify.success(roleLabel + ' ' + action.toLowerCase());
              this.load();
            } else alertify.error((r && r.message) || 'Sign-off failed');
          });
        });
    };
    doIt('');
  }
  releaseBatch(): void {
    const isBpr = this.batch?.record_type === 'eBPR';
    const title = isBpr ? 'QA Batch Release' : 'Release Batch for Packing';
    const detail = isBpr ? 'QA release of batch' : 'Release eBMR batch for packing';
    this.esign
      .request({
        meaning: 'Approved By',
        module: 'execution',
        recordRef: this.batchId,
        detail,
        requireReason: true,
        reasonLabel: 'Release remark',
        title,
        confirmLabel: isBpr ? 'Sign & Release' : 'Sign & Release for Packing',
      })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post('master/ebmr_bpr.php?type=releaseBatch', JSON.stringify({ batch_id: this.batchId, remark: sig.reason || '' }))
          .subscribe((r: any) => {
            if (r && r.status === 'success') {
              const newSt = r.batch_status || (isBpr ? 'Released' : 'Released for Packing');
              this.batch.status = newSt;
              alertify.success(isBpr ? 'Batch released — proceed to FG transfer' : 'Batch released for packing');
              this.load();
              alertify.confirm(
                isBpr ? 'Batch Released' : 'Released for Packing',
                isBpr ? 'Batch released. Open packing transfer now?' : 'Batch released for packing. Open packing BPR start now?',
                () => {
                  const dest = isBpr ? '/packing/transfer' : '/packing/bpr/start';
                  this.router.navigate([dest]);
                },
                () => {}
              );
            } else alertify.error((r && r.message) || 'Release failed');
          });
      });
  }

  holdBatch(): void {
    if (!this.canHoldBatch) return;
    this.esign
      .request({
        meaning: 'Reviewed By',
        module: 'execution',
        recordRef: this.batchId,
        detail: 'Put batch On Hold',
        requireReason: true,
        reasonLabel: 'Hold reason',
        title: 'Put Batch On Hold',
        confirmLabel: 'Sign & Hold',
      })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post('master/ebmr_bpr.php?type=holdBatch', JSON.stringify({ batch_id: this.batchId, remark: sig.reason || '' }))
          .subscribe((r: any) => {
            if (r && r.status === 'success') {
              this.batch.status = 'On Hold';
              alertify.success('Batch is On Hold');
              this.load();
            } else alertify.error((r && r.message) || 'Hold failed');
          });
      });
  }

  resumeBatch(): void {
    if (!this.isOnHold) return;
    this.esign
      .request({
        meaning: 'Reviewed By',
        module: 'execution',
        recordRef: this.batchId,
        detail: 'Resume batch from On Hold',
        title: 'Resume Batch',
        confirmLabel: 'Sign & Resume',
      })
      .then((sig) => {
        if (!sig) return;
        this.service.post('master/ebmr_bpr.php?type=resumeBatch', JSON.stringify({ batch_id: this.batchId })).subscribe((r: any) => {
          if (r && r.status === 'success') {
            this.batch.status = 'In Progress';
            alertify.success('Batch resumed — In Progress');
            this.load();
          } else alertify.error((r && r.message) || 'Resume failed');
        });
      });
  }

  cancelBatch(): void {
    if (!this.canCancelBatch) return;
    alertify.confirm('Cancel Batch', 'This will cancel the batch permanently from active execution. Continue?', () => {
      this.esign
        .request({
          meaning: 'Reviewed By',
          module: 'execution',
          recordRef: this.batchId,
          detail: 'Cancel batch',
          requireReason: true,
          reasonLabel: 'Cancellation reason',
          title: 'Cancel Batch',
          confirmLabel: 'Sign & Cancel',
        })
        .then((sig) => {
          if (!sig) return;
          this.service
            .post('master/ebmr_bpr.php?type=cancelBatch', JSON.stringify({ batch_id: this.batchId, remark: sig.reason || '' }))
            .subscribe((r: any) => {
              if (r && r.status === 'success') {
                this.batch.status = 'Cancelled';
                alertify.success('Batch cancelled');
                this.load();
              } else alertify.error((r && r.message) || 'Cancel failed');
            });
        });
    }, () => {});
  }

  loadAudit(): void {
    this.service.get('master/ebmr_bpr.php?type=getBatchLog&id=' + this.batchId).subscribe((r: any) => {
      this.auditLog = Array.isArray(r) ? r : [];
    });
  }

  close(): void {
    const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');
    if (returnUrl) {
      this.router.navigateByUrl(returnUrl);
      return;
    }
    const closeRoute = this.route.snapshot.data?.['closeRoute'] as string;
    if (closeRoute) {
      this.router.navigate([closeRoute]);
      return;
    }
    this.router.navigate(['/master/ebmr-bpr/batches'], { queryParams: { type: this.batch?.record_type || 'eBMR' } });
  }
}
