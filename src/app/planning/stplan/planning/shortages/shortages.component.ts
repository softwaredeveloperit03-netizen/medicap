import {
  ChangeDetectionStrategy,
  ChangeDetectorRef,
  Component,
  OnDestroy,
  OnInit,
} from '@angular/core';
import { Subject, from, of } from 'rxjs';
import {
  catchError,
  debounceTime,
  map,
  mergeMap,
  takeUntil,
  toArray,
} from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-shortages',
  templateUrl: './shortages.component.html',
  styleUrls: ['./shortages.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ShortagesComponent implements OnInit, OnDestroy {
  private readonly destroy$ = new Subject<void>();
  private readonly search$ = new Subject<string>();

  /** Full list after processing (same semantics as before filter). */
  pendingpo: any[] = [];
  /** Shallow backup for search; same object refs as pendingpo. */
  pendingpoBackup: any[] = [];
  /** Filtered list (search); `RaiseInd` / actions use objects from here or backup. */
  pendingpoFiltered: any[] = [];
  /** Paged slice of `pendingpoFiltered` for the template to limit DOM / datagrids. */
  visibleMaterials: any[] = [];

  searchText = '';
  loading = false;

  /** Calculation rules modal (header "Rules" button). */
  showRules = false;

  /** Main shortages list vs cancelled-indents tab */
  activeView: 'shortages' | 'cancelled' = 'shortages';
  cancelledIndents: any[] = [];
  cancelledIndentsBackup: any[] = [];
  cancelledIndentsFiltered: any[] = [];
  cancelledLoading = false;
  permanentCancelModalOpen = false;
  permanentCancelTarget: any = null;
  permanentCancelRemark = '';
  permanentCancelSubmitting = false;

  openRules(): void {
    this.showRules = true;
    this.cdr.markForCheck();
  }

  closeRules(): void {
    this.showRules = false;
    this.cdr.markForCheck();
  }

  setActiveView(view: 'shortages' | 'cancelled'): void {
    this.activeView = view;
    if (view === 'cancelled' && !this.cancelledIndentsBackup.length) {
      this.loadCancelledIndents();
    } else if (view === 'shortages') {
      this.loadSummary();
    }
    this.applyFilterInternal(this.searchText);
    this.cdr.markForCheck();
  }

  loadCancelledIndents(): void {
    this.cancelledLoading = true;
    this.cdr.markForCheck();
    this.service.get('purchase/indent.php?type=getReturnedPlanningIndents').subscribe({
      next: (response: any) => {
        this.cancelledIndentsBackup = Array.isArray(response) ? response : [];
        this.applyFilterInternal(this.searchText);
        this.cancelledLoading = false;
        this.cdr.markForCheck();
      },
      error: () => {
        this.cancelledIndentsBackup = [];
        this.cancelledIndentsFiltered = [];
        this.cancelledIndents = [];
        this.cancelledLoading = false;
        alertify.error('Failed to load cancelled indents.');
        this.cdr.markForCheck();
      },
    });
  }

  canProceedCancelled(row: any): boolean {
    return String(row?.indent_raw_status || '').toLowerCase() === 'returned';
  }

  canPermanentCancel(row: any): boolean {
    const st = String(row?.indent_raw_status || '').toLowerCase();
    return st === 'returned' || st === 'hold';
  }

  proceedCancelledIndent(row: any): void {
    const id = Number(row?.indent_id);
    if (!id) {
      alertify.error('Invalid indent.');
      return;
    }
    row.proceedLoading = true;
    this.cdr.markForCheck();
    this.service
      .post(
        'purchase/indent.php?type=proceedReturnedPlanningIndent',
        JSON.stringify({ indent_ids: [id] })
      )
      .subscribe({
        next: (response: any) => {
          row.proceedLoading = false;
          if (response?.status === 'success') {
            alertify.success(response.message || 'Indent sent back to Confirm Indent.');
            this.loadCancelledIndents();
          } else {
            alertify.error(response?.message || 'Proceed failed.');
          }
          this.cdr.markForCheck();
        },
        error: () => {
          row.proceedLoading = false;
          alertify.error('Proceed failed.');
          this.cdr.markForCheck();
        },
      });
  }

  openPermanentCancelModal(row: any): void {
    this.permanentCancelTarget = row;
    this.permanentCancelRemark = row?.planning_indent_remark || '';
    this.permanentCancelModalOpen = true;
    this.cdr.markForCheck();
  }

  closePermanentCancelModal(): void {
    this.permanentCancelModalOpen = false;
    this.permanentCancelTarget = null;
    this.permanentCancelRemark = '';
    this.permanentCancelSubmitting = false;
    this.cdr.markForCheck();
  }

  submitPermanentCancel(): void {
    const row = this.permanentCancelTarget;
    const id = Number(row?.indent_id);
    if (!id) {
      alertify.error('Invalid indent.');
      return;
    }
    this.permanentCancelSubmitting = true;
    this.cdr.markForCheck();
    this.service
      .post(
        'purchase/indent.php?type=permanentCancelPlanningIndent',
        JSON.stringify({
          indent_ids: [id],
          remark: (this.permanentCancelRemark || '').trim(),
        })
      )
      .subscribe({
        next: (response: any) => {
          this.permanentCancelSubmitting = false;
          if (response?.status === 'success') {
            alertify.success(response.message || 'Indent permanently cancelled.');
            this.closePermanentCancelModal();
            this.loadCancelledIndents();
          } else {
            alertify.error(response?.message || 'Permanent cancel failed.');
          }
          this.cdr.markForCheck();
        },
        error: () => {
          this.permanentCancelSubmitting = false;
          alertify.error('Permanent cancel failed.');
          this.cdr.markForCheck();
        },
      });
  }

  printRules(): void {
    window.print();
  }

  /** Initial cap after load or new search; increased via Show more. */
  materialsDisplayCap = 30;
  private static readonly MATERIALS_CAP_STEP = 25;

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.search$
      .pipe(debounceTime(300), takeUntil(this.destroy$))
      .subscribe((q) => {
        this.applyFilterInternal(q);
        this.cdr.markForCheck();
      });
    this.loadSummary();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  applyFilter(): void {
    this.search$.next(this.searchText ?? '');
  }

  private applyFilterInternal(raw: string): void {
    const q = (raw ?? '').trim().toLowerCase();
    if (this.activeView === 'cancelled') {
      if (!q) {
        this.cancelledIndentsFiltered = this.cancelledIndentsBackup;
      } else {
        this.cancelledIndentsFiltered = this.cancelledIndentsBackup.filter((row) =>
          JSON.stringify(row).toLowerCase().includes(q)
        );
      }
      this.cancelledIndents = this.cancelledIndentsFiltered;
      return;
    }
    if (!q) {
      this.pendingpoFiltered = this.pendingpoBackup;
    } else {
      this.pendingpoFiltered = this.pendingpoBackup.filter((m) =>
        (m._searchText || '').includes(q)
      );
    }
    this.pendingpo = this.pendingpoFiltered;
    this.materialsDisplayCap = 30;
    this.refreshVisibleMaterials();
  }

  private refreshVisibleMaterials(): void {
    const list = this.pendingpoFiltered;
    this.visibleMaterials =
      list.length <= this.materialsDisplayCap
        ? list
        : list.slice(0, this.materialsDisplayCap);
  }

  get hasMoreMaterials(): boolean {
    return (
      (this.pendingpoFiltered?.length ?? 0) > (this.visibleMaterials?.length ?? 0)
    );
  }

  get totalMaterialsFiltered(): number {
    return this.pendingpoFiltered?.length ?? 0;
  }

  showMoreMaterials(): void {
    this.materialsDisplayCap = Math.min(
      this.materialsDisplayCap + ShortagesComponent.MATERIALS_CAP_STEP,
      this.pendingpoFiltered.length
    );
    this.refreshVisibleMaterials();
    this.cdr.markForCheck();
  }

  /** Fast filter string; avoids JSON.stringify on every row. */
  private buildMaterialSearchText(mat: any): string {
    const parts: string[] = [];
    const add = (v: unknown) => {
      if (v != null && v !== '') {
        parts.push(String(v).toLowerCase());
      }
    };
    add(mat.material_code);
    add(mat.material_name);
    add(mat.MotherCode);
    add(mat.mat_type);
    add(mat.client_name);
    add(mat.client_code);
    add(mat.wo_count);
    add(mat.total_shortage);
    add(mat.uom);
    add(mat.Matunit);
    add(mat.forecast_nos);
    add(mat.fo_codes);
    add(mat.workorder_nos);
    add(mat.indent_no);
    add(mat.indent_request_no);
    const clients = mat.Client || [];
    for (const c of clients) {
      add(c.client_code);
      add(c.client_name);
      const wos = c.wos || [];
      for (const wo of wos) {
        add(wo.product_name);
        add(wo.product_code);
        add(wo.bulk_code);
        add(wo.order_no);
        add(wo.Fo_code);
        add(wo.workorder_no);
      }
    }
    return parts.join(' ');
  }

  trackByMaterial(_index: number, mat: any): string {
    return (
      (mat?.material_code ?? '') +
      '\0' +
      (mat?.material_name ?? '') +
      '\0' +
      _index
    );
  }

  trackByClient(_index: number, client: any): string {
    return (client?.client_code ?? '') + '\0' + _index;
  }

  trackByWo(index: number, wo: any): string {
    return (
      (wo?.workorder_no ?? '') +
      '\0' +
      (wo?.order_no ?? '') +
      '\0' +
      index
    );
  }


  /** Display/calc quantities are never negative on Shortages. */
  private nonNeg(value: unknown): number {
    const n = Number(value);
    return Number.isFinite(n) ? Math.max(0, n) : 0;
  }

  private sanitizeWorkOrder(wo: any): void {
    const fields = [
      'batch_plan_qty',
      'used_from_RM',
      'used_from_MC',
      'rm_remaining',
      'rm_shortage',
      'mc_remaining',
      'mc_shortage',
      'clmc_Inhand',
      'clmc_Remaining',
      'clmc_Shortage',
      'shortage',
      'deducted_from_RM',
      'deducted_from_MC',
      'openIndent',
      'Client_code_Indent',
      'Mother_code_Indent',
      'live_rm_available',
      'live_mc_available',
      'live_rm_shortage',
      'live_mc_shortage',
      'live_shortage',
    ];
    for (const key of fields) {
      if (wo[key] != null && wo[key] !== '') {
        wo[key] = this.nonNeg(wo[key]);
      }
    }
  }

  canRaiseClientIndent(client: any): boolean {
    return (
      this.nonNeg(client?.total_Client_shortage || client?.total_rm_shortage) > 0
    );
  }

  /**
   * The "Use of MC" dropdown (and the Use MC Stock / Send For Approval buttons it
   * unlocks) is only enabled when the client (RM) stock is short for this work
   * order AND there is mother-code (MC) stock available (> 0).
   */
  canUseMC(client: any, wo: any): boolean {
    const rmShort = this.nonNeg(wo?.rm_shortage) > 0;
    const mcAvailable =
      this.nonNeg(
        client?.available_MotherCode_Stock_qty1 ??
          client?.available_MotherCode_Stock_qty
      ) > 0;
    return rmShort && mcAvailable;
  }

  canRaiseMotherIndent(client: any): boolean {
    return (
      this.nonNeg(client?.total_Mother_shortage || client?.total_mc_shortage) > 0 &&
      this.isMcStockApproved(client)
    );
  }

  /**
   * MC stock usage must be cleared by the Director before a Mother-code indent
   * can be raised. Any work order that still needs MC stock to cover a client
   * (RM) shortage must have director_approval === 'Approve'. Work orders without
   * an RM shortage do not require approval (no MC stock is being consumed).
   */
  isMcStockApproved(client: any): boolean {
    const wos = client?.wos || [];
    const awaitingApproval = wos.some((wo: any) => {
      const needsMcStock = this.nonNeg(wo?.rm_shortage) > 0;
      const approved =
        String(wo?.director_approval || '').toLowerCase() === 'approve';
      return needsMcStock && !approved;
    });
    return !awaitingApproval;
  }

  /** WO still has live RM shortage after stock recalc (client code only). */
  private workOrderHasLiveShortage(wo: any): boolean {
    const rm = this.nonNeg(wo?.live_rm_shortage ?? wo?.rm_shortage ?? wo?.shortage);
    return rm > 0;
  }

  /** Hide materials with no remaining shortage work orders. */
  private isVisibleShortageMaterial(mat: any): boolean {
    return (
      this.nonNeg(mat?.wo_count) > 0 && this.nonNeg(mat?.total_shortage) > 0
    );
  }

  private removeMaterialFromLists(materialCode: string): void {
    if (!materialCode) {
      return;
    }
    const keep = (m: any) => m?.material_code !== materialCode;
    this.pendingpo = this.pendingpo.filter(keep);
    this.pendingpoBackup = this.pendingpoBackup.filter(keep);
    this.pendingpoFiltered = this.pendingpoFiltered.filter(keep);
    this.refreshVisibleMaterials();
  }

  private pruneInvisibleMaterials(): void {
    this.pendingpo = this.pendingpo.filter((m) =>
      this.isVisibleShortageMaterial(m)
    );
    this.pendingpoBackup = this.pendingpoBackup.filter((m) =>
      this.isVisibleShortageMaterial(m)
    );
    this.pendingpoFiltered = this.pendingpoFiltered.filter((m) =>
      this.isVisibleShortageMaterial(m)
    );
    this.refreshVisibleMaterials();
  }

  loadSummary(): void {
    this.loading = true;
    this.cdr.markForCheck();

    this.service.get('marketing/po.php?type=getShortagesPlannedWOSummary').subscribe({
      next: (response: any) => {
        let candidates: any[] = [];
        try {
          const list = Array.isArray(response) ? response : [];
          candidates = list
            .map((row) => this.createSummaryMaterial(row))
            .filter((m) => this.isVisibleShortageMaterial(m));
          candidates.sort((a, b) =>
            String(a.material_code ?? '').localeCompare(String(b.material_code ?? ''))
          );
        } catch (err) {
          console.error('Shortages: failed processing summary', err);
          candidates = [];
        }
        // Pre-load each material's detail (View) and keep only those that still
        // have shortage work orders after the live recalculation.
        this.prevalidateMaterials(candidates);
      },
      error: (err) => {
        console.error('Error fetching shortages summary', err);
        this.pendingpo = [];
        this.finalizeListState();
      },
    });
  }

  /** Build the detail (View) endpoint URL for a material. */
  private buildDetailUrl(code: string): string {
    return (
      'marketing/po.php?type=getShortagesPlannedWO&material_code=' +
      encodeURIComponent(code)
    );
  }

  /**
   * Apply a detail (View) API response onto a summary material and run the
   * RM/MC recalculation. Returns whether the material still has shortage WOs.
   */
  private applyDetailToMaterial(mat: any, response: any): boolean {
    const list = Array.isArray(response) ? response : [];
    const detail =
      list.find((m) => m.material_code === mat.material_code) || list[0];
    if (!detail) {
      mat.Client = [];
      mat.wo_count = 0;
      return false;
    }
    mat.Client = detail.Client || [];
    mat.uom = detail.uom ?? detail.Matunit ?? mat.uom;
    mat.Matunit = mat.uom;
    mat.client_code = detail.client_code ?? mat.client_code;
    mat.client_name = detail.client_name ?? mat.client_name;
    mat.MotherCode = detail.MotherCode ?? mat.MotherCode;
    mat.mat_type = detail.mat_type ?? mat.mat_type;
    mat.material_name = detail.material_name ?? mat.material_name;
    mat.openIndent = this.nonNeg(detail.openIndent ?? mat.openIndent);
    mat.openPO = this.nonNeg(detail.openPO ?? mat.openPO);
    mat.available_in_hand = this.nonNeg(detail.available_in_hand ?? mat.available_in_hand);
    mat.mother_available_qty = this.nonNeg(
      detail.mother_available_qty ?? mat.mother_available_qty
    );
    this.processMaterial(mat);
    return this.isVisibleShortageMaterial(mat);
  }

  /**
   * Calls the View detail for every candidate material in parallel (limited
   * concurrency) BEFORE rendering, dropping any material that has no shortage
   * work orders. Detail is cached so the View button only toggles expansion.
   */
  private prevalidateMaterials(materials: any[]): void {
    if (!materials.length) {
      this.pendingpo = [];
      this.finalizeListState();
      return;
    }

    const CONCURRENCY = 4;
    from(materials)
      .pipe(
        mergeMap(
          (mat) =>
            this.service.get(this.buildDetailUrl(mat.material_code)).pipe(
              map((resp: any) => {
                const visible = this.applyDetailToMaterial(mat, resp);
                mat.detailLoaded = visible;
                mat.detailExpanded = false;
                mat.detailLoading = false;
                return { mat, visible };
              }),
              catchError((err) => {
                console.error(
                  'Shortages: detail pre-check failed for',
                  mat?.material_code,
                  err
                );
                // On transient error keep the material based on summary counts.
                return of({
                  mat,
                  visible: this.isVisibleShortageMaterial(mat),
                });
              })
            ),
          CONCURRENCY
        ),
        toArray(),
        takeUntil(this.destroy$)
      )
      .subscribe({
        next: (results) => {
          this.pendingpo = results
            .filter((r) => r.visible)
            .map((r) => r.mat);
          this.finalizeListState();
        },
        error: (err) => {
          console.error('Shortages: pre-validation failed', err);
          // Fall back to summary-only visibility.
          this.pendingpo = materials.filter((m) =>
            this.isVisibleShortageMaterial(m)
          );
          this.finalizeListState();
        },
      });
  }

  getMaterialUom(mat: any): string {
    const u =
      mat?.uom ||
      mat?.Matunit ||
      mat?.unit ||
      mat?.Client?.[0]?.Matunit ||
      mat?.Client?.[0]?.uom ||
      '';
    return String(u).trim() || '—';
  }

  private createSummaryMaterial(row: any): any {
    const uom =
      row?.uom || row?.Matunit || row?.unit || '';
    return {
      ...row,
      mat_type: row.mat_type || row.material_type || '-',
      material_name: row.material_name || row.material_code || '-',
      planning_class: row.planning_class || 'rm',
      total_shortage: this.nonNeg(row?.total_shortage),
      wo_count: this.nonNeg(row?.wo_count),
      openPO: this.nonNeg(row?.openPO),
      available_in_hand: this.nonNeg(row?.available_in_hand),
      mother_available_qty: this.nonNeg(row?.mother_available_qty),
      uom,
      Matunit: uom,
      Client: [],
      detailExpanded: false,
      detailLoaded: false,
      detailLoading: false,
      selected: false,
    };
  }

  private finalizeListState(): void {
    try {
      this.pendingpo = this.pendingpo.filter((m) =>
        this.isVisibleShortageMaterial(m)
      );
      this.pendingpoBackup = [...this.pendingpo];
      this.pendingpo.forEach((m) => (m._searchText = this.buildMaterialSearchText(m)));
      this.applyFilterInternal(this.searchText);
      this.pruneInvisibleMaterials();
    } catch (err) {
      console.error('Shortages: failed building search / filter', err);
      this.pendingpoBackup = [];
      this.pendingpoFiltered = [];
      this.visibleMaterials = [];
    } finally {
      this.loading = false;
      this.cdr.markForCheck();
    }
  }

  toggleMaterialDetail(mat: any): void {
    if (mat.detailLoading) {
      return;
    }
    if (mat.detailLoaded) {
      mat.detailExpanded = !mat.detailExpanded;
      this.cdr.markForCheck();
      return;
    }
    this.loadMaterialDetail(mat);
  }

  loadMaterialDetail(mat: any): void {
    const code = mat?.material_code;
    if (!code) {
      return;
    }
    mat.detailLoading = true;
    mat.detailExpanded = true;
    this.cdr.markForCheck();

    this.service.get(this.buildDetailUrl(code)).subscribe({
      next: (response: any) => {
        try {
          const visible = this.applyDetailToMaterial(mat, response);
          if (!visible) {
            this.removeMaterialFromLists(mat.material_code);
            alertify.warning(
              'No work orders with shortage remain for this material.'
            );
            return;
          }
          mat.detailLoaded = true;
          mat.detailExpanded = true;
          this.syncMaterialToBackup(mat);
          this.pruneInvisibleMaterials();
        } catch (err) {
          console.error('Shortages: failed processing material detail', err);
          alertify.error('Failed to load material details.');
          mat.detailExpanded = false;
        } finally {
          mat.detailLoading = false;
          this.cdr.markForCheck();
        }
      },
      error: (err) => {
        console.error('Error fetching material detail', err);
        mat.detailLoading = false;
        mat.detailExpanded = false;
        alertify.error('Failed to load material details.');
        this.cdr.markForCheck();
      },
    });
  }

  private syncMaterialToBackup(mat: any): void {
    const idx = this.pendingpoBackup.findIndex(
      (m) => m.material_code === mat.material_code
    );
    if (idx >= 0) {
      this.pendingpoBackup[idx] = mat;
      mat._searchText = this.buildMaterialSearchText(mat);
    }
    const fIdx = this.pendingpoFiltered.findIndex(
      (m) => m.material_code === mat.material_code
    );
    if (fIdx >= 0) {
      this.pendingpoFiltered[fIdx] = mat;
    }
    this.refreshVisibleMaterials();
  }

  /** Shared RM pool across all clients/WOs for one material (matches PHP cascade). */
  private cascadeMaterialRmPool(mat: any): void {
    let rmStock = this.nonNeg(mat.available_in_hand);
    if (rmStock <= 0 && mat.Client?.[0]) {
      rmStock = this.nonNeg(mat.Client[0].available_Stock_qty);
    }
    const startPool = rmStock;
    const entries: Array<{ wo: any }> = [];
    (mat.Client || []).forEach((client: any) => {
      client.available_Stock_qty = startPool;
      (client.wos || []).forEach((wo: any) => entries.push({ wo }));
    });
    entries.sort((a, b) =>
      String(a.wo.workorder_no ?? '').localeCompare(String(b.wo.workorder_no ?? ''))
    );
    entries.forEach(({ wo }) => {
      const planQty = this.nonNeg(wo.batch_plan_qty);
      wo.clmc_Inhand = rmStock;
      wo.used_from_RM = Math.min(rmStock, planQty);
      const rmBal = rmStock - planQty;
      wo.rm_remaining = this.nonNeg(rmBal);
      wo.rm_shortage = this.nonNeg(-rmBal);
      wo.used_from_MC = 0;
      wo.deducted_from_MC = 0;
      wo.mc_remaining = 0;
      wo.mc_shortage = 0;
      wo.clmc_Remaining = wo.rm_remaining;
      wo.clmc_Shortage = wo.rm_shortage;
      rmStock = wo.rm_remaining;
      this.sanitizeWorkOrder(wo);
    });
  }

  /** RM/MC calculations and bulk processing for one material (after detail API). */
  private processMaterial(mat: any): void {
        if (mat.Client && mat.Client.length > 0) {
          mat.Client.sort((a, b) =>
            String(a.client_code ?? '').localeCompare(String(b.client_code ?? ''))
          );
        }

        const planningClass = String(mat.planning_class || '').toLowerCase();
        const isBulk =
          planningClass === 'bulk' ||
          (mat.mat_type && mat.mat_type.toLowerCase().includes('bulk'));
        if (isBulk) {
          mat.bulk_code = mat.material_code;
        }

        if (!Array.isArray(mat.Client)) {
          mat.Client = [];
        }

        mat.Client.forEach((client) => {
          // For bulk materials, ensure bulk_code is set at client level too
          if (isBulk) {
            client.bulk_code = mat.material_code;
          }

          // Client-code RM pool is material-wide (set in cascadeMaterialRmPool).
          client.available_MotherCode_Stock_qty = this.nonNeg(
            client.available_MotherCode_Stock_qty
          );
          client.available_MotherCode_Stock_qty1 = this.nonNeg(
            client.available_MotherCode_Stock_qty1 ??
              client.available_MotherCode_Stock_qty
          );

          (client.wos || []).forEach((wo) => {
            if (isBulk) {
              wo.bulk_code = mat.material_code;
            }

            // Process bulk and primix arrays from work order
            if (wo.bulk && Array.isArray(wo.bulk)) {
              wo.processedBulk = {
                premix: [],
                rawMaterials: []
              };
              
              // Extract indexData for bulk shortages
              const indexData = wo.indexData || {};
              const bulkComponents = indexData.bulkComponents || [];
              const primixShortages = indexData.primixShortages || [];
              const bulkShortage = Number(indexData.bulkShortage) || 0;

              wo.bulk.forEach(bulkItem => {
                // Find matching bulk component shortage data
                const compShortageData = bulkComponents.find((comp: any) => 
                  comp.material_code === bulkItem.material_code
                );
                
                if (bulkItem.material_type === 'Premix' || bulkItem.material_subtype === 'Premix') {
                  // This is a Premix material
                  const premixItem = {
                    id: bulkItem.id,
                    premixCode: bulkItem.material_code,
                    bulkCode: bulkItem.bulkCode,
                    material_code: bulkItem.material_code,
                    material_type: bulkItem.material_type,
                    material_subtype: bulkItem.material_subtype || 'Premix',
                    perQty: Number(bulkItem.perQty) || 0,
                    required_qty: compShortageData?.required_qty || 0,
                    available_qty: compShortageData?.available_qty || 0,
                    shortage_qty: compShortageData?.shortage_qty || 0,
                    primix: []
                  };
                  
                  // Get primix (raw materials) for this premix from wo.primix array
                  if (wo.primix && Array.isArray(wo.primix)) {
                    wo.primix.forEach(primixRawMat => {
                      // Find matching primix shortage data
                      const primixShortageData = primixShortages.find((ps: any) => 
                        ps.material_code === primixRawMat.material_code && 
                        ps.premixCode === bulkItem.material_code
                      );
                      
                      premixItem.primix.push({
                        id: primixRawMat.id,
                        material_code: primixRawMat.material_code,
                        material_type: primixRawMat.material_type,
                        material_subtype: primixRawMat.material_subtype,
                        perQty: Number(primixRawMat.perQty) || 0,
                        premixCode: primixRawMat.premixCode,
                        required_qty: primixShortageData?.required_qty || 0,
                        available_qty: primixShortageData?.available_qty || 0,
                        shortage_qty: primixShortageData?.shortage_qty || 0
                      });
                    });
                  }

                  wo.processedBulk.premix.push(premixItem);
                } else if (bulkItem.material_type === 'Raw Material') {
                  // This is a Raw Material directly in bulk
                  wo.processedBulk.rawMaterials.push({
                    id: bulkItem.id,
                    bulkCode: bulkItem.bulkCode,
                    material_code: bulkItem.material_code,
                    material_type: bulkItem.material_type,
                    material_subtype: bulkItem.material_subtype,
                    perQty: Number(bulkItem.perQty) || 0,
                    required_qty: compShortageData?.required_qty || 0,
                    available_qty: compShortageData?.available_qty || 0,
                    shortage_qty: compShortageData?.shortage_qty || 0
                  });
                }
              });

              // Store bulk shortage data
              wo.processedBulk.bulkShortage = bulkShortage;
            } else {
              // no bulk array on this WO
            }
          });

        });

        this.cascadeMaterialRmPool(mat);
        mat.Client.forEach((client) => {
          client.wos = (client.wos || []).filter((wo: any) =>
            this.workOrderHasLiveShortage(wo)
          );
          this.updateClientTotals(client);
        });

        // Drop empty clients and keep only materials with actual shortage rows.
        mat.Client = (mat.Client || []).filter((c: any) => (c?.wos || []).length > 0);
        mat.wo_count = (mat.Client || []).reduce(
          (sum: number, c: any) => sum + ((c?.wos || []).length),
          0
        );
        if (this.nonNeg(mat.total_shortage) <= 0) {
          mat.total_shortage = (mat.Client || []).reduce((sum: number, c: any) => {
            return sum + this.nonNeg(c?.total_rm_shortage);
          }, 0);
        }
        mat.total_shortage = this.nonNeg(mat.total_shortage);

        // Calculate material-level totals for bulk materials
        if (isBulk) {
          // Initialize totals for all materials
          mat.total_required = 0;
          mat.total_rm_shortage = 0;
          mat.total_mc_shortage = 0;
          mat.total_clmc_shortage = 0;
          
          // Initialize totals for Premix
          mat.premix_total_required = 0;
          mat.premix_total_rm_shortage = 0;
          mat.premix_total_mc_shortage = 0;
          mat.premix_total_clmc_shortage = 0;
          
          // Initialize totals for Raw Material
          mat.rawmaterial_total_required = 0;
          mat.rawmaterial_total_rm_shortage = 0;
          mat.rawmaterial_total_mc_shortage = 0;
          mat.rawmaterial_total_clmc_shortage = 0;
          
          // Check material_subtype at material level first
          const materialSubtype = mat.material_subtype || '';
          const isMaterialPremix = materialSubtype && (materialSubtype.toLowerCase().includes('premix') || materialSubtype.toLowerCase().includes('primix'));
          const isMaterialRawMaterial = materialSubtype && (materialSubtype.toLowerCase().includes('raw material') || materialSubtype.toLowerCase().includes('rawmaterial'));
          
          mat.Client.forEach((client) => {
            // Track Premix and Raw Material separately for this client
            let clientPremixRmShortage = 0;
            let clientPremixMcShortage = 0;
            let clientPremixClmcShortage = 0;
            let clientRawMaterialRmShortage = 0;
            let clientRawMaterialMcShortage = 0;
            let clientRawMaterialClmcShortage = 0;
            
            (client.wos || []).forEach((wo) => {
              const planQty = Number(wo.batch_plan_qty);
              mat.total_required += planQty;
              
              // Check if material is Premix or Raw Material
              // First check material level, then work order level
              const woMaterialSubtype = wo.material_subtype || materialSubtype || '';
              const isPremix = isMaterialPremix || (woMaterialSubtype && (woMaterialSubtype.toLowerCase().includes('premix') || woMaterialSubtype.toLowerCase().includes('primix')));
              const isRawMaterial = isMaterialRawMaterial || (woMaterialSubtype && (woMaterialSubtype.toLowerCase().includes('raw material') || woMaterialSubtype.toLowerCase().includes('rawmaterial')));
              
              if (isPremix) {
                mat.premix_total_required += planQty;
                // Accumulate shortages for Premix
                clientPremixRmShortage += wo.rm_shortage || 0;
                clientPremixMcShortage += wo.mc_shortage || 0;
                clientPremixClmcShortage += wo.clmc_Shortage || 0;
              } else if (isRawMaterial) {
                mat.rawmaterial_total_required += planQty;
                // Accumulate shortages for Raw Material
                clientRawMaterialRmShortage += wo.rm_shortage || 0;
                clientRawMaterialMcShortage += wo.mc_shortage || 0;
                clientRawMaterialClmcShortage += wo.clmc_Shortage || 0;
              } else {
                // If subtype not found, default to Raw Material for bulk materials
                mat.rawmaterial_total_required += planQty;
                clientRawMaterialRmShortage += wo.rm_shortage || 0;
                clientRawMaterialMcShortage += wo.mc_shortage || 0;
                clientRawMaterialClmcShortage += wo.clmc_Shortage || 0;
              }
            });
            
            // Add client totals to material totals
            mat.total_rm_shortage += client.total_rm_shortage || 0;
            mat.total_mc_shortage += client.total_mc_shortage || 0;
            mat.total_clmc_shortage += client.total_clmc_shortage || 0;
            
            // Add Premix and Raw Material shortages separately
            mat.premix_total_rm_shortage += clientPremixRmShortage;
            mat.premix_total_mc_shortage += clientPremixMcShortage;
            mat.premix_total_clmc_shortage += clientPremixClmcShortage;
            
          mat.rawmaterial_total_rm_shortage += clientRawMaterialRmShortage;
          mat.rawmaterial_total_mc_shortage += clientRawMaterialMcShortage;
          mat.rawmaterial_total_clmc_shortage += clientRawMaterialClmcShortage;
        });
        
        // Collect Premix materials list
        if (isBulk) {
          const materialSubtype = mat.material_subtype || '';
          const isMaterialPremix = materialSubtype && (materialSubtype.toLowerCase().includes('premix') || materialSubtype.toLowerCase().includes('primix'));
          
          if (isMaterialPremix || mat.premix_total_required > 0) {
            // This material itself is Premix or has Premix work orders
            mat.premixMaterials = [{
              material_code: mat.material_code,
              material_name: mat.material_name,
              material_subtype: mat.material_subtype || 'Premix',
              MotherCode: mat.MotherCode,
              total_required: mat.premix_total_required || 0,
              total_rm_shortage: mat.premix_total_rm_shortage || 0,
              total_mc_shortage: mat.premix_total_mc_shortage || 0,
              total_clmc_shortage: mat.premix_total_clmc_shortage || 0,
              clients: []
            }];
            
            // Add client details
            mat.Client.forEach((client) => {
              let clientPremixRequired = 0;
              let clientPremixRmShortage = 0;
              let clientPremixMcShortage = 0;
              let clientPremixClmcShortage = 0;
              
              (client.wos || []).forEach((wo) => {
                const woMaterialSubtype = wo.material_subtype || materialSubtype || '';
                const isPremix = isMaterialPremix || (woMaterialSubtype && (woMaterialSubtype.toLowerCase().includes('premix') || woMaterialSubtype.toLowerCase().includes('primix')));
                
                if (isPremix) {
                  clientPremixRequired += Number(wo.batch_plan_qty);
                  clientPremixRmShortage += wo.rm_shortage || 0;
                  clientPremixMcShortage += wo.mc_shortage || 0;
                  clientPremixClmcShortage += wo.clmc_Shortage || 0;
                }
              });
              
              if (clientPremixRequired > 0) {
                mat.premixMaterials[0].clients.push({
                  client_code: client.client_code,
                  client_name: client.client_name,
                  total_required: clientPremixRequired,
                  total_rm_shortage: clientPremixRmShortage,
                  total_mc_shortage: clientPremixMcShortage,
                  total_clmc_shortage: clientPremixClmcShortage
                });
              }
            });
          } else {
            mat.premixMaterials = [];
          }
        }
        }
  }

  refreshShortages(): void {
    this.loadSummary();
  }

// Called when clicking "Use MC Stock"
useMCStock(client, wo) {
  if (this.nonNeg(wo.rm_shortage) <= 0) return;
  if (wo.director_approval == 'pending'){
    alertify.error('Please Get Approval from Director');
    return;
  }

  const rmShortage = this.nonNeg(wo.rm_shortage);
  const mcAvailable = this.nonNeg(client.cumulative_mcStock);
  const mcUsed = Math.min(mcAvailable, rmShortage);

  wo.used_from_MC = this.nonNeg(this.nonNeg(wo.used_from_MC) + mcUsed);
  wo.mc_remaining = this.nonNeg(mcAvailable - mcUsed);
  wo.mc_shortage = this.nonNeg(rmShortage - mcUsed);
  wo.deducted_from_MC = wo.used_from_MC;
  wo.rm_shortage = this.nonNeg(rmShortage - mcUsed);

  client.cumulative_mcStock = wo.mc_remaining;
  wo.clmc_Remaining = this.nonNeg(wo.rm_remaining + wo.mc_remaining);
  wo.clmc_Shortage = this.nonNeg(wo.rm_shortage + wo.mc_shortage);
  this.sanitizeWorkOrder(wo);

  this.updateClientTotals(client);
  this.cdr.markForCheck();
}

// Update totals for a client (footer row + raise-indent quantities — client code only)
updateClientTotals(client: any) {
  const wos = client.wos || [];
  if (!wos.length) {
    client.total_rm_balance = 0;
    client.total_rm_shortage = 0;
    client.total_mc_balance = 0;
    client.total_mc_shortage = 0;
    client.total_clmc_inhand = 0;
    client.total_clmc_remaining = 0;
    client.total_clmc_shortage = 0;
    client.total_Client_shortage = 0;
    client.total_Mother_shortage = 0;
    return;
  }

  const lastWo = wos[wos.length - 1];
  const initialRm = this.nonNeg(client.available_Stock_qty);

  client.total_rm_balance = this.nonNeg(lastWo.rm_remaining);
  client.total_rm_shortage = wos.reduce(
    (sum, wo) => sum + this.nonNeg(wo.rm_shortage),
    0
  );
  client.total_mc_balance = 0;
  client.total_mc_shortage = 0;
  client.total_clmc_inhand = initialRm;
  client.total_clmc_remaining = this.nonNeg(lastWo.rm_remaining);
  client.total_clmc_shortage = client.total_rm_shortage;

  client.total_Client_shortage = this.nonNeg(client.total_rm_shortage);
  client.total_Mother_shortage = 0;

  wos.forEach((wo) => {
    const rm = this.nonNeg(wo.rm_shortage);
    const stored = this.nonNeg(wo.shortage);
    wo.Client_code_Indent = rm > 0 ? rm : stored;
    wo.Mother_code_Indent = 0;
    wo.used_from_RM = this.nonNeg(wo.used_from_RM);
    wo.used_from_MC = 0;
    this.sanitizeWorkOrder(wo);
  });
}

 



 SENDfORaNALYSIS(){
 
let temp={}
temp['Worders']=this.pendingpo;
    this.service.post(
      `marketing/po.php?type=update_Wo_SENDfORaNALYSIS`,
      JSON.stringify(temp)
    ).subscribe((response: any) => {
      if (response.status === 'success') {
        alert('Selected Orders Approved Successfully');
      
        this.refreshShortages();
       
      } else {
        alert('An error has occurred, please try again');
      }
    });
 }

 isView=false;
 selectedWo=[]
 View(wo:any){
  this.isView=true;
  this.selectedWo=wo;
  for(let i=0;i<this.selectedWo['Deductions'].length;i++){
    let wo=this.selectedWo['Deductions'][i];
    wo['requiredQty']=Number(wo['deducted_from_MC'])+Number(wo['deducted_from_RM'])+Number(wo['shortage']);
  }
 }

  RaiseInd(data: any, category: string, mat?: any): void {
    if (category !== 'Client') {
      return;
    }
    this.updateClientTotals(data);
    const temp = { ...data, category: 'Client' };
    const wos = temp.wos || [];

    temp.material_code = temp.material_code || mat?.material_code || '';
    temp.material_name = temp.material_name || mat?.material_name || '';
    temp.MotherCode = temp.MotherCode || mat?.MotherCode || '';
    temp.mat_type = temp.material_type || mat?.mat_type || '';
    temp.material_type = temp.material_type || mat?.mat_type || '';
    temp.material_subtype =
      temp.material_subtype || mat?.material_subtype || '';
    temp.Matunit =
      temp.Matunit || temp.uom || mat?.Matunit || mat?.uom || 'KG';
    temp.indent_type = 'Client Code';
    temp.total_rm_shortage =
      Number(temp.total_rm_shortage || temp.total_Client_shortage || 0);
    temp.total_mc_shortage = 0;

    temp.wos = wos.map((wo: any) => {
      const rmShortage = Number(
        wo.rm_shortage || wo.Client_code_Indent || wo.shortage || 0
      );

      return {
        ...wo,
        material_code: wo.material_code || temp.material_code,
        material_name: wo.material_name || temp.material_name,
        MotherCode: wo.MotherCode || temp.MotherCode,
        Matunit: wo.Matunit || wo.uom || temp.Matunit,
        rm_shortage: rmShortage,
        mc_shortage: 0,
        used_from_RM: Number(wo.used_from_RM ?? 0),
        used_from_MC: 0,
        workorder_no: wo.workorder_no || wo.required_for,
        work_order_no: wo.workorder_no || wo.required_for,
        // WO_deductions.id — must be the numeric deduction id (wo_deduction_id),
        // NOT wo.workorder_ID which actually holds the workorder_no string.
        workorder_ID: wo.wo_deduction_id || wo.id || wo.workorder_ID,
      };
    });

    const wosWithQty = temp.wos.filter(
      (wo: any) => Number(wo.rm_shortage || 0) > 0
    );
    if (!wosWithQty.length) {
      alertify.error('No shortage quantity to raise indent.');
      return;
    }
    temp.wos = wosWithQty;

    temp.required_for = temp.wos.map((wo: any) => ({
      reqQty:
        Number(wo.rm_shortage) ||
        Number(wo.Client_code_Indent) ||
        Number(wo.shortage) ||
        0,
      Matunit: wo.Matunit,
      client_name: wo.client_name,
      order_no: wo.order_no,
      product_code: wo.product_code,
      work_order_no: wo.workorder_no || wo.required_for,
      Client_code_Indent: wo.Client_code_Indent,
      Mother_code_Indent: 0,
      material_code: wo.material_code,
      MotherCode: wo.MotherCode,
    }));

    this.service
      .post(
        'purchase/indent.php?type=savePlanningIndentStore',
        JSON.stringify(temp)
      )
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Indent records saved successfully');
          this.refreshShortages();
        } else {
          const code = response?.error_code;
          if (code === 'FORECAST_COVERED') {
            alertify.warning(
              response?.message ||
                'Shortage is already covered by forecast MRP indent. Check Forecast Reconciliation Hub.'
            );
          } else {
            alertify.error(
              response?.message ||
                response?.details ||
                'Failed: An error occurred, please try again!'
            );
          }
        }
      });
  }



 // Raise Indent for Primix Material (Raw Material inside Premix)
 RaiseIndForPrimix(primixItem: any, client: any, wo: any) {
   // Find the material that contains this client
   const material = this.pendingpoBackup.find(mat => 
     mat.Client && mat.Client.some(c => c.client_code === client.client_code)
   );
   
   if (!material) {
     alertify.error('Material not found for this client');
     return;
   }
   
   // Find the client object in the material
   const clientObj = material.Client.find(c => c.client_code === client.client_code);
   
   if (!clientObj) {
     alertify.error('Client not found in material');
     return;
   }
   
   // Collect all primix items with the same material_code and client_code across all work orders
   const consolidatedPrimixItems: any[] = [];
   let totalShortageQty = 0;
   let motherCode = primixItem.MotherCode || '';
   let matUnit = client.Matunit || 'KG';
   
   clientObj.wos.forEach(workOrder => {
     if (workOrder.processedBulk && workOrder.processedBulk.premix) {
       workOrder.processedBulk.premix.forEach((premix: any) => {
         if (premix.primix && Array.isArray(premix.primix)) {
           premix.primix.forEach((pItem: any) => {
             // Check if this primix item matches the material_code
             if (pItem.material_code === primixItem.material_code) {
               const shortageQty = Number(pItem.shortage_qty) || 0;
               if (shortageQty > 0) {
                 consolidatedPrimixItems.push({
                   Matunit: matUnit,
                   work_order_no: workOrder.workorder_no,
                   Client_code_Indent: shortageQty,
                   Mother_code_Indent: 0,
                   material_code: pItem.material_code,
                   MotherCode: pItem.MotherCode || motherCode
                 });
                 totalShortageQty += shortageQty;
               }
             }
           });
         }
       });
     }
   });
   
   if (consolidatedPrimixItems.length === 0) {
     alertify.error('No primix items found to consolidate');
     return;
   }
   
   let temp: any = {
     client_code: client.client_code,
     client_name: client.client_name,
     material_code: primixItem.material_code,
     material_name: primixItem.material_code, // You may want to fetch material name
     category: 'Client',
     required_for: consolidatedPrimixItems
   };
   
   console.log('Raising consolidated indent for Primix Material:', temp);
   console.log('Total shortage quantity:', totalShortageQty);
   
   this.service.post('purchase/indent.php?type=savePlanningIndentStore', JSON.stringify(temp)).subscribe(response => {
     if (response['status'] == 'success') {
       alertify.success(`Indent record saved successfully for Primix Material (${consolidatedPrimixItems.length} work orders consolidated)`);
       this.refreshShortages();
     } else {
       alertify.error('Failed: An error occurred, please try again!');
     }
   });
 }

 // Raise Indent for Premix Component
 RaiseIndForPremix(premix: any, client: any, wo: any) {
   let temp: any = {
     client_code: client.client_code,
     client_name: client.client_name,
     material_code: premix.premixCode,
     material_name: premix.premixCode, // You may want to fetch material name
     category: 'Client',
     required_for: [{
       Matunit: client.Matunit || 'KG',
       work_order_no: wo.workorder_no,
       Client_code_Indent: premix.shortage_qty || 0,
       Mother_code_Indent: 0,
       material_code: premix.premixCode,
       MotherCode: premix.MotherCode || ''
     }]
   };
   
   console.log('Raising indent for Premix Component:', temp);
   
   this.service.post('purchase/indent.php?type=savePlanningIndentStore', JSON.stringify(temp)).subscribe(response => {
     if (response['status'] == 'success') {
       alertify.success('Indent record saved successfully for Premix Component');
       this.refreshShortages();
     } else {
       alertify.error('Failed: An error occurred, please try again!');
     }
   });
 }

 // Raise Indent for Bulk Raw Material (Direct Raw Material in Bulk)
 RaiseIndForBulkRawMaterial(rawMat: any, client: any, wo: any) {
   let temp: any = {
     client_code: client.client_code,
     client_name: client.client_name,
     material_code: rawMat.material_code,
     material_name: rawMat.material_code, // You may want to fetch material name
     category: 'Client',
     required_for: [{
       Matunit: client.Matunit || 'KG',
       work_order_no: wo.workorder_no,
       Client_code_Indent: rawMat.shortage_qty || 0,
       Mother_code_Indent: 0,
       material_code: rawMat.material_code,
       MotherCode: rawMat.MotherCode || ''
     }]
   };
   
   console.log('Raising indent for Bulk Raw Material:', temp);
   
   this.service.post('purchase/indent.php?type=savePlanningIndentStore', JSON.stringify(temp)).subscribe(response => {
     if (response['status'] == 'success') {
       alertify.success('Indent record saved successfully for Bulk Raw Material');
       this.refreshShortages();
     } else {
       alertify.error('Failed: An error occurred, please try again!');
     }
   });
 }

 SendForApproval(data,available_Stock_qty,available_MotherCode_Stock_qty1){
  let temp=data
  temp['available_Stock_qty']=available_Stock_qty;
  temp['available_MotherCode_Stock_qty1']=available_MotherCode_Stock_qty1;
 

 
console.log('temp :>> ', temp);



 
    this.service.post('marketing/po.php?type=sendMaterialForDirectorApproval', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Request Sent successfully');
        this.refreshShortages();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
// savePlanningIndentStore
}










}





















 