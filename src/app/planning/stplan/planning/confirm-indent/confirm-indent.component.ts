import {
  ChangeDetectionStrategy,
  ChangeDetectorRef,
  Component,
  OnDestroy,
  OnInit,
} from '@angular/core';
import { Subject } from 'rxjs';
import { debounceTime, takeUntil } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-confirm-indent',
  templateUrl: './confirm-indent.component.html',
  styleUrls: ['./confirm-indent.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ConfirmIndentComponent implements OnInit, OnDestroy {
  private readonly destroy$ = new Subject<void>();
  private readonly search$ = new Subject<string>();
  private static readonly DISPLAY_CAP_STEP = 25;

  searchText = '';

  confirmMaterials: any[] = [];
  confirmMaterialsBackup: any[] = [];
  confirmMaterialsFiltered: any[] = [];
  visibleConfirmMaterials: any[] = [];
  confirmLoading = false;
  confirmMaterialsDisplayCap = 30;

  /** Remark modal for Hold / Cancel indent */
  remarkModalOpen = false;
  remarkAction: 'hold' | 'cancel' = 'hold';
  remarkText = '';
  selectedMat: any = null;
  remarkSubmitting = false;

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
    this.loadConfirmSummary();
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
    if (!q) {
      this.confirmMaterialsFiltered = this.confirmMaterialsBackup;
    } else {
      this.confirmMaterialsFiltered = this.confirmMaterialsBackup.filter((m) =>
        (m._searchText || '').includes(q)
      );
    }
    this.confirmMaterials = this.confirmMaterialsFiltered;
    this.confirmMaterialsDisplayCap = 30;
    this.refreshVisibleConfirmMaterials();
  }

  private refreshVisibleConfirmMaterials(): void {
    const list = this.confirmMaterialsFiltered;
    this.visibleConfirmMaterials =
      list.length <= this.confirmMaterialsDisplayCap
        ? list
        : list.slice(0, this.confirmMaterialsDisplayCap);
  }

  get hasMoreConfirmMaterials(): boolean {
    return (
      (this.confirmMaterialsFiltered?.length ?? 0) >
      (this.visibleConfirmMaterials?.length ?? 0)
    );
  }

  get totalConfirmMaterialsFiltered(): number {
    return this.confirmMaterialsFiltered?.length ?? 0;
  }

  showMoreConfirmMaterials(): void {
    this.confirmMaterialsDisplayCap = Math.min(
      this.confirmMaterialsDisplayCap + ConfirmIndentComponent.DISPLAY_CAP_STEP,
      this.confirmMaterialsFiltered.length
    );
    this.refreshVisibleConfirmMaterials();
    this.cdr.markForCheck();
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

  private buildMaterialSearchText(mat: any): string {
    const parts: string[] = [];
    const add = (v: unknown) => {
      if (v != null && v !== '') {
        parts.push(String(v).toLowerCase());
      }
    };
    add(mat.material_code);
    add(mat.material_name);
    add(mat.client_name);
    add(mat.client_code);
    add(mat.product_name);
    add(mat.product_names);
    add(mat.product_code);
    add(mat.product_codes);
    add(mat.bulk_code);
    add(mat.forecast_no);
    add(mat.forecast_nos);
    add(mat.purchase_lead_time);
    add(mat.delivery_time);
    add(mat.indent_no);
    add(mat.indent_request_no);
    add(mat.indent_raised_by_name);
    add(mat.indent_raw_status);
    add(mat.total_shortage);
    return parts.join(' ');
  }

  loadConfirmSummary(): void {
    this.confirmLoading = true;
    this.cdr.markForCheck();
    this.service
      .get('marketing/po.php?type=getShortagesPlannedWOSummary&raised=1')
      .subscribe({
        next: (response: any) => {
          try {
            const list = Array.isArray(response) ? response : [];
            this.confirmMaterials = list.map((row) => this.createSummaryMaterial(row));
            this.confirmMaterials.sort((a, b) =>
              String(a.material_code ?? '').localeCompare(String(b.material_code ?? ''))
            );
          } catch (err) {
            console.error('ConfirmIndent: failed processing summary', err);
            this.confirmMaterials = [];
          }
          this.finalizeConfirmListState();
        },
        error: (err) => {
          console.error('Error fetching raised indent summary', err);
          this.confirmLoading = false;
          this.cdr.markForCheck();
        },
      });
  }

  private finalizeConfirmListState(): void {
    try {
      this.confirmMaterialsBackup = [...this.confirmMaterials];
      this.confirmMaterials.forEach(
        (m) => (m._searchText = this.buildMaterialSearchText(m))
      );
      this.applyFilterInternal(this.searchText);
    } catch (err) {
      this.confirmMaterialsBackup = [];
      this.confirmMaterialsFiltered = [];
      this.visibleConfirmMaterials = [];
    } finally {
      this.confirmLoading = false;
      this.cdr.markForCheck();
    }
  }

  collectIndentIdsForMaterial(mat: any): number[] {
    const ids = new Set<number>();
    if (mat?.indent_id) {
      ids.add(Number(mat.indent_id));
    }
    (mat?.Client || []).forEach((client: any) => {
      (client?.wos || []).forEach((wo: any) => {
        if (wo?.indent_id) {
          ids.add(Number(wo.indent_id));
        }
      });
    });
    return Array.from(ids).filter((id) => id > 0);
  }

  canConfirmMaterial(mat: any): boolean {
    if (!this.collectIndentIdsForMaterial(mat).length) {
      return false;
    }
    const st: string = String(mat?.indent_raw_status || 'confirmed').toLowerCase();
    const onHold =
      String(mat?.indent_status || '').toLowerCase() === 'hold' || st === 'hold';
    return (st === 'pending' || st === 'store_confirm') && !onHold;
  }

  canSendMaterialForPurchase(mat: any): boolean {
    if (!this.collectIndentIdsForMaterial(mat).length) {
      return false;
    }
    const st: string = String(mat?.indent_raw_status || '').toLowerCase();
    const onHold =
      String(mat?.indent_status || '').toLowerCase() === 'hold' || st === 'hold';
    return (
      (st === 'pending' || st === 'confirmed' || st === 'store_confirm') && !onHold
    );
  }

  canHoldOrCancelMaterial(mat: any): boolean {
    if (!this.collectIndentIdsForMaterial(mat).length) {
      return false;
    }
    const st = String(mat?.indent_raw_status || '').toLowerCase();
    return !['returned', 'cancelled'].includes(st);
  }

  openRemarkModal(mat: any, action: 'hold' | 'cancel'): void {
    this.selectedMat = mat;
    this.remarkAction = action;
    this.remarkText = '';
    this.remarkModalOpen = true;
    this.cdr.markForCheck();
  }

  closeRemarkModal(): void {
    this.remarkModalOpen = false;
    this.selectedMat = null;
    this.remarkText = '';
    this.remarkSubmitting = false;
    this.cdr.markForCheck();
  }

  submitRemarkAction(): void {
    const remark = (this.remarkText || '').trim();
    if (!remark) {
      alertify.error('Remark is required.');
      return;
    }
    const mat = this.selectedMat;
    if (!mat) {
      return;
    }
    const indentIds = this.collectIndentIdsForMaterial(mat);
    if (!indentIds.length) {
      alertify.error('No indent found for this material.');
      return;
    }
    const endpoint =
      this.remarkAction === 'hold'
        ? 'purchase/indent.php?type=holdPlanningIndent'
        : 'purchase/indent.php?type=cancelPlanningIndent';
    this.remarkSubmitting = true;
    if (this.remarkAction === 'hold') {
      mat.holdActionLoading = true;
    } else {
      mat.cancelActionLoading = true;
    }
    this.cdr.markForCheck();
    this.service
      .post(endpoint, JSON.stringify({ indent_ids: indentIds, remark }))
      .subscribe({
        next: (response: any) => {
          this.remarkSubmitting = false;
          if (mat) {
            mat.holdActionLoading = false;
            mat.cancelActionLoading = false;
          }
          if (response?.status === 'success') {
            alertify.success(response.message || 'Updated successfully.');
            this.closeRemarkModal();
            this.loadConfirmSummary();
          } else {
            alertify.error(response?.message || 'Action failed.');
          }
          this.cdr.markForCheck();
        },
        error: () => {
          this.remarkSubmitting = false;
          if (mat) {
            mat.holdActionLoading = false;
            mat.cancelActionLoading = false;
          }
          alertify.error('Action failed.');
          this.cdr.markForCheck();
        },
      });
  }

  confirmMaterialIndent(mat: any): void {
    const indentIds = this.collectIndentIdsForMaterial(mat);
    if (!indentIds.length) {
      alertify.error('No indent found for this material.');
      return;
    }
    mat.confirmActionLoading = true;
    this.cdr.markForCheck();
    this.service
      .post(
        'purchase/indent.php?type=confirmPlanningIndent',
        JSON.stringify({ indent_ids: indentIds })
      )
      .subscribe({
        next: (response: any) => {
          mat.confirmActionLoading = false;
          if (response?.status === 'success') {
            alertify.success(response.message || 'Indent confirmed.');
            this.loadConfirmSummary();
          } else {
            alertify.error(response?.message || 'Confirm indent failed.');
          }
          this.cdr.markForCheck();
        },
        error: () => {
          mat.confirmActionLoading = false;
          alertify.error('Confirm indent failed.');
          this.cdr.markForCheck();
        },
      });
  }

  sendMaterialIndentForPurchase(mat: any): void {
    const indentIds = this.collectIndentIdsForMaterial(mat);
    if (!indentIds.length) {
      alertify.error('No indent found for this material.');
      return;
    }
    mat.sendActionLoading = true;
    this.cdr.markForCheck();
    this.service
      .post(
        'purchase/indent.php?type=sendPlanningIndentForPurchase',
        JSON.stringify({ indent_ids: indentIds })
      )
      .subscribe({
        next: (response: any) => {
          mat.sendActionLoading = false;
          if (response?.status === 'success') {
            alertify.success(
              response.message ||
                'Indent sent to Purchase → Indent from MRP.'
            );
            this.loadConfirmSummary();
          } else {
            alertify.error(response?.message || 'Send for purchase failed.');
          }
          this.cdr.markForCheck();
        },
        error: () => {
          mat.sendActionLoading = false;
          alertify.error('Send for purchase failed.');
          this.cdr.markForCheck();
        },
      });
  }

  private createSummaryMaterial(row: any): any {
    const uom = row?.uom || row?.Matunit || row?.unit || '';
    return {
      ...row,
      mat_type: row.mat_type || row.material_type || '-',
      uom,
      Matunit: uom,
      Client: [],
      confirmActionLoading: false,
      sendActionLoading: false,
      holdActionLoading: false,
      cancelActionLoading: false,
    };
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

  getForecastNo(mat: any): string {
    const v = mat?.forecast_no || mat?.forecast_nos || mat?.order_no || '';
    return String(v).trim() || '—';
  }

  /** Purchase lead time (days) from timeline-master / material table. */
  getPurchaseLeadTime(mat: any): string {
    const days = Number(mat?.purchase_lead_time ?? 0);
    if (!days || days <= 0) {
      return '—';
    }
    return `${days} day${days === 1 ? '' : 's'}`;
  }

  /** Delivery time (days) from the material master (PurchaseDeliveryTime). */
  getDeliveryTime(mat: any): string {
    const raw = mat?.delivery_time ?? '';
    const days = Number(raw);
    if (!raw || isNaN(days) || days <= 0) {
      return String(raw || '').trim() || '—';
    }
    return `${days} day${days === 1 ? '' : 's'}`;
  }

  /** Product name(s) from the work order(s) consuming this material. */
  getProductName(mat: any): string {
    const v = mat?.product_names || mat?.product_name || '';
    return String(v).trim() || '—';
  }

  /** Product code(s) from the work order(s) consuming this material. */
  getProductCode(mat: any): string {
    const v = mat?.product_codes || mat?.product_code || '';
    return String(v).trim() || '—';
  }

  getIndentRaisedBy(mat: any): string {
    const name =
      mat?.indent_raised_by_name ||
      mat?.indent_entry_by_name ||
      mat?.indent_raised_by ||
      mat?.indent_entry_by ||
      mat?.entry_by ||
      '';
    return String(name).trim() || '—';
  }

  getIndentRaisedDate(mat: any): string | null {
    const d =
      mat?.indent_raised_on ||
      mat?.indent_entry_date ||
      mat?.entry_date ||
      null;
    return d || null;
  }

  formatRaisedIndent(mat: any): string {
    const parts: string[] = [];
    const requestNo = mat?.indent_request_no || mat?.request_no;
    const indentNo = mat?.indent_no || mat?.no;
    if (requestNo) {
      parts.push(String(requestNo));
    }
    if (indentNo) {
      parts.push(String(indentNo));
    }
    return parts.length ? parts.join(' / ') : '—';
  }

  getIndentWorkflowStatus(mat: any): { label: string; badgeClass: string } {
    const raw = String(mat?.indent_raw_status || mat?.status || '')
      .trim()
      .toLowerCase();
    const wo = String(mat?.indent_status || '')
      .trim()
      .toLowerCase();

    if (raw === 'hold' || wo === 'hold') {
      return { label: 'On Hold', badgeClass: 'ci-badge--hold' };
    }
    if (raw === 'returned') {
      return { label: 'Returned to Shortages', badgeClass: 'ci-badge--other' };
    }
    if (raw === 'cancelled') {
      return { label: 'Cancelled', badgeClass: 'ci-badge--cancelled' };
    }
    if (raw === 'to_planthead' || raw === 'to planthead') {
      return { label: 'Sent to Purchase (legacy)', badgeClass: 'ci-badge--purchase' };
    }
    if (raw === 'pending' && wo === 'indent sent') {
      return { label: 'In Purchase Queue', badgeClass: 'ci-badge--purchase' };
    }
    if (raw === 'store_confirm') {
      return { label: 'Pending', badgeClass: 'ci-badge--pending' };
    }
    if (raw === 'confirmed') {
      return { label: 'Confirmed', badgeClass: 'ci-badge--confirmed' };
    }
    if (raw === 'pending') {
      return { label: 'Pending', badgeClass: 'ci-badge--pending' };
    }
    if (raw === 'merge' || raw === 'rejected') {
      return {
        label: mat.indent_raw_status || '—',
        badgeClass: 'ci-badge--other',
      };
    }
    if (wo === 'indent sent' || wo === 'raised') {
      return { label: 'Raised Indent', badgeClass: 'ci-badge--raised' };
    }
    const fallback = mat?.indent_raw_status || mat?.indent_status || '—';
    return { label: fallback, badgeClass: 'ci-badge--other' };
  }
}
