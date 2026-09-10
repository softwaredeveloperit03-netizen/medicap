import { Component, OnDestroy, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { Subscription, combineLatest } from 'rxjs';
import {
  CHECKLIST_STATUS,
  EFFECTIVE_DATE,
  FORM_B_NO,
  NCR_STATUS,
  OPEN_ITEMS_STATUS,
  PRODUCT_STAGES,
  REVISION_NO,
  SOP_REF,
  YES_NO_NA,
  ChecklistRow,
  MANUAL_PRODUCT,
  createDefaultChecklist,
  defaultFromDate,
  defaultToDate,
  dispositionOptionsForStages,
  formatProductStages,
  getChecklistSections,
  getEmpDisplayName,
  mergeChecklistWithStages,
  parseChecklist,
  parseProductStages,
  STAGE_SECTION_MAP,
  stampNow,
  statusClass,
} from '../sqr.utils';

declare let alertify: any;

@Component({
  selector: 'app-stepwise-review',
  templateUrl: './stepwise-review.component.html',
  styleUrls: ['../sqr.shared.css'],
  providers: [DatePipe],
})
export class StepwiseReviewComponent implements OnInit, OnDestroy {
  formNo = FORM_B_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;
  productStages = PRODUCT_STAGES;
  manualProduct = MANUAL_PRODUCT;
  checklistStatus = CHECKLIST_STATUS;
  yesNoNa = YES_NO_NA;
  ncrStatusOptions = NCR_STATUS;
  openItemsStatusOptions = OPEN_ITEMS_STATUS;

  isLog = false;
  isEdit = false;
  isView = false;
  isQaReview = false;
  recordId = 0;
  dept_head = 'No';

  results: any[] = [];
  pendingQa: any[] = [];
  products: any[] = [];
  productBatches: any[] = [];
  selectedProduct: any = null;
  selectedProductBatch: any = null;
  productSelectMode = 'master';
  batchSelectMode: 'master' | 'manual' = 'manual';
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';

  selectedStages: string[] = ['Finished Product'];
  productStage = 'Finished Product';
  productName = '';
  productCode = '';
  lotNumber = '';
  batchNo = '';
  processingStartDate = '';
  mfgDate = '';
  expDate = '';
  batchSize = '';
  checklist: ChecklistRow[] = createDefaultChecklist(['Finished Product']);
  conditionallyReleased = 'No';
  crNo = '';
  crReleasedBy = '';
  crDate = '';
  ncrList = '';
  ncrStatus = 'Not Applicable';
  openItems = '';
  openItemsStatus = 'Not Applicable';
  analyticalResultsReviewed = '';
  cleaningVerificationReviewed = '';
  lotDisposition = '';
  reviewedBy = '';
  approvedBy = '';
  recordStatus = 'Draft';

  dispositionOptsList: string[] = dispositionOptionsForStages(['Finished Product']);
  checklistSections: { title: string; key: string; items: ChecklistRow[] }[] = [];

  private routeSub?: Subscription;

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private datePipe: DatePipe
  ) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
    this.reviewedBy = getEmpDisplayName();
    this.refreshChecklistSections();
  }

  ngOnInit(): void {
    this.loadRights();
    this.routeSub = combineLatest([this.route.data, this.route.paramMap]).subscribe(() => {
      this.applyRoute();
    });
  }

  ngOnDestroy(): void {
    this.routeSub?.unsubscribe();
  }

  applyRoute(): void {
    const view = this.route.snapshot.data['view'] || 'new';
    this.isLog = view === 'log';
    this.isEdit = view === 'edit';
    this.isView = view === 'view';
    this.isQaReview = view === 'qa-review';
    this.recordId = +this.route.snapshot.paramMap.get('id') || 0;

    if (this.isQaReview || this.isEdit || this.isView) {
      this.getProducts();
      this.loadRecord();
      return;
    }
    if (this.isLog) {
      this.getLog();
      this.loadPendingQa();
      return;
    }
    this.resetForm();
    this.getProducts();
  }

  resetForm(): void {
    this.recordId = 0;
    this.selectedProductBatch = null;
    this.selectedProduct = null;
    this.selectedProductBatch = null;
    this.productBatches = [];
    this.productSelectMode = 'master';
    this.batchSelectMode = 'manual';
    this.selectedStages = ['Finished Product'];
    this.productStage = formatProductStages(this.selectedStages);
    this.productName = '';
    this.productCode = '';
    this.lotNumber = '';
    this.batchNo = '';
    this.processingStartDate = '';
    this.mfgDate = '';
    this.expDate = '';
    this.batchSize = '';
    this.checklist = createDefaultChecklist(this.selectedStages);
    this.conditionallyReleased = 'No';
    this.crNo = '';
    this.crReleasedBy = '';
    this.crDate = '';
    this.ncrList = '';
    this.ncrStatus = 'Not Applicable';
    this.openItems = '';
    this.openItemsStatus = 'Not Applicable';
    this.analyticalResultsReviewed = '';
    this.cleaningVerificationReviewed = '';
    this.lotDisposition = '';
    this.reviewedBy = getEmpDisplayName();
    this.approvedBy = '';
    this.recordStatus = 'Draft';
    this.dispositionOptsList = dispositionOptionsForStages(this.selectedStages);
    this.refreshChecklistSections();
  }

  get showBulkFields(): boolean {
    return this.selectedStages.includes('Bulk') || this.selectedStages.includes('Finished Product');
  }

  isStageSelected(stage: string): boolean {
    return this.selectedStages.includes(stage);
  }

  toggleStage(stage: string, checked: boolean): void {
    if (checked) {
      if (!this.selectedStages.includes(stage)) {
        this.selectedStages = PRODUCT_STAGES.filter(
          (s) => this.selectedStages.includes(s) || s === stage
        );
      }
    } else {
      this.selectedStages = this.selectedStages.filter((s) => s !== stage);
    }
    this.onStagesChange();
  }

  refreshChecklistSections(): void {
    this.checklistSections = getChecklistSections(this.checklist, this.selectedStages);
  }

  trackSection(_index: number, section: { title: string }): string {
    return section.title;
  }

  trackChecklistItem(_index: number, item: ChecklistRow): number {
    return item.item_no;
  }

  loadRights(): void {
    const empId = localStorage.getItem('emp_id');
    if (!empId) return;
    this.service.get('hr/employee.php?type=getrights&emp_id=' + encodeURIComponent(empId)).subscribe((response: any) => {
      const r = Array.isArray(response) && response[0] ? response[0] : {};
      this.dept_head = r.dept_head || 'No';
    });
  }

  getProducts(): void {
    this.service.get('master/product.php?type=getBrandProductsLog').subscribe(
      (response: any) => {
        this.products = Array.isArray(response) ? response : [];
        if (!this.products.length) {
          this.service.get('common.php?type=getProducts').subscribe((res: any) => {
            this.products = Array.isArray(res) ? res : [];
            this.syncSelectedProductFromFields();
          });
        } else {
          this.syncSelectedProductFromFields();
        }
      },
      () => {
        this.service.get('common.php?type=getProducts').subscribe((res: any) => {
          this.products = Array.isArray(res) ? res : [];
          this.syncSelectedProductFromFields();
        });
      }
    );
  }

  syncSelectedProductFromFields(): void {
    if (!this.productName || this.productSelectMode === this.manualProduct) {
      return;
    }
    const match = this.products.find(
      (p) =>
        (p.product_name || p.material_name) === this.productName ||
        (p.product_code || p.material_code) === this.productCode
    );
    if (match) {
      this.selectedProduct = match;
      this.productSelectMode = 'master';
      this.loadBatchesForProduct(() => this.syncSelectedProductBatch());
    }
  }

  onProductChange(): void {
    if (this.selectedProduct === this.manualProduct) {
      this.productSelectMode = this.manualProduct;
      this.selectedProduct = null;
      this.productName = '';
      this.productCode = '';
      this.clearBatchFields();
      this.productBatches = [];
      this.batchSelectMode = 'manual';
      return;
    }
    if (!this.selectedProduct) {
      this.productName = '';
      this.productCode = '';
      this.clearBatchFields();
      this.productBatches = [];
      this.batchSelectMode = 'manual';
      return;
    }
    this.productSelectMode = 'master';
    this.productName = this.selectedProduct.product_name || this.selectedProduct.material_name || '';
    this.productCode = this.selectedProduct.product_code || this.selectedProduct.material_code || '';
    this.clearBatchFields();
    this.loadBatchesForProduct();
  }

  loadBatchesForProduct(afterLoad?: () => void): void {
    if (!this.productCode && !this.productName) {
      this.productBatches = [];
      this.batchSelectMode = 'manual';
      afterLoad?.();
      return;
    }
    const url =
      'qa/stepwiseQaRelease.php?type=getBatchesForProduct&product_code=' +
      encodeURIComponent(this.productCode || '') +
      '&product_name=' +
      encodeURIComponent(this.productName || '');
    this.service.get(url).subscribe((response: any) => {
      this.productBatches = response?.batches || [];
      if (this.productBatches.length) {
        this.batchSelectMode = 'master';
        this.syncSelectedProductBatch();
        if (!this.selectedProductBatch && this.productBatches.length === 1) {
          this.selectedProductBatch = this.productBatches[0];
          this.applyBatchRow(this.selectedProductBatch);
        }
      } else {
        this.batchSelectMode = 'manual';
        this.selectedProductBatch = null;
      }
      afterLoad?.();
    });
  }

  syncSelectedProductBatch(): void {
    if (!this.productBatches.length) {
      return;
    }
    const lot = (this.lotNumber || '').trim();
    const batch = (this.batchNo || '').trim();
    if (!lot && !batch) {
      return;
    }
    const match = this.productBatches.find(
      (b) =>
        (b.lot_number || '').trim() === lot ||
        (b.batch_no || '').trim() === batch ||
        (b.batch_no || '').trim() === lot
    );
    if (match) {
      this.selectedProductBatch = match;
      this.batchSelectMode = 'master';
    }
  }

  onProductBatchSelect(): void {
    if (!this.selectedProductBatch) {
      this.clearBatchFields();
      return;
    }
    this.applyBatchRow(this.selectedProductBatch);
    this.batchSelectMode = 'master';
  }

  applyBatchRow(batch: any): void {
    if (!batch) {
      return;
    }
    this.lotNumber = batch.lot_number || batch.batch_no || '';
    this.batchNo = batch.batch_no || this.lotNumber;
    this.mfgDate = batch.mfg_date || '';
    this.expDate = batch.exp_date || '';
    this.batchSize = batch.batch_size || '';
    this.processingStartDate = batch.processing_start_date || batch.mfg_date || '';
  }

  clearBatchFields(): void {
    this.selectedProductBatch = null;
    this.lotNumber = '';
    this.batchNo = '';
    this.mfgDate = '';
    this.expDate = '';
    this.batchSize = '';
    this.processingStartDate = '';
  }

  useManualBatchEntry(): void {
    this.batchSelectMode = 'manual';
    this.selectedProductBatch = null;
    this.clearBatchFields();
  }

  trackProductBatch(_index: number, batch: any): string {
    return (batch.batch_no || '') + '|' + (batch.lot_number || '') + '|' + (batch.mfg_date || '');
  }

  onStagesChange(): void {
    if (!this.selectedStages.length) {
      alertify.error('Select at least one Product Stage');
      return;
    }
    this.productStage = formatProductStages(this.selectedStages);
    this.checklist = mergeChecklistWithStages(this.checklist, this.selectedStages);
    this.dispositionOptsList = dispositionOptionsForStages(this.selectedStages);
    if (this.lotDisposition && !this.dispositionOptsList.includes(this.lotDisposition)) {
      this.lotDisposition = '';
    }
    this.refreshChecklistSections();
  }

  stampReviewedBy(): void {
    this.reviewedBy = stampNow(this.datePipe);
  }

  getLog(): void {
    this.service
      .get(
        'qa/stepwiseQaRelease.php?type=getStepwiseReviewLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  loadPendingQa(): void {
    this.service.get('qa/stepwiseQaRelease.php?type=getPendingStepwiseApprovals').subscribe((response: any) => {
      this.pendingQa = Array.isArray(response) ? response : [];
    });
  }

  loadRecord(): void {
    if (!this.recordId) {
      alertify.error('Invalid record');
      this.router.navigate(['/qa/stepwise-qa-release/stepwise/log']);
      return;
    }
    this.service.get('qa/stepwiseQaRelease.php?type=getStepwiseReviewById&id=' + this.recordId).subscribe((response: any) => {
      if (!response?.id) {
        alertify.error('Record not found');
        this.router.navigate(['/qa/stepwise-qa-release/stepwise/log']);
        return;
      }
      if (this.isQaReview && response.status !== 'Pending QA Manager') {
        alertify.error('This record is not pending QA Manager approval');
        this.router.navigate(['/qa/stepwise-qa-release/stepwise/log']);
        return;
      }
      if (this.isEdit && response.status !== 'Draft') {
        alertify.error('Only draft records can be edited');
        this.router.navigate(['/qa/stepwise-qa-release/stepwise/log']);
        return;
      }
      this.populateFromRecord(response);
    });
  }

  populateFromRecord(record: any): void {
    this.recordId = record.id;
    this.selectedStages = parseProductStages(record.product_stage);
    this.productStage = formatProductStages(this.selectedStages);
    this.productName = record.product_name || '';
    this.productCode = record.product_code || '';
    this.lotNumber = record.lot_number || '';
    this.batchNo = record.batch_no || '';
    this.processingStartDate = record.processing_start_date || '';
    this.mfgDate = record.mfg_date || '';
    this.expDate = record.exp_date || '';
    this.batchSize = record.batch_size || '';
    this.checklist = parseChecklist(record.checklist_data, this.selectedStages);
    this.conditionallyReleased = record.conditionally_released || 'No';
    this.crNo = record.cr_no || '';
    this.crReleasedBy = record.cr_released_by || '';
    this.crDate = record.cr_date || '';
    this.ncrList = record.ncr_list || '';
    this.ncrStatus = record.ncr_status || 'Not Applicable';
    this.openItems = record.open_items || '';
    this.openItemsStatus = record.open_items_status || 'Not Applicable';
    this.analyticalResultsReviewed = record.analytical_results_reviewed || '';
    this.cleaningVerificationReviewed = record.cleaning_verification_reviewed || '';
    this.lotDisposition = record.lot_disposition || '';
    this.reviewedBy = record.reviewed_by || '';
    this.approvedBy = record.approved_by || '';
    this.recordStatus = record.status || 'Draft';
    this.dispositionOptsList = dispositionOptionsForStages(this.selectedStages);
    this.syncSelectedProductFromFields();
    if (this.productName && !this.selectedProduct) {
      this.productSelectMode = this.manualProduct;
      this.batchSelectMode = 'manual';
    } else if (this.productCode || this.productName) {
      this.loadBatchesForProduct(() => this.syncSelectedProductBatch());
    }
    this.refreshChecklistSections();
  }

  buildPayload(status: string): any {
    return {
      id: this.recordId || 0,
      product_stage: formatProductStages(this.selectedStages),
      product_code: this.productCode,
      product_name: this.productName,
      lot_number: this.lotNumber,
      batch_no: this.batchNo || this.lotNumber,
      processing_start_date: this.processingStartDate,
      mfg_date: this.mfgDate,
      exp_date: this.expDate,
      batch_size: this.batchSize,
      checklist_data: this.checklist,
      conditionally_released: this.conditionallyReleased,
      cr_no: this.crNo,
      cr_released_by: this.crReleasedBy,
      cr_date: this.crDate,
      ncr_list: this.ncrList,
      ncr_status: this.ncrStatus,
      open_items: this.openItems,
      open_items_status: this.openItemsStatus,
      analytical_results_reviewed: this.analyticalResultsReviewed,
      cleaning_verification_reviewed: this.cleaningVerificationReviewed,
      lot_disposition: this.lotDisposition,
      reviewed_by: this.reviewedBy,
      status,
    };
  }

  validateForm(submit: boolean): boolean {
    if (!this.selectedStages.length) {
      alertify.error('Select at least one Product Stage');
      return false;
    }
    if (!this.productName.trim() || !this.lotNumber.trim()) {
      alertify.error('Product Name and Lot # are required');
      return false;
    }
    if (submit) {
      if (!this.lotDisposition) {
        alertify.error('Please select Lot Disposition');
        return false;
      }
      const visibleItems = this.checklist.filter((c) =>
        this.selectedStages.some((s) => {
          const key = STAGE_SECTION_MAP[s];
          return c.section === key;
        })
      );
      const incomplete = visibleItems.some((c) => !c.status);
      if (incomplete) {
        alertify.error('Please complete all checklist items (Ok / Not Ok / N/A)');
        return false;
      }
      if (!this.reviewedBy.trim()) {
        alertify.error('Reviewed By is required — use Stamp');
        return false;
      }
    }
    return true;
  }

  saveDraft(): void {
    if (!this.validateForm(false)) return;
    this.save('Draft');
  }

  submitForApproval(form: NgForm): void {
    if (form.invalid || !this.validateForm(true)) return;
    this.stampReviewedBy();
    this.save('Pending QA Manager');
  }

  save(status: string): void {
    const payload = this.buildPayload(status);
    this.service.post('qa/stepwiseQaRelease.php?type=saveStepwiseReview', JSON.stringify(payload)).subscribe((response: any) => {
      if (response?.status === 'success') {
        alertify.success(status === 'Draft' ? 'Draft saved' : 'Submitted for QA Manager approval');
        this.router.navigate(['/qa/stepwise-qa-release/stepwise/log']);
      } else {
        alertify.error(response?.message || 'Failed to save');
      }
    });
  }

  approve(): void {
    this.service
      .post('qa/stepwiseQaRelease.php?type=approveStepwiseReview', JSON.stringify({ id: this.recordId, action: 'approve' }))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Stepwise review approved');
          this.router.navigate(['/qa/stepwise-qa-release/approval']);
        } else {
          alertify.error(response?.message || 'Approval failed');
        }
      });
  }

  reject(): void {
    this.service
      .post('qa/stepwiseQaRelease.php?type=approveStepwiseReview', JSON.stringify({ id: this.recordId, action: 'reject' }))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Stepwise review rejected');
          this.router.navigate(['/qa/stepwise-qa-release/approval']);
        } else {
          alertify.error(response?.message || 'Rejection failed');
        }
      });
  }

  view(record: any): void {
    this.router.navigate(['/qa/stepwise-qa-release/stepwise/view', record.id]);
  }

  edit(record: any): void {
    this.router.navigate(['/qa/stepwise-qa-release/stepwise/edit', record.id]);
  }

  goQaReview(record: any): void {
    this.router.navigate(['/qa/stepwise-qa-release/stepwise/qa-review', record.id]);
  }

  closeView(): void {
    this.router.navigate(['/qa/stepwise-qa-release/stepwise/log']);
  }

  downloadForm(id: number): void {
    this.service.open('qa/stepwiseQaRelease.php?type=downloadStepwiseReviewForm&id=' + id);
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }
}
