import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';
declare let alertify: any;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
})
export class ApprovalComponent implements OnInit {

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getAllPendingPO();
  }

  po_type = 'All';
  results: any[] = [];
  loading = false;
  searchQuery = '';

  getAllPendingPO(): void {
    this.loading = true;
    this.service
      .getJsonArray(
        'purchase/po/raw.php?type=getAllPendingPO&po_type=' + encodeURIComponent(this.po_type || 'All')
      )
      .subscribe({
        next: (list) => {
          this.results = Array.isArray(list) ? list : [];
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.loading = false;
        },
      });
  }

  downloadExcel(): void {
    const list = this.filteredMaterials || [];
    if (list.length === 0) return;
    const headers = ['Sr.No', 'Material Type', 'PO No.', 'Po Dt.', 'Materials', 'Entered By', 'Vendor Name', 'Status'];
    const rows = list.map((row: any, index: number) => {
      const matNames = (row.materials || []).map((m: any) => m.material_name).join(', ');
      return [
        index + 1,
        row.po_type ?? '',
        row.po_no ?? '',
        this.formatDate(row.entry_date),
        matNames,
        row.entry_by ?? '',
        (row.vendor_name || '') + (row.vendor_no ? ' - ' + row.vendor_no : ''),
        row.status ?? '',
      ];
    });

    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('PO Dept Head Approval', { views: [{ rightToLeft: false }] });
    const headerRow = ws.addRow(headers);
    const headerColors = ['FF0d9488', 'FF059669', 'FF0891b2', 'FF7c3aed', 'FFdc2626', 'FFea580c', 'FF2563eb', 'FF16a34a'];
    headerRow.eachCell((cell, colNumber) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: headerColors[colNumber - 1] || 'FF64748b' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
    });
    ws.getRow(1).height = 22;
    const colWidths = [8, 18, 14, 12, 36, 14, 28, 12];
    ws.columns.forEach((col, i) => { if (i < headers.length) col.width = colWidths[i] ?? 14; });
    ws.addRows(rows);
    const thinBorder = { top: { style: 'thin' as const }, bottom: { style: 'thin' as const }, left: { style: 'thin' as const }, right: { style: 'thin' as const } };
    for (let r = 2; r <= rows.length + 1; r++) {
      ws.getRow(r).eachCell((cell) => { cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true }; cell.border = thinBorder; });
      ws.getRow(r).height = 20;
    }

    wb.xlsx.writeBuffer().then((buffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'PO_Dept_Head_Approval.xlsx';
      a.click();
      URL.revokeObjectURL(a.href);
    });
  }

  formatDate(dateString: string): string {
    if (!dateString) return '';
    const d = new Date(dateString);
    const day = ('0' + d.getDate()).slice(-2);
    const month = ('0' + (d.getMonth() + 1)).slice(-2);
    return `${day}-${month}-${d.getFullYear()}`;
  }

  isView = false;
  selectedPO: any = null;
  selectedBill: any = {};
  selectedShip: any = {};

  view(data: any): void {
    this.selectedPO = data;
    this.selectedBill = this.selectedPO && this.selectedPO['selectedBill'] ? this.selectedPO['selectedBill'] : {};
    this.selectedShip = this.selectedPO && this.selectedPO['selectedShip'] ? this.selectedPO['selectedShip'] : {};
    this.isView = true;
  }

  updatePO(status: string): void {
    if (!this.selectedPO || !this.selectedPO['id'] || status == null || status === '') return;
    const temp = { id: this.selectedPO['id'], status };
    this.service.post('purchase/po/raw.php?type=approvePO', JSON.stringify(temp)).subscribe({
      next: (response: any) => {
        if (response && response['status'] === 'success') {
          alertify.success('PO Sent For ' + status + ' Approval.....');
          this.getAllPendingPO();
          this.isView = false;
        } else {
          alertify.error('Failed to Update PO, Please try again!');
        }
      },
      error: () => alertify.error('Failed to Update PO, Please try again!')
    });
  }

  get filteredMaterials(): any[] {
    const list = this.results || [];
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) return list;
    return list.filter((row: any) => {
      if (row.entry_date && typeof row.entry_date === 'string' && row.entry_date.toLowerCase().includes(q)) return true;
      if (row.po_no && String(row.po_no).toLowerCase().includes(q)) return true;
      if (row.po_type && String(row.po_type).toLowerCase().includes(q)) return true;
      if (row.vendor_name && String(row.vendor_name).toLowerCase().includes(q)) return true;
      if (row.vendor_no && String(row.vendor_no).toLowerCase().includes(q)) return true;
      if (row.entry_by && String(row.entry_by).toLowerCase().includes(q)) return true;
      if (row.status && String(row.status).toLowerCase().includes(q)) return true;
      const materials = row.materials || [];
      if (materials.some((m: any) => (m.material_name && String(m.material_name).toLowerCase().includes(q)) || (m.material_code && String(m.material_code).toLowerCase().includes(q)))) return true;
      return false;
    });
  }

  clearFilter(): void {
    this.searchQuery = '';
  }
}
