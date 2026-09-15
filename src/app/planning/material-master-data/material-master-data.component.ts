import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
import * as FileSaver from 'file-saver';

declare let alertify: any;

@Component({
  selector: 'app-material-master-data',
  templateUrl: './material-master-data.component.html',
  styleUrls: ['./material-master-data.component.css'],
})
export class MaterialMasterDataComponent implements OnInit {
  loading = false;
  results: any[] = [];
  total = 0;
  page = 1;
  pageSize = 25;
  pages = 1;
  search = '';
  materialType = 'RM_PM';

  selected: { [code: string]: any } = {};
  selectAllPage = false;

  isEdit = false;
  isBulk = false;
  editRow: any = null;

  // form days
  indent_approval = 0;
  po_after_indent = 0;
  payment_days = 0;
  vendor_lead_days = 0;
  sampling_days = 0;
  testing_days = 0;
  doc_days = 0;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadList();
  }

  get selectedCount(): number {
    return Object.keys(this.selected).length;
  }

  get selectedList(): any[] {
    return Object.keys(this.selected).map((k) => this.selected[k]);
  }

  get totalReceiving(): number {
    return (
      this.n(this.indent_approval) +
      this.n(this.po_after_indent) +
      this.n(this.payment_days) +
      this.n(this.vendor_lead_days)
    );
  }

  get indentBeforeRecv(): number {
    return this.totalReceiving;
  }

  get purchaseLeadDays(): number {
    return this.totalReceiving;
  }

  get totalRelease(): number {
    return this.n(this.sampling_days) + this.n(this.testing_days) + this.n(this.doc_days);
  }

  get forecastTotal(): number {
    return this.totalReceiving + this.totalRelease;
  }

  n(v: any): number {
    const x = Number(v);
    return isNaN(x) || x < 0 ? 0 : Math.floor(x);
  }

  loadList(): void {
    this.loading = true;
    const q =
      'planning/material_master_data.php?type=getList' +
      '&page=' +
      this.page +
      '&pageSize=' +
      this.pageSize +
      '&search=' +
      encodeURIComponent(this.search || '') +
      '&material_type=' +
      encodeURIComponent(this.materialType || 'RM_PM');
    this.service.get(q).subscribe(
      (res: any) => {
        this.results = Array.isArray(res?.data) ? res.data : [];
        this.total = Number(res?.total || 0);
        this.pages = Math.max(1, Number(res?.pages || 1));
        this.selectAllPage = false;
        this.loading = false;
      },
      () => {
        this.results = [];
        this.total = 0;
        this.loading = false;
        alertify.error('Failed to load materials');
      }
    );
  }

  onSearch(): void {
    this.page = 1;
    this.loadList();
  }

  onTypeChange(): void {
    this.page = 1;
    this.loadList();
  }

  onPageSizeChange(): void {
    this.page = 1;
    this.loadList();
  }

  goFirst(): void {
    this.page = 1;
    this.loadList();
  }
  goPrev(): void {
    if (this.page > 1) {
      this.page--;
      this.loadList();
    }
  }
  goNext(): void {
    if (this.page < this.pages) {
      this.page++;
      this.loadList();
    }
  }
  goLast(): void {
    this.page = this.pages;
    this.loadList();
  }

  toggleRow(row: any, checked: boolean): void {
    const code = row.material_code;
    if (!code) {
      return;
    }
    if (checked) {
      this.selected[code] = {
        material_code: code,
        material_type: row.material_type,
        material_name: row.material_name,
      };
    } else {
      delete this.selected[code];
    }
    this.selectAllPage = this.results.every((r) => !!this.selected[r.material_code]);
  }

  toggleSelectAll(checked: boolean): void {
    this.selectAllPage = checked;
    this.results.forEach((r) => this.toggleRow(r, checked));
  }

  isSelected(row: any): boolean {
    return !!this.selected[row.material_code];
  }

  resetFormDays(): void {
    this.indent_approval = 0;
    this.po_after_indent = 0;
    this.payment_days = 0;
    this.vendor_lead_days = 0;
    this.sampling_days = 0;
    this.testing_days = 0;
    this.doc_days = 0;
  }

  openEdit(row: any): void {
    this.editRow = row;
    this.indent_approval = this.n(row.indent_approval);
    this.po_after_indent = this.n(row.po_after_indent);
    this.payment_days = this.n(row.payment_days);
    this.vendor_lead_days = this.n(row.vendor_lead_days);
    this.sampling_days = this.n(row.sampling_days);
    this.testing_days = this.n(row.testing_days);
    this.doc_days = this.n(row.doc_days);
    this.isBulk = false;
    this.isEdit = true;
  }

  openBulk(): void {
    if (this.selectedCount < 1) {
      return;
    }
    this.editRow = null;
    this.resetFormDays();
    this.isBulk = true;
    this.isEdit = true;
  }

  cancelEdit(): void {
    this.isEdit = false;
    this.isBulk = false;
    this.editRow = null;
  }

  buildPayload(): any {
    return {
      indent_approval: this.n(this.indent_approval),
      po_after_indent: this.n(this.po_after_indent),
      payment_days: this.n(this.payment_days),
      vendor_lead_days: this.n(this.vendor_lead_days),
      sampling_days: this.n(this.sampling_days),
      testing_days: this.n(this.testing_days),
      doc_days: this.n(this.doc_days),
    };
  }

  save(): void {
    if (this.isBulk) {
      const body = {
        ...this.buildPayload(),
        materials: this.selectedList,
      };
      this.service
        .post('planning/material_master_data.php?type=saveBulk', JSON.stringify(body))
        .subscribe((res: any) => {
          if (res?.status === 'success') {
            alertify.success(res.message || 'Material timeline updated');
            this.selected = {};
            this.selectAllPage = false;
            this.cancelEdit();
            this.loadList();
          } else {
            alertify.error(res?.message || 'Save failed');
          }
        });
      return;
    }

    if (!this.editRow?.material_code) {
      return;
    }
    const body = {
      ...this.buildPayload(),
      material_code: this.editRow.material_code,
      material_type: this.editRow.material_type,
    };
    this.service
      .post('planning/material_master_data.php?type=saveOne', JSON.stringify(body))
      .subscribe((res: any) => {
        if (res?.status === 'success') {
          alertify.success('Material timeline updated');
          this.cancelEdit();
          this.loadList();
        } else {
          alertify.error(res?.message || 'Save failed');
        }
      });
  }

  exportPage(): void {
    if (!this.results.length) {
      alertify.error('No rows to export');
      return;
    }
    const rows = this.results.map((r, i) => ({
      Sr: (this.page - 1) * this.pageSize + i + 1,
      'Total Purchase Lead Time': r.purchase_lead_days,
      'Material Code': r.material_code,
      'Material Name': r.material_name,
      Grade: r.grade,
      Type: r.type_bucket,
      'Indent Bfr Recv.': r.indent_before_recv,
      'Indent Approval': r.indent_approval,
      'PO After Indent': r.po_after_indent,
      'Payment After PO': r.payment_days,
      'Vendor Transit': r.vendor_lead_days,
      'Total Receiving': r.total_receiving,
      Sampling: r.sampling_days,
      Testing: r.testing_days,
      'Doc. & Release': r.doc_days,
      'Total Release': r.total_release,
      'Forecast Total': r.forecast_total,
    }));
    const sheet = XLSX.utils.json_to_sheet(rows);
    const book = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(book, sheet, 'Material Master Data');
    const buf = XLSX.write(book, { bookType: 'xlsx', type: 'array' });
    FileSaver.saveAs(
      new Blob([buf], { type: 'application/octet-stream' }),
      'material-master-data-page' + this.page + '.xlsx'
    );
  }
}
