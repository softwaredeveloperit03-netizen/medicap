import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-comparative',
  templateUrl: './comparative.component.html',
  styleUrls: ['./comparative.component.css', '../../shared/purchase-vapp-host.css']
})
export class ComparativeComponent implements OnInit {
  materials: any[] = [];
  isView = false;
  results: any[] = [];
  item: any[] = [];
  types: any[] = [];
  selectedResult: any = {};
  material_subtype = '';

  plant_id: any;
  loading = false;

  currentPage = 1;
  pageSize = 10;

  /** approved = historical comparative; pending = new entries awaiting approval */
  viewMode: 'pending' | 'approved' = 'pending';
  /** Quotation = RM/PM; General = general material quotation */
  quotationCategory: 'Quotation' | 'General' = 'Quotation';
  isapprover = 'No';
  ischecker = 'No';

  isOpen = false;
  Reject_remark = '';
  rejectQuotationHdrId: number | string = '';

  isQuotationViewOpen = false;
  viewQuotationHdr: any = null;
  viewQuotationLoading = false;

  constructor(private service: DataAccessService, private route: ActivatedRoute) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
  }

  ngOnInit() {
    this.applyRouteParams(this.route.snapshot.queryParamMap);
    this.route.queryParamMap.subscribe((params) => {
      this.applyRouteParams(params);
      this.loadComparatives();
    });
    this.getMaterialSub();
    this.get_rights();
  }

  private applyRouteParams(params: { get: (key: string) => string | null }) {
    const category = params.get('quotation_category');
    if (category === 'General' || category === 'Quotation') {
      this.quotationCategory = category;
    }
    const mode = params.get('mode');
    if (mode === 'pending' || mode === 'approved') {
      this.viewMode = mode;
    }
  }

  get quotationCategoryLabel(): string {
    return this.quotationCategory === 'General' ? 'General Quotation' : 'Quotation (RM/PM)';
  }

  get canApprove(): boolean {
    return this.isapprover === 'Yes' || this.ischecker === 'Yes';
  }

  get isPendingMode(): boolean {
    return this.viewMode === 'pending';
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  onPageChange(page: number) {
    this.currentPage = page;
  }

  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10);
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
      this.isapprover = r.isapprover || 'No';
      this.ischecker = r.ischecker || 'No';
      const modeFromRoute = this.route.snapshot.queryParamMap.get('mode');
      if (this.canApprove && modeFromRoute !== 'approved') {
        this.viewMode = 'pending';
      }
      this.loadComparatives();
    });
  }

  setViewMode(mode: 'pending' | 'approved') {
    this.viewMode = mode;
    this.isView = false;
    this.currentPage = 1;
    this.loadComparatives();
  }

  setQuotationCategory(category: 'Quotation' | 'General') {
    this.quotationCategory = category;
    this.isView = false;
    this.currentPage = 1;
    this.loadComparatives();
  }

  private comparativeApiParams(): string {
    return 'quotation_category=' + encodeURIComponent(this.quotationCategory);
  }

  loadComparatives() {
    if (this.isPendingMode) {
      this.getComparativesPending();
    } else {
      this.getComparatives();
    }
  }

  getComparatives() {
    this.loading = true;
    this.service.get('purchase/quotation.php?type=getComparatives&' + this.comparativeApiParams()).subscribe({
      next: (response: any) => {
        this.results = this.normalizeComparativeResponse(response);
        this.filterMaterial();
        this.loading = false;
        this.showEmptyHint();
      },
      error: () => {
        this.results = [];
        this.item = [];
        this.loading = false;
        alertify.error('Failed to load comparative quotations');
      }
    });
  }

  getComparativesPending() {
    this.loading = true;
    this.service.get('purchase/quotation.php?type=getComparativesPending&' + this.comparativeApiParams()).subscribe({
      next: (response: any) => {
        this.results = this.normalizeComparativeResponse(response);
        this.filterMaterial();
        this.loading = false;
        this.showEmptyHint();
      },
      error: () => {
        this.results = [];
        this.item = [];
        this.loading = false;
        alertify.error('Failed to load comparative quotations');
      }
    });
  }

  getMaterials(value: string) {
    this.service.get('common.php?type=getMaterialsByType&material_subtype=' + value).subscribe((response: any) => {
      this.materials = Array.isArray(response) ? response : [];
    });
  }

  uploadQuatation(url: string) {
    const fullUrl = this.service.url + '../../upload/quotation/' + url;
    window.open(fullUrl, '_blank');
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
    this.uploadQuatation(quotation.doc_url);
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

  getMaterialSub() {
    this.service.get('master/materialtype.php?type=getMaterialtype').subscribe((response: any) => {
      this.types = Array.isArray(response) ? response : [];
    });
  }

  view(row: any) {
    this.selectedResult = row || {};
    this.isView = true;
  }

  private normalizeComparativeResponse(response: any): any[] {
    if (Array.isArray(response)) {
      return response;
    }
    if (response && typeof response === 'object' && Array.isArray(response.data)) {
      return response.data;
    }
    return [];
  }

  filterMaterial() {
    this.item = [...(this.results || [])];
  }

  clearFilter() {
    this.material_subtype = '';
    this.filterMaterial();
  }

  quotationNoDisplay(quotation: any): string {
    if (!quotation) {
      return '';
    }
    if (String(this.plant_id) === '67') {
      return (quotation.vendor_quotation_no || '').toString().trim();
    }
    return (quotation.quotation_no || quotation.vendor_quotation_no || '').toString().trim();
  }

  quotationHdrNoDisplay(hdr: any): string {
    if (!hdr) {
      return '';
    }
    if (String(this.plant_id) === '67') {
      return (hdr.vendor_quotation_no || '').toString().trim();
    }
    return (hdr.quotation_no || hdr.vendor_quotation_no || '').toString().trim();
  }

  quotationHdrId(quotation: any): string | number {
    return quotation?.quotation_hdr_id ?? quotation?.id;
  }

  isPendingQuotation(quotation: any): boolean {
    const s = String(quotation?.status || '').trim().toLowerCase();
    return s === 'pending' || s === '';
  }

  isApprovedQuotation(quotation: any): boolean {
    const s = String(quotation?.status || '').trim().toLowerCase();
    return s === 'approve' || s === 'approved';
  }

  private showEmptyHint(): void {
    if (!this.item.length && !this.loading) {
      const tab = this.isPendingMode ? 'For Approval' : 'Approved';
      alertify.warning(
        'No ' + tab + ' entries for ' + this.quotationCategoryLabel + '. Try switching the quotation type dropdown.'
      );
    }
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
          this.isView = false;
          this.isOpen = false;
          this.Reject_remark = '';
          this.rejectQuotationHdrId = '';
          if (String(status).toLowerCase() === 'approve') {
            this.viewMode = 'approved';
          }
          this.loadComparatives();
        } else {
          alertify.error('Failed: An error occurred, please try again!');
        }
      });
  }

  openRejectModal(quotation: any) {
    this.rejectQuotationHdrId = this.quotationHdrId(quotation);
    this.Reject_remark = '';
    this.isOpen = true;
  }

  approveQuotation(quotation: any) {
    if (!this.canApprove) {
      return;
    }
    this.updateQuotation('approve', quotation);
  }
}
