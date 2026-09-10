import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  isView = false;
  loading = false;
  results: any[] = [];
  selectedResult: any = {};
  rights: any;
  canApprove = false;
  Reject_remark = '';
  isOpen = false;
  rejectQuotationHdrId: number | string = '';

  isQuotationViewOpen = false;
  viewQuotationHdr: any = null;
  viewQuotationLoading = false;

  plant_id: any;
  currentPage = 1;
  pageSize = 10;

  constructor(private service: DataAccessService) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
  }

  ngOnInit() {
    this.get_rights();
    this.loadMaterialsForApproval();
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  onPageChange(page: number) {
    this.currentPage = page;
  }

  viewf() {
    this.isView = false;
    this.currentPage = 1;
    this.pageSize = 10;
  }

  get_rights() {
    this.service.get(
      'hr/employee.php?type=getrights&emp_id=' +
        localStorage.getItem('emp_id') +
        '&dep_name=' +
        encodeURIComponent(localStorage.getItem('department') || '')
    ).subscribe((response: any) => {
      const r = Array.isArray(response) && response[0] ? response[0] : {};
      this.rights = response;
      this.canApprove =
        r.isapprover === 'Yes' ||
        r.ischecker === 'Yes' ||
        r.user_access === 'Grant' ||
        r.isuser === 'Yes';
    });

    // Fallback: module-specific rights (some plants use this mapping)
    this.service.get(
      'hr/employee.php?type=getrights&module_name=Quotation&form_type=approval&form_name=Quotation Approval&user_access=Grant&emp_id=' +
        localStorage.getItem('emp_id')
    ).subscribe((response: any) => {
      const r = Array.isArray(response) && response[0] ? response[0] : {};
      if (
        r.user_access === 'Grant' ||
        r.isapprover === 'Yes' ||
        r.ischecker === 'Yes'
      ) {
        this.canApprove = true;
      }
    });
  }

  loadMaterialsForApproval(onLoaded?: () => void) {
    this.loading = true;
    this.service
      .get('purchase/quotation.php?type=getQuotationsForApprovalByMaterial&plant_id=' + this.plant_id)
      .subscribe({
        next: (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
          if (onLoaded) {
            onLoaded();
          }
        },
        error: () => {
          this.results = [];
          this.loading = false;
          alertify.error('Failed to load quotations for approval');
        }
      });
  }

  view(row: any) {
    this.selectedResult = row || {};
    this.isView = true;
  }

  quotationHdrId(quotation: any): string | number {
    return quotation?.quotation_hdr_id ?? quotation?.id;
  }

  getQuotationStatus(quotation: any): string {
    const s = String(quotation?.status || '').toLowerCase();
    if (s === 'approve' || s === 'approved') {
      return 'Approved';
    }
    if (s === 'reject' || s === 'rejected') {
      return 'Rejected';
    }
    return 'Pending';
  }

  isPendingQuotation(quotation: any): boolean {
    const s = String(quotation?.status ?? '').trim().toLowerCase();
    return s === 'pending' || s === '';
  }

  isApprovedQuotation(quotation: any): boolean {
    return this.getQuotationStatus(quotation) === 'Approved';
  }

  hasUploadedDoc(quotation: any): boolean {
    const url = quotation?.doc_url;
    if (!url) {
      return false;
    }
    const s = String(url).trim();
    return s !== '' && s.toUpperCase() !== 'NA';
  }

  viewUploadedQuotation(quotation: any) {
    if (!this.hasUploadedDoc(quotation)) {
      alertify.warning('No uploaded quotation file available');
      return;
    }
    const fullUrl = this.service.url + '../../upload/quotation/' + quotation.doc_url;
    window.open(fullUrl, '_blank');
  }

  viewQuotation(quotation: any) {
    const hdrId = this.quotationHdrId(quotation);
    if (!hdrId) {
      alertify.error('Invalid quotation record');
      return;
    }
    this.viewQuotationLoading = true;
    this.service
      .get('purchase/quotation.php?type=getQuotationHdrDetails&id=' + encodeURIComponent(String(hdrId)))
      .subscribe({
        next: (response: any) => {
          this.viewQuotationLoading = false;
          if (!response || !response.id) {
            alertify.error('Quotation details not found');
            return;
          }
          this.viewQuotationHdr = response;
          this.isQuotationViewOpen = true;
        },
        error: () => {
          this.viewQuotationLoading = false;
          alertify.error('Failed to load quotation details');
        }
      });
  }

  closeQuotationView() {
    this.isQuotationViewOpen = false;
    this.viewQuotationHdr = null;
  }

  quotationHdrMaterialType(): string {
    const mats = this.viewQuotationHdr?.materials;
    if (!Array.isArray(mats) || mats.length === 0) {
      return '';
    }
    return mats[0].material_type || '';
  }

  openRejectModal(quotation: any) {
    this.rejectQuotationHdrId = this.quotationHdrId(quotation);
    this.Reject_remark = '';
    this.isOpen = true;
  }

  approveQuotation(quotation: any) {
    this.updateQuotation('approve', quotation);
  }

  updateQuotation(status: string, quotation?: any) {
    const hdrId = quotation ? this.quotationHdrId(quotation) : this.rejectQuotationHdrId;
    if (!hdrId) {
      alertify.error('Invalid quotation record');
      return;
    }

    this.service
      .get(
        'purchase/quotation.php?type=updateQuotation&status=' +
          status +
          '&id=' +
          hdrId +
          '&reject_remark=' +
          encodeURIComponent(this.Reject_remark || '')
      )
      .subscribe((response: any) => {
        if (response['status'] === 'success') {
          alertify.success('Record updated successfully');
          this.isOpen = false;
          this.Reject_remark = '';
          this.rejectQuotationHdrId = '';
          this.refreshAfterUpdate();
        } else {
          alertify.error('Failed: An error occurred, please try again!');
        }
      });
  }

  private refreshAfterUpdate() {
    const materialCode = this.selectedResult?.material_code;
    const wasViewing = this.isView;

    this.loadMaterialsForApproval(() => {
      if (!wasViewing || !materialCode) {
        return;
      }
      const updated = (this.results || []).find((r: any) => r.material_code === materialCode);
      if (updated) {
        this.selectedResult = updated;
      } else {
        this.isView = false;
      }
    });
  }
}
