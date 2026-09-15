import {
  ChangeDetectionStrategy,
  ChangeDetectorRef,
  Component,
  NgZone,
  OnDestroy,
  OnInit,
} from '@angular/core';
import { Router } from '@angular/router';
import { Subject, of } from 'rxjs';
import { catchError, debounceTime, takeUntil, timeout } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-wowise',
  templateUrl: './wowise.component.html',
  styleUrls: ['./wowise.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class WowiseComponent implements OnInit, OnDestroy {
  isConci = false;
  private destroy$ = new Subject<void>();
  private search$ = new Subject<string>();

  private static readonly PROCESS_CHUNK_SIZE = 8;
  private static readonly REQUEST_TIMEOUT_MS = 120000;
  /** Orders shown (card-level cap) */
  displayOrderLimit = 15;
  /** Material rows per order before “Show more” */
  private static readonly INITIAL_MAT_ROWS = 60;
  private static readonly MAT_ROWS_STEP = 60;
  loading = false;
  finalDeductions: any[] = [];
  pendingpo: any[] = [];
  /** Cached slice – avoid getters (fewer CD cycles) */
  pendingpoDisplay: any[] = [];
  searchText = '';
  pendingpoBackup: any[] = [];

  /** Shared MRP material availability popup. */
  availModalOpen = false;
  availMaterialCode = '';
  availMaterialName = '';
  availMaterialType = '';
  availMaterialUom = '';
  availRequiredQty: number | null = null;

  openMaterialAvailability(mat: any, event?: Event): void {
    if (event) {
      event.stopPropagation();
      event.preventDefault();
    }
    if (!mat?.material_code) {
      return;
    }
    this.availMaterialCode = String(mat.material_code);
    this.availMaterialName = String(mat.material_name || '');
    this.availMaterialType = String(
      mat.material_type || (this.isRmMaterialRow(mat) ? 'Raw Material' : 'Packing Material') || ''
    );
    this.availMaterialUom = String(mat.unit || mat.Matunit || '');
    const req = Number(mat.required_qty ?? mat.rm_shortage ?? 0);
    this.availRequiredQty = isFinite(req) && req > 0 ? req : null;
    this.availModalOpen = true;
    this.cdr.markForCheck();
  }
  pendingpoBackup1: any[] = [];

  autoConsolidated: any[] = [];

  isView = false;
  selectedWo: any = null;

  indentProcessing = false;
  allConsolidatedSelected = false;
  selectedConsolidatedCount = 0;

  /** Material codes whose indent has already been raised (blocks a second indent in either mode). */
  private raisedMaterialCodes = new Set<string>();

  /** Virtual scroll row height (allows wrapped material / text columns) */
  readonly virtualScrollRowPx = 64;
  readonly virtualScrollRowPxCompact = 56;

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef,
    private ngZone: NgZone,
    private router: Router
  ) {}

  private num(v: any): number {
    const n = Number(v);
    return isNaN(n) ? 0 : n;
  }

  /** Never show a negative quantity. */
  private clamp0(v: any): number {
    return Math.max(0, this.num(v));
  }

  ngOnInit(): void {
    this.setupSearchDebounce();
    this.getPendingWOs();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    if (this.loading) {
      this.loading = false;
      this.cdr.markForCheck();
    }
  }

  private refreshPendingpoDisplay(): void {
    const list = this.pendingpo || [];
    this.pendingpoDisplay =
      list.length <= this.displayOrderLimit
        ? list
        : list.slice(0, this.displayOrderLimit);
    this.cdr.markForCheck();
  }

  get hasMoreOrders(): boolean {
    return (this.pendingpo?.length ?? 0) > this.displayOrderLimit;
  }

  get totalOrdersCount(): number {
    return this.pendingpo?.length ?? 0;
  }

  showMoreOrders(): void {
    this.displayOrderLimit = Math.min(
      this.displayOrderLimit + 15,
      this.pendingpo?.length ?? 0
    );
    this.refreshPendingpoDisplay();
  }

  /** Hard cap to avoid freezing the tab */
  showMoreOrdersLarge(): void {
    this.displayOrderLimit = Math.min(
      this.displayOrderLimit + 50,
      Math.min(this.pendingpo?.length ?? 0, 120)
    );
    this.refreshPendingpoDisplay();
  }

  private setupSearchDebounce(): void {
    this.search$
      .pipe(debounceTime(300), takeUntil(this.destroy$))
      .subscribe((query) => {
        if (this.isConci) {
          this.applyFilter1Internal(query);
        } else {
          this.applyFilterInternal(query);
        }
        this.cdr.markForCheck();
      });
  }

  applyFilter(): void {
    this.search$.next(this.searchText);
  }

  applyFilter1(): void {
    this.search$.next(this.searchText);
  }

  private applyFilterInternal(query: string): void {
    if (!Array.isArray(this.pendingpoBackup)) {
      return;
    }
    const q = (query || '').toLowerCase().trim();
    if (!q) {
      this.pendingpo = [...this.pendingpoBackup];
    } else {
      this.pendingpo = this.pendingpoBackup.filter((po) =>
        (po._searchText || '').includes(q)
      );
    }
    this.refreshPendingpoDisplay();
  }

  private applyFilter1Internal(query: string): void {
    if (!Array.isArray(this.pendingpoBackup1)) {
      return;
    }
    const q = (query || '').toLowerCase().trim();
    if (!q) {
      this.autoConsolidated = [...this.pendingpoBackup1];
    } else {
      this.autoConsolidated = this.pendingpoBackup1.filter((po) =>
        this.consolidatedSearchText(po).includes(q)
      );
    }
    this.refreshConsolidatedViews();
  }

  private consolidatedSearchText(po: any): string {
    return [
      po.material_code,
      po.material_name,
      po.required_qty,
    ]
      .filter((v) => v != null && v !== '')
      .join(' ')
      .toLowerCase();
  }

  private refreshConsolidatedViews(): void {
    this.cdr.markForCheck();
  }

  getPendingWOs(): void {
    this.loading = true;
    this.pendingpo = [];
    this.pendingpoBackup = [];
    this.pendingpoDisplay = [];
    this.displayOrderLimit = 15;
    this.cdr.markForCheck();

    this.service
      .get('marketing/po.php?type=getShortagesPlannedWO_wise')
      .pipe(
        timeout(WowiseComponent.REQUEST_TIMEOUT_MS),
        takeUntil(this.destroy$),
        catchError((err) => {
          this.ngZone.run(() => {
            this.loading = false;
            this.pendingpo = [];
            this.pendingpoBackup = [];
            this.pendingpoDisplay = [];
            this.cdr.markForCheck();
          });
          if (err?.name === 'TimeoutError' || err?.message?.includes('timeout')) {
            if (typeof alertify !== 'undefined') {
              alertify.error('Request timed out. Please try again.');
            } else {
              alert('Request timed out. Please try again.');
            }
          } else {
            console.error('Error fetching pending Materials', err);
            if (typeof alertify !== 'undefined') {
              alertify.error('Failed to load work orders. Please try again.');
            } else {
              alert('Failed to load work orders. Please try again.');
            }
          }
          return of(null);
        })
      )
      .subscribe((response: any) => {
        if (response == null) {
          return;
        }

        const data = Array.isArray(response) ? response : response?.data ?? [];
        const orders = Array.isArray(data) ? data : [];

        if (orders.length === 0) {
          this.ngZone.run(() => {
            this.pendingpo = [];
            this.pendingpoBackup = [];
            this.refreshPendingpoDisplay();
            this.loading = false;
            this.cdr.markForCheck();
          });
          return;
        }

        const normalized: any[] = [];
        let index = 0;
        const chunkSize = WowiseComponent.PROCESS_CHUNK_SIZE;
        const self = this;

        const processChunk = (): void => {
          try {
            const end = Math.min(index + chunkSize, orders.length);
            for (let i = index; i < end; i++) {
              const ord = self.normalizeOrder(orders[i]);
              if ((ord.WorkOrders?.length ?? 0) > 0) {
                normalized.push(ord);
              }
            }
            index = end;
            if (index < orders.length) {
              setTimeout(processChunk, 0);
            } else {
              self.ngZone.run(() => {
                self.pendingpo = normalized;
                self.pendingpoBackup = [...normalized];
                self.displayOrderLimit = 15;
                self.refreshPendingpoDisplay();
                self.loading = false;
                self.cdr.markForCheck();
              });
            }
          } catch (e) {
            console.error('Error processing orders', e);
            self.ngZone.run(() => {
              self.pendingpo = normalized.length ? normalized : [];
              self.pendingpoBackup = [...self.pendingpo];
              self.refreshPendingpoDisplay();
              self.loading = false;
              self.cdr.markForCheck();
            });
          }
        };
        setTimeout(processChunk, 0);
      });
  }

  private buildSearchIndex(order: any): string {
    const parts: string[] = [];
    const push = (v: any) => {
      if (v != null && String(v).trim() !== '') {
        parts.push(String(v));
      }
    };
    push(order.order_no);
    push(order.client_name);
    push(order.client_code);
    push(order.groupcode);
    push(order.product_name);
    push(order.product_code);
    for (const wo of order.WorkOrders || []) {
      push(wo.workorder_no);
      push(wo.bulk_code);
      for (const m of wo.Materials || []) {
        push(m.material_code);
        push(m.material_name);
      }
    }
    return parts.join(' ').toLowerCase();
  }

  private dedupeWoMaterials(materials: any[]): any[] {
    const byCode = new Map<string, any>();
    for (const m of materials) {
      const code = String(m?.material_code ?? '').trim();
      if (!code) {
        continue;
      }
      if (!byCode.has(code)) {
        byCode.set(code, { ...m });
      }
    }
    return Array.from(byCode.values());
  }

  private normalizeWorkOrder(wo: any, orderProductCode = ''): any {
    const raw = Array.isArray(wo?.Materials)
      ? wo.Materials.map((mat: any) => ({
          ...mat,
          Matunit: mat.unit ?? mat.Matunit,
          _indentRaised: !!mat.shortage_queued,
        }))
      : [];
    let materials = this.dedupeWoMaterials(raw).filter(
      (m) => this.clamp0(m.rm_shortage) > 0
    );
    for (const m of materials) {
      if (m.shortage_queued) {
        m._indentRaised = true;
        this.raisedMaterialCodes.add(String(m.material_code));
      }
    }
    const pc = orderProductCode || (wo?.product_code || '').toString().trim();
    if (pc) {
      materials = materials.filter((m) => {
        const matPc = (m.product_code || wo.product_code || pc).toString().trim();
        return matPc === pc;
      });
    }
    const cap = Math.min(WowiseComponent.INITIAL_MAT_ROWS, materials.length);
    return {
      ...wo,
      Materials: materials,
      _materialsAll: materials,
      _displayMaterials: materials.slice(0, cap),
      _matDisplayCap: cap,
      _hasMoreMaterials: materials.length > cap,
      _expanded: false,
      _selected: false,
    };
  }

  /** Accordion toggle for a work order. */
  toggleWo(wo: any, ev?: Event): void {
    ev?.stopPropagation?.();
    wo._expanded = !wo._expanded;
    this.cdr.markForCheck();
  }

  onWoSelectionChange(): void {
    this.syncGlobalWoSelection();
    this.cdr.markForCheck();
  }

  /** Number of work orders currently checked across all orders. */
  get selectedWoCount(): number {
    let count = 0;
    for (const order of this.pendingpo || []) {
      for (const wo of order?.WorkOrders || []) {
        if (wo?._selected) {
          count++;
        }
      }
    }
    return count;
  }

  private normalizeOrder(order: any): any {
    if (!order) {
      return { WorkOrders: [], _totalMaterialLines: 0, _searchText: '' };
    }
    const productCode = (order?.product_code || '').toString().trim();
    const workOrders = Array.isArray(order.WorkOrders)
      ? order.WorkOrders
          .map((wo: any) => this.normalizeWorkOrder(wo, productCode))
          .filter((wo: any) => (wo._materialsAll?.length ?? 0) > 0)
      : [];

    let totalMaterialLines = 0;
    for (const wo of workOrders) {
      totalMaterialLines += wo._materialsAll?.length ?? 0;
    }

    const base = {
      ...order,
      WorkOrders: workOrders,
      _totalMaterialLines: totalMaterialLines,
    };
    base._searchText = this.buildSearchIndex(base);
    base._orderExpanded = true;
    base._allWoSelected = false;
    return base;
  }

  toggleOrderCollapse(order: any, ev?: Event): void {
    ev?.stopPropagation?.();
    order._orderExpanded = !order._orderExpanded;
    this.cdr.markForCheck();
  }

  isOrderAllWoSelected(order: any): boolean {
    const wos = order?.WorkOrders || [];
    return wos.length > 0 && wos.every((wo: any) => wo._selected);
  }

  toggleOrderSelect(order: any, checked: boolean, ev?: Event): void {
    ev?.stopPropagation?.();
    for (const wo of order?.WorkOrders || []) {
      wo._selected = checked;
    }
    order._allWoSelected = checked;
    this.syncGlobalWoSelection();
    this.cdr.markForCheck();
  }

  toggleWoSelectFromTableHeader(wo: any, checked: boolean, ev?: Event): void {
    ev?.stopPropagation?.();
    wo._selected = checked;
    this.syncGlobalWoSelection();
    this.cdr.markForCheck();
  }

  private syncGlobalWoSelection(): void {
    for (const order of this.pendingpo || []) {
      order._allWoSelected = this.isOrderAllWoSelected(order);
    }
  }

  showMoreMaterialsForWo(wo: any, ev?: Event): void {
    ev?.stopPropagation?.();
    const all = wo._materialsAll || [];
    if (!all.length) {
      return;
    }
    const step = WowiseComponent.MAT_ROWS_STEP;
    const next = Math.min((wo._matDisplayCap || 0) + step, all.length);
    wo._matDisplayCap = next;
    wo._displayMaterials = all.slice(0, next);
    wo._hasMoreMaterials = next < all.length;
    this.cdr.markForCheck();
  }

  trackByOrder(_: number, order: any): string {
    return order?.order_no ?? order?.client_code ?? String(_);
  }

  trackByWo(_: number, wo: any): string {
    const womId = wo?.wom_id ?? wo?.id;
    if (womId != null && String(womId).trim() !== '') {
      return `wom-${womId}`;
    }
    return `${wo?.workorder_no ?? ''}|${wo?.product_code ?? ''}|${_}`;
  }

  trackByMaterial(index: number, mat: any): string {
    return `${mat?.workorder_no ?? ''}|${mat?.material_code ?? ''}|${index}`;
  }

  isRmMaterialRow(row: any): boolean {
    if (row?.mat_category === 'PM' || row?.mat_type === 'PM') {
      return false;
    }
    if (row?.mat_category === 'RM' || row?.mat_type === 'RM') {
      return true;
    }
    const type = (row?.material_type || '').toLowerCase();
    return !type.includes('packing');
  }

  materialTypeTextClass(row: any): string {
    return this.isRmMaterialRow(row) ? 'wowise-mat-type--rm' : 'wowise-mat-type--pm';
  }

  backToWorkOrders(): void {
    this.revertConsolidation();
  }

  revertConsolidation(): void {
    this.isConci = false;
    this.autoConsolidated = [];
    this.pendingpoBackup1 = [];
    this.allConsolidatedSelected = false;
    this.selectedConsolidatedCount = 0;
    this.raisedMaterialCodes.clear();
    this.cdr.markForCheck();
  }

  private flattenMaterialsFromOrders(orders: any[], selectedOnly = false): any[] {
    const flat: any[] = [];
    for (const order of orders) {
      for (const wo of order?.WorkOrders || []) {
        if (selectedOnly && !wo?._selected) {
          continue;
        }
        for (const mat of wo?.Materials || []) {
          if (this.clamp0(mat.rm_shortage) <= 0) {
            continue;
          }
          flat.push({
            ...mat,
            workorder_no: mat.workorder_no || wo.workorder_no,
            bulk_code: wo.bulk_code,
            order_no: mat.order_no || order.order_no,
            client_code: mat.client_code || order.client_code,
            client_name: mat.client_name || order.client_name,
            product_code: mat.product_code || order.product_code,
            product_name: mat.product_name || order.product_name,
            plan_month:
              mat.plan_month ||
              mat.planMonth ||
              wo.plan_month ||
              wo.planMonth ||
              order.plan_month ||
              order.planMonth,
          });
        }
      }
    }
    return flat;
  }

  consolidateMaterials(): void {
    this.consolidateFromFlat(this.flattenMaterialsFromOrders(this.pendingpo || []));
  }

  /** Consolidate only the materials of the checked work orders. */
  consolidateSelectedMaterials(): void {
    const flat = this.flattenMaterialsFromOrders(this.pendingpo || [], true);
    if (!flat.length) {
      if (typeof alertify !== 'undefined') {
        alertify.error('Please select at least one work order to consolidate.');
      } else {
        alert('Please select at least one work order to consolidate.');
      }
      return;
    }
    this.consolidateFromFlat(flat);
  }

  /** Shared consolidation: group flattened materials by code and sum required qty. */
  private consolidateFromFlat(flat: any[]): void {
    const groups: Record<string, any[]> = {};
    for (const mat of flat) {
      if (this.clamp0(mat.rm_shortage) <= 0) {
        continue;
      }
      const code = mat.material_code ?? '';
      if (!groups[code]) {
        groups[code] = [];
      }
      groups[code].push(mat);
    }

    this.autoConsolidated = Object.values(groups).map((arr: any[]) => {
      const last = arr[arr.length - 1];
      const totalRequired = arr.reduce(
        (sum, m) => sum + this.num(m.required_qty),
        0
      );
      // Own-code stockbook pool once — do not sum in-hand across WOs.
      const rmInHand = this.clamp0(
        arr[0]?.stockbook_qty ?? arr[0]?.available_stock_qty
      );
      const allQueued =
        arr.length > 0 &&
        arr.every((s) => s.shortage_queued || s._indentRaised);
      const row: any = {
        ...last,
        required_qty: totalRequired,
        available_stock_qty: rmInHand,
        stockbook_qty: rmInHand,
        _sources: arr,
        _selected: false,
        _indentRaised: allQueued,
      };
      this.decorateOwnStock(row);
      return row;
    });
    this.autoConsolidated = this.sortShortageFirst(
      this.autoConsolidated.filter((r) => r._hasShort)
    );

    this.pendingpoBackup1 = [...this.autoConsolidated];
    this.isConci = true;
    this.syncConsolidatedSelection();
    this.refreshConsolidatedViews();
  }

  /**
   * Own material stock only (no mother-code).
   * In hand = stockbook net (view − booked − hold, + Cannot Plan add-back).
   * Shortage = required − in hand.
   */
  private decorateOwnStock(mat: any): void {
    const req = this.clamp0(mat.required_qty);
    const rmInHand = this.clamp0(mat.stockbook_qty ?? mat.available_stock_qty);
    const rmUsed = Math.min(rmInHand, req);
    const rmBalance = rmInHand - rmUsed;
    const rmShort = req - rmUsed;

    mat._req = req;
    mat._rmInHand = rmInHand;
    mat._rmBalance = rmBalance;
    mat._rmShort = rmShort;
    mat._totalShort = rmShort;
    mat._hasShort = rmShort > 0;
    this.decorateMeta(mat);
  }

  /** Plan Month / Forecast No / Client Group / Product columns (aggregated over sources). */
  private decorateMeta(mat: any): void {
    const src = mat._sources && mat._sources.length ? mat._sources : [mat];
    mat._planMonths = this.distinctJoin(src, ['plan_month', 'planMonth'], (v) =>
      this.formatPlanMonthShort(v)
    );
    mat._forecastNos = this.distinctJoin(src, ['order_no', 'forecast_no']);
    mat._groupCodes = this.distinctJoin(src, ['client_code']);
    mat._clientGroups = this.distinctJoin(src, ['client_name', 'client_code']);
    mat._products = this.distinctJoin(src, ['product_name', 'product_code']);
  }

  private distinctJoin(
    sources: any[],
    keys: string[],
    formatter?: (v: any) => string
  ): string {
    const set = new Set<string>();
    for (const s of sources || []) {
      let raw: any = '';
      for (const k of keys) {
        if (s && s[k] != null && String(s[k]).trim() !== '') {
          raw = s[k];
          break;
        }
      }
      if (raw === '') {
        continue;
      }
      const str = (formatter ? formatter(raw) : String(raw)).trim();
      if (str && str !== '-') {
        set.add(str);
      }
    }
    return set.size ? Array.from(set).join(', ') : '-';
  }

  private formatPlanMonthShort(value: any): string {
    if (!value) {
      return '';
    }
    if (typeof value === 'string' && /^\d{2}-\d{4}$/.test(value)) {
      return value;
    }
    const parsed = new Date(value);
    if (!isNaN(parsed.getTime())) {
      const month = String(parsed.getMonth() + 1).padStart(2, '0');
      return `${month}-${parsed.getFullYear()}`;
    }
    const iso = String(value).match(/^(\d{4})-(\d{2})/);
    if (iso) {
      return `${iso[2]}-${iso[1]}`;
    }
    return String(value);
  }

  /** Materials with shortages float to the top (largest shortage first). */
  private sortShortageFirst(list: any[]): any[] {
    return list.sort((a, b) => {
      const sa = a._hasShort ? 1 : 0;
      const sb = b._hasShort ? 1 : 0;
      if (sa !== sb) {
        return sb - sa;
      }
      const diff = this.num(b._totalShort) - this.num(a._totalShort);
      if (diff !== 0) {
        return diff;
      }
      return String(a.material_name || '').localeCompare(String(b.material_name || ''));
    });
  }

  toggleConsolidatedSelectAll(checked: boolean): void {
    for (const mat of this.autoConsolidated || []) {
      mat._selected = checked && !mat._indentRaised;
    }
    this.syncConsolidatedSelection();
    this.cdr.markForCheck();
  }

  onConsolidatedRowSelect(mat: any): void {
    if (mat?._indentRaised) {
      mat._selected = false;
    }
    this.syncConsolidatedSelection();
    this.cdr.markForCheck();
  }

  private syncConsolidatedSelection(): void {
    const list = this.autoConsolidated || [];
    this.selectedConsolidatedCount = list.filter((m) => m._selected).length;
    const selectable = list.filter((m) => !m._indentRaised);
    this.allConsolidatedSelected =
      selectable.length > 0 && selectable.every((m) => m._selected);
  }

  /** Mark proceeded WO+material lines and clear consolidated selection. */
  private markIndentRaised(
    lines: Array<{ material_code: string; workorder_no: string }>
  ): void {
    if (!lines.length) {
      return;
    }
    const keys = new Set(
      lines.map((l) => `${l.workorder_no}|${l.material_code}`)
    );
    for (const line of lines) {
      this.raisedMaterialCodes.add(String(line.material_code));
    }
    for (const order of this.pendingpo || []) {
      for (const wo of order?.WorkOrders || []) {
        for (const mat of wo?.Materials || []) {
          const key = `${mat.workorder_no}|${mat.material_code}`;
          if (keys.has(key)) {
            mat.shortage_queued = true;
            mat._indentRaised = true;
          }
        }
      }
    }
    for (const mat of this.autoConsolidated || []) {
      const sources = mat._sources?.length ? mat._sources : [mat];
      const allQueued = sources.every(
        (s: any) =>
          s.shortage_queued ||
          s._indentRaised ||
          keys.has(`${s.workorder_no}|${s.material_code}`)
      );
      if (allQueued) {
        mat._indentRaised = true;
        mat._selected = false;
      }
    }
    this.syncConsolidatedSelection();
  }

  private buildIndentPayloads(selectedMats: any[]): any[] {
    const payloads: any[] = [];
    for (const mat of selectedMats) {
      const sources: any[] = mat._sources?.length ? mat._sources : [mat];
      const byClient: Record<string, any[]> = {};
      for (const src of sources) {
        const cc = (src.client_code || 'NA').toString();
        if (!byClient[cc]) {
          byClient[cc] = [];
        }
        byClient[cc].push(src);
      }
      for (const clientCode of Object.keys(byClient)) {
        const rows = byClient[clientCode];
        const clientName = rows[0]?.client_name || '';
        const rmTotal = rows.reduce((s, r) => s + Number(r.rm_shortage || 0), 0);
        if (rmTotal > 0) {
          payloads.push(this.buildSingleIndentPayload(rows, clientCode, clientName));
        }
      }
    }
    return payloads;
  }

  private buildSingleIndentPayload(
    rows: any[],
    clientCode: string,
    clientName: string
  ): any {
    const first = rows[0];
    const wos = rows
      .map((row) => ({
        workorder_no: row.workorder_no,
        work_order_no: row.workorder_no,
        workorder_ID: row.wo_deduction_id,
        material_code: row.material_code,
        material_name: row.material_name,
        Matunit: row.unit || row.Matunit || 'KG',
        rm_shortage: Number(row.rm_shortage || 0),
        used_from_RM: Number(row.used_from_RM ?? 0),
        client_code: clientCode,
        client_name: clientName,
        order_no: row.order_no,
        product_code: row.product_code,
      }))
      .filter((wo) => Number(wo.rm_shortage) > 0);

    const totalRm = wos.reduce((s, w) => s + Number(w.rm_shortage || 0), 0);

    return {
      client_code: clientCode,
      client_name: clientName,
      material_code: first.material_code,
      material_name: first.material_name,
      material_type: first.material_type || 'Raw Material',
      material_subtype: first.material_subtype || '',
      Matunit: first.unit || first.Matunit || 'KG',
      indent_type: first.indent_type || 'Client Code',
      category: 'Client',
      total_rm_shortage: totalRm,
      wos,
      required_for: wos.map((wo) => ({
        reqQty: wo.rm_shortage,
        Matunit: wo.Matunit,
        client_name: clientName,
        order_no: wo.order_no,
        product_code: wo.product_code,
        work_order_no: wo.workorder_no,
        Client_code_Indent: wo.rm_shortage,
        material_code: wo.material_code,
      })),
    };
  }

  /** Common action: proceed with all selected consolidated materials (skip already-raised). */
  proceedSelectedIndents(): void {
    const selected = (this.autoConsolidated || []).filter(
      (m) => m._selected && !m._indentRaised
    );
    this.processIndents(selected);
  }

  /** Proceed with every not-yet-raised consolidated material that has a shortage. */
  proceedAllIndents(): void {
    const all = (this.autoConsolidated || []).filter(
      (m) => m._hasShort && !m._indentRaised
    );
    if (!all.length) {
      this.notifyError('No pending materials with shortage to proceed.');
      return;
    }
    this.processIndents(all);
  }

  /** Count of shortage materials still available to raise (excludes already-raised). */
  get shortageMaterialCount(): number {
    return (this.autoConsolidated || []).filter(
      (m) => m._hasShort && !m._indentRaised
    ).length;
  }

  /** Whether an indent has already been raised for this material (either mode). */
  isIndentRaised(mat: any): boolean {
    return !!mat?._indentRaised || this.raisedMaterialCodes.has(String(mat?.material_code));
  }

  /**
   * Export the current consolidated view to a styled Excel file (.xls) that mirrors
   * the on-screen table — dark header, bordered cells, shortage values in red.
   * Uses an HTML table (which Excel renders with full styling) so no extra library
   * beyond what's already bundled is required.
   */
  downloadConsolidatedExcel(): void {
    const rows = this.autoConsolidated || [];
    if (!rows.length) {
      this.notifyError('No consolidated materials to export.');
      return;
    }

    const headers = [
      'Material', 'Code', 'Plan Month', 'Forecast No', 'Group Code', 'Client Group',
      'Product', 'Required Qty', 'Stock in hand', 'Stock balance', 'Shortage',
    ];

    const esc = (v: any): string =>
      String(v == null ? '' : v)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const headStyle =
      'background-color:#2c3e50;color:#ffffff;font-weight:bold;border:1px solid #1b2733;' +
      'padding:6px 10px;text-align:left;font-family:Calibri,Arial,sans-serif;font-size:11pt;white-space:nowrap;';
    const cellBase =
      'border:1px solid #cfcfcf;padding:5px 10px;text-align:left;' +
      'font-family:Calibri,Arial,sans-serif;font-size:10.5pt;white-space:nowrap;';

    let body = '<table border="1" style="border-collapse:collapse;">';
    body += '<thead><tr>';
    for (const h of headers) {
      body += `<th style="${headStyle}">${esc(h)}</th>`;
    }
    body += '</tr></thead><tbody>';

    for (const r of rows) {
      const rowBg = r._hasShort ? 'background-color:#fff5f5;' : '';
      const cells: Array<{ v: any; short?: boolean }> = [
        { v: r.material_name || '-' }, { v: r.material_code || '-' },
        { v: r._planMonths }, { v: r._forecastNos }, { v: r._groupCodes },
        { v: r._clientGroups }, { v: r._products },
        { v: r._req }, { v: r._rmInHand }, { v: r._rmBalance },
        { v: r._rmShort, short: r._rmShort > 0 },
      ];

      body += '<tr>';
      for (const c of cells) {
        const shortStyle = c.short ? 'color:#c81e1e;font-weight:bold;' : '';
        body += `<td style="${cellBase}${rowBg}${shortStyle}">${esc(c.v)}</td>`;
      }
      body += '</tr>';
    }
    body += '</tbody></table>';

    const title = 'Consolidated Materials (Own stock)';
    const fullHtml =
      '<html xmlns:o="urn:schemas-microsoft-com:office:office" ' +
      'xmlns:x="urn:schemas-microsoft-com:office:excel">' +
      '<head><meta charset="UTF-8"><style>br{mso-data-placement:same-cell;}</style></head>' +
      `<body><h3 style="font-family:Calibri,Arial,sans-serif;">${title}</h3>${body}</body></html>`;

    const blob = new Blob([fullHtml], {
      type: 'application/vnd.ms-excel;charset=utf-8;',
    });
    const url = URL.createObjectURL(blob);
    const stamp = new Date().toISOString().slice(0, 10);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = `Consolidated_Materials_${stamp}.xls`;
    document.body.appendChild(anchor);
    anchor.click();
    document.body.removeChild(anchor);
    URL.revokeObjectURL(url);
  }

  /** Per-row action: proceed with a single material (resolve to its MC twin for a full payload). */
  proceedIndentForMaterial(mat: any): void {
    this.processIndents([mat]);
  }

  /** Backwards-compatible alias. */
  sendIndentsForProcessing(): void {
    this.proceedSelectedIndents();
  }

  /**
   * Forward the selected shortage materials to the Shortages screen.
   * Marks matching WO_deductions rows on the server (shortage_queue_by) so only
   * materials explicitly proceeded from WO-wise appear on Shortages.
   */
  private processIndents(mats: any[]): void {
    const selected = (mats || []).filter(
      (m) => m && m._hasShort && !m._indentRaised
    );
    if (!selected.length) {
      this.notifyError('No shortage material available to proceed.');
      return;
    }

    if (
      !this.confirmAction(
        `Proceed ${selected.length} shortage material(s) to Shortages for indent processing?`
      )
    ) {
      return;
    }

    const lines: Array<{ material_code: string; workorder_no: string }> = [];
    const seen = new Set<string>();
    for (const mat of selected) {
      const sources = mat._sources?.length ? mat._sources : [mat];
      for (const src of sources) {
        const material_code = String(
          src.material_code || mat.material_code || ''
        ).trim();
        const workorder_no = String(
          src.workorder_no || mat.workorder_no || ''
        ).trim();
        if (!material_code || !workorder_no) {
          continue;
        }
        const key = `${workorder_no}|${material_code}`;
        if (seen.has(key)) {
          continue;
        }
        seen.add(key);
        lines.push({ material_code, workorder_no });
      }
    }
    if (!lines.length) {
      this.notifyError('No work order lines found for the selected materials.');
      return;
    }

    this.indentProcessing = true;
    this.cdr.markForCheck();
    this.service
      .post(
        'marketing/po.php?type=proceedWoShortageMaterials',
        JSON.stringify({ lines })
      )
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          this.indentProcessing = false;
          if (response?.status === 'success') {
            this.markIndentRaised(lines);
            this.notifySuccess(
              response.message ||
                'Shortage material(s) forwarded to Shortages for indent processing.'
            );
            this.goToShortages();
          } else {
            this.notifyError(
              response?.message ||
                'Failed to forward materials to Shortages. Please try again.'
            );
            this.cdr.markForCheck();
          }
        },
        error: () => {
          this.indentProcessing = false;
          this.notifyError('Request failed. Please try again.');
          this.cdr.markForCheck();
        },
      });
  }

  private goToShortages(): void {
    this.router.navigate(['/planning/Shortages']);
  }

  SENDfORaNALYSIS(): void {
    const temp = { Worders: this.pendingpo || [] };
    this.service
      .post(
        'marketing/po.php?type=update_Wo_SENDfORaNALYSIS',
        JSON.stringify(temp)
      )
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          if (response?.status === 'success') {
            if (typeof alertify !== 'undefined') {
              alertify.success('Selected Orders Approved Successfully');
            } else {
              alert('Selected Orders Approved Successfully');
            }
            this.getPendingWOs();
          } else {
            if (typeof alertify !== 'undefined') {
              alertify.error('An error has occurred, please try again');
            } else {
              alert('An error has occurred, please try again');
            }
          }
        },
        error: () => {
          if (typeof alertify !== 'undefined') {
            alertify.error('Request failed. Please try again.');
          } else {
            alert('Request failed. Please try again.');
          }
        },
      });
  }

  View(wo: any): void {
    this.isView = true;
    this.selectedWo = wo;
    const deductions = this.selectedWo?.Deductions;
    if (Array.isArray(deductions)) {
      for (let i = 0; i < deductions.length; i++) {
        const d = deductions[i];
        d['requiredQty'] =
          Number(d['deducted_from_MC'] || 0) +
          Number(d['deducted_from_RM'] || 0) +
          Number(d['shortage'] || 0);
      }
    }
    this.cdr.markForCheck();
  }

  private notifySuccess(message: string): void {
    if (typeof alertify !== 'undefined') {
      alertify.success(message);
    } else {
      alert(message);
    }
  }

  private notifyError(message: string): void {
    if (typeof alertify !== 'undefined') {
      alertify.error(message);
    } else {
      alert(message);
    }
  }

  private confirmAction(message: string): boolean {
    return window.confirm(message);
  }

  cancelWo(wo: any, ev?: Event): void {
    ev?.stopPropagation?.();
    const woNo = wo?.workorder_no;
    if (!woNo) {
      return;
    }
    if (
      !this.confirmAction(
        `Cancel work order ${woNo}? It will be sent back to Generate WO for re-processing.`
      )
    ) {
      return;
    }
    wo._cancelling = true;
    this.cdr.markForCheck();
    this.service
      .post(
        'marketing/po.php?type=cancel_planned_wo_wise',
        JSON.stringify({ workorder_no: woNo })
      )
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          wo._cancelling = false;
          if (response?.status === 'success') {
            this.notifySuccess(
              response?.message ||
                `Work order ${woNo} sent back to Generate WO`
            );
            this.getPendingWOs();
          } else {
            this.notifyError(
              response?.message || 'Failed to cancel work order. Please try again.'
            );
            this.cdr.markForCheck();
          }
        },
        error: () => {
          wo._cancelling = false;
          this.notifyError('Request failed. Please try again.');
          this.cdr.markForCheck();
        },
      });
  }

  cancelOrder(order: any, ev?: Event): void {
    ev?.stopPropagation?.();
    const orderNo = order?.order_no;
    if (!orderNo) {
      return;
    }
    const woCount = order?.WorkOrders?.length ?? 0;
    if (
      !this.confirmAction(
        `Cancel order ${orderNo} and all ${woCount} work order(s)? It will be sent back to Marketing PO Processing.`
      )
    ) {
      return;
    }
    order._cancelling = true;
    this.cdr.markForCheck();
    this.service
      .post(
        'marketing/po.php?type=cancel_planned_order_wise',
        JSON.stringify({ order_no: orderNo })
      )
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          order._cancelling = false;
          if (response?.status === 'success') {
            const count = response?.cancelled_count ?? woCount;
            this.notifySuccess(
              response?.message ||
                `Order ${orderNo} sent back to PO Processing (${count} work order(s) removed)`
            );
            this.getPendingWOs();
          } else {
            this.notifyError(
              response?.message || 'Failed to cancel order. Please try again.'
            );
            this.cdr.markForCheck();
          }
        },
        error: () => {
          order._cancelling = false;
          this.notifyError('Request failed. Please try again.');
          this.cdr.markForCheck();
        },
      });
  }

  /** Open the cancellation log view. */
  openCancellationLog(): void {
    this.router.navigate(['/planning/Wowisecancellog']);
  }

  RaiseInd(data: any, category: string): void {
    const temp = data;
    temp['category'] = category;

    const materials = temp?.Materials || [];
    temp['required_for'] = materials.map((wo: any) => ({
      Matunit: wo['Matunit'],
      work_order_no: wo['required_for'],
      Client_code_Indent: wo['Client_code_Indent'],
      material_code: wo['material_code'],
    }));

    this.service
      .post(
        'purchase/indent.php?type=savePlanningIndentStore',
        JSON.stringify(temp)
      )
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          if (response?.status === 'success') {
            if (typeof alertify !== 'undefined') {
              alertify.success('Indent records saved successfully');
            }
            this.getPendingWOs();
          } else {
            if (typeof alertify !== 'undefined') {
              alertify.error('Failed: An error occurred, please try again!');
            }
          }
        },
        error: () => {
          if (typeof alertify !== 'undefined') {
            alertify.error('Request failed. Please try again.');
          }
        },
      });
  }
}
