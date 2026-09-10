import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { HttpResponse } from '@angular/common/http';
import { MasterHubReturnService } from '../../master-hub-return.service';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: {
  success: (msg: string) => void;
  error: (msg: string) => void;
  message?: (msg: string) => void;
};

export interface CbmrProcessDef {
  dosage_form: string;
  process_title: string;
  line_count: number;
}

export interface CbmrStructureRow {
  hierarchy_level: string;
  sequence_path: string;
  dosage_form: string;
  process_title: string;
  stage_no: string;
  stage_title: string;
  step_no: string;
  step_title: string;
  substep_no: string;
  substep_title: string;
  label_stage: string;
  label_step: string;
  label_substep: string;
  provision_equipments?: string | number;
  provision_line_clearance?: string | number;
  provision_stage_yield?: string | number;
  provision_inprocess_analysis?: string | number;
  provision_weighing?: string | number;
  provision_procedure?: string | number;
  provision_additional_parameter?: string | number;
  additional_param_label?: string;
  additional_param_db_table?: string;
  additional_param_name?: string;
  additional_params_json?: string;
  in_process_checks_by?: string;
}

/**
 * Phase-1 enterprise field definition for dynamic BMR capture per hierarchy node (`sequence_path` key).
 * Extensible toward full form-builder / SQL / API generator.
 */
export interface BmrCaptureFieldDef {
  id: string;
  fieldName: string;
  label: string;
  /** text | textarea | number | decimal | email | date | datetime | time | dropdown | multiselect | checkbox | file */
  controlType: string;
  required: boolean;
  unique: boolean;
  searchable: boolean;
  filterable: boolean;
  sortable: boolean;
  placeholder: string;
  helpText: string;
  defaultValue: string;
  validationPattern: string;
  hidden: boolean;
  readOnly: boolean;
  dropdownMode: 'static' | 'dynamic' | '';
  fkTable: string;
  fkValueColumn: string;
  fkDisplayColumn: string;
  fkWhereClause: string;
  dependsOnField: string;
  lazyLoad: boolean;
  paginated: boolean;
  /** Lines: value|label per line when dropdownMode === static */
  staticOptionsText: string;
  /** One line per mirrored column shown when operator picks a row: column_or code|friendly label */
  lookupMirrorText: string;
}

export interface CbmrDropdownMirrorPreview {
  column: string;
  caption: string;
  text: string;
}

export interface CbmrDropdownPreviewRow {
  value: string;
  label: string;
  mirrors?: CbmrDropdownMirrorPreview[];
}

/** Local sample state for dynamic lists (built-in layouts only — never queries the database). */
export interface CbmrDropdownPreviewState {
  rows?: CbmrDropdownPreviewRow[];
  /** Shown when table name has no built-in sample layout (mapping still saves). */
  error?: string;
}

interface CbmrStaticLookupPresetDef {
  fkTableAliases: string[];
  defaultValueColumn: string;
  defaultDisplayColumn: string;
  defaultWhereClause: string;
  suggestedMirrorLines: string[];
  demoRows: Record<string, string>[];
}

const CBMR_STATIC_LOOKUP_PRESETS: CbmrStaticLookupPresetDef[] = [
  {
    fkTableAliases: ['equipment', 'equipment_master'],
    defaultValueColumn: 'equipment_code',
    defaultDisplayColumn: 'equipment_name',
    defaultWhereClause: 'is_active = 1',
    suggestedMirrorLines: ['capacity|Rated capacity', 'model|Model', 'location_name|Location'],
    demoRows: [
      {
        equipment_code: 'EQ-101',
        equipment_name: 'Blender line A',
        capacity: '500 kg',
        model: 'BR-500',
        location_name: 'Blending hall',
      },
      {
        equipment_code: 'EQ-202',
        equipment_name: 'Tablet coater 2',
        capacity: '320 kg',
        model: 'CT-320',
        location_name: 'Coating suite',
      },
    ],
  },
  {
    fkTableAliases: ['departments', 'department'],
    defaultValueColumn: 'department_id',
    defaultDisplayColumn: 'department_name',
    defaultWhereClause: '',
    suggestedMirrorLines: ['cost_center|Cost center'],
    demoRows: [
      { department_id: 'D001', department_name: 'Production', cost_center: 'CC-P1' },
      { department_id: 'D002', department_name: 'Quality control', cost_center: 'CC-QC' },
    ],
  },
  {
    fkTableAliases: ['employee', 'employees'],
    defaultValueColumn: 'emp_id',
    defaultDisplayColumn: 'emp_name',
    defaultWhereClause: '',
    suggestedMirrorLines: ['department_name|Department'],
    demoRows: [
      { emp_id: 'E1001', emp_name: 'A. Sharma', department_name: 'Production' },
      { emp_id: 'E1002', emp_name: 'R. Iyer', department_name: 'Quality control' },
    ],
  },
  {
    fkTableAliases: ['product', 'products'],
    defaultValueColumn: 'product_code',
    defaultDisplayColumn: 'product_name',
    defaultWhereClause: '',
    suggestedMirrorLines: ['strength|Strength', 'dosage_form|Dosage form'],
    demoRows: [
      { product_code: 'PR-MAG-01', product_name: 'Magaldrate tab 400 mg', strength: '400 mg', dosage_form: 'Tablet' },
      { product_code: 'PR-ASP-02', product_name: 'Aspirin tab 81 mg', strength: '81 mg', dosage_form: 'Tablet' },
    ],
  },
  {
    fkTableAliases: ['room_master', 'room', 'rooms'],
    defaultValueColumn: 'id',
    defaultDisplayColumn: 'room_name',
    defaultWhereClause: '',
    suggestedMirrorLines: ['room_code|Room code', 'area|Area'],
    demoRows: [
      { id: '12', room_name: 'Granulation suite', room_code: 'RM-G01', area: 'Block A' },
      { id: '18', room_name: 'Compression hall', room_code: 'RM-C02', area: 'Block B' },
    ],
  },
];

const CBMR_EQUIP_BY_PATH_KEY = '__equipment_by_path__';

/** Generated provision blocks per capture path (saved in capture_schema_json). */
const CBMR_PROVISION_SEQUENCES_KEY = '__cbmr_provision_sequences__';

/** Header area: product/batch metadata (not per-path field arrays). */
const CBMR_PRODUCT_INFO_KEY = '__cbmr_product_info__';

/** Distinct from real `dosage_form` values (templates may legally use blank). */
const CBMR_DOSAGE_PLACEHOLDER = '__cbmr_pick_dosage__';

/** Fixed BMR segments (left rail) — stored in `capture_schema_json` like hierarchy paths. */
export const CBMR_FIXED_PATH = {
  productBatch: '__cbmr_fixed__/product_batch',
  approval: '__cbmr_fixed__/approval',
  unitFormula: '__cbmr_fixed__/unit_formula',
  dispensing: '__cbmr_fixed__/dispensing',
  equipment: '__cbmr_fixed__/equipment',
} as const;

export type CbmrLeftNavId = keyof typeof CBMR_FIXED_PATH | 'dynamic';

export type CbmrRightAuxTab = 'main' | 'inprocess' | 'ipqc' | 'qms' | 'breakdown';

export interface CbmrProductInformation {
  bmrScopeMode: 'product_specific' | 'common';
  product_name: string;
  nmr_number: string;
  product_code: string;
  process_type: string;
  batch_size: string;
  unit_formula_number: string;
  generic_name: string;
}

/** Enabled provisions / IPC from Process Type Master for the selected node — shown as header chips. */
export interface CbmrMasterActionChip {
  id: string;
  label: string;
}

/** One provision sequence block generated for a capture path. */
export interface CbmrProvisionSequenceEntry {
  id: string;
  label: string;
  order: number;
  /** Manufacturing hierarchy path (e.g. `1.1.1`) when generated on a stage / step / substep. */
  sequence_path?: string;
  hierarchy_level?: string;
}

/** Stage / step / substep with provision sequences ready for PDF export. */
export interface CbmrProvisionExportBundle {
  path: string;
  row: CbmrStructureRow;
  sequences: CbmrProvisionSequenceEntry[];
}

/** Where the user is configuring BMR content (highlighted in the center panel). */
export interface CbmrDesignContext {
  kind: 'fixed' | 'manufacturing';
  hierarchyLevel: string;
  levelLabel: string;
  sequencePath: string;
  manufacturingPath: string;
  caption: string;
  breadcrumb: string;
  rightTabLabel: string;
  provisionSequenceCount: number;
}

/** One mapped evaluation / additional-parameter column from Process Type Master. */
export interface CbmrEvalParamRow {
  label: string;
  db_table: string;
  param_name: string;
}

/** Display chip for Configure BMR header / product table area. */
export interface CbmrEvalParamChip {
  id: string;
  label: string;
  detail: string;
  /** Where this mapping is defined (`sequence_path`) when aggregated across the ladder. */
  sourcePath?: string;
}

export interface CbmrStageTreeNode {
  row: CbmrStructureRow;
  children: CbmrStageTreeNode[];
}

export interface CbmrEquipmentPickRow {
  equipment_name: string;
  make: string;
  equipment_code: string;
}

@Component({
  selector: 'app-configure-bmr',
  templateUrl: './configure-bmr.component.html',
  styleUrls: ['./configure-bmr.component.css'],
})
export class ConfigureBmrComponent implements OnInit {
  readonly deptId = 'process-stage';

  /** Same as `CBMR_DOSAGE_PLACEHOLDER`; exposed for the template `[value]`. */
  readonly cbmrDosagePlaceholder = CBMR_DOSAGE_PLACEHOLDER;

  schemaStatus: 'idle' | 'loading' | 'ready' | 'error' = 'idle';
  schemaError = '';

  definitions: CbmrProcessDef[] = [];
  loadingDefinitions = false;

  selectedDosageForm = CBMR_DOSAGE_PLACEHOLDER;
  selectedProcessTitle = '';

  structureRows: CbmrStructureRow[] = [];
  /** Cached left-rail tree (rebuilt when `structureRows` changes). */
  manufacturingTreeNodes: CbmrStageTreeNode[] = [];
  loadingStructure = false;
  /** Background refresh of manufacturing tree (does not hide the workbench). */
  refreshingManufacturingStructure = false;
  downloadingProvisionPdf = false;

  /** Set false to silence manufacturing click / load logs in the browser console. */
  readonly cbmrMfgDebug = true;

  saving = false;
  existingEntryAt: string | null = null;

  /** Left rail: fixed segment or manufacturing tree node. */
  leftNavId: CbmrLeftNavId = 'productBatch';
  /** When `leftNavId === 'dynamic'`, selected `sequence_path` from Process Type Master. */
  selectedDynamicSequencePath = '';

  /** Left-rail manufacturing tree: expanded `sequence_path` keys (stages / steps with children). */
  expandedManufacturingPaths: string[] = [];

  /** Right rail: main form vs auxiliary modules (each gets its own capture path). */
  rightAuxTab: CbmrRightAuxTab = 'main';

  /** Product & batch header (saved under `__cbmr_product_info__` in capture JSON). */
  productInformation: CbmrProductInformation = {
    bmrScopeMode: 'product_specific',
    product_name: '',
    nmr_number: '',
    product_code: '',
    process_type: '',
    batch_size: '',
    unit_formula_number: '',
    generic_name: '',
  };

  /** Field definitions keyed by Process Type hierarchy `sequence_path` (enterprise form-builder MVP). */
  captureByPath: Record<string, BmrCaptureFieldDef[]> = {};

  /**
   * When Process Type marks Equipments for a node — selected rows for that `sequence_path`, saved under
   * `__equipment_by_path__` in capture_schema_json.
   */
  equipmentUsedByPath: Record<string, CbmrEquipmentPickRow[]> = {};

  equipmentsMasterList: CbmrEquipmentPickRow[] = [];
  equipmentsMasterLoading = false;
  equipmentsMasterError = '';
  private equipmentsMasterFetchAttempted = false;

  /** Provision sequences generated per capture path (from Process Type Master provisions). */
  provisionSequencesByPath: Record<string, CbmrProvisionSequenceEntry[]> = {};

  /** Dropdown selection before generating a provision default-format sequence. */
  selectedProvisionForGenerate = '';

  /** Last provision whose default BMR block is shown / sent to print. */
  lastPrintedProvisionId = '';

  /** Provisions enabled for the selected manufacturing node (provision dropdown). */
  activeProvisionChips: CbmrMasterActionChip[] = [];

  /** Per-path picker: 0 = none, 1-based index into equipmentsMasterList. */
  equipmentDraftIndexByPath: Record<string, number> = {};

  readonly captureControlTypes = [
    'text',
    'textarea',
    'number',
    'decimal',
    'email',
    'date',
    'datetime',
    'time',
    'dropdown',
    'multiselect',
    'autocomplete',
    'checkbox',
    'radio',
    'file',
  ];

  private captureFieldSeed = 1;

  /** Sample rows built from built-in presets (never calls the database). */
  dropdownPreviewByFieldId: Record<string, CbmrDropdownPreviewState> = {};

  /** Sample parent value (documentation only — sample list does not filter by it). */
  capturePreviewDependsValueByFieldId: Record<string, string> = {};

  /** Operator-preview dropdown selection; drives mirrored snippets in preset samples. */
  previewPickedLookupValueByFieldId: Record<string, string> = {};

  /** Plain-language names for dropdown options (stored value stays `captureControlTypes`). */
  private readonly captureFriendlyLabels: Record<string, string> = {
    text: 'Short answer — single line',
    textarea: 'Long answer — multiple lines',
    number: 'Whole number',
    decimal: 'Number with decimals',
    email: 'Email address',
    date: 'Date',
    datetime: 'Date and time',
    time: 'Time only',
    dropdown: 'Pick one — standard list',
    multiselect: 'Pick several — list',
    autocomplete: 'Type to search — list',
    checkbox: 'Yes / No checkbox',
    radio: 'Yes / No or single choice',
    file: 'Attach a file',
  };

  /** Select options with friendly labels for the form designer dropdown. */
  get captureFriendlyControlOptions(): Array<{ value: string; label: string }> {
    return this.captureControlTypes.map((value) => ({
      value,
      label: this.captureFriendlyLabels[value] ?? value,
    }));
  }

  /** Title shown in the designer card header (prioritises what operators will read). */
  designerFieldHeading(f: BmrCaptureFieldDef, indexZeroBased: number): string {
    const label = (f.label || '').trim();
    if (label.length > 0) {
      return label;
    }
    const slug = (f.fieldName || '').trim();
    if (slug.length > 0) {
      return slug;
    }
    return `Question ${indexZeroBased + 1}`;
  }

  /** Short subtitle: input type + required hint. */
  designerFieldSubtitle(f: BmrCaptureFieldDef): string {
    const kind = this.captureFriendlyLabels[f.controlType] || f.controlType || 'Short answer';
    const suffix = f.required ? ' · Operator must fill this' : ' · Optional';
    return kind + suffix;
  }

  operatorPreviewTitle(f: BmrCaptureFieldDef, indexZeroBased: number): string {
    return (f.label || '').trim() || this.designerFieldHeading(f, indexZeroBased);
  }

  operatorPreviewCheckboxCaption(f: BmrCaptureFieldDef, indexZeroBased: number): string {
    return this.operatorPreviewTitle(f, indexZeroBased);
  }

  onDosageFormChange(): void {
    this.structureRows = [];
    this.manufacturingTreeNodes = [];
    this.captureByPath = {};
    this.clearEquipmentProvisioningState();
    this.selectedProcessTitle = '';
    this.existingEntryAt = null;
    this.resetWorkbenchNav();
  }

  onProcessTemplateChange(): void {
    if (this.selectedDosageForm === CBMR_DOSAGE_PLACEHOLDER) {
      this.structureRows = [];
      this.manufacturingTreeNodes = [];
      this.captureByPath = {};
      this.clearEquipmentProvisioningState();
      this.existingEntryAt = null;
      this.resetWorkbenchNav();
      return;
    }
    const df = this.selectedDosageForm.trim();
    const pt = this.selectedProcessTitle.trim();
    if (!df || !pt) {
      this.structureRows = [];
      this.manufacturingTreeNodes = [];
      this.captureByPath = {};
      this.clearEquipmentProvisioningState();
      this.existingEntryAt = null;
      this.resetWorkbenchNav();
      return;
    }
    this.captureByPath = {};
    this.clearEquipmentProvisioningState();
    this.existingEntryAt = null;
    this.resetWorkbenchNav();
    this.refreshStructure({ reloadSavedCapture: true });
  }

  private resetWorkbenchNav(): void {
    this.leftNavId = 'productBatch';
    this.selectedDynamicSequencePath = '';
    this.activeProvisionChips = [];
    this.rightAuxTab = 'main';
    this.applyDefaultProductInformation();
  }

  /** Stage grouping label for the left rail (one section per top-level manufacturing stage). */
  stageSectionHeading(row: CbmrStructureRow): string {
    const cap = `${(row.label_stage || 'Stage').trim()} ${(row.stage_no || '').trim()}: ${(row.stage_title || '').trim()}`.trim();
    if (cap.replace(/[^a-zA-Z0-9]/g, '').length > 0) {
      return cap;
    }
    return this.displayCaption(row);
  }

  /**
   * Provisions and IPC flags enabled in Process Type Master for this node — rendered as header action buttons.
   */
  private masterProvisionCatalog(): { key: keyof CbmrStructureRow; id: string; label: string }[] {
    return [
      { key: 'provision_equipments', id: 'equipments', label: 'Equipments' },
      { key: 'provision_line_clearance', id: 'line_clearance', label: 'Line clearance' },
      { key: 'provision_stage_yield', id: 'stage_yield', label: 'Stage yield' },
      { key: 'provision_inprocess_analysis', id: 'inprocess_analysis', label: 'In-process analysis' },
      { key: 'provision_weighing', id: 'weighing', label: 'Weighing' },
      { key: 'provision_procedure', id: 'procedure', label: 'Procedure' },
      { key: 'provision_additional_parameter', id: 'additional_parameter', label: 'Additional parameter' },
    ];
  }

  enabledMasterActionChips(row: CbmrStructureRow): CbmrMasterActionChip[] {
    const out: CbmrMasterActionChip[] = [];
    for (const t of this.masterProvisionCatalog()) {
      if (this.truthyProvision(row[t.key] as string | number | boolean | undefined | null)) {
        out.push({ id: t.id, label: t.label });
      }
    }
    const ipc = (row.in_process_checks_by || '').trim().toLowerCase();
    if (ipc && ipc !== 'none') {
      out.push({
        id: 'inprocess_checks',
        label: 'In-process checks (' + this.ipcDetail(row) + ')',
      });
    }
    return out;
  }

  scrollToMasterActionChip(id: string): void {
    let targetId = 'cbmr-node-provisions-summary';
    if (id === 'equipments') {
      targetId = 'cbmr-node-provisions-equipments';
    }
    const el = typeof document !== 'undefined' ? document.getElementById(targetId) : null;
    el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  /**
   * Provisions enabled in Process Type Master for the current context —
   * selected manufacturing node, or union across the loaded template when on a fixed segment.
   */
  provisionDropdownOptions(): CbmrMasterActionChip[] {
    if (this.leftNavId === 'dynamic' && this.activeProvisionChips.length) {
      return this.activeProvisionChips;
    }
    if (!this.structureRows.length || this.leftNavId !== 'dynamic') {
      return [];
    }
    const row = this.selectedStructureRow();
    if (!row) {
      return [];
    }
    return this.provisionChipsForRow(row);
  }

  private normalizeSequencePath(path: string | number | null | undefined): string {
    if (path === null || path === undefined) {
      return '';
    }
    return String(path).trim();
  }

  private structureRowByPath(path: string | number | null | undefined): CbmrStructureRow | null {
    const p = this.normalizeSequencePath(path);
    if (!p) {
      return null;
    }
    return this.structureRows.find((r) => this.normalizeSequencePath(r.sequence_path) === p) ?? null;
  }

  /**
   * Provisions for one structure row only — Process Type Master flags (`provision_*` === `1`).
   * Used for the sequential-order dropdown when a stage / step / substep is selected.
   */
  private provisionChipsForRow(row: CbmrStructureRow | null): CbmrMasterActionChip[] {
    if (!row) {
      return [];
    }
    return this.enabledMasterActionChips(row).filter((c) => c.id !== 'inprocess_checks');
  }

  /** Structure rows already loaded for the dosage form + process template in the header. */
  private structureLoadedForCurrentTemplate(): boolean {
    if (!this.structureRows.length) {
      return false;
    }
    const df = this.selectedDosageForm.trim();
    const pt = this.selectedProcessTitle.trim();
    if (!df || df === CBMR_DOSAGE_PLACEHOLDER || !pt) {
      return false;
    }
    const r0 = this.structureRows[0];
    const loaded =
      (r0.dosage_form || '').trim().toLowerCase() === df.toLowerCase() &&
      (r0.process_title || '').trim().toLowerCase() === pt.toLowerCase();
    this.mfgDebugLog('structureLoadedForCurrentTemplate', {
      loaded,
      rowCount: this.structureRows.length,
      header: { dosage_form: df, process_title: pt },
      firstRow: {
        dosage_form: r0.dosage_form,
        process_title: r0.process_title,
      },
    });
    return loaded;
  }

  private mfgDebugLog(message: string, data?: unknown): void {
    if (!this.cbmrMfgDebug) {
      return;
    }
    if (data !== undefined) {
      console.log(`[ConfigureBMR] ${message}`, data);
    } else {
      console.log(`[ConfigureBMR] ${message}`);
    }
  }

  onManufacturingTitleClick(event?: Event): void {
    event?.preventDefault();
    event?.stopPropagation();
    this.mfgDebugLog('Manufacturing (stage master) title click');
    this.reloadManufacturingStructureFromMaster();
  }

  onManufacturingTreeRowClick(row: CbmrStructureRow, event?: Event): void {
    event?.preventDefault();
    event?.stopPropagation();
    const path = this.normalizeSequencePath(row?.sequence_path);
    this.mfgDebugLog('manufacturing tree row click', {
      path,
      hierarchy_level: row?.hierarchy_level,
      stage: row?.stage_title,
      step: row?.step_title,
      substep: row?.substep_title,
    });
    if (!path) {
      this.mfgDebugLog('manufacturing tree row click aborted: empty path');
      return;
    }
    this.selectDynamicNav(path);
  }

  /** Arrow fn so Angular `trackBy` keeps component `this`. */
  manufacturingTreeTrack = (_index: number, node: CbmrStageTreeNode): string => {
    return this.normalizeSequencePath(node.row.sequence_path) || `node-${_index}`;
  };

  manufacturingTreeRowClass(row: CbmrStructureRow): string {
    const lv = (row.hierarchy_level || '').trim().toLowerCase();
    if (lv === 'stage' || lv === 'step' || lv === 'substep') {
      return `cbmr-tree__row--${lv}`;
    }
    return 'cbmr-tree__row--node';
  }

  provisionChipTrack(_index: number, chip: CbmrMasterActionChip): string {
    return chip.id;
  }

  /** Count of generated provision blocks on the main form for a manufacturing `sequence_path`. */
  provisionSequenceCountForManufacturingPath(sequencePath: string): number {
    const p = (sequencePath || '').trim();
    if (!p) {
      return 0;
    }
    return (this.provisionSequencesByPath[p] || []).length;
  }

  hierarchyLevelLabel(row: CbmrStructureRow): string {
    const lv = (row.hierarchy_level || '').toLowerCase();
    if (lv === 'stage') {
      return (row.label_stage || 'Stage').trim();
    }
    if (lv === 'step') {
      return (row.label_step || 'Step').trim();
    }
    if (lv === 'substep') {
      return (row.label_substep || 'Substep').trim();
    }
    return 'Node';
  }

  hierarchyBreadcrumb(row: CbmrStructureRow): string {
    const parts = (row.sequence_path || '').split('.').filter((x) => x.length > 0);
    const crumbs: string[] = [];
    for (let i = 0; i < parts.length; i++) {
      const p = parts.slice(0, i + 1).join('.');
      const r = this.structureRows.find((x) => this.normalizeSequencePath(x.sequence_path) === p);
      if (r) {
        crumbs.push(this.displayCaption(r));
      }
    }
    return crumbs.join(' › ');
  }

  designContextLevelClass(ctx: CbmrDesignContext): string {
    const lv = (ctx.hierarchyLevel || '').toLowerCase();
    if (lv === 'stage') {
      return 'cbmr-design-context--stage';
    }
    if (lv === 'step') {
      return 'cbmr-design-context--step';
    }
    if (lv === 'substep') {
      return 'cbmr-design-context--substep';
    }
    return 'cbmr-design-context--fixed';
  }

  /** Highlighted context: which stage / step / substep (or fixed segment) is being designed. */
  activeDesignContext(): CbmrDesignContext | null {
    const path = this.activeCapturePathKey.trim();
    if (!path) {
      return null;
    }
    const tabNames: Record<CbmrRightAuxTab, string> = {
      main: 'Main form',
      inprocess: 'In-process',
      ipqc: 'IPQC',
      qms: 'QMS',
      breakdown: 'Breakdown',
    };
    const rightTabLabel = tabNames[this.rightAuxTab];

    if (this.leftNavId === 'dynamic') {
      const row = this.selectedStructureRow();
      if (!row) {
        return null;
      }
      const mfgPath = this.normalizeSequencePath(row.sequence_path);
      return {
        kind: 'manufacturing',
        hierarchyLevel: (row.hierarchy_level || '').toLowerCase(),
        levelLabel: this.hierarchyLevelLabel(row),
        sequencePath: path,
        manufacturingPath: mfgPath,
        caption: this.displayCaption(row),
        breadcrumb: this.hierarchyBreadcrumb(row),
        rightTabLabel,
        provisionSequenceCount: (this.provisionSequencesByPath[path] || []).length,
      };
    }

    const fixedLabels: Record<Exclude<CbmrLeftNavId, 'dynamic'>, string> = {
      productBatch: 'Product & batch details',
      approval: 'Approval',
      unitFormula: 'Unit formula',
      dispensing: 'Dispensing',
      equipment: 'Equipment',
    };
    return {
      kind: 'fixed',
      hierarchyLevel: 'fixed',
      levelLabel: 'Fixed segment',
      sequencePath: path,
      manufacturingPath: '',
      caption: fixedLabels[this.leftNavId as Exclude<CbmrLeftNavId, 'dynamic'>],
      breadcrumb: '',
      rightTabLabel,
      provisionSequenceCount: (this.provisionSequencesByPath[path] || []).length,
    };
  }

  isManufacturingDesignTarget(): boolean {
    return this.leftNavId === 'dynamic' && !!this.selectedStructureRow();
  }

  /** Center panel: form builder (fixed segments and manufacturing aux tabs only). */
  showCenterCaptureForm(): boolean {
    if (this.leftNavId === 'productBatch' || !this.activeCapturePathKey.trim()) {
      return false;
    }
    if (this.leftNavId === 'dynamic' && this.rightAuxTab === 'main') {
      return false;
    }
    return true;
  }

  showFixedSegmentHead(): boolean {
    return this.leftNavId !== 'dynamic' && this.leftNavId !== 'productBatch';
  }

  showProvisionPickHint(): boolean {
    return this.leftNavId === 'dynamic' && !this.isManufacturingDesignTarget();
  }

  /** Print only the selected manufacturing stage / step / substep block. */
  printSelectedNodeData(): void {
    if (!this.isManufacturingDesignTarget()) {
      alertify.error('Select a manufacturing stage, step, or substep first.');
      return;
    }
    const el = typeof document !== 'undefined' ? document.getElementById('cbmr-mfg-node-print-root') : null;
    if (!el) {
      alertify.error('Nothing to print for this node.');
      return;
    }
    if (typeof document === 'undefined' || typeof window === 'undefined') {
      return;
    }
    document.body.classList.add('cbmr-print-node-only');
    const cleanup = (): void => {
      document.body.classList.remove('cbmr-print-node-only');
      window.removeEventListener('afterprint', cleanup);
    };
    window.addEventListener('afterprint', cleanup);
    setTimeout(() => window.print(), 120);
  }

  provisionSequencesForActivePath(): CbmrProvisionSequenceEntry[] {
    const p = this.activeCapturePathKey.trim();
    if (!p) {
      return [];
    }
    return [...(this.provisionSequencesByPath[p] || [])].sort((a, b) => a.order - b.order);
  }

  /** Provision shown in preview / print (dropdown selection or last picked in sequence). */
  activeProvisionPreviewId(): string {
    return (this.lastPrintedProvisionId || this.selectedProvisionForGenerate || '').trim();
  }

  canPrintProvisions(): boolean {
    if (!this.isManufacturingDesignTarget()) {
      return false;
    }
    return this.provisionSequencesForActivePath().length > 0 || !!this.activeProvisionPreviewId();
  }

  showProvisionPrintBundle(): boolean {
    return (
      this.provisionSequencesForActivePath().length > 0 || !!this.activeProvisionPreviewId()
    );
  }

  provisionPrintButtonLabel(): string {
    const n = this.provisionSequencesForActivePath().length;
    if (n > 1) {
      return `Print sequence (${n} pages)`;
    }
    if (n === 1) {
      return 'Print sequence (1 page)';
    }
    return 'Print selected provision';
  }

  provisionPageElementId(provisionId: string): string {
    const id = (provisionId || '').trim().replace(/[^a-zA-Z0-9_-]/g, '_');
    return `cbmr-prov-page-${id || 'unknown'}`;
  }

  onProvisionDropdownChange(): void {
    const id = (this.selectedProvisionForGenerate || '').trim();
    if (!id) {
      this.lastPrintedProvisionId = '';
      return;
    }
    this.previewProvisionFormat(id);
    if (id === 'equipments') {
      this.ensureEquipmentsMasterListLoaded();
    }
    setTimeout(() => this.scrollProvisionPrintPreview(), 50);
  }

  provisionFormatTitle(provisionId: string): string {
    const chip = this.provisionDropdownOptions().find((c) => c.id === provisionId);
    return chip?.label || provisionId || 'Provision';
  }

  private previewProvisionFormat(provisionId: string): void {
    const id = (provisionId || '').trim();
    this.lastPrintedProvisionId = id;
  }

  /** Manufacturing node for equipment rows when on a dynamic path. */
  structureRowForActivePath(): CbmrStructureRow | null {
    if (this.leftNavId !== 'dynamic') {
      return null;
    }
    return this.selectedStructureRow();
  }

  equipmentRowsForProvisionPrint(): CbmrEquipmentPickRow[] {
    const row = this.structureRowForActivePath();
    if (!row?.sequence_path) {
      return [];
    }
    return this.equipmentUsedByPath[this.normalizeSequencePath(row.sequence_path)] || [];
  }

  generateProvisionSequence(): void {
    if (!this.isManufacturingDesignTarget()) {
      alertify.error('Select a manufacturing stage, step, or substep on the left to generate sequences.');
      return;
    }
    const path = this.activeCapturePathKey.trim();
    const row = this.selectedStructureRow();
    if (!path || !row) {
      alertify.error('Select a manufacturing stage, step, or substep on the left first.');
      return;
    }
    const id = this.selectedProvisionForGenerate.trim();
    if (!id) {
      alertify.error('Select a provision from the list.');
      return;
    }
    const chip = this.provisionDropdownOptions().find((c) => c.id === id);
    if (!chip) {
      alertify.error('That provision is not enabled in Process Type Master for this node.');
      return;
    }
    const list = [...(this.provisionSequencesByPath[path] || [])];
    if (list.some((e) => e.id === chip.id)) {
      alertify.error(`${chip.label} is already in the sequence for this ${this.hierarchyLevelLabel(row).toLowerCase()}.`);
      return;
    }
    list.push({
      id: chip.id,
      label: chip.label,
      order: list.length + 1,
      sequence_path: this.normalizeSequencePath(row.sequence_path),
      hierarchy_level: (row.hierarchy_level || '').trim(),
    });
    this.provisionSequencesByPath = { ...this.provisionSequencesByPath, [path]: list };
    this.selectedProvisionForGenerate = chip.id;
    this.previewProvisionFormat(chip.id);
    if (chip.id === 'equipments') {
      this.ensureEquipmentsMasterListLoaded();
    }
    setTimeout(() => {
      this.scrollToProvisionPage(chip.id);
      this.scrollProvisionPrintPreview();
    }, 80);
    const ctx = this.displayCaption(row);
    alertify.success(`Added to sequence (${list.length}): ${chip.label}. Form added as page ${list.length}.`);
  }

  /**
   * Ensure every provision enabled in Process Type Master for the selected node
   * has a generated sequence block (skips ones already present).
   */
  private ensureProvisionSequencesForActiveNode(): number {
    if (!this.isManufacturingDesignTarget()) {
      return 0;
    }
    const chips = this.provisionDropdownOptions();
    if (!chips.length) {
      return 0;
    }
    const path = this.activeCapturePathKey.trim();
    const row = this.selectedStructureRow();
    if (!path || !row) {
      return 0;
    }
    let added = 0;
    for (const chip of chips) {
      const list = [...(this.provisionSequencesByPath[path] || [])];
      if (list.some((e) => e.id === chip.id)) {
        continue;
      }
      list.push({
        id: chip.id,
        label: chip.label,
        order: list.length + 1,
        sequence_path: this.normalizeSequencePath(row.sequence_path),
        hierarchy_level: (row.hierarchy_level || '').trim(),
      });
      this.provisionSequencesByPath = { ...this.provisionSequencesByPath, [path]: list };
      added++;
    }
    if (chips.some((c) => c.id === 'equipments')) {
      this.ensureEquipmentsMasterListLoaded();
    }
    const sequences = this.provisionSequencesForActivePath();
    if (sequences.length) {
      this.lastPrintedProvisionId = sequences[sequences.length - 1].id;
      this.selectedProvisionForGenerate = sequences[0].id;
      if (added > 0) {
        setTimeout(() => this.scrollProvisionPrintPreview(), 80);
      }
    }
    return added;
  }

  /** Add every provision enabled for this stage / step / substep in one action. */
  generateAllProvisionsForActiveNode(): void {
    if (!this.isManufacturingDesignTarget()) {
      alertify.error('Select a manufacturing stage, step, or substep on the left.');
      return;
    }
    const chips = this.provisionDropdownOptions();
    if (!chips.length) {
      alertify.error('No provisions enabled for this node in Process Type Master.');
      return;
    }
    const added = this.ensureProvisionSequencesForActiveNode();
    const row = this.selectedStructureRow();
    if (added > 0) {
      setTimeout(() => this.scrollProvisionPrintPreview(), 80);
    }
    const total = this.provisionSequencesForActivePath().length;
    alertify.success(
      `${total} provision form(s) in sequence for ${row ? this.displayCaption(row) : 'this node'} — prints as ${total} page(s).`,
    );
  }

  scrollProvisionPrintPreview(): void {
    const el = typeof document !== 'undefined' ? document.getElementById('cbmr-provision-print-root') : null;
    el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  scrollToProvisionPage(provisionId: string): void {
    if (typeof document === 'undefined') {
      return;
    }
    document.getElementById(this.provisionPageElementId(provisionId))?.scrollIntoView({
      behavior: 'smooth',
      block: 'start',
    });
  }

  canDownloadProvisionSequencePdf(): boolean {
    if (this.selectedDosageForm === CBMR_DOSAGE_PLACEHOLDER || !this.selectedProcessTitle?.trim()) {
      return false;
    }
    return this.structureRows.length > 0;
  }

  /** Every stage / step / substep that has at least one provision in its sequence (hierarchy order). */
  allManufacturingProvisionExportBundles(): CbmrProvisionExportBundle[] {
    const sorted = [...this.structureRows].sort((a, b) =>
      this.compareSequencePath(
        this.normalizeSequencePath(a.sequence_path),
        this.normalizeSequencePath(b.sequence_path),
      ),
    );
    const out: CbmrProvisionExportBundle[] = [];
    for (const row of sorted) {
      const path = this.normalizeSequencePath(row.sequence_path);
      if (!path) {
        continue;
      }
      const sequences = [...(this.provisionSequencesByPath[path] || [])].sort((a, b) => a.order - b.order);
      if (!sequences.length) {
        continue;
      }
      out.push({ path, row, sequences });
    }
    return out;
  }

  allManufacturingProvisionExportNodeCount(): number {
    return this.allManufacturingProvisionExportBundles().length;
  }

  allManufacturingProvisionExportPageCount(): number {
    return this.allManufacturingProvisionExportBundles().reduce((n, b) => n + b.sequences.length, 0);
  }

  /** Left rail: complete BMR PDF (all stages / steps / substeps) via server TCPDF. */
  downloadProvisionSequencePdf(): void {
    if (!this.canDownloadProvisionSequencePdf()) {
      alertify.error('Load a process template with manufacturing structure first.');
      return;
    }
    const df = this.selectedDosageForm;
    const pt = this.selectedProcessTitle;
    if (!df || df === CBMR_DOSAGE_PLACEHOLDER || !pt?.trim()) {
      alertify.error('Select dosage form and process template first.');
      return;
    }

    this.downloadingProvisionPdf = true;
    const body = {
      dosage_form: df,
      process_title: pt,
      capture_schema: {
        [CBMR_EQUIP_BY_PATH_KEY]: this.equipmentUsedByPath,
        [CBMR_PROVISION_SEQUENCES_KEY]: this.provisionSequencesByPath,
        [CBMR_PRODUCT_INFO_KEY]: { ...this.productInformation },
      },
    };

    this.api.postPdfBlob('master/configure_bmr_export_pdf.php?export=1', JSON.stringify(body)).subscribe({
      next: (resp: HttpResponse<Blob>) => {
        void this.handleProvisionSequencePdfResponse(resp);
      },
      error: () => {
        alertify.error('Could not generate PDF. Try again.');
        this.downloadingProvisionPdf = false;
      },
    });
  }

  private async handleProvisionSequencePdfResponse(resp: HttpResponse<Blob>): Promise<void> {
    try {
      const blob = resp.body;
      if (!blob) {
        alertify.error('Empty PDF response from server.');
        return;
      }
      const ct = (resp.headers.get('Content-Type') || '').toLowerCase();
      if (ct.includes('application/json') || ct.includes('text/')) {
        const text = await blob.text();
        let msg = 'Could not generate PDF.';
        try {
          const parsed = JSON.parse(text) as { message?: string };
          if (parsed.message) {
            msg = parsed.message;
          }
        } catch {
          if (text.trim()) {
            msg = text.trim().slice(0, 200);
          }
        }
        alertify.error(msg);
        return;
      }
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = this.provisionSequencePdfFileName();
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
      alertify.success('Complete BMR PDF downloaded.');
    } catch {
      alertify.error('Could not save PDF file.');
    } finally {
      this.downloadingProvisionPdf = false;
      this.cdr.detectChanges();
    }
  }

  provisionSequencePdfFileName(): string {
    const pt = (this.selectedProcessTitle || 'template').trim().replace(/[^\w.-]+/g, '_');
    const df = (this.selectedDosageForm || 'dosage').trim().replace(/[^\w.-]+/g, '_');
    return `BMR_${df}_${pt}_complete.pdf`;
  }

  printProvisionFormat(): void {
    if (!this.isManufacturingDesignTarget()) {
      alertify.error('Select a manufacturing stage, step, or substep first.');
      return;
    }
    const sequences = this.provisionSequencesForActivePath();
    if (sequences.length) {
      this.printFullProvisionSequence();
      return;
    }
    const id = this.activeProvisionPreviewId();
    if (!id) {
      alertify.error('Select a provision from the dropdown first.');
      return;
    }
    this.runProvisionPrint([id]);
  }

  /** Print every provision form in sequence order (one PDF-style page each). */
  printFullProvisionSequence(): void {
    const sequences = this.provisionSequencesForActivePath();
    if (!sequences.length) {
      alertify.error('Add provisions to the sequence first.');
      return;
    }
    if (!this.isManufacturingDesignTarget()) {
      alertify.error('Select a manufacturing stage, step, or substep first.');
      return;
    }
    if (sequences.some((s) => s.id === 'equipments')) {
      this.ensureEquipmentsMasterListLoaded();
    }
    this.runProvisionPrint(sequences.map((s) => s.id));
    alertify.success(`Printing ${sequences.length} provision form(s) in sequence order.`);
  }

  private runProvisionPrint(provisionIds: string[]): void {
    if (typeof document === 'undefined' || typeof window === 'undefined') {
      return;
    }
    const root = document.getElementById('cbmr-provision-print-root');
    if (!root) {
      alertify.error('Provision forms are not available to print.');
      return;
    }
    document.body.classList.add('cbmr-print-provision-only');
    const cleanup = (): void => {
      document.body.classList.remove('cbmr-print-provision-only');
      window.removeEventListener('afterprint', cleanup);
    };
    window.addEventListener('afterprint', cleanup);
    setTimeout(() => window.print(), 150);
  }

  removeProvisionSequence(path: string, index: number): void {
    const p = (path || '').trim();
    if (!p || index < 0) {
      return;
    }
    const list = [...(this.provisionSequencesByPath[p] || [])];
    list.splice(index, 1);
    const next = { ...this.provisionSequencesByPath };
    if (list.length === 0) {
      delete next[p];
    } else {
      next[p] = list.map((e, i) => ({ ...e, order: i + 1 }));
    }
    this.provisionSequencesByPath = next;
  }

  showProvisionFormat(provisionId: string): void {
    this.selectedProvisionForGenerate = provisionId;
    this.previewProvisionFormat(provisionId);
    if (provisionId === 'equipments') {
      this.ensureEquipmentsMasterListLoaded();
    }
    setTimeout(() => {
      this.scrollToProvisionPage(provisionId);
      this.scrollProvisionPrintPreview();
    }, 50);
  }

  isProvisionSequenceItemActive(seq: CbmrProvisionSequenceEntry): boolean {
    return seq.id === this.activeProvisionPreviewId();
  }

  private applyDefaultProductInformation(): void {
    this.productInformation = {
      bmrScopeMode: 'product_specific',
      product_name: '',
      nmr_number: '',
      product_code: '',
      process_type: (this.selectedProcessTitle || '').trim(),
      batch_size: '',
      unit_formula_number: '',
      generic_name: '',
    };
  }

  private ingestProductInfoFromRaw(raw: unknown): void {
    const pt = (this.selectedProcessTitle || '').trim();
    if (!raw || typeof raw !== 'object' || Array.isArray(raw)) {
      this.applyDefaultProductInformation();
      return;
    }
    const o = raw as Record<string, unknown>;
    const modeRaw = String(o['bmrScopeMode'] ?? '').trim().toLowerCase();
    this.productInformation = {
      bmrScopeMode: modeRaw === 'common' ? 'common' : 'product_specific',
      product_name: this.coerceString(o['product_name']).trim(),
      nmr_number: this.coerceString(o['nmr_number']).trim(),
      product_code: this.coerceString(o['product_code']).trim(),
      process_type: this.coerceString(o['process_type']).trim() || pt,
      batch_size: this.coerceString(o['batch_size']).trim(),
      unit_formula_number: this.coerceString(o['unit_formula_number']).trim(),
      generic_name: this.coerceString(o['generic_name']).trim(),
    };
  }

  selectFixedNav(id: CbmrLeftNavId): void {
    if (id !== 'dynamic') {
      this.leftNavId = id;
      this.selectedDynamicSequencePath = '';
      this.activeProvisionChips = [];
      this.resetProvisionUiState();
    }
  }

  selectDynamicNav(sequencePath: string | number): void {
    const p = this.normalizeSequencePath(sequencePath);
    this.mfgDebugLog('selectDynamicNav', {
      sequencePath,
      normalizedPath: p,
      leftNavId: this.leftNavId,
      structureRowCount: this.structureRows.length,
    });
    if (!p) {
      this.mfgDebugLog('selectDynamicNav aborted: empty path');
      return;
    }
    const dfRaw = this.selectedDosageForm;
    const pt = this.selectedProcessTitle.trim();
    if (dfRaw === CBMR_DOSAGE_PLACEHOLDER || !dfRaw.trim() || !pt) {
      this.mfgDebugLog('selectDynamicNav aborted: missing dosage or process template', {
        dosage_form: dfRaw,
        process_title: pt,
      });
      alertify.error('Choose dosage form and process template above, then pick a manufacturing line.');
      return;
    }
    const apply = () => this.applyManufacturingNodeSelectionIfPathExists(p);
    if (this.structureLoadedForCurrentTemplate()) {
      this.mfgDebugLog('selectDynamicNav: applying from loaded structure (no API)');
      apply();
      return;
    }
    this.mfgDebugLog('selectDynamicNav: loading structure from API then apply');
    const ok = this.reloadManufacturingStructureFromMaster(apply);
    if (!ok) {
      this.mfgDebugLog('selectDynamicNav: reloadManufacturingStructureFromMaster returned false');
    }
  }

  /**
   * Pull latest stage / step / substep lines from the same DB table Process Type Master uses
   * (`cyclone_process_type_stage_lines`), for the dosage form + process template selected above.
   */
  reloadManufacturingStructureFromMaster(afterRowsLoaded?: () => void): boolean {
    const dfRaw = this.selectedDosageForm;
    const pt = this.selectedProcessTitle.trim();
    this.mfgDebugLog('reloadManufacturingStructureFromMaster', {
      dosage_form: dfRaw,
      process_title: pt,
      hasAfterLoad: !!afterRowsLoaded,
    });
    if (dfRaw === CBMR_DOSAGE_PLACEHOLDER || !dfRaw.trim() || !pt) {
      alertify.error('Choose dosage form and process template above, then pick a manufacturing line.');
      return false;
    }
    this.leftNavId = 'dynamic';
    this.refreshStructure({
      reloadSavedCapture: false,
      afterLoad: () => {
        this.mfgDebugLog('reloadManufacturingStructureFromMaster afterLoad', {
          rowCount: this.structureRows.length,
        });
        if (this.structureRows.length && !afterRowsLoaded) {
          alertify.success(
            `Loaded ${this.structureRows.length} stage / step / substep line(s) from Process Type Master.`,
          );
        }
        afterRowsLoaded?.();
      },
    });
    return true;
  }

  private applyManufacturingNodeSelectionIfPathExists(p: string): void {
    const row = this.structureRowByPath(p);
    this.mfgDebugLog('applyManufacturingNodeSelectionIfPathExists', {
      path: p,
      found: !!row,
      pathsInStructure: this.structureRows.map((r) => this.normalizeSequencePath(r.sequence_path)),
    });
    if (!row) {
      alertify.error(
        `Path "${p}" was not returned from Process Type Master for this process template.`,
      );
      return;
    }
    this.applyManufacturingNodeSelection(p);
  }

  private applyManufacturingNodeSelection(p: string): void {
    this.leftNavId = 'dynamic';
    this.selectedDynamicSequencePath = p;
    this.rightAuxTab = 'main';
    this.expandManufacturingAncestors(p);
    this.activeProvisionChips = this.provisionChipsForRow(this.structureRowByPath(p));
    this.selectedProvisionForGenerate = this.activeProvisionChips[0]?.id ?? '';
    if (this.selectedProvisionForGenerate) {
      this.previewProvisionFormat(this.selectedProvisionForGenerate);
    } else {
      this.lastPrintedProvisionId = '';
    }
    this.mfgDebugLog('applyManufacturingNodeSelection', {
      path: p,
      leftNavId: this.leftNavId,
      activeCapturePathKey: this.activeCapturePathKey,
      provisionChips: this.activeProvisionChips,
    });
    setTimeout(() => {
      this.scrollProvisionGeneratePanel();
      this.scrollManufacturingSelectionIntoView();
    }, 0);
  }

  /** After tree selection, pre-fill the provision dropdown for that stage / step / substep. */
  private syncProvisionDropdownForActiveNode(): void {
    const row = this.structureRowByPath(this.selectedDynamicSequencePath);
    this.activeProvisionChips = this.provisionChipsForRow(row);
    this.selectedProvisionForGenerate = this.activeProvisionChips[0]?.id ?? '';
    if (this.selectedProvisionForGenerate) {
      this.previewProvisionFormat(this.selectedProvisionForGenerate);
    } else {
      this.lastPrintedProvisionId = '';
    }
  }

  private scrollProvisionGeneratePanel(): void {
    if (typeof document === 'undefined') {
      return;
    }
    document.getElementById('cbmr-provision-gen-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  /** Bring the center panel into view after picking a tree node. */
  private scrollManufacturingSelectionIntoView(): void {
    if (typeof document === 'undefined') {
      return;
    }
    document.querySelector<HTMLElement>('.cbmr-center__body')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  isMfgExpanded(sequencePath: string): boolean {
    const p = (sequencePath || '').trim();
    return p.length > 0 && this.expandedManufacturingPaths.includes(p);
  }

  toggleMfgExpand(sequencePath: string, event?: Event): void {
    event?.stopPropagation();
    event?.preventDefault();
    const p = (sequencePath || '').trim();
    if (!p) {
      return;
    }
    if (this.isMfgExpanded(p)) {
      this.expandedManufacturingPaths = this.expandedManufacturingPaths.filter((x) => x !== p);
    } else {
      this.expandedManufacturingPaths = [...this.expandedManufacturingPaths, p];
    }
  }

  /** Expand all ancestors so the selected path is visible in the left tree. */
  private expandManufacturingAncestors(sequencePath: string, into?: Set<string>): void {
    const parts = (sequencePath || '').split('.').filter((x) => x.length > 0);
    const set = into ?? new Set(this.expandedManufacturingPaths);
    for (let i = 1; i < parts.length; i++) {
      set.add(parts.slice(0, i).join('.'));
    }
    this.expandedManufacturingPaths = [...set];
  }

  /** After structure load, expand every parent that has children so steps/substeps are visible. */
  private syncExpandedPathsAfterStructureLoad(): void {
    const expanded = new Set<string>();
    for (const row of this.structureRows) {
      const p = this.normalizeSequencePath(row.sequence_path);
      if (!p) {
        continue;
      }
      const hasChild = this.structureRows.some((r) => this.parentSequencePath(this.normalizeSequencePath(r.sequence_path)) === p);
      if (hasChild) {
        expanded.add(p);
      }
    }
    const sel = this.normalizeSequencePath(this.selectedDynamicSequencePath);
    if (sel) {
      this.expandManufacturingAncestors(sel, expanded);
    }
    this.expandedManufacturingPaths = [...expanded];
  }

  setRightAuxTab(tab: CbmrRightAuxTab): void {
    this.rightAuxTab = tab;
    this.resetProvisionUiState();
  }

  private resetProvisionUiState(): void {
    this.lastPrintedProvisionId = '';
  }

  /** Tree of manufacturing rows for the left rail (nested by `sequence_path`). */
  manufacturingTree(): CbmrStageTreeNode[] {
    return this.manufacturingTreeNodes;
  }

  private rebuildManufacturingTree(): void {
    this.manufacturingTreeNodes = this.buildManufacturingTree(this.structureRows);
    this.mfgDebugLog('rebuildManufacturingTree', {
      rowCount: this.structureRows.length,
      rootCount: this.manufacturingTreeNodes.length,
      paths: this.structureRows.map((r) => ({
        path: this.normalizeSequencePath(r.sequence_path),
        level: r.hierarchy_level,
      })),
    });
  }

  private compareSequencePath(a: string, b: string): number {
    const sa = (a || '').split('.').filter((x) => x.length > 0).map((x) => parseInt(x, 10) || 0);
    const sb = (b || '').split('.').filter((x) => x.length > 0).map((x) => parseInt(x, 10) || 0);
    const len = Math.max(sa.length, sb.length);
    for (let i = 0; i < len; i++) {
      const na = sa[i] ?? 0;
      const nb = sb[i] ?? 0;
      if (na !== nb) {
        return na - nb;
      }
    }
    return 0;
  }

  private parentSequencePath(path: string): string {
    const p = (path || '').trim();
    const i = p.lastIndexOf('.');
    return i > 0 ? p.slice(0, i) : '';
  }

  private buildManufacturingTree(rows: CbmrStructureRow[]): CbmrStageTreeNode[] {
    const sorted = [...rows].sort((a, b) =>
      this.compareSequencePath(this.normalizeSequencePath(a.sequence_path), this.normalizeSequencePath(b.sequence_path))
    );
    const nodeByPath = new Map<string, CbmrStageTreeNode>();
    for (const row of sorted) {
      const path = this.normalizeSequencePath(row.sequence_path);
      if (!path) {
        continue;
      }
      nodeByPath.set(path, { row, children: [] });
    }
    const root: CbmrStageTreeNode[] = [];
    for (const row of sorted) {
      const path = this.normalizeSequencePath(row.sequence_path);
      const node = nodeByPath.get(path);
      if (!node) {
        continue;
      }
      const parentPath = this.parentSequencePath(path);
      const parent = parentPath ? nodeByPath.get(parentPath) : undefined;
      if (parent) {
        parent.children.push(node);
      } else {
        root.push(node);
      }
    }
    return root;
  }

  fixedPathForNav(id: CbmrLeftNavId): string {
    if (id === 'dynamic') {
      return '';
    }
    return CBMR_FIXED_PATH[id];
  }

  /** Path key for capture / SQL preview for the current left + right selection. */
  get activeCapturePathKey(): string {
    const base = this.leftNavBasePath();
    if (!base) {
      return '';
    }
    if (this.rightAuxTab === 'main') {
      return base;
    }
    const slug = base.replace(/\./g, '_').replace(/[^a-zA-Z0-9_]/g, '_').replace(/_+/g, '_').slice(0, 120) || 'node';
    return `__cbmr_aux__/${this.rightAuxTab}/${slug}`;
  }

  private leftNavBasePath(): string {
    if (this.leftNavId === 'dynamic') {
      return (this.selectedDynamicSequencePath || '').trim();
    }
    return CBMR_FIXED_PATH[this.leftNavId];
  }

  selectedStructureRow(): CbmrStructureRow | null {
    if (this.leftNavId !== 'dynamic') {
      return null;
    }
    return this.structureRowByPath(this.selectedDynamicSequencePath);
  }

  /** Top stripe title (matches sketch: batch header + stage header). */
  centerTopTitle(): string {
    if (this.leftNavId === 'dynamic') {
      const row = this.selectedStructureRow();
      return row ? this.displayCaption(row) : 'Manufacturing stage';
    }
    const labels: Record<Exclude<CbmrLeftNavId, 'dynamic'>, string> = {
      productBatch: '',
      approval: 'Approval',
      unitFormula: 'Unit formula',
      dispensing: 'Dispensing',
      equipment: 'Equipment',
    };
    return labels[this.leftNavId as Exclude<CbmrLeftNavId, 'dynamic'>];
  }

  centerSubTitle(): string {
    const t = this.rightAuxTab;
    if (t === 'main') {
      return '';
    }
    const names: Record<Exclude<CbmrRightAuxTab, 'main'>, string> = {
      inprocess: 'In-process',
      ipqc: 'IPQC',
      qms: 'QMS',
      breakdown: 'Breakdown',
    };
    return names[t];
  }

  isDynamicNodeActive(sequencePath: string | number): boolean {
    return (
      this.leftNavId === 'dynamic' &&
      this.normalizeSequencePath(this.selectedDynamicSequencePath) === this.normalizeSequencePath(sequencePath)
    );
  }

  private isSyntheticCapturePathKey(k: string): boolean {
    return k.startsWith('__cbmr_fixed__/') || k.startsWith('__cbmr_aux__/');
  }

  /** Distinct dosage values from Process Type Master lines (order for dropdown). */
  get dosageFormOptions(): string[] {
    const seen = new Set<string>();
    const list: string[] = [];
    for (const d of this.definitions) {
      const v = d.dosage_form ?? '';
      if (!seen.has(v)) {
        seen.add(v);
        list.push(v);
      }
    }
    list.sort((a, b) =>
      (a || '').trim().localeCompare((b || '').trim(), undefined, { sensitivity: 'base' }),
    );
    return list;
  }
  constructor(
    private readonly api: DataAccessService,
    private readonly masterHubReturn: MasterHubReturnService,
    private readonly cdr: ChangeDetectorRef,
  ) {}

  ngOnInit(): void {
    this.masterHubReturn.setReturnDepartment(this.deptId);
    this.runEnsureSchema();
  }

  private runEnsureSchema(): void {
    this.schemaStatus = 'loading';
    this.schemaError = '';
    this.api.get('master/configure_bmr_map_api.php?type=ensure_schema').subscribe({
      next: (res: unknown) => {
        const o = res as { status?: string; message?: string };
        if (o?.status === 'success') {
          this.schemaStatus = 'ready';
          this.loadDefinitions();
        } else {
          this.schemaStatus = 'error';
          this.schemaError = o?.message || 'Schema check failed.';
          alertify.error(this.schemaError);
        }
      },
      error: () => {
        this.schemaStatus = 'error';
        this.schemaError = 'Cannot reach Configure BMR API.';
        alertify.error(this.schemaError);
      },
    });
  }

  loadDefinitions(): void {
    this.loadingDefinitions = true;
    this.api.get('master/configure_bmr_map_api.php?type=list_process_definitions').subscribe({
      next: (res: unknown) => {
        this.loadingDefinitions = false;
        const o = res as { status?: string; definitions?: CbmrProcessDef[] };
        this.definitions = Array.isArray(o?.definitions) ? o.definitions : [];
      },
      error: () => {
        this.loadingDefinitions = false;
        alertify.error('Could not load process definitions.');
      },
    });
  }

  /**
   * Load saved capture_schema for this process template (`dosage_form` + `process_title`).
   * Ladder rows always come fresh from Process Type Master via `refreshStructure`; this only restores UI questions per path.
   */
  private loadSavedCaptureSchema(dosageForm: string, processTitle: string): void {
    const pdf = dosageForm.trim();
    const ppt = processTitle.trim();
    if (!pdf || !ppt) {
      return;
    }
    const q =
      'master/configure_bmr_map_api.php?type=get_mapping&dosage_form=' +
      encodeURIComponent(pdf) +
      '&process_title=' +
      encodeURIComponent(ppt);
    this.api.get(q).subscribe({
      next: (res: unknown) => {
        const o = res as {
          status?: string;
          mapping?: {
            dosage_form?: string;
            process_title?: string;
            entry_at?: string;
            capture_schema?: Record<string, unknown> | unknown[] | unknown;
          } | null;
        };
        if (
          this.selectedDosageForm.trim() !== pdf ||
          this.selectedProcessTitle.trim() !== ppt
        ) {
          return;
        }
        const m = o?.mapping;
        if (!m) {
          this.existingEntryAt = null;
          this.ingestCaptureSchemaInbound(null);
          this.syncCapturePathsAfterStructureLoad();
          return;
        }
        this.existingEntryAt = m.entry_at ?? null;
        this.ingestCaptureSchemaInbound(m.capture_schema);
        this.syncCapturePathsAfterStructureLoad();
      },
      error: () => {
        /* optional — keep locally edited templates */
      },
    });
  }

  refreshStructure(opts?: { reloadSavedCapture?: boolean; afterLoad?: () => void }): void {
    const reloadSaved = !!opts?.reloadSavedCapture;
    const dfRaw = this.selectedDosageForm;
    const pt = this.selectedProcessTitle.trim();
    if (dfRaw === CBMR_DOSAGE_PLACEHOLDER || !dfRaw.trim()) {
      this.structureRows = [];
      this.manufacturingTreeNodes = [];
      return;
    }
    const df = dfRaw.trim();
    if (!pt) {
      this.structureRows = [];
      this.manufacturingTreeNodes = [];
      return;
    }
    if (reloadSaved) {
      this.loadingStructure = true;
    } else {
      this.refreshingManufacturingStructure = true;
    }
    const q =
      'master/configure_bmr_map_api.php?type=structure&dosage_form=' +
      encodeURIComponent(df) +
      '&process_title=' +
      encodeURIComponent(pt);
    this.api.get(q).subscribe({
      next: (res: unknown) => {
        this.loadingStructure = false;
        this.refreshingManufacturingStructure = false;
        const o = res as { status?: string; rows?: CbmrStructureRow[]; message?: string };
        if (o?.status === 'success' && Array.isArray(o.rows)) {
          this.structureRows = o.rows;
          this.rebuildManufacturingTree();
          if (
            this.leftNavId === 'dynamic' &&
            this.normalizeSequencePath(this.selectedDynamicSequencePath) &&
            !this.structureRows.some(
              (r) =>
                this.normalizeSequencePath(r.sequence_path) ===
                this.normalizeSequencePath(this.selectedDynamicSequencePath),
            )
          ) {
            this.selectedDynamicSequencePath = '';
          }
          this.syncExpandedPathsAfterStructureLoad();
          if (this.leftNavId === 'dynamic' && this.normalizeSequencePath(this.selectedDynamicSequencePath)) {
            this.syncProvisionDropdownForActiveNode();
          }
          this.syncCapturePathsAfterStructureLoad();
          if (this.structureRows.some((r) => this.provisionEquipmentsEnabled(r))) {
            this.ensureEquipmentsMasterListLoaded();
          }
          const doReloadSaved =
            reloadSaved &&
            df === this.selectedDosageForm.trim() &&
            pt === this.selectedProcessTitle.trim();
          if (doReloadSaved) {
            this.loadSavedCaptureSchema(df, pt);
          }
          opts?.afterLoad?.();
        } else {
          this.structureRows = [];
          this.manufacturingTreeNodes = [];
          if (o?.message) {
            alertify.error(o.message);
          }
          this.mfgDebugLog('refreshStructure failed', o);
          opts?.afterLoad?.();
        }
      },
      error: (err: unknown) => {
        this.loadingStructure = false;
        this.refreshingManufacturingStructure = false;
        this.structureRows = [];
        this.manufacturingTreeNodes = [];
        this.mfgDebugLog('refreshStructure HTTP error', err);
        alertify.error('Could not load structure.');
        opts?.afterLoad?.();
      },
    });
  }

  /** Process definitions matching the selected dosage form. */
  filteredDefinitions(): CbmrProcessDef[] {
    if (this.selectedDosageForm === CBMR_DOSAGE_PLACEHOLDER) {
      return [];
    }
    const dfSel = (this.selectedDosageForm ?? '').trim().toLowerCase();
    return this.definitions.filter((d) => (d.dosage_form || '').trim().toLowerCase() === dfSel);
  }

  /** Short label for process picker (dosage is chosen separately). */
  processDefLabel(d: CbmrProcessDef): string {
    const t = (d.process_title || '').trim();
    const n = d.line_count ?? 0;
    return `${t} (${n} lines)`;
  }

  dosageFormOptionLabel(df: string): string {
    const s = df ?? '';
    return s.trim() === '' ? '(blank dosage)' : s;
  }

  displayCaption(row: CbmrStructureRow): string {
    const lv = (row.hierarchy_level || '').toLowerCase();
    if (lv === 'stage') {
      return `${row.label_stage || 'Stage'} ${row.stage_no}: ${row.stage_title}`;
    }
    if (lv === 'step') {
      return `${row.label_step || 'Step'} ${row.step_no}: ${row.step_title}`;
    }
    if (lv === 'substep') {
      return `${row.label_substep || 'Substep'} ${row.substep_no}: ${row.substep_title}`;
    }
    return row.sequence_path || 'Node';
  }

  /** Compact label for the left manufacturing tree (path shows hierarchy). */
  manufacturingTreeCaption(row: CbmrStructureRow): string {
    const lv = (row.hierarchy_level || '').toLowerCase();
    if (lv === 'stage') {
      const title = (row.stage_title || '').trim();
      if (title) {
        return title;
      }
      return `${(row.label_stage || 'Stage').trim()} ${(row.stage_no || '').trim()}`.trim();
    }
    if (lv === 'step') {
      const title = (row.step_title || '').trim();
      if (title) {
        return title;
      }
      return `${(row.label_step || 'Step').trim()} ${(row.step_no || '').trim()}`.trim();
    }
    if (lv === 'substep') {
      const title = (row.substep_title || '').trim();
      if (title) {
        return title;
      }
      return `${(row.label_substep || 'Substep').trim()} ${(row.substep_no || '').trim()}`.trim();
    }
    return this.displayCaption(row);
  }

  indentClass(row: CbmrStructureRow): string {
    const lv = (row.hierarchy_level || '').toLowerCase();
    if (lv === 'stage') {
      return 'cbmr-ladder__item--stage';
    }
    if (lv === 'step') {
      return 'cbmr-ladder__item--step';
    }
    return 'cbmr-ladder__item--sub';
  }

  /** Matches Process Type Master provision flags — lists human labels that are enabled. */
  provisionDetailLine(row: CbmrStructureRow): string {
    const toggles: { key: keyof CbmrStructureRow; label: string }[] = [
      { key: 'provision_equipments', label: 'Equipments' },
      { key: 'provision_line_clearance', label: 'Line clearance' },
      { key: 'provision_stage_yield', label: 'Stage yield' },
      { key: 'provision_inprocess_analysis', label: 'In-process analysis' },
      { key: 'provision_weighing', label: 'Weighing' },
      { key: 'provision_procedure', label: 'Procedure' },
      { key: 'provision_additional_parameter', label: 'Additional parameter' },
    ];
    const enabled = toggles.filter((t) => this.truthyProvision(row[t.key])).map((t) => t.label);
    return enabled.length > 0 ? enabled.join('; ') : 'None';
  }

  additionalParamsDetail(row: CbmrStructureRow): string {
    const list = this.parseEvaluationParamRows(row);
    if (!list.length) {
      return '';
    }
    return list
      .map((x) => {
        const L = (x.label || '').trim();
        const t = (x.db_table || '').trim();
        const c = (x.param_name || '').trim();
        const rhs = t && c ? `${t}.${c}` : (t || c);
        if (L && rhs) {
          return `${L} → ${rhs}`;
        }
        return L || rhs;
      })
      .filter((s) => s.length > 0)
      .join('; ');
  }

  /**
   * Additional-parameter mappings from Process Type Master for the selected node —
   * shown as chips under stage actions when `provision_additional_parameter` is enabled.
   */
  evaluationParameterChips(row: CbmrStructureRow | null): CbmrEvalParamChip[] {
    if (!row || !this.truthyProvision(row.provision_additional_parameter)) {
      return [];
    }
    const seq = String(row.sequence_path || '').replace(/[^a-zA-Z0-9]+/g, '_') || 'n';
    return this.parseEvaluationParamRows(row).map((p, i) => {
      const lbl = (p.label || '').trim();
      const path = `${(p.db_table || '').trim()}.${(p.param_name || '').trim()}`.replace(/^\./, '').replace(/\.$/, '');
      return {
        id: `eval-${seq}-${i}`,
        label: lbl || path || `Parameter ${i + 1}`,
        detail: path ? path : lbl,
        sourcePath: this.normalizeSequencePath(row.sequence_path),
      };
    });
  }

  /**
   * All evaluation-parameter mappings from the ladder (deduped) — shown below the product & batch header table.
   */
  aggregatedEvaluationChips(): CbmrEvalParamChip[] {
    const dedupe = new Set<string>();
    const chips: CbmrEvalParamChip[] = [];
    let gid = 0;
    for (const row of this.structureRows) {
      if (!this.truthyProvision(row.provision_additional_parameter)) {
        continue;
      }
      for (const p of this.parseEvaluationParamRows(row)) {
        const dk = `${(p.db_table || '').toLowerCase()}|${(p.param_name || '').toLowerCase()}|${(p.label || '').toLowerCase()}`;
        if (!dk.replace(/\|/g, '').trim()) {
          continue;
        }
        if (dedupe.has(dk)) {
          continue;
        }
        dedupe.add(dk);
        const path = `${(p.db_table || '').trim()}.${(p.param_name || '').trim()}`.replace(/^\./, '').replace(/\.$/, '');
        const lbl = (p.label || '').trim();
        const seq = this.normalizeSequencePath(row.sequence_path);
        chips.push({
          id: `eval-all-${gid++}`,
          label: lbl || path || 'Parameter',
          detail: path ? `${path}${seq ? ' · hierarchy ' + seq : ''}` : lbl,
          sourcePath: seq,
        });
      }
    }
    return chips;
  }

  scrollToEvaluationParameters(): void {
    const el = typeof document !== 'undefined' ? document.getElementById('cbmr-node-evaluation-params') : null;
    el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  /** From Product & batch: open the defining node on Main and scroll to the evaluation block. */
  onAggregatedEvaluationChipClick(ev: CbmrEvalParamChip): void {
    const path = (ev.sourcePath || '').trim();
    if (!path) {
      return;
    }
    this.selectDynamicNav(path);
    this.setRightAuxTab('main');
    setTimeout(() => this.scrollToEvaluationParameters(), 80);
  }

  private parseEvaluationParamRows(row: CbmrStructureRow): CbmrEvalParamRow[] {
    const out: CbmrEvalParamRow[] = [];
    const raw = (row.additional_params_json || '').trim();
    if (raw) {
      try {
        const arr = JSON.parse(raw) as { label?: string; db_table?: string; param_name?: string }[];
        if (Array.isArray(arr)) {
          for (const x of arr) {
            const label = String(x?.label ?? '').trim();
            const db_table = String(x?.db_table ?? '').trim();
            const param_name = String(x?.param_name ?? '').trim();
            if (label || db_table || param_name) {
              out.push({
                label,
                db_table,
                param_name,
              });
            }
          }
        }
      } catch {
        /* ignore */
      }
    }
    if (out.length === 0) {
      const leg = (row.additional_param_label || '').trim();
      const tbl = (row.additional_param_db_table || '').trim();
      const col = (row.additional_param_name || '').trim();
      if (leg || tbl || col) {
        out.push({ label: leg, db_table: tbl, param_name: col });
      }
    }
    return out;
  }

  ipcDetail(row: CbmrStructureRow): string {
    const v = (row.in_process_checks_by || '').trim().toLowerCase();
    if (v === '' || v === 'none') {
      return 'Not applicable';
    }
    if (v === 'production') {
      return 'Production';
    }
    if (v === 'ipqa') {
      return 'IPQA';
    }
    return row.in_process_checks_by || '—';
  }

  provisionEquipmentsEnabled(row: CbmrStructureRow): boolean {
    return this.truthyProvision(row.provision_equipments);
  }

  equipmentDraftPickIndex(sequencePath: string): number {
    const p = (sequencePath || '').trim();
    const n = this.equipmentDraftIndexByPath[p];
    return typeof n === 'number' && n >= 0 ? n : 0;
  }

  setEquipmentDraftPickIndex(sequencePath: string, picked: number | string): void {
    const p = (sequencePath || '').trim();
    if (!p) {
      return;
    }
    const raw = typeof picked === 'number' ? picked : Number.parseInt(String(picked), 10);
    const v = Number.isFinite(raw) && raw >= 0 ? raw : 0;
    this.equipmentDraftIndexByPath = { ...this.equipmentDraftIndexByPath, [p]: v };
  }

  equipmentDraftPreviewRow(sequencePath: string): CbmrEquipmentPickRow {
    const idx = this.equipmentDraftPickIndex(sequencePath);
    if (idx <= 0 || idx > this.equipmentsMasterList.length) {
      return { equipment_name: '', make: '', equipment_code: '' };
    }
    const e = this.equipmentsMasterList[idx - 1];
    return {
      equipment_name: (e.equipment_name || '').trim(),
      make: (e.make || '').trim(),
      equipment_code: (e.equipment_code || '').trim(),
    };
  }

  addMappedEquipmentRow(sequencePath: string): void {
    const p = (sequencePath || '').trim();
    if (!p) {
      return;
    }
    const d = this.equipmentDraftPreviewRow(p);
    if (!d.equipment_name && !d.equipment_code) {
      alertify.message('Choose an equipment from the list first.');
      return;
    }
    const cur = [...(this.equipmentUsedByPath[p] ?? [])];
    cur.push({ ...d });
    this.equipmentUsedByPath = { ...this.equipmentUsedByPath, [p]: cur };
  }

  removeMappedEquipmentRow(sequencePath: string, index: number): void {
    const p = (sequencePath || '').trim();
    if (!p || index < 0) {
      return;
    }
    const cur = [...(this.equipmentUsedByPath[p] ?? [])];
    cur.splice(index, 1);
    const next = { ...this.equipmentUsedByPath };
    if (cur.length === 0) {
      delete next[p];
    } else {
      next[p] = cur;
    }
    this.equipmentUsedByPath = next;
  }

  /** Same master list endpoint as awaiting-proceed (equipment picker). */
  private ensureEquipmentsMasterListLoaded(): void {
    if (this.equipmentsMasterLoading || this.equipmentsMasterFetchAttempted) {
      return;
    }
    this.equipmentsMasterFetchAttempted = true;
    this.equipmentsMasterLoading = true;
    this.equipmentsMasterError = '';
    this.api.get('common.php?type=getEquipments').subscribe({
      next: (res: unknown) => {
        this.equipmentsMasterLoading = false;
        const list = Array.isArray(res) ? res : [];
        this.equipmentsMasterList = list
          .map((item) => {
            if (!item || typeof item !== 'object') {
              return { equipment_name: '', make: '', equipment_code: '' };
            }
            const o = item as Record<string, unknown>;
            return {
              equipment_name: String(o['equipment_name'] ?? ''),
              make: String(o['make'] ?? ''),
              equipment_code: String(o['equipment_code'] ?? ''),
            };
          })
          .filter((r) => r.equipment_name.trim() !== '' || r.equipment_code.trim() !== '');
      },
      error: () => {
        this.equipmentsMasterLoading = false;
        this.equipmentsMasterError = 'Could not load equipment list.';
      },
    });
  }

  private clearEquipmentProvisioningState(): void {
    this.equipmentUsedByPath = {};
    this.equipmentDraftIndexByPath = {};
    this.equipmentsMasterError = '';
    this.equipmentsMasterList = [];
    this.equipmentsMasterFetchAttempted = false;
    this.provisionSequencesByPath = {};
    this.selectedProvisionForGenerate = '';
    this.lastPrintedProvisionId = '';
  }

  private parseProvisionSequencesInbound(raw: unknown): Record<string, CbmrProvisionSequenceEntry[]> {
    const out: Record<string, CbmrProvisionSequenceEntry[]> = {};
    if (!raw || typeof raw !== 'object' || Array.isArray(raw)) {
      return out;
    }
    for (const key of Object.keys(raw as Record<string, unknown>)) {
      const path = key.trim();
      if (!path) {
        continue;
      }
      const arr = (raw as Record<string, unknown>)[key];
      if (!Array.isArray(arr)) {
        continue;
      }
      const list: CbmrProvisionSequenceEntry[] = [];
      let order = 0;
      for (const cell of arr) {
        if (!cell || typeof cell !== 'object') {
          continue;
        }
        const o = cell as Record<string, unknown>;
        const id = String(o['id'] ?? '').trim();
        if (!id) {
          continue;
        }
        order++;
        list.push({
          id,
          label: String(o['label'] ?? id).trim() || id,
          order: typeof o['order'] === 'number' && !isNaN(o['order']) ? o['order'] : order,
          sequence_path: String(o['sequence_path'] ?? '').trim(),
          hierarchy_level: String(o['hierarchy_level'] ?? '').trim(),
        });
      }
      if (list.length) {
        out[path] = list;
      }
    }
    return out;
  }

  private parseEquipmentByPathInbound(rawEquipment: unknown): Record<string, CbmrEquipmentPickRow[]> {
    const out: Record<string, CbmrEquipmentPickRow[]> = {};
    if (!rawEquipment || typeof rawEquipment !== 'object' || Array.isArray(rawEquipment)) {
      return out;
    }
    for (const key of Object.keys(rawEquipment)) {
      const path = key.trim();
      if (!path) {
        continue;
      }
      const rows = (rawEquipment as Record<string, unknown>)[key];
      if (!Array.isArray(rows)) {
        continue;
      }
      out[path] = rows.map((cell) => {
        if (!cell || typeof cell !== 'object') {
          return { equipment_name: '', make: '', equipment_code: '' };
        }
        const o = cell as Record<string, unknown>;
        return {
          equipment_name: String(o['equipment_name'] ?? ''),
          make: String(o['make'] ?? ''),
          equipment_code: String(o['equipment_code'] ?? ''),
        };
      });
    }
    return out;
  }

  private truthyProvision(val: string | number | boolean | undefined | null): boolean {
    if (val === true) {
      return true;
    }
    if (val === false) {
      return false;
    }
    if (typeof val === 'number') {
      return val !== 0;
    }
    const s = String(val ?? '')
      .trim()
      .toLowerCase();
    if (!s || s === '0' || s === 'false' || s === 'no' || s === 'n') {
      return false;
    }
    return s === '1' || s === 'true' || s === 'yes' || s === 'y' || s === 'on' || s === 'enabled';
  }

  private readSaveErrorMessage(err: unknown): string {
    const raw = (err as { error?: unknown })?.error;
    if (typeof raw === 'string' && raw.trim()) {
      return raw;
    }
    if (raw && typeof raw === 'object' && 'message' in raw) {
      const m = (raw as { message?: unknown }).message;
      if (typeof m === 'string' && m.trim()) {
        return m;
      }
    }
    return 'Save failed.';
  }

  saveMapping(): void {
    if (this.selectedDosageForm === CBMR_DOSAGE_PLACEHOLDER) {
      alertify.error('Select dosage form.');
      return;
    }
    const dosageForm = this.selectedDosageForm.trim();
    const processTitle = this.selectedProcessTitle.trim();
    if (!dosageForm || !processTitle) {
      alertify.error('Select dosage form and process template.');
      return;
    }
    if (this.structureRows.length === 0) {
      alertify.error('No structure to save. Choose a process that has lines in Process Type Master.');
      return;
    }

    const body = {
      dosage_form: dosageForm,
      process_title: processTitle,
      capture_schema: {
        ...this.captureByPath,
        [CBMR_EQUIP_BY_PATH_KEY]: this.equipmentUsedByPath,
        [CBMR_PROVISION_SEQUENCES_KEY]: this.provisionSequencesByPath,
        [CBMR_PRODUCT_INFO_KEY]: { ...this.productInformation },
      },
      saved_by_emp_id: typeof localStorage !== 'undefined' ? localStorage.getItem('emp_id') || '' : '',
    };

    this.saving = true;
    this.api.postJson('master/configure_bmr_map_api.php?type=save_mapping', JSON.stringify(body)).subscribe({
      next: (res: unknown) => {
        this.saving = false;
        const o = res as { status?: string; message?: string; row_count?: number };
        if (o?.status === 'success') {
          alertify.success(`Saved BMR map (${o.row_count ?? this.structureRows.length} lines in structure).`);
          this.existingEntryAt = new Date().toISOString().slice(0, 19).replace('T', ' ');
          this.loadSavedCaptureSchema(dosageForm, processTitle);
        } else {
          alertify.error(o?.message || 'Save failed.');
        }
      },
      error: (err: unknown) => {
        this.saving = false;
        alertify.error(this.readSaveErrorMessage(err));
      },
    });
  }

  captureIsDropdownFamily(f: BmrCaptureFieldDef): boolean {
    const ct = (f.controlType || '').toLowerCase();
    return ct === 'dropdown' || ct === 'multiselect' || ct === 'autocomplete';
  }

  previewDependsDraft(fid: string): string {
    return this.capturePreviewDependsValueByFieldId[fid] ?? '';
  }

  setPreviewDependsDraft(fid: string, v: string): void {
    this.capturePreviewDependsValueByFieldId = { ...this.capturePreviewDependsValueByFieldId, [fid]: v };
  }

  dropdownPreview(fid: string): CbmrDropdownPreviewState {
    return this.dropdownPreviewByFieldId[fid] ?? {};
  }

  /**
   * Heuristic table/column defaults from plain language. Preview uses built-in sample rows only (no DB).
   */
  applyLookupHeuristicsFromQuestion(f: BmrCaptureFieldDef): void {
    if (!this.captureIsDropdownFamily(f)) {
      alertify.error('Set answer type to a list-type control first.');
      return;
    }
    if (!f.dropdownMode) {
      f.dropdownMode = 'dynamic';
    }
    const blob = `${f.label} ${f.helpText} ${f.fieldName}`.toLowerCase();
    if (/(equipment|mixer|machine|vessel)/.test(blob)) {
      if (!f.fkTable.trim()) {
        f.fkTable = 'equipment';
      }
      if (!f.fkValueColumn.trim()) {
        f.fkValueColumn = 'equipment_code';
      }
      if (!f.fkDisplayColumn.trim()) {
        f.fkDisplayColumn = 'equipment_name';
      }
      if (!f.fkWhereClause.trim()) {
        f.fkWhereClause = 'is_active = 1';
      }
      if (!(f.lookupMirrorText || '').trim()) {
        const used = new Set(
          [
            String(f.fkValueColumn || '')
              .trim()
              .toLowerCase(),
            String(f.fkDisplayColumn || '')
              .trim()
              .toLowerCase(),
          ].filter(Boolean),
        );
        const pair = (col: string, cap: string) =>
          used.has(col.toLowerCase()) ? '' : col + '|' + cap;
        const lines = [
          pair('capacity', 'Rated capacity'),
          pair('equipment_model', 'Model'),
          pair('model', 'Model'),
          pair('manufacturer', 'Manufacturer'),
          pair('location_name', 'Location'),
          pair('department_name', 'Department'),
        ].filter((x) => x.length > 0);
        if (lines.length > 0) {
          f.lookupMirrorText = lines.join('\n');
        }
      }
    } else if (/(department|dept\b)/.test(blob)) {
      if (!f.fkTable.trim()) {
        f.fkTable = 'departments';
      }
      if (!f.fkValueColumn.trim()) {
        f.fkValueColumn = 'department_id';
      }
      if (!f.fkDisplayColumn.trim()) {
        f.fkDisplayColumn = 'department_name';
      }
    } else if (/(employee|operator|person|staff)/.test(blob)) {
      if (!f.fkTable.trim()) {
        f.fkTable = 'employee';
      }
      if (!f.fkValueColumn.trim()) {
        f.fkValueColumn = 'emp_id';
      }
      if (!f.fkDisplayColumn.trim()) {
        f.fkDisplayColumn = 'emp_name';
      }
    } else if (/(product|material|raw material|rm)/.test(blob)) {
      if (!f.fkTable.trim()) {
        f.fkTable = 'product';
      }
      if (!f.fkValueColumn.trim()) {
        f.fkValueColumn = 'product_code';
      }
      if (!f.fkDisplayColumn.trim()) {
        f.fkDisplayColumn = 'product_name';
      }
    } else if (/(room|area|location)/.test(blob)) {
      if (!f.fkTable.trim()) {
        f.fkTable = 'room_master';
      }
      if (!f.fkValueColumn.trim()) {
        f.fkValueColumn = 'id';
      }
      if (!f.fkDisplayColumn.trim()) {
        f.fkDisplayColumn = 'room_name';
      }
    } else {
      alertify.message('No strong keyword match — type master table and columns to match how you will bind data later.');
      return;
    }
    alertify.success('Filled a suggested mapping from wording — use “Show sample list” for the built-in preview (not live data).');
  }

  parseStaticOptionsForPreview(f: BmrCaptureFieldDef): Array<{ value: string; label: string }> {
    const raw = (f.staticOptionsText || '').split(/\r?\n/);
    const out: Array<{ value: string; label: string }> = [];
    for (const line of raw) {
      const t = line.trim();
      if (!t) {
        continue;
      }
      const pipe = t.indexOf('|');
      if (pipe >= 0) {
        out.push({ value: t.slice(0, pipe).trim(), label: t.slice(pipe + 1).trim() || t.slice(0, pipe).trim() });
      } else {
        out.push({ value: t, label: t });
      }
    }
    return out;
  }

  refreshLocalLookupPresetSample(f: BmrCaptureFieldDef): void {
    const fid = f.id;
    if (!this.captureIsDropdownFamily(f) || f.dropdownMode !== 'dynamic') {
      return;
    }
    const tbl = String(f.fkTable || '').trim();
    const vc = String(f.fkValueColumn || '').trim();
    if (!tbl || !vc) {
      alertify.error('Enter master table and stored value column first.');
      return;
    }
    const preset = this.staticLookupPresetForTable(tbl);
    if (!preset) {
      const np = { ...this.previewPickedLookupValueByFieldId };
      delete np[fid];
      this.previewPickedLookupValueByFieldId = np;
      this.dropdownPreviewByFieldId = {
        ...this.dropdownPreviewByFieldId,
        [fid]: {
          rows: [],
          error:
            'No built-in sample for this table name. The mapping is still saved; live master-data binding is developed separately.',
        },
      };
      return;
    }
    const rows = this.buildPresetSampleRows(f, preset);
    const cur = this.previewPickedLookupValueByFieldId[fid];
    let nextPid = '';
    if (rows.length === 0) {
      nextPid = '';
    } else if (cur && rows.some((r) => r.value === cur)) {
      nextPid = cur;
    } else {
      nextPid = rows[0]?.value ?? '';
    }
    const nextPvPick = { ...this.previewPickedLookupValueByFieldId };
    if (!nextPid) {
      delete nextPvPick[fid];
    } else {
      nextPvPick[fid] = nextPid;
    }
    this.previewPickedLookupValueByFieldId = nextPvPick;

    this.dropdownPreviewByFieldId = {
      ...this.dropdownPreviewByFieldId,
      [fid]: {
        rows,
        error: rows.length ? undefined : 'Sample layout produced no rows — check column names against the preset.',
      },
    };
  }

  suggestMirrorsFromPreset(f: BmrCaptureFieldDef): void {
    const tbl = String(f.fkTable || '').trim();
    if (!tbl) {
      alertify.error('Enter master table name first.');
      return;
    }
    const preset = this.staticLookupPresetForTable(tbl);
    if (!preset) {
      alertify.message('No built-in layout for this table — enter mirror lines manually.');
      return;
    }
    const used = new Set(
      [String(f.fkValueColumn || '').trim().toLowerCase(), String(f.fkDisplayColumn || '').trim().toLowerCase()].filter(
        Boolean,
      ),
    );
    const toAdd = preset.suggestedMirrorLines.filter((line) => {
      const col = line.split('|')[0]?.trim().toLowerCase() ?? '';
      return col.length > 0 && !used.has(col);
    });
    if (!toAdd.length) {
      alertify.message('Nothing to add — value/display columns may already cover these.');
      return;
    }
    const prev = String(f.lookupMirrorText || '').trim();
    f.lookupMirrorText = prev ? prev + '\n' + toAdd.join('\n') : toAdd.join('\n');
    alertify.success('Suggested mirror lines from the built-in layout.');
  }

  private staticLookupPresetForTable(tableRaw: string): CbmrStaticLookupPresetDef | null {
    const t = String(tableRaw || '')
      .trim()
      .toLowerCase();
    if (!t) {
      return null;
    }
    return CBMR_STATIC_LOOKUP_PRESETS.find((p) => p.fkTableAliases.includes(t)) ?? null;
  }

  private parseLookupMirrorSpecsFromText(text: string): Array<{ col: string; caption: string }> {
    const out: Array<{ col: string; caption: string }> = [];
    for (const line of String(text || '').split(/\r?\n/)) {
      const trimmed = line.trim();
      if (!trimmed) {
        continue;
      }
      const pipe = trimmed.indexOf('|');
      const raw = pipe >= 0 ? trimmed.slice(0, pipe).trim() : trimmed;
      const cap = pipe >= 0 ? trimmed.slice(pipe + 1).trim() : '';
      if (!/^[A-Za-z_][A-Za-z0-9_]*$/.test(raw)) {
        continue;
      }
      const caption = cap.length > 0 ? cap : raw.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
      out.push({ col: raw, caption });
    }
    return out;
  }

  private buildPresetSampleRows(f: BmrCaptureFieldDef, preset: CbmrStaticLookupPresetDef): CbmrDropdownPreviewRow[] {
    const vc = String(f.fkValueColumn || preset.defaultValueColumn || '').trim();
    const dc = String(f.fkDisplayColumn || preset.defaultDisplayColumn || '').trim();
    if (!vc) {
      return [];
    }
    const mirrorSource =
      String(f.lookupMirrorText || '').trim().length > 0
        ? String(f.lookupMirrorText)
        : preset.suggestedMirrorLines.join('\n');
    const specs = this.parseLookupMirrorSpecsFromText(mirrorSource).filter((s) => {
      const sl = s.col.toLowerCase();
      return sl !== vc.toLowerCase() && sl !== dc.toLowerCase();
    });

    return preset.demoRows
      .map((data) => {
        const value = String(data[vc] ?? '');
        const label = String(data[dc] ?? value);
        const mirrors: CbmrDropdownMirrorPreview[] = [];
        for (const s of specs) {
          const text = String(data[s.col] ?? '');
          if (!text.trim()) {
            continue;
          }
          mirrors.push({ column: s.col, caption: s.caption, text });
        }
        return { value, label, mirrors };
      })
      .filter((r) => r.value !== '');
  }

  getPreviewDynamicDropdownModel(f: BmrCaptureFieldDef): string {
    const fid = f.id;
    const rows = this.dropdownPreview(fid).rows ?? [];
    if (!rows.length) {
      return '';
    }
    const stored = this.previewPickedLookupValueByFieldId[fid];
    if (
      typeof stored === 'string' &&
      stored !== '' &&
      rows.some((r) => r.value === stored)
    ) {
      return stored;
    }
    return rows[0]?.value ?? '';
  }

  setPreviewDynamicDropdownModel(f: BmrCaptureFieldDef, picked: string): void {
    this.previewPickedLookupValueByFieldId = {
      ...this.previewPickedLookupValueByFieldId,
      [f.id]: String(picked ?? ''),
    };
  }

  mirrorsForDropdownOperatorPreview(f: BmrCaptureFieldDef): CbmrDropdownMirrorPreview[] {
    if (f.dropdownMode !== 'dynamic') {
      return [];
    }
    const fid = f.id;
    const rows = this.dropdownPreview(fid).rows ?? [];
    if (!rows.length) {
      return [];
    }
    const pick = this.getPreviewDynamicDropdownModel(f);
    const row =
      rows.find((r) => r.value === pick) ?? rows[0];
    const raw = Array.isArray(row?.mirrors) ? row!.mirrors! : [];
    return raw.filter((m) => String(m?.text ?? '').trim().length > 0);
  }

  getPreviewStaticDropdownModel(f: BmrCaptureFieldDef): string {
    const opts = this.parseStaticOptionsForPreview(f);
    if (!opts.length) {
      return '';
    }
    const stored = this.previewPickedLookupValueByFieldId[f.id];
    if (
      typeof stored === 'string' &&
      stored !== '' &&
      opts.some((o) => o.value === stored)
    ) {
      return stored;
    }
    return opts[0]?.value ?? '';
  }

  setPreviewStaticDropdownModel(f: BmrCaptureFieldDef, picked: string): void {
    this.previewPickedLookupValueByFieldId = {
      ...this.previewPickedLookupValueByFieldId,
      [f.id]: String(picked ?? ''),
    };
  }

  captureFieldTrack(i: number, f: BmrCaptureFieldDef): string {
    return f.id ? f.id : 'i' + i;
  }

  fieldsForPath(path: string): BmrCaptureFieldDef[] {
    return this.captureByPath[path] || [];
  }

  addCaptureField(path: string): void {
    const p = (path || '').trim();
    if (!p) {
      return;
    }
    const nextList = [...(this.captureByPath[p] || []), this.blankCaptureField()];
    this.captureByPath = { ...this.captureByPath, [p]: nextList };
  }

  removeCaptureField(path: string, index: number): void {
    const p = (path || '').trim();
    if (!p || index < 0) {
      return;
    }
    const prev = [...(this.captureByPath[p] || [])];
    const removed = prev[index];
    prev.splice(index, 1);
    this.captureByPath = { ...this.captureByPath, [p]: prev };
    if (removed?.id) {
      const rid = removed.id;
      const nextPv = { ...this.dropdownPreviewByFieldId };
      delete nextPv[rid];
      this.dropdownPreviewByFieldId = nextPv;
      const nextDep = { ...this.capturePreviewDependsValueByFieldId };
      delete nextDep[rid];
      this.capturePreviewDependsValueByFieldId = nextDep;
      const nextPick = { ...this.previewPickedLookupValueByFieldId };
      delete nextPick[rid];
      this.previewPickedLookupValueByFieldId = nextPick;
    }
  }

  sanitizePathForTable(sequencePath: string): string {
    return String(sequencePath || '')
      .trim()
      .replace(/\./g, '_')
      .replace(/[^a-zA-Z0-9_]/g, '');
  }

  /**
   * Read-only illustrative CREATE TABLE plus dropdown FK / SELECT hints (not executed by the app).
   */
  captureSqlSketch(sequencePath: string): string {
    const path = String(sequencePath || '').trim();
    const slug = this.sanitizePathForTable(path);
    const tableName = 'ebmr_cap_' + (slug || 'node');
    const fields = (this.captureByPath[path] || []).filter((f) => (f.fieldName || f.label || '').trim().length > 0);

    const cols: string[] = [
      '  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
      '  `plant_id` VARCHAR(36) NOT NULL',
      '  `product_code` VARCHAR(128) NOT NULL',
      '  `sequence_path` VARCHAR(64) NOT NULL',
    ];

    const extras: string[] = [];

    for (let i = 0; i < fields.length; i++) {
      const f = fields[i];
      const slugBase =
        String(f.fieldName || f.label || 'field_' + (i + 1))
          .trim()
          .toLowerCase()
          .replace(/\s+/g, '_')
          .replace(/[^a-z0-9_]/g, '') || 'field_' + (i + 1);

      let ctype = 'VARCHAR(400)';
      switch ((f.controlType || '').toLowerCase()) {
        case 'number':
          ctype = 'BIGINT';
          break;
        case 'decimal':
          ctype = 'DECIMAL(18,4)';
          break;
        case 'checkbox':
        case 'radio':
          ctype = 'TINYINT(1)';
          break;
        case 'date':
          ctype = 'DATE';
          break;
        case 'datetime':
          ctype = 'DATETIME(3)';
          break;
        case 'time':
          ctype = 'TIME';
          break;
        case 'textarea':
          ctype = 'LONGTEXT';
          break;
        case 'file':
        case 'autocomplete':
          ctype = 'VARCHAR(512)';
          break;
        default:
          ctype = 'VARCHAR(400)';
      }

      const nullPart = f.required ? ' NOT NULL' : ' NULL';
      const uniqPart = f.unique ? ' UNIQUE' : '';
      cols.push('  `' + slugBase + '` ' + ctype + nullPart + uniqPart);

      const ctLow = (f.controlType || '').toLowerCase();
      if (
        this.captureIsDropdownFamily(f) &&
        f.dropdownMode === 'dynamic' &&
        String(f.fkTable).trim() &&
        String(f.fkValueColumn).trim()
      ) {
        const vt = String(f.fkTable).trim();
        const vv = String(f.fkValueColumn).trim();
        const vd = String(f.fkDisplayColumn).trim();
        const wf = String(f.fkWhereClause || '').trim();
        const dp = String(f.dependsOnField || '').trim();
        let ln =
          '-- ' +
          slugBase +
          ' dropdown: SELECT `' +
          vv +
          '`' +
          (vd ? ', `' + vd + '`' : '') +
          ' FROM `' +
          vt +
          '`';
        if (wf) {
          ln += ' WHERE (' + wf + ')';
        }
        if (dp) {
          ln += '; -- cascading parent field: `' + dp + '`';
        } else {
          ln += ';';
        }
        extras.push(ln);
        extras.push(
          '-- FK (apply when master exists): ALTER TABLE `' +
            tableName +
            '` ADD CONSTRAINT `fk_' +
            slugBase +
            '` FOREIGN KEY (`' +
            slugBase +
            '`) REFERENCES `' +
            vt +
            '` (`' +
            vv +
            '`);'
        );
      }
      if (this.captureIsDropdownFamily(f) && f.dropdownMode === 'static' && String(f.staticOptionsText).trim()) {
        extras.push('-- Static `' + slugBase + '`:\n-- ' + f.staticOptionsText.replace(/\r\n/g, '\n').split('\n').join('\n-- '));
      }
      if (
        ctLow === 'multiselect' &&
        !(this.captureIsDropdownFamily(f) && f.dropdownMode === 'dynamic' && String(f.fkTable).trim())
      ) {
        extras.push('-- Hint: `' + slugBase + '` multi-select JSON: consider JSON column instead of FK.');
      }

      const fnLower = String(f.fieldName || '').trim().toLowerCase();
      if ((fnLower.endsWith('_id') || fnLower.endsWith('_code')) && String(f.fieldName || '').trim() && ctype === 'VARCHAR(400)') {
        extras.push(
          '-- Suggested FK naming: `' + slugBase + '` REFERENCES `<master>`(`...`) — adjust type to BIGINT if surrogate key.'
        );
      }
    }

    cols.push(
      '  `audit_created_at` TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)',
      '  `audit_updated_at` TIMESTAMP(3) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(3)',
      '  PRIMARY KEY (`id`),',
      '  KEY `idx_ebmr_cap_slug` (`plant_id`, `product_code`, `sequence_path`)'
    );

    const ddl = ['CREATE TABLE `' + tableName + '` (', cols.join(',\n'), ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;', ''];
    if (extras.length) {
      ddl.push(extras.join('\n'));
      ddl.push('');
    }
    return ddl.join('\n');
  }

  private newCaptureFieldId(): string {
    try {
      if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
      }
    } catch {
      /* ignore */
    }
    return 'cbf_' + (this.captureFieldSeed++).toString(36) + '_' + Math.random().toString(36).slice(2, 10);
  }

  private blankCaptureField(): BmrCaptureFieldDef {
    return {
      id: this.newCaptureFieldId(),
      fieldName: '',
      label: '',
      controlType: 'text',
      required: false,
      unique: false,
      searchable: true,
      filterable: true,
      sortable: true,
      placeholder: '',
      helpText: '',
      defaultValue: '',
      validationPattern: '',
      hidden: false,
      readOnly: false,
      dropdownMode: '',
      fkTable: '',
      fkValueColumn: '',
      fkDisplayColumn: '',
      fkWhereClause: '',
      dependsOnField: '',
      lazyLoad: false,
      paginated: false,
      staticOptionsText: '',
      lookupMirrorText: '',
    };
  }

  private coerceBool(v: unknown, def = false): boolean {
    if (typeof v === 'boolean') {
      return v;
    }
    if (typeof v === 'number') {
      return v !== 0;
    }
    if (v === null || v === undefined || v === '') {
      return def;
    }
    const s = String(v).trim().toLowerCase();
    if (s === '1' || s === 'true' || s === 'yes') {
      return true;
    }
    if (s === '0' || s === 'false' || s === 'no') {
      return false;
    }
    return def;
  }

  private coerceString(v: unknown, def = ''): string {
    if (typeof v === 'string') {
      return v;
    }
    if (v === null || v === undefined) {
      return def;
    }
    return String(v);
  }

  private coerceDropdownMode(v: unknown): '' | 'static' | 'dynamic' {
    const s = String(v ?? '').trim().toLowerCase();
    if (s === 'static' || s === 'dynamic') {
      return s;
    }
    return '';
  }

  private coerceField(raw: unknown): BmrCaptureFieldDef {
    const o =
      raw && typeof raw === 'object' && !Array.isArray(raw) ? (raw as Record<string, unknown>) : ({} as Record<string, unknown>);
    const idGot = this.coerceString(o['id']).trim();
    const staticTxt = this.coerceString(o['staticOptionsText'] ?? o['staticOptions']).trim();
    const lookupMirrorTxt = this.coerceString(o['lookupMirrorText'] ?? o['lookup_mirror_text']).trim();
    const blank = this.blankCaptureField();
    return {
      ...blank,
      id: idGot ? idGot : blank.id,
      fieldName: this.coerceString(o['fieldName']).trim(),
      label: this.coerceString(o['label']).trim(),
      controlType: (this.coerceString(o['controlType'] || 'text').trim().toLowerCase() || 'text') as string,
      required: this.coerceBool(o['required']),
      unique: this.coerceBool(o['unique']),
      searchable: this.coerceBool(o['searchable'], true),
      filterable: this.coerceBool(o['filterable'], true),
      sortable: this.coerceBool(o['sortable'], true),
      placeholder: this.coerceString(o['placeholder']).trim(),
      helpText: this.coerceString(o['helpText']).trim(),
      defaultValue: this.coerceString(o['defaultValue']).trim(),
      validationPattern: this.coerceString(o['validationPattern']).trim(),
      hidden: this.coerceBool(o['hidden']),
      readOnly: this.coerceBool(o['readOnly']),
      dropdownMode: this.coerceDropdownMode(o['dropdownMode']),
      fkTable: this.coerceString(o['fkTable']).trim(),
      fkValueColumn: this.coerceString(o['fkValueColumn']).trim(),
      fkDisplayColumn: this.coerceString(o['fkDisplayColumn']).trim(),
      fkWhereClause: this.coerceString(o['fkWhereClause']).trim(),
      dependsOnField: this.coerceString(o['dependsOnField']).trim(),
      lazyLoad: this.coerceBool(o['lazyLoad']),
      paginated: this.coerceBool(o['paginated']),
      staticOptionsText: staticTxt,
      lookupMirrorText: lookupMirrorTxt,
    };
  }

  private importCaptureSchema(raw: Record<string, unknown>): Record<string, BmrCaptureFieldDef[]> {
    const out: Record<string, BmrCaptureFieldDef[]> = {};
    for (const k of Object.keys(raw)) {
      const pathKey = String(k).trim();
      const v = raw[k];
      if (!pathKey || !Array.isArray(v)) {
        continue;
      }
      out[pathKey] = v.map((x) => this.coerceField(x));
    }
    return out;
  }

  private ingestCaptureSchemaInbound(raw: unknown): void {
    this.equipmentUsedByPath = {};
    this.equipmentDraftIndexByPath = {};
    this.provisionSequencesByPath = {};
    if (!raw || typeof raw !== 'object' || Array.isArray(raw)) {
      this.captureByPath = {};
      this.ingestProductInfoFromRaw(undefined);
      return;
    }
    const rawObj = raw as Record<string, unknown>;
    this.equipmentUsedByPath = this.parseEquipmentByPathInbound(rawObj[CBMR_EQUIP_BY_PATH_KEY]);
    this.provisionSequencesByPath = this.parseProvisionSequencesInbound(rawObj[CBMR_PROVISION_SEQUENCES_KEY]);
    this.ingestProductInfoFromRaw(rawObj[CBMR_PRODUCT_INFO_KEY]);
    const rest: Record<string, unknown> = {};
    for (const k of Object.keys(rawObj)) {
      if (
        k === CBMR_EQUIP_BY_PATH_KEY ||
        k === CBMR_PROVISION_SEQUENCES_KEY ||
        k === CBMR_PRODUCT_INFO_KEY
      ) {
        continue;
      }
      rest[k] = rawObj[k];
    }
    this.captureByPath = this.importCaptureSchema(rest);
  }

  syncCapturePathsAfterStructureLoad(): void {
    const paths = new Set(this.structureRows.map((r) => this.normalizeSequencePath(r.sequence_path)).filter(Boolean));
    for (const k of Object.keys(this.captureByPath)) {
      if (this.isSyntheticCapturePathKey(k)) {
        paths.add(k);
      }
    }
    const next: Record<string, BmrCaptureFieldDef[]> = {};
    const nextEq: Record<string, CbmrEquipmentPickRow[]> = {};
    const nextDraft: Record<string, number> = {};
    const nextSeq: Record<string, CbmrProvisionSequenceEntry[]> = {};
    const pathSet = paths;
    for (const k of Object.keys(this.provisionSequencesByPath)) {
      pathSet.add(k);
    }
    for (const p of pathSet) {
      next[p] = this.captureByPath[p] ? [...this.captureByPath[p]] : [];
      nextEq[p] = this.equipmentUsedByPath[p] ? [...this.equipmentUsedByPath[p]] : [];
      if (this.provisionSequencesByPath[p]?.length) {
        nextSeq[p] = [...this.provisionSequencesByPath[p]];
      }
      const d = this.equipmentDraftIndexByPath[p];
      if (typeof d === 'number') {
        nextDraft[p] = d;
      }
    }
    this.captureByPath = next;
    this.equipmentUsedByPath = nextEq;
    this.equipmentDraftIndexByPath = nextDraft;
    this.provisionSequencesByPath = nextSeq;
  }
}
