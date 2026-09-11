import { Component, OnDestroy, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-verify-stock',
  templateUrl: './verify-stock.component.html',
  styleUrls: ['./verify-stock.component.css']
})
export class VerifyStockComponent implements OnInit, OnDestroy {

  ngOnInit() {
    this.getPendingWOs();
  }

  constructor(private service: DataAccessService) { }

  loading = false;
  logLoading = false;
  verifyingStock = false;
  verifyingWoNo: string | null = null;
  rejectingWoNo: string | null = null;
  bookingStock = false;
  viewFromLog = false;
  pendingpo: any[] = [];
  searchText = '';
  shortageInfo: any[] = [];
  shortageCount = 0;
  showShortageModal = false;
  isView = false;
  selectedWo: any = null;
  verificationResults: any = {};
  logData: any[] = [];
  logSearchText = '';

  currentPage = 1;
  pageSize = 25;
  totalRecords = 0;

  logCurrentPage = 1;
  logPageSize = 25;
  logTotalRecords = 0;

  private searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;
  private logSearchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

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
      'marketing/po.php?type=getPendingVerificationWO&page=' +
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
      'marketing/po.php?type=getSendForBatchAllocationLog&page=' +
      this.logCurrentPage +
      '&limit=' +
      this.logPageSize;
    const q = (this.logSearchText || '').trim();
    if (q) {
      url += '&search=' + encodeURIComponent(q);
    }
    return url;
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

  formatDeliveryDate(value: unknown): string {
    if (value == null || value === '' || value === '0000-00-00' || value === '0000-00-00 00:00:00') {
      return '—';
    }
    const d = new Date(String(value));
    if (!isNaN(d.getTime())) {
      return d.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      });
    }
    return String(value);
  }

  woDeliveryDate(wo: any): string {
    return this.formatDeliveryDate(wo?.deliveryDate || wo?.delivery_date || '');
  }

  woGeneratedDate(wo: any): string {
    return this.formatDeliveryDate(
      wo?.Wo_Generated_on || wo?.entryOn || wo?.wo_generated_by_digi_sign_date || ''
    );
  }

  getOrderQty(wo: any): string {
    const qty = wo?.order_qty ?? wo?.planQty ?? wo?.plan_qty ?? '';
    return qty !== '' && qty != null ? String(qty) : '—';
  }

  getOrderUom(wo: any): string {
    const u = wo?.planUnit ?? wo?.plan_unit ?? '';
    return String(u).trim() || 'Nos';
  }

  getPendingWOs() {
    this.loading = true;
    this.service.get(this.buildQueueUrl()).subscribe({
      next: (response: any) => {
        if (response?.status === 'error') {
          this.pendingpo = [];
          this.totalRecords = 0;
          this.shortageInfo = [];
          this.shortageCount = 0;
          this.loading = false;
          alertify.error(response?.message || 'Error loading Verify & Book Stock queue');
          return;
        }
        const rows =
          response?.work_orders ||
          response?.data ||
          (Array.isArray(response) ? response : []);
        this.pendingpo = Array.isArray(rows) ? rows : [];
        this.totalRecords = Number(response?.total ?? this.pendingpo.length);
        const rawShortages = response?.shortage_info || [];
        this.shortageInfo = this.cleanShortageInfo(rawShortages);
        this.shortageCount = this.shortageInfo.length;
        this.hydrateVerificationFromWorkOrders(this.pendingpo);
        this.loading = false;
      },
      error: (err) => {
        console.error('Error fetching work orders:', err);
        this.loading = false;
        this.pendingpo = [];
        this.totalRecords = 0;
        alertify.error('Error loading work orders');
      }
    });
  }

  private hydrateVerificationFromWorkOrders(list: any[]): void {
    for (const wo of list || []) {
      const woNo = wo?.workorder_no;
      if (!woNo) {
        continue;
      }
      if ((wo.status || '') === 'Rejected') {
        this.verificationResults[woNo] = {
          ...(this.verificationResults[woNo] || {}),
          rejected: true,
        };
        continue;
      }
      if (wo.stock_verified_on) {
        const hasShortage = (wo.Deductions || []).some(
          (d: any) => Number(d?.shortage || 0) > 0
        );
        this.verificationResults[woNo] = {
          verified: true,
          hasShortage,
          stockBooked: !!wo.stock_booked && !hasShortage,
          shortageInfo: this.verificationResults[woNo]?.shortageInfo || [],
          deductions: wo.Deductions || [],
        };
      }
    }
  }

  isRejected(wo: any): boolean {
    if (!wo?.workorder_no) {
      return false;
    }
    return (
      (wo.status || '') === 'Rejected' ||
      !!this.verificationResults[wo.workorder_no]?.rejected
    );
  }

  isVerifyingWo(wo: any): boolean {
    return !!wo?.workorder_no && this.verifyingWoNo === wo.workorder_no;
  }

  isRejectingWo(wo: any): boolean {
    return !!wo?.workorder_no && this.rejectingWoNo === wo.workorder_no;
  }

  getDisplayStatusLabel(wo: any): string {
    if (this.isRejected(wo)) {
      return 'Rejected';
    }
    if (this.isSentForAllocation(wo) || (wo?.status || '') === 'Sent for Batch Allocation') {
      return 'Sent for Batch Allocation';
    }
    if (wo?.verification_status) {
      return String(wo.verification_status);
    }
    if (this.isVerified(wo)) {
      return this.hasShortages(wo) ? 'Verified - Shortage' : 'Verified (Stock Booked)';
    }
    return 'Pending Verification';
  }

  getDisplayStatusClass(wo: any): string {
    if (this.isRejected(wo)) {
      return 'vs-status--rejected';
    }
    if (this.isSentForAllocation(wo) || (wo?.status || '') === 'Sent for Batch Allocation') {
      return 'vs-status--allocation';
    }
    const verificationStatus = String(wo?.verification_status || '').toLowerCase();
    if (verificationStatus.includes('shortage')) {
      return 'vs-status--shortage';
    }
    if (verificationStatus.includes('verified')) {
      return 'vs-status--verified';
    }
    if (this.isVerified(wo)) {
      return this.hasShortages(wo) ? 'vs-status--shortage' : 'vs-status--verified';
    }
    return 'vs-status--pending';
  }

  verifyStock(wo: any) {
    if (!wo || !wo.workorder_no) {
      alertify.error('Invalid work order');
      return;
    }
    if (this.isRejected(wo)) {
      alertify.error('Work order is rejected');
      return;
    }

    this.verifyingStock = true;
    this.verifyingWoNo = wo.workorder_no;

    const verifyData = {
      workorder_no: wo.workorder_no
    };

    this.service.post(
      'marketing/po.php?type=verifyStockForWO',
      JSON.stringify(verifyData)
    ).subscribe({
      next: (response: any) => {
        this.verifyingStock = false;
        this.verifyingWoNo = null;

        if (response.status === 'success') {
          const cleanedShortages = this.cleanShortageInfo(response.shortage_info || []);
          this.verificationResults[wo.workorder_no] = {
            verified: true,
            hasShortage: response.has_shortage || false,
            stockBooked: response.stock_booked === true,
            shortageInfo: cleanedShortages,
            deductions: response.deductions || [],
          };

          if (response.deductions) {
            wo.Deductions = response.deductions;
            wo.stock_verified_on = wo.stock_verified_on || new Date().toISOString();
            wo.verification_status = response.verification_status
              || (response.has_shortage ? 'Verified - Shortage' : 'Verified');
            wo.stock_booked = response.stock_booked === true;
            wo.has_shortage = response.has_shortage === true;
          }
          if (this.selectedWo?.workorder_no === wo.workorder_no) {
            this.syncSelectedWoDeductions(wo);
          }

          if (response.has_shortage) {
            alertify.warning(`Stock verification completed. Shortages found for work order: ${wo.workorder_no}`);
            this.showShortageDetails(wo);
          } else {
            alertify.success(`Stock verification completed. All materials available for work order: ${wo.workorder_no}`);
          }
        } else {
          const msg = response?.message || 'Failed to verify stock';
          if (/already sent for batch allocation/i.test(msg)) {
            alertify.warning(msg);
          } else if (/already verified/i.test(msg)) {
            alertify.success(msg);
          } else {
            alertify.error(msg);
          }
        }
      },
      error: (err) => {
        this.verifyingStock = false;
        this.verifyingWoNo = null;
        console.error('Error verifying stock:', err);
        alertify.error('Error verifying stock');
      }
    });
  }

  rejectWorkOrder(wo: any): void {
    if (!wo?.workorder_no) {
      alertify.error('Invalid work order');
      return;
    }
    if (this.isRejected(wo)) {
      alertify.error('Work order is already rejected');
      return;
    }
    if (!confirm(`Reject work order ${wo.workorder_no}? Booked/hold stock will be released.`)) {
      return;
    }

    this.rejectingWoNo = wo.workorder_no;
    this.service
      .post(
        'marketing/po.php?type=rejectPendingVerificationWO',
        JSON.stringify({ workorder_no: wo.workorder_no })
      )
      .subscribe({
        next: (response: any) => {
          this.rejectingWoNo = null;
          if (response?.status === 'success') {
            alertify.success(response.message || 'Work order rejected');
            wo.status = 'Rejected';
            this.verificationResults[wo.workorder_no] = { rejected: true };
            if (this.isView && this.selectedWo?.workorder_no === wo.workorder_no) {
              this.isView = false;
            }
            this.getPendingWOs();
          } else {
            alertify.error(response?.message || 'Failed to reject work order');
          }
        },
        error: () => {
          this.rejectingWoNo = null;
          alertify.error('Failed to reject work order');
        },
      });
  }

  showShortageDetails(wo: any) {
    const result = this.verificationResults[wo.workorder_no];
    if (result && result.shortageInfo && result.shortageInfo.length > 0) {
      this.shortageInfo = result.shortageInfo;
      this.showShortageModal = true;
    }
  }

  canSendForBatchAllocation(wo: any): boolean {
    return (
      this.isVerified(wo) &&
      !this.hasShortages(wo) &&
      !this.isRejected(wo) &&
      !this.isSentForAllocation(wo)
    );
  }

  sendForBatchAllocation(wo: any) {
    if (!wo || !wo.workorder_no) {
      alertify.error('Invalid work order');
      return;
    }

    const result = this.verificationResults[wo.workorder_no];
    if (!result || !result.verified) {
      alertify.warning('Please verify stock first before sending for batch allocation');
      return;
    }

    if (result.hasShortage || this.hasShortages(wo)) {
      alertify.error('Cannot send work order with material shortages for batch allocation. Please resolve shortages first.');
      return;
    }

    if (!this.canSendForBatchAllocation(wo)) {
      if (!this.isVerified(wo)) {
        alertify.error('Click Verify Stock first. After it succeeds, Send for Batch Allocation will enable.');
        return;
      }
      if (this.hasShortages(wo)) {
        alertify.error('This work order has a shortage. Resolve stock, then verify again.');
        return;
      }
      alertify.error('Stock must be verified before batch allocation');
      return;
    }

    if (!confirm(`Are you sure you want to send work order ${wo.workorder_no} for Batch No Allocation?`)) {
      return;
    }

    this.bookingStock = true;

    const allocationData = {
      workorder_no: wo.workorder_no,
      order_no: wo.order_no,
      product_code: wo.product_code,
      product_name: wo.product_name,
      batch_size: wo.batch_size,
      planUnit: wo.planUnit,
      packing_type: wo.packing_type,
      work_order_planned_qty: wo.work_order_planned_qty,
      planMonth: wo.planMonth,
      mainGroupName: wo.mainGroupName,
      Deductions: wo.Deductions
    };

    this.service.post(
      'marketing/po.php?type=sendForBatchAllocation',
      JSON.stringify(allocationData)
    ).subscribe({
      next: (response: any) => {
        this.bookingStock = false;
        if (response.status === 'success') {
          alertify.success(`Work order ${wo.workorder_no} sent for Batch No Allocation successfully`);
          if (this.verificationResults[wo.workorder_no]) {
            this.verificationResults[wo.workorder_no].sentForAllocation = true;
          }
          this.isView = false;
          this.getPendingWOs();
        } else {
          alertify.error(response.message || `Failed to send work order for batch allocation`);
        }
      },
      error: (err) => {
        this.bookingStock = false;
        console.error('Error sending work order for batch allocation:', err);
        alertify.error('Error sending work order for batch allocation');
      }
    });
  }

  View(wo: any, fromLog = false) {
    this.viewFromLog = fromLog;
    this.isView = true;
    this.selectedWo = wo;
    this.syncSelectedWoDeductions(wo);
  }

  private syncSelectedWoDeductions(wo: any): void {
    if (!wo) {
      return;
    }
    if (!wo.Deductions) {
      wo.Deductions = [];
    }
    for (const ded of wo.Deductions) {
      ded.requiredQty =
        Number(ded.requiredQty ?? ded.plan_qty ?? 0) ||
        Number(ded.deducted_from_RM || 0) + Number(ded.shortage || 0);
    }
  }

  isVerified(wo: any): boolean {
    if (!wo?.workorder_no) {
      return false;
    }
    const st = String(wo.status || '').trim();
    if (st === 'Verified - Ready for Batch Allocation' || st === 'Sent for Batch Allocation') {
      return true;
    }
    return (
      !!this.verificationResults[wo.workorder_no]?.verified ||
      !!wo.stock_verified_on
    );
  }

  hasShortages(wo: any): boolean {
    if (!wo?.workorder_no) {
      return false;
    }
    const fromVerification = this.verificationResults[wo.workorder_no]?.hasShortage;
    if (typeof fromVerification === 'boolean') {
      return fromVerification;
    }
    const deductions = wo?.Deductions || [];
    return deductions.some((d: any) => Number(d?.shortage || 0) > 0);
  }

  isSentForAllocation(wo: any): boolean {
    return this.verificationResults[wo.workorder_no]?.sentForAllocation || false;
  }

  getLogData() {
    this.logLoading = true;
    this.service.get(this.buildLogUrl()).subscribe({
      next: (response: any) => {
        const rows =
          response?.can_plan_work_orders ||
          response?.work_orders ||
          response?.data ||
          (Array.isArray(response) ? response : []);
        this.logData = Array.isArray(rows) ? rows : [];
        this.logTotalRecords = Number(response?.total ?? this.logData.length);
        this.hydrateVerificationFromWorkOrders(this.logData);
        this.logLoading = false;
      },
      error: (err) => {
        this.logLoading = false;
        console.error('Error fetching log data:', err);
        alertify.error('Error loading log data');
        this.logData = [];
        this.logTotalRecords = 0;
      }
    });
  }

  canVerifyInView(): boolean {
    return (
      !!this.selectedWo &&
      !this.viewFromLog &&
      !this.isRejected(this.selectedWo) &&
      !this.isSentForAllocation(this.selectedWo)
    );
  }

  canRejectInView(): boolean {
    return !!this.selectedWo && !this.viewFromLog && !this.isRejected(this.selectedWo);
  }

  private cleanShortageInfo(raw: any[]): any[] {
    if (!Array.isArray(raw)) {
      return [];
    }
    return raw
      .map(item => {
        const filtered = (item.shortage_materials || []).filter((m: any) =>
          m.material_code && m.material_code !== 'N/A'
        );
        return { ...item, shortage_materials: filtered };
      })
      .filter(item => item.shortage_materials && item.shortage_materials.length > 0);
  }

  printLog() {
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
      <html>
        <head>
          <title>Send For Batch Allocation Log</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #4CAF50; color: white; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            h2 { text-align: center; margin-bottom: 20px; }
            .print-date { text-align: right; margin-bottom: 10px; }
          </style>
        </head>
        <body>
          <h2>Send For Batch Allocation Log</h2>
          <div class="print-date">Printed on: ${new Date().toLocaleString()}</div>
          ${printContent.innerHTML}
        </body>
      </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 250);
  }
}
