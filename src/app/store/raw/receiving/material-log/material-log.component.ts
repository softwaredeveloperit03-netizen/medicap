import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

declare let alertify;

@Component({
  selector: 'app-material-log',
  templateUrl: './material-log.component.html',
  styleUrls: ['./material-log.component.css'],
  providers: [DatePipe],
})
export class MaterialLogComponent implements OnInit {
  material_type = 'Raw Material';
  results: any[] = [];
  loading = false;
  searchQuery = '';
  from_date = '';
  to_date = '';
  pageSizeOptions: number[] = [10, 20, 30, 50, 100];
  pageSize = 20;
  currentPage = 1;
  Math = Math;

  constructor(
    private service: DataAccessService,
    private datePipe: DatePipe
  ) {}

  ngOnInit(): void {
    this.initDates();
    this.getMaterialLog();
  }

  private initDates(): void {
    const today = new Date();
    this.to_date = today.toISOString().slice(0, 10);
    const from = new Date();
    from.setMonth(from.getMonth() - 3);
    this.from_date = from.toISOString().slice(0, 10);
  }

  getMaterialLog(): void {
    this.loading = true;
    const mt = encodeURIComponent(this.material_type || '');
    const from = encodeURIComponent(this.from_date || '');
    const to = encodeURIComponent(this.to_date || '');
    this.service
      .get(
        'store/raw.php?type=getReceivingMaterialLog&material_type=' +
          mt +
          '&from_date=' +
          from +
          '&to_date=' +
          to
      )
      .subscribe(
        (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        () => {
          this.results = [];
          this.loading = false;
        }
      );
  }

  onFiltersChanged(): void {
    this.currentPage = 1;
  }

  onPageSizeChange(value: any): void {
    const size = Number(value);
    this.pageSize = Number.isFinite(size) && size > 0 ? size : 20;
    this.currentPage = 1;
  }

  getSrNo(index: number): number {
    return (this.currentPage - 1) * this.pageSize + index + 1;
  }

  formatReceivingNo(value: any): string {
    if (value == null || value === '') {
      return '-';
    }
    const str = String(value).trim();
    const parts = str.split('/');
    const lastPart = parts.length ? parts[parts.length - 1] : str;
    const num = lastPart.replace(/\D/g, '');
    if (num) {
      return num.padStart(6, '0');
    }
    const allDigits = str.replace(/\D/g, '');
    if (allDigits) {
      return allDigits.slice(-6).padStart(6, '0');
    }
    return str;
  }

  formatLotNumber(row: any): string {
    const lot = row?.lot_number ?? row?.batch_no ?? '';
    return lot ? String(lot).trim() : '-';
  }

  formatQuantity(row: any): string {
    const qty = row?.qty_received;
    const unit = String(row?.unit ?? '').trim();
    if (qty == null || qty === '') {
      return '-';
    }
    return unit ? `${qty} ${unit}` : String(qty);
  }

  getAssignedInitials(row: any): string {
    const name = String(row?.received_by_name ?? '').trim();
    if (name) {
      const parts = name.split(/\s+/).filter(Boolean);
      if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
      }
      return name.length >= 2 ? name.substring(0, 2).toUpperCase() : name.toUpperCase();
    }
    const initials = String(row?.assigned_by_initials ?? '').trim();
    if (initials) {
      return initials;
    }
    const receivedBy = String(row?.received_by ?? '').trim();
    return receivedBy || '-';
  }

  formatDate(value: any): string {
    if (value == null || value === '') {
      return '-';
    }
    const d = value instanceof Date ? value : new Date(value);
    if (isNaN(d.getTime())) {
      return String(value);
    }
    return this.datePipe.transform(d, 'dd-MM-yyyy') || '-';
  }

  formatAssignedBy(row: any): string {
    return `${this.getAssignedInitials(row)} / ${this.formatDate(row?.receiving_date)}`;
  }

  get filteredRows(): any[] {
    if (!Array.isArray(this.results) || !this.results.length) {
      return [];
    }
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }
    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((row) =>
      Object.values(row).some((value) => {
        if (value == null) {
          return false;
        }
        return String(value).toLowerCase().includes(query);
      })
    );
  }

  exportPdf(): void {
    const mt = encodeURIComponent(this.material_type || '');
    const from = encodeURIComponent(this.from_date || '');
    const to = encodeURIComponent(this.to_date || '');
    this.service.open(
      'store/raw.php?type=receivingMaterialLogPDF&material_type=' +
        mt +
        '&from_date=' +
        from +
        '&to_date=' +
        to
    );
  }

  exportCsv(): void {
    const rows = this.filteredRows;
    if (!rows.length) {
      if (typeof alertify !== 'undefined') {
        alertify.warning('No data to export');
      }
      return;
    }

    const header = [
      'Sr.No',
      'Lot Number',
      'Code Number',
      'Name of Material / Description',
      'Quantity',
      'Receiving Number',
      'Supplier Name',
      'Supplier Lot No.',
      'Assigned By (Initial / Date)',
    ];

    const lines = [header.join(',')];
    rows.forEach((row, i) => {
      const line = [
        i + 1,
        this.formatLotNumber(row),
        row.material_code || '',
        row.material_name || '',
        this.formatQuantity(row),
        this.formatReceivingNo(row.receiving_no),
        row.vendor_name || '',
        row.supplier_batch_no || '',
        this.formatAssignedBy(row),
      ].map((cell) => `"${String(cell).replace(/"/g, '""')}"`);
      lines.push(line.join(','));
    });

    const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `receiving_material_log_${this.datePipe.transform(new Date(), 'yyyyMMdd')}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  }
}
