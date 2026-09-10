import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';

declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {


  from_date = '';
  client_code = '';
  to_date = '';
  clients: any[] = [];
  results: any[] = [];
  loading = false;



  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
     this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');    
      this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   
  }



  ngOnInit() {
    this.getClientFeedbacks();
  }


  getClientFeedbacks() {
    this.service.get('marketing/feedback.php?type=getClientFeedbacks&client_code=' + this.client_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  searchQuery = '';

  get filteredMaterials(): any[] {
    if (!this.results || this.results.length === 0) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.results.filter((material: any) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entryOn') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

  /** Download filtered feedback log as formatted Excel */
  downloadExcel() {
    const list = this.filteredMaterials;
    if (!list || list.length === 0) {
      if (typeof alertify !== 'undefined') {
        alertify.warning('No data to download.');
      } else {
        alert('No data to download.');
      }
      return;
    }
    const formatDate = (d: any) => {
      if (!d) return '-';
      const dt = new Date(d);
      if (isNaN(dt.getTime())) return '-';
      return dt.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
    };
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Feedback Log', { pageSetup: { orientation: 'landscape' } });
    const headers = ['Sr.No.', 'Client Name', 'Feedback through', 'Feedback', 'Entry By', 'Entry On'];
    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0e4370' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
    });
    ws.getRow(1).height = 22;
    list.forEach((row: any, i: number) => {
      ws.addRow([
        i + 1,
        row.LglNm || '-',
        row.feedback_by || '-',
        row.feedback || '-',
        row.entry_by || '-',
        formatDate(row.entry_date)
      ]);
    });
    const thinBorder = { top: { style: 'thin' as const }, bottom: { style: 'thin' as const }, left: { style: 'thin' as const }, right: { style: 'thin' as const } };
    for (let r = 2; r <= list.length + 1; r++) {
      const row = ws.getRow(r);
      row.height = 20;
      row.eachCell((cell) => {
        cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
        cell.border = thinBorder;
      });
    }
    const colWidths = [8, 22, 16, 36, 14, 14];
    ws.columns.forEach((col, idx) => { col.width = colWidths[idx] ?? 14; });
    wb.xlsx.writeBuffer().then((buffer: ArrayBuffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'Feedback_Log_' + new Date().toISOString().slice(0, 10) + '.xlsx';
      a.click();
      URL.revokeObjectURL(url);
    });
  }
}
