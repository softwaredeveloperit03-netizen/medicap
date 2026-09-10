import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-processing',
  templateUrl: './processing.component.html',
  styleUrls: ['./processing.component.css']
})
export class ProcessingComponent implements OnInit {

  pendingpo: any[] = [];
  loading = false;
searchText: string = '';
filterDateFrom = '';
filterDateTo = '';
pendingpoBackup: any[] = [];   // original data
 
  // Extra Batch Modal
  extraBatchModalOpen = false;
  currentPO: any = null;
  currentProduct: any = null;
  currentBatchSizes: any[] = [];
  selectedBatchSize: number = 0;
  extraBatchQty: number = 0;

  isView = false;
  selectedResult: any[] = [];

  // Cancelled orders log
  showCancelled = false;
  cancelledOrders: any[] = [];
  cancelledBackup: any[] = [];
  cancelledLoading = false;
  cancelledSearch = '';

  newFoEntryType: 'FO Service' | 'FO Product' = 'FO Service';

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingPOs();
  }

  /** Open the Cancelled Orders log view. */
  openCancelledOrders(): void {
    this.showCancelled = true;
    this.loadCancelledOrders();
  }

  /** Return to the main processing log. */
  closeCancelledOrders(): void {
    this.showCancelled = false;
  }

  /** Fetch all orders currently in the Cancelled state. */
  loadCancelledOrders(): void {
    this.cancelledLoading = true;
    this.service.get('marketing/po_entry_flow.php?type=getCancelledProcessingPOs')
      .subscribe((response: any) => {
        this.cancelledBackup = Array.isArray(response) ? response : [];
        this.cancelledOrders = [...this.cancelledBackup];
        this.cancelledLoading = false;
        this.applyCancelledFilter();
      }, () => {
        this.cancelledLoading = false;
      });
  }

  applyCancelledFilter(): void {
    const query = (this.cancelledSearch || '').toLowerCase().trim();
    if (!query) {
      this.cancelledOrders = [...this.cancelledBackup];
      return;
    }
    this.cancelledOrders = this.cancelledBackup.filter((row) =>
      JSON.stringify(row || {}).toLowerCase().includes(query)
    );
  }

  /** Send a cancelled order back into the main processing log. */
  proceedCancelledOrder(row: any): void {
    if (!row) {
      return;
    }
    if (!confirm('Move this order back to PO Processing?')) {
      return;
    }
    const payload = {
      order_material_id: row.order_material_id,
      order_no: row.order_no
    };
    this.service.post('marketing/po_entry_flow.php?type=proceed_cancelled_order', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response && response.status === 'success') {
          alert('Order moved back to PO Processing');
          this.loadCancelledOrders();
          this.getPendingPOs();
        } else {
          alert((response && response.message) || 'An error occurred, please try again');
        }
      });
  }

  limitBatchChange(row) { 


  }


  private getPoDateMs(value: any): number | null {
    if (value == null || value === '') {
      return null;
    }
    const d = new Date(value);
    return isNaN(d.getTime()) ? null : d.getTime();
  }

  /** Newest PO/FO first (id, then entry date). */
  private comparePoNewestFirst(a: any, b: any): number {
    const idA = Number(a?.id) || 0;
    const idB = Number(b?.id) || 0;
    if (idB !== idA) {
      return idB - idA;
    }
    const dateA = this.getPoDateMs(a?.entry_date || a?.po_date) || 0;
    const dateB = this.getPoDateMs(b?.entry_date || b?.po_date) || 0;
    return dateB - dateA;
  }

  clearDateFilter(): void {
    this.filterDateFrom = '';
    this.filterDateTo = '';
    this.applyFilter();
  }

  /** Flatten entire PO row (header + nested products/batches) for free-text search. */
  private buildSearchHaystack(record: any): string {
    const parts: string[] = [];

    const pushDate = (value: any): void => {
      if (value == null || value === '') {
        return;
      }
      const d = new Date(value);
      if (!isNaN(d.getTime())) {
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        parts.push(`${dd}-${mm}-${yyyy}`);
        parts.push(`${dd}/${mm}/${yyyy}`);
        parts.push(`${mm}-${yyyy}`);
      }
    };

    const walk = (value: any): void => {
      if (value == null || value === '') {
        return;
      }
      if (value instanceof Date) {
        parts.push(value.toISOString());
        parts.push(value.toLocaleDateString('en-GB'));
        return;
      }
      if (Array.isArray(value)) {
        value.forEach((item) => walk(item));
        return;
      }
      if (typeof value === 'object') {
        Object.values(value).forEach((v) => walk(v));
        return;
      }
      parts.push(String(value));
    };

    walk(record);

    // Formatted date variants (match what is shown in the grid)
    pushDate(record?.po_date);
    pushDate(record?.planMonth);
    pushDate(record?.deliveryDate);

    // Derived values shown in the grid
    parts.push(String(this.getRowWorkorderQty(record)));
    parts.push(String(this.getRowBalance(record)));

    return parts.join(' ').toLowerCase();
  }

  applyFilter(): void {
    const query = this.searchText.toLowerCase().trim();
    const fromMs = this.filterDateFrom
      ? new Date(this.filterDateFrom + 'T00:00:00').getTime()
      : null;
    const toMs = this.filterDateTo
      ? new Date(this.filterDateTo + 'T23:59:59').getTime()
      : null;

    this.pendingpo = this.pendingpoBackup
      .filter((po) => {
        const wantService = this.newFoEntryType === 'FO Service';
        if (this.isFoServiceRow(po) !== wantService) {
          return false;
        }
        if (query && !this.buildSearchHaystack(po).includes(query)) {
          return false;
        }
        const poMs = this.getPoDateMs(po.po_date);
        if (fromMs != null && (poMs == null || poMs < fromMs)) {
          return false;
        }
        if (toMs != null && (poMs == null || poMs > toMs)) {
          return false;
        }
        return true;
      })
      .sort((a, b) => this.comparePoNewestFirst(a, b));
  }
  /** Fetch entered PO/FO rows waiting in Processing (status Entered / Hold). */
  getPendingPOs() {
    this.loading = true;
    this.service.get('marketing/po_entry_flow.php?type=getEnteredPOsForProcessing')
      .subscribe((response: any) => {
        const rows = Array.isArray(response) ? response : [];
        this.pendingpo = rows.map((po: any) => this.normalizeEnteredPoRow(po))
          .sort((a: any, b: any) => this.comparePoNewestFirst(a, b));

        this.loading = false;
        this.pendingpoBackup = [...this.pendingpo];
        this.applyFilter();
      }, () => {
        this.loading = false;
        this.pendingpo = [];
        this.pendingpoBackup = [];
      });
  }

  private normalizeEnteredPoRow(po: any): any {
    const row = { ...po };
    row.doc_no = row.id ?? row.doc_no;
    row.plan_qty = row.plan_qty ?? row.line_plan_qty ?? row.planQty ?? 0;
    row.planQty = row.plan_qty;
    row.order_no = row.order_no || row.po_no || '';
    row.mainGroupName = row.mainGroupName || row.LglNm || row.TrdNm || '-';
    row.fo_entry_type = row.fo_entry_type || row.client_type || '';
    row.isFoService = this.isFoServiceRow(row);
    row.batches = Array.isArray(row.batches) ? row.batches : [];
    row.leftover = Number(row.leftover) || 0;
    row.excess = Number(row.excess) || 0;
    row.batch_formula = row.batch_formula || [];
    return row;
  }

  isFoServiceRow(row: any): boolean {
    const entryType = (row?.fo_entry_type || row?.client_type || '').toString().trim();
    if (entryType === 'FO Service') {
      return true;
    }
    if (entryType === 'FO Product') {
      return false;
    }
    const productCode = (row?.product_code || row?.parent_product_code || '').toString().trim();
    if (productCode) {
      return false;
    }
    const planQty = Number(row?.plan_qty ?? row?.planQty ?? 0) || 0;
    if (planQty > 0) {
      return false;
    }
    return (row?.serviceCategory || '').toString().trim() !== '';
  }

  get showProductLogColumns(): boolean {
    return this.newFoEntryType === 'FO Product';
  }

  onFoTypeFilterChange(): void {
    this.applyFilter();
  }

  getFoEntryTypeLabel(row: any): string {
    return this.isFoServiceRow(row) ? 'FO Service' : 'FO Product';
  }

  getServiceCategoryLabel(row: any): string {
    return (row?.serviceCategory || '-').toString().trim() || '-';
  }

  private normalizeProcessingRecord(po: any): any {
    this.applySavedOrCalculatedBatches(po, po.batch_formula || []);
    return po;
  }

  /** Keep DB-saved batch plan; only auto-calculate when nothing is saved yet. */
  private applySavedOrCalculatedBatches(row: any, batchFormula: any[]): void {
    if (typeof row?.batches === 'string') {
      try {
        row.batches = JSON.parse(row.batches);
      } catch {
        row.batches = [];
      }
    }
    if (Array.isArray(row?.batches) && row.batches.length > 0) {
      row.leftover = Number(row.leftover) || 0;
      row.excess = Number(row.excess) || 0;
      return;
    }
    const planQty = Number(row?.plan_qty ?? row?.planQty ?? 0);
    const batchResult = this.calculateBatchPlan(planQty, batchFormula || []);
    row.batches = batchResult.batches;
    row.leftover = batchResult.leftover;
    row.excess = batchResult.excess;
  }

  /** Generic batch plan calculation */
  calculateBatchPlan(planQty: number, batchSizes: any[]): any {
    if (!planQty || !batchSizes || batchSizes.length === 0)
      return { batches: [], leftover: planQty, excess: 0 };

    let remainingQty = planQty;
    const batches: any[] = [];

    // Sort descending by batch size
    const sortedBatches = batchSizes.sort(
      (a: any, b: any) => b.batch_formula_weight - a.batch_formula_weight
    );

    for (let batch of sortedBatches) {
      const size = parseFloat(batch.batch_formula_weight);
      const count = Math.floor(remainingQty / size);
      if (count > 0) {
        batches.push({ size, count });
        remainingQty -= count * size;
      }
    }

    const leftover = remainingQty;
    const smallestBatch = parseFloat(sortedBatches[sortedBatches.length - 1].batch_formula_weight);
    const excess = leftover > 0 ? smallestBatch - leftover : 0;

    return { batches, leftover, excess };
  }

  /** Open Extra Batch Modal */
  generateExtraBatch(po: any, product: any) {
    this.currentPO = po;
    this.currentProduct = product;
    this.currentBatchSizes = (product?.batch_formula?.length ? product.batch_formula : po.batch_formula) || [];
    this.selectedBatchSize = this.currentBatchSizes.length ? this.currentBatchSizes[0].batch_formula_weight : 0;
    this.extraBatchQty = 0;
    this.extraBatchModalOpen = true;
  }

  /** Confirm adding extra batch */
  confirmExtraBatch() {
    if (!this.selectedBatchSize || this.extraBatchQty <= 0) {
      alert("Please select valid batch size and quantity");
      return;
    }

    const batchPlanTarget = this.currentProduct
      ? (this.currentProduct.batchPlan ||= { batches: [], leftover: 0, excess: 0 })
      : (this.currentPO.batchPlan ||= { batches: [], leftover: 0, excess: 0 });

    const planUnit = this.currentProduct ? this.currentProduct.planUnit : this.currentPO.planUnit;

    // Calculate batch count and update
    const batchCountToAdd = Math.floor(this.extraBatchQty / this.selectedBatchSize);
    if (batchCountToAdd > 0) {
      const existingBatch = batchPlanTarget.batches.find(b => b.size === this.selectedBatchSize);
      if (existingBatch) {
        existingBatch.count += batchCountToAdd;
      } else {
        batchPlanTarget.batches.push({ size: this.selectedBatchSize, count: batchCountToAdd });
      }
    }

    // Update leftover/excess
    const totalAdded = batchCountToAdd * this.selectedBatchSize;
    const leftoverQty = this.extraBatchQty - totalAdded;
    batchPlanTarget.leftover = leftoverQty;
    batchPlanTarget.excess = leftoverQty > 0 ? this.selectedBatchSize - leftoverQty : 0;

    // Update plan_qty
    if (this.currentProduct) {
      this.currentProduct.planQty += totalAdded;
    } else {
      this.currentPO.plan_qty += totalAdded;
    }

    // Reset modal
    this.extraBatchModalOpen = false;
    this.currentPO = null;
    this.currentProduct = null;
    this.currentBatchSizes = [];
    this.selectedBatchSize = 0;
    this.extraBatchQty = 0;
  }

  /** Update leftover/excess dynamically on selection in modal */
  updateBatchCalculation(entity: any) {
    if (!entity.selectedBatchSize) return;

    const planQty = entity.plan_qty || entity.planQty;
    const batchSize = +entity.selectedBatchSize;

    entity.minBatches = Math.floor(planQty / batchSize);
    entity.maxBatches = Math.ceil(planQty / batchSize);

    const totalPlannedQty = batchSize * (entity.batchesToAdd || entity.minBatches);

    if (totalPlannedQty > planQty) {
      entity.leftoverExcessLabel = `${totalPlannedQty - planQty} (Excess)`;
    } else if (totalPlannedQty < planQty) {
      entity.leftoverExcessLabel = `${planQty - totalPlannedQty} (Short)`;
    } else {
      entity.leftoverExcessLabel = `0`;
    }

    const batchPlanTarget = entity.batchPlan || { batches: [], leftover: 0, excess: 0 };
    const existingBatch = batchPlanTarget.batches.find(b => b.size == batchSize);

    if (existingBatch) {
      existingBatch.count = entity.batchesToAdd;
    } else {
      batchPlanTarget.batches = [{ size: batchSize, count: entity.batchesToAdd }];
    }

    const leftoverQty = planQty - totalPlannedQty;
    batchPlanTarget.leftover = leftoverQty > 0 ? leftoverQty : 0;
    batchPlanTarget.excess = leftoverQty < 0 ? Math.abs(leftoverQty) : 0;

    entity.batchPlan = batchPlanTarget;
  }

  /** Persist batch plan on po_entry so Approval/Log can read Workorder Count. */
  private persistBatchPlan(row: any): void {
    const poId = row?.id ?? row?.po_entry_id ?? row?.doc_no;
    if (!poId) {
      return;
    }
    this.service
      .post('marketing/po_entry_flow.php?type=saveProcessingBatchPlan', {
        id: poId,
        batches: row?.batches || [],
        leftover: row?.leftover ?? 0,
        excess: row?.excess ?? 0,
      })
      .subscribe(() => {}, () => {});
  }

  /** Send to approval, cancel, or hold an entered PO/FO. */
  updatePendingPOs(status: string, data: any) {
    const statusMap: Record<string, string> = {
      'Work Order Processed': 'Send for Approval',
      'Rejected': 'Cancelled',
      'Hold': 'Hold',
    };
    const apiStatus = statusMap[status] || status;
    const poId = data?.id ?? data?.po_entry_id ?? data?.doc_no;
    if (!poId) {
      alertify.error('PO record id missing');
      return;
    }

    const remark = window.prompt('Please enter remark:');
    if (remark === null) {
      return;
    }
    if (!String(remark).trim()) {
      alertify.error('Remark is required');
      return;
    }

    if (Array.isArray(data?.batches) && data.batches.length) {
      this.persistBatchPlan(data);
    }

    const url =
      'marketing/po_entry_flow.php?type=updateProcessingPoStatus' +
      '&id=' + encodeURIComponent(String(poId)) +
      '&status=' + encodeURIComponent(apiStatus) +
      '&remark=' + encodeURIComponent(String(remark).trim());

    this.service.get(url).subscribe((response: any) => {
      if (response && response.status === 'success') {
        alertify.success(response.message || 'Updated successfully');
        this.isView = false;
        this.getPendingPOs();
      } else {
        alertify.error((response && response.message) || 'An error has occurred, please try again');
      }
    }, () => {
      alertify.error('An error has occurred, please try again');
    });
  }


  /** View PO */
  view(data: any) {
    this.selectedResult = data;
    this.isView = true;
  }

  /** Download PO document */
  downloadpo(doc_url: string) {
    if (!doc_url || doc_url === 'NA') {
      return;
    }
    const path = doc_url.includes('upload/') ? doc_url : 'upload/poentry/' + doc_url;
    const url = this.service.url + '../../' + path.replace(/^\//, '');
    window.open(url, '_blank');
  }
  
selectedProduct = [];
selectedPO = [];
remainingQty = 0;
batchModalOpen = false;
batch_formula = [];
batchRows = [];
draftBatches: any[] = [];
batchModalIsEdit = false;
planDisplayUnit = '';

  hasBatchPlan(target: any): boolean {
    return Array.isArray(target?.batches) && target.batches.length > 0;
  }

  getBatchButtonLabel(target: any): string {
    return this.hasBatchPlan(target) ? 'Change Batches' : 'Generate Batches';
  }

  /** Plan quantity for a PO-level row (plan_qty) or product row (planQty). */
  getRowPlanQty(row: any): number {
    return Number(row?.plan_qty ?? row?.planQty ?? 0) || 0;
  }

  /** Quantity actually planned through batches = plan − leftover + excess. */
  getRowWorkorderQty(row: any): number {
    const plan = this.getRowPlanQty(row);
    const leftover = Number(row?.leftover ?? 0) || 0;
    const excess = Number(row?.excess ?? 0) || 0;
    const qty = plan - leftover + excess;
    return Math.round((qty + Number.EPSILON) * 1000) / 1000;
  }

  /** Remaining balance = Plan Qty − Workorder Qty (positive = short, negative = excess). */
  getRowBalance(row: any): number {
    const balance = this.getRowPlanQty(row) - this.getRowWorkorderQty(row);
    return Math.round((balance + Number.EPSILON) * 1000) / 1000;
  }

  private getBatchTarget(): any {
    return this.selectedProduct || this.selectedPO;
  }

  private getPlanQtyForBatchTarget(target: any): number {
    if (!target) {
      return 0;
    }
    return Number(this.selectedProduct ? target.planQty : target.plan_qty) || 0;
  }

  private unitsMatch(a: string, b: string): boolean {
    const left = (a || '').trim().toLowerCase();
    const right = (b || '').trim().toLowerCase();
    if (!left || !right) {
      return true;
    }
    return left === right;
  }

  /** Only batches in the FO plan unit count toward plan qty / remaining balance. */
  private sumBatchPlannedQty(batches: any[], planUnit?: string): number {
    const pu = (planUnit || this.planDisplayUnit || '').trim();
    return (batches || []).reduce((sum, b) => {
      const batchUnit = (b?.unit || '').trim();
      if (pu && batchUnit && !this.unitsMatch(batchUnit, pu)) {
        return sum;
      }
      const size = Number(b?.size) || 0;
      const count = Number(b?.count) || 0;
      return sum + size * count;
    }, 0);
  }

  private applyBatchPlanToTarget(target: any, batches: any[]): void {
    const planQty = this.getPlanQtyForBatchTarget(target);
    const planned = this.sumBatchPlannedQty(batches);
    target.batches = batches.map((b) => ({
      size: Number(b.size),
      count: Number(b.count),
      unit: b.unit || this.planDisplayUnit,
    }));

    if (planned >= planQty) {
      const excess = planned - planQty;
      target.excess = excess > 0 ? excess : 0;
      target.leftover = 0;
    } else {
      target.leftover = planQty - planned;
      target.excess = 0;
    }
  }

  formatBatchPlanSummary(batches: any[], unit?: string): string {
    if (!batches?.length) {
      return '-';
    }
    const fallback = unit || this.planDisplayUnit || '';
    return batches
      .map((b) => {
        const u = (b?.unit || fallback).trim();
        return `${b.count} × ${b.size}${u ? ' ' + u : ''}`;
      })
      .join(', ');
  }

  private asList(response: any): any[] {
    if (Array.isArray(response)) {
      return response;
    }
    if (Array.isArray(response?.batch_formula)) {
      return response.batch_formula;
    }
    return [];
  }

  private resolvePlanUnit(po: any, product?: any): string {
    return (
      product?.planUnit ||
      product?.packingUnit ||
      po?.planUnit ||
      po?.packingUnit ||
      ''
    ).trim();
  }

  normalizeBatchFormulas(list: any[]): any[] {
    return (list || []).map((b) => ({
      ...b,
      batch_formula_weight: b.batch_formula_weight,
      batch_formula_weight_unit:
        b.batch_formula_weight_unit || b.rm_batch_size_unit || this.planDisplayUnit || 'KG',
    }));
  }

  /** Prefer batch sizes in the same unit as the FO plan qty (Nos vs Kg). */
  private filterBatchFormulasForPlanUnit(formulas: any[], planUnit: string): any[] {
    const pu = (planUnit || '').trim().toLowerCase();
    if (!pu || !formulas?.length) {
      return formulas || [];
    }
    const matched = formulas.filter((b) =>
      this.unitsMatch(b.batch_formula_weight_unit || b.rm_batch_size_unit || '', planUnit)
    );
    return matched.length ? matched : formulas;
  }

  formatBatchLabel(b: any): string {
    const size = b?.batch_formula_weight ?? '';
    const unit = b?.batch_formula_weight_unit || this.planDisplayUnit || '';
    return `${size} ${unit}`.trim();
  }

  formatQtyWithUnit(qty: any, unit?: string): string {
    const u = (unit || this.planDisplayUnit || '').trim();
    const val = qty ?? 0;
    return u ? `${val} ${u}` : `${val}`;
  }

  /** Format plan month for grid (handles yyyy-mm-dd and month-year strings). */
  formatPlanMonth(value: any): string {
    if (value == null || value === '') {
      return '-';
    }
    const d = new Date(value);
    if (!isNaN(d.getTime())) {
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const yyyy = d.getFullYear();
      return `${mm}-${yyyy}`;
    }
    return String(value);
  }

  private newBatchRow(): any {
    return {
      selectedBatch: null,
      numBatches: 0,
      planQty: 0,
      leftover: this.remainingQty,
    };
  }

  private applyBatchFormulasToModal(formulas: any[]): void {
    const normalized = this.normalizeBatchFormulas(formulas);
    this.batch_formula = this.filterBatchFormulasForPlanUnit(normalized, this.planDisplayUnit);
  }

  private loadBatchFormulasForModal(productCode: string, planUnit: string, existing: any[]): void {
    if (existing?.length) {
      this.applyBatchFormulasToModal(existing);
      this.batchModalOpen = true;
      return;
    }
    if (!productCode) {
      this.batch_formula = [];
      this.batchModalOpen = true;
      return;
    }
    this.service
      .get(
        'marketing/po.php?type=getProcessingBatchFormula&product_code=' +
          encodeURIComponent(productCode) +
          '&plan_unit=' +
          encodeURIComponent(planUnit || '') +
          '&fresh=1&all_units=1'
      )
      .subscribe(
        (response: any) => {
          this.applyBatchFormulasToModal(this.asList(response));
          this.batchModalOpen = true;
        },
        () => {
          this.batch_formula = [];
          this.batchModalOpen = true;
        }
      );
  }

  generateBatches(po: any, product?: any): void {
    if (this.isFoServiceRow(po) && !product) {
      alertify.error('Batch planning applies to FO Product entries only.');
      return;
    }
    this.selectedPO = po;
    this.selectedProduct = product || null;
    this.planDisplayUnit = this.resolvePlanUnit(po, product);

    const target = this.getBatchTarget();
    const productCode =
      product?.product_code || po?.product_code || po?.parent_product_code || '';
    const formulaList =
      product?.batch_formula?.length
        ? product.batch_formula
        : po?.batch_formula || [];

    this.batchModalIsEdit = this.hasBatchPlan(target);
    this.draftBatches = this.batchModalIsEdit
      ? JSON.parse(JSON.stringify(target.batches))
      : [];

    const planQty = this.getPlanQtyForBatchTarget(target);
  if (this.batchModalIsEdit) {
    const planned = this.sumBatchPlannedQty(this.draftBatches);
    this.remainingQty = Math.max(0, planQty - planned);
    if (planned > planQty) {
      this.remainingQty = 0;
    }
  } else {
    this.remainingQty = planQty;
  }

    this.currentRowExcessLeftover = `(${this.formatQtyWithUnit(this.remainingQty)}) Leftover`;
    this.batchRows = [this.newBatchRow()];
    this.loadBatchFormulasForModal(productCode, this.planDisplayUnit, formulaList);
  }

  commitBatchPlan(): void {
    const target = this.getBatchTarget();
    if (!target) {
      return;
    }
    if (!this.draftBatches.length) {
      alertify.error('Add at least one batch line before saving.');
      return;
    }

    this.applyBatchPlanToTarget(target, this.draftBatches);
    this.persistBatchPlan(target);
    this.batchModalOpen = false;
    alertify.success(
      this.batchModalIsEdit ? 'Batches updated in the table.' : 'Batches saved to the table.'
    );
  }

  removeDraftBatch(index: number): void {
    this.draftBatches.splice(index, 1);
    const target = this.getBatchTarget();
    const planQty = this.getPlanQtyForBatchTarget(target);
    const planned = this.sumBatchPlannedQty(this.draftBatches);
    this.remainingQty = Math.max(0, planQty - planned);
    this.currentRowExcessLeftover = `(${this.formatQtyWithUnit(this.remainingQty)}) Leftover`;
    if (this.batchRows.length) {
      this.batchRows[0].leftover = this.remainingQty;
    }
  }
// Track the current row excess/leftover
currentRowExcessLeftover: string = '';

// Calculate max possible batches when batch size selected
calculateBatch(idx: number) {
  const row = this.batchRows[idx];
  if (!row.selectedBatch) return;

  const batchUnit = row.selectedBatch.batch_formula_weight_unit || this.planDisplayUnit;
  if (!this.unitsMatch(batchUnit, this.planDisplayUnit)) {
    alertify.warning(
      `FO plan is in ${this.planDisplayUnit || 'plan unit'}. Select a batch size in the same unit.`
    );
    row.numBatches = 0;
    row.planQty = 0;
    row.leftover = this.remainingQty;
    return;
  }

  const batchSize = row.selectedBatch.batch_formula_weight;
  row.numBatches = Math.floor(this.remainingQty / batchSize);
  row.checkNumBatches = Math.floor(this.remainingQty / batchSize);
  this.updatePlanQty(idx);
}

// Update planQty and leftover/excess dynamically when numBatches is edited
updatePlanQty(idx: number) {
  const row = this.batchRows[idx];
    let plusVal = +row['checkNumBatches'] + 1;
    let minusVal = +row['checkNumBatches'] - 1;

    if(+row['numBatches']  >= plusVal){
        row['numBatches']  = plusVal;
    }
    if(+row['numBatches']  <= minusVal){
      if(+row['numBatches'] <= 0){
        row['numBatches']  = 1;
      }else{
        row['numBatches']  = minusVal;
      }
    }







  if (!row.selectedBatch || !row.numBatches) {
    row.planQty = 0;
    row.leftover = this.remainingQty;
    this.currentRowExcessLeftover = `(${this.formatQtyWithUnit(this.remainingQty)}) Leftover`;
    return;
  }

  const batchSize = Number(row.selectedBatch.batch_formula_weight);
  row.planQty = row.numBatches * batchSize;
  const unit = row.selectedBatch.batch_formula_weight_unit || this.planDisplayUnit;

  if (row.planQty > this.remainingQty) {
    row.leftover = row.planQty - this.remainingQty; // excess 
    this.currentRowExcessLeftover = `(${this.formatQtyWithUnit(row.leftover, unit)}) Excess`;
  } else {
    row.leftover = this.remainingQty - row.planQty; // leftover
    this.currentRowExcessLeftover = `(${this.formatQtyWithUnit(row.leftover, unit)}) Leftover`;
  }
  console.log('checkval'+row['checkNumBatches']);  
  console.log('ogval'+row['numBatches']);  



}

  addBatch(idx: number): void {
    const row = this.batchRows[idx];
    if (!row?.selectedBatch || !row.numBatches || row.numBatches <= 0) {
      alertify.error('Select batch size and enter number of batches.');
      return;
    }

    const batchUnit = row.selectedBatch.batch_formula_weight_unit || this.planDisplayUnit;
    if (!this.unitsMatch(batchUnit, this.planDisplayUnit)) {
      alertify.error(
        `Cannot add batch in ${batchUnit}: FO plan qty is in ${this.planDisplayUnit || 'plan unit'}.`
      );
      return;
    }

    const batchSize = Number(row.selectedBatch.batch_formula_weight);
    const numBatches = Number(row.numBatches);
    const totalPlanned = batchSize * numBatches;

    this.draftBatches.push({
      size: batchSize,
      count: numBatches,
      unit: row.selectedBatch.batch_formula_weight_unit || this.planDisplayUnit,
    });

    const target = this.getBatchTarget();
    const planQty = this.getPlanQtyForBatchTarget(target);

    if (totalPlanned >= this.remainingQty) {
      this.remainingQty = 0;
    } else {
      this.remainingQty -= totalPlanned;
    }

    this.currentRowExcessLeftover = `(${this.formatQtyWithUnit(this.remainingQty)}) Leftover`;

    const saveHint = this.batchModalIsEdit ? 'Change Batches' : 'Save Batches';
    if (this.remainingQty > 0) {
      this.batchRows[idx] = this.newBatchRow();
      alertify.success(`Batch line added. Add more or click ${saveHint} to update the table.`);
    } else {
      this.batchRows = [this.newBatchRow()];
      alertify.success(`Batch lines added. Click ${saveHint} to update the table.`);
    }
  }


}
