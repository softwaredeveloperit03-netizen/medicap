import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';
declare let alertify: any;

@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css'],
  providers: [DatePipe]
})
export class CorrectionComponent implements OnInit {

  results;
  selectedResult = [];
  isView = false;
  searchQuery = '';
  loading = false;

  constructor(private service: DataAccessService, private datePipe: DatePipe) { }
  ngOnInit() {
    this.getCheckedIndendForCorrection();
  }


  getCheckedIndendForCorrection() {
      this.service.get('purchase/indent.php?type=getCheckedIndendForCorrection').subscribe((response : any) => {
      this.results = response; 
    });
  }

  view(data){
    this.selectedResult = data
    this.isView = true;
  }

 

  sendIndentForPoPreparation() {

    let temp = {};
    temp['materials'] = this.selectedResult['materials'];
    this.service.post('purchase/indent.php?type=sendIndentForPoPreparation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getCheckedIndendForCorrection();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

  download() {
    if (!this.results || this.results.length === 0) {
      if (typeof alertify !== 'undefined') alertify.warning('No data to download');
      return;
    }
    const headers = ['Sr.', 'Date', 'Purchase Requisition No', 'Request No', 'Materials', 'Entered By'];
    const headerColors = ['FF0d9488', 'FF0ea5e9', 'FF9b8fb0', 'FF7c6b94', 'FF4a7c59', 'FF5a7cb0'];
    const data = (this.results || []).map((row: any, i: number) => {
      const matNames = (row.materials || []).map((m: any) => m.material_name).join(', ');
      return [
        i + 1,
        row.entry_date ? this.datePipe.transform(row.entry_date, 'dd-MM-yyyy') : '',
        row.indend_no || '-',
        row.request_no || '-',
        matNames || '',
        row.entry_by || ''
      ];
    });
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Correction Log', { views: [{ rightToLeft: false }] });
    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell, colNumber) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: headerColors[colNumber - 1] || 'FFb8c9e0' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
      cell.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
    });
    ws.addRows(data);
    const colWidths = [6, 14, 16, 16, 40, 18];
    ws.columns.forEach((col, idx) => { if (idx < colWidths.length) col.width = colWidths[idx]; });
    headerRow.height = 22;
    data.forEach((_, r) => {
      const row = ws.getRow(r + 2);
      row.height = 18;
      row.eachCell(c => {
        c.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
        c.alignment = { vertical: 'middle', wrapText: true };
      });
    });
    wb.xlsx.writeBuffer().then((buffer: ArrayBuffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = `Indent_Correction_Log_${this.datePipe.transform(Date.now(), 'yyyy-MM-dd')}.xlsx`;
      a.click();
      URL.revokeObjectURL(a.href);
      if (typeof alertify !== 'undefined') alertify.success('Excel downloaded successfully');
    });
  }

}
