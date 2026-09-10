import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';

@Component({
  selector: 'app-wolog',
  templateUrl: './wolog.component.html',
  styleUrls: ['./wolog.component.css']
})
export class WologComponent implements OnInit {

  ngOnInit() {
    this.getPendingWOs();
  }

  constructor(private service: DataAccessService) { }

  loading = false;
  pendingpo: any[] = [];
  searchText = '';
  pendingpoBackup: any[] = [];

  prodctWise = true;
  matWise = false;
  isView = false;
  selectedWo: any = null;

  applyFilter() {
    const query = this.searchText.toLowerCase().trim();
    if (!query) {
      this.pendingpo = [...this.pendingpoBackup];
      return;
    }
    this.pendingpo = this.pendingpoBackup.filter((po) =>
      JSON.stringify(po).toLowerCase().includes(query)
    );
  }

  trackByIndex(index: number): number {
    return index;
  }

  formatIndentNumber(mat: any): string {
    const raw =
      mat?.indent_no ??
      mat?.indent_numbers ??
      mat?.indent_request_no ??
      '';
    const s = String(raw).trim();
    if (!s) {
      return '—';
    }
    return s;
  }

  formatDisplayDate(value: unknown): string {
    if (value == null || value === '') {
      return '—';
    }
    const s = String(value).trim();
    if (!s) {
      return '—';
    }
    const parts = s.split(',').map((p) => p.trim()).filter(Boolean);
    if (parts.length > 1) {
      return parts.map((p) => this.formatDisplayDate(p)).join(', ');
    }
    const d = new Date(s);
    if (!isNaN(d.getTime())) {
      return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    return s;
  }

  formatPlanMonthList(value: unknown): string {
    if (value == null || value === '') {
      return '—';
    }
    const s = String(value).trim();
    if (!s) {
      return '—';
    }
    return s
      .split(',')
      .map((p) => this.formatPlanMonth(p.trim()))
      .filter((x) => x !== '—')
      .join(', ') || '—';
  }

  formatPlanMonth(value: unknown): string {
    if (value == null || value === '') {
      return '—';
    }
    const s = String(value).trim();
    if (!s) {
      return '—';
    }
    const isoMonth = s.match(/^(\d{4})-(\d{2})(?:-\d{2})?/);
    if (isoMonth) {
      const d = new Date(Number(isoMonth[1]), Number(isoMonth[2]) - 1, 1);
      if (!isNaN(d.getTime())) {
        return d.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });
      }
    }
    const dmy = s.match(/^(\d{1,2})[\/\-](\d{4})$/);
    if (dmy) {
      const d = new Date(Number(dmy[2]), Number(dmy[1]) - 1, 1);
      if (!isNaN(d.getTime())) {
        return d.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });
      }
    }
    const parsed = new Date(s);
    if (!isNaN(parsed.getTime()) && /^\d{4}/.test(s)) {
      return parsed.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });
    }
    return s;
  }

  isIndentRaised(mat: any): boolean {
    const st = String(mat?.indent_status || '')
      .trim()
      .toLowerCase();
    if (st === 'raised' || st === 'indent sent') {
      return true;
    }
    return Number(mat?.indent_id || 0) > 0;
  }

  hasMaterialShortage(mat: any): boolean {
    return Number(mat?.shortage || 0) > 0;
  }

  getMaterialIndentDisplay(mat: any): { label: string; cssClass: string } {
    if (this.isIndentRaised(mat)) {
      return { label: 'Indent Raised', cssClass: 'wolog-indent--raised' };
    }
    if (!this.hasMaterialShortage(mat)) {
      return { label: 'No Shortage', cssClass: 'wolog-indent--no-shortage' };
    }
    return { label: 'Not Raised', cssClass: 'wolog-indent--not-raised' };
  }

  getMatLogIndentLabel(mat: any): string {
    const st = String(mat?.indent_status || '').trim();
    if (st) {
      return st;
    }
    if (this.isIndentRaised(mat)) {
      return 'Indent Raised';
    }
    if (!this.hasMaterialShortage(mat)) {
      return 'No Shortage';
    }
    return 'Not Raised';
  }

  getMatLogIndentCss(mat: any): string {
    const label = this.getMatLogIndentLabel(mat).toLowerCase();
    if (label.includes('raised') || label.includes('indent sent')) {
      return 'wolog-indent--raised';
    }
    if (label.includes('not raised')) {
      return 'wolog-indent--not-raised';
    }
    if (label.includes('no shortage')) {
      return 'wolog-indent--no-shortage';
    }
    return '';
  }

  materialRequiredQty(mat: any): number {
    const plan = Number(mat?.plan_qty ?? 0);
    if (plan > 0) {
      return plan;
    }
    return (
      Number(mat?.deducted_from_RM || 0) +
      Number(mat?.deducted_from_Bulk || 0) +
      Number(mat?.shortage || 0)
    );
  }

  exportMaterialLogExcel(): void {
    if (!this.pendingpo?.length) {
      alert('No material log data to export.');
      return;
    }
    const fileName = 'Material_Planning_Log_' + new Date().toISOString().slice(0, 10) + '.xlsx';
    const header = [
      'Sr.',
      'Material Code',
      'Material Name',
      'Type',
      'Client Group',
      'Vendor Names',
      'Product Code',
      'Product Name',
      'Work Order No.',
      'Plan Month',
      'Shortage',
      'Indent Qty',
      'UOM',
      'Purchase Lead Time (Days)',
      'Purchase Status',
      'Tentative Receiving Date',
      'Indent No.',
      'Indent Raised By',
      'Indent Raised Date',
      'Indent For Plan Month',
      'Indent Status',
    ];
    const data = [
      header,
      ...this.pendingpo.map((mat: any, index: number) => [
        index + 1,
        mat.material_code ?? '',
        mat.material_name ?? '',
        mat.mat_type ?? '',
        mat.client_group ?? '',
        mat.vendor_names ?? '',
        mat.product_code ?? mat.product_codes ?? '',
        mat.product_name ?? mat.product_names ?? '',
        mat.workorder_no ?? mat.workorder_nos ?? '',
        this.formatPlanMonthList(mat.planMonth ?? mat.plan_months),
        Number(mat.shortage ?? 0),
        Number(mat.req_qty ?? 0),
        mat.uom ?? '',
        mat.purchase_lead_time_days ?? mat.lead_time_days ?? '',
        mat.purchase_status ?? '',
        this.formatDisplayDate(mat.tentative_receiving_date),
        (() => {
          const n = this.formatIndentNumber(mat);
          return n === '—' ? '' : n;
        })(),
        mat.indent_raised_by_name ?? '',
        this.formatDisplayDate(mat.indent_raised_on),
        this.formatPlanMonthList(mat.indent_for_plan_month ?? mat.indent_plan_months),
        this.getMatLogIndentLabel(mat),
      ]),
    ];
    const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(data);
    const wb: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Material Planning');
    XLSX.writeFile(wb, fileName);
  }

  getWoIndentStatus(wo: any): { label: string; cssClass: string } {
    const deductions = wo?.Deductions || [];
    if (!deductions.length) {
      return { label: '—', cssClass: 'wolog-wo-status--neutral' };
    }

    const withShortage = deductions.filter((m) => this.hasMaterialShortage(m));
    if (!withShortage.length) {
      return { label: 'No Shortage', cssClass: 'wolog-wo-status--no-shortage' };
    }

    const allShortageRaised = withShortage.every((m) => this.isIndentRaised(m));
    if (allShortageRaised) {
      return { label: 'Indent Raised', cssClass: 'wolog-wo-status--raised' };
    }

    const anyNotRaised = withShortage.some((m) => !this.isIndentRaised(m));
    if (anyNotRaised) {
      return { label: 'Not Raised', cssClass: 'wolog-wo-status--not-raised' };
    }

    return { label: 'Indent Raised', cssClass: 'wolog-wo-status--raised' };
  }

  getPendingWOs() {
    this.loading = true;
    this.service.get('marketing/po.php?type=getPlannedWOLog').subscribe({
      next: (response: any) => {
        this.pendingpo = Array.isArray(response) ? response : [];
        this.pendingpoBackup = [...this.pendingpo];
        this.loading = false;
      },
      error: () => {
        this.pendingpo = [];
        this.pendingpoBackup = [];
        this.loading = false;
      },
    });
  }

  matView() {
    this.prodctWise = false;
    this.matWise = true;
    this.searchText = '';
    this.getPlannedWOMatLog();
  }

  ProdView() {
    this.prodctWise = true;
    this.matWise = false;
    this.searchText = '';
    this.getPendingWOs();
  }

  getPlannedWOMatLog() {
    this.loading = true;
    this.pendingpo = [];
    this.pendingpoBackup = [];
    this.service.get('marketing/po.php?type=getPlannedWOMatLog').subscribe({
      next: (response: any) => {
        if (Array.isArray(response)) {
          this.pendingpo = response;
        } else if (response && Array.isArray(response.data)) {
          this.pendingpo = response.data;
        } else {
          this.pendingpo = [];
        }
        this.pendingpoBackup = [...this.pendingpo];
        this.loading = false;
      },
      error: () => {
        this.pendingpo = [];
        this.pendingpoBackup = [];
        this.loading = false;
        alert('Could not load material planning log.');
      },
    });
  }

  View(wo: any) {
    this.isView = true;
    this.selectedWo = wo;
  }

  updateStat(status: string, data: any) {
    const temp: any = {};
    temp['NewStatus'] = status;
    temp['Data'] = data;
    temp['WO_NO'] = this.selectedWo['workorder_no'];
    this.service
      .post(`marketing/po.php?type=update_wo_status`, JSON.stringify(temp))
      .subscribe((response: any) => {
        if (response.status === 'success') {
          alert('Selected Orders ' + status + ' Successfully');
          this.isView = false;
          this.getPendingWOs();
        } else {
          alert('An error has occurred, please try again');
        }
      });
  }
}
