


import { Component, OnInit } from '@angular/core';
import { ClrLoadingState } from '@clr/angular';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';
declare let alertify;
@Component({
  selector: 'app-storestatus',
  templateUrl: './storestatus.component.html',
  styleUrls: ['./storestatus.component.css', '../../../shared/purchase-vapp-host.css']
})
export class StorestatusComponent implements OnInit {

  isView = false;
  isUpincedent = false;
  results: any[] = [];
  isupdate = false;
  selectedReport: any = null;
  receiveDetails: any[] = [];
  devdetails: any = null;
  productdetails: any[] = [];
  batches: any[] = [];
  insdetails: any = null;
  incidentProduct: any[] = [];
  materials: any[] = [];
  material_code = '';
  challan_date = '';
  checklist: any = null;
  plant_id: any;
  loading = false;
  searchTerm = '';

  submitBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;
  validateBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;

  constructor(private service: DataAccessService) { }

  get filteredResults(): any[] {
    const list = this.results || [];
    const q = (this.searchTerm || '').trim().toLowerCase();
    if (!q) return list;
    return list.filter((r: any) =>
      (r.po_no && String(r.po_no).toLowerCase().includes(q)) ||
      (r.vendor_name && String(r.vendor_name).toLowerCase().includes(q)) ||
      (r.material_code && String(r.material_code).toLowerCase().includes(q)) ||
      (r.material_name && String(r.material_name).toLowerCase().includes(q)) ||
      (r.ch_no && String(r.ch_no).toLowerCase().includes(q))
    );
  }

  ngOnInit(): void {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.getInprocessReceivings();
    this.getMaterials();
  }

  clearFilter(): void {
    this.searchTerm = '';
  }

  getInprocessReceivings(): void {
    this.loading = true;
    const materialParam = this.material_code
      ? '&material_code=' + encodeURIComponent(this.material_code)
      : '';
    this.service.get('store/raw.php?type=getInprocessReceivings_for_store_status' + materialParam).subscribe({
      next: (response) => {
        const rows = Array.isArray(response) ? response : [];
        this.results = rows.map((row) => this.normalizeRow(row));
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
      }
    });
  }

  private normalizeRow(row: any): any {
    if (!row) {
      return {};
    }
    return {
      ...row,
      po_no: row.po_no || row.po_number || '',
      po_date: row.po_date || row.entry_date || row.challan_header_po_date || row.challan_po_date || '',
      ch_no: row.ch_no || row.challan_no || '',
      challan_date: row.challan_date || row.challan_header_po_date || '',
    };
  }

  AllRecord(): void {
    this.material_code = '';
    this.getInprocessReceivings();
  }

  getMaterials(): void {
    this.service.get('common.php?type=getRawMaterials').subscribe((response) => {
      this.materials = Array.isArray(response) ? response : [];
    });
  }

  view(index: number): void {
    const list = this.filteredResults;
    if (index < 0 || index >= list.length) return;
    this.selectedReport = list[index];
    this.batches = this.selectedReport && this.selectedReport['batches'] ? this.selectedReport['batches'] : [];
    this.receiveDetails = this.selectedReport && this.selectedReport['receiving_details'] ? this.selectedReport['receiving_details'] : [];
    if (this.selectedReport && this.selectedReport['error_type'] === 'error2') {
      this.devdetails = this.selectedReport['deviations'] || null;
      this.productdetails = this.devdetails && this.devdetails['product_details'] ? this.devdetails['product_details'] : [];
    } else if (this.selectedReport && this.selectedReport['error_type'] === 'error1') {
      this.insdetails = this.selectedReport['incidents'] || null;
      this.incidentProduct = this.insdetails && this.insdetails['product_details'] ? this.insdetails['product_details'] : [];
    }
    if (this.selectedReport && this.selectedReport['receiving_no']) {
      this.getChkListData(this.selectedReport['receiving_no']);
    }
    this.isView = true;
  }

  viewCoafile(url) {
    url = this.service.url + '../../../../upload/coa/' + url;
    window.open(url, '_blank');
  }

  viewChallan(url) {
    url = this.service.url + 'upload/challan/' + url;
    window.open(url, '_blank');
    // window.open(this.service.url+ 'upload/challan/' + this.selectedReport['challan_file']);

  }

  update(status: string): void {
    if (!this.selectedReport) return;
    this.submitBtnState = ClrLoadingState.LOADING;
    this.service.get('store/raw.php?type=checkReceivedMaterial&status=' + status + '&id=' + this.selectedReport['id'] + '&challan_id=' + this.selectedReport['challan_id'] + '&pack_size=' + (this.selectedReport['pack_size'] || '')).subscribe(response => {
      if (response['status'] == 'success') {
        this.submitBtnState = ClrLoadingState.DEFAULT;
        alertify.success('Material updated successfully');
        this.isView = false;
        this.getInprocessReceivings();
      } else {
        this.submitBtnState = ClrLoadingState.DEFAULT;
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getChkListData(rec_no) {
    this.service.get('master/checklist.php?type=get_rec_ChkListByTranID&rec_no='+rec_no).subscribe(response => {
      this.checklist = response;
    });
  }

  downloadExcel(): void {
    const list = this.results || [];
    if (list.length === 0) return;
    const headers = [
      'Sr. No.',
      'Po No',
      'P.O Date',
      'Vendor Name',
      'Challan No',
      'Challan Date',
      'Material Code',
      'Material Name',
      'Status',
    ];
    const rows = list.map((user: any, index: number) => [
      index + 1,
      user.po_no ?? '',
      this.formatDate(user.po_date),
      user.vendor_name ?? '',
      user.ch_no || user.challan_no || '',
      this.formatDate(user.challan_date),
      user.material_code ?? '',
      user.material_name ?? '',
      user.po_status ?? '',
    ]);

    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('PO Status From Stores', { views: [{ rightToLeft: false }] });

    const headerRow = ws.addRow(headers);
    const headerColors = ['FF0d9488', 'FF059669', 'FF0891b2', 'FF7c3aed', 'FFdc2626', 'FFea580c', 'FF2563eb', 'FF16a34a', 'FF4f46e5'];
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
    ws.getRow(1).height = 22;

    const colWidths = [8, 14, 12, 22, 14, 12, 14, 28, 12];
    ws.columns.forEach((col, i) => {
      if (i < headers.length) col.width = colWidths[i] ?? 14;
    });

    ws.addRows(rows);
    const thinBorder = {
      top: { style: 'thin' as const },
      bottom: { style: 'thin' as const },
      left: { style: 'thin' as const },
      right: { style: 'thin' as const },
    };
    for (let r = 2; r <= rows.length + 1; r++) {
      const row = ws.getRow(r);
      row.eachCell((cell) => {
        cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
        cell.border = thinBorder;
      });
      row.height = 20;
    }

    wb.xlsx.writeBuffer().then((buffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'PO_Status_From_Stores.xlsx';
      a.click();
      URL.revokeObjectURL(a.href);
    });
  }
  formatDate(dateString: string): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    const day = ("0" + date.getDate()).slice(-2);
    const month = ("0" + (date.getMonth() + 1)).slice(-2);
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
  }

}
