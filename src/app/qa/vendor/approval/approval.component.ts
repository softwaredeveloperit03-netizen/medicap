import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-qa-vendor-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
})
export class ApprovalComponent implements OnInit {

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPendingVendorForQaApproval();
  }

  results: any[] = [];
  loading = false;

  getPendingVendorForQaApproval(): void {
    this.loading = true;
    this.service.get('purchase/vendor.php?type=getPendingVendorForQaApproval').subscribe(
      (response) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.loading = false;
      }
    );
  }

  isView = false;
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

  statusLabel(row: any): string {
    if (row?.status === 'Corrected Details' || this.correctionChanges(row).length > 0) {
      return 'Corrected Details';
    }
    return row?.status || '—';
  }

  isCorrectedResubmit(row: any): boolean {
    return row?.status === 'Corrected Details' || this.correctionChanges(row).length > 0;
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

  showVendorIndiaTax(v: any): boolean {
    if (!v || v.country !== 'India') {
      return false;
    }
    const g = (x: any) => x != null && String(x).trim() !== '';
    return g(v.gst_applicable) || g(v.scode) || g(v.gst_no) || g(v.panNo);
  }

  showCorporateIndiaTax(v: any): boolean {
    if (!v || v.c_country !== 'India') {
      return false;
    }
    const g = (x: any) => x != null && String(x).trim() !== '';
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

  updateVendorStatusFromQa(status: string, remark?: string): void {
    const id = this.selectedResult?.id;
    if (id == null || id === '') {
      alertify.error('Invalid vendor record.');
      return;
    }
    const temp: any = { status, id };
    if (remark != null && String(remark).trim() !== '') {
      temp.status_remark = String(remark).trim();
    }
    this.service.post('purchase/vendor.php?type=updateVendorStatusFromQa', JSON.stringify(temp)).subscribe(
      (response: any) => {
        if (response?.status === 'success') {
          const msg =
            status === 'RETURN_FOR_PURCHASE_EDIT'
              ? 'Vendor sent to purchase for editing.'
              : 'Vendor updated successfully.';
          alertify.success(msg);
          this.getPendingVendorForQaApproval();
          this.isView = false;
        } else if (response?.status === 'no rows updated') {
          alertify.error('This vendor is no longer waiting for QA (refresh the list).');
          this.getPendingVendorForQaApproval();
          this.isView = false;
        } else {
          alertify.error('Failed: An error occurred, please try again.');
        }
      },
      () => {
        alertify.error('Failed: An error occurred, please try again.');
      }
    );
  }

  isCorrectionOpen = false;
  correctionRemark = '';

  /** Open the remark modal before sending the vendor back to purchase for corrections. */
  sendToPurchaseForEditing(): void {
    this.correctionRemark = '';
    this.isCorrectionOpen = true;
  }

  /** Submit the correction remark and send the vendor to purchase's “for editing” queue. */
  submitCorrection(): void {
    if (!this.correctionRemark || this.correctionRemark.trim() === '') {
      alertify.error('Please enter a remark for the correction.');
      return;
    }
    this.updateVendorStatusFromQa('RETURN_FOR_PURCHASE_EDIT', this.correctionRemark);
    this.isCorrectionOpen = false;
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
        if (key === 'entry_date' || key === 'entryDate' || key === 'entryOn') {
          const dateStr =
            typeof value === 'string' ? value : value instanceof Date ? value.toISOString().slice(0, 10) : '';
          return dateStr && dateStr.toLowerCase().includes(query);
        }
        return value.toString().toLowerCase().includes(query);
      });
    });
  }
}
