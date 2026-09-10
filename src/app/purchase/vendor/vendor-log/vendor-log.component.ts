import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';

function na(val: any): string {
  return val != null && String(val).trim() !== '' ? String(val) : 'NA';
}

@Component({
  selector: 'app-vendor-log',
  templateUrl: './vendor-log.component.html',
  styleUrls: ['./vendor-log.component.css'],
})
export class VendorLogComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getLogs();
  }

  results: any[] = [];
  loading = false;
  searchQuery = '';
  vendorType = '';
  materialType = '';
  vendorTypes: string[] = [];
  materialTypes: string[] = [];
  pageSize = 10;
  page = 1;
  total = 0;
  private searchTimer: any;
  private allRowsFallback: any[] | null = null;

  isView = false;
  /** Detail row with normalized JSON arrays */
  selectedResult: any = {};

  get totalPages(): number {
    return Math.max(1, Math.ceil(this.total / this.pageSize));
  }

  get pageStart(): number {
    return this.total === 0 ? 0 : (this.page - 1) * this.pageSize + 1;
  }

  get pageEnd(): number {
    return Math.min(this.page * this.pageSize, this.total);
  }

  rowNumber(index: number): number {
    return (this.page - 1) * this.pageSize + index + 1;
  }

  onSearchChange(): void {
    if (this.searchTimer) {
      clearTimeout(this.searchTimer);
    }
    this.searchTimer = setTimeout(() => {
      this.page = 1;
      this.getLogs();
    }, 350);
  }

  onFilterChange(): void {
    this.page = 1;
    this.getLogs();
  }

  onPageSizeChange(): void {
    this.page = 1;
    this.getLogs();
  }

  goToPage(p: number): void {
    const next = Math.min(this.totalPages, Math.max(1, p));
    if (next === this.page) {
      return;
    }
    this.page = next;
    this.getLogs();
  }

  private listUrl(exportAll = false): string {
    const params = [
      'type=getApprovedVendors',
      'page=' + encodeURIComponent(String(this.page)),
      'pageSize=' + encodeURIComponent(String(this.pageSize)),
      'q=' + encodeURIComponent((this.searchQuery || '').trim()),
      'vendor_type=' + encodeURIComponent(this.vendorType || ''),
      'material_type=' + encodeURIComponent(this.materialType || ''),
    ];
    if (exportAll) {
      params.push('export=1');
    }
    return 'purchase/vendor.php?' + params.join('&');
  }

  getLogs() {
    this.loading = true;
    this.service.get(this.listUrl(false)).subscribe(
      (response: any) => {
        this.applyListResponse(response, false);
        this.loading = false;
      },
      () => { this.loading = false; }
    );
  }

  private applyListResponse(response: any, forExport: boolean): any[] {
    if (Array.isArray(response)) {
      this.allRowsFallback = response;
      this.vendorTypes = this.uniqueField(response, 'vendor_type');
      this.materialTypes = this.uniqueField(response, 'material_type');
      const filtered = this.clientFilter(response);
      this.total = filtered.length;
      if (forExport) {
        return filtered;
      }
      const start = (this.page - 1) * this.pageSize;
      this.results = filtered.slice(start, start + this.pageSize);
      return this.results;
    }
    this.allRowsFallback = null;
    const rows = Array.isArray(response?.rows) ? response.rows : [];
    if (Array.isArray(response?.vendor_types) && response.vendor_types.length) {
      this.vendorTypes = response.vendor_types;
    }
    if (Array.isArray(response?.material_types) && response.material_types.length) {
      this.materialTypes = response.material_types;
    }
    this.total = Number(response?.total) || rows.length;
    if (forExport) {
      return rows;
    }
    this.results = rows;
    return rows;
  }

  private uniqueField(rows: any[], key: string): string[] {
    const set = new Set<string>();
    rows.forEach((r) => {
      const v = r && r[key] != null ? String(r[key]).trim() : '';
      if (v) {
        set.add(v);
      }
    });
    return Array.from(set).sort();
  }

  private clientFilter(rows: any[]): any[] {
    const q = (this.searchQuery || '').toLowerCase().trim();
    return rows.filter((row) => {
      if (this.vendorType && String(row.vendor_type || '') !== this.vendorType) {
        return false;
      }
      if (this.materialType && String(row.material_type || '') !== this.materialType) {
        return false;
      }
      if (!q) {
        return true;
      }
      return Object.entries(row).some(([, value]) =>
        value != null && String(value).toLowerCase().includes(q)
      );
    });
  }

  view(data: any): void {
    const row = { ...data };
    row.other_contact = this.parseJsonArray(row.other_contact);
    row.selectedCurrencies = this.parseJsonArray(row.selectedCurrencies ?? row.currency);
    this.selectedResult = row;
    this.isView = true;
  }

  private parseJsonArray(value: any): any[] {
    if (Array.isArray(value)) {
      return value;
    }
    if (typeof value === 'string' && value.trim()) {
      try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  regionLabel(country: string): string {
    if (country === 'Canada') {
      return 'Province / territory';
    }
    if (country === 'India') {
      return 'State';
    }
    return 'State / province';
  }

  postalLabel(_country?: string): string {
    return 'Postal Code';
  }

  /** India-only tax IDs (hidden for Canada and other countries) */
  showVendorIndiaTax(v: any): boolean {
    if (!v || v.country !== 'India') {
      return false;
    }
    const g = (x: any) => (x != null && String(x).trim() !== '');
    return g(v.gst_applicable) || g(v.scode) || g(v.gst_no) || g(v.panNo);
  }

  showCorporateIndiaTax(v: any): boolean {
    if (!v || v.c_country !== 'India') {
      return false;
    }
    const g = (x: any) => (x != null && String(x).trim() !== '');
    return g(v.c_gst_applicable) || g(v.c_scode) || g(v.c_gst_no) || g(v.c_panNo);
  }

  currencyCodesDisplay(v: any): string {
    if (!v) {
      return '—';
    }
    const arr = Array.isArray(v.selectedCurrencies)
      ? v.selectedCurrencies
      : this.parseJsonArray(v.selectedCurrencies ?? v.currency);
    if (!arr.length) {
      return '—';
    }
    const codes = arr.map((x: any) => (x && x.code) ? x.code : x).filter(Boolean);
    return codes.length ? codes.join(', ') : '—';
  }

  exportToExcel(): void {
    this.loading = true;
    this.service.get(this.listUrl(true)).subscribe(
      (response: any) => {
        const rows = this.applyListResponse(response, true);
        this.buildExcel(rows);
        this.loading = false;
        if (this.allRowsFallback == null) {
          this.getLogs();
        }
      },
      () => { this.loading = false; }
    );
  }

  private buildExcel(list: any[]): void {
    const headers = [
      'Sr.No', 'Vendor No.', 'Vendor Name', 'Vendor/Division', 'Parent Vendor Name', 'Vendor Type',
      'Contact Person', 'Email Id', 'Qualified By', 'Vendor For', 'Material Type', 'Status',
      'Address', 'Country', 'State', 'City', 'Postal Code',
    ];
    const rows = list.map((ven, index) => [
      index + 1,
      na(ven.vendor_no),
      na(ven.vendor_name),
      na(ven.vendor_Is),
      na(ven.parentVenName),
      na(ven.vendor_type),
      na(ven.contact_person || ven.contact_number),
      na(ven.contact_email),
      na(ven.qualifiedBy),
      na(ven.vendorFor),
      na(ven.material_type),
      na(ven.status),
      na(ven.address),
      na(ven.country),
      na(ven.permanent_state),
      na(ven.city),
      na(ven.pincode),
    ]);

    const headerColors = [
      'FF0d9488', 'FF0ea5e9', 'FF7c3aed', 'FFdc2626', 'FF16a34a', 'FFca8a04', 'FF2563eb', 'FFdb2777',
      'FF059669', 'FF4f46e5', 'FFea580c', 'FF0891b2', 'FF65a30d', 'FFbe185d', 'FF0d9488', 'FF0ea5e9',
      'FF7c3aed', 'FFdc2626', 'FF16a34a', 'FFca8a04', 'FF2563eb', 'FFdb2777', 'FF059669', 'FF4f46e5',
      'FFea580c', 'FF0891b2',
    ];
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Approved Vendor Log', { views: [{ rightToLeft: false }] });
    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell, colNumber) => {
      const colorIndex = colNumber - 1;
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: headerColors[colorIndex] || 'FF64748b' },
      };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = {
        top: { style: 'thin' },
        bottom: { style: 'thin' },
        left: { style: 'thin' },
        right: { style: 'thin' },
      };
    });
    ws.addRows(rows);
    const lastCol = headers.length;
    ws.getRow(1).height = 22;
    /* Column widths to avoid overlap: min 8, wider for Address/Email/Names */
    const colWidths = [6, 10, 14, 12, 14, 10, 22, 20, 16, 24, 36, 12, 12, 12, 12, 14, 14, 12, 8, 16, 12, 14, 18, 18, 18];
    ws.columns.forEach((col, i) => {
      if (i < lastCol) col.width = colWidths[i] ?? 14;
    });
    /* Style data rows: wrap text, borders, alignment; set row height per row to avoid overlapping */
    const thinBorder = { top: { style: 'thin' as const }, bottom: { style: 'thin' as const }, left: { style: 'thin' as const }, right: { style: 'thin' as const } };
    const pointsPerLine = 14;
    const minRowHeight = 22;
    const maxRowHeight = 120;
    for (let r = 2; r <= rows.length + 1; r++) {
      const row = ws.getRow(r);
      const rowData = rows[r - 2] as (string | number)[];
      let maxLines = 1;
      rowData.forEach((val, c) => {
        const str = val != null ? String(val) : '';
        const colW = colWidths[c] ?? 14;
        const charsPerLine = Math.max(1, Math.floor(colW * 1.8));
        const lines = Math.max(1, Math.ceil(str.length / charsPerLine));
        if (lines > maxLines) maxLines = lines;
      });
      const rowHeight = Math.min(maxRowHeight, Math.max(minRowHeight, maxLines * pointsPerLine));
      row.height = rowHeight;
      row.eachCell((cell) => {
        cell.alignment = { vertical: 'top', horizontal: 'left', wrapText: true };
        cell.border = thinBorder;
      });
    }

    wb.xlsx.writeBuffer().then((buffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'approved_vendor_log.xlsx';
      a.click();
      URL.revokeObjectURL(a.href);
    });
  }

}
