import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  pendingpo: any[] = [];
  pendingpoBackup: any[] = [];
  loading = false;
  searchText = '';
  newFoEntryType: 'FO Service' | 'FO Product' = 'FO Service';

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingPOs();
  }

  applyFilter() {
    const query = this.searchText.toLowerCase().trim();
    if (!query) {
      this.pendingpo = [...this.pendingpoBackup];
      return;
    }
    this.pendingpo = this.pendingpoBackup.filter((po) =>
      this.buildSearchHaystack(po).includes(query)
    );
  }

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
        parts.push(`${mm}-${yyyy}`);
      }
    };

    const walk = (value: any): void => {
      if (value == null || value === '') {
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

    return parts.join(' ').toLowerCase();
  }

  getPendingPOs() {
    this.loading = true;
    this.service.get('marketing/po_entry_flow.php?type=getPendingProcessingPOslog').subscribe(
      (response: any) => {
        const rows = Array.isArray(response) ? response : [];
        this.pendingpo = rows.map((po: any) => this.normalizePoRow(po));
        this.pendingpoBackup = [...this.pendingpo];
        this.loading = false;
      },
      () => {
        this.pendingpo = [];
        this.pendingpoBackup = [];
        this.loading = false;
      }
    );
  }

  getRowPlanQty(row: any): number {
    return Number(row?.plan_qty ?? row?.planQty ?? 0) || 0;
  }

  getRowWorkorderQty(row: any): number {
    const plan = this.getRowPlanQty(row);
    const leftover = Number(row?.leftover ?? 0) || 0;
    const excess = Number(row?.excess ?? 0) || 0;
    const qty = plan - leftover + excess;
    return Math.round((qty + Number.EPSILON) * 1000) / 1000;
  }

  getRowBalance(row: any): number {
    const balance = this.getRowPlanQty(row) - this.getRowWorkorderQty(row);
    return Math.round((balance + Number.EPSILON) * 1000) / 1000;
  }

  private normalizePoRow(po: any): any {
    const row = { ...po };
    row.fo_entry_type = row.fo_entry_type || row.client_type || '';
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
    const planQty = Number(row?.plan_qty ?? row?.planQty ?? 0) || 0;
    return !productCode && planQty <= 0;
  }

  getFoEntryTypeLabel(row: any): string {
    return this.isFoServiceRow(row) ? 'FO Service' : 'FO Product';
  }

  getServiceCategoryLabel(row: any): string {
    return (row?.serviceCategory || '-').toString().trim() || '-';
  }

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

  downloadpo(doc_url: string) {
    if (!doc_url || doc_url === 'NA') {
      return;
    }
    const url = this.service.url + '../../upload/poentry/' + doc_url;
    window.open(url, '_blank');
  }
}
