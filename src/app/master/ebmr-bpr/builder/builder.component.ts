import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { EsignService } from 'src/app/shared/esign/esign.service';
import {
  ProcedureParagraph,
  assignProcedureNumbers,
  blankProcedureParagraph,
  procedureParagraphsToText,
} from '../shared/procedure.util';
import {
  DynamicColumn,
  DynamicRow,
  FormTableType,
  addColumn,
  addRow,
  blankRow,
  defaultTableForType,
  ensureRowKeys,
  removeColumn,
  removeRow,
} from '../shared/dynamic-table.util';
import { bmrForLabel, genericBmrBannerHint, GENERIC_BMR_SHORT } from '../shared/bmr-for-labels';
import {
  checkpointLabel,
  syncCheckpointSequenceForConfig,
} from '../shared/step-checkpoint-sequence.util';
import {
  applyMasterLockedFields,
  clearProductApprovalExecutionFields,
  emptyProductApprovalPage,
  normalizeProductApprovalPage,
} from '../shared/product-approval.util';
import {
  emptySafetyPrecautions,
  normalizeSafetyPrecautions,
  syncSafetyText,
} from '../shared/safety-precautions.util';
import {
  EquipmentMasterRow,
  EquipmentSelectionRow,
  equipmentMasterLabel,
  equipmentSelectionLabel,
  equipmentToSelectionRow,
  filterBmrEquipmentMaster,
  findEquipmentByCode,
  findEquipmentSelectionByCode,
  normalizeEquipmentMasterList,
  selectionRowToStepRow,
} from '../shared/equipment-master.util';
import {
  UnitFormulaMasterSummary,
  UnitFormulaRow,
  normalizeUnitFormulaRows,
  unitFormulaMasterLabel,
  buildDispensingMaterialsFromUnitFormula,
  isDispensingInProductionRow,
} from '../shared/unit-formula-master.util';
import {
  ProductSpecMasterSummary,
  ProductSpecRow,
  normalizeProductSpecRows,
  productSpecMasterLabel,
} from '../shared/product-spec-master.util';
import {
  emptyGeneralInstructions,
  checklistLinesToParagraphs,
  normalizeGeneralInstructions,
  syncGeneralInstructionsText,
} from '../shared/general-instructions.util';
import {
  DispensingPage,
  DispensingSection,
  emptyDispensingPage,
  normalizeDispensingPage,
  normalizeDispensingSections,
  evaluationParameterLabel,
} from '../shared/dispensing-store.util';
import {
  StageStepIndexRow,
  buildStageStepIndexRows,
  esignResultToSnapshot,
  masterStepSigners,
  stageAllStepsApproved,
  stepCanConfigure,
  stepCanEdit,
  stepIsLocked,
  stepWorkflowStatus,
} from '../shared/master-step-workflow.util';
import { CfrSigner } from 'src/app/shared/cfr-signature-block/cfr-signature-block.component';

declare let alertify: any;

interface SelNode {
  kind: 'header' | 'static' | 'stage' | 'step';
  staticKey?: string;
  stageIndex?: number;
  stepIndex?: number;
}

@Component({
  selector: 'app-ebmr-builder',
  templateUrl: './builder.component.html',
  styleUrls: ['../ebmr-bpr.theme.css', './builder.component.css'],
})
export class BuilderComponent implements OnInit {
  profileId = 0;
  loading = false;
  saving = false;
  profile: any = null;
  headerLoading = false;
  headerSyncedAt = '';

  // master option lists
  stageMaster: any[] = [];
  stepMaster: any[] = [];
  ipcMaster: any[] = [];
  lineClearance: any[] = [];
  deptChecks: any[] = [];
  qaChecks: any[] = [];
  ipqcSpecs: any[] = [];
  workAlloc: any[] = [];
  procedureMaster: any[] = [];
  yieldTableMaster: any[] = [];
  weighingTableMaster: any[] = [];
  equipmentMaster: EquipmentMasterRow[] = [];
  equipmentMasterLoading = false;
  equipmentPickCode = '';
  stepEquipmentPickCode = '';
  /** Build | Review | Checking */
  builderTab: 'build' | 'checking' | 'review' | 'final' | 'final_html_bmr' | 'final_html_bpr' = 'build';
  workflowQueue: any[] = [];
  workflowQueueLoading = false;
  stepViewOnly = false;
  signatureFocusStep: any = null;

  unitFormulaMasterList: UnitFormulaMasterSummary[] = [];
  unitFormulaMasterLoading = false;
  unitFormulaPickId: number | null = null;
  unitFormulaMaterialRows: UnitFormulaRow[] = [];
  unitFormulaBoundProductPick = '';
  unitFormulaRowsLoading = false;

  productSpecMasterList: ProductSpecMasterSummary[] = [];
  productSpecMasterLoading = false;
  productSpecPickId: number | null = null;
  productSpecRows: ProductSpecRow[] = [];
  productSpecRowsLoading = false;

  storeDispensingLoading = false;
  generalInstructionsMasterLoading = false;

  tableColTypes = [
    { value: 'text', label: 'Text' },
    { value: 'number', label: 'Number' },
  ];

  masterFormDefs = [
    { key: 'line_clearance', label: 'Line Clearance', icon: 'fas fa-broom', bindKey: 'line_clearance', listKey: 'lineClearance', labelField: 'checkpoint_text', hint: 'Select from Line Clearance master' },
    { key: 'procedure', label: 'Procedure', icon: 'fas fa-file-lines', bindKey: null, listKey: null, labelField: 'title', hint: 'Load from Procedure master or customise paragraphs' },
    { key: 'inprocess_checks', label: 'In-Process Checks', icon: 'fas fa-sliders-h', bindKey: 'inprocess_checks', listKey: 'ipcMaster', labelField: 'check_name', hint: 'Select IPC checks from master' },
    { key: 'dept_checks', label: 'Department Checks', icon: 'fas fa-clipboard-list', bindKey: 'dept_checks', listKey: 'deptChecks', labelField: 'checkpoint_text', hint: 'Department verification checkpoints' },
    { key: 'qa_checks', label: 'QA Checks', icon: 'fas fa-user-shield', bindKey: 'qa_checks', listKey: 'qaChecks', labelField: 'checkpoint_text', hint: 'QA review checkpoints' },
    { key: 'ipqc', label: 'IPQC Specification', icon: 'fas fa-vials', bindKey: 'ipqc', listKey: 'ipqcSpecs', labelField: 'parameter', hint: 'Mapped with Inprocess Specification — holds next step until QC releases results' },
    { key: 'yield_table', label: 'Yield Table', icon: 'fas fa-chart-line', bindKey: null, listKey: 'yieldTableMaster', labelField: 'title', hint: 'Load yield table layout — add columns & rows' },
    { key: 'weighing_table', label: 'Weighing Table', icon: 'fas fa-weight-scale', bindKey: null, listKey: 'weighingTableMaster', labelField: 'title', hint: 'Load weighing table layout — add columns & rows' },
    { key: 'equipment', label: 'Equipment', icon: 'fas fa-tools', bindKey: null, listKey: null, labelField: 'name', hint: 'Pick applicable equipment from the saved Equipment Selection page' },
  ];

  procedureLevelOptions = [
    { value: 1, label: '1.0 — Main' },
    { value: 2, label: '1.1.0 — Sub' },
    { value: 3, label: '1.1.1.0 — Detail' },
  ];

  readonly bmrForLabel = bmrForLabel;
  readonly genericBmrBannerHint = genericBmrBannerHint;
  readonly genericBmrShort = GENERIC_BMR_SHORT;

  // bound products
  products: any[] = [];
  bindModalOpen = false;
  productSearch = '';
  productResults: any[] = [];

  selected: SelNode = { kind: 'header' };
  rightTab: string | null = null;

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
    { key: 'ipqc_results', label: 'IPQC Check Results', icon: 'fas fa-vials' },
    { key: 'critical_params', label: 'Process Critical Parameters', icon: 'fas fa-exclamation-triangle' },
    { key: 'deviations', label: 'Deviations', icon: 'fas fa-code-branch' },
    { key: 'manpower', label: 'Manpower Allocation', icon: 'fas fa-users' },
    { key: 'logbook', label: 'Machine Log Book', icon: 'fas fa-book' },
    { key: 'breakdown', label: 'Breakdown Maintenance', icon: 'fas fa-wrench' },
    { key: 'incident', label: 'Incident Report', icon: 'fas fa-triangle-exclamation' },
  ];

  openRightTabModal(key: string): void {
    this.rightTab = key;
  }

  closeRightTabModal(): void {
    this.rightTab = null;
  }

  rightTabTitle(): string {
    const t = this.rightTabs.find((x) => x.key === this.rightTab);
    return t?.label || 'Execution Tab';
  }

  constructor(private service: DataAccessService, private router: Router, private route: ActivatedRoute, private esign: EsignService) {}

  ngOnInit(): void {
    this.profileId = Number(this.route.snapshot.paramMap.get('id') || 0);
    this.loadProfile();
  }

  /* ------------- load ------------- */
  loadProfile(): void {
    this.loading = true;
    this.service.get('master/ebmr_bpr.php?type=getProfile&id=' + this.profileId).subscribe({
      next: (r: any) => {
        if (r && r.status === 'success') {
          const p = r.profile;
          p.header = this.mergeDefaults(p.header, this.emptyHeader());
          p.static = this.mergeDefaults(p.static, this.emptyStatic());
          p.static.product_approval = normalizeProductApprovalPage(p.static.product_approval, p.header);
          clearProductApprovalExecutionFields(p.static.product_approval);
          this.syncProductApprovalFromMaster(false);
          p.static.safety = normalizeSafetyPrecautions(p.static.safety);
          syncSafetyText(p.static.safety);
          p.static.general_instructions = normalizeGeneralInstructions(p.static.general_instructions);
          syncGeneralInstructionsText(p.static.general_instructions);
          p.static.dispensing = normalizeDispensingPage(p.static.dispensing);
          p.right_tabs = this.mergeDefaults(p.right_tabs, this.emptyRightTabs());
          p.stages = (p.stages || []).map((s: any) => ({
            id: s.id,
            stage_name: s.stage_name,
            seq_no: s.seq_no,
            stage_master_id: s.stage_master_id,
            frozen: Number(s.frozen || 0),
            frozen_at: s.frozen_at || null,
            frozen_by: s.frozen_by || null,
            freeze_sign: s.freeze_sign || null,
            config: this.mergeDefaults(s.config, this.emptyStageConfig()),
            steps: (s.steps || []).map((t: any) => ({
              id: t.id,
              step_name: t.step_name,
              seq_no: t.seq_no,
              step_master_id: t.step_master_id,
              workflow_status: t.workflow_status || 'Draft',
              prepared: t.prepared || null,
              checked: t.checked || null,
              reviewed: t.reviewed || null,
              approved: t.approved || null,
              correction_remark: t.correction_remark || '',
              sent_back_by: t.sent_back_by || null,
              sent_back_at: t.sent_back_at || null,
              sent_back_from_role: t.sent_back_from_role || null,
              config: this.normalizeStepConfig(this.mergeDefaults(t.config, this.emptyStepConfig())),
            })),
          }));
          this.profile = p;
          this.products = p.products || [];
          this.loadMasters(p.dosage_form);
          this.loadWorkflowQueue();
          if (this.isProductSpecific && p.header.product_code) {
            this.syncProductHeaderFromMaster(false);
          }
        } else {
          alertify.error('Profile not found');
          this.close();
        }
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        alertify.error('Failed to load profile');
      },
    });
  }

  loadMasters(dosage: string): void {
    const df = dosage ? '&dosage_form=' + encodeURIComponent(dosage) : '';
    this.service.get('master/ebmr_bpr.php?type=getStages' + df).subscribe((r: any) => (this.stageMaster = r || []));
    this.service.get('master/ebmr_bpr.php?type=getSteps').subscribe((r: any) => (this.stepMaster = r || []));
    this.service.get('master/ebmr_bpr.php?type=getInprocessChecks' + df).subscribe((r: any) => (this.ipcMaster = r || []));
    this.service.get('master/ebmr_bpr.php?type=getCheckpoints&category=line_clearance' + df).subscribe((r: any) => (this.lineClearance = r || []));
    this.service.get('master/ebmr_bpr.php?type=getCheckpoints&category=department' + df).subscribe((r: any) => (this.deptChecks = r || []));
    this.service.get('master/ebmr_bpr.php?type=getCheckpoints&category=qa' + df).subscribe((r: any) => (this.qaChecks = r || []));
    this.service.get('master/ebmr_bpr.php?type=getIpqcSpecs' + df).subscribe((r: any) => (this.ipqcSpecs = r || []));
    this.service.get('master/ebmr_bpr.php?type=getWorkAllocations' + df).subscribe((r: any) => (this.workAlloc = r || []));
    this.service.get('master/ebmr_bpr.php?type=getProcedures' + df).subscribe((r: any) => (this.procedureMaster = r || []));
    this.service.get('master/ebmr_bpr.php?type=getFormTables&table_type=yield' + df).subscribe((r: any) => (this.yieldTableMaster = r || []));
    this.service.get('master/ebmr_bpr.php?type=getFormTables&table_type=weighing' + df).subscribe((r: any) => (this.weighingTableMaster = r || []));
    this.loadEquipmentMaster();
  }

  loadEquipmentMaster(showToast = false): void {
    this.equipmentMasterLoading = true;
    const plantId = this.service.getPlantConfigFields('plant_id') || '0';
    this.service.get('master/equipment.php?type=getEquipments&plant_id=' + encodeURIComponent(String(plantId))).subscribe({
      next: (r: unknown) => {
        this.equipmentMaster = filterBmrEquipmentMaster(normalizeEquipmentMasterList(r));
        this.equipmentMasterLoading = false;
        if (showToast) {
          alertify.message(this.equipmentMaster.length + ' Process / Production equipment loaded');
        }
      },
      error: () => {
        this.equipmentMaster = [];
        this.equipmentMasterLoading = false;
        if (showToast) alertify.error('Failed to load Equipment Master');
      },
    });
  }

  equipmentLabel(eq: EquipmentMasterRow): string {
    return equipmentMasterLabel(eq);
  }

  equipmentSelectionRowLabel(row: EquipmentSelectionRow): string {
    return equipmentSelectionLabel(row);
  }

  savedEquipmentSelectionPool(): EquipmentSelectionRow[] {
    return Array.isArray(this.profile?.static?.equipment_selection)
      ? this.profile.static.equipment_selection
      : [];
  }

  private usedEquipmentCodes(list: any[]): Set<string> {
    return new Set(
      (list || [])
        .map((r) => String(r?.equipment_code || r?.id_no || '').trim())
        .filter(Boolean)
    );
  }

  availableEquipmentForPick(): EquipmentMasterRow[] {
    const used = this.usedEquipmentCodes(this.profile?.static?.equipment_selection);
    return this.equipmentMaster.filter((eq) => !used.has(eq.equipment_code));
  }

  availableStepEquipmentForPick(): EquipmentSelectionRow[] {
    const used = this.usedEquipmentCodes(this.currentStep?.config?.equipment);
    return this.savedEquipmentSelectionPool().filter((row) => {
      const code = String(row?.equipment_code || row?.id_no || '').trim();
      return code && !used.has(code);
    });
  }

  addEquipmentFromPick(): void {
    if (!this.profile?.static) return;
    const code = (this.equipmentPickCode || '').trim();
    if (!code) {
      alertify.warning('Select equipment from the dropdown');
      return;
    }
    const eq = findEquipmentByCode(this.equipmentMaster, code);
    if (!eq) return;
    if (!Array.isArray(this.profile.static.equipment_selection)) {
      this.profile.static.equipment_selection = [];
    }
    if (this.usedEquipmentCodes(this.profile.static.equipment_selection).has(code)) {
      alertify.warning('Equipment already added to the table');
      return;
    }
    this.profile.static.equipment_selection.push(equipmentToSelectionRow(eq));
    this.equipmentPickCode = '';
  }

  addStepEquipmentFromPick(): void {
    if (!this.currentStep?.config) return;
    const pool = this.savedEquipmentSelectionPool();
    if (!pool.length) {
      alertify.warning('Add equipment on the Equipment Selection page first, then pick items for this step');
      return;
    }
    const code = (this.stepEquipmentPickCode || '').trim();
    if (!code) {
      alertify.warning('Select equipment from the dropdown');
      return;
    }
    const row = findEquipmentSelectionByCode(pool, code);
    if (!row) return;
    if (!Array.isArray(this.currentStep.config.equipment)) {
      this.currentStep.config.equipment = [];
    }
    if (this.usedEquipmentCodes(this.currentStep.config.equipment).has(code)) {
      alertify.warning('Equipment already added to this step');
      return;
    }
    const stepRow = selectionRowToStepRow(row);
    const master = findEquipmentByCode(this.equipmentMaster, code);
    if (master && (!stepRow.calibration_applicable || stepRow.calibration_applicable === 'Not Applicable')) {
      const fromMaster = (master.calibration_required || '').trim();
      if (fromMaster) {
        stepRow.calibration_applicable = fromMaster === 'Applicable' ? 'Applicable' : 'Not Applicable';
        stepRow.calibration_required = stepRow.calibration_applicable;
      }
    }
    this.currentStep.config.equipment.push(stepRow);
    this.stepEquipmentPickCode = '';
  }

  /* ------------- unit formula master ------------- */
  get unitFormulaBoundProducts(): { product_code: string; product_name?: string }[] {
    if (this.isProductSpecific) {
      const code = (this.profile?.header?.product_code || '').trim();
      if (!code) return [];
      return [{ product_code: code, product_name: this.profile?.header?.product_name }];
    }
    return (this.products || [])
      .map((p) => ({
        product_code: String(p?.product_code || '').trim(),
        product_name: p?.product_name,
      }))
      .filter((p) => p.product_code);
  }

  get unitFormulaRequiresProductPick(): boolean {
    return !this.isProductSpecific && this.unitFormulaBoundProducts.length > 1;
  }

  unitFormulaProductCode(): string {
    if (this.isProductSpecific) {
      return (this.profile?.header?.product_code || '').trim();
    }
    if (this.unitFormulaBoundProductPick) {
      return this.unitFormulaBoundProductPick.trim();
    }
    const bound = this.unitFormulaBoundProducts;
    return bound.length === 1 ? bound[0].product_code : '';
  }

  private syncUnitFormulaBoundProductPick(): void {
    const bound = this.unitFormulaBoundProducts;
    if (!bound.length) {
      this.unitFormulaBoundProductPick = '';
      return;
    }
    if (this.isProductSpecific || bound.length === 1) {
      this.unitFormulaBoundProductPick = bound[0].product_code;
      return;
    }
    if (!bound.some((p) => p.product_code === this.unitFormulaBoundProductPick)) {
      this.unitFormulaBoundProductPick = bound[0].product_code;
    }
  }

  private validateUnitFormulaAgainstBoundProduct(): void {
    if (!this.profile?.static) return;
    const code = this.unitFormulaProductCode();
    const meta = this.profile.static.unit_formula_meta;
    if (meta?.product_code && code && meta.product_code !== code) {
      this.profile.static.unit_formula = [];
      this.profile.static.unit_formula_meta = null;
      this.unitFormulaPickId = null;
      this.unitFormulaMaterialRows = [];
    }
  }

  loadUnitFormulaMasterList(showToast = false): void {
    this.syncUnitFormulaBoundProductPick();
    this.validateUnitFormulaAgainstBoundProduct();
    const productCode = this.unitFormulaProductCode();
    if (!productCode) {
      this.unitFormulaMasterList = [];
      if (showToast) {
        alertify.warning(this.isProductSpecific ? 'Link a product to this BMR first' : 'Bind a product to this BMR first');
      }
      return;
    }
    this.unitFormulaMasterLoading = true;
    const url =
      'master/ebmr_bpr.php?type=getUnitFormulaMasterList&product_code=' + encodeURIComponent(productCode);

    this.service.get(url).subscribe({
      next: (r: any) => {
        this.unitFormulaMasterList = r?.status === 'success' && Array.isArray(r.formulas) ? r.formulas : [];
        this.unitFormulaMasterLoading = false;
        if (this.unitFormulaPickId && !this.unitFormulaMasterList.some((f) => f.id === this.unitFormulaPickId)) {
          this.unitFormulaPickId = null;
          this.unitFormulaMaterialRows = [];
        }
        if (!this.unitFormulaPickId && this.unitFormulaMasterList.length === 1) {
          this.unitFormulaPickId = this.unitFormulaMasterList[0].id;
          this.loadUnitFormulaMaterialRows(false);
        }
        if (showToast) {
          alertify.message(this.unitFormulaMasterList.length + ' approved unit formula(s) for ' + productCode);
        }
      },
      error: () => {
        this.unitFormulaMasterList = [];
        this.unitFormulaMasterLoading = false;
        if (showToast) alertify.error('Failed to load Unit Formula Master');
      },
    });
  }

  unitFormulaLabel(f: UnitFormulaMasterSummary): string {
    return unitFormulaMasterLabel(f);
  }

  onUnitFormulaBoundProductChange(): void {
    this.unitFormulaPickId = null;
    this.unitFormulaMaterialRows = [];
    this.productSpecPickId = null;
    this.productSpecRows = [];
    if (this.profile?.static) {
      this.profile.static.unit_formula = [];
      this.profile.static.unit_formula_meta = null;
      this.profile.static.product_spec = [];
      this.profile.static.product_spec_meta = null;
      this.profile.static.dispensing = emptyDispensingPage();
      this.profile.static.dispensing_meta = null;
    }
    this.loadUnitFormulaMasterList(false);
    this.loadProductSpecMasterList(false);
  }

  onUnitFormulaPickChange(): void {
    this.loadUnitFormulaMaterialRows(false);
  }

  loadUnitFormulaMaterialRows(showToast = false): void {
    const id = Number(this.unitFormulaPickId || 0);
    const productCode = this.unitFormulaProductCode();
    if (!id || !productCode) {
      this.unitFormulaMaterialRows = [];
      return;
    }
    this.unitFormulaRowsLoading = true;
    this.service
      .get(
        'master/ebmr_bpr.php?type=getUnitFormulaMasterRows&id=' +
          id +
          '&product_code=' +
          encodeURIComponent(productCode)
      )
      .subscribe({
        next: (r: any) => {
          this.unitFormulaMaterialRows =
            r?.status === 'success' ? normalizeUnitFormulaRows(r.rows) : [];
          this.unitFormulaRowsLoading = false;
          if (showToast) {
            alertify.message(this.unitFormulaMaterialRows.length + ' material line(s) loaded');
          }
        },
        error: () => {
          this.unitFormulaMaterialRows = [];
          this.unitFormulaRowsLoading = false;
          if (showToast) alertify.error('Failed to load unit formula materials');
        },
      });
  }

  loadUnitFormulaFromMaster(): void {
    if (!this.profile?.static) return;
    const productCode = this.unitFormulaProductCode();
    if (!productCode) {
      alertify.warning(this.isProductSpecific ? 'Link a product to this BMR first' : 'Bind a product to this BMR first');
      return;
    }
    if (!this.unitFormulaPickId) {
      alertify.warning('Select a unit formula from the dropdown');
      return;
    }
    const apply = (rows: UnitFormulaRow[], formula: any) => {
      if (!rows.length) {
        alertify.warning('No materials in the selected unit formula');
        return;
      }
      this.profile.static.unit_formula = rows.map((r) => ({ ...r }));
      this.profile.static.unit_formula_meta = {
        id: formula?.id || this.unitFormulaPickId,
        product_code: formula?.product_code || productCode,
        mfr_no: formula?.mfr_no || '',
      };
      this.syncDispensingMaterialsFromUnitFormula(rows, formula);
      const dispCount = this.profile.static.dispensing?.materials?.length || 0;
      let msg = 'Unit formula loaded (' + rows.length + ' materials) — read-only from master';
      if (dispCount) msg += '; ' + dispCount + ' for Dispensing in Production';
      alertify.success(msg);
    };
    if (this.unitFormulaMaterialRows.length) {
      const f = this.unitFormulaMasterList.find((x) => x.id === this.unitFormulaPickId);
      apply(this.unitFormulaMaterialRows, f);
      return;
    }
    this.service
      .get(
        'master/ebmr_bpr.php?type=getUnitFormulaMasterRows&id=' +
          this.unitFormulaPickId +
          '&product_code=' +
          encodeURIComponent(productCode)
      )
      .subscribe({
        next: (r: any) => {
          const rows = r?.status === 'success' ? normalizeUnitFormulaRows(r.rows) : [];
          this.unitFormulaMaterialRows = rows;
          apply(rows, r?.formula);
        },
        error: () => alertify.error('Failed to load unit formula'),
      });
  }

  private syncDispensingMaterialsFromUnitFormula(rows: UnitFormulaRow[], formula: any): void {
    if (!this.profile?.static) return;
    const materials = buildDispensingMaterialsFromUnitFormula(rows);
    if (!this.profile.static.dispensing || typeof this.profile.static.dispensing !== 'object') {
      this.profile.static.dispensing = emptyDispensingPage();
    }
    this.profile.static.dispensing.materials = materials.map((m) => ({ ...m }));
    if (!this.profile.static.dispensing_meta) this.profile.static.dispensing_meta = {};
    if (materials.length) {
      this.profile.static.dispensing_meta.unit_formula_id = formula?.id || this.unitFormulaPickId;
      this.profile.static.dispensing_meta.mfr_no =
        formula?.mfr_no || this.profile.static.unit_formula_meta?.mfr_no || '';
      this.profile.static.dispensing_meta.product_code =
        formula?.product_code || this.unitFormulaProductCode();
    } else {
      delete this.profile.static.dispensing_meta.unit_formula_id;
      delete this.profile.static.dispensing_meta.mfr_no;
    }
  }

  private validateProductSpecAgainstBoundProduct(): void {
    if (!this.profile?.static) return;
    const code = this.unitFormulaProductCode();
    const meta = this.profile.static.product_spec_meta;
    if (meta?.product_code && code && meta.product_code !== code) {
      this.profile.static.product_spec = [];
      this.profile.static.product_spec_meta = null;
      this.productSpecPickId = null;
      this.productSpecRows = [];
    }
  }

  loadProductSpecMasterList(showToast = false): void {
    this.syncUnitFormulaBoundProductPick();
    this.validateProductSpecAgainstBoundProduct();
    const productCode = this.unitFormulaProductCode();
    if (!productCode) {
      this.productSpecMasterList = [];
      if (showToast) {
        alertify.warning(this.isProductSpecific ? 'Link a product to this BMR first' : 'Bind a product to this BMR first');
      }
      return;
    }
    this.productSpecMasterLoading = true;
    this.service
      .get('master/ebmr_bpr.php?type=getProductSpecMasterList&product_code=' + encodeURIComponent(productCode))
      .subscribe({
        next: (r: any) => {
          this.productSpecMasterList =
            r?.status === 'success' && Array.isArray(r.specifications) ? r.specifications : [];
          this.productSpecMasterLoading = false;
          if (this.productSpecPickId && !this.productSpecMasterList.some((s) => s.id === this.productSpecPickId)) {
            this.productSpecPickId = null;
            this.productSpecRows = [];
          }
          if (!this.productSpecPickId && this.productSpecMasterList.length === 1) {
            this.productSpecPickId = this.productSpecMasterList[0].id;
            this.loadProductSpecRows(false);
          }
          if (showToast) {
            alertify.message(this.productSpecMasterList.length + ' approved specification(s) for ' + productCode);
          }
        },
        error: () => {
          this.productSpecMasterList = [];
          this.productSpecMasterLoading = false;
          if (showToast) alertify.error('Failed to load Specification Master');
        },
      });
  }

  productSpecLabel(s: ProductSpecMasterSummary): string {
    return productSpecMasterLabel(s);
  }

  onProductSpecPickChange(): void {
    this.loadProductSpecRows(false);
  }

  loadProductSpecRows(showToast = false): void {
    const id = Number(this.productSpecPickId || 0);
    const productCode = this.unitFormulaProductCode();
    if (!id || !productCode) {
      this.productSpecRows = [];
      return;
    }
    this.productSpecRowsLoading = true;
    this.service
      .get(
        'master/ebmr_bpr.php?type=getProductSpecMasterRows&id=' +
          id +
          '&product_code=' +
          encodeURIComponent(productCode)
      )
      .subscribe({
        next: (r: any) => {
          this.productSpecRows = r?.status === 'success' ? normalizeProductSpecRows(r.rows) : [];
          this.productSpecRowsLoading = false;
          if (showToast) {
            alertify.message(this.productSpecRows.length + ' specification line(s) loaded');
          }
        },
        error: () => {
          this.productSpecRows = [];
          this.productSpecRowsLoading = false;
          if (showToast) alertify.error('Failed to load specification lines');
        },
      });
  }

  loadProductSpecFromMaster(): void {
    if (!this.profile?.static) return;
    const productCode = this.unitFormulaProductCode();
    if (!productCode) {
      alertify.warning(this.isProductSpecific ? 'Link a product to this BMR first' : 'Bind a product to this BMR first');
      return;
    }
    if (!this.productSpecPickId) {
      alertify.warning('Select a specification from the dropdown');
      return;
    }
    const apply = (rows: ProductSpecRow[], spec: any) => {
      if (!rows.length) {
        alertify.warning('No test lines in the selected specification');
        return;
      }
      this.profile.static.product_spec = rows.map((r) => ({ ...r }));
      this.profile.static.product_spec_meta = {
        id: spec?.id || this.productSpecPickId,
        product_code: spec?.product_code || productCode,
        specification_no: spec?.specification_no || '',
        version_no: spec?.version_no || '',
      };
      alertify.success('Product specification loaded (' + rows.length + ' lines) — read-only from master');
    };
    if (this.productSpecRows.length) {
      const s = this.productSpecMasterList.find((x) => x.id === this.productSpecPickId);
      apply(this.productSpecRows, s);
      return;
    }
    this.service
      .get(
        'master/ebmr_bpr.php?type=getProductSpecMasterRows&id=' +
          this.productSpecPickId +
          '&product_code=' +
          encodeURIComponent(productCode)
      )
      .subscribe({
        next: (r: any) => {
          const rows = r?.status === 'success' ? normalizeProductSpecRows(r.rows) : [];
          this.productSpecRows = rows;
          apply(rows, r?.specification);
        },
        error: () => alertify.error('Failed to load product specification'),
      });
  }

  loadGeneralInstructionsFromMaster(showToast = false): void {
    if (!this.profile?.static) return;
    this.generalInstructionsMasterLoading = true;
    this.service.get('master/ebmr_bpr.php?type=getGeneralInstructionsMaster').subscribe({
      next: (r: any) => {
        this.generalInstructionsMasterLoading = false;
        const rows = r?.status === 'success' && Array.isArray(r.rows) ? r.rows : [];
        const lines = rows.map((row: any) => String(row?.check_point ?? '').trim()).filter(Boolean);
        if (!lines.length) {
          if (showToast) {
            alertify.warning('No General Instructions in checklist master. Add under Master → Checklist (BMR).');
          }
          return;
        }
        const paragraphs = checklistLinesToParagraphs(lines);
        this.profile.static.general_instructions = { paragraphs, text: '' };
        syncGeneralInstructionsText(this.profile.static.general_instructions);
        if (showToast) {
          alertify.success(
            'General Instructions loaded from master (' + paragraphs.length + ' line(s)). Add sub-points as needed.'
          );
        }
      },
      error: () => {
        this.generalInstructionsMasterLoading = false;
        if (showToast) alertify.error('Failed to load General Instructions master');
      },
    });
  }

  private validateDispensingAgainstBoundProduct(): void {
    if (!this.profile?.static) return;
    const code = this.unitFormulaProductCode();
    const meta = this.profile.static.dispensing_meta;
    if (meta?.product_code && code && meta.product_code !== code) {
      this.profile.static.dispensing = emptyDispensingPage();
      this.profile.static.dispensing_meta = null;
    }
  }

  loadStoreDispensingFromMaster(showToast = false): void {
    if (!this.profile?.static) return;
    this.syncUnitFormulaBoundProductPick();
    this.validateDispensingAgainstBoundProduct();
    const productCode = this.unitFormulaProductCode();
    if (!productCode) {
      if (showToast) {
        alertify.warning(this.isProductSpecific ? 'Link a product to this BMR first' : 'Bind a product to this BMR first');
      }
      return;
    }
    this.storeDispensingLoading = true;
    this.service
      .get(
        'master/ebmr_bpr.php?type=getStoreDispensingMaster&product_code=' +
          encodeURIComponent(productCode)
      )
      .subscribe({
        next: (r: any) => {
          this.storeDispensingLoading = false;
          const sections = r?.status === 'success' ? normalizeDispensingSections(r.sections) : [];
          if (!sections.length) {
            if (showToast) {
              alertify.warning(
                'No store dispensing checklist found. Configure in Master → Checklist (BMR) or product-specific dispensing in eBMR.'
              );
            }
            return;
          }
          this.profile.static.dispensing = {
            sections: sections.map((s) => ({ ...s, rows: s.rows.map((row) => ({ ...row })) })),
            materials: (this.profile.static.dispensing?.materials || []).map((m) => ({ ...m })),
          };
          this.profile.static.dispensing_meta = {
            product_code: productCode,
            source: r?.source || 'store_dispensing_checklist',
          };
          if (showToast) {
            const lines = sections.reduce((n, s) => n + s.rows.length, 0);
            alertify.success('Dispensing loaded from stores (' + sections.length + ' section(s), ' + lines + ' line(s))');
          }
        },
        error: () => {
          this.storeDispensingLoading = false;
          if (showToast) alertify.error('Failed to load store dispensing checklist');
        },
      });
  }

  get dispensingPage(): DispensingPage {
    return normalizeDispensingPage(this.profile?.static?.dispensing);
  }

  get dispensingMaterials() {
    return this.dispensingPage.materials || [];
  }

  isDispensingMaterialRow(row: UnitFormulaRow): boolean {
    return isDispensingInProductionRow(row);
  }

  dispensingEvalLabel(value: string | undefined): string {
    return evaluationParameterLabel(value);
  }

  isGeneralInstructionSection(section: DispensingSection): boolean {
    return (section?.heading || '').trim() === 'General Instructions';
  }

  /* ------------- defaults ------------- */
  // Deep-merge a loaded object with a defaults template so the template never
  // hits an undefined nested property (prevents runtime crashes on old data).
  private mergeDefaults(target: any, defaults: any): any {
    const out = target && typeof target === 'object' && !Array.isArray(target) ? target : {};
    for (const k of Object.keys(defaults)) {
      const dv = defaults[k];
      if (dv && typeof dv === 'object' && !Array.isArray(dv)) {
        out[k] = this.mergeDefaults(out[k], dv);
      } else if (out[k] === undefined || out[k] === null) {
        out[k] = Array.isArray(dv) ? [] : dv;
      }
    }
    return out;
  }

  emptyStageConfig(): any {
    return {
      instructions: '',
      esign: {
        checking_required: true,
        approval_required: false,
        qa_check_required: false,
        qa_approval_required: false,
        line_clearance_ipqa: true,
      },
    };
  }
  emptyHeader(): any {
    return {
      bmr_for: 'Generic',
      product_code: '', product_name: '', product_for: '', generic_name: '', product_strength: '',
      bmr_no: '', mfr_no: '', client_name: '',
      label_claim: '',
      batch_size: '', batch_size_uom: '', pack_size: '', shelf_life: '',
      storage_condition: '', market: '', mfg_license: '', specification_ref: '',
      mfg_site: '', therapeutic_category: '', process_type: '',
    };
  }
  emptyStatic(): any {
    return {
      product_approval: emptyProductApprovalPage(),
      general_instructions: emptyGeneralInstructions(),
      safety: emptySafetyPrecautions(),
      equipment_selection: [], unit_formula: [], product_spec: [], dispensing: emptyDispensingPage(), dispensing_meta: null,
    };
  }
  emptyStepConfig(): any {
    return {
      master_forms: {
        line_clearance: false,
        procedure: false,
        inprocess_checks: false,
        dept_checks: false,
        qa_checks: false,
        ipqc: false,
        yield_table: false,
        weighing_table: false,
        equipment: false,
      },
      procedure: '',
      procedure_master_id: 0,
      procedure_mode: 'custom',
      procedure_paragraphs: [],
      yield_table_master_id: 0,
      yield_table: { columns: [], rows: [] },
      weighing_table_master_id: 0,
      weighing_table: { columns: [], rows: [] },
      inprocess_checks: [],
      inprocess_check_setup: {},
      line_clearance: [],
      dept_checks: [],
      qa_checks: [],
      equipment: [],
      ipqc: [],
      holding: { required: 'No', condition: '', max_duration: '' },
      step_timestamp: { enabled: false, capture_date: true, capture_start_time: true, capture_end_time: true, substeps: [] },
      checkpoint_sequence: [],
      yield: { enabled: false, rows: [] },
      custom_blocks: [],
    };
  }

  /** Back-fill master form toggles and paragraph structure for older saved profiles. */
  private normalizeStepConfig(config: any): any {
    const c = config;
    const mf = c.master_forms || {};
    if (mf.line_clearance === undefined && (c.line_clearance || []).length) mf.line_clearance = true;
    if (mf.procedure === undefined && (c.procedure || c.procedure_master_id || (c.procedure_paragraphs || []).length)) mf.procedure = true;
    if (mf.inprocess_checks === undefined && (c.inprocess_checks || []).length) mf.inprocess_checks = true;
    if (mf.dept_checks === undefined && (c.dept_checks || []).length) mf.dept_checks = true;
    if (mf.qa_checks === undefined && (c.qa_checks || []).length) mf.qa_checks = true;
    if (mf.ipqc === undefined && (c.ipqc || []).length) mf.ipqc = true;
    if (mf.yield_table === undefined && (c.yield?.enabled || c.yield_table_master_id || (c.yield_table?.columns || []).length)) mf.yield_table = true;
    if (mf.weighing_table === undefined && (c.weighing_table_master_id || (c.weighing_table?.columns || []).length)) mf.weighing_table = true;
    if (mf.equipment === undefined && (c.equipment || []).length) mf.equipment = true;
    c.master_forms = this.mergeDefaults(mf, this.emptyStepConfig().master_forms);

    if (!c.inprocess_check_setup || typeof c.inprocess_check_setup !== 'object') {
      c.inprocess_check_setup = {};
    }
    if (Array.isArray(c.inprocess_checks)) {
      c.inprocess_checks.forEach((id: any) => this.ensureInprocessSetup(c, id));
    }

    if (!c.yield_table || typeof c.yield_table !== 'object') c.yield_table = { columns: [], rows: [] };
    if (!c.weighing_table || typeof c.weighing_table !== 'object') c.weighing_table = { columns: [], rows: [] };
    if (!c.step_timestamp || typeof c.step_timestamp !== 'object') {
      c.step_timestamp = { enabled: false, capture_date: true, capture_start_time: true, capture_end_time: true, substeps: [] };
    } else {
      if (c.step_timestamp.enabled === undefined) c.step_timestamp.enabled = false;
      if (c.step_timestamp.capture_date === undefined) c.step_timestamp.capture_date = true;
      if (c.step_timestamp.capture_start_time === undefined) c.step_timestamp.capture_start_time = true;
      if (c.step_timestamp.capture_end_time === undefined) c.step_timestamp.capture_end_time = true;
      if (!Array.isArray(c.step_timestamp.substeps)) c.step_timestamp.substeps = [];
    }
    c.checkpoint_sequence = syncCheckpointSequenceForConfig(c);
    if (mf.yield_table && !c.yield_table.columns?.length && c.yield?.enabled && c.yield?.rows?.length) {
      c.yield_table = {
        columns: [
          { key: 'col_1', label: 'Label', col_type: 'text' },
          { key: 'col_2', label: 'Theoretical', col_type: 'number' },
          { key: 'col_3', label: 'Actual', col_type: 'number' },
          { key: 'col_4', label: 'UOM', col_type: 'text' },
          { key: 'col_5', label: 'Limit %', col_type: 'number' },
        ],
        rows: c.yield.rows.map((y: any) => ({
          col_1: y.label || '',
          col_2: y.theoretical || '',
          col_3: y.actual || '',
          col_4: y.uom || '',
          col_5: y.limit || '',
        })),
      };
    }

    if (!Array.isArray(c.procedure_paragraphs)) c.procedure_paragraphs = [];
    if (!c.procedure_paragraphs.length && c.procedure) {
      c.procedure_paragraphs = String(c.procedure)
        .split('\n')
        .map((line: string) => line.trim())
        .filter(Boolean)
        .map((line: string) => {
          const m = line.match(/^([\d]+(?:\.[\d]+)*\.?0?)\s+(.+)$/);
          if (m) {
            const segs = m[1].replace(/\.0$/, '').split('.').filter(Boolean);
            const level = Math.min(3, Math.max(1, segs.length));
            return { level, text: m[2], bold: false, italic: false };
          }
          return { ...blankProcedureParagraph(1), text: line };
        });
    }
    if (!c.procedure_mode) c.procedure_mode = c.procedure_master_id ? 'master' : 'custom';
    return c;
  }
  emptyRightTabs(): any {
    return {
      ipqc_results: { enabled: true, note: '' },
      critical_params: { enabled: true, rows: [] },
      deviations: { enabled: true, note: '' },
      manpower: { enabled: true, rows: [] },
      logbook: { enabled: true, note: '' },
      breakdown: { enabled: true, note: '' },
      incident: { enabled: true, note: '' },
    };
  }

  /* ------------- selection ------------- */
  get isProductSpecific(): boolean {
    return this.profile?.header?.bmr_for === 'Product';
  }

  get headerFieldLocked(): boolean {
    return this.isProductSpecific;
  }

  /** Pull latest product master data into header (product-specific BMR only). */
  syncProductHeaderFromMaster(showToast = true): void {
    const code = (this.profile?.header?.product_code || '').trim();
    if (!this.isProductSpecific || !code) {
      if (showToast) alertify.warning('No product code linked to this BMR.');
      return;
    }
    this.headerLoading = true;
    this.service.get('master/ebmr_bpr.php?type=getProductForBmrPrep&product_code=' + encodeURIComponent(code)).subscribe({
      next: (r: any) => {
        this.headerLoading = false;
        if (r?.status === 'success' && r.product) {
          this.applyMasterProduct(r.product);
          this.syncProductApprovalFromMaster(false);
          this.headerSyncedAt = new Date().toLocaleString();
          if (showToast) alertify.success('Product profile loaded from master');
        } else if (showToast) {
          alertify.error((r && r.message) || 'Product not found in master');
        }
      },
      error: () => {
        this.headerLoading = false;
        if (showToast) alertify.error('Failed to load product from master');
      },
    });
  }

  private applyMasterProduct(prod: any): void {
    if (!this.profile?.header || !prod) return;
    const h = this.profile.header;
    const set = (key: string, val: any) => {
      if (val !== undefined && val !== null && String(val).trim() !== '') {
        h[key] = String(val).trim();
      }
    };
    set('product_code', prod.product_code);
    set('product_name', prod.product_name);
    set('product_for', prod.product_for);
    set('generic_name', prod.generic_name);
    set('product_strength', prod.product_strength);
    set('mfr_no', prod.mfr_no);
    set('client_name', prod.client_name);
    set('label_claim', prod.label_claim_text || prod.label_claim);
    set('shelf_life', prod.shelf_life);
    set('storage_condition', prod.storage_condition);
    set('market', prod.market || prod.market_type);
    set('mfg_license', prod.mfg_license || prod.mfg_lic);
    set('therapeutic_category', prod.therapeutic_category || prod.thera);
    set('pack_size', prod.pack_size || prod.pack_desc || prod.pack_sizes);
    set('batch_size_uom', prod.batch_size_uom || prod.unit);
    if (prod.dosage_form && !h.dosage_form) {
      h.dosage_form = prod.dosage_form;
    }
  }

  selectHeader(): void { this.selected = { kind: 'header' }; }
  selectStatic(key: string): void {
    this.selected = { kind: 'static', staticKey: key };
    if (key === 'unit_formula') this.loadUnitFormulaMasterList(false);
    if (key === 'product_spec') this.loadProductSpecMasterList(false);
    if (key === 'dispensing') this.validateDispensingAgainstBoundProduct();
  }
  selectStage(i: number): void {
    this.stepViewOnly = this.panelReadOnly;
    this.signatureFocusStep = null;
    this.selected = { kind: 'stage', stageIndex: i };
  }
  selectStep(si: number, ti: number, viewOnly?: boolean): void {
    this.selected = { kind: 'step', stageIndex: si, stepIndex: ti };
    this.signatureFocusStep = this.profile?.stages?.[si]?.steps?.[ti] || null;
    if (viewOnly != null) this.stepViewOnly = viewOnly;
    else this.stepViewOnly = this.panelReadOnly;
  }

  get isBuildTab(): boolean {
    return this.builderTab === 'build';
  }
  get isCheckingTab(): boolean {
    return this.builderTab === 'checking';
  }
  get isReviewTab(): boolean {
    return this.builderTab === 'review';
  }
  get isFinalTab(): boolean {
    return this.builderTab === 'final';
  }
  get isFinalHtmlTab(): boolean {
    return this.builderTab === 'final_html_bmr' || this.builderTab === 'final_html_bpr';
  }
  /** Checking / Review / Final use Build layout but are not editable. */
  get panelReadOnly(): boolean {
    return this.builderTab !== 'build';
  }
  get stageTableMode(): 'builder' | 'checking' | 'review' | 'final' {
    if (this.builderTab === 'checking') return 'checking';
    if (this.builderTab === 'review') return 'review';
    if (this.builderTab === 'final' || this.isFinalHtmlTab) return 'final';
    return 'builder';
  }

  setBuilderTab(tab: 'build' | 'checking' | 'review' | 'final' | 'final_html_bmr' | 'final_html_bpr'): void {
    this.builderTab = tab;
    this.stepViewOnly = tab !== 'build';
    if (tab === 'checking' || tab === 'review') {
      this.loadWorkflowQueue();
    }
    if (tab !== 'build' && this.selected.kind === 'step') {
      this.stepViewOnly = true;
    }
  }

  loadWorkflowQueue(): void {
    this.workflowQueueLoading = true;
    this.service
      .get('master/ebmr_bpr.php?type=getMasterStepApprovals&profile_id=' + this.profileId)
      .subscribe({
        next: (r: any) => {
          this.workflowQueue = Array.isArray(r) ? r : [];
          this.workflowQueueLoading = false;
        },
        error: () => {
          this.workflowQueue = [];
          this.workflowQueueLoading = false;
        },
      });
  }

  get checkingQueue(): any[] {
    return (this.workflowQueue || []).filter((x) => x.workflow_status === 'Pending Check');
  }

  get reviewQueue(): any[] {
    return (this.workflowQueue || []).filter((x) => x.workflow_status === 'Pending Review');
  }

  stageStepRows(stageIndex: number): StageStepIndexRow[] {
    const stage = this.profile?.stages?.[stageIndex];
    if (!stage) return [];
    return buildStageStepIndexRows(stage.stage_name || 'Stage', stage.steps || [], {
      stageIndex,
      stageFrozen: !!Number(stage.frozen),
      mode: this.stageTableMode,
    });
  }

  onStageStepConfigure(row: StageStepIndexRow): void {
    if (row.stageIndex == null || row.stepIndex == null) return;
    this.selectStep(row.stageIndex, row.stepIndex, false);
  }

  onStageStepView(row: StageStepIndexRow): void {
    if (row.stageIndex == null || row.stepIndex == null) return;
    this.selectStep(row.stageIndex, row.stepIndex, true);
  }

  onStageStepEdit(row: StageStepIndexRow): void {
    if (row.stageIndex == null || row.stepIndex == null) return;
    this.selectStep(row.stageIndex, row.stepIndex, false);
  }

  onStageStepCheck(row: StageStepIndexRow): void {
    this.actOnStageStepRow(row, 'check');
  }

  onStageStepReview(row: StageStepIndexRow): void {
    this.actOnStageStepRow(row, 'review');
  }

  onStageStepSendBack(row: StageStepIndexRow): void {
    this.actOnStageStepRow(row, 'send_back');
  }

  actOnStageStepRow(row: StageStepIndexRow, action: 'check' | 'review' | 'approve' | 'send_back'): void {
    const step = row.stepRef;
    if (!step?.id) {
      alertify.error('Save the profile first so the step has an id');
      return;
    }
    this.actOnQueueItem(
      {
        id: step.id,
        stage_name: row.stageName,
        step_name: row.stepName,
      },
      action
    );
  }

  actOnCurrentStep(action: 'check' | 'review' | 'send_back'): void {
    const step = this.currentStep;
    const stage = this.currentStage;
    if (!step?.id) {
      alertify.error('Step id missing');
      return;
    }
    this.actOnQueueItem(
      {
        id: step.id,
        stage_name: stage?.stage_name || '',
        step_name: step.step_name || '',
      },
      action
    );
  }

  canCheckCurrentStep(): boolean {
    return this.isCheckingTab && String(this.currentStep?.workflow_status || '') === 'Pending Check';
  }

  canReviewCurrentStep(): boolean {
    return this.isReviewTab && String(this.currentStep?.workflow_status || '') === 'Pending Review';
  }

  currentStepSigners(): CfrSigner[] {
    const step = this.signatureFocusStep || this.currentStep;
    return masterStepSigners(step);
  }

  stepLockedForEdit(): boolean {
    const stage = this.currentStage;
    const step = this.currentStep;
    if (!step) return false;
    if (this.panelReadOnly || this.stepViewOnly) return true;
    return stepIsLocked(step, !!Number(stage?.frozen));
  }

  canFreezeCurrentStage(): boolean {
    const stage = this.currentStage;
    if (!stage || Number(stage.frozen)) return false;
    return stageAllStepsApproved(stage.steps || []);
  }

  saveCurrentStepWithPreparedBy(): void {
    const step = this.currentStep;
    const stage = this.currentStage;
    if (!step || !stage) return;
    if (Number(stage.frozen)) {
      alertify.error('Stage is frozen');
      return;
    }
    if (!stepCanConfigure(step, false) && !stepCanEdit(step, false)) {
      alertify.error('Step is locked at status: ' + stepWorkflowStatus(step));
      return;
    }
    this.syncAllProcedureTexts();
    this.syncAllStepTables();
    this.esign
      .request({
        meaning: 'Prepared By',
        module: 'master:profile_step',
        recordRef: step.id || this.profileId,
        detail: 'Prepare step: ' + (stage.stage_name || '') + ' / ' + (step.step_name || ''),
      })
      .then((sig) => {
        if (!sig) return;
        this.saving = true;
        const payload = {
          profile_id: this.profileId,
          profile_step_id: step.id,
          config: step.config,
          sign: esignResultToSnapshot(sig, 'Prepared By'),
        };
        if (!step.id) {
          // Persist tree first so DB ids exist, then prepare.
          this.persist(() => {
            this.reloadAndPrepareStep(stage.stage_name, step.step_name, esignResultToSnapshot(sig, 'Prepared By'));
          });
          return;
        }
        this.service
          .post('master/ebmr_bpr.php?type=saveProfileStepConfig', JSON.stringify(payload))
          .subscribe({
            next: (r: any) => {
              this.saving = false;
              if (r && r.status === 'success') {
                step.workflow_status = r.workflow_status || 'Pending Check';
                step.prepared = r.prepared || payload.sign;
                step.checked = null;
                step.reviewed = null;
                step.approved = null;
                step.correction_remark = '';
                this.signatureFocusStep = step;
                this.loadWorkflowQueue();
                alertify.success('Step saved & sent for Checking');
                this.selectStage(this.selected.stageIndex!);
              } else {
                alertify.error((r && r.message) || 'Step save failed');
              }
            },
            error: () => {
              this.saving = false;
              alertify.error('Step save failed');
            },
          });
      });
  }

  private reloadAndPrepareStep(stageName: string, stepName: string, sign: any): void {
    this.service.get('master/ebmr_bpr.php?type=getProfile&id=' + this.profileId).subscribe({
      next: (r: any) => {
        if (!(r && r.status === 'success')) {
          this.saving = false;
          alertify.error('Could not reload profile after structure save');
          return;
        }
        // Soft-update ids only
        (r.profile.stages || []).forEach((s: any, si: number) => {
          if (this.profile.stages[si]) {
            this.profile.stages[si].id = s.id;
            this.profile.stages[si].frozen = Number(s.frozen || 0);
            (s.steps || []).forEach((t: any, ti: number) => {
              if (this.profile.stages[si].steps[ti]) {
                this.profile.stages[si].steps[ti].id = t.id;
                this.profile.stages[si].steps[ti].workflow_status = t.workflow_status || 'Draft';
              }
            });
          }
        });
        const stage = (this.profile.stages || []).find(
          (s: any) => String(s.stage_name).toLowerCase() === String(stageName).toLowerCase()
        );
        const step = (stage?.steps || []).find(
          (t: any) => String(t.step_name).toLowerCase() === String(stepName).toLowerCase()
        );
        if (!step?.id) {
          this.saving = false;
          alertify.error('Step id missing after save — try again');
          return;
        }
        this.service
          .post(
            'master/ebmr_bpr.php?type=saveProfileStepConfig',
            JSON.stringify({
              profile_id: this.profileId,
              profile_step_id: step.id,
              config: step.config,
              sign,
            })
          )
          .subscribe({
            next: (r2: any) => {
              this.saving = false;
              if (r2 && r2.status === 'success') {
                step.workflow_status = r2.workflow_status || 'Pending Check';
                step.prepared = r2.prepared || sign;
                this.loadWorkflowQueue();
                alertify.success('Step saved & sent for Checking');
              } else {
                alertify.error((r2 && r2.message) || 'Step prepare failed');
              }
            },
            error: () => {
              this.saving = false;
              alertify.error('Step prepare failed');
            },
          });
      },
      error: () => {
        this.saving = false;
        alertify.error('Reload failed');
      },
    });
  }

  freezeCurrentStage(): void {
    const stage = this.currentStage;
    if (!stage) return;
    if (!this.canFreezeCurrentStage()) {
      alertify.error('All steps must be Approved before freezing this stage');
      return;
    }
    if (!stage.id) {
      alertify.warning('Save the profile once so the stage has an id, then freeze');
      this.persist();
      return;
    }
    this.esign
      .request({
        meaning: 'Approved By',
        module: 'master:profile_stage',
        recordRef: stage.id,
        detail: 'Freeze stage: ' + (stage.stage_name || ''),
        requireReason: true,
        reasonLabel: 'Freeze remark',
      })
      .then((sig) => {
        if (!sig) return;
        this.saving = true;
        this.service
          .post(
            'master/ebmr_bpr.php?type=masterStepWorkflow',
            JSON.stringify({
              action: 'freeze_stage',
              profile_stage_id: stage.id,
              sign: esignResultToSnapshot(sig, 'Approved By'),
              remark: sig.reason || '',
            })
          )
          .subscribe({
            next: (r: any) => {
              this.saving = false;
              if (r && r.status === 'success') {
                stage.frozen = 1;
                stage.freeze_sign = r.freeze_sign;
                (stage.steps || []).forEach((t: any) => {
                  if (stepWorkflowStatus(t) === 'Approved') t.workflow_status = 'Frozen';
                });
                alertify.success('Stage saved & frozen');
              } else {
                alertify.error((r && r.message) || 'Freeze failed');
              }
            },
            error: () => {
              this.saving = false;
              alertify.error('Freeze failed');
            },
          });
      });
  }

  actOnQueueItem(item: any, action: 'check' | 'review' | 'approve' | 'send_back'): void {
    const meaning =
      action === 'check' ? 'Checked By' : action === 'review' ? 'Reviewed By' : action === 'approve' ? 'Approved By' : 'Checked By';
    const requireReason = action === 'send_back';
    this.esign
      .request({
        meaning,
        module: 'master:profile_step',
        recordRef: item.id,
        detail: (item.stage_name || '') + ' / ' + (item.step_name || ''),
        requireReason,
        reasonLabel: action === 'send_back' ? 'Correction remark' : 'Remark',
      })
      .then((sig) => {
        if (!sig) return;
        this.service
          .post(
            'master/ebmr_bpr.php?type=masterStepWorkflow',
            JSON.stringify({
              action,
              profile_step_id: item.id,
              sign: esignResultToSnapshot(sig, meaning),
              remark: sig.reason || '',
            })
          )
          .subscribe({
            next: (r: any) => {
              if (r && r.status === 'success') {
                alertify.success(action === 'send_back' ? 'Sent back for correction' : 'Signed successfully');
                this.loadWorkflowQueue();
                this.loadProfile();
              } else {
                alertify.error((r && r.message) || 'Action failed');
              }
            },
            error: () => alertify.error('Action failed'),
          });
      });
  }

  get currentStage(): any {
    if (this.selected.stageIndex == null) return null;
    return this.profile.stages[this.selected.stageIndex];
  }
  get currentStep(): any {
    const s = this.currentStage;
    if (!s || this.selected.stepIndex == null) return null;
    return s.steps[this.selected.stepIndex];
  }
  staticLabel(key: string): string {
    return (this.staticPages.find((p) => p.key === key) || { label: key }).label;
  }

  /* ------------- stage / step tree ------------- */
  addStage(): void {
    this.profile.stages.push({
      stage_name: 'New Stage',
      seq_no: this.profile.stages.length + 1,
      stage_master_id: 0,
      frozen: 0,
      config: this.emptyStageConfig(),
      steps: [],
    });
    this.selectStage(this.profile.stages.length - 1);
  }
  addStageFromMaster(masterId: any): void {
    const m = this.stageMaster.find((x) => String(x.id) === String(masterId));
    if (!m) return;
    this.profile.stages.push({
      stage_name: m.stage_name,
      seq_no: this.profile.stages.length + 1,
      stage_master_id: m.id,
      config: this.emptyStageConfig(),
      steps: [],
    });
    this.selectStage(this.profile.stages.length - 1);
  }

  /* ------------- progress / travel bar ------------- */
  get activeStageIndex(): number {
    if ((this.selected.kind === 'stage' || this.selected.kind === 'step') && this.selected.stageIndex != null) {
      return this.selected.stageIndex;
    }
    return -1;
  }
  goStage(i: number): void {
    this.selectStage(i);
  }
  get stageInstructions(): string {
    const s = this.currentStage;
    return s && s.config ? s.config.instructions || '' : '';
  }
  removeStage(i: number): void {
    alertify.confirm('Remove Stage', 'Remove this stage and its steps from the profile?', () => {
      this.profile.stages.splice(i, 1);
      this.selectHeader();
    }, () => {});
  }
  moveStage(i: number, dir: number): void {
    const j = i + dir;
    if (j < 0 || j >= this.profile.stages.length) return;
    const arr = this.profile.stages;
    [arr[i], arr[j]] = [arr[j], arr[i]];
    arr.forEach((s: any, idx: number) => (s.seq_no = idx + 1));
  }
  addStep(si: number): void {
    const stage = this.profile.stages[si];
    if (Number(stage.frozen)) {
      alertify.error('Stage is frozen — cannot add steps');
      return;
    }
    stage.steps.push({
      step_name: 'New Step',
      seq_no: stage.steps.length + 1,
      step_master_id: 0,
      workflow_status: 'Draft',
      prepared: null,
      checked: null,
      reviewed: null,
      approved: null,
      config: this.emptyStepConfig(),
    });
    this.selectStep(si, stage.steps.length - 1);
  }
  removeStep(si: number, ti: number): void {
    this.profile.stages[si].steps.splice(ti, 1);
    this.selectStage(si);
  }
  moveStep(si: number, ti: number, dir: number): void {
    const steps = this.profile.stages[si].steps;
    const j = ti + dir;
    if (j < 0 || j >= steps.length) return;
    [steps[ti], steps[j]] = [steps[j], steps[ti]];
    steps.forEach((s: any, idx: number) => (s.seq_no = idx + 1));
  }

  /* ------------- step config helpers ------------- */
  masterFormEnabled(key: string): boolean {
    return !!this.currentStep?.config?.master_forms?.[key];
  }

  masterFormDefByKey(key: string): { key: string; label: string; icon: string; bindKey: string | null; listKey: string | null; labelField: string; hint: string } | undefined {
    return this.masterFormDefs.find((d) => d.key === key);
  }

  stepCheckpointSequence(): string[] {
    return syncCheckpointSequenceForConfig(this.currentStep?.config);
  }

  /** Selected/enabled checkpoints only — used for config panels and sequence UI. */
  enabledCheckpointSequence(): string[] {
    return this.stepCheckpointSequence();
  }

  private syncStepCheckpointSequence(): void {
    if (!this.currentStep?.config) return;
    this.currentStep.config.checkpoint_sequence = syncCheckpointSequenceForConfig(this.currentStep.config);
  }

  enabledMasterFormDefs(): typeof this.masterFormDefs {
    const order = this.enabledCheckpointSequence();
    const rank = new Map(order.map((k, i) => [k, i]));
    return this.masterFormDefs
      .filter((d) => this.masterFormEnabled(d.key))
      .sort((a, b) => (rank.get(a.key) ?? 999) - (rank.get(b.key) ?? 999));
  }

  checkpointSequenceLabel(key: string): string {
    const labels: Record<string, string> = {};
    this.masterFormDefs.forEach((d) => (labels[d.key] = d.label));
    return checkpointLabel(key, labels);
  }

  moveCheckpointSequence(index: number, dir: number): void {
    if (!this.currentStep?.config) return;
    const seq = [...this.enabledCheckpointSequence()];
    const j = index + dir;
    if (j < 0 || j >= seq.length) return;
    [seq[index], seq[j]] = [seq[j], seq[index]];
    this.currentStep.config.checkpoint_sequence = seq;
  }

  onCheckpointSelectionChanged(): void {
    this.syncStepCheckpointSequence();
  }

  toggleMasterForm(key: string, enabled: boolean): void {
    if (!this.currentStep?.config) return;
    this.currentStep.config.master_forms[key] = enabled;
    if (!enabled) {
      if (key === 'line_clearance') this.currentStep.config.line_clearance = [];
      if (key === 'inprocess_checks') {
        this.currentStep.config.inprocess_checks = [];
        this.currentStep.config.inprocess_check_setup = {};
      }
      if (key === 'dept_checks') this.currentStep.config.dept_checks = [];
      if (key === 'qa_checks') this.currentStep.config.qa_checks = [];
      if (key === 'ipqc') this.currentStep.config.ipqc = [];
      if (key === 'procedure') {
        this.currentStep.config.procedure_master_id = 0;
        this.currentStep.config.procedure_paragraphs = [];
        this.currentStep.config.procedure = '';
        this.currentStep.config.procedure_mode = 'custom';
      }
      if (key === 'yield_table') {
        this.currentStep.config.yield_table_master_id = 0;
        this.currentStep.config.yield_table = { columns: [], rows: [] };
        this.currentStep.config.yield.enabled = false;
        this.currentStep.config.yield.rows = [];
      }
      if (key === 'weighing_table') {
        this.currentStep.config.weighing_table_master_id = 0;
        this.currentStep.config.weighing_table = { columns: [], rows: [] };
      }
      if (key === 'equipment') {
        this.currentStep.config.equipment = [];
        this.stepEquipmentPickCode = '';
      }
    } else if (key === 'procedure' && !this.currentStep.config.procedure_paragraphs?.length) {
      this.currentStep.config.procedure_paragraphs = [blankProcedureParagraph(1)];
    } else if (key === 'yield_table' && !this.currentStep.config.yield_table?.columns?.length) {
      const def = defaultTableForType('yield');
      this.currentStep.config.yield_table = { columns: def.columns.map((c) => ({ ...c })), rows: def.rows.map((r) => ({ ...r })) };
    } else if (key === 'weighing_table' && !this.currentStep.config.weighing_table?.columns?.length) {
      const def = defaultTableForType('weighing');
      this.currentStep.config.weighing_table = { columns: def.columns.map((c) => ({ ...c })), rows: def.rows.map((r) => ({ ...r })) };
    }
    this.syncStepCheckpointSequence();
  }

  isDynamicTableForm(key: string): boolean {
    return key === 'yield_table' || key === 'weighing_table';
  }

  stepFormTableType(key: string): FormTableType {
    return key === 'weighing_table' ? 'weighing' : 'yield';
  }

  stepFormTable(key: string): { columns: DynamicColumn[]; rows: DynamicRow[] } {
    const cfg = this.currentStep?.config?.[key];
    if (!cfg || !cfg.columns) return { columns: [], rows: [] };
    return cfg;
  }

  stepFormTableMasterId(key: string): number {
    return Number(this.currentStep?.config?.[key + '_master_id'] || 0);
  }

  setStepFormTableMasterId(key: string, id: number): void {
    if (!this.currentStep?.config) return;
    this.currentStep.config[key + '_master_id'] = id;
    this.loadStepFormTableFromMaster(key);
  }

  stepFormTableMasterList(key: string): any[] {
    return key === 'weighing_table' ? this.weighingTableMaster : this.yieldTableMaster;
  }

  loadStepFormTableFromMaster(formKey: string): void {
    const id = this.stepFormTableMasterId(formKey);
    if (!id || !this.currentStep) return;
    this.service.get('master/ebmr_bpr.php?type=getFormTable&id=' + id).subscribe({
      next: (r: any) => {
        if (r?.status === 'success' && r.table) {
          this.currentStep.config[formKey] = {
            columns: (r.table.columns || []).map((c: DynamicColumn) => ({ ...c })),
            rows: (r.table.rows || []).map((row: DynamicRow) => ({ ...row })),
          };
          ensureRowKeys(this.currentStep.config[formKey].columns, this.currentStep.config[formKey].rows);
          if (formKey === 'yield_table') this.syncLegacyYieldFromStepTable(this.currentStep.config);
          alertify.message('Table loaded from master — customise if needed');
        } else {
          alertify.error('Table master not found');
        }
      },
      error: () => alertify.error('Failed to load table master'),
    });
  }

  addStepFormTableColumn(formKey: string): void {
    const tbl = this.stepFormTable(formKey);
    addColumn(tbl.columns, 'Column ' + (tbl.columns.length + 1));
    tbl.rows.forEach((row) => {
      row[tbl.columns[tbl.columns.length - 1].key] = '';
    });
    if (formKey === 'yield_table') this.syncLegacyYieldFromStepTable(this.currentStep.config);
  }

  removeStepFormTableColumn(formKey: string, colKey: string): void {
    const tbl = this.stepFormTable(formKey);
    if (tbl.columns.length <= 1) {
      alertify.warning('At least one column is required');
      return;
    }
    removeColumn(tbl.columns, tbl.rows, colKey);
    if (formKey === 'yield_table') this.syncLegacyYieldFromStepTable(this.currentStep.config);
  }

  addStepFormTableRow(formKey: string): void {
    addRow(this.stepFormTable(formKey).columns, this.stepFormTable(formKey).rows);
    if (formKey === 'yield_table') this.syncLegacyYieldFromStepTable(this.currentStep.config);
  }

  removeStepFormTableRow(formKey: string, i: number): void {
    const tbl = this.stepFormTable(formKey);
    if (tbl.rows.length <= 1) {
      alertify.warning('At least one row is required');
      return;
    }
    removeRow(tbl.rows, i);
    if (formKey === 'yield_table') this.syncLegacyYieldFromStepTable(this.currentStep.config);
  }

  private syncLegacyYieldFromStepTable(config: any): void {
    if (!config?.master_forms?.yield_table) return;
    const cols: DynamicColumn[] = config.yield_table?.columns || [];
    const rows: DynamicRow[] = config.yield_table?.rows || [];
    config.yield = config.yield || { enabled: false, rows: [] };
    config.yield.enabled = true;
    const pick = (row: DynamicRow, hints: string[], idx: number): string => {
      const col = cols.find((c) => hints.some((h) => c.label.toLowerCase().includes(h))) || cols[idx];
      return col ? String(row[col.key] ?? '') : '';
    };
    config.yield.rows = rows.map((row) => ({
      label: pick(row, ['item', 'label', 'stage'], 0),
      theoretical: pick(row, ['theoretical', 'theory'], 1),
      actual: pick(row, ['actual'], 2),
      uom: pick(row, ['uom', 'unit'], 3),
      limit: pick(row, ['limit'], 4),
    }));
  }

  private syncAllStepTables(): void {
    if (!this.profile?.stages) return;
    for (const s of this.profile.stages) {
      for (const t of s.steps || []) {
        if (t.config?.master_forms?.yield_table) this.syncLegacyYieldFromStepTable(t.config);
      }
    }
  }

  masterListFor(def: any): any[] {
    if (!def?.listKey) return [];
    return (this as any)[def.listKey] || [];
  }

  get numberedStepProcedure(): ReturnType<typeof assignProcedureNumbers> {
    return assignProcedureNumbers(this.currentStep?.config?.procedure_paragraphs || []);
  }

  loadProcedureFromMaster(): void {
    const id = Number(this.currentStep?.config?.procedure_master_id || 0);
    if (!id || !this.currentStep) return;
    this.service.get('master/ebmr_bpr.php?type=getProcedure&id=' + id).subscribe({
      next: (r: any) => {
        if (r?.status === 'success' && r.procedure) {
          this.currentStep.config.procedure_mode = 'master';
          this.currentStep.config.procedure_paragraphs = (r.procedure.paragraphs || []).map((p: ProcedureParagraph) => ({ ...p }));
          this.syncStepProcedureText();
          alertify.message('Procedure loaded from master — customise if needed');
        } else {
          alertify.error('Procedure not found');
        }
      },
      error: () => alertify.error('Failed to load procedure'),
    });
  }

  addStepProcedureParagraph(level = 1): void {
    if (!this.currentStep) return;
    this.currentStep.config.procedure_paragraphs.push(blankProcedureParagraph(level));
    this.syncStepProcedureText();
  }

  removeStepProcedureParagraph(i: number): void {
    if (!this.currentStep) return;
    this.currentStep.config.procedure_paragraphs.splice(i, 1);
    if (!this.currentStep.config.procedure_paragraphs.length) {
      this.currentStep.config.procedure_paragraphs.push(blankProcedureParagraph(1));
    }
    this.syncStepProcedureText();
  }

  moveStepProcedureParagraph(i: number, dir: number): void {
    if (!this.currentStep) return;
    const arr = this.currentStep.config.procedure_paragraphs;
    const j = i + dir;
    if (j < 0 || j >= arr.length) return;
    [arr[i], arr[j]] = [arr[j], arr[i]];
    this.syncStepProcedureText();
  }

  syncStepProcedureText(): void {
    if (!this.currentStep?.config) return;
    this.currentStep.config.procedure = procedureParagraphsToText(this.currentStep.config.procedure_paragraphs || []);
  }

  private syncAllProcedureTexts(): void {
    if (!this.profile?.stages) return;
    for (const s of this.profile.stages) {
      for (const t of s.steps || []) {
        if (t.config?.master_forms?.procedure && (t.config.procedure_paragraphs || []).length) {
          t.config.procedure = procedureParagraphsToText(t.config.procedure_paragraphs);
        }
      }
    }
  }

  toggleSelection(list: any[], id: any): void {
    const idx = list.indexOf(id);
    if (idx >= 0) {
      list.splice(idx, 1);
      if (this.currentStep?.config?.inprocess_check_setup) {
        delete this.currentStep.config.inprocess_check_setup[String(id)];
        delete this.currentStep.config.inprocess_check_setup[id];
      }
    } else {
      list.push(id);
      this.ensureInprocessSetup(this.currentStep?.config, id);
    }
  }
  isSelected(list: any[], id: any): boolean {
    return list && list.indexOf(id) >= 0;
  }

  /** Selected IPC master rows for the current step (form preview + role/frequency setup). */
  selectedInprocessChecks(): any[] {
    const ids: any[] = this.currentStep?.config?.inprocess_checks || [];
    if (!ids.length) return [];
    return ids
      .map((id) => (this.ipcMaster || []).find((c: any) => Number(c.id) === Number(id)))
      .filter((c) => !!c);
  }

  ensureInprocessSetup(config: any, id: any): void {
    if (!config) return;
    if (!config.inprocess_check_setup || typeof config.inprocess_check_setup !== 'object') {
      config.inprocess_check_setup = {};
    }
    const key = String(id);
    const master = (this.ipcMaster || []).find((c: any) => Number(c.id) === Number(id));
    if (!config.inprocess_check_setup[key]) {
      config.inprocess_check_setup[key] = {
        responsibility: master?.responsibility || 'Production',
        frequency: master?.frequency || '',
      };
    } else {
      if (!config.inprocess_check_setup[key].responsibility) {
        config.inprocess_check_setup[key].responsibility = master?.responsibility || 'Production';
      }
      if (!String(config.inprocess_check_setup[key].frequency || '').trim() && master?.frequency) {
        config.inprocess_check_setup[key].frequency = master.frequency;
      }
    }
  }

  inprocessSetup(id: any): any {
    this.ensureInprocessSetup(this.currentStep?.config, id);
    return this.currentStep.config.inprocess_check_setup[String(id)];
  }

  ipcLimitSummary(c: any): string {
    if (!c) return '—';
    switch (c.check_type) {
      case 'numeric':
        return `${c.target_value || '—'}${c.tolerance ? ' ± ' + c.tolerance : ''} ${c.uom || ''}`.trim();
      case 'range':
        return `${c.min_limit || '—'} to ${c.max_limit || '—'} ${c.uom || ''}`.trim();
      case 'min':
        return `NLT ${c.min_limit || '—'} ${c.uom || ''}`.trim();
      case 'max':
        return `NMT ${c.max_limit || '—'} ${c.uom || ''}`.trim();
      case 'selection':
        return 'Select options';
      case 'boolean':
        return 'Pass / Fail';
      default:
        return 'Descriptive';
    }
  }

  ipcTypeLabel(t: string): string {
    const map: any = {
      numeric: 'Numeric ±',
      range: 'Range',
      min: 'NLT (min)',
      max: 'NMT (max)',
      selection: 'Selection',
      boolean: 'Pass/Fail',
      text: 'Text',
    };
    return map[t] || t || '—';
  }
  removeEquipment(i: number): void {
    this.currentStep.config.equipment.splice(i, 1);
  }
  addYieldRow(): void {
    this.currentStep.config.yield.rows.push({ label: '', theoretical: '', actual: '', uom: '', limit: '' });
  }
  removeYieldRow(i: number): void {
    this.currentStep.config.yield.rows.splice(i, 1);
  }
  addCustomBlock(): void {
    this.currentStep.config.custom_blocks.push({ title: '', type: 'text', value: '', options: [] });
    this.syncStepCheckpointSequence();
  }
  removeCustomBlock(i: number): void {
    this.currentStep.config.custom_blocks.splice(i, 1);
    this.syncStepCheckpointSequence();
  }

  addTimestampSubstep(): void {
    const ts = this.currentStep?.config?.step_timestamp;
    if (!ts) return;
    if (!Array.isArray(ts.substeps)) ts.substeps = [];
    const n = ts.substeps.length + 1;
    ts.substeps.push({ id: 'ss_' + Date.now() + '_' + n, name: '' });
    ts.enabled = true;
    this.onCheckpointSelectionChanged();
  }

  removeTimestampSubstep(i: number): void {
    const ts = this.currentStep?.config?.step_timestamp;
    if (!ts || !Array.isArray(ts.substeps)) return;
    ts.substeps.splice(i, 1);
  }

  /* ------------- static page helpers ------------- */
  addStaticRow(key: string): void {
    if (key === 'unit_formula' || key === 'product_spec') return;
    if (!Array.isArray(this.profile.static[key])) this.profile.static[key] = [];
    if (key === 'equipment_selection') this.profile.static[key].push({ name: '', id_no: '', purpose: '', capacity: '', uom: '', equipment_code: '' });
  }
  removeStaticRow(key: string, i: number): void {
    if (key === 'unit_formula' || key === 'product_spec') return;
    this.profile.static[key].splice(i, 1);
  }

  fillProductApprovalFromHeader(): void {
    this.syncProductApprovalFromMaster(true);
  }

  /** Pull label claim & shelf life from Product Master; other fields stay for batch execution. */
  syncProductApprovalFromMaster(showToast = true): void {
    if (!this.profile?.static?.product_approval) return;
    const pap = this.profile.static.product_approval;
    clearProductApprovalExecutionFields(pap);

    const code = (this.profile.header?.product_code || this.products?.[0]?.product_code || '').trim();
    const apply = (source: Record<string, any>) => {
      applyMasterLockedFields(pap, source);
      if (showToast) alertify.success('Label claim & shelf life loaded from Product Master');
    };

    if (code) {
      this.service.get('master/ebmr_bpr.php?type=getProductForBmrPrep&product_code=' + encodeURIComponent(code)).subscribe({
        next: (r: any) => {
          if (r?.status === 'success' && r.product) {
            apply(r.product);
          } else {
            apply(this.profile.header || {});
            if (showToast) alertify.warning('Product not found — used BMR header values if available');
          }
        },
        error: () => {
          apply(this.profile.header || {});
          if (showToast) alertify.error('Failed to load product — used BMR header values if available');
        },
      });
      return;
    }

    apply(this.profile.header || {});
    if (showToast) {
      alertify.warning('No product linked — set Product BMR or bind a product, then refresh');
    }
  }

  /* ------------- right tab helpers ------------- */
  addCriticalParam(): void {
    this.profile.right_tabs.critical_params.rows.push({ parameter: '', target: '', limit: '', uom: '' });
  }
  removeCriticalParam(i: number): void {
    this.profile.right_tabs.critical_params.rows.splice(i, 1);
  }
  addManpowerRow(): void {
    this.profile.right_tabs.manpower.rows.push({ stage: '', activity: '', designation: '', count: 1 });
  }
  removeManpowerRow(i: number): void {
    this.profile.right_tabs.manpower.rows.splice(i, 1);
  }
  prefillManpowerFromMaster(): void {
    this.profile.right_tabs.manpower.rows = this.workAlloc.map((w) => ({
      stage: w.stage_ref, activity: w.activity, designation: w.designation, count: Number(w.manpower_count || 1),
    }));
    alertify.message('Manpower prefilled from Work Allocation master');
  }

  /* ------------- product binding ------------- */
  openBind(): void {
    this.bindModalOpen = true;
    this.searchProducts();
  }
  searchProducts(): void {
    this.service
      .get('master/ebmr_bpr.php?type=getProductsForBinding&search=' + encodeURIComponent(this.productSearch))
      .subscribe((r: any) => (this.productResults = Array.isArray(r) ? r : []));
  }
  bindProduct(p: any): void {
    const payload = { profile_id: this.profileId, product_code: p.product_code, product_name: p.product_name };
    this.service.post('master/ebmr_bpr.php?type=bindProduct', JSON.stringify(payload)).subscribe((r: any) => {
      if (r && r.status === 'success') {
        alertify.success('Product bound');
        this.refreshProducts();
      } else {
        alertify.error((r && r.message) || 'Bind failed');
      }
    });
  }
  unbind(p: any): void {
    this.service.get('master/ebmr_bpr.php?type=unbindProduct&id=' + p.id).subscribe((r: any) => {
      if (r && r.status === 'success') {
        alertify.success('Removed');
        this.refreshProducts();
      }
    });
  }
  useProductAsHeader(p: any): void {
    if (this.isProductSpecific) {
      this.profile.header.product_code = p.product_code;
      this.syncProductHeaderFromMaster(true);
      return;
    }
    this.profile.header.product_code = p.product_code;
    this.profile.header.product_name = p.product_name;
    this.syncProductApprovalFromMaster(false);
    alertify.message('Header product set. Review and complete the profile fields.');
  }
  refreshProducts(): void {
    this.service.get('master/ebmr_bpr.php?type=getProfile&id=' + this.profileId).subscribe((r: any) => {
      if (r && r.status === 'success') this.products = r.profile.products || [];
    });
  }

  /* ------------- save ------------- */
  save(): void {
    this.esign
      .request({ meaning: 'Prepared By', module: 'master:profile', recordRef: this.profileId, detail: 'Save eBMR/eBPR profile: ' + (this.profile.profile_name || '') })
      .then((sig) => {
        if (!sig) return;
        this.persist();
      });
  }

  saveSafetyPrecautions(): void {
    if (this.profile?.static?.safety) syncSafetyText(this.profile.static.safety);
    this.esign
      .request({
        meaning: 'Prepared By',
        module: 'master:profile',
        recordRef: this.profileId,
        detail: 'Save Safety & Precautions: ' + (this.profile.profile_name || ''),
      })
      .then((sig) => {
        if (!sig) return;
        this.persist();
      });
  }

  saveGeneralInstructions(): void {
    if (this.profile?.static?.general_instructions) {
      syncGeneralInstructionsText(this.profile.static.general_instructions);
    }
    this.esign
      .request({
        meaning: 'Prepared By',
        module: 'master:profile',
        recordRef: this.profileId,
        detail: 'Save General Instructions: ' + (this.profile.profile_name || ''),
      })
      .then((sig) => {
        if (!sig) return;
        this.persist();
      });
  }

  private persist(after?: () => void): void {
    this.syncAllProcedureTexts();
    this.syncAllStepTables();
    if (this.profile?.static?.safety) syncSafetyText(this.profile.static.safety);
    if (this.profile?.static?.general_instructions) {
      syncGeneralInstructionsText(this.profile.static.general_instructions);
    }
    this.saving = true;
    const meta = {
      profile_name: this.profile.profile_name,
      record_type: this.profile.record_type,
      dosage_form: this.profile.dosage_form,
      version: this.profile.version,
      status: this.profile.status || 'Draft',
      header_json: this.profile.header,
      static_json: this.profile.static,
      right_tabs_json: this.profile.right_tabs,
    };
    this.service.post('master/ebmr_bpr.php?type=updateProfile&id=' + this.profileId, JSON.stringify(meta)).subscribe((r: any) => {
      if (r && r.status === 'success') {
        const struct = { profile_id: this.profileId, stages: this.profile.stages };
        this.service.post('master/ebmr_bpr.php?type=saveProfileStructure', JSON.stringify(struct)).subscribe((r2: any) => {
          this.saving = false;
          if (r2 && r2.status === 'success') {
            if (after) {
              after();
            } else {
              alertify.success('Profile saved');
            }
          } else {
            alertify.error((r2 && r2.message) || 'Structure save failed');
          }
        });
      } else {
        this.saving = false;
        alertify.error((r && r.message) || 'Save failed');
      }
    });
  }

  markApproved(): void {
    const err = this.validateForApproval();
    if (err) { alertify.error(err); return; }
    alertify.confirm(
      'Approve Profile',
      'Approve this profile? Approved profiles become available for batch execution and should not be edited without re-approval.',
      () => {
        this.esign
          .request({ meaning: 'Approved By', module: 'master:profile', recordRef: this.profileId, detail: 'Approve profile: ' + (this.profile.profile_name || ''), requireReason: true, reasonLabel: 'Approval remark' })
          .then((sig) => {
            if (!sig) return;
            this.profile.status = 'Approved';
            this.persist();
          });
      },
      () => {}
    );
  }

  private validateForApproval(): string {
    const p = this.profile;
    if (!p.profile_name || !p.dosage_form) return 'Profile name and dosage form are required';
    if (this.isProductSpecific && (!p.header?.product_code || !p.header?.product_name)) {
      return 'Product BMR requires product code & name in the header before approval';
    }
    if (!p.stages || !p.stages.length) return 'Add at least one stage before approval';
    for (const s of p.stages) {
      if (!s.stage_name || !s.stage_name.trim()) return 'Every stage must have a name';
      if (!s.steps || !s.steps.length) return `Stage "${s.stage_name}" has no steps`;
      for (const t of s.steps) {
        if (!t.step_name || !t.step_name.trim()) return `A step in "${s.stage_name}" has no name`;
      }
    }
    return '';
  }

  close(): void {
    const data = this.route.snapshot.data || {};
    if (this.profile?.config_id) {
      this.router.navigate([data['prepRoute'] || '/master/ebmr-bpr/bmr-prep']);
    } else {
      this.router.navigate([data['profilesRoute'] || '/master/ebmr-bpr/profiles'], {
        queryParams: { type: this.profile?.record_type || 'eBMR' },
      });
    }
  }
}
