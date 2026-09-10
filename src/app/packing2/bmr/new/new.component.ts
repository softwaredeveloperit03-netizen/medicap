import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { PackingEbmrStage } from '../services/packing-ebmr-master.service';

type RowKind = 'step' | 'substep';

interface ProceedRow {
  key: string;
  kind: RowKind;
  stageTitle: string;
  stepId: string;
  stepName: string;
  substepId: string;
  substepName: string;
}

interface ApprovedBatchRow {
  product_code: string;
  product_name?: string;
  work_order_no: string;
  batch_number: string;
  approved_at?: string;
  approved_by_emp_id?: string;
  last_step_saved_at?: string;
}

type TableInputType = 'text' | 'number' | 'date' | 'time' | 'equipment' | 'employee';

interface RowTableColumn {
  key: string;
  header: string;
  type: TableInputType;
}

interface RowTableModel {
  columns: RowTableColumn[];
  rows: Array<Record<string, any>>;
}

interface StepBlock {
  stepId: string;
  stepName: string;
  mainRow: ProceedRow | null;
  subRows: ProceedRow[];
}

interface StageBlock {
  stageTitle: string;
  steps: StepBlock[];
}

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  product_code = '';
  product_name = '';
  work_order_no = '';
  batch_number = '';
  selectedBatchKey = '';
  selectedStageFilter = '';

  busy = false;
  loadingBatches = false;
  message = '';
  messageKind: 'ok' | 'err' | 'info' = 'info';

  approvedBatches: ApprovedBatchRow[] = [];
  productsByCode: Record<string, any> = {};
  stages: PackingEbmrStage[] = [];
  rows: ProceedRow[] = [];
  payloadText: Record<string, string> = {};
  savedAtMap: Record<string, string> = {};
  tableByRow: Record<string, RowTableModel> = {};
  newColNameByRow: Record<string, string> = {};
  newColTypeByRow: Record<string, TableInputType> = {};
  equipments: any[] = [];
  employees: any[] = [];
  headerPopupOpen = false;
  headerPopupRowKey = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadProductMasterMap();
    this.getApprovedBatches();
    this.loadReferenceLists();
  }

  private loadReferenceLists(): void {
    this.service.get('common.php?type=getEquipments').subscribe({
      next: (res: any) => {
        this.equipments = Array.isArray(res) ? res : [];
      },
      error: () => {
        this.equipments = [];
      },
    });
    this.service.get('employee.php?type=getEmp').subscribe({
      next: (res: any) => {
        this.employees = Array.isArray(res) ? res : [];
      },
      error: () => {
        this.employees = [];
      },
    });
  }

  equipmentLabel(e: any): string {
    if (!e) {
      return '';
    }
    const name = String(e.equipment_name || e.name || '').trim();
    const code = String(e.equipment_code || e.code || '').trim();
    return code ? `${name} (${code})` : name;
  }

  employeeLabel(emp: any): string {
    if (!emp) {
      return '';
    }
    const id = String(emp.emp_id || emp.id || '').trim();
    const name = String(emp.emp_name || emp.name || '').trim();
    return id ? `${name} (${id})` : name;
  }

  private detectTypeFromHeader(label: string): TableInputType {
    const s = String(label || '').toLowerCase();
    if (
      s.includes('equipment') ||
      s.includes('machine') ||
      s.includes('equip id') ||
      s.includes('equipment id')
    ) {
      return 'equipment';
    }
    if (
      s.includes('employee') ||
      s.includes('checked by') ||
      s.includes('check by') ||
      s.includes('approved by') ||
      s.includes('approve by') ||
      s.includes('verified by') ||
      s.includes('prepared by') ||
      s.includes('reviewed by') ||
      s.includes('sign by')
    ) {
      return 'employee';
    }
    if (s.includes('date')) {
      return 'date';
    }
    if (s.includes('time')) {
      return 'time';
    }
    if (
      s.includes('qty') ||
      s.includes('quantity') ||
      s.includes('count') ||
      s.includes('weight') ||
      s.includes('temp') ||
      s.includes('humidity')
    ) {
      return 'number';
    }
    return 'text';
  }

  private loadProductMasterMap(): void {
    this.service.get('master/product.php?type=getProductsLog').subscribe({
      next: (res: any) => {
        const arr = Array.isArray(res) ? res : [];
        const map: Record<string, any> = {};
        for (const p of arr) {
          const code =
            (p?.product_code != null && String(p.product_code).trim() !== ''
              ? String(p.product_code).trim()
              : p?.product_code1 != null
              ? String(p.product_code1).trim()
              : '') || '';
          if (code) {
            map[code] = p;
          }
        }
        this.productsByCode = map;
      },
      error: () => {
        this.productsByCode = {};
      },
    });
  }

  private notify(kind: 'ok' | 'err' | 'info', msg: string): void {
    this.messageKind = kind;
    this.message = msg;
  }

  private hasBatchContext(): boolean {
    return (
      this.product_code.trim() !== '' &&
      this.work_order_no.trim() !== '' &&
      this.batch_number.trim() !== ''
    );
  }

  private makeKey(stepId: string, subId: string): string {
    return subId ? 'sub:' + stepId + ':' + subId : 'step:' + stepId;
  }

  private parseTextPayload(s: string): any {
    const t = (s || '').trim();
    if (!t) {
      return {};
    }
    try {
      return JSON.parse(t);
    } catch {
      return { value: t };
    }
  }

  private makeColumnKey(name: string): string {
    const base = String(name || '')
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '');
    return base || 'col_' + Date.now();
  }

  private getOrCreateTable(row: ProceedRow): RowTableModel {
    const k = row.key;
    if (!this.tableByRow[k]) {
      this.tableByRow[k] = {
        columns: [],
        rows: [],
      };
      this.newColTypeByRow[k] = 'text';
      this.newColNameByRow[k] = '';
    }
    return this.tableByRow[k];
  }

  openHeaderPopup(row: ProceedRow): void {
    this.getOrCreateTable(row);
    this.headerPopupRowKey = row.key;
    this.headerPopupOpen = true;
  }

  closeHeaderPopup(): void {
    this.headerPopupOpen = false;
    this.headerPopupRowKey = '';
  }

  get popupRow(): ProceedRow | null {
    if (!this.headerPopupRowKey) {
      return null;
    }
    return this.rows.find((r) => r.key === this.headerPopupRowKey) || null;
  }

  getTableColumns(row: ProceedRow): RowTableColumn[] {
    return this.getOrCreateTable(row).columns;
  }

  getTableRows(row: ProceedRow): Array<Record<string, any>> {
    return this.getOrCreateTable(row).rows;
  }

  addTableColumn(row: ProceedRow): void {
    const k = row.key;
    const label = (this.newColNameByRow[k] || '').trim();
    if (!label) {
      this.notify('err', 'Enter header name first.');
      return;
    }
    const t = this.getOrCreateTable(row);
    let type = (this.newColTypeByRow[k] || 'text') as TableInputType;
    if (type === 'text') {
      type = this.detectTypeFromHeader(label);
    }
    let key = this.makeColumnKey(label);
    if (t.columns.some((c) => c.key === key)) {
      key = key + '_' + (t.columns.length + 1);
    }
    t.columns.push({ key, header: label, type });
    for (const r of t.rows) {
      r[key] = '';
    }
    this.newColNameByRow[k] = '';
    this.syncPayloadFromTable(row);
  }

  removeTableColumn(row: ProceedRow, ci: number): void {
    const t = this.getOrCreateTable(row);
    const col = t.columns[ci];
    if (!col) {
      return;
    }
    t.columns.splice(ci, 1);
    for (const r of t.rows) {
      delete r[col.key];
    }
    if (!t.columns.length) {
      t.rows = [];
    }
    this.syncPayloadFromTable(row);
  }

  addTableDataRow(row: ProceedRow): void {
    const t = this.getOrCreateTable(row);
    if (!t.columns.length) {
      this.notify('err', 'Design headers first, then add execution row.');
      return;
    }
    const obj: Record<string, any> = {};
    for (const c of t.columns) {
      obj[c.key] = '';
    }
    t.rows.push(obj);
    this.syncPayloadFromTable(row);
  }

  removeTableDataRow(row: ProceedRow, ri: number): void {
    const t = this.getOrCreateTable(row);
    t.rows.splice(ri, 1);
    this.syncPayloadFromTable(row);
  }

  syncPayloadFromTable(row: ProceedRow): void {
    const t = this.getOrCreateTable(row);
    const data = {
      table_columns: t.columns.map((c) => ({ key: c.key, header: c.header, type: c.type })),
      table_rows: t.rows,
    };
    this.payloadText[row.key] = this.toPrettyJson(data);
  }

  private hydrateTableFromPayload(row: ProceedRow, payload: any): void {
    const k = row.key;
    const cols = Array.isArray(payload?.table_columns) ? payload.table_columns : [];
    const rows = Array.isArray(payload?.table_rows) ? payload.table_rows : [];
    if (!cols.length) {
      return;
    }
    this.tableByRow[k] = {
      columns: cols.map((c: any) => ({
        key: String(c?.key || this.makeColumnKey(c?.header || 'value')),
        header: String(c?.header || c?.key || 'Value'),
        type: (
          ['text', 'number', 'date', 'time', 'equipment', 'employee'].includes(String(c?.type))
            ? c.type
            : this.detectTypeFromHeader(String(c?.header || ''))
        ) as TableInputType,
      })),
      rows: rows.length ? rows : [{}],
    };
    if (!cols.length) {
      this.tableByRow[k].rows = [];
    }
    this.newColTypeByRow[k] = 'text';
    this.newColNameByRow[k] = '';
    this.syncPayloadFromTable(row);
  }

  private toPrettyJson(v: any): string {
    try {
      return JSON.stringify(v ?? {}, null, 2);
    } catch {
      return '{}';
    }
  }

  private composeBatchKey(item: ApprovedBatchRow): string {
    return [
      String(item?.product_code ?? '').trim(),
      String(item?.work_order_no ?? '').trim(),
      String(item?.batch_number ?? '').trim(),
    ].join('||');
  }

  batchOptionLabel(item: ApprovedBatchRow): string {
    const batch = String(item?.batch_number ?? '').trim();
    const wo = String(item?.work_order_no ?? '').trim();
    const pc = String(item?.product_code ?? '').trim();
    const at = String(item?.approved_at ?? '-').trim();
    return `${batch} | WO: ${wo} | Product: ${pc} | Approved: ${at}`;
  }

  getApprovedBatches(): void {
    this.loadingBatches = true;
    this.service
      .get('bmr/ebmr_filled_step_api.php?type=get_approved_batches')
      .subscribe({
        next: (res: any) => {
          this.loadingBatches = false;
          let parsed: any = res;
          if (typeof parsed === 'string') {
            try {
              parsed = JSON.parse(parsed);
            } catch {
              parsed = {};
            }
          }
          const raw = parsed?.batches;
          let arr: any[] = [];
          if (Array.isArray(raw)) {
            arr = raw;
          } else if (raw && typeof raw === 'object') {
            arr = Object.values(raw);
          }
          this.approvedBatches = arr as ApprovedBatchRow[];
          if (!this.approvedBatches.length) {
            this.notify(
              'info',
              'No approved batches found yet. Approve in Awaiting Proceed Check first.'
            );
          } else {
            this.notify('ok', 'Loaded ' + this.approvedBatches.length + ' approved batches.');
          }
        },
        error: () => {
          this.loadingBatches = false;
          this.notify('err', 'Unable to load approved batch list.');
        },
      });
  }

  onBatchSelectionChanged(): void {
    const key = (this.selectedBatchKey || '').trim();
    if (!key) {
      this.product_code = '';
      this.product_name = '';
      this.work_order_no = '';
      this.batch_number = '';
      this.rows = [];
      this.stages = [];
      return;
    }
    const row = this.approvedBatches.find((x) => this.composeBatchKey(x) === key);
    if (!row) {
      return;
    }
    this.product_code = row.product_code || '';
    const master = this.productsByCode[this.product_code];
    this.product_name = row.product_name || master?.product_name || '';
    this.work_order_no = row.work_order_no || '';
    this.batch_number = row.batch_number || '';
  }

  generateFormForRow(row: ProceedRow): void {
    const name = `${row.stepName} ${row.substepName}`.toLowerCase();
    const obj: any = {
      title: row.kind === 'substep' ? row.substepName || row.stepName : row.stepName,
      observed_value: '',
      result: 'Pass',
      remarks: '',
      checked_by_emp_id: '',
      checked_at: '',
    };

    // Lightweight RnD "AI-like" heuristics from label words.
    if (name.includes('temperature') || name.includes('temp')) {
      obj.temperature_c = '';
    }
    if (name.includes('humidity')) {
      obj.relative_humidity_percent = '';
    }
    if (name.includes('weight') || name.includes('weigh')) {
      obj.weight = '';
      obj.weight_uom = 'kg';
    }
    if (name.includes('qty') || name.includes('quantity') || name.includes('count')) {
      obj.quantity = '';
      obj.quantity_uom = '';
    }
    if (name.includes('label') || name.includes('artwork') || name.includes('code')) {
      obj.reference_code = '';
      obj.verification = '';
    }
    if (name.includes('line clearance') || name.includes('clearance')) {
      obj.clearance_status = 'Done';
      obj.area = '';
    }
    if (name.includes('equipment') || name.includes('machine')) {
      obj.equipment_id = '';
    }

    const table = this.getOrCreateTable(row);
    const columns: RowTableColumn[] = [];
    const rowObj: Record<string, any> = {};
    for (const k of Object.keys(obj)) {
      const v = obj[k];
      let t: TableInputType = 'text';
      if (typeof v === 'number') {
        t = 'number';
      } else if (k.includes('date') || k.endsWith('_at')) {
        t = 'date';
      } else if (k.includes('time')) {
        t = 'time';
      }
      columns.push({ key: this.makeColumnKey(k), header: k, type: t });
    }
    for (const c of columns) {
      rowObj[c.key] = '';
    }
    table.columns = columns.length ? columns : [{ key: 'value', header: 'Value', type: 'text' }];
    table.rows = [rowObj];
    this.syncPayloadFromTable(row);
    this.notify('ok', 'Auto-structured payload generated for this row.');
  }

  generateFormsForAll(): void {
    for (const row of this.rows) {
      if ((this.payloadText[row.key] || '').trim()) {
        continue;
      }
      this.generateFormForRow(row);
    }
    this.notify('ok', 'Auto-structured payload generated for all empty rows.');
  }

  private rebuildRows(): void {
    const out: ProceedRow[] = [];
    for (const st of this.stages || []) {
      const stageTitle = (st as any)?.stages || '';
      for (const sp of st.Steps || []) {
        const stepId = String((sp as any)?.id ?? '');
        const stepName = String((sp as any)?.step ?? '');
        out.push({
          key: this.makeKey(stepId, ''),
          kind: 'step',
          stageTitle,
          stepId,
          stepName,
          substepId: '',
          substepName: '',
        });
        for (const su of sp.Substeps || []) {
          const substepId = String((su as any)?.id ?? '');
          const substepName = String((su as any)?.substep ?? '');
          out.push({
            key: this.makeKey(stepId, substepId),
            kind: 'substep',
            stageTitle,
            stepId,
            stepName,
            substepId,
            substepName,
          });
        }
      }
    }
    this.rows = out;
    for (const r of this.rows) {
      this.getOrCreateTable(r);
    }
  }

  loadMasterAndSaved(): void {
    if (!this.product_code.trim()) {
      this.notify('err', 'Product code is required.');
      return;
    }
    this.busy = true;
    this.service
      .get(
        'bmr/packing_ebmr_master_api.php?type=get_master_tree&product_code=' +
          encodeURIComponent(this.product_code.trim())
      )
      .subscribe({
        next: (res: any) => {
          if (res?.status !== 'ok' || !Array.isArray(res?.Stages)) {
            this.busy = false;
            this.stages = [];
            this.rows = [];
            this.notify('err', res?.msg || 'Master not found for this product.');
            return;
          }
          this.product_name = (res?.product_name as string) || this.product_name;
          this.stages = res.Stages as PackingEbmrStage[];
          this.rebuildRows();
          if (this.hasBatchContext()) {
            this.loadSaved();
            return;
          }
          this.busy = false;
          this.notify('ok', 'Master loaded. Add work order + batch to fetch saved records.');
        },
        error: () => {
          this.busy = false;
          this.notify('err', 'Unable to load master.');
        },
      });
  }

  loadSaved(): void {
    if (!this.hasBatchContext()) {
      this.notify('err', 'Product code, work order, and batch number are required.');
      return;
    }
    this.busy = true;
    this.service
      .get(
        'bmr/ebmr_filled_step_api.php?type=get_batch_steps&product_code=' +
          encodeURIComponent(this.product_code.trim()) +
          '&work_order_no=' +
          encodeURIComponent(this.work_order_no.trim()) +
          '&batch_number=' +
          encodeURIComponent(this.batch_number.trim())
      )
      .subscribe({
        next: (res: any) => {
          this.busy = false;
          const steps = (res?.steps || {}) as Record<string, any>;
          const subs = (res?.substeps || {}) as Record<string, any>;
          this.savedAtMap = {};
          for (const row of this.rows) {
            if (row.kind === 'step') {
              const s = steps[row.stepId];
              if (s?.data !== undefined) {
                this.payloadText[row.key] = this.toPrettyJson(s.data);
                this.savedAtMap[row.key] = s?.saved_at || '';
                this.hydrateTableFromPayload(row, s.data);
              }
            } else {
              const k = row.stepId + ':' + row.substepId;
              // Server historically used both "step:sub" and "step__sub" keys.
              const u = subs[k] || subs[row.stepId + '__' + row.substepId];
              if (u?.data !== undefined) {
                this.payloadText[row.key] = this.toPrettyJson(u.data);
                this.savedAtMap[row.key] = u?.saved_at || '';
                this.hydrateTableFromPayload(row, u.data);
              }
            }
          }
          this.notify('ok', 'Saved batch entries loaded.');
        },
        error: () => {
          this.busy = false;
          this.notify('err', 'Unable to load saved batch entries.');
        },
      });
  }

  saveRow(row: ProceedRow): void {
    if (!this.hasBatchContext()) {
      this.notify('err', 'Product code, work order, and batch number are required.');
      return;
    }
    this.syncPayloadFromTable(row);
    const data = this.parseTextPayload(this.payloadText[row.key] || '{}');
    const base = {
      product_code: this.product_code.trim(),
      work_order_no: this.work_order_no.trim(),
      batch_number: this.batch_number.trim(),
      step_id: row.stepId,
      step_name: row.stepName,
      data,
    } as any;

    const isSub = row.kind === 'substep';
    if (isSub) {
      base.substep_id = row.substepId;
      base.substep_name = row.substepName;
    }

    this.busy = true;
    this.service
      .postJson(
        'bmr/ebmr_filled_step_api.php?type=' + (isSub ? 'save_substep' : 'save_step'),
        JSON.stringify(base)
      )
      .subscribe({
        next: () => {
          this.busy = false;
          this.savedAtMap[row.key] = new Date().toISOString();
          this.notify('ok', (isSub ? 'Substep' : 'Step') + ' saved.');
        },
        error: () => {
          this.busy = false;
          this.notify('err', 'Save failed.');
        },
      });
  }

  saveAll(): void {
    for (const row of this.rows) {
      this.saveRow(row);
    }
  }

  /** Build Stage -> Step -> Substep structure for cleaner execution UI. */
  get stageStepGroups(): StageBlock[] {
    const stageMap: Record<string, Record<string, StepBlock>> = {};
    const stageOrder: string[] = [];
    const stepOrderByStage: Record<string, string[]> = {};

    for (const row of this.rows) {
      const sName = row.stageTitle || 'Uncategorized stage';
      const sKey = sName;
      if (!stageMap[sKey]) {
        stageMap[sKey] = {};
        stageOrder.push(sKey);
        stepOrderByStage[sKey] = [];
      }
      const pKey = `${row.stepId}__${row.stepName}`;
      if (!stageMap[sKey][pKey]) {
        stageMap[sKey][pKey] = {
          stepId: row.stepId,
          stepName: row.stepName,
          mainRow: null,
          subRows: [],
        };
        stepOrderByStage[sKey].push(pKey);
      }
      if (row.kind === 'step') {
        stageMap[sKey][pKey].mainRow = row;
      } else {
        stageMap[sKey][pKey].subRows.push(row);
      }
    }

    return stageOrder.map((sKey) => ({
      stageTitle: sKey,
      steps: stepOrderByStage[sKey].map((pKey) => stageMap[sKey][pKey]),
    }));
  }

  get stageFilterOptions(): string[] {
    return this.stageStepGroups.map((g) => g.stageTitle);
  }

  get visibleStageStepGroups(): StageBlock[] {
    const stage = (this.selectedStageFilter || '').trim();
    if (!stage) {
      return this.stageStepGroups;
    }
    return this.stageStepGroups.filter((g) => g.stageTitle === stage);
  }

  trackRow(_i: number, row: ProceedRow): string {
    return row.key;
  }

  trackStageGroup(_i: number, g: StageBlock): string {
    return g.stageTitle;
  }

  trackStepBlock(_i: number, s: StepBlock): string {
    return `${s.stepId}__${s.stepName}`;
  }

  trackApprovedBatch = (_i: number, row: ApprovedBatchRow): string =>
    [
      String(row?.product_code ?? '').trim(),
      String(row?.work_order_no ?? '').trim(),
      String(row?.batch_number ?? '').trim(),
    ].join('||');

}
