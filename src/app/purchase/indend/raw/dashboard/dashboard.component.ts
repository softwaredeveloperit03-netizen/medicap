import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';
declare let alertify: any;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {


  results;
  selectedResult=[];
  isView=false;
  isEdit=false;
  today='';
  to_date='';
  from_date='';
  searchQuery = '';
  loading = false;

  /** '' = All, Quotation = RM/PM, General = general material requisitions */
  materialCategoryFilter: '' | 'Quotation' | 'General' = '';

  getRowMaterialType(row: any): string {
    const fromRow = row && row.material_type;
    if (fromRow) {
      return String(fromRow).trim();
    }
    const materials = row && row.materials;
    if (Array.isArray(materials) && materials.length) {
      for (const m of materials) {
        const t = m && m.material_type;
        if (t) {
          return String(t).trim();
        }
      }
    }
    return '';
  }

  isRmPmMaterialType(materialType: string): boolean {
    const t = String(materialType || '').trim().toUpperCase();
    return t === 'RAW MATERIAL' || t === 'PACKING MATERIAL';
  }

  getMaterialCategoryLabel(row: any): string {
    return this.isRmPmMaterialType(this.getRowMaterialType(row)) ? 'RM/PM Purchase Requisition' : 'General Material Purchase Requisition';
  }

  matchesMaterialCategory(row: any): boolean {
    if (!this.materialCategoryFilter) {
      return true;
    }
    const isRmPm = this.isRmPmMaterialType(this.getRowMaterialType(row));
    if (this.materialCategoryFilter === 'Quotation') {
      return isRmPm;
    }
    return !isRmPm;
  }

  get filteredIndends(): any[] {
    if (!this.results || !Array.isArray(this.results)) {
      return [];
    }
    if (!this.materialCategoryFilter) {
      return this.results;
    }
    return this.results.filter((row: any) => this.matchesMaterialCategory(row));
  }

  /**
   * Real material name only — never "null", never code-as-name (e.g. nullS0053).
   * Code belongs in Material Code column; name comes from master / saved material_name.
   */
  materialDisplayName(item: any): string {
    const code = String(item?.material_code ?? '').trim();
    const candidates = [item?.material_name, item?.gm_material];
    for (const raw of candidates) {
      if (raw === null || raw === undefined) {
        continue;
      }
      let name = String(raw).trim();
      if (!name || /^null$/i.test(name) || /^undefined$/i.test(name)) {
        continue;
      }
      // Guard against JS null+code → "nullS0053"
      if (/^null/i.test(name) && code && name.toUpperCase().endsWith(code.toUpperCase())) {
        name = name.replace(/^null/i, '').trim();
      }
      if (!name || /^null$/i.test(name)) {
        continue;
      }
      // Do not treat material code itself as a display name
      if (code && name.toUpperCase() === code.toUpperCase()) {
        continue;
      }
      return name;
    }
    return '-';
  }

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {      
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd'); 
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }
  ngOnInit() {
    this.getDeptIndendsLog();
  }


  getDeptIndendsLog() {
      this.service.get('purchase/indent.php?type=getPurchaseIndendsLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe((response : any) => {
      this.results = Array.isArray(response) ? response : [];
    }, () => {
      this.results = [];
    });
  }

  statusLabel(status: string): string {
    const s = String(status || '');
    if (s === 'TO_HOD' || s === 'To_HOD_RMPM' || s === 'To_Store_Head') {
      return 'Dept Head Approval Pending';
    }
    if (s === 'TO_PlantHead') {
      return 'Plant Head Approval Pending';
    }
    if (s === 'pending') {
      return 'Pending';
    }
    if (s === 'approve') {
      return 'Approved';
    }
    if (s.indexOf('Revert_') === 0) {
      return 'Reverted — Pending';
    }
    return s || 'Pending';
  }

  view(data){
    this.selectedResult = data
    this.isView = true;
  }

  download(){
    const rows = this.filteredIndends || [];
    if (rows.length === 0) {
      if (typeof alertify !== 'undefined') alertify.warning('No data to download');
      return;
    }
    const headers = ['Sr.', 'Date', 'Purchase Requisition No', 'Request No', 'Status', 'Material Type', 'Materials', 'Entered By'];
    const headerColors = ['FF0d9488', 'FF0ea5e9', 'FF9b8fb0', 'FF7c6b94', 'FF6b8e7a', 'FF4a7c59', 'FF5a7cb0', 'FF334155'];
    const data = rows.map((row: any, i: number) => {
      const matNames = (row.materials || []).map((m: any) => this.materialDisplayName(m)).join(', ');
      return [
        i + 1,
        row.entry_date ? this.datePipe.transform(row.entry_date, 'dd-MM-yyyy') : '',
        row.indend_no || '-',
        row.request_no || '-',
        this.statusLabel(row.status),
        this.getMaterialCategoryLabel(row),
        matNames || '',
        row.entry_by || ''
      ];
    });
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Purchase Requisition Log', { views: [{ rightToLeft: false }] });
    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell, colNumber) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: headerColors[colNumber - 1] || 'FFb8c9e0' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
      cell.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
    });
    ws.addRows(data);
    const colWidths = [6, 14, 16, 16, 22, 18, 40, 18];
    ws.columns.forEach((col, idx) => { if (idx < colWidths.length) col.width = colWidths[idx]; });
    headerRow.height = 22;
    data.forEach((_, r) => { const row = ws.getRow(r + 2); row.height = 18; row.eachCell(c => { c.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } }; c.alignment = { vertical: 'middle', wrapText: true }; }); });
    wb.xlsx.writeBuffer().then((buffer: ArrayBuffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
      const filterSuffix = this.materialCategoryFilter
        ? `_${this.materialCategoryFilter === 'General' ? 'General' : 'RM_PM'}`
        : '';
      a.download = `Requisition_Log${filterSuffix}_${this.datePipe.transform(Date.now(), 'yyyy-MM-dd')}.xlsx`; a.click(); URL.revokeObjectURL(a.href);
      if (typeof alertify !== 'undefined') alertify.success('Excel downloaded successfully');
    });
  }

  downloadPDF(){
    this.service.open('purchase/indend/raw.php?type=downloadPurchaseIndend&request_no='+this.selectedResult['request_no'])
  }

 
 
 
 
  
}
