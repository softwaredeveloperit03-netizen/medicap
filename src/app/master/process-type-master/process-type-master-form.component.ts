import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from '../master-hub-return.service';

declare let alertify: { success: (msg: string) => void; error: (msg: string) => void };

export interface DosageOptionRow {
  dosage_form?: string;
}

export interface ProcessTypeSchemaInfo {
  plant_id: string;
  configured_database: string;
  mysql_database: string;
  table_name: string;
  columns: string[];
  column_count: number;
  user_hint?: string;
  message?: string;
}

export type HierarchyLevel = 'stage' | 'step' | 'substep';

/** Persisted extras for one hierarchy row (maps to additional_params_json) */
export interface AdditionalParamStored {
  label: string;
  db_table: string;
  param_name: string;
}

/** One row in the queue / DB (sequential hierarchy: stage → step → substep) */
export interface ProcessTypeStageLine {
  hierarchy_level: HierarchyLevel;
  sequence_path: string;
  /** Snapshot of user-defined level labels when this row was added */
  label_stage: string;  
  label_step: string;
  label_substep: string;

  dosage_form: string;
  process_title: string;
  stage_no: string;
  stage_title: string;

  step_no: string;
  step_title: string;
  substep_no: string;
  substep_title: string;

  provision_equipments: boolean;
  provision_line_clearance: boolean;
  provision_stage_yield: boolean;
  provision_inprocess_analysis: boolean;
  provision_weighing: boolean;
  provision_procedure: boolean;
  provision_additional_parameter: boolean;
  /** One or more DB column mappings saved as JSON (+ legacy first slot columns) */
  additional_params: AdditionalParamStored[];
  in_process_checks_by: string;
}

/** Draft row while editing “Additional parameter” slots in the Actions panel */
interface AdditionalParamDraft {
  label: string;
  dbTable: string;
  columnName: string;
}

@Component({
  selector: 'app-process-type-master-form',
  templateUrl: './process-type-master-form.component.html',
  styleUrls: ['./process-type-master-form.component.css'],
})
export class ProcessTypeMasterFormComponent implements OnInit {
  readonly hubDept = 'process-stage';
  private readonly LS_LEVEL_LABELS = 'ptm_level_labels';
  /** Fallback if schema API has not returned yet — must match `process_type_stage_master_api.php` $table_name */
  private readonly PTM_STAGE_LINES_TABLE = 'cyclone_process_type_stage_lines';

  /** Dynamic labels — drive every caption in the form & table */
  labelStage = 'Stage';
  labelStep = 'Step';
  labelSubstep = 'Substep';

  /** What the user is adding next */
  entryMode: HierarchyLevel = 'stage';
  selectedParentStagePath = '';
  selectedParentStepPath = '';

  dosageOptions: DosageOptionRow[] = [];
  dosageForm = '';
  processTitle = '';
  stageNumber = '';
  stageTitle = '';

  stepNumber = '';
  stepTitle = '';
  substepNumber = '';
  substepTitle = '';

  actionsOpen = false;
  provisionEquipments = false;
  provisionLineClearance = false;
  provisionStageYield = false;
  provisionInprocessAnalysis = false;
  provisionWeighing = false;
  provisionProcedure = false;
  provisionAdditionalParameter = false;
  /** Entries for extra parameters — user types labels only; table + column sync automatically */
  additionalParamSlots: AdditionalParamDraft[] = [];
  inProcessChecksBy: 'none' | 'production' | 'ipqa' = 'none';

  lines: ProcessTypeStageLine[] = [];
  batchUid = '';

  schemaStatus: 'idle' | 'loading' | 'ready' | 'error' = 'idle';
  schemaInfo: ProcessTypeSchemaInfo | null = null;
  schemaErrorMessage = '';
  plantDisplayName = '';

  loadingDosages = false;
  savingBatch = false;

  /** Bumped after schema sync or final save so the save-log child refetches `list_save_log`. */
  saveLogRefreshTick = 0;

  constructor(
    private readonly api: DataAccessService,
    private readonly router: Router,
    private readonly masterHubReturn: MasterHubReturnService
  ) {}

  ngOnInit(): void {
    this.masterHubReturn.setReturnDepartment(this.hubDept);
    this.loadLevelLabels();
    this.regenerateBatchUid();
    this.loadPlantDisplayName();
    this.runAutoSchemaSync();
    this.fetchDosageForms();
  }

  persistLevelLabels(): void {
    try {
      localStorage.setItem(
        this.LS_LEVEL_LABELS,
        JSON.stringify({ stage: this.labelStage, step: this.labelStep, substep: this.labelSubstep })
      );
    } catch {
      /* ignore */
    }
  }

  private loadLevelLabels(): void {
    try {
      const raw = localStorage.getItem(this.LS_LEVEL_LABELS);
      if (!raw) {
        return;
      }
      const o = JSON.parse(raw) as { stage?: string; step?: string; substep?: string };
      if (typeof o.stage === 'string' && o.stage.trim()) {
        this.labelStage = o.stage.trim();
      }
      if (typeof o.step === 'string' && o.step.trim()) {
        this.labelStep = o.step.trim();
      }
      if (typeof o.substep === 'string' && o.substep.trim()) {
        this.labelSubstep = o.substep.trim();
      }
    } catch {
      /* ignore */
    }
  }

  loadPlantDisplayName(): void {
    try {
      const raw = localStorage.getItem('all_plants');
      const pid = String(localStorage.getItem('plant_id') || '').trim();
      if (!raw || !pid) {
        return;
      }
      const plants = JSON.parse(raw) as { plant_id: string | number; display_name?: string }[];
      if (!Array.isArray(plants)) {
        return;
      }
      const p = plants.find((x) => String(x.plant_id) === pid);
      this.plantDisplayName = (p?.display_name || '').trim();
    } catch {
      /* ignore */
    }
  }

  runAutoSchemaSync(): void {
    this.schemaStatus = 'loading';
    this.schemaErrorMessage = '';
    this.api.get('master/process_type_stage_master_api.php?type=ensure_schema').subscribe({
      next: (res: unknown) => {
        const o = res as {
          status?: string;
          message?: string;
          user_hint?: string;
          plant_id?: string;
          configured_database?: string;
          mysql_database?: string;
          table_name?: string;
          columns?: string[];
          column_count?: number;
        };
        if (o?.status === 'success') {
          this.schemaStatus = 'ready';
          this.schemaInfo = {
            plant_id: String(o.plant_id ?? ''),
            configured_database: String(o.configured_database ?? ''),
            mysql_database: String(o.mysql_database ?? ''),
            table_name: String(o.table_name ?? ''),
            columns: Array.isArray(o.columns) ? o.columns : [],
            column_count: Number(o.column_count) || (Array.isArray(o.columns) ? o.columns.length : 0),
            user_hint: o.user_hint,
            message: o.message,
          };
          if (this.provisionAdditionalParameter) {
            this.refreshAdditionalParamSlotsTables();
          }
          this.saveLogRefreshTick++;
        } else {
          this.schemaStatus = 'error';
          this.schemaInfo = null;
          this.schemaErrorMessage = o?.message || 'Schema check failed.';
          alertify.error(this.schemaErrorMessage);
        }
      },
      error: () => {
        this.schemaStatus = 'error';
        this.schemaInfo = null;
        this.schemaErrorMessage =
          'Cannot reach schema API. Check network and that process_type_stage_master_api.php is deployed.';
        alertify.error(this.schemaErrorMessage);
      },
    });
  }

  private regenerateBatchUid(): void {
    try {
      this.batchUid =
        typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
          ? crypto.randomUUID()
          : `bid-${Date.now()}-${Math.floor(Math.random() * 1e6)}`;
    } catch {
      this.batchUid = `bid-${Date.now()}`;
    }
  }

  fetchDosageForms(): void {
    this.loadingDosages = true;
    this.api.get('master/process_type_stage_master_api.php?type=list_product_dosage_forms').subscribe({
      next: (res: unknown) => {
        const o = res as {
          status?: string;
          dosage_forms?: DosageOptionRow[];
          message?: string;
        };
        if (o?.status === 'success' && Array.isArray(o.dosage_forms)) {
          this.dosageOptions = o.dosage_forms;
        } else {
          this.dosageOptions = [];
          if (o?.message) {
            alertify.error(o.message);
          }
        }
        this.loadingDosages = false;
      },
      error: () => {
        this.loadingDosages = false;
        alertify.error('Unable to load dosage forms.');
      },
    });
  }

  onEntryModeChange(): void {
    this.selectedParentStagePath = '';
    this.selectedParentStepPath = '';
    this.resetProvisionFields();
  }

  stageRows(): ProcessTypeStageLine[] {
    return this.lines.filter((l) => l.hierarchy_level === 'stage');
  }

  /** Stages in the current batch that share the same dosage + process type (process title). */
  stagesForProcessType(dosageForm: string, processTitle: string): ProcessTypeStageLine[] {
    const df = (dosageForm || '').trim().toLowerCase();
    const pt = (processTitle || '').trim().toLowerCase();
    if (!df || !pt) {
      return [];
    }
    return this.stageRows().filter(
      (s) =>
        (s.dosage_form || '').trim().toLowerCase() === df &&
        (s.process_title || '').trim().toLowerCase() === pt,
    );
  }

  /** First row in the batch — used to keep one process type per save batch when adding more stages. */
  batchProcessAnchor(): { dosage_form: string; process_title: string } | null {
    const first = this.lines[0];
    if (!first) {
      return null;
    }
    return {
      dosage_form: (first.dosage_form || '').trim(),
      process_title: (first.process_title || '').trim(),
    };
  }

  stepRowsUnder(stagePath: string): ProcessTypeStageLine[] {
    const prefix = stagePath + '.';
    return this.lines.filter(
      (l) => l.hierarchy_level === 'step' && l.sequence_path.startsWith(prefix) && l.sequence_path.split('.').length === 2
    );
  }

  stepRowsForDropdown(): ProcessTypeStageLine[] {
    if (!this.selectedParentStagePath) {
      return [];
    }
    return this.stepRowsUnder(this.selectedParentStagePath);
  }

  toggleActions(): void {
    this.actionsOpen = !this.actionsOpen;
  }

  onAdditionalParameterChange(checked: boolean): void {
    if (!checked) {
      this.additionalParamSlots = [];
    } else {
      if (this.additionalParamSlots.length === 0) {
        this.additionalParamSlots = [this.blankAdditionalParamSlot()];
      }
      this.refreshAdditionalParamSlotsTables();
    }
  }

  /** User edits display labels; each row’s table defaults to the stage-lines table and column is derived (snake_case). */
  onAdditionalSlotLabelChange(index: number): void {
    this.syncAdditionalSlot(index);
  }

  addAdditionalParamSlot(): void {
    this.additionalParamSlots = [...this.additionalParamSlots, this.blankAdditionalParamSlot()];
  }

  removeAdditionalParamSlot(index: number): void {
    if (this.additionalParamSlots.length <= 1) {
      return;
    }
    this.additionalParamSlots = this.additionalParamSlots.filter((_s, i) => i !== index);
  }

  trackBySlotIndex(index: number, _slot: AdditionalParamDraft): number {
    return index;
  }

  private blankAdditionalParamSlot(): AdditionalParamDraft {
    return {
      label: '',
      dbTable: this.defaultAdditionalParamTable(),
      columnName: '',
    };
  }

  private refreshAdditionalParamSlotsTables(): void {
    if (!this.provisionAdditionalParameter) {
      return;
    }
    this.additionalParamSlots.forEach((_s, i) => this.syncAdditionalSlot(i));
  }

  private defaultAdditionalParamTable(): string {
    const t = (this.schemaInfo?.table_name || '').trim();
    return t || this.PTM_STAGE_LINES_TABLE;
  }

  /** Shown in the hint next to the auto-filled table name. */
  defaultAdditionalParamTableDisplay(): string {
    return this.defaultAdditionalParamTable();
  }

  private labelToSnakeCase(raw: string): string {
    let s = raw
      .trim()
      .toLowerCase()
      .replace(/[\s\-]+/g, '_')
      .replace(/[^a-z0-9_]/g, '')
      .replace(/_+/g, '_')
      .replace(/^_+|_+$/g, '');
    if (!s) {
      return '';
    }
    if (/^[0-9]/.test(s)) {
      s = 'param_' + s;
    }
    if (s.length > 64) {
      s = s.slice(0, 64).replace(/_+$/, '');
    }
    return s;
  }

  /** If snake_case collides elsewhere in this batch or on other slots, append _2, _3, … */
  private ensureUniqueAdditionalParamColumn(base: string, excludeSlotIndex: number): string {
    const tblNorm = this.defaultAdditionalParamTable().toLowerCase();
    let candidate = base;
    let n = 2;
    while (candidate) {
      const used = this.collectUsedAdditionalParamKeys(excludeSlotIndex);
      const k = `${tblNorm}\0${candidate.toLowerCase()}`;
      if (!used.has(k)) {
        return candidate;
      }
      const suffix = '_' + n;
      const maxLen = 64 - suffix.length;
      const stem = base.slice(0, Math.max(1, maxLen)).replace(/_+$/, '');
      candidate = stem + suffix;
      n++;
      if (n > 200) {
        return base;
      }
    }
    return base;
  }

  private syncAdditionalSlot(index: number): void {
    if (!this.provisionAdditionalParameter || index < 0 || index >= this.additionalParamSlots.length) {
      return;
    }
    const slot = this.additionalParamSlots[index];
    slot.dbTable = this.defaultAdditionalParamTable();
    const label = slot.label.trim();
    if (!label) {
      slot.columnName = '';
      return;
    }
    const snake = this.labelToSnakeCase(label);
    slot.columnName = snake ? this.ensureUniqueAdditionalParamColumn(snake, index) : '';
  }

  /**
   * Table/column pairs already used in queued lines, plus other form slots (excluding excludeSlotIndex).
   */
  private collectUsedAdditionalParamKeys(excludeSlotIndex?: number): Set<string> {
    const keys = new Set<string>();
    for (const line of this.lines) {
      for (const p of line.additional_params) {
        const t = (p.db_table || '').trim().toLowerCase();
        const c = (p.param_name || '').trim().toLowerCase();
        if (t && c) {
          keys.add(`${t}\0${c}`);
        }
      }
    }
    if (this.provisionAdditionalParameter) {
      this.additionalParamSlots.forEach((slot, i) => {
        if (excludeSlotIndex !== undefined && i === excludeSlotIndex) {
          return;
        }
        const t = (slot.dbTable || '').trim().toLowerCase();
        const c = (slot.columnName || '').trim().toLowerCase();
        if (t && c) {
          keys.add(`${t}\0${c}`);
        }
      });
    }
    return keys;
  }

  private additionalParamDraftHasDuplicateInBatch(): boolean {
    const addl = this.buildAdditionalParamsFromDraft();
    const seen = new Set<string>();
    for (const a of addl) {
      const k = `${(a.db_table || '').trim().toLowerCase()}\0${(a.param_name || '').trim().toLowerCase()}`;
      if (seen.has(k)) {
        return true;
      }
      seen.add(k);
    }
    for (const a of addl) {
      const k = `${(a.db_table || '').trim().toLowerCase()}\0${(a.param_name || '').trim().toLowerCase()}`;
      for (const line of this.lines) {
        for (const p of line.additional_params) {
          const pk = `${(p.db_table || '').trim().toLowerCase()}\0${(p.param_name || '').trim().toLowerCase()}`;
          if (pk === k) {
            return true;
          }
        }
      }
    }
    return false;
  }

  ipcLabel(): string {
    if (this.inProcessChecksBy === 'production') {
      return 'Production';
    }
    if (this.inProcessChecksBy === 'ipqa') {
      return 'IPQA';
    }
    return '';
  }

  hasAnyActionProvision(): boolean {
    const ipc = this.inProcessChecksBy !== 'none';
    return (
      this.provisionEquipments ||
      this.provisionLineClearance ||
      this.provisionStageYield ||
      this.provisionInprocessAnalysis ||
      this.provisionWeighing ||
      this.provisionProcedure ||
      this.provisionAdditionalParameter ||
      ipc
    );
  }

  labelSnapshot(): { label_stage: string; label_step: string; label_substep: string } {
    return {
      label_stage: (this.labelStage || 'Stage').trim(),
      label_step: (this.labelStep || 'Step').trim(),
      label_substep: (this.labelSubstep || 'Substep').trim(),
    };
  }

  private pathOrder(a: string, b: string): number {
    const sa = a.split('.');
    const sb = b.split('.');
    const len = Math.max(sa.length, sb.length);
    for (let i = 0; i < len; i++) {
      const na = parseInt(sa[i] || '0', 10) || 0;
      const nb = parseInt(sb[i] || '0', 10) || 0;
      if (na !== nb) {
        return na - nb;
      }
    }
    return 0;
  }

  private sortLines(): void {
    this.lines = [...this.lines].sort((x, y) => this.pathOrder(x.sequence_path, y.sequence_path));
  }

  private nextStagePath(): string {
    const nums = this.stageRows()
      .map((l) => parseInt(l.sequence_path.split('.')[0], 10))
      .filter((n) => !isNaN(n));
    const max = nums.length ? Math.max(...nums) : 0;
    return String(max + 1);
  }

  private nextStepPath(stagePath: string): string {
    const steps = this.lines.filter(
      (l) =>
        l.hierarchy_level === 'step' &&
        l.sequence_path.startsWith(stagePath + '.') &&
        l.sequence_path.split('.').length === 2
    );
    const nums = steps
      .map((l) => parseInt(l.sequence_path.split('.')[1], 10))
      .filter((n) => !isNaN(n));
    const max = nums.length ? Math.max(...nums) : 0;
    return `${stagePath}.${max + 1}`;
  }

  private nextSubstepPath(stepPath: string): string {
    const subs = this.lines.filter(
      (l) =>
        l.hierarchy_level === 'substep' &&
        l.sequence_path.startsWith(stepPath + '.') &&
        l.sequence_path.split('.').length === 3
    );
    const nums = subs
      .map((l) => parseInt(l.sequence_path.split('.')[2], 10))
      .filter((n) => !isNaN(n));
    const max = nums.length ? Math.max(...nums) : 0;
    return `${stepPath}.${max + 1}`;
  }

  addHierarchyRow(): void {
    this.persistLevelLabels();
    const snap = this.labelSnapshot();

    if (this.entryMode === 'stage') {
      const df = this.dosageForm.trim();
      const pt = this.processTitle.trim();
      const sn = this.stageNumber.trim();
      const st = this.stageTitle.trim();
      if (!df || !pt || !sn || !st) {
        alertify.error(
          `Fill dosage form, process type, ${snap.label_stage.toLowerCase()} no., and ${snap.label_stage.toLowerCase()} title.`
        );
        return;
      }
      const anchor = this.batchProcessAnchor();
      if (anchor) {
        const dfOk = df.toLowerCase() === anchor.dosage_form.toLowerCase();
        const ptOk = pt.toLowerCase() === anchor.process_title.toLowerCase();
        if (!dfOk || !ptOk) {
          alertify.error(
            `This batch already defines process type "${anchor.process_title}" (${anchor.dosage_form}). ` +
              `Add more ${snap.label_stage.toLowerCase()} rows under that same process type, or reset the list to start a new one.`,
          );
          return;
        }
      }
      if (!this.validateProvisionsForAdd()) {
        return;
      }
      const path = this.nextStagePath();
      const row: ProcessTypeStageLine = {
        hierarchy_level: 'stage',
        sequence_path: path,
        ...snap,
        dosage_form: df,
        process_title: pt,
        stage_no: sn,
        stage_title: st,
        step_no: '',
        step_title: '',
        substep_no: '',
        substep_title: '',
        ...this.buildProvisionsFromForm(),
      };
      this.lines = [...this.lines, row];
      this.sortLines();
      this.resetStageEntryFields();
      return;
    }

    if (this.entryMode === 'step') {
      if (!this.selectedParentStagePath) {
        alertify.error(`Select a parent ${snap.label_stage.toLowerCase()} first.`);
        return;
      }
      const parent = this.lines.find(
        (l) => l.hierarchy_level === 'stage' && l.sequence_path === this.selectedParentStagePath
      );
      if (!parent) {
        alertify.error('Parent no longer exists. Choose again.');
        return;
      }
      const sno = this.stepNumber.trim();
      const sti = this.stepTitle.trim();
      if (!sno || !sti) {
        alertify.error(`Enter ${snap.label_step.toLowerCase()} no. and title.`);
        return;
      }
      if (!this.validateProvisionsForAdd()) {
        return;
      }
      const path = this.nextStepPath(parent.sequence_path);
      const row: ProcessTypeStageLine = {
        hierarchy_level: 'step',
        sequence_path: path,
        ...snap,
        dosage_form: parent.dosage_form,
        process_title: parent.process_title,
        stage_no: parent.stage_no,
        stage_title: parent.stage_title,
        step_no: sno,
        step_title: sti,
        substep_no: '',
        substep_title: '',
        ...this.buildProvisionsFromForm(),
      };
      this.lines = [...this.lines, row];
      this.sortLines();
      this.stepNumber = '';
      this.stepTitle = '';
      this.resetProvisionFields();
      return;
    }

    /* substep */
    if (!this.selectedParentStepPath) {
      alertify.error(`Select a parent ${snap.label_step.toLowerCase()} first.`);
      return;
    }
    const stepParent = this.lines.find(
      (l) => l.hierarchy_level === 'step' && l.sequence_path === this.selectedParentStepPath
    );
    if (!stepParent) {
      alertify.error('Parent step no longer exists. Choose again.');
      return;
    }
    const uno = this.substepNumber.trim();
    const uti = this.substepTitle.trim();
    if (!uno || !uti) {
      alertify.error(`Enter ${snap.label_substep.toLowerCase()} no. and title.`);
      return;
    }
    if (!this.validateProvisionsForAdd()) {
      return;
    }
    const path = this.nextSubstepPath(stepParent.sequence_path);
    const row: ProcessTypeStageLine = {
      hierarchy_level: 'substep',
      sequence_path: path,
      ...snap,
      dosage_form: stepParent.dosage_form,
      process_title: stepParent.process_title,
      stage_no: stepParent.stage_no,
      stage_title: stepParent.stage_title,
      step_no: stepParent.step_no,
      step_title: stepParent.step_title,
      substep_no: uno,
      substep_title: uti,
      ...this.buildProvisionsFromForm(),
    };
    this.lines = [...this.lines, row];
    this.sortLines();
    this.substepNumber = '';
    this.substepTitle = '';
    this.resetProvisionFields();
  }

  /** Provisions / IPC only (dosage & hierarchy fields unchanged). */
  private resetProvisionFields(): void {
    this.actionsOpen = false;
    this.provisionEquipments = false;
    this.provisionLineClearance = false;
    this.provisionStageYield = false;
    this.provisionInprocessAnalysis = false;
    this.provisionWeighing = false;
    this.provisionProcedure = false;
    this.provisionAdditionalParameter = false;
    this.additionalParamSlots = [];
    this.inProcessChecksBy = 'none';
  }

  private buildAdditionalParamsFromDraft(): AdditionalParamStored[] {
    return this.additionalParamSlots
      .map((s) => ({
        label: s.label.trim(),
        db_table: s.dbTable.trim(),
        param_name: s.columnName.trim(),
      }))
      .filter((x) => x.label && x.db_table && x.param_name);
  }

  private buildProvisionsFromForm(): Pick<
    ProcessTypeStageLine,
    | 'provision_equipments'
    | 'provision_line_clearance'
    | 'provision_stage_yield'
    | 'provision_inprocess_analysis'
    | 'provision_weighing'
    | 'provision_procedure'
    | 'provision_additional_parameter'
    | 'additional_params'
    | 'in_process_checks_by'
  > {
    const addl = this.buildAdditionalParamsFromDraft();
    return {
      provision_equipments: this.provisionEquipments,
      provision_line_clearance: this.provisionLineClearance,
      provision_stage_yield: this.provisionStageYield,
      provision_inprocess_analysis: this.provisionInprocessAnalysis,
      provision_weighing: this.provisionWeighing,
      provision_procedure: this.provisionProcedure,
      provision_additional_parameter: addl.length > 0,
      additional_params: addl,
      in_process_checks_by: this.ipcLabel(),
    };
  }

  private validateProvisionsForAdd(): boolean {
    if (!this.hasAnyActionProvision()) {
      alertify.error('Open Actions and select at least one provision for this row.');
      return false;
    }
    if (this.provisionAdditionalParameter) {
      const addl = this.buildAdditionalParamsFromDraft();
      if (addl.length === 0) {
        alertify.error('Additional parameter: add at least one display label (or turn the option off).');
        return false;
      }
      for (const slot of this.additionalParamSlots) {
        const hasLabel = !!slot.label.trim();
        const hasCol = !!slot.columnName.trim();
        if (hasLabel && !hasCol) {
          alertify.error(
            'Additional parameter: every label must produce a valid column name. Check for empty or invalid characters.'
          );
          return false;
        }
      }
      if (this.additionalParamDraftHasDuplicateInBatch()) {
        alertify.error('Two additional parameters use the same table/column. Use different labels.');
        return false;
      }
    }
    return true;
  }

  private resetStageEntryFields(): void {
    this.stageNumber = '';
    this.stageTitle = '';
    this.resetProvisionFields();
  }

  resetCurrentEntry(): void {
    this.dosageForm = '';
    this.processTitle = '';
    this.resetStageEntryFields();
    this.stepNumber = '';
    this.stepTitle = '';
    this.substepNumber = '';
    this.substepTitle = '';
  }

  removeLine(index: number): void {
    const victim = this.lines[index];
    if (!victim) {
      return;
    }
    const p = victim.sequence_path;
    this.lines = this.lines.filter((r) => r.sequence_path !== p && !r.sequence_path.startsWith(p + '.'));
  }

  levelCaption(row: ProcessTypeStageLine): string {
    if (row.hierarchy_level === 'stage') {
      return row.label_stage || this.labelStage;
    }
    if (row.hierarchy_level === 'step') {
      return row.label_step || this.labelStep;
    }
    return row.label_substep || this.labelSubstep;
  }

  indentRem(row: ProcessTypeStageLine): number {
    if (row.hierarchy_level === 'stage') {
      return 0.2;
    }
    if (row.hierarchy_level === 'step') {
      return 1.1;
    }
    return 2;
  }

  yn(v: boolean): string {
    return v ? 'Y' : 'N';
  }

  ynOrDash(_row: ProcessTypeStageLine, v: boolean): string {
    return this.yn(v);
  }

  ipcOrDash(row: ProcessTypeStageLine): string {
    return row.in_process_checks_by || '—';
  }

  additionalParamSummary(row: ProcessTypeStageLine): string {
    const list = row.additional_params;
    if (!row.provision_additional_parameter || !list.length) {
      return '—';
    }
    return list
      .map((p) => [p.label, p.db_table, p.param_name].filter(Boolean).join(' · '))
      .join(' | ');
  }

  printTable(): void {
    if (this.lines.length === 0) {
      alertify.error('Add at least one row before printing.');
      return;
    }
    window.print();
  }

  yieldProvisionLabel(): string {
    return this.entryMode === 'stage' ? 'Stage yield' : 'Yield';
  }

  closePage(): void {
    this.router.navigate(['/master'], { queryParams: { dept: this.hubDept } });
  }

  rowPayload(r: ProcessTypeStageLine): Record<string, string | boolean> {
    const addl = r.additional_params || [];
    const first = addl[0];
    return {
      hierarchy_level: r.hierarchy_level,
      sequence_path: r.sequence_path,
      label_stage: r.label_stage,
      label_step: r.label_step,
      label_substep: r.label_substep,
      dosage_form: r.dosage_form,
      process_title: r.process_title,
      stage_no: r.stage_no,
      stage_title: r.stage_title,
      step_no: r.step_no,
      step_title: r.step_title,
      substep_no: r.substep_no,
      substep_title: r.substep_title,
      provision_equipments: r.provision_equipments,
      provision_line_clearance: r.provision_line_clearance,
      provision_stage_yield: r.provision_stage_yield,
      provision_inprocess_analysis: r.provision_inprocess_analysis,
      provision_weighing: r.provision_weighing,
      provision_procedure: r.provision_procedure,
      provision_additional_parameter: r.provision_additional_parameter,
      additional_param_label: first?.label || '',
      additional_param_db_table: first?.db_table || '',
      additional_param_name: first?.param_name || '',
      additional_params_json: addl.length ? JSON.stringify(addl) : '',
      in_process_checks_by: r.in_process_checks_by || '',
    };
  }

  finalSaveToDb(): void {
    if (this.lines.length === 0) {
      alertify.error('Add at least one hierarchy row, then use Final save.');
      return;
    }
    if (!this.stageRows().length) {
      alertify.error(`Add at least one ${this.labelStage.toLowerCase()} before final save.`);
      return;
    }

    if (this.schemaStatus !== 'ready') {
      alertify.error('Wait for database sync to finish, or fix the error above.');
      return;
    }

    const payload = {
      rows: this.lines.map((r) => this.rowPayload(r)),
      batch_uid: this.batchUid,
      saved_by_emp_id: typeof localStorage !== 'undefined' ? localStorage.getItem('emp_id') || '' : '',
    };

    this.savingBatch = true;
    this.api
      .postJson('master/process_type_stage_master_api.php?type=save_batch', JSON.stringify(payload))
      .subscribe({
        next: (res: unknown) => {
          this.savingBatch = false;
          const obj = res as {
            status?: string;
            inserted?: number;
            message?: string;
            mysql_database?: string;
            table_name?: string;
          };
          if (obj?.status === 'success') {
            const n = obj.inserted ?? this.lines.length;
            const where =
              obj.mysql_database && obj.table_name ? ` in ${obj.mysql_database}.${obj.table_name}` : '';
            alertify.success(`Stored ${n} row(s)${where}.`);
            this.saveLogRefreshTick++;
            this.lines = [];
            this.regenerateBatchUid();
            this.resetCurrentEntry();
            this.runAutoSchemaSync();
          } else {
            alertify.error(obj?.message || 'Save failed.');
          }
        },
        error: (err) => {
          this.savingBatch = false;
          const msg =
            err?.error?.message ||
            (typeof err?.error === 'string' ? err.error : null) ||
            'Could not reach server.';
          alertify.error(String(msg));
        },
      });
  }
}
