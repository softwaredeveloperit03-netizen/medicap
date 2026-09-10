import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

@Component({
  selector: 'app-receivepofo',
  templateUrl: './receivepofo.component.html',
  styleUrls: ['./receivepofo.component.css']
})
export class ReceivepofoComponent implements OnInit {
  pendingpo: any = [];
  loading = false;

  // Extra Batch Modal
  extraBatchModalOpen = false;
  currentPO: any = null;
  currentProduct: any = null;
  currentBatchSizes: any[] = [];
  selectedBatchSize: number = 0;
  extraBatchQty: number = 0;

  isView = false;
  selectedResult: any[] = [];
  loginEmpId = (localStorage.getItem('emp_id') || '').trim();
  loggedInDept = (localStorage.getItem('department') || '').trim();

  constructor(
    private service: DataAccessService,
    private route: ActivatedRoute,
    private deptNav: DeptNavigationService
  ) { }

  ngOnInit() {
    this.getPendingPOs();
    this.initializeYearOptions();
  }

  closePage(): void {
    this.deptNav.goBack(this.route, '/planning/stplan?returnUrl=%2Fplanning');
  }

  // ---------------- Order splitting ----------------
  splitModalOpen = false;
  splitSource: any = null;
  planEntireSingleMonth = false;
  entireMonth = '';
  entireYear = '';
  useFgStock = false;
  subtractInProcess = false;
  splitRows: any[] = [];
  newInhouse: any = 0;
  newOutsource: any = 0;
  newMonth = '';
  newYear = '';
  monthOptions = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
  ];
  yearOptions: string[] = [];

  initializeYearOptions() {
    const currentYear = new Date().getFullYear();
    this.yearOptions = [];
    for (let i = 0; i < 2; i++) {
      this.yearOptions.push((currentYear + i).toString());
    }
  }

  /** Months valid for a given year: current year hides past months, future years show all. */
  monthsForYear(year: any): string[] {
    if (!year) {
      return [];
    }
    const now = new Date();
    if (Number(year) === now.getFullYear()) {
      return this.monthOptions.slice(now.getMonth());
    }
    return this.monthOptions;
  }

  onEntireYearChange() {
    if (this.entireMonth && !this.monthsForYear(this.entireYear).includes(this.entireMonth)) {
      this.entireMonth = '';
    }
  }

  onNewYearChange() {
    if (this.newMonth && !this.monthsForYear(this.newYear).includes(this.newMonth)) {
      this.newMonth = '';
    }
  }

  openSplit(row: any) {
    this.splitSource = row;
    this.splitRows = [];
    this.planEntireSingleMonth = false;
    this.entireMonth = '';
    this.entireYear = '';
    this.useFgStock = false;
    this.subtractInProcess = false;
    this.newInhouse = 0;
    this.newOutsource = 0;
    this.newMonth = '';
    this.newYear = '';
    this.splitModalOpen = true;
    this.loadSavedSplits(row);
  }

  /** Load previously saved split rows so the user can edit them on reopen. */
  loadSavedSplits(row: any) {
    const orderNo = row?.order_no;
    const productCode = row?.product_code;
    if (!orderNo || !productCode) {
      return;
    }
    this.service
      .get(`marketing/po.php?type=getOrderSplitPlan&order_no=${encodeURIComponent(orderNo)}&product_code=${encodeURIComponent(productCode)}`)
      .subscribe((response: any) => {
        const savedSplits = Array.isArray(response?.splits) ? response.splits : [];
        this.splitRows = savedSplits.map((s: any) => ({
          inhouse: Number(s.inhouse) || 0,
          outsource: Number(s.outsource) || 0,
          produce: Number(s.produce) || 0,
          month: s.month || '',
          year: s.year || '',
        }));
        if (response?.use_fg_stock) {
          this.useFgStock = true;
        }
      });
  }

  get splitOrderQty(): number {
    const r = this.splitSource || {};
    return Number(r.plan_qty ?? r.planQty ?? 0) || 0;
  }

  get splitFgStock(): number {
    const r = this.splitSource || {};
    return Number(r.avblStock ?? r.fg_stock ?? r.available_fg_stock ?? 0) || 0;
  }

  get splitInProcessing(): number {
    const r = this.splitSource || {};
    return Number(r.in_process ?? r.inProcess ?? r.processing_qty ?? 0) || 0;
  }

  get splitPlanned(): number {
    return this.splitRows.reduce((sum, x) => sum + (Number(x.produce) || 0), 0);
  }

  get splitRemainingToAllocate(): number {
    return this.splitOrderQty - this.splitPlanned;
  }

  get splitRemainingForSplit(): number {
    let base = this.splitRemainingToAllocate;
    if (this.useFgStock) {
      base -= this.splitFgStock;
    }
    if (this.useFgStock && this.subtractInProcess) {
      base -= this.splitInProcessing;
    }
    return base > 0 ? base : 0;
  }

  get splitCapForNextRow(): number {
    return this.splitRemainingForSplit;
  }

  get newProduceQty(): number {
    return (Number(this.newInhouse) || 0) + (Number(this.newOutsource) || 0);
  }

  get splitDeliveryDate(): any {
    const r = this.splitSource || {};
    return r.deliveryDate || r.delivery_date || null;
  }

  addSplitRow() {
    const produce = this.newProduceQty;
    if (produce <= 0) {
      alert('Enter Inhouse and/or Outsource quantity.');
      return;
    }
    const month = this.planEntireSingleMonth ? this.entireMonth : this.newMonth;
    const year = this.planEntireSingleMonth ? this.entireYear : this.newYear;
    if (!month || !year) {
      alert('Please select Month and Year.');
      return;
    }
    if (produce > this.splitCapForNextRow) {
      alert('Qty to produce exceeds the remaining for split.');
      return;
    }
    this.splitRows.push({
      inhouse: Number(this.newInhouse) || 0,
      outsource: Number(this.newOutsource) || 0,
      produce,
      month,
      year,
    });
    this.newInhouse = 0;
    this.newOutsource = 0;
    this.newMonth = '';
    this.newYear = '';
  }

  removeSplitRow(index: number) {
    this.splitRows.splice(index, 1);
  }

  saveAndSendSplit() {
    let splits = this.splitRows;

    if (this.planEntireSingleMonth) {
      // Entire order qty planned in a single month — split rows are not required.
      if (!this.entireYear || !this.entireMonth) {
        alert('Please select Year and Month for the entire quantity.');
        return;
      }
      splits = [{
        inhouse: this.splitOrderQty,
        outsource: 0,
        produce: this.splitOrderQty,
        month: this.entireMonth,
        year: this.entireYear,
      }];
    } else {
      if (!this.splitRows.length) {
        alert('Please add at least one split row.');
        return;
      }
      if (this.splitRemainingToAllocate !== 0) {
        if (!confirm('Remaining to allocate is not zero. Do you want to continue?')) {
          return;
        }
      }
    }

    const payload = {
      order_no: this.splitSource?.order_no,
      product_code: this.splitSource?.product_code,
      product_name: this.splitSource?.product_name,
      order_qty: this.splitOrderQty,
      use_fg_stock: this.useFgStock,
      subtract_in_process: this.subtractInProcess,
      plan_entire_single_month: this.planEntireSingleMonth,
      splits,
    };
    this.service.post('marketing/po.php?type=saveOrderSplitPlan', JSON.stringify(payload)).subscribe((response: any) => {
      if (response.status === 'success') {
        alert('Order split & plan sent for next planning successfully');
        this.splitModalOpen = false;
        this.getPendingPOs();
      } else {
        alert('An error has occurred, please try again');
      }
    });
  }

  /** Fetch pending POs and calculate initial batches */

      searchText: string = '';
pendingpoBackup: any[] = [];  
  allVisibleSelected = false;
  selectedVisibleCount = 0;

  applyFilter() {
    const query = (this.searchText || '').toLowerCase().trim();
    this.pendingpo = this.pendingpoBackup.filter((po) => {
      if (!query) {
        return true;
      }
      return JSON.stringify(po).toLowerCase().includes(query);
    });
    this.syncSelectAllState();
  }

  /** Entry user: API returns "Firstname (emp_id)"; fallback to entry_by. */
  entryUserDisplay(row: any, parent?: any): string {
    const label = (row?.emp_name || parent?.emp_name || '').toString().trim();
    if (label) {
      return label;
    }
    const eid = (row?.entry_by || parent?.entry_by || '').toString().trim();
    return eid || '-';
  }

  toggleSelectAll(checked: boolean): void {
    for (const po of this.pendingpo || []) {
      if (po.products?.length) {
        for (const item of po.products) {
          item.selected = checked;
        }
      } else {
        po.selected = checked;
      }
    }
    this.syncSelectAllState();
  }

  onRowSelectionChange(): void {
    this.syncSelectAllState();
  }

  private syncSelectAllState(): void {
    let total = 0;
    let selected = 0;
    for (const po of this.pendingpo || []) {
      if (po.products?.length) {
        for (const item of po.products) {
          total++;
          if (item.selected) {
            selected++;
          }
        }
      } else {
        total++;
        if (po.selected) {
          selected++;
        }
      }
    }
    this.selectedVisibleCount = selected;
    this.allVisibleSelected = total > 0 && selected === total;
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
    return String(value);
  }

  formatBatchSummary(batches: any[] | null | undefined, unit?: string): string {
    if (!batches?.length) {
      return '-';
    }
    return batches
      .map((b) => `${b.count} × ${b.size}${unit || b.unit || ''}`)
      .join(', ');
  }

  getWorkOrderQty(row: any): number {
    const qty = row.plan_qty ?? row.planQty ?? 0;
    return qty - (row.leftover || 0) + (row.excess || 0);
  }

  getBalance(row: any): number {
    const qty = row.plan_qty ?? row.planQty ?? 0;
    return qty - (row.leftover || 0);
  }

  /** Normalize batches JSON and Combi SFG children from API (Unit Formula kit lines, not CombiMaster). */
  private normalizeReceivingRow(po: any): any {
    po.products = (po.products || []).filter((x: any) => x != null);
    const isCombi = !!po.is_combi || po.products.length > 0;
    po.batches = this.normalizeBatches(po.batches);

    if (!isCombi) {
      po.products = [];
      po.is_combi = false;
      if (po.planQty == null && po.plan_qty != null) {
        po.planQty = po.plan_qty;
      }
      if (po.plan_qty == null && po.planQty != null) {
        po.plan_qty = po.planQty;
      }
      return po;
    }

    po.is_combi = true;
    po.batches = [];
    po.products.forEach((item: any) => {
      item.is_combi_child = true;
      item.kit_qty = Number(item.kit_qty ?? item.CombiMaster_dtl_qty) || 1;
      item.batches = this.normalizeBatches(item.batches);
      if (item.planQty == null && item.plan_qty != null) {
        item.planQty = item.plan_qty;
      }
      if (item.plan_qty == null && item.planQty != null) {
        item.plan_qty = item.planQty;
      }
      if (!item.doc_no && po.doc_no) {
        item.doc_no = po.doc_no;
      }
      if (!item.mainGroupName && po.mainGroupName) {
        item.mainGroupName = po.mainGroupName;
      }
    });
    return po;
  }

  private normalizeBatches(batches: any): any[] {
    if (Array.isArray(batches)) {
      return batches;
    }
    if (typeof batches === 'string' && batches && batches !== 'null') {
      try {
        const parsed = JSON.parse(batches);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  getPendingPOs() {
    this.loading = true;
    this.service.get('marketing/po.php?type=getPendingProcessingPOsReceiving').subscribe((response: any) => {
      this.pendingpoBackup = (Array.isArray(response) ? response : []).map((po: any) =>
        this.normalizeReceivingRow(po)
      );
      this.pendingpo = [...this.pendingpoBackup];
      this.applyFilter();
      this.syncSelectAllState();
      this.loading = false;
    });
  }
 
  /** Approve or Reject PO */
updatePendingPOs(status: string, data: any) {
  let temp = data;

  if (status === 'Work Order Processed') {

    if (
      temp['batches'] === "null" ||     // backend returns string "null"
      temp['batches'] === null ||       // real null
      temp['batches'] === undefined ||  // undefined
      (Array.isArray(temp['batches']) && temp['batches'].length === 0) // empty array
    ) {
      alert('Please generate batches before approving the PO.');
      return;
    }

  }

  this.service.post(
    `marketing/po.php?type=updateProceedPendingPOs&status=${status}`,
    JSON.stringify(temp)
  ).subscribe((response: any) => {
    if (response.status === 'success') {
      alert('Plan Approved Successfully');
      this.isView = false;
      this.getPendingPOs();
    } else {
      alert('An error has occurred, please try again');
    }
  });
}
selectedPO=[]
Worders;
view(data: any) {
  this.isView = true;
  this.selectedPO = data;


      this.loading = true;
        this.service.get('marketing/po.php?type=Get_Generated_wo&order_no='+this.selectedPO['order_no']).subscribe(response => {
      this.Worders = response;
       this.loading = false;
    });
    
}
  /** Collect checked rows from master list (not filtered view). */
  collectSelectedItems(): any[] {
    const selected: any[] = [];
    for (const po of this.pendingpoBackup) {
      if (po.products?.length) {
        for (const item of po.products) {
          if (item.selected) {
            selected.push(item);
          }
        }
      } else if (po.selected) {
        selected.push(po);
      }
    }
    return selected;
  }

  hasValidBatches(item: any): boolean {
    const batches = item?.batches;
    return Array.isArray(batches) && batches.length > 0;
  }

  submitApproval() {
    const selectedItems = this.collectSelectedItems();
    if (selectedItems.length === 0) {
      alert('Please select at least one order to approve.');
      return;
    }

    const missingBatches = selectedItems.filter(item => !this.hasValidBatches(item));
    if (missingBatches.length > 0) {
      const labels = missingBatches
        .map(item => item.order_no || item.product_code || 'Unknown')
        .join(', ');
      alert(`Please generate batches before approving: ${labels}`);
      return;
    }

    const orderNos = [...new Set(
      selectedItems.map(item => item.order_no).filter(Boolean)
    )];
    if (orderNos.length === 0) {
      alert('Selected rows are missing order numbers. Please refresh and try again.');
      return;
    }

    const approvalData = {
      orders: orderNos,
      status: 'Work Order Generation Done'
    };

    this.service.post(
      `marketing/po.php?type=updateProceed_Wo_Gen&status=Work Order Processed`,
      JSON.stringify(approvalData)
    ).subscribe((response: any) => {
      if (response.status === 'success') {
        alert('Selected Orders Approved Successfully');
        this.isView = false;
        this.clearAllSelections();
        this.getPendingPOs();
      } else {
        alert('An error has occurred, please try again');
      }
    });
  }

  clearAllSelections() {
    for (const po of this.pendingpoBackup) {
      po.selected = false;
      if (po.products?.length) {
        for (const item of po.products) {
          item.selected = false;
        }
      }
    }
    this.syncSelectAllState();
  }

}
