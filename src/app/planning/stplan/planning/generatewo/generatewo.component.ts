import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface ProductMaterialRow {
  material_code: string;
  material_name: string;
  material_type?: string;
  mat_category?: 'RM' | 'PM';
  unit: string;
  required_qty: number;
  available_qty: number;
  short_qty: number;
}

interface StockDeductionRecord {
  workorder_no: string;
  unit: string;
  material_code: string;
  material_name?: string;
  type: 'RM' | 'PM';
  deducted_from_RM?: number;
  deducted_from_MC?: number;
  deducted_from_Bulk?: number;
  shortage: number;
  status1?: string;
}

interface WorkOrder {
  workorder_no: string;
  order_no?: string;
  id?: string | number;
  selected?: boolean;
  status?: string;
  plan_materials?: ProductMaterialRow[];
  deductions?: StockDeductionRecord[];
  [key: string]: any;
}

interface MonthGroup {
  key: string;
  label: string;
  workOrders: WorkOrder[];
}

interface ForecastGroup {
  order_no: string;
  groupKey?: string;
  collapsed: boolean;
  workOrders: WorkOrder[];
  monthGroups: MonthGroup[];
  planMonth?: string;
  billing_type: string;
  mainGroupName?: string;
  productCodes: string[];
  productSummary: string;
  bg_color?: string;
  selectedCount: number;
  allSelected: boolean;
  canPlanCount: number;
  cannotPlanCount: number;
  totalCount: number;
  hasCombiChild?: boolean;
}

@Component({
  selector: 'app-generatewo',
  templateUrl: './generatewo.component.html',
  styleUrls: ['./generatewo.component.css']
})
export class GeneratewoComponent implements OnInit {
  pendingpo: any = [];
  loading = false;

  isView = false;
  selectedResult: any[] = [];

  selectedPO = [];
  Worders: WorkOrder[] = [];
  autoSendInProgress = false;

  // Cancelled work orders log
  showCancelled = false;
  cancelledWOs: any[] = [];
  cancelledBackup: any[] = [];
  cancelledLoading = false;
  cancelledSearch = '';

  forecastGroups: ForecastGroup[] = [];
  private collapsedForecastKeys = new Set<string>();

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingWOs();
  }

  /** Open the Cancelled WO log view. */
  openCancelledWOs(): void {
    this.showCancelled = true;
    this.loadCancelledWOs();
  }

  /** Return to the main Generate WO log. */
  closeCancelledWOs(): void {
    this.showCancelled = false;
  }

  /** Fetch all work orders currently in the Cancelled state. */
  loadCancelledWOs(): void {
    this.cancelledLoading = true;
    this.service.get('marketing/po.php?type=getCancelledGeneratedWOs')
      .subscribe((response: any) => {
        this.cancelledBackup = Array.isArray(response) ? response : [];
        this.cancelledWOs = [...this.cancelledBackup];
        this.cancelledLoading = false;
        this.applyCancelledFilter();
      }, () => {
        this.cancelledLoading = false;
      });
  }

  applyCancelledFilter(): void {
    const query = (this.cancelledSearch || '').toLowerCase().trim();
    if (!query) {
      this.cancelledWOs = [...this.cancelledBackup];
      return;
    }
    this.cancelledWOs = this.cancelledBackup.filter((row) =>
      JSON.stringify(row || {}).toLowerCase().includes(query)
    );
  }

  /** Send a cancelled work order back into the main Generate WO log. */
  proceedCancelledWO(row: any): void {
    if (!row) {
      return;
    }
    if (!confirm('Move this work order back to Generate WO?')) {
      return;
    }
    const payload = {
      id: row.id,
      workorder_no: row.workorder_no
    };
    this.service.post('marketing/po.php?type=proceed_cancelled_wo', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response && response.status === 'success') {
          alert('Work order moved back to Generate WO');
          this.loadCancelledWOs();
          this.getPendingWOs(true);
        } else {
          alert((response && response.message) || 'An error occurred, please try again');
        }
      });
  }

    searchText: string = '';
pendingpoBackup: any[] = [];  
  applyFilter() {
  const query = this.searchText.toLowerCase();

  this.Worders = this.pendingpoBackup.filter(po =>
    JSON.stringify(po).toLowerCase().includes(query)
  );
  this.buildForecastGroups();
  this.syncSelectAllState();
}

  buildForecastGroups(): void {
    const map = new Map<string, WorkOrder[]>();
    for (const wo of this.Worders || []) {
      // Combi SFG children share Fo_code/doc_no; fallback to child order_no
      const key = (
        wo['forecast_group_key'] ||
        wo['Fo_code'] ||
        wo['doc_no'] ||
        wo.order_no ||
        'UNKNOWN'
      ).toString();
      if (!map.has(key)) {
        map.set(key, []);
      }
      map.get(key)!.push(wo);
    }

    this.forecastGroups = Array.from(map.entries())
      .sort((a, b) => a[0].localeCompare(b[0], undefined, { numeric: true, sensitivity: 'base' }))
      .map(([order_no, workOrders]) => {
        const first = workOrders[0];
        const productCodes = [...new Set(
          workOrders.map((w) => (w['product_code'] || '').toString()).filter((c) => c && c !== '-')
        )];
        const childOrders = [...new Set(
          workOrders.map((w) => (w.order_no || '').toString()).filter(Boolean)
        )];
        const group: ForecastGroup = {
          order_no: childOrders.length === 1 ? childOrders[0] : order_no,
          groupKey: order_no,
          collapsed: this.collapsedForecastKeys.has(order_no),
          workOrders,
          planMonth: first['planMonth'],
          billing_type: this.formatBillingType(first),
          mainGroupName: first['mainGroupName'],
          productCodes,
          productSummary: productCodes.join(', '),
          bg_color: first['bg_color'],
          monthGroups: this.buildMonthGroups(workOrders),
          selectedCount: workOrders.filter((w) => w.selected).length,
          allSelected: workOrders.length > 0 && workOrders.every((w) => w.selected),
          canPlanCount: workOrders.filter((w) => this.isCanPlanStatus(w.status)).length,
          cannotPlanCount: workOrders.filter((w) => this.isCannotPlanStatus(w.status)).length,
          totalCount: workOrders.length,
          hasCombiChild: workOrders.some((w) => !!w['is_combi_child']),
        };
        return group;
      });
  }

  /** i-button modal rows — returned ready from API (plan_materials). */
  getProductMaterialRows(wo: WorkOrder | null): ProductMaterialRow[] {
    return Array.isArray(wo?.plan_materials) ? wo.plan_materials : [];
  }

  getPlanRawMaterialRows(wo: WorkOrder | null): ProductMaterialRow[] {
    return this.getProductMaterialRows(wo).filter((row) => this.isRmMaterialRow(row));
  }

  getPlanPackingMaterialRows(wo: WorkOrder | null): ProductMaterialRow[] {
    return this.getProductMaterialRows(wo).filter((row) => !this.isRmMaterialRow(row));
  }

  hasPlanMaterials(wo: WorkOrder | null): boolean {
    return this.getProductMaterialRows(wo).length > 0;
  }

  isRmMaterialRow(row: ProductMaterialRow): boolean {
    if (row.mat_category === 'PM') {
      return false;
    }
    if (row.mat_category === 'RM') {
      return true;
    }
    const type = (row.material_type || '').toLowerCase();
    return !type.includes('packing');
  }

  materialTypeTextClass(row: ProductMaterialRow): string {
    return this.isRmMaterialRow(row) ? 'gw-mat-type-text--rm' : 'gw-mat-type-text--pm';
  }

  private normalizeGeneratedWoRow(wo: WorkOrder): WorkOrder {
    const normalized: WorkOrder = {
      ...wo,
      plan_materials: Array.isArray(wo.plan_materials)
        ? wo.plan_materials.map((row) => ({ ...row }))
        : [],
      deductions: Array.isArray(wo.deductions)
        ? wo.deductions.map((d) => ({ ...d }))
        : [],
    };
    // Fallback if API still returns Work Order Processed without CAN_PLAN / CANNOT_PLAN.
    const status = String(normalized.status || '').trim();
    const classified = status === 'CAN_PLAN' || status === 'CAN_PLAN_MC_QTY_USED' || status === 'CANNOT_PLAN';
    if (!classified) {
      const canPlanned = normalized['can_planned'] === true || normalized['can_planned'] === 1 || normalized['can_planned'] === '1';
      const bomMissing = !!normalized['bom_missing'];
      normalized.status = canPlanned && !bomMissing ? 'CAN_PLAN' : 'CANNOT_PLAN';
    }
    return normalized;
  }

  isCombiChild(row: any): boolean {
    return !!row?.is_combi_child;
  }

  /** Group a forecast's work orders month-wise (splits become separate month blocks). */
  private buildMonthGroups(workOrders: WorkOrder[]): MonthGroup[] {
    const map = new Map<string, WorkOrder[]>();
    for (const wo of workOrders || []) {
      const key = this.formatPlanMonth(wo['planMonth']);
      if (!map.has(key)) {
        map.set(key, []);
      }
      map.get(key)!.push(wo);
    }
    return Array.from(map.entries())
      .sort((a, b) => this.monthSortValue(a[0]) - this.monthSortValue(b[0]))
      .map(([key, wos]) => ({
        key,
        label: this.planMonthLabel(wos[0]['planMonth']),
        workOrders: wos,
      }));
  }

  private monthSortValue(key: string): number {
    const m = (key || '').match(/^(\d{2})-(\d{4})$/);
    if (m) {
      return parseInt(m[2], 10) * 100 + parseInt(m[1], 10);
    }
    return Number.MAX_SAFE_INTEGER;
  }

  /** Human-friendly month label e.g. "Jul 2026". */
  planMonthLabel(value: any): string {
    const formatted = this.formatPlanMonth(value);
    const m = formatted.match(/^(\d{2})-(\d{4})$/);
    if (m) {
      const names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const idx = parseInt(m[1], 10) - 1;
      if (idx >= 0 && idx < 12) {
        return `${names[idx]} ${m[2]}`;
      }
    }
    return formatted;
  }

  toggleForecastCollapse(group: ForecastGroup): void {
    group.collapsed = !group.collapsed;
    const key = group.groupKey || group.order_no;
    if (group.collapsed) {
      this.collapsedForecastKeys.add(key);
    } else {
      this.collapsedForecastKeys.delete(key);
    }
  }

  toggleForecastSelect(group: ForecastGroup, checked: boolean): void {
    if (checked) {
      const lock = this.selectionLock || this.homogeneousGroupLock(group);
      if (!lock) {
        return;
      }
      this.selectionLock = lock;
      for (const wo of group.workOrders) {
        wo.selected = this.matchesSelectionLock(wo, lock);
      }
    } else {
      for (const wo of group.workOrders) {
        wo.selected = false;
      }
    }
    this.syncSelectAllState();
  }

  refreshForecastGroupSelection(): void {
    for (const group of this.forecastGroups) {
      const selectable = this.selectableWorkOrders(group.workOrders);
      group.selectedCount = group.workOrders.filter((w) => w.selected).length;
      group.allSelected = selectable.length > 0 && selectable.every((w) => w.selected);
    }
  }

  isCannotPlanStatus(status?: string): boolean {
    return status === 'CANNOT_PLAN';
  }

  isCanPlanStatus(status?: string): boolean {
    return status === 'CAN_PLAN' || status === 'CAN_PLAN_MC_QTY_USED';
  }

  /** First selected WO type locks further selection to that type only. */
  selectionLock: 'can_plan' | 'cannot_plan' | null = null;

  woSelectionType(wo: WorkOrder): 'can_plan' | 'cannot_plan' | null {
    if (this.isCanPlanStatus(wo?.status)) {
      return 'can_plan';
    }
    if (this.isCannotPlanStatus(wo?.status)) {
      return 'cannot_plan';
    }
    return null;
  }

  matchesSelectionLock(wo: WorkOrder, lock: 'can_plan' | 'cannot_plan' | null = this.selectionLock): boolean {
    if (!lock) {
      return false;
    }
    return this.woSelectionType(wo) === lock;
  }

  canSelectWorkOrder(wo: WorkOrder): boolean {
    if (!this.woSelectionType(wo)) {
      return false;
    }
    return !this.selectionLock || this.matchesSelectionLock(wo);
  }

  selectionLockHint(wo: WorkOrder): string {
    if (this.canSelectWorkOrder(wo)) {
      return this.woSelectionType(wo) === 'can_plan'
        ? 'To Be Plan — Approve To Be Plan Work Orders'
        : 'To Be Not Plan — Send for Requirement Analysis';
    }
    if (this.selectionLock === 'can_plan') {
      return 'Only To Be Plan work orders can be selected now. Clear All to switch.';
    }
    if (this.selectionLock === 'cannot_plan') {
      return 'Only To Be Not Plan work orders can be selected now. Clear All to switch.';
    }
    return '';
  }

  canForecastSelectAll(group: ForecastGroup): boolean {
    if (!group?.workOrders?.length) {
      return false;
    }
    if (this.selectionLock) {
      return this.selectableWorkOrders(group.workOrders).length > 0;
    }
    return !!this.homogeneousGroupLock(group);
  }

  private homogeneousGroupLock(group: ForecastGroup): 'can_plan' | 'cannot_plan' | null {
    const types = [...new Set(
      (group?.workOrders || []).map((wo) => this.woSelectionType(wo)).filter((t) => !!t)
    )];
    return types.length === 1 ? types[0] as 'can_plan' | 'cannot_plan' : null;
  }

  private selectableWorkOrders(workOrders: WorkOrder[]): WorkOrder[] {
    return (workOrders || []).filter((wo) => this.canSelectWorkOrder(wo));
  }

  private refreshSelectionLockFromSelected(): void {
    const selected = this.collectSelectedWorkOrders();
    if (!selected.length) {
      this.selectionLock = null;
      return;
    }
    const firstType = this.woSelectionType(selected[0]);
    this.selectionLock = firstType;
    for (const wo of selected) {
      if (!this.matchesSelectionLock(wo, firstType)) {
        wo.selected = false;
      }
    }
  }

  getSelectedCannotPlanCount(): number {
    return this.collectSelectedWorkOrders().filter((wo) => this.isCannotPlanStatus(wo.status)).length;
  }

  getSelectedCanPlanCount(): number {
    return this.collectSelectedWorkOrders().filter((wo) => this.isCanPlanStatus(wo.status)).length;
  }

  formatPlanMonth(value: any): string {
    if (!value) {
      return '-';
    }
    if (typeof value === 'string' && /^\d{2}-\d{4}$/.test(value)) {
      return value;
    }
    const parsed = new Date(value);
    if (!isNaN(parsed.getTime())) {
      const month = String(parsed.getMonth() + 1).padStart(2, '0');
      return `${month}-${parsed.getFullYear()}`;
    }
    const isoMatch = String(value).match(/^(\d{4})-(\d{2})/);
    if (isoMatch) {
      return `${isoMatch[2]}-${isoMatch[1]}`;
    }
    return String(value);
  }

  formatBillingType(wo: any): string {
    const raw = wo?.billing_type ?? wo?.billingType ?? '';
    if (raw && String(raw).trim() && raw !== 'null') {
      return String(raw).trim();
    }
    const orderNo = String(wo?.order_no || '').toUpperCase();
    if (orderNo.startsWith('FO')) {
      return 'Forecast';
    }
    if (orderNo.startsWith('PO')) {
      return 'PO';
    }
    return '-';
  }

  getActionStatusLabel(status?: string): string {
    if (!status) {
      return '-';
    }
    const labels: Record<string, string> = {
      CAN_PLAN: 'To Be Plan',
      CANNOT_PLAN: 'To Be Not Plan',
      CAN_PLAN_MC_QTY_USED: 'To Be Plan (MC Used)',
    };
    return labels[status] || String(status).replace(/_/g, ' ');
  }

  getActionStatusBadgeClass(status?: string): string {
    if (status === 'CANNOT_PLAN') {
      return 'badge-danger';
    }
    if (status === 'CAN_PLAN_MC_QTY_USED') {
      return 'badge-warning';
    }
    if (status === 'CAN_PLAN') {
      return 'badge-success';
    }
    return 'badge-secondary';
  }

  // ---- Material stock detail modal ("i" button) ----
  infoWo: WorkOrder | null = null;
  infoMaterialRows: ProductMaterialRow[] = [];
  infoModalKey = '';
  infoModalOpen = false;
  infoModalLoading = false;

  openInfoModal(wo: WorkOrder, event?: Event): void {
    if (event) {
      event.stopPropagation();
    }
    if (!wo?.id) {
      alert('Work order id missing — please refresh the page.');
      return;
    }

    const woId = String(wo.id);
    const key = woId;

    this.infoWo = { ...wo };
    this.infoMaterialRows = [];
    this.infoModalKey = key;
    this.infoModalLoading = true;
    this.infoModalOpen = true;

    this.service
      .get('marketing/po.php?type=getGenerateWoPlanMaterials&wo_id=' + encodeURIComponent(woId))
      .subscribe(
        (res: any) => {
          this.infoModalLoading = false;
          if (!res || res.status !== 'success') {
            this.infoMaterialRows = [];
            alert(res?.message || res?.plan_message || 'Could not load materials for this work order.');
            return;
          }
          if (String(res.id) !== woId) {
            return;
          }
          this.infoWo = {
            ...wo,
            product_code: res.product_code || wo['product_code'],
            product_name: res.product_name || wo['product_name'],
            bfr_no: res.bfr_no || '',
            batch_size: res.batch_size ?? wo.batch_size,
            planUnit: res.planUnit || wo.planUnit,
          };
          this.infoMaterialRows = Array.isArray(res.plan_materials)
            ? res.plan_materials.map((row: ProductMaterialRow) => ({ ...row }))
            : [];
        },
        () => {
          this.infoModalLoading = false;
          this.infoMaterialRows = [];
          alert('Failed to load materials. Check connection and try again.');
        }
      );
  }

  closeInfo(): void {
    this.infoModalOpen = false;
    this.infoWo = null;
    this.infoMaterialRows = [];
    this.infoModalKey = '';
    this.infoModalLoading = false;
  }

  onInfoModalOpenChange(open: boolean): void {
    if (!open) {
      this.closeInfo();
    }
  }

  trackInfoMaterialRow(_index: number, row: ProductMaterialRow): string {
    return `${row.material_code || ''}-${row.mat_category || ''}-${_index}`;
  }

  trackWorkOrderRow(_index: number, wo: WorkOrder): string {
    return String(wo?.id ?? wo?.workorder_no ?? _index);
  }

  getActionStatusTooltip(status?: string, wo?: WorkOrder): string {
    const detail = String(wo?.['message'] || '').trim();
    if (status === 'CAN_PLAN_MC_QTY_USED') {
      return detail || 'To be plan — mother code stock was used';
    }
    if (status === 'CANNOT_PLAN') {
      if (wo?.['bom_missing']) {
        return detail || 'Missing BOM — to be not plan';
      }
      return detail || 'Material shortage — to be not plan';
    }
    if (status === 'CAN_PLAN') {
      return detail || 'Sufficient stock — to be plan';
    }
    return detail;
  }

  packingTypeDisplay(wo: any): string {
    const raw = String(wo?.packing_type || wo?.packingStyle || '').trim();
    const unit = String(wo?.packingUnit || '').trim();
    if (!raw && !unit) {
      return '-';
    }
    if (raw === '-') {
      return '-';
    }
    if (raw && unit && !raw.toLowerCase().includes(unit.toLowerCase())) {
      return `${raw} ${unit}`.trim();
    }
    return raw || unit;
  }

  getPendingWOs(skipAutoSend = false) {
    this.loading = true;
    this.service.get("marketing/po.php?type=Get_Processed_Generated_wo")
      .subscribe((res: WorkOrder[]) => {
        this.pendingpoBackup = (Array.isArray(res) ? res : []).map((wo) => this.normalizeGeneratedWoRow(wo));
        this.applyFilter();
        this.loading = false;
      }, err => {
        console.error(err);
        this.loading = false;
      });
  }

  /** Plannable statuses that can be approved from Generate WO. */
  private isCanPlanForwardStatus(status?: string): boolean {
    return this.isCanPlanStatus(status);
  }

  /** Slim payload for analysis APIs — avoid posting full stock matrices. */
  private buildAnalysisPayload(
    workOrders: WorkOrder[],
    action: 'approve_can_plan' | 'send_requirement_analysis'
  ): { action: string; order_no: string[]; Worders: any[] } {
    const orderNos = [...new Set(workOrders.map((wo) => wo.order_no).filter(Boolean))] as string[];
    const Worders = workOrders.map((wo) => ({
      id: wo.id,
      workorder_no: wo.workorder_no,
      order_no: wo.order_no,
      doc_no: wo['doc_no'] || '',
      product_code: wo['product_code'],
      status: wo.status,
      bom_missing: !!wo['bom_missing'],
      plan_message: wo['plan_message'] || '',
      deductions: (wo.deductions || []).map((d) => ({
        workorder_no: d.workorder_no || wo.workorder_no,
        material_code: d.material_code,
        material_name: d.material_name,
        unit: d.unit,
        type: d.type || 'RM',
        deducted_from_RM: Number(d.deducted_from_RM) || 0,
        deducted_from_MC: Number(d.deducted_from_MC) || 0,
        deducted_from_Bulk: Number(d.deducted_from_Bulk) || 0,
        shortage: Number(d.shortage) || 0,
        status1: d.status1,
      })),
    }));
    return { action, order_no: orderNos, Worders };
  }

  private woHasShortage(wo: WorkOrder): boolean {
    return (wo.deductions || []).some((d) => (Number(d.shortage) || 0) > 0);
  }

  private submitWorkOrdersForAnalysis(
    workOrders: WorkOrder[],
    options: {
      silent?: boolean;
      refreshOnSuccess?: boolean;
      successMessage?: string;
      action: 'approve_can_plan' | 'send_requirement_analysis';
    }
  ): void {
    if (this.autoSendInProgress) {
      return;
    }
    if (!workOrders.length) {
      if (!options.silent) {
        alert('Please select at least one work order to send for requirement planning.');
      }
      return;
    }

    const missingIds = workOrders.filter((wo) => !wo.id || !wo.workorder_no);
    if (missingIds.length) {
      if (!options.silent) {
        alert('Selected work orders are missing id / workorder number. Please refresh and try again.');
      }
      return;
    }

    const orderNos = [...new Set(workOrders.map((wo) => wo.order_no).filter(Boolean))];
    if (orderNos.length === 0) {
      if (!options.silent) {
        alert('Selected work orders are missing order numbers. Please refresh and try again.');
      }
      return;
    }

    if (options.action === 'approve_can_plan') {
      const bad = workOrders.filter(
        (wo) => !this.isCanPlanStatus(wo.status) || this.woHasShortage(wo) || !!wo['bom_missing']
      );
      if (bad.length) {
        if (!options.silent) {
          alert(
            'Only To Be Plan work orders with no shortage (and resolved BOM) can be approved. Refresh stock and try again.'
          );
        }
        return;
      }
    } else {
      const bad = workOrders.filter((wo) => !this.isCannotPlanStatus(wo.status));
      if (bad.length) {
        if (!options.silent) {
          alert('Only To Be Not Plan work orders can be sent for Requirement Analysis.');
        }
        return;
      }
    }

    const payload = this.buildAnalysisPayload(workOrders, options.action);

    this.autoSendInProgress = true;
    this.service
      .post(`marketing/po.php?type=update_Wo_SENDfORaNALYSIS`, JSON.stringify(payload))
      .subscribe({
        next: (response: any) => {
          this.autoSendInProgress = false;
          if (response?.status === 'success') {
            if (!options.silent) {
              alert(options.successMessage || 'Work orders processed successfully');
              this.isView = false;
              this.clearAllSelections();
            }
            if (options.refreshOnSuccess) {
              this.getPendingWOs(true);
            } else {
              this.getPendingWOs();
            }
          } else if (!options.silent) {
            alert(response?.message || 'An error has occurred, please try again');
          }
        },
        error: (err) => {
          this.autoSendInProgress = false;
          console.error('Error sending work orders for analysis:', err);
          if (!options.silent) {
            const msg = err?.error?.message || err?.message || 'An error has occurred, please try again';
            alert(msg);
          }
        },
      });
  }

  allVisibleSelected = false;
  selectedVisibleCount = 0;

  collectSelectedWorkOrders(): WorkOrder[] {
    return this.pendingpoBackup.filter(wo => wo.selected);
  }

  toggleSelectAll(checked: boolean): void {
    if (checked) {
      if (!this.selectionLock) {
        return;
      }
      for (const wo of this.Worders || []) {
        wo.selected = this.matchesSelectionLock(wo);
      }
    } else {
      for (const wo of this.Worders || []) {
        wo.selected = false;
      }
      this.selectionLock = null;
    }
    this.syncSelectAllState();
  }

  onRowSelectionChange(wo?: WorkOrder): void {
    if (wo?.selected) {
      const type = this.woSelectionType(wo);
      if (!type) {
        wo.selected = false;
      } else if (!this.selectionLock) {
        this.selectionLock = type;
      } else if (!this.matchesSelectionLock(wo)) {
        wo.selected = false;
      }
    }
    this.syncSelectAllState();
  }

  private syncSelectAllState(): void {
    this.refreshSelectionLockFromSelected();
    const rows = this.Worders || [];
    this.selectedVisibleCount = rows.filter((wo) => wo.selected).length;
    const selectable = this.selectableWorkOrders(rows);
    this.allVisibleSelected = selectable.length > 0 && selectable.every((wo) => wo.selected);
    this.refreshForecastGroupSelection();
  }

  clearAllSelections() {
    for (const wo of this.pendingpoBackup) {
      wo.selected = false;
    }
    this.syncSelectAllState();
  }

  sendForRequirementAnalysis(): void {
    if (this.autoSendInProgress) {
      return;
    }
    const selected = this.collectSelectedWorkOrders();
    if (!selected.length) {
      alert('Please select at least one work order to send for Requirement Analysis.');
      return;
    }
    if (this.selectionLock && this.selectionLock !== 'cannot_plan') {
      alert('Selection is locked to To Be Plan. Clear All, then select To Be Not Plan rows.');
      return;
    }

    const invalid = selected.filter((wo) => !this.isCannotPlanStatus(wo.status));
    if (invalid.length) {
      alert('Only "To Be Not Plan" work orders can be sent for Requirement Analysis. Use "Approve To Be Plan Work Orders" for to be plan items.');
      return;
    }

    const alreadySent = selected.filter((wo) => wo['send_for_analysis_by']);
    if (alreadySent.length) {
      alert('Some selected work orders were already sent for analysis.');
      return;
    }

    if (!confirm(`Send ${selected.length} To Be Not Plan work order(s) for Requirement Analysis?`)) {
      return;
    }

    this.submitWorkOrdersForAnalysis(selected, {
      action: 'send_requirement_analysis',
      successMessage: 'Selected work orders sent for Requirement Analysis successfully',
    });
  }

  approveCanPlanWorkOrders(): void {
    if (this.autoSendInProgress) {
      return;
    }
    const selected = this.collectSelectedWorkOrders();
    if (!selected.length) {
      alert('Please select at least one To Be Plan work order to approve.');
      return;
    }
    if (this.selectionLock && this.selectionLock !== 'can_plan') {
      alert('Selection is locked to To Be Not Plan. Clear All, then select To Be Plan rows.');
      return;
    }

    const invalid = selected.filter((wo) => !this.isCanPlanStatus(wo.status));
    if (invalid.length) {
      alert('Only "To Be Plan" work orders can be approved. To Be Not Plan items must be sent for Requirement Analysis.');
      return;
    }

    const withShortage = selected.filter((wo) => this.woHasShortage(wo) || !!wo['bom_missing']);
    if (withShortage.length) {
      alert('Some selected work orders still have shortage or missing BOM. Refresh and approve only To Be Plan rows.');
      return;
    }

    const alreadySent = selected.filter((wo) => wo['send_for_analysis_by']);
    if (alreadySent.length) {
      alert('Some selected work orders were already sent.');
      return;
    }

    if (!confirm(`Approve ${selected.length} To Be Plan work order(s)? They will move to the Can Plan planning queue.`)) {
      return;
    }

    this.submitWorkOrdersForAnalysis(selected, {
      action: 'approve_can_plan',
      successMessage: 'To Be Plan work orders approved successfully',
    });
  }

  SENDfORaNALYSIS() {
    this.sendForRequirementAnalysis();
  }

}
