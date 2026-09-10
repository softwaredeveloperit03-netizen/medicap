import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
import * as ExcelJS from 'exceljs';
import { buildChecklistReadonlyRows, hasChecklistFooterRow, resolveReceivingChecklistRows, resolveReceivingDamageChecklistRows } from 'src/app/master/checklist/checklist-shared';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe],
})
export class LogComponent implements OnInit {
  isView = false;
  entrySignature: any = {};
  checkedSignature: any = {};

  plant_id: any;

  constructor(
    private service: DataAccessService,
    private datePipe: DatePipe
  ) {}

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    this.getReceivingLog();
  }

  material_type = 'Raw Material';
  results: any[] = [];
  loading = false;

  getReceivingLog() {
    this.loading = true;
    const mt = encodeURIComponent(this.material_type || '');
    this.service
      .get('store/raw.php?type=getReceivingLog&material_type=' + mt)
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

  selectedReport: any = {};

  view(data: any) {
    this.selectedReport = data;
    this.resetDamageDetailsPanel();
    this.prepareDigitalSignatures();
    this.getChkListData(this.selectedReport['receiving_no']);
    this.isView = true;
    this.getUploadChallans();
  }

  uploadedFileNames: any;

  getUploadChallans() {
    this.service
      .get(
        'store/challan.php?type=getUploadedChallans&ch_no=' +
          this.selectedReport['ch_no'] +
          '&po_no=' +
          this.selectedReport['po_no'] +
          '&vendor_no=' +
          this.selectedReport['vendor_no']
      )
      .subscribe((response) => {
        this.uploadedFileNames = response;
      });
  }

  viewFile(url1: string) {
    const url = this.service.url + '../../upload/challan/' + url1 + '?v=1';
    window.open(url, '_blank');
  }

  viewCoafile(url: string) {
    const full = this.service.url + '../../upload/coa/' + url;
    window.open(full, '_blank');
  }

  downloadPDF(sign: string) {
    this.service.open(
      'store/raw.php?type=receivingMaterialPDF&pdfsign=' +
        sign +
        '&id=' +
        this.selectedReport['id']
    );
  }

  printLabel(row?: any, batch?: any) {
    const data = row || this.selectedReport;
    if (!data || !data.id) {
      return;
    }
    let url =
      'store/raw.php?type=printReceivingMaterialLabel&id=' + data.id;
    if (batch && batch.id) {
      url += '&batch_id=' + batch.id;
    }
    this.service.open(url);
  }

  /** Report title matching card header: "Received {material_type} Log" */
  private getLogReportTitle(): string {
    return `Received ${this.material_type || ''} Log`.trim();
  }

  private formatDate(value: any): string {
    if (value == null || value === '') {
      return '';
    }
    const d = value instanceof Date ? value : new Date(value);
    if (isNaN(d.getTime())) {
      return String(value);
    }
    return this.datePipe.transform(d, 'dd-MM-yyyy') || '';
  }

 

  /**
   * Excel export of the same log grid with merged title row (header text from log screen).
   */
  exportLogExcel(): void {
    const rows = this.filteredMaterials || [];
    if (!rows.length) {
      if (typeof alertify !== 'undefined') {
        alertify.warning('No data to export');
      }
      return;
    }

    const title = this.getLogReportTitle();
    const header = [
      'Sr.No',
      'Receiving No./Dt.',
      'Material Type',
      'Inward Dt.',
      'PO No./Dt.',
      'Payment Slip No./Dt.',
      'Vendor Name',
      'Material Name',
      'Material Code',
      'Received By/Dt.',
      'Billing Qty.',
      'Short Qty.',
      'Damage Qty.',
      'Received Qty.',
    ];

    const dataRows = rows.map((r: any, i: number) => [
      i + 1,
      `${r.receiving_no ?? ''} / ${this.formatDate(r.receiving_date)}`,
      r.material_type ?? '',
      this.formatDate(r.inward_date),
      `${r.po_no ?? ''} / ${this.formatDate(r.po_date)}`,
      `${r.ch_no ?? ''} / ${this.formatDate(r.challan_date)}`,
      `${r.vendor_name ?? ''} - ${r.vendor_no ?? ''}`,
      r.material_name ?? '',
      r.material_code ?? '',
      `${r.received_by ?? ''} / ${this.formatDate(r.receiving_date)}`,
      `${r.qty ?? ''} ${r.unit ?? ''}`.trim(),
      `${r.short_qty ?? 0} ${r.unit ?? ''}`.trim(),
      `${r.hold_qty ?? 0} ${r.unit ?? ''}`.trim(),
      `${r.received_qty ?? ''} ${r.unit ?? ''}`.trim(),
    ]);

    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet('Receiving Log', {
      pageSetup: { orientation: 'landscape', fitToPage: true },
    });
    const totalColumns = header.length;

    sheet.mergeCells(1, 1, 1, totalColumns);
    const titleCell = sheet.getCell(1, 1);
    titleCell.value = title;
    titleCell.font = { bold: true, size: 14, color: { argb: 'FFFFFFFF' } };
    titleCell.alignment = {
      horizontal: 'center',
      vertical: 'middle',
      wrapText: true,
    };
    titleCell.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FF1F4E78' },
    };
    sheet.getRow(1).height = 26;

    const generatedAtCell = sheet.getCell(2, 1);
    generatedAtCell.value =
      'Generated On: ' +
      (this.datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm') || '');
    generatedAtCell.font = { italic: true, size: 10, color: { argb: 'FF374151' } };
    sheet.mergeCells(2, 1, 2, totalColumns);
    sheet.getRow(2).height = 20;

    const headerRow = sheet.addRow(header);
    headerRow.eachCell((cell: any) => {
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FF0EA5E9' },
      };
      cell.font = { bold: true, size: 11, color: { argb: 'FFFFFFFF' } };
      cell.alignment = {
        horizontal: 'center',
        vertical: 'middle',
        wrapText: true,
      };
      cell.border = {
        top: { style: 'thin' },
        bottom: { style: 'thin' },
        left: { style: 'thin' },
        right: { style: 'thin' },
      };
    });
    headerRow.height = 24;

    dataRows.forEach((r) => sheet.addRow(r));

    const colWidths = [8, 18, 18, 14, 18, 18, 26, 26, 16, 18, 14, 12, 12, 14];
    sheet.columns.forEach((col, idx) => {
      col.width = colWidths[idx] || 14;
    });

    const dataStartRow = 4;
    const dataEndRow = dataStartRow + dataRows.length - 1;
    for (let r = dataStartRow; r <= dataEndRow; r++) {
      const row = sheet.getRow(r);
      row.height = 20;
      row.eachCell((cell: any, colNumber: number) => {
        cell.alignment = {
          horizontal:
            colNumber === 1 ||
            colNumber === 11 ||
            colNumber === 12 ||
            colNumber === 13 ||
            colNumber === 14
              ? 'center'
              : 'left',
          vertical: 'middle',
          wrapText: true,
        };
        cell.border = {
          top: { style: 'thin' },
          bottom: { style: 'thin' },
          left: { style: 'thin' },
          right: { style: 'thin' },
        };
      });
    }

    sheet.autoFilter = {
      from: { row: 3, column: 1 },
      to: { row: 3, column: totalColumns },
    };

    workbook.xlsx.writeBuffer().then((buffer: ArrayBuffer) => {
      const blob = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      const safeName = (this.material_type || 'log').replace(/[^\w\-]+/g, '_');
      a.download = `received_${safeName}_log_${this.datePipe.transform(
        new Date(),
        'yyyyMMdd_HHmm'
      )}.xlsx`;
      a.click();
      URL.revokeObjectURL(a.href);
      if (typeof alertify !== 'undefined') {
        alertify.success('Excel exported successfully');
      }
    });
  }

  checklist: any[] = [];
  checkPointRows: any[] = [];
  damageChecklist: any[] = [];
  damageCheckPointRows: any[] = [];
  showDamageDetails = false;
  damageDetailsLoading = false;
  damageDetailsLoaded = false;

  private resetDamageDetailsPanel(): void {
    this.showDamageDetails = false;
    this.damageDetailsLoading = false;
    this.damageDetailsLoaded = false;
    this.damageChecklist = [];
    this.damageCheckPointRows = [];
  }

  private resolveLocalDamageChecklistRows(): any[] {
    return resolveReceivingDamageChecklistRows(
      [],
      this.selectedReport?.receiving_details,
      this.selectedReport?.damageChecklist
    );
  }

  private applyChecklistRows(rows: any[]): void {
    this.checklist = rows;
    this.checkPointRows = buildChecklistReadonlyRows(rows);
  }

  private applyDamageChecklistRows(rows: any[]): void {
    this.damageChecklist = rows;
    this.damageCheckPointRows = buildChecklistReadonlyRows(rows);
  }

  getChkListData(rec_no: string) {
    const cmId = this.selectedReport?.id || '';
    this.applyChecklistRows(
      resolveReceivingChecklistRows([], this.selectedReport?.receiving_details)
    );
    this.service
      .get(
        'store/raw.php?type=getReceivingChecklist&rec_no=' +
          encodeURIComponent(rec_no || '') +
          '&challan_material_id=' +
          encodeURIComponent(cmId)
      )
      .subscribe(
        (response: any) => {
          this.applyChecklistRows(
            resolveReceivingChecklistRows(response, this.selectedReport?.receiving_details)
          );
        },
        () => {
          this.applyChecklistRows(
            resolveReceivingChecklistRows([], this.selectedReport?.receiving_details)
          );
        }
      );
  }

  getDamageChecklistData(rec_no: string) {
    const cmId = this.selectedReport?.id || '';
    const localRows = this.resolveLocalDamageChecklistRows();
    if (localRows.length > 0) {
      this.applyDamageChecklistRows(localRows);
      this.damageDetailsLoaded = true;
      this.damageDetailsLoading = false;
      return;
    }

    this.damageDetailsLoading = true;
    this.applyDamageChecklistRows([]);
    this.service
      .get(
        'store/raw.php?type=getReceivingDamageChecklist&rec_no=' +
          encodeURIComponent(rec_no || '') +
          '&challan_material_id=' +
          encodeURIComponent(cmId)
      )
      .subscribe(
        (response: any) => {
          this.applyDamageChecklistRows(
            resolveReceivingDamageChecklistRows(
              response,
              this.selectedReport?.receiving_details,
              this.selectedReport?.damageChecklist
            )
          );
          this.damageDetailsLoaded = true;
          this.damageDetailsLoading = false;
        },
        () => {
          this.applyDamageChecklistRows(this.resolveLocalDamageChecklistRows());
          this.damageDetailsLoaded = true;
          this.damageDetailsLoading = false;
        }
      );
  }

  toggleDamageDetails(): void {
    if (this.showDamageDetails) {
      this.showDamageDetails = false;
      return;
    }
    this.showDamageDetails = true;
    if (!this.damageDetailsLoaded) {
      this.getDamageChecklistData(this.selectedReport?.receiving_no || '');
    }
  }

  getHoldQty(): string {
    const value = this.getValueFromSources(['hold_qty']);
    const unit = this.selectedReport?.unit || '';
    if (value === '' || value === null || value === undefined) {
      return '-';
    }
    return `${value} ${unit}`.trim();
  }

  trackCheckPointRow(index: number, row: any): string {
    if (row?.rowKey) {
      return row.rowKey;
    }
    if (row?.type === 'header' || row?.type === 'footer') {
      return `${row.type}_${row.title || index}`;
    }
    return String(index);
  }

  hasChecklistFooterInRows(): boolean {
    return hasChecklistFooterRow(this.checkPointRows);
  }

  hasDamageContainers(): boolean {
    const damageFlag = this.getValueFromSources(['isdamagecontainer', 'damage']);
    return String(damageFlag).toLowerCase() === 'yes';
  }

  getDamageContainerObserved(): string {
    const value = this.getValueFromSources(['isdamagecontainer', 'damage']);
    return value ? String(value) : 'No';
  }

  getDamageBatchNo(): string {
    return String(
      this.getValueFromSources(['batch_no', 'damage_batch_no']) || ''
    ).trim();
  }

  getDamageContainersCount(): string {
    const value = this.getValueFromSources(['outer_damage']);
    return value !== '' ? String(value) : '-';
  }

  isDamageBatch(batch: any): boolean {
    if (!this.hasDamageContainers()) {
      return false;
    }
    const damageBatch = this.getDamageBatchNo();
    if (!damageBatch) {
      return false;
    }
    return String(batch?.batch_no || '').trim() === damageBatch;
  }

  searchQuery = '';
  pageSizeOptions: number[] = [10, 20, 30, 50, 100];
  pageSize = 10;
  currentPage = 1;

  onPageSizeChange(value: any): void {
    const size = Number(value);
    this.pageSize = Number.isFinite(size) && size > 0 ? size : 10;
    this.currentPage = 1;
  }

  getSrNo(index: number): number {
    return (this.currentPage - 1) * this.pageSize + index + 1;
  }

  onFiltersChanged(): void {
    this.currentPage = 1;
  }

  private getValueFromSources(keys: string[]): any {
    const sources = [this.selectedReport, this.selectedReport?.receiving_details];
    for (const source of sources) {
      if (!source) {
        continue;
      }
      for (const key of keys) {
        const value = source[key];
        if (value !== null && value !== undefined && String(value).trim() !== '') {
          return value;
        }
      }
    }
    return '';
  }

  private formatDateTime(value: any): string {
    if (value == null || value === '') {
      return '-';
    }
    const dt = value instanceof Date ? value : new Date(value);
    if (!isNaN(dt.getTime())) {
      return this.datePipe.transform(dt, 'dd-MM-yyyy HH:mm') || '-';
    }
    return String(value);
  }

  private buildSignatureCard(role: 'entry' | 'checked'): any {
    const isEntry = role === 'entry';
    const name = this.getValueFromSources(
      isEntry
        ? ['entry_by_name', 'entry_name', 'entry_by', 'received_by', 'created_by']
        : ['checked_by_name', 'checker_name', 'checked_by', 'check_by', 'approve_by']
    );
    const userId = this.getValueFromSources(
      isEntry
        ? ['entry_user_id', 'entry_by_id', 'entry_emp_id', 'created_by_id', 'emp_id']
        : ['checked_user_id', 'checker_id', 'checked_by_id', 'approve_by_id']
    );
    const department = this.getValueFromSources(
      isEntry
        ? ['entry_department', 'entry_dept', 'department', 'entry_by_department']
        : ['checked_department', 'checked_dept', 'checker_department', 'approve_department']
    );
    const signedOn = this.formatDateTime(
      this.getValueFromSources(
        isEntry
          ? ['entry_date_time', 'entry_datetime', 'entry_date', 'created_at', 'receiving_date']
          : ['checked_date_time', 'checked_datetime', 'checked_date', 'check_date', 'approve_date']
      )
    );

    return {
      roleLabel: isEntry ? 'Entry By' : 'Checked By',
      isSigned: !!name,
      name: name || '-',
      userId: userId || '-',
      department: department || '-',
      signedOn,
    };
  }

  prepareDigitalSignatures(): void {
    this.entrySignature = this.buildSignatureCard('entry');
    this.checkedSignature = this.buildSignatureCard('checked');
  }

  get filteredMaterials(): any[] {
    if (!Array.isArray(this.results) || !this.results.length) {
      return [];
    }

    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.results.filter((material) => {
      return Object.entries(material).some(([key, value]) => {
        if (value === null || value === undefined) {
          return false;
        }
        if (key === 'entry_date') {
          const dateValue =
            typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            !isNaN(dateValue.getTime()) &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        }
        return value.toString().toLowerCase().includes(query);
      });
    });
  }
}
