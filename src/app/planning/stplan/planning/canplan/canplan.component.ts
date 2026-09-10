import { Component, OnDestroy, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-canplan',
  templateUrl: './canplan.component.html',
  styleUrls: ['./canplan.component.css'],
})
export class CanplanComponent implements OnInit, OnDestroy {
  loading = false;
  logLoading = false;

  pendingpo: any[] = [];
  shortageInfo: any[] = [];
  flattenedShortageRows: any[] = [];
  showShortageModal = false;
  shortageCount = 0;

  canPlanReadyCount = 0;
  notToBePlanCount = 0;

  logData: any[] = [];
  logSearchText = '';
  searchText = '';

  currentPage = 1;
  pageSize = 25;
  totalRecords = 0;

  logCurrentPage = 1;
  logPageSize = 25;
  logTotalRecords = 0;

  isView = false;
  selectedWo: any = null;

  private searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;
  private logSearchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPendingWOs();
  }

  ngOnDestroy(): void {
    if (this.searchDebounceTimer) {
      clearTimeout(this.searchDebounceTimer);
    }
    if (this.logSearchDebounceTimer) {
      clearTimeout(this.logSearchDebounceTimer);
    }
  }

  trackByIndex(index: number): number {
    return index;
  }

  trackByWo(_index: number, wo: any): string {
    return String(wo?.workorder_no ?? _index);
  }

  formatPlanMonth(value: unknown): string {
    if (value == null || value === '') {
      return '—';
    }
    const s = String(value).trim();
    if (!s) {
      return '—';
    }
    const isoMonth = s.match(/^(\d{4})-(\d{2})(?:-\d{2})?/);
    if (isoMonth) {
      const d = new Date(Number(isoMonth[1]), Number(isoMonth[2]) - 1, 1);
      if (!isNaN(d.getTime())) {
        return d.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });
      }
    }
    const dmy = s.match(/^(\d{1,2})[\/\-](\d{4})$/);
    if (dmy) {
      const d = new Date(Number(dmy[2]), Number(dmy[1]) - 1, 1);
      if (!isNaN(d.getTime())) {
        return d.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });
      }
    }
    const parsed = new Date(s);
    if (!isNaN(parsed.getTime()) && /^\d{4}/.test(s)) {
      return parsed.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });
    }
    return s;
  }

  formatPlanningStatus(wo: any): string {
    if (wo?.display_plan_status) {
      return String(wo.display_plan_status);
    }
    const raw = String(wo?.planning_status || wo?.status || '').toUpperCase();
    if (raw === 'CAN_PLAN_MC_QTY_USED' || raw === 'CAN_PLAN') {
      return 'Can Plan';
    }
    if (raw === 'CANNOT_PLAN') {
      return 'Not To Be Plan';
    }
    return wo?.can_plan ? 'Can Plan' : 'Not To Be Plan';
  }

  planningStatusBadgeClass(wo: any): string {
    return this.canSendForVerification(wo) ? 'badge-success' : 'badge-warning';
  }

  formatProcessSource(wo: any): string {
    if (wo?.process_plan_source === 'indent_sent_to_purchase') {
      return 'Indent → Purchase';
    }
    return 'Generate WO — To Be Plan';
  }

  formatPurchasePipeline(wo: any): string {
    const raw = String(wo?.purchase_pipeline_status || '').trim();
    if (raw && raw !== '—' && raw !== '-') {
      return raw;
    }
    return this.isCanPlanRow(wo) ? 'Stock in hand' : '—';
  }

  formatDateTime(value: unknown): string {
    if (value == null || value === '') {
      return '—';
    }
    const d = new Date(String(value));
    if (isNaN(d.getTime())) {
      return String(value);
    }
    return d.toLocaleString('en-GB', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  formatLogWoStatus(wo: any): string {
    const s = String(wo?.status || '').trim();
    if (!s) {
      return '—';
    }
    if (s === 'Pending Verification') {
      return 'Pending Verification';
    }
    if (s === 'Sent for Batch Allocation') {
      return 'Sent for Batch Allocation';
    }
    if (s === 'CAN_PLAN' || s === 'CAN_PLAN_MC_QTY_USED') {
      return 'Can Plan';
    }
    return s;
  }

  logStatusBadgeClass(wo: any): string {
    const s = String(wo?.status || '').trim();
    if (s === 'Sent for Batch Allocation') {
      return 'badge-info';
    }
    if (s === 'Pending Verification') {
      return 'badge-warning';
    }
    return 'badge-secondary';
  }

  isCanPlanRow(wo: any): boolean {
    if (!wo || wo.has_shortage === true || Number(wo.total_shortage || 0) > 0) {
      return false;
    }
    const status = String(wo.planning_status || wo.status || '').toUpperCase();
    if (status === 'CAN_PLAN' || status === 'CAN_PLAN_MC_QTY_USED') {
      return true;
    }
    return wo.can_plan === true || wo.can_plan === 1 || wo.can_plan === '1'
      || wo.ready_for_verification === true || wo.ready_for_verification === 1;
  }

  canSendForVerification(wo: any): boolean {
    return this.isCanPlanRow(wo);
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  calculateLogStartSrNo(): number {
    return (this.logCurrentPage - 1) * this.logPageSize;
  }

  onSearchChange(): void {
    if (this.searchDebounceTimer) {
      clearTimeout(this.searchDebounceTimer);
    }
    this.searchDebounceTimer = setTimeout(() => {
      this.currentPage = 1;
      this.getPendingWOs();
    }, 350);
  }

  onLogSearchChange(): void {
    if (this.logSearchDebounceTimer) {
      clearTimeout(this.logSearchDebounceTimer);
    }
    this.logSearchDebounceTimer = setTimeout(() => {
      this.logCurrentPage = 1;
      this.getLogData();
    }, 350);
  }

  onPageChange(page: number): void {
    this.currentPage = page;
    this.getPendingWOs();
  }

  onPageSizeChange(size: number): void {
    this.pageSize = size;
    this.currentPage = 1;
    this.getPendingWOs();
  }

  onLogPageChange(page: number): void {
    this.logCurrentPage = page;
    this.getLogData();
  }

  onLogPageSizeChange(size: number): void {
    this.logPageSize = size;
    this.logCurrentPage = 1;
    this.getLogData();
  }

  private buildQueueUrl(): string {
    let url =
      'marketing/po.php?type=getCanPlannedWO&page=' +
      this.currentPage +
      '&limit=' +
      this.pageSize;
    const q = (this.searchText || '').trim();
    if (q) {
      url += '&search=' + encodeURIComponent(q);
    }
    return url;
  }

  private buildLogUrl(): string {
    let url =
      'marketing/po.php?type=getSendForVerificationLog&page=' +
      this.logCurrentPage +
      '&limit=' +
      this.logPageSize;
    const q = (this.logSearchText || '').trim();
    if (q) {
      url += '&search=' + encodeURIComponent(q);
    }
    return url;
  }

  getPendingWOs(): void {
    this.loading = true;
    this.service.get(this.buildQueueUrl()).subscribe({
      next: (response: any) => {
        if (response?.status === 'success' || response?.can_plan_work_orders) {
          this.pendingpo = response.data || response.can_plan_work_orders || [];
          this.totalRecords = Number(response.total ?? this.pendingpo.length) || 0;
          this.canPlanReadyCount = Number(response.can_plan_ready_count ?? 0) || 0;
          this.notToBePlanCount = Number(
            response.not_to_be_plan_count ?? Math.max(0, this.totalRecords - this.canPlanReadyCount)
          ) || 0;
          const rawShortages = response.shortage_info || [];
          this.shortageInfo = this.cleanShortageInfo(rawShortages);
          this.shortageCount = Number(response.shortage_count ?? this.shortageInfo.length) || 0;
        } else if (Array.isArray(response)) {
          this.pendingpo = response;
          this.totalRecords = response.length;
          this.shortageInfo = [];
          this.shortageCount = 0;
        } else {
          this.pendingpo = [];
          this.totalRecords = 0;
          this.shortageInfo = [];
          this.shortageCount = 0;
        }
        this.syncPlanCountsFromRows();
        this.rebuildFlattenedShortages();
        this.loading = false;
      },
      error: (err) => {
        console.error('Error fetching work orders:', err);
        this.loading = false;
        this.pendingpo = [];
        this.totalRecords = 0;
        alertify.error('Error loading Process Plan queue');
      },
    });
  }

  private syncPlanCountsFromRows(): void {
    const rows = Array.isArray(this.pendingpo) ? this.pendingpo : [];
    this.canPlanReadyCount = rows.filter((wo) => this.isCanPlanRow(wo)).length;
    this.notToBePlanCount = Math.max(0, rows.length - this.canPlanReadyCount);
    if (!this.totalRecords) {
      this.totalRecords = rows.length;
    }
  }

  getLogData(): void {
    this.logLoading = true;
    this.service.get(this.buildLogUrl()).subscribe({
      next: (response: any) => {
        if (response?.status === 'success' || response?.can_plan_work_orders) {
          this.logData = response.data || response.can_plan_work_orders || [];
          this.logTotalRecords = Number(response.total ?? this.logData.length) || 0;
        } else if (Array.isArray(response)) {
          this.logData = response;
          this.logTotalRecords = response.length;
        } else {
          this.logData = [];
          this.logTotalRecords = 0;
        }
        this.logLoading = false;
      },
      error: (err) => {
        console.error('Error fetching log data:', err);
        this.logLoading = false;
        this.logData = [];
        this.logTotalRecords = 0;
        alertify.error('Error loading verification log');
      },
    });
  }

  sendForVerification(wo: any): void {
    if (!wo?.workorder_no) {
      alertify.error('Invalid work order');
      return;
    }
    if (!this.canSendForVerification(wo)) {
      alertify.error('Cannot send — live stock shortage on this work order');
      return;
    }
    if (!confirm(`Send work order ${wo.workorder_no} for verification?`)) {
      return;
    }
    this.loading = true;
    this.service
      .post('marketing/po.php?type=sendForVerification', JSON.stringify({ workorder_no: wo.workorder_no }))
      .subscribe({
        next: (response: any) => {
          this.loading = false;
          if (response?.status === 'success') {
            alertify.success(`Work order ${wo.workorder_no} sent for verification`);
            this.getPendingWOs();
          } else {
            alertify.error(response?.message || 'Failed to send for verification');
          }
        },
        error: () => {
          this.loading = false;
          alertify.error('Error sending work order for verification');
        },
      });
  }

  View(wo: any): void {
    this.isView = true;
    this.selectedWo = wo;
    const deductions = this.selectedWo?.Deductions || [];
    for (const mat of deductions) {
      mat.requiredQty =
        Number(mat.requiredQty ?? mat.plan_qty ?? 0) ||
        Number(mat.deducted_from_RM || 0) + Number(mat.deducted_from_Bulk || 0) + Number(mat.shortage || 0);
    }
  }

  rejectWorkOrder(wo: any): void {
    if (!wo?.workorder_no) {
      alertify.error('Invalid work order');
      return;
    }
    if (!confirm(`Reject work order ${wo.workorder_no}? Booked stock will be released.`)) {
      return;
    }
    this.loading = true;
    this.service
      .post('marketing/po.php?type=rejectCanPlanWorkOrder', JSON.stringify({ workorder_no: wo.workorder_no }))
      .subscribe({
        next: (response: any) => {
          this.loading = false;
          if (response?.status === 'success') {
            alertify.success(response.message || 'Work order rejected');
            if (this.isView && this.selectedWo?.workorder_no === wo.workorder_no) {
              this.isView = false;
            }
            this.getPendingWOs();
          } else {
            alertify.error(response?.message || 'Failed to reject work order');
          }
        },
        error: () => {
          this.loading = false;
          alertify.error('Error rejecting work order');
        },
      });
  }

  private rebuildFlattenedShortages(): void {
    const rows: any[] = [];
    for (const wo of this.shortageInfo || []) {
      for (const mat of wo.shortage_materials || []) {
        rows.push({
          workorder_no: wo.workorder_no,
          product_name: wo.product_name || wo.product_code || '—',
          ...mat,
        });
      }
    }
    this.flattenedShortageRows = rows;
  }

  private cleanShortageInfo(raw: any[]): any[] {
    if (!Array.isArray(raw)) {
      return [];
    }
    return raw
      .map((item) => {
        const filtered = (item.shortage_materials || []).filter(
          (m: any) => m.material_code && m.material_code !== 'N/A'
        );
        return { ...item, shortage_materials: filtered };
      })
      .filter((item) => item.shortage_materials?.length > 0);
  }

  printLog(): void {
    const printContent = document.getElementById('logTable');
    if (!printContent) {
      alertify.error('Log table not found');
      return;
    }
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
      alertify.error('Please allow popups to print');
      return;
    }
    printWindow.document.write(`
      <html><head><title>Send For Verification Log</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
      </style></head><body>
      <h2>Send For Verification Log</h2>
      <div>Printed: ${new Date().toLocaleString()}</div>
      ${printContent.innerHTML}
      </body></html>`);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 250);
  }
}
