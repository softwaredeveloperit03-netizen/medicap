import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  pendingpo: any = [];
  loading = false;

  isView = false;
  selectedResult: any[] = [];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingPOs();
  }

  searchText: string = '';
  pendingpoBackup: any[] = [];
  newFoEntryType: 'FO Service' | 'FO Product' = 'FO Product';

  /** Flatten a PO row for free-text search. */
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
    pushDate(record?.po_date);
    pushDate(record?.planMonth);
    pushDate(record?.deliveryDate);
    parts.push(String(this.getRowWorkorderQty(record)));
    parts.push(String(this.getRowBalance(record)));

    return parts.join(' ').toLowerCase();
  }

  applyFilter() {
    const query = this.searchText.toLowerCase().trim();
    const wantService = this.newFoEntryType === 'FO Service';

    this.pendingpo = this.pendingpoBackup.filter((po) => {
      if (this.isFoServiceRow(po) !== wantService) {
        return false;
      }
      if (query && !this.buildSearchHaystack(po).includes(query)) {
        return false;
      }
      return true;
    });
  }

  onFoTypeFilterChange(): void {
    this.applyFilter();
  }

  /** Fetch POs awaiting approval (po_entry status Pending). */
  getPendingPOs() {
    this.loading = true;
    this.service.get('marketing/po_entry_flow.php?type=getPendingPOsForApproval').subscribe(
      (response: any) => {
        const rows = Array.isArray(response) ? response : [];
        this.pendingpo = rows.map((po: any) => this.normalizePoRow(po));
        this.pendingpoBackup = [...this.pendingpo];
        this.applyFilter();
        this.loading = false;
      },
      () => {
        this.pendingpo = [];
        this.pendingpoBackup = [];
        this.loading = false;
      }
    );
  }

  /** Approve, reject, or hold a PO sent from Processing. */
  updatePendingPOs(status: string, data: any) {
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

    const statusMap: Record<string, string> = {
      'Work Order Preparation Approved': 'Approved',
      'Rejected': 'Rejected',
      'Hold': 'Hold',
    };
    const apiStatus = statusMap[status] || status;
    const empId = (localStorage.getItem('emp_id') || '').trim();

    const url =
      'marketing/po_entry_flow.php?type=updatePendingPoApproval' +
      '&id=' + encodeURIComponent(String(poId)) +
      '&status=' + encodeURIComponent(apiStatus) +
      '&remark=' + encodeURIComponent(String(remark).trim()) +
      (empId ? '&digital_signature=' + encodeURIComponent(empId) : '');

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

  /** Plan quantity for a row. */
  getRowPlanQty(row: any): number {
    return Number(row?.plan_qty ?? row?.planQty ?? 0) || 0;
  }

  /** Quantity planned through batches = plan − leftover + excess. */
  getRowWorkorderQty(row: any): number {
    const plan = this.getRowPlanQty(row);
    const leftover = Number(row?.leftover ?? 0) || 0;
    const excess = Number(row?.excess ?? 0) || 0;
    const qty = plan - leftover + excess;
    return Math.round((qty + Number.EPSILON) * 1000) / 1000;
  }

  /** Remaining balance = Plan Qty − Workorder Qty. */
  getRowBalance(row: any): number {
    const balance = this.getRowPlanQty(row) - this.getRowWorkorderQty(row);
    return Math.round((balance + Number.EPSILON) * 1000) / 1000;
  }

  private normalizePoRow(po: any): any {
    const row = { ...po };
    row.doc_no = row.doc_no || row.id;
    row.fo_entry_type = row.fo_entry_type || row.client_type || '';
    row.proceed_approved_by = row.proceed_approved_by || row.processing_by_name || '';
    row.isFoService = this.isFoServiceRow(row);
    if (typeof row.batches === 'string') {
      try {
        row.batches = JSON.parse(row.batches);
      } catch {
        row.batches = [];
      }
    }
    row.batches = Array.isArray(row.batches) ? row.batches : [];
    row.leftover = Number(row.leftover) || 0;
    row.excess = Number(row.excess) || 0;
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

  getFoEntryTypeLabel(row: any): string {
    return this.isFoServiceRow(row) ? 'FO Service' : 'FO Product';
  }

  getServiceCategoryLabel(row: any): string {
    return (row?.serviceCategory || '-').toString().trim() || '-';
  }

  /** Format plan month for grid. */
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

  /** Download PO document. */
  downloadpo(doc_url: string) {
    if (!doc_url || doc_url === 'NA') {
      return;
    }
    const url = this.service.url + '../../upload/poentry/' + doc_url;
    window.open(url, '_blank');
  }

}
