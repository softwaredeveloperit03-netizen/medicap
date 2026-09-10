import { Component, OnDestroy, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { Subscription, combineLatest } from 'rxjs';
import {
  EFFECTIVE_DATE,
  FORM_A_NO,
  REVISION_NO,
  SOP_REF,
  YES_NO_NA,
  InprocessRecordRow,
  createDefaultInprocessRecords,
  defaultFromDate,
  defaultToDate,
  getEmpDisplayName,
  parseInprocessRecords,
  stampNow,
  statusClass,
} from '../sqr.utils';

declare let alertify: any;

@Component({
  selector: 'app-final-release',
  templateUrl: './final-release.component.html',
  styleUrls: ['../sqr.shared.css'],
  providers: [DatePipe],
})
export class FinalReleaseComponent implements OnInit, OnDestroy {
  formNo = FORM_A_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;
  yesNoNa = YES_NO_NA;

  isLog = false;
  isView = false;
  isQaReview = false;
  recordId = 0;
  dept_head = 'No';

  results: any[] = [];
  pendingFinal: any[] = [];
  approvedStepwise: any[] = [];
  selectedRecord: any = null;
  selectedStepwise: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';

  stepwiseReviewId = 0;
  productName = '';
  productCode = '';
  lotNumber = '';
  lotCount = '';
  inprocessRecords: InprocessRecordRow[] = createDefaultInprocessRecords();
  oosNcrSummary = '';
  allLirsNcrsCompleted = '';
  finalProductAnalysisSigned = '';
  auditTrailReviewed = '';
  labelledSubmissionOnly = '';
  labelledCommercialUse = '';
  retainSamplesLogged = '';
  releaseDate = '';
  qaDirector = '';
  recordStatus = 'Draft';

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
    this.releaseDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
    this.qaDirector = getEmpDisplayName();
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
    this.isView = view === 'view';
    this.isQaReview = view === 'qa-review';
    this.recordId = +this.route.snapshot.paramMap.get('id') || 0;

    if (this.isQaReview || this.isView) {
      this.loadRecord();
      return;
    }
    if (this.isLog) {
      this.getLog();
      this.loadPendingFinal();
      return;
    }
    this.resetForm();
    this.loadApprovedStepwise();
  }

  resetForm(): void {
    this.recordId = 0;
    this.selectedStepwise = null;
    this.stepwiseReviewId = 0;
    this.productName = '';
    this.productCode = '';
    this.lotNumber = '';
    this.lotCount = '';
    this.inprocessRecords = createDefaultInprocessRecords();
    this.oosNcrSummary = '';
    this.allLirsNcrsCompleted = '';
    this.finalProductAnalysisSigned = '';
    this.auditTrailReviewed = '';
    this.labelledSubmissionOnly = '';
    this.labelledCommercialUse = '';
    this.retainSamplesLogged = '';
    this.releaseDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
    this.qaDirector = getEmpDisplayName();
    this.recordStatus = 'Draft';
  }

  loadRights(): void {
    const empId = localStorage.getItem('emp_id');
    if (!empId) return;
    this.service.get('hr/employee.php?type=getrights&emp_id=' + encodeURIComponent(empId)).subscribe((response: any) => {
      const r = Array.isArray(response) && response[0] ? response[0] : {};
      this.dept_head = r.dept_head || 'No';
    });
  }

  loadApprovedStepwise(): void {
    this.service.get('qa/stepwiseQaRelease.php?type=getApprovedStepwiseForFinal').subscribe((response: any) => {
      this.approvedStepwise = Array.isArray(response) ? response : [];
    });
  }

  onStepwiseSelect(): void {
    if (!this.selectedStepwise) return;
    this.stepwiseReviewId = this.selectedStepwise.id;
    this.productName = this.selectedStepwise.product_name || '';
    this.productCode = this.selectedStepwise.product_code || '';
    this.lotNumber = this.selectedStepwise.lot_number || '';
  }

  stampQaDirector(): void {
    this.qaDirector = stampNow(this.datePipe);
  }

  stampInprocess(stage: string): void {
    const row = this.inprocessRecords.find((r) => r.stage === stage);
    if (row) {
      row.checked_by = stampNow(this.datePipe);
      row.checked_date = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
    }
  }

  getLog(): void {
    this.service
      .get(
        'qa/stepwiseQaRelease.php?type=getFinalReleaseLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  loadPendingFinal(): void {
    this.service.get('qa/stepwiseQaRelease.php?type=getPendingFinalReleaseApprovals').subscribe((response: any) => {
      this.pendingFinal = Array.isArray(response) ? response : [];
    });
  }

  loadRecord(): void {
    if (!this.recordId) {
      alertify.error('Invalid record');
      this.router.navigate(['/qa/stepwise-qa-release/final/log']);
      return;
    }
    this.service.get('qa/stepwiseQaRelease.php?type=getFinalReleaseById&id=' + this.recordId).subscribe((response: any) => {
      if (!response?.id) {
        alertify.error('Record not found');
        this.router.navigate(['/qa/stepwise-qa-release/final/log']);
        return;
      }
      if (this.isQaReview && response.status !== 'Pending Final Release') {
        alertify.error('This record is not pending final release approval');
        this.router.navigate(['/qa/stepwise-qa-release/final/log']);
        return;
      }
      this.populateFromRecord(response);
    });
  }

  populateFromRecord(record: any): void {
    this.recordId = record.id;
    this.stepwiseReviewId = record.stepwise_review_id || 0;
    this.productName = record.product_name || '';
    this.productCode = record.product_code || '';
    this.lotNumber = record.lot_number || '';
    this.lotCount = record.lot_count || '';
    this.inprocessRecords = parseInprocessRecords(record.inprocess_records);
    this.oosNcrSummary = record.oos_ncr_summary || '';
    this.allLirsNcrsCompleted = record.all_lirs_ncrs_completed || '';
    this.finalProductAnalysisSigned = record.final_product_analysis_signed || '';
    this.auditTrailReviewed = record.audit_trail_reviewed || '';
    this.labelledSubmissionOnly = record.labelled_submission_only || '';
    this.labelledCommercialUse = record.labelled_commercial_use || '';
    this.retainSamplesLogged = record.retain_samples_logged || '';
    this.releaseDate = record.release_date || '';
    this.qaDirector = record.qa_director || '';
    this.recordStatus = record.status || 'Draft';
  }

  buildPayload(status: string): any {
    return {
      id: this.recordId || 0,
      stepwise_review_id: this.stepwiseReviewId,
      product_code: this.productCode,
      product_name: this.productName,
      lot_number: this.lotNumber,
      lot_count: this.lotCount,
      inprocess_records: this.inprocessRecords,
      oos_ncr_summary: this.oosNcrSummary,
      all_lirs_ncrs_completed: this.allLirsNcrsCompleted,
      final_product_analysis_signed: this.finalProductAnalysisSigned,
      audit_trail_reviewed: this.auditTrailReviewed,
      labelled_submission_only: this.labelledSubmissionOnly,
      labelled_commercial_use: this.labelledCommercialUse,
      retain_samples_logged: this.retainSamplesLogged,
      release_date: this.releaseDate,
      qa_director: this.qaDirector,
      status,
    };
  }

  validateForm(submit: boolean): boolean {
    if (!this.productName.trim() || !this.lotNumber.trim()) {
      alertify.error('Product Name and Lot # are required');
      return false;
    }
    if (!this.stepwiseReviewId && !this.recordId) {
      alertify.error('Please select an approved Finished Product stepwise review');
      return false;
    }
    if (submit) {
      const requiredFields = [
        this.allLirsNcrsCompleted,
        this.finalProductAnalysisSigned,
        this.auditTrailReviewed,
        this.retainSamplesLogged,
      ];
      if (requiredFields.some((f) => !f)) {
        alertify.error('Please complete all final release checklist items');
        return false;
      }
      if (!this.labelledSubmissionOnly && !this.labelledCommercialUse) {
        alertify.error('Select at least one release label type (Submission Only or Commercial Use)');
        return false;
      }
      if (!this.qaDirector.trim()) {
        alertify.error('QA Director signature required — use Stamp');
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
    this.stampQaDirector();
    this.save('Pending Final Release');
  }

  save(status: string): void {
    const payload = this.buildPayload(status);
    this.service.post('qa/stepwiseQaRelease.php?type=saveFinalRelease', JSON.stringify(payload)).subscribe((response: any) => {
      if (response?.status === 'success') {
        alertify.success(status === 'Draft' ? 'Draft saved' : 'Submitted for QA Director final release approval');
        this.router.navigate(['/qa/stepwise-qa-release/final/log']);
      } else {
        alertify.error(response?.message || 'Failed to save');
      }
    });
  }

  approve(): void {
    this.service
      .post('qa/stepwiseQaRelease.php?type=approveFinalRelease', JSON.stringify({ id: this.recordId, action: 'approve' }))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Lot released');
          this.router.navigate(['/qa/stepwise-qa-release/approval']);
        } else {
          alertify.error(response?.message || 'Release failed');
        }
      });
  }

  reject(): void {
    this.service
      .post('qa/stepwiseQaRelease.php?type=approveFinalRelease', JSON.stringify({ id: this.recordId, action: 'reject' }))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Final release rejected');
          this.router.navigate(['/qa/stepwise-qa-release/approval']);
        } else {
          alertify.error(response?.message || 'Rejection failed');
        }
      });
  }

  view(record: any): void {
    this.router.navigate(['/qa/stepwise-qa-release/final/view', record.id]);
  }

  goQaReview(record: any): void {
    this.router.navigate(['/qa/stepwise-qa-release/final/qa-review', record.id]);
  }

  closeView(): void {
    this.router.navigate(['/qa/stepwise-qa-release/final/log']);
  }

  downloadForm(id: number): void {
    this.service.open('qa/stepwiseQaRelease.php?type=downloadFinalReleaseForm&id=' + id);
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }
}
