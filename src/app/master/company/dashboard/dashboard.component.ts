import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { buildCompanyCountryOptions } from '../merge-countries';
import * as ExcelJS from 'exceljs';
import { saveAs } from 'file-saver';

declare let alertify: { success: (m: string) => void; error: (m: string) => void };

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  isUser = false;
  isChecker = false;
  isApprover = false;
  company_code = '';
  person = '';
  p_email = '';
  mobile_no = '';
  country = '';
  present_state = '';
  isView = false;
  results: Record<string, unknown>[] = [];
  company_name = '';
  selectedClient: Record<string, unknown> = {};
  companies: Record<string, unknown>[] = [];
  selectedResult: Record<string, unknown> = {};
  isEdit = false;
  states: unknown;
  cities: unknown;
  countries: { country?: string }[] = [];
  isDelete = false;
  loading = false;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getcompanylist();
    this.getCountries();
    this.get_rights();
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights: unknown;
  loggedInDept: string | null;

  get_rights(): void {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response: any) => {
        this.rights = response;
        this.isuser = response[0].isuser;
        this.ischecker = response[0].ischecker;
        this.isapprover = response[0].isapprover;
        this.qms_approver = response[0].qms_approver;
        this.dept_head = response[0].dept_head;
        this.isauditor = response[0].isauditor;
        this.plant_head = response[0].plant_head;
        this.shift_allocator = response[0].shift_allocator;
      });
  }

  currentPage = 1;
  pageSize = 10;

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  onPageChange(page: number): void {
    this.currentPage = page;
  }

  viewf(): void {
    this.isView = false;
    this.currentPage = 1;
    this.pageSize = 10;
  }

  getState(value: string): void {
    this.service
      .get('master/state.php?type=getStateBycountry&country=' + value)
      .subscribe((response) => {
        this.states = response;
      });
  }

  getCity(value: string): void {
    this.service
      .get('master/area.php?type=getCityByStateName&state_name=' + value)
      .subscribe((response) => {
        this.cities = response;
      });
  }

  getCountries(): void {
    this.service.get('master/country.php?type=getCountries').subscribe((response) => {
      const api = Array.isArray(response) ? (response as { country?: string }[]) : [];
      this.countries = buildCompanyCountryOptions(api);
    });
  }

  /** Safe cell text for detail / tables */
  dv(key: string): string {
    const v = this.selectedClient[key];
    if (v === null || v === undefined || v === '') {
      return '—';
    }
    return String(v);
  }

  getcompanylist(): void {
    this.loading = true;
    this.service.get('master/company.php?type=getCompany').subscribe({
      next: (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.filterCompany();
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        alertify.error('Could not load companies');
      },
    });
  }

  filterCompany(): void {
    this.companies = [];
    const q = (this.company_name || '').toUpperCase();
    for (let i = 0; i < this.results.length; i++) {
      const material = this.results[i];
      const name = (material['company_name'] as string) || '';
      if (name.toUpperCase().includes(q)) {
        this.companies.push(material);
      }
    }
  }

  /** Open detail using row object (correct with filters / pagination). */
  viewItem(item: Record<string, unknown>): void {
    this.selectedClient = item;
    this.isView = true;
  }

  isIndiaCompany(row: Record<string, unknown> | null | undefined): boolean {
    if (!row) return false;
    const c = row['country'];
    return (c != null ? String(c) : '').trim().toLowerCase() === 'india';
  }

  editCompany(): void {
    this.service
      .post(
        'master/company.php?type=editCompany&id=' + this.selectedResult['id'],
        JSON.stringify(this.selectedResult)
      )
      .subscribe((response: { status?: string }) => {
        if (response['status'] === 'success') {
          alertify.success('Company updated successfully');
          this.isEdit = false;
          this.getcompanylist();
        } else {
          alertify.error('Some error occurred');
        }
      });
  }

  deleteCompany(id: string | number): void {
    this.service.get('master/company.php?type=deleteCompany&id=' + id).subscribe((response: any) => {
      if (response['status']) {
        alertify.success('Company deleted successfully');
        this.isDelete = false;
        this.getcompanylist();
      } else {
        alertify.error('Some error occurred');
      }
    });
  }

  edit(index: number): void {
    this.selectedResult = { ...(this.results[index] || {}) };
    this.isEdit = true;
  }

  del(index: number): void {
    this.selectedClient = this.results[index];
    this.isDelete = true;
  }

  AllRecord(): void {
    this.companies = [...this.results];
    this.company_name = '';
  }

  /**
   * Styled Excel: sheet title, multicolor header row, column widths, borders, left-aligned text.
   */
  async downloadCompanyExcel(): Promise<void> {
    const headers = [
      'Sr. no.',
      'Company code',
      'Company name',
      'Contact person',
      'Email',
      'Contact no.',
      'Country',
      'State',
    ];
    const headerColors = [
      'FF0d9488',
      'FF2563eb',
      'FF7c3aed',
      'FFdb2777',
      'FFea580c',
      'FF16a34a',
      'FF0891b2',
      'FFca8a04',
    ];
    const rows: (string | number)[][] = this.companies.map((item, i) => [
      i + 1,
      this.cell(item['company_code']),
      this.cell(item['company_name']),
      this.cell(item['person']),
      this.cell(item['p_email']),
      this.cell(item['mobile_no']),
      this.cell(item['country']),
      this.cell(item['present_state']),
    ]);

    const wb = new ExcelJS.Workbook();
    wb.creator = 'Cyclone';
    wb.created = new Date();
    const ws = wb.addWorksheet('Company Master', {
      views: [{ showGridLines: true }],
    });

    const numCols = headers.length;
    ws.mergeCells(1, 1, 1, numCols);
    const title = ws.getCell(1, 1);
    title.value = 'Company master register';
    title.font = { bold: true, size: 16, color: { argb: 'FF0f2744' } };
    title.alignment = { vertical: 'middle', horizontal: 'center' };
    ws.getRow(1).height = 28;

    const sub = ws.getCell(2, 1);
    ws.mergeCells(2, 1, 2, numCols);
    sub.value = 'Exported: ' + new Date().toLocaleString();
    sub.font = { italic: true, size: 10, color: { argb: 'FF64748b' } };
    sub.alignment = { horizontal: 'left', vertical: 'middle' };
    ws.getRow(2).height = 18;

    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell, colNumber) => {
      const idx = colNumber - 1;
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: headerColors[idx] || 'FF64748b' },
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
    headerRow.height = 22;

    ws.addRows(rows);
    const colWidths = [8, 14, 28, 20, 28, 14, 18, 22];
    for (let c = 0; c < numCols; c++) {
      ws.getColumn(c + 1).width = colWidths[c] ?? 16;
    }

    const thin = {
      top: { style: 'thin' as const },
      bottom: { style: 'thin' as const },
      left: { style: 'thin' as const },
      right: { style: 'thin' as const },
    };
    const firstDataRow = 4;
    const lastRow = firstDataRow + Math.max(0, rows.length) - 1;
    for (let r = firstDataRow; r <= lastRow; r++) {
      const row = ws.getRow(r);
      row.height = 20;
      row.eachCell((cell) => {
        cell.alignment = { vertical: 'top', horizontal: 'left', wrapText: true };
        cell.border = thin;
      });
    }

    const buf = await wb.xlsx.writeBuffer();
    const blob = new Blob([buf], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    saveAs(blob, `company_master_${this.slugDate()}.xlsx`);
  }

  private cell(v: unknown): string {
    if (v === null || v === undefined) return '';
    return String(v);
  }

  private slugDate(): string {
    const d = new Date();
    const p = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}_${p(d.getHours())}${p(d.getMinutes())}`;
  }
}
