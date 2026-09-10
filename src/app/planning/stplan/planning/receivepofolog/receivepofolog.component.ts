import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-receivepofolog',
  templateUrl: './receivepofolog.component.html',
  styleUrls: ['./receivepofolog.component.css']
})
export class ReceivepofologComponent implements OnInit {
  pendingpo: any = [];
  loading = false;
  searchText = '';
  pendingpoBackup: any[] = [];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingPOs();
  }

  applyFilter() {
    const query = (this.searchText || '').toLowerCase();
    this.pendingpo = this.pendingpoBackup.filter(po =>
      JSON.stringify(po).toLowerCase().includes(query)
    );
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

  /** Same Combi SFG structure as Receive FO/PO (API is_combi + nested products). */
  private normalizeLogRow(po: any): any {
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

  getPendingPOs() {
    this.loading = true;
    this.service.get('marketing/po.php?type=getPendingProcessingPOsReceivingLog').subscribe((response: any) => {
      this.pendingpoBackup = (Array.isArray(response) ? response : []).map((po: any) =>
        this.normalizeLogRow(po)
      );
      this.pendingpo = [...this.pendingpoBackup];
      this.applyFilter();
      this.loading = false;
    });
  }
}
