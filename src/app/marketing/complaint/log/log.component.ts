import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';

declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  from_date = '';
  client_code = '';
  to_date = '';
  isView = false;
  results: any[] = [];
  selectedClient = [];
  clients: any[] = [];
  /** Search by company name (filters displayed list) */
  searchCompany = '';

  constructor(private service: DataAccessService) {
    const d = new Date();
    let day = d.getDate();
    let m = d.getMonth();
    m = +m + 1;
    let mon = '';
    if (day > 0 && day < 10) {
      mon = '0' + day;
    }
    if (m > 0 && m < 10) {
      mon = '0' + m;
    }
    this.to_date = d.getFullYear() + '-' + mon + '-' + day;
    this.from_date = d.getFullYear() + '-' + mon + '-01';
  }

  ngOnInit() {
    this.getComplaintsLog();
    this.getclientlist();
  }

  getclientlist() {
    this.service.get('marketing/po.php?type=getClients').subscribe((response: any) => {
      this.clients = response;
    });
  }

  getComplaintsLog() {
    this.service.get('marketing/complaint.php?type=getComplaintsLog&client_code=' + this.client_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  /** List filtered by company name (search bar) */
  get filteredResults(): any[] {
    if (!this.results || this.results.length === 0) return [];
    const q = (this.searchCompany || '').trim().toLowerCase();
    if (!q) return this.results;
    return this.results.filter((row: any) => {
      const company = (row.company || '').toString().toLowerCase();
      return company.indexOf(q) !== -1;
    });
  }

  view(index: number) {
    const list = this.filteredResults;
    if (list && list[index] !== undefined) {
      this.selectedClient = list[index];
      this.isView = true;
    }
  }

  /** Download filtered complaint log as formatted Excel */
  downloadExcel() {
    const list = this.filteredResults;
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
    const ws = wb.addWorksheet('Complaint Log', { pageSetup: { orientation: 'landscape' } });
    const headers = ['Sr.No.', 'Client name', 'Complaint date', 'Complaint nature', 'Status', 'Entry by', 'Entry date'];
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
        row.company || '-',
        formatDate(row.complaint_date),
        row.complaint_nature || '-',
        row.status || '-',
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
    const colWidths = [8, 22, 14, 22, 12, 14, 14];
    ws.columns.forEach((col, idx) => { col.width = colWidths[idx] ?? 14; });
    wb.xlsx.writeBuffer().then((buffer: ArrayBuffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'Complaint_Log_' + new Date().toISOString().slice(0, 10) + '.xlsx';
      a.click();
      URL.revokeObjectURL(url);
    });
  }
}
