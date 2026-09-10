import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPendingVendorsForPurchaseApproval();
  }

  results: any[] = [];
  loading = false;

  getPendingVendorsForPurchaseApproval(): void {
    this.loading = true;
    this.service.get('purchase/vendor.php?type=getPendingVendorsForPurchaseApproval').subscribe(response => {
      this.results = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => {
      this.loading = false;
    });
  }

  isView = false;
  /** Detail row with normalized JSON arrays */
  selectedResult: any = {};

  view(data: any): void {
    const row = { ...data };
    row.other_contact = this.parseJsonArray(row.other_contact);
    row.selectedCurrencies = this.parseJsonArray(row.selectedCurrencies);
    row.correction_log = this.parseCorrectionLog(row.correction_log);
    this.selectedResult = row;
    this.isView = true;
  }

  private parseCorrectionLog(value: any): { corrected_by?: string; corrected_on?: string; qa_remark?: string; changes?: any[] } {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      return {
        corrected_by: value.corrected_by,
        corrected_on: value.corrected_on,
        qa_remark: value.qa_remark,
        changes: Array.isArray(value.changes) ? value.changes : [],
      };
    }
    if (typeof value === 'string' && value.trim()) {
      try {
        const parsed = JSON.parse(value);
        if (parsed && typeof parsed === 'object') {
          return this.parseCorrectionLog(parsed);
        }
      } catch {
        return { changes: [] };
      }
    }
    return { changes: [] };
  }

  statusLabel(status: string): string {
    if (status === 'Corrected Details') {
      return 'Corrected Details';
    }
    return status || '—';
  }

  isCorrectedResubmit(row: any): boolean {
    return row?.status === 'Corrected Details';
  }

  isQualifiedByOwn(row: any): boolean {
    return String(row?.qualifiedBy || '').trim().toLowerCase() === 'own';
  }

  correctionChanges(row: any): any[] {
    const log = row?.correction_log;
    if (log && Array.isArray(log.changes)) {
      return log.changes;
    }
    return [];
  }

  formatCorrectionValue(value: any): string {
    if (value == null || value === '') {
      return '—';
    }
    const str = String(value);
    if (str.startsWith('[') || str.startsWith('{')) {
      try {
        const parsed = JSON.parse(str);
        return JSON.stringify(parsed);
      } catch {
        return str;
      }
    }
    return str;
  }

  private parseJsonArray(value: any): any[] {
    if (Array.isArray(value)) {
      return value;
    }
    if (typeof value === 'string' && value.trim()) {
      try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  regionLabel(country: string): string {
    if (country === 'Canada') {
      return 'Province / territory';
    }
    if (country === 'India') {
      return 'State';
    }
    return 'State / province';
  }

  postalLabel(_country?: string): string {
    return 'Postal Code';
  }

  /** India-only tax IDs (hidden for Canada and other countries) */
  showVendorIndiaTax(v: any): boolean {
    if (!v || v.country !== 'India') {
      return false;
    }
    const g = (x: any) => (x != null && String(x).trim() !== '');
    return g(v.gst_applicable) || g(v.scode) || g(v.gst_no) || g(v.panNo);
  }

  showCorporateIndiaTax(v: any): boolean {
    if (!v || v.c_country !== 'India') {
      return false;
    }
    const g = (x: any) => (x != null && String(x).trim() !== '');
    return g(v.c_gst_applicable) || g(v.c_scode) || g(v.c_gst_no) || g(v.c_panNo);
  }

  currencyCodesDisplay(v: any): string {
    if (!v) {
      return '—';
    }
    const arr = Array.isArray(v.selectedCurrencies)
      ? v.selectedCurrencies
      : this.parseJsonArray(v.selectedCurrencies);
    if (!arr.length) {
      return '—';
    }
    const codes = arr.map((x: any) => (x && x.code) ? x.code : x).filter(Boolean);
    return codes.length ? codes.join(', ') : '—';
  }

  updateVendor(): void {
    const id = this.selectedResult?.id;
    if (id == null || id === '') {
      alertify.error('Invalid vendor record.');
      return;
    }
    this.service.get('purchase/vendor.php?type=approveVendorFromPurchase&id=' + id).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Vendor sent to QA successfully.');
        this.isView = false;
        this.getPendingVendorsForPurchaseApproval();
      } else {
        alertify.error('Failed: An error occurred, please try again.');
      }
    });
  }

  searchQuery = '';

  get filteredMaterials(): any[] {
    if (!this.results || this.results.length === 0) {
      return [];
    }
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }

    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((material) => {
      return Object.entries(material).some(([key, value]) => {
        if (value == null) {
          return false;
        }
        if (key === 'entry_date' || key === 'entryDate') {
          const dateStr = typeof value === 'string' ? value : (value instanceof Date ? value.toISOString().slice(0, 10) : '');
          return dateStr && dateStr.toLowerCase().includes(query);
        }
        return value.toString().toLowerCase().includes(query);
      });
    });
  }
}
